
window['cashInput'] = (function ($, app) {
	"use strict";
	var cashInput = {};
	
	cashInput.listen = function () {
		$('.cash_format').not('.cash_listening').change(function () {
			$(this).cashFormat();
		}).change().addClass('cash_listening');
	};
	
	$(cashInput.listen);
	
	return cashInput
}(jQuery, App));
