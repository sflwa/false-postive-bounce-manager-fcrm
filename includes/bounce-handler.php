<?php
if (!defined('ABSPATH')) exit;

add_filter('fluent_crm/ses_bounce_data', function($data) {
    global $wpdb;
    
    $bounce = $data['bounce'] ?? [];
    $recipients = $bounce['bouncedRecipients'] ?? [];
    $type = $bounce['bounceType'] ?? 'Unknown';

    foreach ($recipients as $recipient) {
        $email = sanitize_email($recipient['emailAddress'] ?? '');
        if (!$email) continue;

        // 1. Check if email is on the Protection/Allow List
        $is_protected = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}" . FPBMFCRM_DB_ALLOW . " WHERE email = %s", 
            $email
        ));

        if ($is_protected) {
            // STOP: Return false so FluentCRM does not mark them as bounced
            return false; 
        }

        // 2. If not protected, log it for review (FluentCRM will proceed to mark as Bounced)
        $wpdb->insert($wpdb->prefix . FPBMFCRM_DB_LOGS, [
            'email'       => $email,
            'bounce_type' => $type,
            'raw_payload' => json_encode($data)
        ]);
    }

    return $data;
}, 10, 1);
