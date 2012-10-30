
new App.Module(function ($) {
	var Module = {name : 'dependency'};
	
	$.fn.mkReq = function () {
		return this.each(function () {
			$(this).addClass('not_blank').
				unbind('change blur keyup', Satisfaction).
				bind('change blur keyup', Satisfaction).
				change().
				parent().
				addClass('inputReq');
		});
	};
	
	$.fn.rmReq = function () {
		return this.each(function () {
			$(this).removeClass('not_blank').
				unbind('change blur keyup', Satisfaction).
				parent().
				removeClass('inputReq');
		});
	};
	
	function Satisfaction() {
		if (!this.nodeType || this.nodeType !== 1) return;
		if($.trim($(this).val()) != ''){
			$(this).parent().addClass('inputSat');
		}else{
			$(this).parent().removeClass('inputSat');
		}
		return;
	};
	
	function dependency(id, conditionMet){
		if(conditionMet){
			$('#' + id).removeClass('none').addClass('supplement');
			$('#' + id + ' input, #' + id + ' select, #' + id + ' textarea').mkReq();
		} else {
			$('#' + id).addClass('none').removeClass('supplement');
			$('#' + id + ' input, #' + id + ' select, #' + id + ' textarea').rmReq();
		}
	}
	
	Module.ynDepend = function (InpId, WhatView) {
		Module.establishDependency(InpId, 'yes', WhatView);
		return Module;
	};
	
	Module.radioDepend = function (InpId, WhatView) {
		$(function () {
			Module.establishDependency(InpId, $('#' + InpId).val(), WhatView, true);
		});
		return Module;
	};
	
	Module.multiDepend = function (whatToViewId, dependencyCheckFunc, $whoToListenTo) {
		if (!$whoToListenTo.jquery) throw 'invalid `whoToListenTo`, jQ obj required';
		$whoToListenTo.change(function () {
			dependency(whatToViewId, dependencyCheckFunc());
		});
		$(function () {
			dependency(whatToViewId, dependencyCheckFunc());
		});
	};
	
	Module.establishDependency = function (InpId, SensVal, WhatToViewId, che) {
		var selector;
		che = che || false;
		$(function () {
			if(che){
				selector='[name="' + $('#' + InpId).attr('name') + '"]';
			} else {
				selector='#' + InpId;
			}
			$(selector).change(function(){
				dependency(WhatToViewId,$('#' + InpId).val() == SensVal && (!che || $('#' + InpId + ':checked').length>0));
			}).change();
		});
		return Module;
	}
	
	Module.gtZeroDepend = function (InpId, WhatView){
		Module.establishCompareDependency(InpId, '>', 0, WhatView);
		return Module;
	}
	
	Module.establishCompareDependency = function (InpId, CompT, Comp, WhatToViewId){
		selector = '#' + InpId;
		$(function () {
			$(selector).change(function(){
				var val = Number($('#' + InpId).val().replace(/\D\.-/g,''));
				var conditionMet;
				switch(CompT){
					case '>':
						conditionMet = val > Comp;
					break;
					case '<':
						conditionMet = val > Comp;
					break;
					case '>=':
						conditionMet = val >= Comp;
					break;
					case '<=':
						conditionMet = val <= Comp;
					break;
					case '==':
						conditionMet = val == Comp;
					break;
					default:
						conditionMet = val === Comp;
				}
				dependency(WhatToViewId, conditionMet);
				return true;
			}).change();
		});
		return Module;
	}
	
	Module.establishInArrayDependency = function (InpId, CompArray, WhatToViewId){
		selector = '#' + InpId;
		$(function () {
			$(selector).change(function () {
				var val = $('#' + InpId).val(),
					sVal = val,
					conditionMet;
				if ($.isNumeric(val)) val = Number(val);
				conditionMet = $.inArray(sVal, CompArray) >= 0 || $.inArray(val, CompArray) >= 0;
				dependency(WhatToViewId, conditionMet);
				return true;
			}).change();
		});
		return Module;
	}
	
	return Module;
});