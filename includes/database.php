<?php
if (!defined('ABSPATH')) exit;

function fpbmfcrm_create_tables() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

    // Table 1: Review Logs
    $table_logs = $wpdb->prefix . FPBMFCRM_DB_LOGS;
    $sql_logs = "CREATE TABLE $table_logs (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        email varchar(255) NOT NULL,
        bounce_type varchar(50),
        raw_payload text,
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_logs);

    // Table 2: The Allow List
    $table_allow = $wpdb->prefix . FPBMFCRM_DB_ALLOW;
    $sql_allow = "CREATE TABLE $table_allow (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        email varchar(255) NOT NULL UNIQUE,
        added_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id)
    ) $charset_collate;";
    dbDelta($sql_allow);
}
