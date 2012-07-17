
var App = (function ($) {
	"use strict";
	var app = {
			subDir : '',
			debugLevel : 1,
		},
		Body,
		$body,
		Modules = {},
		ModuleNames = [],
		provisionalLocks = {},
		moduleQueue = {},
		ajaxLoadingGifs = [],
		maxLoadingGifs = 2,
		additionalFormValidations = {},
		Gif;

	/**
	 * Modules
	 */
	
	/**
	 * App.Module constructor
	 * Manage the addition of Modules to this App
	 * @param Function func The object returned from calling this function is the Module
	 */
	app.Module = function (func) {
		var O, i;
		if (typeof func !== 'function') {
			throw "Constructor only accepts 'Function' as parameter";
		}
		O = func($, app);
		if (!O || (!O.name && !O.moduleName)) {
			throw "Malformed Module returned; Must return an Object with a 'moduleName' property";
		}
		O.moduleName = O.name || O.moduleName;
		Modules[O.moduleName] = app[O.moduleName] = O;
		ModuleNames.push(O.moduleName);
		if (Modules[O.moduleName].onReady) Modules[O.moduleName].onReady();
	};
	
	/**
	 * Get a list of loaded Modules
	 * @return Array
	 */
	app.getModules = function () {
		return ModuleNames.slice();
	};
	
	/**
	 * Is the given Module Loaded?
	 * @return Boolean
	 */
	app.moduleLoaded = function (moduleName) {
		return $.inArray(moduleName, ModuleNames) !== -1;
	};
	
	/**
	 * Execute the given method, of the given Module, with the given Arguments
	 * Safe to call _before_ the module has even loaded
	 * @param String moduleName Module Name
	 * @param String funcName Module Method Name
	 * @param Array args Arguments to pass into the method
	 * @return App
	 */
	app.moduleCall = function(moduleName, funcName, args) {
		return app.loadModule(moduleName, function () {
			if (Modules[moduleName][funcName]) {
				Modules[moduleName][funcName].apply(Modules[moduleName], args);
			}
		});
	};
	
	/**
	 * Load the given Module
	 * Safe to call with a module that has already been loaded
	 * 
	 * @param String moduleName Module Name
	 * @param Function onLoad Function to be called as soon as the Module has Loaded
	 * @param Function onError Function to be called if loading fails
	 * @return App
	 */
	app.loadModule = function (moduleName, onLoad, onError) {
		var s;
		if (app.moduleLoaded(moduleName)) {
			if (onLoad) onLoad();
			return app;
		}
		if (onLoad) {
			if (!moduleQueue[moduleName]) moduleQueue[moduleName] = [];
			moduleQueue[moduleName].push(onLoad);
		}
		if (provisionalLocks[moduleName]) return app;
		provisionalLocks[moduleName] = true;
		s = document.createElement('script');
		s.src = 'js/' + moduleName + '.js';
		s.type = 'text/javascript';
		s.onload = function () {
			var i, l;
			Body.removeChild(s);
			provisionalLocks[moduleName] = false;
			if (moduleQueue[moduleName]) {
				for (i = 0, l = moduleQueue[moduleName].length; i < l; i++) {
					moduleQueue[moduleName][i]();
				}
			}
		};
		s.onerror = function () {
			Body.removeChild(s);
			provisionalLocks[moduleName] = false;
			if (onError) onError();
		}
		$(function () {Body.appendChild(s)});
		return app;
	};
	
	for (var i = 1; i <= maxLoadingGifs; i++) {
		Gif = new Image();
		Gif.src = app.subDir + '/images/ajax_loader_' + i + '.gif';
		Gif.alt = 'Loading...';
		Gif.className = 'ajaxLoadingGif gif_' + i;
		ajaxLoadingGifs.push(Gif);
	}
	
	app.getAjMask = function (which) {
		if (!ajaxLoadingGifs[which]) which = 0;
		return ajaxLoadingGifs[which];
	};
	
	$.fn.ajMask = function (which) {
		return this.each(function (i, E) {
			$(E).html(app.getAjMask(which));
		});
	};
	
	/**
	 * Top Level Utilities
	 */
	
	app.parseInt = function (str){
		if (!str) return 0;
		return parseInt(String(str).replace(/[^\d\.-]/g, ''), 10) || 0;
	};

	app.parseFloat = function (str) {
		if (!str) return 0;
		return parseFloat(String(str).replace(/[^\d\.-]/g, '')) || 0;
	}

	app.notZero = function (fl) {
		return (Math.floor(fl)<0 && fl + 0.001 < 0) || (Math.ceil(fl)>0 && fl - 0.001 > 0);
	}
	
	app.addCommas = function (nStr) {
		var x, x1, x2, rgx = /(\d+)(\d{3})/;
		nStr = String(nStr);
		x = nStr.split('.');
		x1 = x[0];
		x2 = x.length > 1 ? '.' + x[1] : '';
		while (rgx.test(x1)) {
			x1 = x1.replace(rgx, '$1' + ',' + '$2');
		}
		return x1 + x2;
	};
	
	app.numberFormat = function (str) {
		return app.addCommas(app.parseFloat(str).toFixed(0));
	};
	
	app.cashFormat = function (str) {
		var cash = app.parseFloat(str),
			sign = cash + 0.01 > 0 ? '' : '-';
		return sign + '$' + app.addCommas(Math.abs(cash).toFixed(2));
	};
	
	app.bigCashFormat = function (str) {
		var cash = app.parseFloat(str),
			sign = cash + 0.01 > 0 ? '' : '-';
		return sign + '$' + app.addCommas(Math.abs(cash).toFixed(0));
	};

	app.percentageFormat = function (str, precision) {
		var perc, sign;
		precision = Number(precision) || 1;
		perc = app.parseFloat(str) || 0;
		sign = perc + 0.01 > 0 ? '' : '-';
		return sign + Math.abs((Math.round(perc * Math.pow(10, precision))) / Math.pow(10, precision)).toFixed(precision) + '%';
	};
	
	/**
	 * Wait-For-It
	 * A specialized promise creator
	 * @param {function} test
	 * @param {function} done
	 * @param {function} fail
	 * @param {int} frequency
	 * @param {int} timeOut
	 * @return jQuery.Deferred
	 */
	app.waitForIt = function (test, done, fail, frequency, timeOut) {
		var D = $.Deferred(), interv, TO;
		fail = fail || function () {};
		D.then(done, fail);
		if (test()) {
			D.resolve();
			return D;
		}
		frequency = frequency || 500;
		timeOut = timeOut || 10000;
		interv = window.setInterval(function () {
			if (test()) {
				window.clearTimeout(TO);
				window.clearInterval(interv);
				D.resolve();
			}
		}, frequency);
		TO = window.setTimeout(function () {
			window.clearInterval(interv);
			D.reject();
		}, timeOut);
		return D;
	};

	/**
	 * Generic form validation
	 */
	app.genericValidate = function (e) {
		var F, $F, Fe, RETURN = true, i, l, fid;
		e = e || window.event;
		F = e.target || e.srcElement;
		$F = $(F);
		fid = $F.attr('id');
		$F.find('.not_blank').each(function(i,E){
			if ($.trim(E.value).length<1) {
				Fe = Fe || $(E);
				$(E).addClass('invalid');
				RETURN=false;
				return;
			}
			$(E).removeClass('invalid');
		});
		if (!RETURN) {
			Fe.focus();
			alert('A required field is missing');
			return false;
		}
		$F.find('.email_required').each(function(i,E){
			if(!E.value.match(/[A-Z_\.\d]+@[^\.]+\.[^\.]+/i)){
				Fe = Fe || $(E);
				$(E).addClass('invalid');
				RETURN=false;
				return;
			}
			$(E).removeClass('invalid');
		});
		if (!RETURN) {
			Fe.focus();
			alert('A valid E-Mail Address is required');
			return false;
		}
		$F.find('.cc_field').each(function(i,E){
			if(!E.value.replace(/\D/g,'').match(/\d{13,16}/)){
				Fe = Fe || $(E);
				$(E).addClass('invalid');
				RETURN=false;
				return;
			}
			$(E).removeClass('invalid');
		});
		if (!RETURN) {
			Fe.focus();
			alert('Please provide a valid Card Number');
		}
		if (fid && additionalFormValidations[fid]) {
			for (i = 0, l = additionalFormValidations[fid].length; i < l; i++) {
				RETURN = RETURN && additionalFormValidations[fid][i]($F);
			}
		}
		return RETURN;
	}
	
	/**
	 * @param {String} fid
	 * @param {Function} func
	 * @return void
	 */
	app.addFormValidation = function (fid, func) {
		if (!additionalFormValidations[fid]) additionalFormValidations[fid] = [];
		additionalFormValidations[fid].push(func);
	};
	
	/**
	 * Curry-ed jQuery Ajax
	 * @param Object
	 * @return jqXHR
	 */
	app.ajax = function (obj) {
		obj.dataType = obj.dataType || 'json';
		obj.headers = obj.headers || {};
		obj.headers.REQBY = 'ajax';
		obj.data = obj.data || {};
		obj.data.requestType = 'ajax';
		if (obj.recip) {
			obj.error = obj.error || function() {
				obj.recip.text('There was an error. Please try again later');
			};
		}
		obj.success = obj.success || function(data){
			if (data.html && obj.recip) obj.recip.html(data.html);
			if (data.js) {
				try {
					eval(data.js);
				} catch (e) {
					if (window.console && window.console.log) {
						window.console.log(e);
					}
				}
			}
			return true;
		};
		return $.ajax(obj);
	}
	
	/**
	 * Aliases of Module Methods for quick/direct access
	 */
	
	/**
	 * ajScreen's Prompt
	 */
	app.prompt = function() {
		var args = arguments;
		return app.moduleCall('ajScreen', 'prompt', args);
	}
	
	/**
	 * Perform Setup that needs the DOM to be ready
	 */
	
	$(function () {
		var wSmall = false, first = true, smallWidth = 783;
		Body = document.getElementsByTagName('body')[0];
		$body = $('body').on('submit', 'form', function (ev) {
			var r = app.genericValidate(ev);
			$.data(this, 'genericlyValid', r);
			if (r !== true) {
				if (ev.stopPropagation) ev.stopPropagation();
				if (ev.preventDefault) ev.preventDefault();
			}
			return r;
		});
		$('input.invisible').keydown(function (ev) {
			if (!ev.metaKey) {
				if (ev.preventDefault) {
					ev.preventDefault();
				}
				return false;
			}
		});
		$(window).resize(function () {
			var w = $(this).width();
			if ((first || wSmall === true) && w > smallWidth) {
				wSmall = false;
				$body.removeClass('small').addClass('normal');
			} else if ((first || wSmall === false) && w <= smallWidth) {
				wSmall = true;
				$body.removeClass('normal').addClass('small');
			}
			first = false;
		}).resize();
	});
	
	return app;
}(jQuery)), clog, clog1, clog2, clog3, clogAt;

clogAt = function (args, level) {
	try {
		if (App.debugLevel >= level && window.console && window.console.log) {
			window.console.log.apply(window.console, args);
		}
	} catch (e) {};
};

clog = clog1 = function () {
	return clogAt(arguments, 1);
};

clog2 = function () {
	return clogAt(arguments, 2);
};

clog3 = function () {
	return clogAt(arguments, 3);
};
