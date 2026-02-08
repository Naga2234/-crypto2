<?php
if (!defined('ABSPATH')) exit;

class WPSM_Admin {
    private static $instance = null;

    public static function init() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);

        // AJAX
        add_action('wp_ajax_wpsm_unblock_ip', [$this, 'ajax_unblock']);
        add_action('wp_ajax_wpsm_get_top_ips', [$this, 'ajax_get_top_ips']);
    }

    public function add_menu() {
        add_menu_page(
            'WP Security Mini',
            'Security',
            'manage_options',
            'wp-security-mini',
            [$this, 'render_dashboard'],
            'dashicons-shield',
            80
        );

        add_submenu_page(
            'wp-security-mini',
            'Топ-10 IP',
            'Топ-10 IP',
            'manage_options',
            'wpsm-top-ips',
            [$this, 'render_top_ips']
        );

        add_submenu_page(
            'wp-security-mini',
            'История входов',
            'История входов',
            'manage_options',
            'wpsm-login-history',
            [$this, 'render_login_history']
        );

        add_submenu_page(
            'wp-security-mini',
            'Настройки',
            'Настройки',
            'manage_options',
            'wpsm-settings',
            [$this, 'render_settings']
        );
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'wp-security-mini') === false && strpos($hook, 'wpsm-') === false) {
            return;
        }

        wp_enqueue_style('wpsm-admin', WPSM_PLUGIN_URL . 'assets/admin.css', [], WPSM_VERSION);
        wp_enqueue_script('wpsm-admin', WPSM_PLUGIN_URL . 'assets/admin.js', ['jquery'], WPSM_VERSION, true);

        wp_localize_script('wpsm-admin', 'wpsmAjax', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('wpsm_nonce')
        ]);
    }

    public function render_dashboard() {
        $stats = WPSM_Logger::get_stats();
        $blocked_ips = $this->get_blocked_ips();

        include WPSM_PLUGIN_DIR . 'templates/dashboard.php';
    }

    public function render_top_ips() {
        $top_ips = WPSM_Logger::get_top_ips(10, 7);
        include WPSM_PLUGIN_DIR . 'templates/top-ips.php';
    }

    public function render_login_history() {
        $history = WPSM_Logger::get_login_history(100);
        include WPSM_PLUGIN_DIR . 'templates/login-history.php';
    }

    public function render_settings() {
        if (isset($_POST['wpsm_save_settings']) && check_admin_referer('wpsm_settings')) {
            update_option('wpsm_ddos_enabled', isset($_POST['ddos_enabled']) ? 1 : 0);
            update_option('wpsm_ddos_threshold', intval($_POST['ddos_threshold']));
            update_option('wpsm_log_retention_days', intval($_POST['log_retention']));

            echo '<div class="notice notice-success"><p>Настройки сохранены!</p></div>';
        }

        include WPSM_PLUGIN_DIR . 'templates/settings.php';
    }

    public function ajax_unblock() {
        check_ajax_referer('wpsm_nonce', 'nonce');

        $ip = sanitize_text_field($_POST['ip'] ?? '');

        if (empty($ip)) {
            wp_send_json_error(['message' => 'IP не указан']);
        }

        global $wpdb;
        $wpdb->delete($wpdb->prefix . 'wpsm_blocked', ['ip_address' => $ip]);

        wp_send_json_success(['message' => "IP $ip разблокирован"]);
    }

    public function ajax_get_top_ips() {
        check_ajax_referer('wpsm_nonce', 'nonce');

        $top_ips = WPSM_Logger::get_top_ips(10, 7);
        wp_send_json_success(['ips' => $top_ips]);
    }

    private function get_blocked_ips() {
        global $wpdb;
        $table = $wpdb->prefix . 'wpsm_blocked';
        return $wpdb->get_results(
            "SELECT * FROM $table WHERE blocked_until > NOW() ORDER BY block_count DESC LIMIT 20"
        );
    }
}
