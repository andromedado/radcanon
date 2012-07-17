
new App.Module(function ($) {
	"use strict";
	var Module = {name : 'ajScreen'},
		ajScreen,
		$ajScreen = $('<div id="ajScreen" />'),
		$ajFWrap = $('<div id="ajForms_Wrap" />'),
		$ajForms = $('<div id="ajForms" />'),
		$wrapper = $(),
		clearAjScreen,
		resizeBound,
		hideTO,
		fit;
	
	fit = function () {
		$ajScreen.height($(window).height());
		$ajScreen.width($(window).width());
		$ajFWrap.css({'margin-left':($(window).width() / 2) - ($('#ajForms_Wrap').width() / 2)});
	};
	
	Module.show = function (onReady) {
		var stagger = 100,
			waiting = false;
		onReady = onReady || function () {};
		if(!$ajScreen.onScreen){
			$ajScreen.prependTo($wrapper).animate({opacity : 0.8}, 'fast', 'swing', onReady);
			$ajScreen.onScreen = true;
		} else {
			onReady();
		}
		if (!resizeBound) {
			$(window).resize(function () {
				if (!waiting) {
					fit();
					setTimeout(function () {waiting = false;}, stagger);
				}
			});
			resizeBound = true;
		}
		fit();
		return Module;
	};
	
	Module.hide = function () {
		clearTimeout(hideTO);
		$(window).unbind('resize');
		resizeBound = false;
		$ajScreen.stop().animate({opacity : 0}, 300, 'swing', function(){
			$ajScreen.remove();
			$ajScreen.onScreen = false;
		});
		$ajFWrap.stop().animate({opacity : 0}, 300, 'swing', function(){
			$ajFWrap.remove().css({opacity : 1});
			$ajFWrap.onScreen = false;
		});
		return Module;
	};
	
	Module.delayedHide = function (delay) {
		delay = delay || 1500;
		hideTO = setTimeout(Module.hide, delay);
	};
	
	Module.prompt = function (url, data) {
		var tD, bits, i, l;
		if (!url.match(RegExp('\^' + App.subDir))) {
			url = App.subDir + url;
		}
		data = data || {};
		if (typeof data == 'string') {
			tD = data.split(',');
			data = {};
			for (i = 0, l = tD.length; i < l; i++) {
				bits = tD[i].split(':');
				data[bits[0]] = bits[1];
			}
		}
		Module.show(function () {
			if(!$('#ajForms').length){
				$ajFWrap.html('<img id="ajScreenClose" src="images/close2.png" alt="Close" title="Close" border="0" /><table border="0" cellspacing="0" cellpadding="0" id="ajForms_table"><tr><td id="ajTL" class="top left"><div>&nbsp;</div></td><td id="ajTC" class="top center"><div>&nbsp;</div></td><td id="ajTR" class="top right"><div>&nbsp;</div></td></tr><tr><td id="ajML" class="middle left"><div>&nbsp;</div></td><td id="ajMC" class="middle center"><div id="ajForms"></div></td><td id="ajMR" class="middle right"><div>&nbsp;</div></td></tr><tr><td id="ajBL" class="bottom left"><div>&nbsp;</div></td><td id="ajBC" class="bottom center"><div>&nbsp;</div></td><td id="ajBR" class="bottom right"><div>&nbsp;</div></td></tr></table>').prependTo($wrapper);
			}else{
				$('#ajForms').empty();
			}
			fit();
			App.ajax({url : url,
				data : data,
				recip : $('#ajForms'),
				complete : function () {
					fit();
				}
			});
		});
		return Module;
	};
	
	$(function () {
		$wrapper = $('#wrapper');
		$('body').delegate('#ajScreen, #ajScreenClose, .ajScreenClose', 'click', Module.hide);
	});
	
	return Module;
});
