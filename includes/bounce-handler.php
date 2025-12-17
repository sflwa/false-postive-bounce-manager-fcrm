<?php
if (!defined('ABSPATH')) exit;

add_action('init', function() {
    // 1. Use the exact logic that worked in your sniffer
    if (isset($_GET['fluentcrm']) && isset($_GET['route']) && $_GET['route'] == 'bounce_handler') {
        
        // Get the raw POST body
        $raw_body = file_get_contents('php://input');
        if (!$raw_body) return;

        $data = json_decode($raw_body, true);
        if (!$data || !isset($data['Message'])) return;

        // Amazon SNS wraps the bounce data inside a 'Message' string
        $message = json_decode($data['Message'], true);
        if (!$message || $message['notificationType'] !== 'Bounce') return;

        global $wpdb;
        $bounce = $message['bounce'] ?? [];
        $type = $bounce['bounceType'] ?? 'Unknown';
        $subType = $bounce['bounceSubType'] ?? 'Unknown';
        $recipients = $bounce['bouncedRecipients'] ?? [];

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

            // 2. Check Protection/Allow List
            $is_protected = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}" . FPBMFCRM_DB_ALLOW . " WHERE email = %s", 
                $clean_email
            ));

            if ($is_protected) {
                /* * If protected, we want to PREVENT FluentCRM from seeing this.
                 * We exit and stop PHP execution for this specific request. 
                 * This mimics a "success" to Amazon so it doesn't retry, 
                 * but FluentCRM never processes the bounce.
                 */
                status_header(200);
                die('FPBMFCRM: Protected email ignored.');
            }

            // 3. Log 'Transient' or 'Undetermined' for review
            if (in_array($type, ['Transient', 'Undetermined'])) {
                $wpdb->insert($wpdb->prefix . FPBMFCRM_DB_LOGS, [
                    'email'       => $clean_email,
                    'bounce_type' => $type . ' (' . $subType . ')',
                    'raw_payload' => $raw_body // Store the full SNS wrapper for debugging
                ]);
            }
        }
        
        // We do NOT call die() here if it wasn't protected.
        // This allows the request to continue so FluentCRM can handle the bounce normally.
    }
}, 1); // Priority 1 to catch it before FluentCRM's own init hooks
