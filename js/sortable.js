
(function ($) {
    'use strict';
    if (!$.ui) {
        window.console && window.console.warn && window.console.warn('Missing jQuery UI!');
        return;
    }

    $(function () {
        $('.sortable').each(function (i, E) {
            var $list = $(E),
                options = $list.data();
            $list.sortable(options);
        });
    });
}(jQuery));

