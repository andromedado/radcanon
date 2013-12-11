
(function ($, app) {
    'use strict';
    var tpls = {};

    if (!window._) {
        return;
    }


    app.tpl = function (name, data, settings) {
        if (tpls[name]) {
            return tpls[name](data, settings);
        }
        return '';
    };

    $(function () {
        $('script.template').each(function (i, E) {
            var $E;
            if (E.id) {
                $E = $(E);
                tpls[E.id] = _.template($E.html());
                $E.remove();
            }
        });
    });
}(jQuery, App));

