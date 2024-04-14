"use strict";

jQuery(function ($) {
    const aymakanShipping = {

        init: function () {
            $('#woocommerce-order-data')
                .on('click', 'button.aymakan_show_modal', this.aymakanShowModal);

            $('body')
                .on('click', 'button.aymakan-shipping-create-btn', this.aymakanSingleAction)
                .on('click', 'button.aymakan-toggle-header', this.aymakanToggle)
                .on('click', 'input#doaction', this.aymakanBulkAction)
                .on('click', '.aymakan-notice-dismiss', this.aymakanNoticeDismiss)
                .on('change', '#woocommerce_aymakan_shipping_cost', this.aymakanShippingCost);

            $('#woocommerce_aymakan_shipping_cost').change();
        },

        aymakanShowModal: function (e) {
            e.preventDefault();
            $(this).WCBackboneModal({
                template: 'wc-aymakan-modal-shipping'
            });
            return false;
        },

        aymakanShippingCost: function () {
            const customCost = $('#woocommerce_aymakan_custom_cost');
            if ($(this).val() === 'custom') {
                customCost.closest('tr').show();
            } else {
                customCost.closest('tr').hide();
            }
        },

        aymakanToggle: function (e) {
            e.preventDefault();
            const el = $(this);
            el.closest('article').toggleClass('expanded-content')
            el.next().toggle();
        },

        aymakanSingleAction: function (e) {
            e.preventDefault();
            const el = $(this);
            const section = el.closest('.aymakan-form-container');
            section.addClass('aymakan-loading');
            const data = {
                action: 'aymakan_manual_shipping_create',
                data: section.find('form').serialize()
            };

            $.ajax({
                type: 'POST',
                url: aymakan_shipping.ajax_url,
                data: data,
                dataType: 'json',
                success: function (response) {
                    $.each(response, function (i) {
                        let shipping = response[i]
                        let notify = section.find('.notification');
                        let vendor = typeof shipping.vendor == "undefined" ? '' : '(Vendor: ' + shipping.vendor + ')'
                        if (shipping.success && shipping.id) {
                            notify.prepend(`<span class="dashicons-yes-alt aymakan-shipment-success">Aymakan Shipment Created. ${vendor}</span>`);
                            setTimeout(function () {
                                $('.aymakan-shipment-success').fadeOut(1000, function () {
                                    $(this).remove();
                                });
                            }, 1000);
                        }
                        if (shipping.errors) {
                            $.each(shipping.errors, function (key, value) {
                                section.find('input#' + key).addClass('has-error');
                                let message = value[0] + vendor;
                                notify.prepend('<span class="dashicons-warning noti-' + key + '">' + message + '</span>').fadeIn('fast')
                                    .find('.noti-' + key)
                                    .delay(7000)
                                    .fadeOut(1000, function () {
                                        $(this).remove();
                                    });
                            });
                        }
                        if (shipping.error) {
                            let message2 = shipping.message + vendor;
                            notify.prepend('<span class="dashicons-warning">' + message2 + '</span>').fadeIn('fast');
                            setTimeout(function () {
                                notify.fadeOut('slow', function () {
                                    notify.html('');
                                });
                            }, 10000);
                        }
                    });
                    section.removeClass('aymakan-loading');
                },
                error: function (error) {
                    console.error(error);
                    section.removeClass('aymakan-loading');
                },
            });
        },

        aymakanBulkAction: function (e) {

            let val = $(this).prev().val();

            if (val !== "aymakan_bulk_shipment" && val !== "aymakan_bulk_awb") {
                return '';
            }
            e.preventDefault();
            let el = $(this);
            el.addClass('aymakan-disable-btn');
            let form = el.closest('form');
            form.addClass('aymakan-loading');

            let action = (val === 'aymakan_bulk_shipment') ? 'aymakan_bulk_shipping_create' : 'aymakan_bulk_awb_create';

            let data = {
                action: action,
                data: form.serialize()
            };

            $.ajax({
                type: 'POST',
                url: aymakan_shipping.ajax_url,
                data: data,
                dataType: 'json',
                success: (val === 'aymakan_bulk_shipment') ? aymakanShipping.aymakanBulkShippingCreateMessage : aymakanShipping.aymakanBulkAwbMessage,
                error: function (error) {
                    el.removeClass('aymakan-disable-btn');
                    form.removeClass('aymakan-loading');
                }
            });
        },

        aymakanBulkAwbMessage: function (response) {

            const openWindow = window.open('', '_blank', 'width=600,height=800');

            openWindow.document.write('<div id="loadingText">Loading...</div>');
            openWindow.document.write('<style>#loadingText { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); }</style>');

            setTimeout(function () {
                openWindow.location = response.data.awb_url;
            }, 700);

            $('.aymakan-disable-btn').removeClass('aymakan-disable-btn');
            $('.aymakan-loading').removeClass('aymakan-loading');
        },

        aymakanBulkShippingCreateMessage: function (response) {
            $.each(response, function (i) {
                let shipping = typeof response[i][1] !== "undefined" ? response[i][1] : typeof response[i]['1_'] !== "undefined" ? response[i]['1_'] : response[i]
                let notify = $('.notification');
                if (notify.length === 0) {
                    notify = $('<div class="notification"></div>').insertBefore('.subsubsub');
                }
                let vendor = typeof shipping.vendor == "undefined" ? '' : '(Vendor: ' + shipping.vendor + ')'
                let shippingId = typeof shipping.id !== "undefined" ? '#' + shipping.id + ' ' : '';
                if (shipping.success && shipping.id) {
                    notify.prepend(`<div class="updated"><p><span class="aymakan-shipment-success">Aymakan Shipment Created. ${vendor}</span></p></div>`);
                    $('.order-'+shipping.id).find('.column-aymakan').html('<span class="order-status aymakan-btn aymakan-awb-btn">Shipment Created</span>');
                    setTimeout(function () {
                        $('.aymakan-shipment-success').fadeOut(20000, function () {
                            $(this).remove();
                        });
                    }, 20000);
                }

                if (shipping.errors) {
                    $.each(shipping.errors, function (key, value) {
                        $('input#' + key).addClass('has-error');
                        let message = shippingId + value[0] + vendor;
                        notify.prepend('<div class="updated noti-' + key + '"><p>' + message + '</p></div>').fadeIn('fast')
                            .find('.noti-' + key)
                            .delay(20000)
                            .fadeOut(20000, function () {
                                $(this).remove();
                            });
                    });
                }
                if (shipping.error) {
                    let message2 = shippingId + shipping.message + vendor;
                    notify.prepend('<div class="updated"><p>' + message2 + '</p>').fadeIn('fast');
                    setTimeout(function () {
                        notify.fadeOut('slow', function () {
                            notify.html('');
                        });
                    }, 20000);
                }
            });
            $('.aymakan-disable-btn').removeClass('aymakan-disable-btn');
            $('.aymakan-loading').removeClass('aymakan-loading');
        },

       /* aymakanAdminNotice: function (message, key, id, type = 'error') {
            id = id ? 'Order #' + id + ' ' : '';
            $('<div class="notice is-dismissible notice-' + type + ' noti-' + key + '"><p> ' + id + message + '</p><button type="button" class="notice-dismiss aymakan-notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>').insertAfter('.wp-header-end');
            $('.noti-' + key).delay(50000).fadeOut(1000, function () {
                $(this).remove();
            });
        },*/

        aymakanNoticeDismiss: function () {
            $(this).parent().fadeOut(1000, function () {
                $(this).remove();
            });
        }
    };
    aymakanShipping.init();
});
