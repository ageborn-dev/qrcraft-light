(function ($) {
    'use strict';

    var QRCraft = {
        init: function () {
            this.initColorPickers();
            this.bindEvents();
            this.checkProgress();
        },

        initColorPickers: function () {
            $('.qrcraft-color-picker').wpColorPicker();
        },

        bindEvents: function () {
            $('#qrcraft-regenerate-all').on('click', this.regenerateAll.bind(this));
            $(document).on('click', '.qrcraft-regenerate', this.regenerateSingle.bind(this));
        },

        regenerateAll: function (e) {
            e.preventDefault();

            if (!confirm(qrcraft.confirm_regen)) {
                return;
            }

            var $button = $('#qrcraft-regenerate-all');
            var $progressWrap = $('.qrcraft-progress-wrap');

            $button.prop('disabled', true).text(qrcraft.regenerating);
            $progressWrap.show();

            $.ajax({
                url: qrcraft.ajax_url,
                type: 'POST',
                data: {
                    action: 'qrcraft_regenerate_all',
                    nonce: qrcraft.nonce
                },
                success: function (response) {
                    if (response.success) {
                        QRCraft.pollProgress();
                    } else {
                        alert(response.data.message || qrcraft.error);
                        $button.prop('disabled', false).text($button.data('original-text') || 'Regenerate All QR Codes');
                    }
                },
                error: function () {
                    alert(qrcraft.error);
                    $button.prop('disabled', false);
                }
            });
        },

        pollProgress: function () {
            $.ajax({
                url: qrcraft.ajax_url,
                type: 'POST',
                data: {
                    action: 'qrcraft_get_progress',
                    nonce: qrcraft.nonce
                },
                success: function (response) {
                    if (response.success) {
                        var progress = response.data;
                        var percentage = progress.total > 0 ? (progress.processed / progress.total) * 100 : 0;

                        $('.qrcraft-progress-fill').css('width', percentage + '%');
                        $('.qrcraft-progress-text').text(progress.processed + ' / ' + progress.total + ' processed');

                        if (progress.status === 'running') {
                            setTimeout(function () {
                                QRCraft.pollProgress();
                            }, 2000);
                        } else if (progress.status === 'complete') {
                            $('#qrcraft-regenerate-all').prop('disabled', false).text(qrcraft.complete);
                            setTimeout(function () {
                                location.reload();
                            }, 1500);
                        }
                    }
                }
            });
        },

        checkProgress: function () {
            if ($('.qrcraft-progress-wrap').is(':visible')) {
                this.pollProgress();
            }
        },

        regenerateSingle: function (e) {
            e.preventDefault();

            var $button = $(e.currentTarget);
            var productId = $button.data('product-id');
            var originalText = $button.text();

            $button.prop('disabled', true).text(qrcraft.regenerating);

            $.ajax({
                url: qrcraft.ajax_url,
                type: 'POST',
                data: {
                    action: 'qrcraft_regenerate_single',
                    nonce: qrcraft.nonce,
                    product_id: productId
                },
                success: function (response) {
                    if (response.success) {
                        var $container = $button.closest('.qrcraft-column-preview, .qrcraft-column-empty, .qrcraft-metabox');

                        if ($container.hasClass('qrcraft-column-empty')) {
                            location.reload();
                        } else {
                            var $img = $container.find('img');
                            if ($img.length) {
                                $img.attr('src', response.data.url + '?t=' + Date.now());
                            }
                            $button.text(qrcraft.complete);
                            setTimeout(function () {
                                $button.prop('disabled', false).text(originalText);
                            }, 1500);
                        }
                    } else {
                        alert(response.data.message || qrcraft.error);
                        $button.prop('disabled', false).text(originalText);
                    }
                },
                error: function () {
                    alert(qrcraft.error);
                    $button.prop('disabled', false).text(originalText);
                }
            });
        }
    };

    $(document).ready(function () {
        QRCraft.init();
    });

})(jQuery);
