<?php
if (!defined('ABSPATH')) exit;

class WPSM_Security {
    private static $instance = null;
    private static $request_count = [];

    public static function init() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Защита от брутфорса
        add_filter('authenticate', [$this, 'check_login_attempts'], 30, 3);
        add_action('wp_login_failed', [$this, 'log_failed_login']);

        // Блокировка вредоносных запросов
        add_action('init', [$this, 'check_malicious'], 1);

        // Проверка блокировки IP
        add_action('init', [$this, 'check_blocked'], 0);

        // Скрытие версии WP
        remove_action('wp_head', 'wp_generator');
        add_filter('the_generator', '__return_empty_string');

        // Отключение XML-RPC
        add_filter('xmlrpc_enabled', '__return_false');
    }

    public function check_blocked() {
        $ip = $this->get_ip();

        if ($this->is_blocked($ip)) {
            WPSM_Logger::log($ip, 'blocked_access', 'Заблокирован');
            wp_die('Access Denied', 'Access Denied', ['response' => 403]);
        }
    }

    public function check_malicious() {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $query = $_SERVER['QUERY_STRING'] ?? '';

        $patterns = [
            '/(\.\.)/',
            '/(union.*select)/i',
            '/(script|javascript)/i',
            '/(<|%3C).*script/i',
            '/(exec|system|shell)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $uri . $query)) {
                $ip = $this->get_ip();
                WPSM_Logger::log($ip, 'malicious', substr($uri, 0, 100));
                $this->block_ip($ip, 'Malicious request', 3600);
                wp_die('Forbidden', 'Forbidden', ['response' => 403]);
            }
        }
    }

    public function check_login_attempts($user, $username, $password) {
        if (empty($username)) return $user;

        $ip = $this->get_ip();
        $key = 'wpsm_login_' . md5($ip);
        $attempts = get_transient($key) ?: 0;

        if ($attempts >= 5) {
            WPSM_Logger::log($ip, 'login_blocked', $username);
            $this->block_ip($ip, 'Too many login attempts', 1800);
            return new WP_Error('too_many_attempts', 'Слишком много попыток. Подождите 30 минут.');
        }

        return $user;
    }

    public function log_failed_login($username) {
        $ip = $this->get_ip();
        $key = 'wpsm_login_' . md5($ip);
        $attempts = get_transient($key) ?: 0;
        $attempts++;
        set_transient($key, $attempts, 1800);

        WPSM_Logger::log($ip, 'failed_login', "$username ($attempts/5)");
    }

    private function is_blocked($ip) {
        global $wpdb;
        $table = $wpdb->prefix . 'wpsm_blocked';

        $result = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE ip_address = %s AND blocked_until > NOW()",
            $ip
        ));

        return $result > 0;
    }

    public function block_ip($ip, $reason, $duration = 3600) {
        global $wpdb;
        $table = $wpdb->prefix . 'wpsm_blocked';
        $until = date('Y-m-d H:i:s', time() + $duration);

        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT block_count FROM $table WHERE ip_address = %s",
            $ip
        ));

        if ($existing) {
            $wpdb->update($table, [
                'reason' => $reason,
                'blocked_until' => $until,
                'block_count' => $existing + 1
            ], ['ip_address' => $ip]);
        } else {
            $wpdb->insert($table, [
                'ip_address' => $ip,
                'reason' => $reason,
                'blocked_until' => $until,
                'block_count' => 1
            ]);
        }
    }

    private function get_ip() {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        }
        return filter_var(trim($ip), FILTER_VALIDATE_IP) ? trim($ip) : '0.0.0.0';
    }
}
