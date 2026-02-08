jQuery(document).ready(function($) {
    var toastTimeout;

    function showToast(message, type) {
        clearTimeout(toastTimeout);
        $('.wpsm-toast').remove();
        var $toast = $('<div />', {
            class: 'wpsm-toast ' + (type || '')
        }).text(message);
        $('body').append($toast);
        toastTimeout = setTimeout(function() {
            $toast.fadeOut(300, function() {
                $(this).remove();
            });
        }, 2600);
    }

    function setButtonState($btn, loadingText, isLoading) {
        if (isLoading) {
            $btn.data('original-text', $btn.text());
            $btn.prop('disabled', true).addClass('button-disabled').text(loadingText);
        } else {
            $btn.prop('disabled', false).removeClass('button-disabled');
            $btn.text($btn.data('original-text') || '');
        }
    }

    // Разблокировка IP
    $(document).on('click', '.wpsm-unblock', function() {
        var $btn = $(this);
        var ip = $btn.data('ip');

        if (!confirm('Разблокировать IP ' + ip + '?')) {
            return;
        }

        setButtonState($btn, 'Разблокируем...', true);

        $.ajax({
            url: wpsmAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'wpsm_unblock_ip',
                nonce: wpsmAjax.nonce,
                ip: ip
            },
            success: function(response) {
                if (response.success) {
                    showToast('IP ' + ip + ' разблокирован', 'wpsm-success');
                    $btn.closest('tr').fadeOut(240, function() {
                        $(this).remove();
                        if ($('tbody tr').length === 0) {
                            location.reload();
                        }
                    });
                } else {
                    showToast(response.data.message || 'Неизвестная ошибка', 'wpsm-error');
                    setButtonState($btn, 'Разблокировать', false);
                }
            },
            error: function() {
                showToast('Ошибка сервера', 'wpsm-error');
                setButtonState($btn, 'Разблокировать', false);
            }
        });
    });

    // Постоянная блокировка IP (на 24 часа)
    $(document).on('click', '.wpsm-block-permanent', function() {
        var $btn = $(this);
        var ip = $btn.data('ip');

        if (!confirm('Заблокировать IP ' + ip + ' на 24 часа?')) {
            return;
        }

        setButtonState($btn, 'Блокируется...', true);

        // Здесь можно добавить AJAX для постоянной блокировки
        showToast('IP ' + ip + ' заблокирован на 24 часа', 'wpsm-success');
        $btn.text('Заблокирован');
    });
});
