"use strict";

jQuery(function ($) {
    const aymakanShipping = {

        init: function () {
            $('#woocommerce-order-data')
                .on('click', 'button.aymakan_show_modal', this.aymakanShowModal);

            $('body')
                .on('click', 'button.aymakan-shipping-create-btn', this.aymakanShippingCreate)
                .on('click', 'button.aymakan-toggle-header', this.aymakanToggle)
                .on('click', 'input#doaction', this.aymakanBulkShippingCreate)
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

        aymakanShippingCreate: function (e) {
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

        aymakanBulkShippingCreate: function (e) {
            let el = $(this);

            if (el.prev().val() !== 'aymakan_bulk_shipment') {
                return '';
            }

            e.preventDefault()

            el.addClass('aymakan-disable-btn');
            let form = el.closest('form');

            form.addClass('aymakan-loading');

            let data = {
                action: 'aymakan_bulk_shipping_create',
                data: form.serialize()
            };

            $.ajax({
                type: 'POST',
                url: aymakan_shipping.ajax_url,
                data: data,
                dataType: 'json',
                success: function (response) {

                    $.each(response, function (i) {
                        let items = response[i]
                        $.each(items, function (i) {

                            let shipping = items[i]
                            console.log(shipping);

                            let row = $('tr#post-' + shipping.id)
                            let orderId = shipping.id;

                            let vendor = typeof shipping.vendor == "undefined" ? '' : '(Vendor: ' + shipping.vendor + ')';

                            if (shipping.success === true) {
                                aymakanShipping.aymakanAdminNotice(`Aymakan Shipments Created Successfully. ${vendor}`, 'success', orderId, 'success')

                                row.find('.column-aymakan').html('<a href="' + shipping.pdf_label + '" class="order-status aymakan-btn aymakan-awb-btn" target="_blank">Print Airway Bill</a>');

                                row.find('.column-aymakan-tracking').html('<a href="' + shipping.tracking_link + '" class="order-status aymakan-btn aymakan-shipping-track-btn" target="_blank">' + shipping.tracking_number + '</a>');
                            }

                            row.find('.check-column input').prop('checked', false)

                            if (shipping.errors) {
                                $.each(shipping.errors, function (key, value) {
                                    let message = value[0] + vendor;
                                    aymakanShipping.aymakanAdminNotice(message, key, orderId)
                                });
                            }

                            if (shipping.error) {
                                let message2 = shipping.message + vendor;
                                aymakanShipping.aymakanAdminNotice(message2, 'error', orderId)
                            }

                        });
                    });

                    el.removeClass('aymakan-disable-btn');
                    form.removeClass('aymakan-loading');
                },
                error: function (error) {
                    el.removeClass('aymakan-disable-btn');
                    form.removeClass('aymakan-loading');
                },

            });
        },

        aymakanAdminNotice: function (message, key, id, type = 'error') {
            id = id ? 'Order #' + id + ' ' : '';
            $('<div class="notice is-dismissible notice-' + type + ' noti-' + key + '"><p> ' + id + message + '</p><button type="button" class="notice-dismiss aymakan-notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></button></div>').insertAfter('.wp-header-end');
            $('.noti-' + key).delay(50000).fadeOut(1000, function () {
                $(this).remove();
            });
        },

        aymakanNoticeDismiss: function () {
            $(this).parent().fadeOut(1000, function () {
                $(this).remove();
            });
        }
    };
    aymakanShipping.init();
});
