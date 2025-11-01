<?php
/**
 * User Profile Manager
 * Manages user profile data (appearance, personality, photo) for image generation
 */

if (!defined('ABSPATH')) {
    exit;
}

class PMV_User_Profile_Manager {
    private static $instance = null;
    private $table_name;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'pmv_user_profiles';
        
        add_action('init', array($this, 'create_table'));
        add_action('wp_ajax_pmv_get_user_profile', array($this, 'ajax_get_user_profile'));
        add_action('wp_ajax_pmv_save_user_profile', array($this, 'ajax_save_user_profile'));
        add_action('wp_ajax_pmv_upload_user_photo', array($this, 'ajax_upload_user_photo'));
        add_action('wp_ajax_pmv_delete_user_photo', array($this, 'ajax_delete_user_photo'));
    }
    
    /**
     * Create user profiles table
     */
    public function create_table() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint(20) unsigned NOT NULL,
            appearance text,
            personality text,
            description text,
            photo_path varchar(255) DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_id (user_id)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Get user profile
     */
    public function get_user_profile($user_id = null) {
        global $wpdb;
        
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return null;
        }
        
        $profile = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->table_name} WHERE user_id = %d",
            $user_id
        ));
        
        if ($profile && $profile->photo_path) {
            $profile->photo_url = wp_get_attachment_url($profile->photo_path);
        }
        
        return $profile;
    }
    
    /**
     * Save user profile
     */
    public function save_user_profile($data, $user_id = null) {
        global $wpdb;
        
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        $profile_data = array(
            'user_id' => $user_id,
            'appearance' => isset($data['appearance']) ? sanitize_textarea_field($data['appearance']) : '',
            'personality' => isset($data['personality']) ? sanitize_textarea_field($data['personality']) : '',
            'description' => isset($data['description']) ? sanitize_textarea_field($data['description']) : '',
        );
        
        $existing = $this->get_user_profile($user_id);
        
        if ($existing) {
            $result = $wpdb->update(
                $this->table_name,
                $profile_data,
                array('user_id' => $user_id)
            );
        } else {
            $result = $wpdb->insert($this->table_name, $profile_data);
        }
        
        return $result !== false;
    }
    
    /**
     * Update user photo
     */
    public function update_user_photo($attachment_id, $user_id = null) {
        global $wpdb;
        
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        $existing = $this->get_user_profile($user_id);
        
        // Delete old photo if exists
        if ($existing && $existing->photo_path) {
            wp_delete_attachment($existing->photo_path, true);
        }
        
        $profile_data = array('photo_path' => $attachment_id);
        
        if ($existing) {
            $result = $wpdb->update(
                $this->table_name,
                $profile_data,
                array('user_id' => $user_id)
            );
        } else {
            $profile_data['user_id'] = $user_id;
            $result = $wpdb->insert($this->table_name, $profile_data);
        }
        
        return $result !== false;
    }
    
    /**
     * Delete user photo
     */
    public function delete_user_photo($user_id = null) {
        global $wpdb;
        
        if (!$user_id) {
            $user_id = get_current_user_id();
        }
        
        if (!$user_id) {
            return false;
        }
        
        $profile = $this->get_user_profile($user_id);
        
        if ($profile && $profile->photo_path) {
            wp_delete_attachment($profile->photo_path, true);
            
            $result = $wpdb->update(
                $this->table_name,
                array('photo_path' => null),
                array('user_id' => $user_id)
            );
            
            return $result !== false;
        }
        
        return true;
    }
    
    /**
     * Get user profile prompt text for image generation
     */
    public function get_user_profile_prompt($user_id = null) {
        $profile = $this->get_user_profile($user_id);
        
        if (!$profile) {
            return '';
        }
        
        $parts = array();
        
        if (!empty($profile->appearance)) {
            $parts[] = 'Appearance: ' . $profile->appearance;
        }
        
        if (!empty($profile->personality)) {
            $parts[] = 'Personality: ' . $profile->personality;
        }
        
        if (!empty($profile->description)) {
            $parts[] = $profile->description;
        }
        
        return implode('. ', $parts);
    }
    
    /**
     * AJAX: Get user profile
     */
    public function ajax_get_user_profile() {
        check_ajax_referer('pmv_user_profile', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'You must be logged in'));
            return;
        }
        
        $profile = $this->get_user_profile();
        
        if ($profile) {
            $response = array(
                'appearance' => $profile->appearance,
                'personality' => $profile->personality,
                'description' => $profile->description,
                'photo_url' => $profile->photo_url ?? null,
            );
            wp_send_json_success($response);
        } else {
            wp_send_json_success(array(
                'appearance' => '',
                'personality' => '',
                'description' => '',
                'photo_url' => null,
            ));
        }
    }
    
    /**
     * AJAX: Save user profile
     */
    public function ajax_save_user_profile() {
        check_ajax_referer('pmv_user_profile', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'You must be logged in'));
            return;
        }
        
        $data = array(
            'appearance' => isset($_POST['appearance']) ? $_POST['appearance'] : '',
            'personality' => isset($_POST['personality']) ? $_POST['personality'] : '',
            'description' => isset($_POST['description']) ? $_POST['description'] : '',
        );
        
        $result = $this->save_user_profile($data);
        
        if ($result) {
            wp_send_json_success(array('message' => 'Profile saved successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to save profile'));
        }
    }
    
    /**
     * AJAX: Upload user photo
     */
    public function ajax_upload_user_photo() {
        check_ajax_referer('pmv_user_profile', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'You must be logged in'));
            return;
        }
        
        if (!isset($_FILES['photo'])) {
            wp_send_json_error(array('message' => 'No file uploaded'));
            return;
        }
        
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        $file = $_FILES['photo'];
        
        // Validate file type
        $allowed_types = array('image/jpeg', 'image/png', 'image/gif', 'image/webp');
        $file_type = wp_check_filetype($file['name']);
        
        if (!in_array($file['type'], $allowed_types)) {
            wp_send_json_error(array('message' => 'Invalid file type. Only images are allowed'));
            return;
        }
        
        // Upload file
        $upload = wp_handle_upload($file, array('test_form' => false));
        
        if (isset($upload['error'])) {
            wp_send_json_error(array('message' => $upload['error']));
            return;
        }
        
        // Create attachment
        $attachment = array(
            'post_mime_type' => $upload['type'],
            'post_title' => sanitize_file_name(pathinfo($upload['file'], PATHINFO_FILENAME)),
            'post_content' => '',
            'post_status' => 'inherit'
        );
        
        $attach_id = wp_insert_attachment($attachment, $upload['file']);
        
        if (is_wp_error($attach_id)) {
            wp_send_json_error(array('message' => 'Failed to create attachment'));
            return;
        }
        
        // Generate attachment metadata
        $attach_data = wp_generate_attachment_metadata($attach_id, $upload['file']);
        wp_update_attachment_metadata($attach_id, $attach_data);
        
        // Update user profile
        $result = $this->update_user_photo($attach_id);
        
        if ($result) {
            wp_send_json_success(array(
                'message' => 'Photo uploaded successfully',
                'photo_url' => wp_get_attachment_url($attach_id)
            ));
        } else {
            wp_send_json_error(array('message' => 'Failed to update profile'));
        }
    }
    
    /**
     * AJAX: Delete user photo
     */
    public function ajax_delete_user_photo() {
        check_ajax_referer('pmv_user_profile', 'nonce');
        
        if (!is_user_logged_in()) {
            wp_send_json_error(array('message' => 'You must be logged in'));
            return;
        }
        
        $result = $this->delete_user_photo();
        
        if ($result) {
            wp_send_json_success(array('message' => 'Photo deleted successfully'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete photo'));
        }
    }
}

// Initialize
PMV_User_Profile_Manager::getInstance();

