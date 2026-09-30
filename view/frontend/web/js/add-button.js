define([
    'jquery',
    'mage/translate',
    'Magento_Customer/js/customer-data',
    'jquery-ui-modules/widget',
    'mage/cookies'
], function ($, $t, customerData) {
    'use strict';

    /* Shared by all widget instances so a page reloads the section once. */
    var isSectionRefreshed = false;

    $.widget('mage.awFeiSpecialPricingAdd', {
        options: {
            addUrl: '',
            basketUrl: '',
            productId: 0,
            formSelector: '#product_addtocart_form',
            productUrl: '',
            buttonSelector: '[data-role="aw-fei-sp-add"]',
            messageSelector: '[data-role="aw-fei-sp-message"]',
            sectionName: 'aw-fei-sp'
        },

        /**
         * Bind click and subscribe to the private section.
         *
         * @private
         */
        _create: function () {
            this.section = customerData.get(this.options.sectionName);
            this.subscription = this.section.subscribe(this._toggle.bind(this));
            this._toggle(this.section());

            this.customer = customerData.get('customer');
            this.customerSubscription = this.customer.subscribe(this._refreshStaleSection.bind(this));
            this._refreshStaleSection();

            this._on(this.element.find(this.options.buttonSelector), {
                'click': this._onClick
            });
        },

        /**
         * Company role permissions can change while the section is cached in localStorage,
         * so a logged-in visitor without access re-checks it once per page.
         *
         * @private
         */
        _refreshStaleSection: function () {
            var data = this.section(),
                customer = this.customer();

            if (isSectionRefreshed || !(customer && customer.firstname) || (data && data.can_use)) {
                return;
            }

            isSectionRefreshed = true;
            customerData.reload([this.options.sectionName], false);
        },

        /**
         * Show the control only for allowed company users.
         *
         * @param {Object} data
         * @private
         */
        _toggle: function (data) {
            this.element.prop('hidden', !(data && data.can_use));
        },

        /**
         * Validate product options and send them to the basket; products with required options
         * in listings are sent to the product page to choose them.
         *
         * @private
         */
        _onClick: function () {
            var form = this.options.formSelector ? $(this.options.formSelector) : $(),
                payload;

            if (this.options.productUrl) {
                window.location.href = this.options.productUrl;

                return;
            }

            if (form.length) {
                if (form.data('mageValidation') && !form.validation('isValid')) {
                    return;
                }
                payload = form.serializeArray();
            } else {
                payload = [{name: 'product', value: this.options.productId}];
            }
            payload.push({name: 'form_key', value: $.mage.cookies.get('form_key')});

            this._setBusy(true);
            $.ajax({
                url: this.options.addUrl,
                type: 'POST',
                dataType: 'json',
                data: $.param(payload)
            }).done(this._onResponse.bind(this))
                .fail(function () {
                    this._showMessage($t('Something went wrong. Please try again.'), false);
                }.bind(this))
                .always(function () {
                    this._setBusy(false);
                }.bind(this));
        },

        /**
         * Render server response.
         *
         * @param {Object} response
         * @private
         */
        _onResponse: function (response) {
            this._showMessage(response.message || '', !!response.success);
        },

        /**
         * Show inline message; success messages link to the basket.
         *
         * @param {String} text
         * @param {Boolean} success
         * @private
         */
        _showMessage: function (text, success) {
            var container = this.element.find(this.options.messageSelector).empty()
                .toggleClass('aw-fei-sp-add__message--error', !success);

            container.append($('<span>').text(text));
            if (success) {
                container.append(' ').append(
                    $('<a>').attr('href', this.options.basketUrl).text($t('View basket'))
                );
            }
        },

        /**
         * Toggle busy state of the button.
         *
         * @param {Boolean} busy
         * @private
         */
        _setBusy: function (busy) {
            this.element.find(this.options.buttonSelector).prop('disabled', busy);
        },

        /**
         * Release subscription.
         *
         * @private
         */
        _destroy: function () {
            if (this.subscription) {
                this.subscription.dispose();
            }
            if (this.customerSubscription) {
                this.customerSubscription.dispose();
            }
            this._super();
        }
    });

    return $.mage.awFeiSpecialPricingAdd;
});
