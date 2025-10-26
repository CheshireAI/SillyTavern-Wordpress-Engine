<?php
/**
 * Image Usage Tracking System
 *
 * Tracks image generation usage stats and exposes them via AJAX for both logged-in users and guests.
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class PMV_Image_Usage_Tracker {

    /**
     * Bootstraps the tracker: registers AJAX actions.
     */
    public static function init() {
        // Logged-in users
        add_action('wp_ajax_pmv_get_image_usage_stats', [__CLASS__, 'ajax_get_image_usage_stats']);
        // Guests
        add_action('wp_ajax_nopriv_pmv_get_image_usage_stats', [__CLASS__, 'ajax_get_image_usage_stats_guest']);
    }

    /**
     * AJAX: Return image usage stats for the current logged-in user.
     */
    public static function ajax_get_image_usage_stats() {
        // Security check
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
            wp_send_json_error('Invalid security token');
        }

        $user_id = get_current_user_id();
        if ($user_id === 0) {
            wp_send_json_error('User not logged in');
        }

        $provider = sanitize_text_field($_POST['provider'] ?? 'swarmui');
        $stats    = self::get_user_usage_stats($user_id, $provider);

        // Get subscription-based limits if available
        if (class_exists('PMV_Subscription_System')) {
            $subscription_system = PMV_Subscription_System::getInstance();
            $limits = $subscription_system->get_user_limits($user_id);
            $credits = $subscription_system->get_user_credits($user_id);
            
            $stats['subscription_tier'] = $subscription_system->get_user_subscription_tier($user_id);
            $stats['image_credits'] = $limits['image_credits'];
            $stats['text_credits'] = $limits['text_credits'];
            $stats['has_credits'] = ($credits['image_credits'] > 0 || $credits['text_credits'] > 0);
            $stats['can_generate_image'] = $subscription_system->can_generate_image($user_id);
            $stats['can_generate_text'] = $subscription_system->can_generate_token($user_id);
            
            // Check if user is near credit limits (for warning purposes)
            $stats['is_low_on_image_credits'] = $credits['image_credits'] > 0 && $credits['image_credits'] < 50;
            $stats['is_low_on_text_credits'] = $credits['text_credits'] > 0 && $credits['text_credits'] < 5000;
        } else {
            // Fallback to user meta limits
            $daily_limit   = get_user_meta($user_id, "pmv_{$provider}_daily_limit", true);
            $monthly_limit = get_user_meta($user_id, "pmv_{$provider}_monthly_limit", true);

            $stats['has_credits'] = ($daily_limit || $monthly_limit) ? true : false;
            if ($daily_limit) {
                $stats['image_credits'] = intval($daily_limit);
                $stats['is_low_on_image_credits'] = $stats['today_images'] > ($daily_limit * 0.8);
            }
            if ($monthly_limit) {
                $stats['text_credits'] = intval($monthly_limit);
                $stats['is_low_on_text_credits'] = $stats['monthly_images'] > ($monthly_limit * 0.8);
            }
        }

        wp_send_json_success($stats);
    }

    /**
     * AJAX: Return image usage stats for guests (tracked via cookies).
     */
    public static function ajax_get_image_usage_stats_guest() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'pmv_ajax_nonce')) {
            wp_send_json_error('Invalid security token');
        }

        $provider    = sanitize_text_field($_POST['provider'] ?? 'swarmui');
        $cookie_name = "pmv_guest_{$provider}_usage";
        $cookie_data = [];

        if (isset($_COOKIE[$cookie_name])) {
            $cookie_data = json_decode(stripslashes($_COOKIE[$cookie_name]), true);
            if (!is_array($cookie_data)) {
                $cookie_data = [];
            }
        }

        $today         = current_time('Y-m-d');
        $current_month = current_time('Y-m');

        $today_images = isset($cookie_data[$today]) ? intval($cookie_data[$today]) : 0;
        $monthly_images = 0;
        foreach ($cookie_data as $date => $count) {
            if (strpos($date, $current_month) === 0) {
                $monthly_images += intval($count);
            }
        }

        $stats = array(
            'today_images' => $today_images,
            'monthly_images' => $monthly_images,
            'provider' => $provider,
            'user_type' => 'guest'
        );

        // Get guest credit limits from options
        $guest_image_limit = intval(get_option('pmv_guest_daily_limit', 50));
        $guest_text_limit = intval(get_option('pmv_guest_monthly_limit', 10000));
        
        $stats['image_credits'] = $guest_image_limit;
        $stats['text_credits'] = $guest_text_limit;
        $stats['has_credits'] = ($guest_image_limit > 0 || $guest_text_limit > 0);
        $stats['can_generate_image'] = $today_images < $guest_image_limit;
        $stats['can_generate_text'] = $monthly_images < $guest_text_limit;
        
        // Check if guest is near limits
        $stats['is_low_on_image_credits'] = $today_images > ($guest_image_limit * 0.8);
        $stats['is_low_on_text_credits'] = $monthly_images > ($guest_text_limit * 0.8);
        
        // For compatibility
        $stats['subscription_tier'] = 'guest';

        wp_send_json_success($stats);
    }

    /**
     * Helper: Retrieve usage stats for a user.
     */
    private static function get_user_usage_stats($user_id, $provider) {
        $today         = current_time('Y-m-d');
        $current_month = current_time('Y-m');

        // Daily usage
        $daily_usage = get_user_meta($user_id, "pmv_{$provider}_daily_usage", true);
        if (!is_array($daily_usage)) {
            $daily_usage = [];
        }
        $today_images = isset($daily_usage[$today]) ? intval($daily_usage[$today]) : 0;

        // Monthly usage
        $monthly_usage = get_user_meta($user_id, "pmv_{$provider}_monthly_usage", true);
        if (!is_array($monthly_usage)) {
            $monthly_usage = [];
        }
        $monthly_images = isset($monthly_usage[$current_month]) ? intval($monthly_usage[$current_month]) : 0;

        return [
            'today_images'   => $today_images,
            'monthly_images' => $monthly_images,
        ];
    }
}

// Kick things off only in WordPress
if (defined('ABSPATH') && function_exists('add_action')) {
    PMV_Image_Usage_Tracker::init();
} 