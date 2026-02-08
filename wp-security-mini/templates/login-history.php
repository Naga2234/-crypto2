<?php if (!defined('ABSPATH')) exit; ?>

<div class="wrap wpsm-login-history">
    <div class="wpsm-page">
        <div class="wpsm-page-header">
            <div class="wpsm-title-group">
                <h1>История входов пользователей</h1>
                <p>Контролируйте попытки входа и быстро находите аномалии.</p>
            </div>
            <div class="wpsm-header-actions">
                <span class="wpsm-chip">🔐 Последние 100 записей</span>
            </div>
        </div>

        <div class="wpsm-section">
            <div class="wpsm-section-header">
                <div>
                    <h2>Логи авторизаций</h2>
                    <p>Успешные и неудачные входы с данными об устройстве и браузере.</p>
                </div>
            </div>

            <?php if (!empty($history)): ?>
            <div class="wpsm-table">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th width="150">Дата/Время</th>
                            <th>Пользователь</th>
                            <th>IP адрес</th>
                            <th width="140">Устройство</th>
                            <th>Браузер</th>
                            <th width="120">Статус</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $login): ?>
                        <tr class="<?php echo $login->status === 'failed' ? 'wpsm-failed' : ''; ?>">
                            <td><?php echo date('d.m.Y H:i:s', strtotime($login->created_at)); ?></td>
                            <td>
                                <strong><?php echo esc_html($login->username); ?></strong>
                                <?php if ($login->user_id > 0): ?>
                                    <br><small style="color:#6b7280;">ID: <?php echo $login->user_id; ?></small>
                                <?php endif; ?>
                            </td>
                            <td><code><?php echo esc_html($login->ip_address); ?></code></td>
                            <td>
                                <?php
                                $icons = ['mobile' => '📱', 'tablet' => '📲', 'desktop' => '💻'];
                                echo $icons[$login->device_type] ?? '❓';
                                echo ' ' . ucfirst($login->device_type);
                                ?>
                            </td>
                            <td><?php echo esc_html($login->browser); ?></td>
                            <td>
                                <?php if ($login->status === 'success'): ?>
                                    <span class="wpsm-badge wpsm-success">✅ Успешно</span>
                                <?php else: ?>
                                    <span class="wpsm-badge wpsm-error">❌ Неудача</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="wpsm-empty">
                <span class="dashicons dashicons-admin-users"></span>
                <p>История входов пуста.</p>
            </div>
            <?php endif; ?>
        </div>

        <div class="wpsm-info-box">
            <h3>ℹ️ Советы по безопасности</h3>
            <ul>
                <li>Обращайте внимание на неудачные попытки входа с незнакомых IP.</li>
                <li>Если видите много попыток с одного IP — он может быть заблокирован автоматически.</li>
                <li>Проверяйте регулярно список «Топ IP» для анализа угроз.</li>
                <li>История хранит только последние 500 записей для экономии места.</li>
            </ul>
        </div>
    </div>
</div>
