/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define([
    'jquery',
    'uiRegistry',
    'underscore',
    'segmentUsers',
    'Magento_Ui/js/modal/modal',
    'hierarchyTreePopup',
    'mage/translate'
], function ($, registry, _, segmentUsers) {
    'use strict';

    $.widget('mage.userEdit', {

        options: {
            popup: '[data-role="add-customer-dialog"]',
            statusSelect: '[data-role="status-select"]',
            roleSelect: '[data-role="role-select"]',
            approvalSelect: '[data-role="segments-approval-select"]',
            filterSelect: '[data-role="segments-filter-select"]',
            isAjax: false,
            gridProvider: '',
            adminUserRoleId: 0,
            getUserUrl: '',
            additionalFields: {
                create: '[data-role="create-additional-fields"]',
                edit: '[data-role="edit-additional-fields"]'
            }
        },

        /**
         * Create widget
         *
         * @private
         */
        _create: function () {
            this._setModal();
            this._bind();
        },

        /**
         * Bind listeners on elements
         *
         * @private
         */
        _bind: function () {
            this._on({
                'editUser': 'editUser',
                'click': 'editUser',
                'reloadTheTree': '_reloadGrid'
            });
        },

        /**
         * Callback for edit event
         *
         * @param {Object} e
         * @public
         */
        editUser: function (e) {
            var title = this.options.id ? $.mage.__('Edit User') : $.mage.__('Add New User');

            if (e) {
                e.preventDefault();
            }
            this.options.popup.modal('setTitle', title);
            this.options.popup.modal('openModal');
            this._populateForm();
            this._setIdFields();

            if (!this.options.id) {
                this._filterRoles('role');
            }
            segmentUsers();
        },

        /**
         * Toggle show addition fields
         *
         * @param {Boolean} isRegisterForm
         * @private
         */
        showAdditionalFields: function (isRegisterForm) {
            $(this.options.additionalFields.create).toggleClass('_hidden', isRegisterForm)
                .find('[name]').prop('disabled', isRegisterForm);
            $(this.options.additionalFields.edit).toggleClass('_hidden', !isRegisterForm)
                .find('[name]').prop('disabled', !isRegisterForm);
        },

        /**
         * Callback for reload event
         *
         * @private
         */
        _reloadGrid: function () {
            this._getGridProvider().reload({
                refresh: true
            });
        },

        /**
         * Get provider
         *
         * @private
         */
        _getGridProvider: function () {
            if (!this.gridProvider) {
                this.gridProvider = registry.get(this.options.gridProvider);
            }

            return this.gridProvider;
        },

        /**
         * Set id customer to field in form
         *
         * @private
         */
        _setIdFields: function () {
            this.options.popup.find('[name="customer_id"]').val(this.options.id);
        },

        /**
         * Set modal for edit customer
         *
         * @private
         */
        _setModal: function () {
            var self = this;

            this.options.popup = $(this.options.popup).hierarchyTreePopup({
                popupTitle: self.options.popupTitle,
                treeSelector: self.element,
                buttons: [{
                    text: $.mage.__('Save'),
                    'class': 'action save primary',

                    /** @inheritdoc */
                    click: function () {
                        self.options.popup.trigger('sendForm');
                    }
                }, {
                    text: $.mage.__('Cancel'),
                    'class': 'action cancel secondary',

                    /** @inheritdoc */
                    click: function () {
                        this.closeModal();
                    }
                }]
            });
        },

        /**
         * Set data to popup form fields
         *
         * @param {String} name
         * @param {String} value
         * @private
         */
        _setPopupFields: function (name, value) {
            var self = this;

            if (name === 'role') {
                self._filterRoles(name, value);
            }
            this.options.popup.find('form [name="' + name + '"]').val(value);
            segmentUsers();
            var value = $("#role option:selected");
            var isSegment = value.text().toLowerCase();
            if(isSegment.length){
                $("#segment_filter").show();
            }
        },

        /**
         * Set multi line values
         *
         * @param {String} name
         * @param {String} id
         * @param {String} value
         */
        setMultilineValues: function (name, id, value) {
            var self = this;

            if (name === 'role') {
                self._filterRoles(name, value);
            }

            this.options.popup.find('form [id="' + id + '"]').val(value);
        },

        /**
         * Set multi select options
         *
         * @param {String} name
         * @param {String} value
         */
        setMultiSelectOptions: function (name, value) {
            var self = this,
                selectValues =  value.split(',');

            if (name === 'role') {
                self._filterRoles(name, value);
            }
            if (name === 'customer_account_create-segment_filter[]') {
                self._removeFilterOptions();
                self._filterSegment(name, value);
            }

            if (name === 'customer_account_create-segment_approval[]') {
                self._removeApprovalOptions();
                self._approvalSegment(name, value);
            }

            this.options.popup.find('form [name="' + name + '"]').val(selectValues);
        },

        _removeFilterOptions: function () {
            $("#segment_filter option").each(function(i){
                $(this).prop('selected', false);
            });
        },

        _removeApprovalOptions: function () {
            $("#segment_approval option").each(function(i){
                $(this).prop('selected', false);
            });
        },

        _filterSegment: function (name, value) {
            var selectFilter = this.options.popup.find(this.options.filterSelect);
            var arr = value.split(',');
            $.each(arr , function(index, val) {
                var approvalFilter = selectFilter.find('[value=' + val + ']');
                approvalFilter.prop('selected', true);
            });
        },

        _approvalSegment: function (name, value) {
            var selectApproval = this.options.popup.find(this.options.approvalSelect);
            var arr = value.split(',');
            $.each(arr , function(index, val) {
                var approvalApply = selectApproval.find('[value=' + val + ']');
                approvalApply.prop('selected', true);
            });
        },

        /**
         * Fill roles input field.
         *
         * @param {String} name
         * @param {String} value
         * @private
         */
        _filterRoles: function (name, value) {
            var selectRoles = this.options.popup.find(this.options.roleSelect),
                statusSelect = this.options.popup.find(this.options.statusSelect),
                optionsRole = selectRoles.find('option'),
                adminRole = selectRoles.find('[value=' + this.options.adminUserRoleId + ']'),
                condition = value === this.options.adminUserRoleId;

            selectRoles.prop('disabled', condition);
            statusSelect.prop('disabled', condition);
            optionsRole.toggle(!condition);
            adminRole.toggle(condition);
            adminRole.attr('disabled', condition ? 'disabled' : '');

            if (_.isUndefined(value)) {
                optionsRole.first().attr('selected', 'selected');
            }
        },

        /**
         * Populate form
         *
         * @private
         */
        _populateForm: function () {
            var self = this;

            this.showAdditionalFields(!this.options.id);
            this.options.popup.find('input').val('');
            this.options.popup.find('select').val('');
            this.options.popup.find('textarea').val('');

            if (!this.options.isAjax && this.options.id) {
                this.options.isAjax = true;

                this.options.popup.addClass('unpopulated');
                this.options.popup.find('input').attr('disabled', true);

                $.ajax({
                    url: self.options.getUserUrl,
                    type: 'get',
                    showLoader: true,

                    /**
                     * @callback
                     */
                    success: $.proxy(function (data) {
                        var that = this;

                        this.options.popup.find('input').attr('disabled', false);

                        if (data.status === 'ok') {
                            $.each(data.data, function (idx, item) {
                                if (idx === 'custom_attributes') {
                                    $.each(item, function (name, itemData) {
                                        var customAttributeCode = itemData['attribute_code'],
                                            issetPopupField = false,
                                            multilineAttributeCode,
                                            multilineAttributeValue,
                                            multilineAttributeId,
                                            multiSelectAttributeCode,
                                            key;

                                        if (itemData.hasOwnProperty('attributeType')) {
                                            customAttributeCode = 'customer_account_create-'.
                                            concat(customAttributeCode);
                                        }

                                        if (itemData.hasOwnProperty('attributeType') && itemData.value) {

                                            if (itemData.attributeType === 'multiline') {

                                                multilineAttributeCode = customAttributeCode + '[]';
                                                multilineAttributeValue = itemData.value.split('\n');

                                                // eslint-disable-next-line max-depth
                                                for (key = 0; key < multilineAttributeValue.length; key++) {
                                                    multilineAttributeId = customAttributeCode + '_' + key;

                                                    that.setMultilineValues(
                                                        multilineAttributeCode,
                                                        multilineAttributeId,
                                                        multilineAttributeValue[key]
                                                    );

                                                    issetPopupField = true;
                                                }
                                            } else if (itemData.attributeType === 'multiselect') {

                                                multiSelectAttributeCode = customAttributeCode + '[]';

                                                that.setMultiSelectOptions(multiSelectAttributeCode, itemData.value);

                                                issetPopupField = true;
                                            }
                                        }

                                        if (!issetPopupField) {
                                            that._setPopupFields(customAttributeCode, itemData.value);
                                        }
                                    });
                                }
                                that._setPopupFields(idx, item);
                            });
                            this.options.popup.removeClass('unpopulated');
                        }
                    }, this),

                    /**
                     * @callback
                     */
                    complete: function () {
                        self.options.isAjax = false;
                    }
                });
            }
        }
    });

    return $.mage.userEdit;
});
