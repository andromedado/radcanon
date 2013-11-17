
(function ($) {
    'use strict';

    function handleToggle(e) {
        var what = $(this).data('toggle');
        if (what) {
            $(what).toggle();
        }
    }

    $(function () {
        $(document).on('click', '.toggle', handleToggle);
    });
}(jQuery));

