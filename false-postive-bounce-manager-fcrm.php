<?php
/**
 * Plugin Name: False Positive Bounce Manager for FluentCRM
 * Version: 1.3
 */

if (!defined('ABSPATH')) exit;

define('FPBMFCRM_PATH', plugin_dir_path(__FILE__));
define('FPBMFCRM_DB_LOGS', 'fpbmfcrm_bounce_logs');
define('FPBMFCRM_DB_ALLOW', 'fpbmfcrm_allow_list');
define('FPBMFCRM_DEBUG', true); // SET TO FALSE TO TURN OFF LOGGING

require_once FPBMFCRM_PATH . 'includes/database.php';
require_once FPBMFCRM_PATH . 'includes/bounce-handler.php';
require_once FPBMFCRM_PATH . 'includes/admin-ui.php';

register_activation_hook(__FILE__, 'fpbmfcrm_create_tables');

// Helper for Debugging
function fpbmfcrm_log($message) {
    if (defined('FPBMFCRM_DEBUG') && FPBMFCRM_DEBUG) {
        $file = FPBMFCRM_PATH . 'debug.log';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($file, "[$timestamp] $message\n", FILE_APPEND);
    }
}

function fpbmfcrm_whitelist_email($email) {
    global $wpdb;
    $email = sanitize_email($email);
    $wpdb->replace($wpdb->prefix . FPBMFCRM_DB_ALLOW, ['email' => $email]);
    if (defined('FLUENTCRM')) {
        $subscriber = \FluentCrm\App\Models\Subscriber::where('email', $email)->first();
        if ($subscriber) {
            $subscriber->status = 'subscribed';
            $subscriber->save();
        }
    }
    $wpdb->delete($wpdb->prefix . FPBMFCRM_DB_LOGS, ['email' => $email]);
}
