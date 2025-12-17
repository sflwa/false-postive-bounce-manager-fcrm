<?php
/**
 * Plugin Name: False Positive Bounce Manager for FluentCRM
 * Description: Intercepts SES/SNS Bounces, logs them for review, and manages an Allow List to prevent future false-positive unsubscribes.
 * Version: 1.2
 * Author: Your Name
 */

if (!defined('ABSPATH')) exit;

// Define constants for paths and prefixes
define('FPBMFCRM_PATH', plugin_dir_path(__FILE__));
define('FPBMFCRM_DB_LOGS', 'fpbmfcrm_bounce_logs');
define('FPBMFCRM_DB_ALLOW', 'fpbmfcrm_allow_list');

// Include logic files
require_once FPBMFCRM_PATH . 'includes/database.php';
require_once FPBMFCRM_PATH . 'includes/bounce-handler.php';
require_once FPBMFCRM_PATH . 'includes/admin-ui.php';

// Activation hook for DB tables (uses the prefix from database.php)
register_activation_hook(__FILE__, 'fpbmfcrm_create_tables');

/**
 * Core function to approve an email
 * 1. Adds to Allow List
 * 2. Re-subscribes in FluentCRM
 * 3. Cleans up the log table
 */
function fpbmfcrm_whitelist_email($email) {
    global $wpdb;
    $email = sanitize_email($email);
    
    // 1. Add to our internal allow list
    $wpdb->replace($wpdb->prefix . FPBMFCRM_DB_ALLOW, ['email' => $email]);

    // 2. Auto change status back to subscribed in FluentCRM
    if (defined('FLUENTCRM')) {
        $subscriber = \FluentCrm\App\Models\Subscriber::where('email', $email)->first();
        if ($subscriber) {
            $subscriber->status = 'subscribed';
            $subscriber->save();
        }
    }

    // 3. Remove from the pending review log
    $wpdb->delete($wpdb->prefix . FPBMFCRM_DB_LOGS, ['email' => $email]);
}
