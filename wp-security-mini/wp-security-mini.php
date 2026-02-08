<?php
/**
 * Plugin Name: WP Security Mini
 * Plugin URI: https://example.com
 * Description: Легковесная защита: DDoS, топ-10 атакующих IP, история входов
 * Version: 1.6.0
 * Author: Your Name
 * License: GPL2
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WPSM_VERSION', '1.6.0');
define('WPSM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPSM_PLUGIN_URL', plugin_dir_url(__FILE__));

// Подключаем классы
require_once WPSM_PLUGIN_DIR . 'includes/class-security.php';
require_once WPSM_PLUGIN_DIR . 'includes/class-ddos.php';
require_once WPSM_PLUGIN_DIR . 'includes/class-logger.php';
require_once WPSM_PLUGIN_DIR . 'includes/class-admin.php';

// Инициализация
function wpsm_init() {
    WPSM_Security::init();
    WPSM_DDoS::init();
    WPSM_Logger::init();

    if (is_admin()) {
        WPSM_Admin::init();
    }
}
add_action('plugins_loaded', 'wpsm_init');

// Активация плагина
register_activation_hook(__FILE__, 'wpsm_activate');
function wpsm_activate() {
    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();

    // Таблица логов (легковесная)
    $table_logs = $wpdb->prefix . 'wpsm_logs';
    $sql_logs = "CREATE TABLE IF NOT EXISTS $table_logs (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        ip_address varchar(45) NOT NULL,
        event_type varchar(50) NOT NULL,
        description varchar(255),
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY ip_event_date (ip_address, event_type, created_at)
    ) $charset_collate;";

    // Таблица блокировок
    $table_blocked = $wpdb->prefix . 'wpsm_blocked';
    $sql_blocked = "CREATE TABLE IF NOT EXISTS $table_blocked (
        ip_address varchar(45) NOT NULL,
        reason varchar(100),
        blocked_until datetime,
        block_count int DEFAULT 1,
        PRIMARY KEY (ip_address)
    ) $charset_collate;";

    // Таблица истории входов (только последние 500 записей)
    $table_login = $wpdb->prefix . 'wpsm_login_history';
    $sql_login = "CREATE TABLE IF NOT EXISTS $table_login (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        username varchar(60) NOT NULL,
        ip_address varchar(45) NOT NULL,
        device_type varchar(20),
        browser varchar(50),
        status varchar(10),
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY created_at (created_at)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql_logs);
    dbDelta($sql_blocked);
    dbDelta($sql_login);

    // Настройки по умолчанию
    add_option('wpsm_ddos_enabled', 1);
    add_option('wpsm_ddos_threshold', 40);
    add_option('wpsm_log_retention_days', 14);
}

// Очистка старых данных (раз в день)
add_action('wp_scheduled_delete', 'wpsm_cleanup');
function wpsm_cleanup() {
    global $wpdb;
    $days = get_option('wpsm_log_retention_days', 14);

    // Удаляем старые логи
    $wpdb->query($wpdb->prepare(
        "DELETE FROM {$wpdb->prefix}wpsm_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
        $days
    ));

    // Оставляем только последние 500 записей истории входов
    $wpdb->query(
        "DELETE FROM {$wpdb->prefix}wpsm_login_history 
        WHERE id NOT IN (
            SELECT id FROM (
                SELECT id FROM {$wpdb->prefix}wpsm_login_history 
                ORDER BY created_at DESC LIMIT 500
            ) tmp
        )"
    );

    // Удаляем истекшие блокировки
    $wpdb->query(
        "DELETE FROM {$wpdb->prefix}wpsm_blocked WHERE blocked_until < NOW()"
    );
}
