<?php
/**
 * Character Settings AJAX Handler
 * 
 * Handles AJAX requests for character settings page with pagination
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX handler to get character cards for settings page with pagination
 */
function pmv_ajax_get_character_cards_for_settings() {
    // Verify nonce - check_ajax_referer expects the action name
    check_ajax_referer('pmv_ajax_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    $page = max(1, intval($_POST['page'] ?? 1));
    $per_page = max(1, intval($_POST['per_page'] ?? 25));
    
    // Get character cards - use processing function if available, otherwise load directly
    $cards = array();
    $total = 0;
    
    // Try using the processing function from cache handler
    if (function_exists('pmv_process_character_cards')) {
        $result = pmv_process_character_cards(array(
            'page' => $page,
            'per_page' => $per_page,
            'folder' => '' // Empty folder means root png-cards directory
        ));
        
        if ($result && isset($result['cards'])) {
            // Convert from cache handler format to our format
            foreach ($result['cards'] as $card) {
                // Extract filename from file_url
                $filename = basename($card['file_url']);
                $cards[] = array(
                    'filename' => $filename,
                    'name' => $card['name'],
                    'url' => $card['file_url']
                );
            }
            $total = isset($result['pagination']['total_items']) ? $result['pagination']['total_items'] : 0;
        }
    }
    
    // Fallback: Load directly if processing function didn't work or returned empty
    if (empty($cards)) {
        $upload_dir = wp_upload_dir();
        $png_cards_dir = trailingslashit($upload_dir['basedir']) . 'png-cards/';
        
        if (file_exists($png_cards_dir)) {
            $files = glob($png_cards_dir . '*.png');
            $total = count($files);
            
            // Paginate
            $offset = ($page - 1) * $per_page;
            $page_files = array_slice($files, $offset, $per_page);
            
            foreach ($page_files as $file) {
                $filename = basename($file);
                $metadata = null;
                if (class_exists('PNG_Metadata_Reader')) {
                    try {
                        $metadata = PNG_Metadata_Reader::extract_highest_spec_fields($file);
                    } catch (Exception $e) {
                        // If metadata extraction fails, use filename
                        error_log('PMV Settings: Error extracting metadata for ' . $filename . ': ' . $e->getMessage());
                    }
                }
                $name = isset($metadata['data']['name']) ? $metadata['data']['name'] : pathinfo($filename, PATHINFO_FILENAME);
                
                $cards[] = array(
                    'filename' => $filename,
                    'name' => $name,
                    'url' => trailingslashit($upload_dir['baseurl']) . 'png-cards/' . $filename
                );
            }
        }
    }
    
    // Get settings map
    $settings_map = array();
    if (class_exists('PMV_Character_Settings_Manager')) {
        $all_settings = PMV_Character_Settings_Manager::get_all_settings();
        foreach ($all_settings as $setting) {
            $settings_map[$setting['character_filename']] = array(
                'prompt_prefix' => $setting['prompt_prefix'],
                'prompt_suffix' => $setting['prompt_suffix'],
                'character_name' => $setting['character_name']
            );
        }
    }
    
    $total_pages = $total > 0 ? ceil($total / $per_page) : 1;
    
    // Ensure we have valid pagination data
    if ($total === 0 && empty($cards)) {
        // No cards found
        wp_send_json_success(array(
            'cards' => array(),
            'settings_map' => $settings_map,
            'pagination' => array(
                'total_items' => 0,
                'total_pages' => 1,
                'current_page' => 1,
                'per_page' => $per_page,
                'has_prev' => false,
                'has_next' => false
            )
        ));
        return;
    }
    
    wp_send_json_success(array(
        'cards' => $cards,
        'settings_map' => $settings_map,
        'pagination' => array(
            'total_items' => $total,
            'total_pages' => $total_pages,
            'current_page' => $page,
            'per_page' => $per_page,
            'has_prev' => $page > 1,
            'has_next' => $page < $total_pages
        )
    ));
}

// Register AJAX handler
add_action('wp_ajax_pmv_get_character_cards_for_settings', 'pmv_ajax_get_character_cards_for_settings');

