<?php
if (!defined('ABSPATH')) exit;

add_filter('fluent_crm/ses_bounce_data', function($data) {
    global $wpdb;
    
    $bounce = $data['bounce'] ?? [];
    $recipients = $bounce['bouncedRecipients'] ?? [];
    $type = $bounce['bounceType'] ?? 'Unknown';

    foreach ($recipients as $recipient) {
        $raw_email = $recipient['emailAddress'] ?? '';
        
        // Use Regex to extract email from "Name <email@domain.com>" format
        $clean_email = '';
        if (preg_match('/<([^>]+)>/', $raw_email, $matches)) {
            $clean_email = sanitize_email($matches[1]);
        } else {
            $clean_email = sanitize_email($raw_email);
        }

        if (!$clean_email) continue;

        // 1. Check Allow List
        $is_protected = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}" . FPBMFCRM_DB_ALLOW . " WHERE email = %s", 
            $clean_email
        ));

        if ($is_protected) {
            return false; 
        }

        // 2. Log for Review
        $wpdb->insert($wpdb->prefix . FPBMFCRM_DB_LOGS, [
            'email'       => $clean_email,
            'bounce_type' => $type,
            'raw_payload' => json_encode($data)
        ]);
    }

    return $data;
}, 10, 1);
