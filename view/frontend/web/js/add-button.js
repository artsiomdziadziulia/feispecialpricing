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
            attributeIds: [],
            tileSelector: '.product-item-info',
            swatchAttributeSelector: '.swatch-attribute',
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
         * Validate product options and send them to the basket; in listings, products whose options
         * are not fully chosen on the tile are sent to the product page to choose them.
         *
         * @private
         */
        _onClick: function () {
            var form = this.options.formSelector ? $(this.options.formSelector) : $(),
                superAttributes,
                payload;

            if (this.options.productUrl) {
                superAttributes = this._getTileSuperAttributes();

                if (!superAttributes) {
                    window.location.href = this.options.productUrl;

                    return;
                }
                payload = [{name: 'product', value: this.options.productId}].concat(superAttributes);
            } else if (form.length) {
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
         * Collect swatch selections of the listing tile; null unless every configurable attribute is chosen.
         *
         * @returns {Array|null}
         * @private
         */
        _getTileSuperAttributes: function () {
            var tile = this.element.closest(this.options.tileSelector),
                result = [],
                isComplete;

            if (!this.options.attributeIds.length) {
                return null;
            }

            isComplete = this.options.attributeIds.every(function (attributeId) {
                var value = tile.find(
                    this.options.swatchAttributeSelector + '[data-attribute-id="' + attributeId + '"]'
                ).attr('data-option-selected');

                if (!value) {
                    return false;
                }
                result.push({name: 'super_attribute[' + attributeId + ']', value: value});

                return true;
            }, this);

            return isComplete ? result : null;
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
