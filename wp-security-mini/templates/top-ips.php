<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap wpsm-top-ips">
    <div class="wpsm-page">
        <div class="wpsm-page-header">
            <div class="wpsm-title-group">
                <h1>Топ-10 активных атакующих IP</h1>
                <p>Фокусируйтесь на самых агрессивных источниках трафика за 7 дней.</p>
            </div>
            <div class="wpsm-header-actions">
                <span class="wpsm-chip">📈 Данные за неделю</span>
                <span class="wpsm-chip">🔍 Готово к анализу</span>
            </div>
        </div>

        <div class="wpsm-section">
            <div class="wpsm-section-header">
                <div>
                    <h2>Подозрительная активность</h2>
                    <p>Сравнение количества атак и типов событий для принятия решений.</p>
                </div>
            </div>

            <?php if (!empty($top_ips)): ?>
            <?php $max_attack = max(array_map(static function($item) { return $item->attack_count; }, $top_ips)); ?>
            <div class="wpsm-table">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th width="60">#</th>
                            <th>IP адрес</th>
                            <th>Типы событий</th>
                            <th>Интенсивность</th>
                            <th>Последняя активность</th>
                            <th>Действие</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $rank = 1;
                        foreach ($top_ips as $ip):
                            $percent = $max_attack > 0 ? min(100, round(($ip->attack_count / $max_attack) * 100)) : 0;
                        ?>
                        <tr>
                            <td><strong><?php echo $rank++; ?></strong></td>
                            <td><code><?php echo esc_html($ip->ip_address); ?></code></td>
                            <td><?php echo esc_html($ip->events); ?></td>
                            <td>
                                <div class="wpsm-risk-meter"><span style="width: <?php echo $percent; ?>%"></span></div>
                                <div style="margin-top:6px;font-weight:600;color:#ef4444;">
                                    <?php echo number_format($ip->attack_count); ?> атак
                                </div>
                            </td>
                            <td><?php echo date('d.m.Y H:i', strtotime($ip->last_seen)); ?></td>
                            <td>
                                <div class="wpsm-inline-actions">
                                    <button class="button wpsm-block-permanent" data-ip="<?php echo esc_attr($ip->ip_address); ?>">
                                        Заблокировать на 24ч
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="wpsm-empty">
                <span class="dashicons dashicons-yes-alt"></span>
                <p>Атак не обнаружено за последние 7 дней.</p>
            </div>
            <?php endif; ?>
        </div>

        <div class="wpsm-info-box">
            <h3>📖 Типы событий</h3>
            <ul>
                <li><strong>ddos_attack:</strong> DDoS атака (массовые запросы за короткий период).</li>
                <li><strong>malicious:</strong> Вредоносный запрос (SQL injection, XSS).</li>
                <li><strong>failed_login:</strong> Неудачная попытка входа.</li>
                <li><strong>blocked_access:</strong> Попытка доступа с заблокированного IP.</li>
            </ul>
        </div>
    </div>
</div>
