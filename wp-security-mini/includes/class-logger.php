<?php
if (!defined('ABSPATH')) exit;

class WPSM_Logger {
    private static $instance = null;

    public static function init() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Логирование успешных входов
        add_action('wp_login', [$this, 'log_success_login'], 10, 2);
    }

    public static function log($ip, $event_type, $description = '') {
        global $wpdb;
        $table = $wpdb->prefix . 'wpsm_logs';

        // Ограничиваем длину
        $description = substr($description, 0, 255);

        // Вставляем лог
        $wpdb->insert($table, [
            'ip_address' => $ip,
            'event_type' => $event_type,
            'description' => $description
        ], ['%s', '%s', '%s']);
    }

    public function log_success_login($user_login, $user) {
        $ip = $this->get_ip();
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $device = $this->detect_device($ua);
        $browser = $this->detect_browser($ua);

        global $wpdb;
        $table = $wpdb->prefix . 'wpsm_login_history';

        // Записываем историю входа
        $wpdb->insert($table, [
            'user_id' => $user->ID,
            'username' => $user_login,
            'ip_address' => $ip,
            'device_type' => $device,
            'browser' => $browser,
            'status' => 'success'
        ], ['%d', '%s', '%s', '%s', '%s', '%s']);

        // Лог события
        self::log($ip, 'login_success', "Вход: $user_login");
    }

    public static function log_failed_login_history($username, $ip) {
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $device = self::detect_device($ua);
        $browser = self::detect_browser($ua);

        global $wpdb;
        $table = $wpdb->prefix . 'wpsm_login_history';

        $wpdb->insert($table, [
            'user_id' => 0,
            'username' => $username,
            'ip_address' => $ip,
            'device_type' => $device,
            'browser' => $browser,
            'status' => 'failed'
        ], ['%d', '%s', '%s', '%s', '%s', '%s']);
    }

    private static function detect_device($ua) {
        if (preg_match('/mobile|android|iphone/i', $ua)) {
            return 'mobile';
        } elseif (preg_match('/tablet|ipad/i', $ua)) {
            return 'tablet';
        }
        return 'desktop';
    }

    private static function detect_browser($ua) {
        if (preg_match('/Firefox\/([0-9.]+)/i', $ua, $m)) {
            return 'Firefox';
        } elseif (preg_match('/Chrome\/([0-9.]+)/i', $ua, $m)) {
            return 'Chrome';
        } elseif (preg_match('/Safari\/([0-9.]+)/i', $ua, $m)) {
            return 'Safari';
        } elseif (preg_match('/Edge\/([0-9.]+)/i', $ua, $m)) {
            return 'Edge';
        }
        return 'Unknown';
    }

    public static function get_top_ips($limit = 10, $days = 7) {
        global $wpdb;
        $table = $wpdb->prefix . 'wpsm_logs';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT 
                ip_address,
                COUNT(*) as attack_count,
                GROUP_CONCAT(DISTINCT event_type SEPARATOR ', ') as events,
                MAX(created_at) as last_seen
            FROM $table
            WHERE event_type IN ('blocked_access', 'ddos_attack', 'malicious', 'failed_login')
              AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
            GROUP BY ip_address
            ORDER BY attack_count DESC
            LIMIT %d",
            $days, $limit
        ));
    }

    public static function get_login_history($limit = 100) {
        global $wpdb;
        $table = $wpdb->prefix . 'wpsm_login_history';

        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table ORDER BY created_at DESC LIMIT %d",
            $limit
        ));
    }

    public static function get_stats() {
        global $wpdb;
        $logs = $wpdb->prefix . 'wpsm_logs';
        $blocked = $wpdb->prefix . 'wpsm_blocked';

        return [
            'total_today' => $wpdb->get_var(
                "SELECT COUNT(*) FROM $logs WHERE DATE(created_at) = CURDATE()"
            ),
            'attacks_today' => $wpdb->get_var(
                "SELECT COUNT(*) FROM $logs 
                WHERE event_type IN ('ddos_attack', 'malicious', 'blocked_access') 
                AND DATE(created_at) = CURDATE()"
            ),
            'failed_logins' => $wpdb->get_var(
                "SELECT COUNT(*) FROM $logs 
                WHERE event_type = 'failed_login' 
                AND DATE(created_at) = CURDATE()"
            ),
            'blocked_ips' => $wpdb->get_var(
                "SELECT COUNT(*) FROM $blocked WHERE blocked_until > NOW()"
            )
        ];
    }

    private function get_ip() {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        }
        return filter_var(trim($ip), FILTER_VALIDATE_IP) ? trim($ip) : '0.0.0.0';
    }
}
