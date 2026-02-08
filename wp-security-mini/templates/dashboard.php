<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap wpsm-dashboard">
    <div class="wpsm-page">
        <div class="wpsm-page-header">
            <div class="wpsm-title-group">
                <h1>WP Security Mini</h1>
                <p>Единый обзор безопасности сайта, последних атак и активных блокировок.</p>
            </div>
            <div class="wpsm-header-actions">
                <span class="wpsm-chip">🛡️ Защита активна</span>
                <span class="wpsm-chip">📅 Обновлено сегодня</span>
            </div>
        </div>

        <div class="wpsm-stats">
            <div class="wpsm-stat-card">
                <div class="wpsm-stat-icon">📊</div>
                <div>
                    <h3>Сегодня</h3>
                    <div class="wpsm-stat-number"><?php echo number_format($stats['total_today']); ?></div>
                    <p>Событий безопасности</p>
                </div>
            </div>

            <div class="wpsm-stat-card wpsm-danger">
                <div class="wpsm-stat-icon">⚠️</div>
                <div>
                    <h3>Атаки</h3>
                    <div class="wpsm-stat-number"><?php echo number_format($stats['attacks_today']); ?></div>
                    <p>DDoS и вредоносные запросы</p>
                </div>
            </div>

            <div class="wpsm-stat-card wpsm-warning">
                <div class="wpsm-stat-icon">🔑</div>
                <div>
                    <h3>Неудачные входы</h3>
                    <div class="wpsm-stat-number"><?php echo number_format($stats['failed_logins']); ?></div>
                    <p>Попытки взлома аккаунтов</p>
                </div>
            </div>

            <div class="wpsm-stat-card wpsm-info">
                <div class="wpsm-stat-icon">🚫</div>
                <div>
                    <h3>Заблокировано</h3>
                    <div class="wpsm-stat-number"><?php echo number_format($stats['blocked_ips']); ?></div>
                    <p>Активных IP ограничений</p>
                </div>
            </div>
        </div>

        <div class="wpsm-section">
            <div class="wpsm-section-header">
                <div>
                    <h2>Заблокированные IP</h2>
                    <p>Список активных блокировок, которые можно снять вручную.</p>
                </div>
                <span class="wpsm-chip">Всего: <?php echo count($blocked_ips); ?></span>
            </div>

            <?php if (!empty($blocked_ips)): ?>
            <div class="wpsm-table">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th>IP адрес</th>
                            <th>Причина</th>
                            <th>Заблокирован до</th>
                            <th>Кол-во блокировок</th>
                            <th>Действие</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($blocked_ips as $ip): ?>
                        <tr>
                            <td><code><?php echo esc_html($ip->ip_address); ?></code></td>
                            <td><?php echo esc_html($ip->reason); ?></td>
                            <td><?php echo date('d.m.Y H:i', strtotime($ip->blocked_until)); ?></td>
                            <td><strong><?php echo $ip->block_count; ?></strong></td>
                            <td>
                                <button class="button button-small wpsm-unblock" data-ip="<?php echo esc_attr($ip->ip_address); ?>">
                                    Разблокировать
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="wpsm-empty">
                <span class="dashicons dashicons-shield"></span>
                <p>Нет заблокированных IP — система работает в штатном режиме.</p>
            </div>
            <?php endif; ?>
        </div>

        <div class="wpsm-info-box">
            <h3>ℹ️ Возможности плагина</h3>
            <ul>
                <li><strong>DDoS защита:</strong> Автоматическое определение массовых запросов и Challenge-страница.</li>
                <li><strong>Защита от брутфорса:</strong> Блокировка после 5 неудачных попыток входа.</li>
                <li><strong>Блокировка вредоносных запросов:</strong> SQL injection, XSS, Directory traversal.</li>
                <li><strong>Топ атакующих IP:</strong> Аналитика по самым активным злоумышленникам.</li>
                <li><strong>История входов:</strong> Отслеживание входов по устройствам и браузерам.</li>
                <li><strong>Легковесность:</strong> Минимальная нагрузка на сервер и базу данных.</li>
            </ul>
        </div>
    </div>
</div>
