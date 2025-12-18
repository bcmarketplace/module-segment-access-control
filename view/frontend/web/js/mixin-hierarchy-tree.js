define([
    'jquery',
    ],
    function ($) {
    'use strict';

        return function (widget) {
            $.widget('mage.hierarchyTree', widget, {

            setMultiSelectOptions: function (name, value) {
                var self = this,
                    selectValues =  value.split(',');

                if (name === 'role') {
                    self._filterRoles(name, value);
                }
                if(typeof this.options.popup !== 'undefined' && typeof this.options.popup.find('form [name="' + name + '"]') !== 'undefined'){
                    this.options.popup.find('form [name="' + name + '"]').val(selectValues);
                }
            }
        });
            return $.mage.hierarchyTree;
    };
});
