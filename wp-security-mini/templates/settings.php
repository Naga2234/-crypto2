<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap wpsm-settings">
    <div class="wpsm-page">
        <div class="wpsm-page-header">
            <div class="wpsm-title-group">
                <h1>Настройки WP Security Mini</h1>
                <p>Настройте защиту, хранение логов и поведение системы.</p>
            </div>
            <div class="wpsm-header-actions">
                <span class="wpsm-chip">⚙️ Конфигурация</span>
            </div>
        </div>

        <form method="post" action="">
            <?php wp_nonce_field('wpsm_settings'); ?>

            <div class="wpsm-section">
                <div class="wpsm-section-header">
                    <div>
                        <h2>DDoS защита</h2>
                        <p>Управляйте режимом защиты и порогом срабатывания.</p>
                    </div>
                </div>

                <div class="wpsm-form-grid">
                    <div class="wpsm-form-field">
                        <label for="wpsm-ddos-enabled">Включить DDoS защиту</label>
                        <div class="wpsm-toggle">
                            <input id="wpsm-ddos-enabled" type="checkbox" name="ddos_enabled" value="1"
                                <?php checked(get_option('wpsm_ddos_enabled', 1), 1); ?>>
                            <span>Активировать защиту от DDoS атак</span>
                        </div>
                        <p class="description">
                            Включает Challenge-страницу при подозрении на DDoS и автоблокировку при превышении лимитов.
                        </p>
                    </div>

                    <div class="wpsm-form-field">
                        <label for="wpsm-ddos-threshold">Порог срабатывания</label>
                        <input id="wpsm-ddos-threshold" type="number" name="ddos_threshold"
                            value="<?php echo esc_attr(get_option('wpsm_ddos_threshold', 40)); ?>"
                            min="20" max="100">
                        <p class="description">
                            Количество запросов за 10 секунд для показа Challenge-страницы (рекомендуется 30-50).
                        </p>
                    </div>
                </div>
            </div>

            <div class="wpsm-section">
                <div class="wpsm-section-header">
                    <div>
                        <h2>Хранение данных</h2>
                        <p>Определите срок хранения логов и историю входов.</p>
                    </div>
                </div>

                <div class="wpsm-form-grid">
                    <div class="wpsm-form-field">
                        <label for="wpsm-log-retention">Хранить логи (дней)</label>
                        <input id="wpsm-log-retention" type="number" name="log_retention"
                            value="<?php echo esc_attr(get_option('wpsm_log_retention_days', 14)); ?>"
                            min="7" max="90">
                        <p class="description">
                            Логи старше указанного периода будут автоматически удаляться (рекомендуется 14-30 дней).
                        </p>
                    </div>
                </div>
            </div>

            <p class="submit">
                <input type="submit" name="wpsm_save_settings" class="button button-primary" value="Сохранить изменения">
            </p>
        </form>

        <div class="wpsm-info-box">
            <h3>💡 Рекомендации</h3>
            <ul>
                <li><strong>Порог DDoS:</strong> Для обычных сайтов — 40, для высоконагруженных — 60-80.</li>
                <li><strong>Хранение логов:</strong> 14 дней достаточно для анализа, не перегружает базу данных.</li>
                <li><strong>Блокировка брутфорса:</strong> Работает автоматически (5 попыток = 30 минут бана).</li>
                <li><strong>Защита от SQL injection и XSS:</strong> Всегда активна, настройка не требуется.</li>
            </ul>
        </div>

        <div class="wpsm-section">
            <div class="wpsm-section-header">
                <div>
                    <h2>📊 Информация о базе данных</h2>
                    <p>Короткий статус и размер хранимых журналов.</p>
                </div>
            </div>
            <?php
            global $wpdb;
            $logs_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wpsm_logs");
            $logins_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wpsm_login_history");
            $blocked_count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wpsm_blocked");
            ?>
            <p><strong>Логов событий:</strong> <?php echo number_format($logs_count); ?> записей</p>
            <p><strong>История входов:</strong> <?php echo number_format($logins_count); ?> записей (макс. 500)</p>
            <p><strong>Заблокированных IP:</strong> <?php echo number_format($blocked_count); ?> адресов</p>
        </div>
    </div>
</div>
