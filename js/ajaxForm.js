
new App.Module(function ($, app) {
    'use strict';
    var Module = {name : 'ajaxForm'},
        ajaxForm,
        forms = [],
        inputUsable,
        addInputValToData;

    ajaxForm = function ($form) {
        var self = this;
        forms.push(this);
        self.$form = $form.addClass('listening');
        self.id = $form.attr('id');
        self.url = $form.data('url') || '';
        self.inputs = $form.find('input, select, textarea');
        self.$results = $($form.data('results')).addClass('ajaxFormResults');
        self.type = String($form.attr('method') || 'get').toUpperCase();
        $form.submit(function () {
            self.submit();
            return false;
        });
    };

    ajaxForm.prototype.submit = function () {
        var data = {};
        this.$results.ajMask(1);
        this.inputs.each(function (i, E) {
            var $E = $(E);
            if (inputUsable($E)) {
                addInputValToData($E, data);
            }
        });
        app.ajax({
            url : this.url,
            data : data,
            type : this.type,
            recip : this.$results
        });
    };

    inputUsable = function ($input) {
        var type;
        if (!$input.attr('name')) return false;
        type = String($input.attr('type')).toLowerCase();
        return (
            type !== 'checkbox' &&
                type !== 'radio'
            ) || $input.prop('checked');
    };

    addInputValToData = function ($input, data) {
        var k = String($input.attr('name')),
            v = $input.val(),
            i, depth;
        depth = 0;
        while (k.match(/\[\]$/)) {
            k = k.replace(/\[\]$/, '');
            depth++;
        }
        for (i = 1; i < depth; i++) {
            v = [v];
        }
        if (depth) {
            if (!data[k]) data[k] = [];
            data[k].push(v);
        } else {
            data[k] = v;
        }
    };

    Module.getForms = function () {
        return forms;
    };

    Module.listen = function () {
        $(function () {
            $('form.ajaxForm').not('.listening').each(function (i, E) {
                new ajaxForm($(E));
            });
        });
    };
    Module.onReady = Module.listen;

    return Module;
});
