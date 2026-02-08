<?php
if (!defined('ABSPATH')) exit;

class WPSM_DDoS {
    private static $instance = null;

    public static function init() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        if (!get_option('wpsm_ddos_enabled', 1)) return;

        add_action('init', [$this, 'detect_ddos'], 0);
    }

    public function detect_ddos() {
        if (is_admin() || wp_doing_ajax()) return;

        $ip = $this->get_ip();
        $key = 'wpsm_ddos_' . md5($ip);

        // Используем транзиенты вместо wp_cache для экономии памяти
        $data = get_transient($key);

        if (!$data) {
            $data = ['count' => 1, 'time' => time()];
        } else {
            // Сброс если прошло больше 10 секунд
            if (time() - $data['time'] > 10) {
                $data = ['count' => 1, 'time' => time()];
            } else {
                $data['count']++;
            }
        }

        set_transient($key, $data, 15);

        $threshold = get_option('wpsm_ddos_threshold', 40);

        // Challenge режим
        if ($data['count'] > $threshold && !isset($_COOKIE['wpsm_verified'])) {
            $this->show_challenge();
        }

        // Жесткая блокировка
        if ($data['count'] > 100) {
            WPSM_Logger::log($ip, 'ddos_attack', "{$data['count']} req/10s");

            global $wpdb;
            $wpdb->replace($wpdb->prefix . 'wpsm_blocked', [
                'ip_address' => $ip,
                'reason' => 'DDoS attack',
                'blocked_until' => date('Y-m-d H:i:s', time() + 3600)
            ]);

            wp_die('Too Many Requests', 'DDoS Protection', ['response' => 429]);
        }
    }

    private function show_challenge() {
        if (is_admin() || wp_doing_ajax()) return;

        ?><!DOCTYPE html>
<html><head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Проверка безопасности</title>
<style>
body{font-family:Arial,sans-serif;display:flex;align-items:center;justify-content:center;height:100vh;margin:0;background:#f5f5f5}
.box{background:#fff;padding:30px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,.1);text-align:center;max-width:400px}
.spinner{border:3px solid #eee;border-top:3px solid #3498db;border-radius:50%;width:40px;height:40px;margin:20px auto;animation:spin 1s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
</style>
</head><body>
<div class="box">
<h2>🛡️ Проверка безопасности</h2>
<p>Идет проверка вашего браузера...</p>
<div class="spinner"></div>
<p><small>Подождите 3 секунды</small></p>
</div>
<script>
setTimeout(function(){
document.cookie="wpsm_verified=1;path=/;max-age=3600";
location.reload();
},3000);
</script>
</body></html><?php
        exit;
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
