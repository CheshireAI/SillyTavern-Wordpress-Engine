<?php
/**
 * SwarmUI API Integration Handler
 * 
 * Handles image generation, usage tracking, and model management
 * Updated to support SwarmUI session-based authentication
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once 'api-handler-base.php';

class PMV_SwarmUI_API_Handler extends PMV_API_Handler_Base {
    
    private $session_id;
    
    public function __construct() {
        parent::__construct('swarmui');
        add_action('wp_ajax_pmv_get_swarmui_session', array($this, 'ajax_get_swarmui_session'));
        add_action('wp_ajax_nopriv_pmv_get_swarmui_session', array($this, 'ajax_get_swarmui_session'));

        // WebSocket generation handlers (logged-in and guest)
        add_action('wp_ajax_pmv_generate_image_websocket', array($this, 'ajax_generate_image_websocket'));
        add_action('wp_ajax_nopriv_pmv_generate_image_websocket', array($this, 'ajax_generate_image_websocket'));
    }
    
    private function get_new_session() {
        if (empty($this->api_base_url)) {
            return new WP_Error('no_api_url', 'SwarmUI API URL not configured');
        }
        
        $url = trailingslashit($this->api_base_url) . 'API/GetNewSession';

        // Retrieve user token stored in plugin settings (if provided)
        $user_token = get_option('pmv_swarmui_user_token', '');

        // Prepare headers
        $headers = array(
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        );

        // If a user token is set, send it as a cookie for authentication
        if (!empty($user_token)) {
            $headers['Cookie'] = 'swarm_user_token=' . $user_token;
        }

        // SwarmUI expects an (empty) JSON body, so pass an empty object
        $response = wp_remote_post($url, array(
            'timeout'   => 15,
            'sslverify' => false,
            'headers'   => $headers,
            'body'      => '{}'
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($data)) {
            return new WP_Error('invalid_response', 'Empty response from SwarmUI when requesting a new session.');
        }

        // SwarmUI may return an error field when authentication fails
        if (isset($data['error'])) {
            return new WP_Error('swarmui_error', $data['error']);
        }

        if (!isset($data['session_id'])) {
            return new WP_Error('invalid_response', 'No session_id in response - check your SwarmUI user token.');
        }

        $this->session_id = $data['session_id'];
        return $data;
    }
    
    public function ajax_get_swarmui_session() {
        if (!wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
            wp_die('Security check failed');
        }
        
        $result = $this->get_new_session();
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success($result);
    }

    /**
     * Prepare a WebSocket URL and request payload for SwarmUI image generation.
     * This avoids holding the WordPress HTTP request open for long-running jobs.
     */
    public function ajax_generate_image_websocket() {
        if (!wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
        }

        // Ensure we have a valid session
        if (empty($this->session_id)) {
            $session = $this->get_new_session();
            if (is_wp_error($session)) {
                wp_send_json_error(array('message' => $session->get_error_message()));
            }
        }

        // Collect parameters
        $prompt         = sanitize_textarea_field($_POST['prompt'] ?? '');
        $model          = sanitize_text_field($_POST['model'] ?? 'OfficialStableDiffusion/sd_xl_base_1.0');
        $images_count   = intval($_POST['images_count'] ?? 1);
        $width          = intval($_POST['width'] ?? 512);
        $height         = intval($_POST['height'] ?? 512);
        $steps          = intval($_POST['steps'] ?? 20);
        $cfg_scale      = floatval($_POST['cfg_scale'] ?? 7.0);
        $negative       = sanitize_textarea_field($_POST['negative_prompt'] ?? '');

        // Build request payload expected by SwarmUI WS endpoint
        $request_data = array(
            'session_id'     => $this->session_id,
            'images'         => $images_count,
            'prompt'         => $prompt,
            'model'          => basename($model),
            'width'          => $width,
            'height'         => $height,
            'steps'          => $steps,
            'cfg_scale'      => $cfg_scale,
            'negative_prompt'=> $negative,
        );

        // Convert API base URL (http/https) to ws/wss for WebSocket
        $ws_url = trailingslashit($this->api_base_url) . 'API/GenerateText2ImageWS';
        if (str_starts_with($ws_url, 'https://')) {
            $ws_url = str_replace('https://', 'wss://', $ws_url);
        } elseif (str_starts_with($ws_url, 'http://')) {
            $ws_url = str_replace('http://', 'ws://', $ws_url);
        }

        wp_send_json_success(array(
            'websocket_url' => $ws_url,
            'request_data'  => $request_data,
        ));
    }
    
    protected function generate_image($prompt, $model, $images_count, $params) {
        /*
         * Updated logic:
         * 1. Ensure we have a session ID.
         * 2. Attempt generation. If SwarmUI reports an error, refresh the session once and retry.
         * 3. On success, fetch/convert each returned image and store a local copy inside the WordPress
         *    uploads directory (sub-folder "pmv-generated") so that the final URL is served over HTTPS
         *    from this WordPress site – eliminating mixed-content issues.
         */

        // Helper to actually call the SwarmUI REST endpoint
        $do_generation = function($session_id) use ($prompt, $model, $images_count, $params) {
            $url = trailingslashit($this->api_base_url) . 'API/GenerateText2Image';
            $request_data = array(
                'session_id' => $session_id,
                'images'     => $images_count,
                'prompt'     => $prompt,
                'model'      => basename($model),
                'width'      => intval($params['width'] ?? 512),
                'height'     => intval($params['height'] ?? 512),
            );

            // Include user token cookie if available
            $user_token = get_option('pmv_swarmui_user_token', '');
            $headers    = array('Content-Type' => 'application/json');
            if (!empty($user_token)) {
                $headers['Cookie'] = 'swarm_user_token=' . $user_token;
            }

            $response = wp_remote_post(
                $url,
                array(
                    'body'       => wp_json_encode($request_data),
                    'timeout'    => 300,
                    'sslverify'  => false,
                    'headers'    => $headers,
                )
            );

            return $response;
        };

        // ---------------------------------------------------------------------
        // 1. Guarantee a session id exists (create if necessary)
        // ---------------------------------------------------------------------
        if (empty($this->session_id)) {
            $session_result = $this->get_new_session();
            if (is_wp_error($session_result)) {
                return $session_result;
            }
        }

        // ---------------------------------------------------------------------
        // 2. Run generation – allow 1 automatic retry with a fresh session
        // ---------------------------------------------------------------------
        $response = $do_generation($this->session_id);
        $retried  = false;

        // Check for transport-level error first
        if (is_wp_error($response)) {
            // No point in retrying – return immediately
            return $response;
        }

        $body        = wp_remote_retrieve_body($response);
        $result_data = json_decode($body, true);

        // If SwarmUI complains about session, try once more with a new session
        if ((isset($result_data['error']) && stripos($result_data['error'], 'session') !== false) && !$retried) {
            $this->session_id = null; // reset
            $session_result   = $this->get_new_session();
            if (!is_wp_error($session_result)) {
                $response  = $do_generation($this->session_id);
                $retried   = true;
                if (is_wp_error($response)) {
                    return $response; // give up – transport failure
                }
                $body        = wp_remote_retrieve_body($response);
                $result_data = json_decode($body, true);
            }
        }

        // Validate final response structure
        $images_from_api = array();
        if (isset($result_data['images']) && is_array($result_data['images'])) {
            $images_from_api = $result_data['images'];
        } elseif (isset($result_data['image'])) { // single key scenario
            $images_from_api = array($result_data['image']);
        } elseif (is_array($result_data) && isset($result_data[0]) && is_string($result_data[0])) {
            // API might directly return array of strings
            $images_from_api = $result_data;
        }

        if (empty($images_from_api)) {
            return new WP_Error('invalid_response', 'SwarmUI returned an unexpected response');
        }

        // ---------------------------------------------------------------------
        // 3. Persist each image locally & build HTTPS URLs
        // ---------------------------------------------------------------------
        $upload_dir = wp_upload_dir();
        $base_dir   = trailingslashit($upload_dir['basedir']) . 'pmv-generated/';
        $base_url   = trailingslashit($upload_dir['baseurl']) . 'pmv-generated/';

        if (!file_exists($base_dir)) {
            wp_mkdir_p($base_dir);
        }

        $local_urls = array();

        foreach ($images_from_api as $img_index => $img_src) {
            $image_data   = null;
            $extension    = 'png'; // default

            if (strpos($img_src, 'data:image') === 0) {
                // Handle base64 data URI
                if (preg_match('/^data:image\/(\w+);base64,/', $img_src, $matches)) {
                    $extension = strtolower($matches[1]);
                    $img_src   = substr($img_src, strpos($img_src, ',') + 1);
                    $image_data = base64_decode($img_src);
                }
            } else {
                // Determine absolute URL if API returned a relative path
                $absolute_url = $img_src;

                if (!preg_match('/^https?:\/\//i', $img_src)) {
                    // Remove leading slash to avoid double slashes later
                    $relative_path = ltrim($img_src, '/');

                    // Prefer protocol that matches the WordPress site, fall back to plain http
                    $preferred_proto = is_ssl() ? 'https://' : 'http://';

                    // Ensure api_base_url has no protocol so we can prepend preferred one
                    $api_host = preg_replace('/^https?:\/\//i', '', rtrim($this->api_base_url, '/'));

                    $absolute_url = $preferred_proto . $api_host . '/' . $relative_path;
                }

                // Remote URL – fetch binary (first attempt)
                $remote_resp = wp_remote_get($absolute_url, array('timeout' => 120, 'sslverify' => false));

                // If failure and we used HTTPS, retry with HTTP
                if (is_wp_error($remote_resp) || wp_remote_retrieve_response_code($remote_resp) !== 200) {
                    if (stripos($absolute_url, 'https://') === 0) {
                        $absolute_url_http = 'http://' . substr($absolute_url, 8);
                        $remote_resp       = wp_remote_get($absolute_url_http, array('timeout' => 120, 'sslverify' => false));
                    }
                }

                if (!is_wp_error($remote_resp) && wp_remote_retrieve_response_code($remote_resp) === 200) {
                    $image_data = wp_remote_retrieve_body($remote_resp);
                    // Attempt to get extension from content-type
                    $content_type = wp_remote_retrieve_header($remote_resp, 'content-type');
                    if ($content_type && strpos($content_type, 'image/') === 0) {
                        $extension = substr($content_type, 6); // after "image/"
                    } else {
                        // fallback to pathinfo
                        $path_ext = pathinfo(parse_url($img_src, PHP_URL_PATH), PATHINFO_EXTENSION);
                        if ($path_ext) {
                            $extension = strtolower($path_ext);
                        }
                    }
                }
            }

            if (!$image_data) {
                // Skip if unable to retrieve
                continue;
            }

            // Sanitize and generate filename
            $filename   = 'swarmui_' . gmdate('Ymd_His') . '_' . wp_unique_id() . '.' . $extension;
            $file_path  = $base_dir . $filename;

            file_put_contents($file_path, $image_data);

            // Build public URL (WordPress will serve over HTTPS if site is on HTTPS)
            $local_urls[] = $base_url . $filename;
        }

        if (empty($local_urls)) {
            return new WP_Error('save_failed', 'Failed to save generated image(s) locally');
        }

        // ---------------------------------------------------------------------
        // 4. Return structure expected by front-end
        // ---------------------------------------------------------------------
        return array(
            'images'            => $local_urls,
            'provider'          => 'swarmui',
            'supports_websocket'=> false,
        );
    }

    protected function get_available_models() {
        if (empty($this->session_id)) {
            $session_result = $this->get_new_session();
            if (is_wp_error($session_result)) {
                return $session_result;
            }
        }

        $url = trailingslashit($this->api_base_url) . 'API/ListT2IParams';
        $request_data = array('session_id' => $this->session_id);

        // Include user token cookie if available
        $user_token = get_option('pmv_swarmui_user_token', '');
        $headers = array('Content-Type' => 'application/json');
        if (!empty($user_token)) {
            $headers['Cookie'] = 'swarm_user_token=' . $user_token;
        }

        $response = wp_remote_post($url, array(
            'body' => json_encode($request_data),
            'timeout' => 15,
            'sslverify' => false,
            'headers' => $headers
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    protected function test_connection() {
        $session_result = $this->get_new_session();
        if (is_wp_error($session_result)) {
            return $session_result;
        }

        $models_result = $this->get_available_models();
        if (is_wp_error($models_result)) {
            return $models_result;
        }
        
        return array(
            'success' => true,
            'message' => 'Connection successful!',
            'models_count' => count($models_result['models'] ?? []),
            'api_url' => $this->api_base_url,
        );
    }
}

new PMV_SwarmUI_API_Handler();
