<?php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', function() {
    add_menu_page(
        'Bounce Manager',
        'Bounce Manager',
        'manage_options',
        'fpbmfcrm-manager',
        'fpbmfcrm_render_admin_page',
        'dashicons-email-alt',
        58
    );
});

function fpbmfcrm_render_admin_page() {
    global $wpdb;
    $log_table = $wpdb->prefix . FPBMFCRM_DB_LOGS;
    $allow_table = $wpdb->prefix . FPBMFCRM_DB_ALLOW;

    // --- HANDLE ACTIONS ---
    if (isset($_GET['action']) && isset($_GET['email'])) {
        $email = sanitize_email($_GET['email']);
        check_admin_referer('fpbmfcrm_action_' . $email);

        if ($_GET['action'] === 'approve') {
            fpbmfcrm_whitelist_email($email);
            echo '<div class="updated"><p>' . esc_html($email) . ' added to Allow List and Resubscribed.</p></div>';
        }
        if ($_GET['action'] === 'delete_log') {
            $wpdb->delete($log_table, ['email' => $email]);
            echo '<div class="updated"><p>Log entry removed.</p></div>';
        }
        if ($_GET['action'] === 'remove_allow') {
            $wpdb->delete($allow_table, ['email' => $email]);
            echo '<div class="updated"><p>' . esc_html($email) . ' removed from protection.</p></div>';
        }
    }

    if (isset($_POST['fpbmfcrm_clear_logs'])) {
        check_admin_referer('fpbmfcrm_bulk');
        $wpdb->query("TRUNCATE TABLE $log_table");
    }

    $logs = $wpdb->get_results("SELECT * FROM $log_table ORDER BY created_at DESC");
    $allow_list = $wpdb->get_results("SELECT * FROM $allow_table ORDER BY added_at DESC");
    ?>

    <div class="wrap">
        <h1>False Positive Bounce Manager</h1>
        
        <div style="background: #fff; border-left: 4px solid #72aee6; padding: 12px; margin-bottom: 20px;">
            <strong>Debug Status:</strong> <?php echo (defined('FPBMFCRM_DEBUG') && FPBMFCRM_DEBUG) ? '<span style="color:red;">ENABLED</span>' : 'OFF'; ?> 
            <?php if (file_exists(FPBMFCRM_PATH . 'debug.log')): ?>
                | <a href="<?php echo plugins_url('debug.log', FPBMFCRM_PATH . 'false-postive-bounce-manager-fcrm.php'); ?>" target="_blank">View Debug Log File</a>
            <?php endif; ?>
        </div>

        <h2>Pending Review (Current Bounces)</h2>
        <form method="post">
            <?php wp_nonce_field('fpbmfcrm_bulk'); ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th width="15%">Date</th>
                        <th width="30%">Email</th>
                        <th width="20%">Type</th>
                        <th width="35%">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($logs): foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo esc_html($log->created_at); ?></td>
                        <td><strong><?php echo esc_html($log->email); ?></strong></td>
                        <td><code><?php echo esc_html($log->bounce_type); ?></code></td>
                        <td>
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=fpbmfcrm-manager&action=approve&email=' . $log->email), 'fpbmfcrm_action_' . $log->email); ?>" class="button button-primary">Approve & Resubscribe</a>
                            <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=fpbmfcrm-manager&action=delete_log&email=' . $log->email), 'fpbmfcrm_action_' . $log->email); ?>" class="button">Dismiss</a>
                            <button type="button" class="button" onclick="document.getElementById('details-<?php echo $log->id; ?>').style.display = (document.getElementById('details-<?php echo $log->id; ?>').style.display == 'none') ? 'block' : 'none';">JSON</button>
                            
                            <div id="details-<?php echo $log->id; ?>" style="display:none; margin-top:10px; background:#f0f0f0; padding:10px; border:1px solid #ccc; font-family:monospace; font-size:11px; white-space: pre-wrap; word-break: break-all;">
                                <?php echo esc_html(json_encode(json_decode($log->raw_payload), JSON_PRETTY_PRINT)); ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="4">No pending bounces to review.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <p><input type="submit" name="fpbmfcrm_clear_logs" class="button" value="Clear All Logs" onclick="return confirm('Delete all review logs?');"></p>
        </form>

        <br /><hr /><br />

        <h2>Protected Allow List</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th width="20%">Added On</th>
                    <th width="50%">Email</th>
                    <th width="30%">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($allow_list): foreach ($allow_list as $item): ?>
                <tr>
                    <td><?php echo esc_html($item->added_at); ?></td>
                    <td><?php echo esc_html($item->email); ?></td>
                    <td>
                        <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=fpbmfcrm-manager&action=remove_allow&email=' . $item->email), 'fpbmfcrm_action_' . $item->email); ?>" class="button">Remove Protection</a>
                    </td>
                </tr>
                <?php endforeach; else: ?>
                <tr><td colspan="3">Allow list is currently empty.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
