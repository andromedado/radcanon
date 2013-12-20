/*!
 * jQuery Tiny Pub/Sub - v0.X - 11/18/2010
 * http://benalman.com/ - https://gist.github.com/661855
 *
 * Original Copyright (c) 2010 "Cowboy" Ben Alman
 * Dual licensed under the MIT and GPL licenses.
 * http://benalman.com/about/license/
 *
 * Made awesome by Rick Waldron - https://gist.github.com/705311
 * 08/21/2013 - Made less clever, but more useful by Shad Downey
 */

(function($){
    "use strict";
    var o = $({}),
        fired = {},
        originalOperator = $.operator,
        operator = {};
    $.operator = operator;
    $.operator.noConflict = function () {
        $.operator = originalOperator;
        return operator;
    };
    $.each(
        {
            "subscribe" : "on",
            "ever" : "on",
            "once" : "one",
            "onceEver" : "one",
            "unsubscribe" : "off",
            "publish" : "trigger"
        },
        /**
         * @param fn {string} method name to attach to the plugin (aka: key)
         * @param api {string} Mapped jQuery method name that `fn` refers to (aka: value)
         */
            function ( fn, api ) {
            operator[ fn ] = function () {
                o[ api ].apply( o, arguments );
                if (typeof arguments[0] === 'string') {
                    var args = Array.prototype.slice.call(arguments),
                    // space delimited events
                        eventNames = args[0].split(' ');
                    if (api === 'trigger') {
                        // We need to keep a record of fired events for `ever`'s sake
                        $.each(eventNames, function (i, eventName) {
                            fired[eventName] = args;
                        });
                    }
                    if ((fn === 'ever' || fn === 'onceEver') && typeof args[1] === 'function') {
                        // If we're binding `ever`/`onceEver`, then check the `fired` hash for a past occurrence
                        $.each(eventNames, function (i, eventName) {
                            if (fired[eventName]) {
                                args[1].apply(o, fired[eventName]);
                                if (fn === 'onceEver') {
                                    //If this was a onceEver, we need to take it back off now
                                    o.off(eventName, args[1]);
                                }
                            }
                        });
                    }
                }
            };
        }
    );
    /**
     * Takes list of publish event names, and returns a promise for
     * when all of them have ever occurred
     * e.g.
     *      $.operator.subWhen('foo', 'bar').then(function () {
     *          console.log('STUFF GETS DONE!');
     *      });
     *      $.operator.publish('foo');
     *      // subscribers to 'foo' do something
     *      $.operator.publish('bar');
     *      // subscribers to 'bar' do something
     *      // then
     *      // STUFF GETS DONE!
     * @returns jQuery.Promise
     */
    operator.subWhen = function (/* eventName, ... */) {
        var promises = [];
        $.each(arguments, function (i, eventName) {
            var def = $.Deferred();
            $.operator.onceEver(eventName, function () {
                def.resolve.apply(def, arguments);
            });
            promises.push(def.promise());
        });
        return $.when.apply($, promises);
    };
})(jQuery);