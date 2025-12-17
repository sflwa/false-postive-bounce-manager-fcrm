<?php
if (!defined('ABSPATH')) exit;

add_filter('fluent_crm/ses_bounce_data', function($data) {
    global $wpdb;
    
    $bounce = $data['bounce'] ?? [];
    $recipients = $bounce['bouncedRecipients'] ?? [];
    $type = $bounce['bounceType'] ?? 'Unknown';
    $subType = $bounce['bounceSubType'] ?? 'Unknown';

    foreach ($recipients as $recipient) {
        $raw_email = $recipient['emailAddress'] ?? '';
        
        // Extract clean email from "Name <email@domain.com>"
        $clean_email = '';
        if (preg_match('/<([^>]+)>/', $raw_email, $matches)) {
            $clean_email = sanitize_email($matches[1]);
        } else {
            $clean_email = sanitize_email($raw_email);
        }

        if (!$clean_email) continue;

        // 1. Always check the Protection/Allow List first
        $is_protected = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}" . FPBMFCRM_DB_ALLOW . " WHERE email = %s", 
            $clean_email
        ));

        if ($is_protected) {
            // If they are protected, we return false to STOP FluentCRM from bouncing them.
            return false; 
        }

        /**
         * 2. Log logic: We want to log 'Transient' and 'Undetermined' specifically
         * so you can review them. 
         */
        if (in_array($type, ['Transient', 'Undetermined'])) {
            $wpdb->insert($wpdb->prefix . FPBMFCRM_DB_LOGS, [
                'email'       => $clean_email,
                'bounce_type' => $type . ' (' . $subType . ')',
                'raw_payload' => json_encode($data)
            ]);
        }
    }

    // Return the data so FluentCRM proceeds to mark non-protected emails as 'Bounced'
    return $data;
}, 1, 1); // Set priority to 1 to catch it before FluentCRM processes it
