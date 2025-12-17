<?php
if (!defined('ABSPATH')) exit;

add_action('init', function() {
    if (isset($_GET['fluentcrm']) && isset($_GET['route']) && $_GET['route'] == 'bounce_handler') {
        
        fpbmfcrm_log("Hook Fired: Route detected.");

        $raw_body = file_get_contents('php://input');
        if (!$raw_body) {
            fpbmfcrm_log("Error: Empty POST body.");
            return;
        }

        $data = json_decode($raw_body, true);
        if (!$data) {
            fpbmfcrm_log("Error: Failed to decode SNS wrapper JSON.");
            return;
        }

        // Amazon SNS can send a SubscriptionConfirmation
        if (isset($data['Type']) && $data['Type'] === 'SubscriptionConfirmation') {
            fpbmfcrm_log("SNS SubscriptionConfirmation received: " . $data['SubscribeURL']);
            return;
        }

        if (!isset($data['Message'])) {
            fpbmfcrm_log("Error: No 'Message' field in SNS payload.");
            return;
        }

        $message = json_decode($data['Message'], true);
        if (!$message) {
            fpbmfcrm_log("Error: Failed to decode internal Message string.");
            return;
        }

        if (($message['notificationType'] ?? '') !== 'Bounce') {
            fpbmfcrm_log("Notice: Notification is not a Bounce. Type: " . ($message['notificationType'] ?? 'N/A'));
            return;
        }

        global $wpdb;
        $bounce = $message['bounce'] ?? [];
        $type = $bounce['bounceType'] ?? 'Unknown';
        $subType = $bounce['bounceSubType'] ?? 'Unknown';
        $recipients = $bounce['bouncedRecipients'] ?? [];

        fpbmfcrm_log("Processing Bounce: Type=$type, SubType=$subType");

        foreach ($recipients as $recipient) {
            $raw_email = $recipient['emailAddress'] ?? '';
            
            $clean_email = '';
            if (preg_match('/<([^>]+)>/', $raw_email, $matches)) {
                $clean_email = sanitize_email($matches[1]);
            } else {
                $clean_email = sanitize_email($raw_email);
            }

            if (!$clean_email) continue;

            // Check Protection
            $is_protected = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}" . FPBMFCRM_DB_ALLOW . " WHERE email = %s", 
                $clean_email
            ));

            if ($is_protected) {
                fpbmfcrm_log("BLOCKED: $clean_email is on Allow List. Killing request.");
                status_header(200);
                die('FPBMFCRM: Protected email ignored.');
            }

            // Log 'Transient' or 'Undetermined' for review
            if (in_array($type, ['Transient', 'Undetermined'])) {
                $inserted = $wpdb->insert($wpdb->prefix . FPBMFCRM_DB_LOGS, [
                    'email'       => $clean_email,
                    'bounce_type' => $type . ' (' . $subType . ')',
                    'raw_payload' => $raw_body 
                ]);
                
                if ($inserted === false) {
                    fpbmfcrm_log("DB Error: Failed to insert $clean_email into logs. Error: " . $wpdb->last_error);
                } else {
                    fpbmfcrm_log("Success: $clean_email logged for review.");
                }
            } else {
                fpbmfcrm_log("Notice: $clean_email is a Permanent bounce ($type). Letting FluentCRM handle it.");
            }
        }
    }
}, 1);
