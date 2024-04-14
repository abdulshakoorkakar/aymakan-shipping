"use strict";

jQuery(function ($) {
    const aymakanShipping = {
        init: function () {
            $('#woocommerce-order-data').on('click', 'button.aymakan_show_modal', this.aymakanShowModal);
            $('body').on('click', 'button.aymakan-shipping-create-btn', this.aymakanAction)
                .on('click', 'button.aymakan-toggle-header', this.aymakanToggle)
                .on('click', 'input#doaction', this.aymakanAction)
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
            customCost.closest('tr').toggle($(this).val() === 'custom');
        },

        aymakanToggle: function (e) {
            e.preventDefault();
            const el = $(this);
            el.closest('article').toggleClass('expanded-content').next().toggle();
        },

        aymakanAction: function (e) {
            e.preventDefault();
            const el = $(this);
            const form = el.closest('form');
            const val = el.prev().val();
            const action = (val === 'aymakan_bulk_shipment' || val === 'aymakan_bulk_awb') ?
                `aymakan_bulk_${val.split('_').pop()}_create` : 'aymakan_manual_shipping_create';
            el.addClass('aymakan-disable-btn');
            form.addClass('aymakan-loading');
            const data = { action: action, data: form.serialize() };
            $.ajax({
                type: 'POST',
                url: aymakan_shipping.ajax_url,
                data: data,
                dataType: 'json',
                success: function (response) {
                    (val.includes('bulk')) ? aymakanShipping.handleBulkResponse(form, response) :
                        aymakanShipping.handleSingleResponse(form, response);
                },
                error: function (error) {
                    console.error(error);
                    el.removeClass('aymakan-disable-btn');
                    form.removeClass('aymakan-loading');
                }
            });
        },

        handleBulkResponse: function (form, response) {
            aymakanShipping.handleSingleResponse(form, response);
        },

        handleSingleResponse: function (form, response) {
            const notify = form.find('.notification');
            response.forEach(function (shipping) {
                const vendor = (typeof shipping.vendor === "undefined") ? '' : `(Vendor: ${shipping.vendor})`;
                if (shipping.success && shipping.id) {
                    notify.prepend(`<span class="dashicons-yes-alt aymakan-shipment-success">Aymakan Shipment Created. ${vendor}</span>`);
                    aymakanShipping.fadeOutNotification('.aymakan-shipment-success');
                }
                if (shipping.errors) {
                    aymakanShipping.showErrorMessages(form, notify, shipping.errors, vendor);
                }
                if (shipping.error) {
                    aymakanShipping.showErrorMessage(notify, `${shipping.message} ${vendor}`);
                }
            });
            form.removeClass('aymakan-loading');
        },

        showErrorMessages: function (form, notify, errors, vendor) {
            $.each(errors, function (key, value) {
                form.find(`input#${key}`).addClass('has-error');
                const message = `${value[0]} ${vendor}`;
                notify.prepend(`<span class="dashicons-warning noti-${key}">${message}</span>`).fadeIn('fast')
                    .find(`.noti-${key}`).delay(7000).fadeOut(1000, function () {
                    $(this).remove();
                });
            });
        },

        showErrorMessage: function (notify, message) {
            notify.prepend(`<span class="dashicons-warning">${message}</span>`).fadeIn('fast');
            aymakanShipping.fadeOutNotification('.dashicons-warning');
        },

        fadeOutNotification: function (selector) {
            setTimeout(function () {
                $(selector).fadeOut(1000, function () {
                    $(this).remove();
                });
            }, 1000);
        },

        aymakanNoticeDismiss: function () {
            $(this).parent().fadeOut(1000, function () {
                $(this).remove();
            });
        }
    };
    aymakanShipping.init();
});
