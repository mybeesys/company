/**
 * Always format numbers with Latin digits (0123456789), even when the OS/UI locale is Arabic.
 */
(function () {
    if (window.__beeLatinDigitsPatched) {
        return;
    }
    window.__beeLatinDigitsPatched = true;

    function withLatn(options) {
        var o = {};
        if (options && typeof options === 'object') {
            for (var key in options) {
                if (Object.prototype.hasOwnProperty.call(options, key)) {
                    o[key] = options[key];
                }
            }
        }
        o.numberingSystem = 'latn';
        return o;
    }

    function patchConstruct(Original) {
        return new Proxy(Original, {
            construct: function (target, args) {
                return new target(args[0], withLatn(args[1]));
            },
            apply: function (target, thisArg, args) {
                return target(args[0], withLatn(args[1]));
            },
        });
    }

    if (typeof Intl !== 'undefined' && Intl.NumberFormat) {
        Intl.NumberFormat = patchConstruct(Intl.NumberFormat);
    }
    if (typeof Intl !== 'undefined' && Intl.DateTimeFormat) {
        Intl.DateTimeFormat = patchConstruct(Intl.DateTimeFormat);
    }

    var origNumber = Number.prototype.toLocaleString;
    Number.prototype.toLocaleString = function (locales, options) {
        return origNumber.call(this, locales, withLatn(options));
    };

    ['toLocaleString', 'toLocaleDateString', 'toLocaleTimeString'].forEach(function (name) {
        var orig = Date.prototype[name];
        if (typeof orig !== 'function') {
            return;
        }
        Date.prototype[name] = function (locales, options) {
            return orig.call(this, locales, withLatn(options));
        };
    });
})();
