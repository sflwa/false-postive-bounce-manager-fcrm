<?php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', function() {
    add_menu_page(
        'Bounce Manager',           // Page Title
        'Bounce Manager',           // Menu Title
        'manage_options',           // Capability
        'fpbmfcrm-manager',         // Menu Slug
        'fpbmfcrm_render_admin_page', // Function
        'dashicons-email-alt',      // Icon (Email icon)
        58                          // Position (Just below FluentCRM)
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

    // Handle Bulk Clear
    if (isset($_POST['fpbmfcrm_clear_logs'])) {
        check_admin_referer('fpbmfcrm_bulk');
        $wpdb->query("TRUNCATE TABLE $log_table");
    }

    $logs = $wpdb->get_results("SELECT * FROM $log_table ORDER BY created_at DESC");
    $allow_list = $wpdb->get_results("SELECT * FROM $allow_table ORDER BY added_at DESC");
    ?>

    <div class="wrap">
        <h1>False Positive Bounce Manager</h1>
        <p>Review "Undetermined" or "Transient" bounces and move them to the Protection List.</p>

        <hr />

        <h2>Pending Review (Current Bounces)</h2>
        <form method="post">
            <?php wp_nonce_field('fpbmfcrm_bulk'); ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th width="20%">Date</th>
                        <th width="35%">Email</th>
                        <th width="15%">Type</th>
                        <th width="30%">Actions</th>
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
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="4">No pending bounces to review.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <p><input type="submit" name="fpbmfcrm_clear_logs" class="button" value="Clear All Logs" onclick="return confirm('Delete all review logs?');"></p>
        </form>

        <br />

        <h2>Protected Allow List</h2>
        <p><small>Emails below will never be marked as "Bounced" by the SNS handler.</small></p>
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
