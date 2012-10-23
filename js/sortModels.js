
(function ($, app, undefined) {
	"use strict";
	var init,
		$AllSorts = $(),
		getCurrentOrder,
		beforeStop;
	
	getCurrentOrder = function ($Sorting) {
		var ids = [];
		$Sorting.children().each(function (i, E) {
			var id = String($(E).attr('id')).replace(/\D+/g, '');
			if (id) ids.push(id);
		});
		return ids;
	};
	
	beforeStop = function (event, ui) {
		var $Sorting = ui.item.parent();
		$Sorting.sortable('disable').css({opacity : 0.6});
		app.ajax({
			url : $Sorting.data('url'),
			type : 'POST',
			data : {ids : getCurrentOrder($Sorting)},
			error : function () {
				$Sorting.sortable('cancel');
			},
			complete : function () {
				$Sorting.sortable('enable').css({opacity : 1});
			}
		});
	};
	
	init = function () {
		if (!$.ui) return false;
		$AllSorts = $('.sortablModels').each(function (i, E) {
			var $E = $(E);
			$E.data('url', $('#' + $E.attr('id') + '_url').val());
		}).sortable({
			axis : 'y',
			handle : '.drag_handle',
			beforeStop : beforeStop
		}).disableSelection();
	};
	
	$(init);
}(jQuery, App));
