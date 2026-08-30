/**
 * Select2 for server-rendered invoice line rows (duplicate / convert / prefilled)
 * and for non-product selects on newly appended rows.
 * Skips empty product-select rows (single placeholder) so ajax init on "add row" stays intact.
 */
(function (window, $) {
    "use strict";
    if (typeof $ === "undefined" || !$ || !$.fn || !$.fn.select2) {
        return;
    }

    function getLineItemsDropdownParent() {
        var $table = $("#salesTable");
        if (!$table.length) {
            return $(document.body);
        }
        var $parent = $table.closest(".card-body");
        if (!$parent.length) {
            $parent = $table.closest(".table-responsive").parent();
        }
        if (!$parent.length) {
            $parent = $("body");
        }
        return $parent;
    }

    function isEmptyAjaxProductSelect($el) {
        return (
            $el.hasClass("product-select") &&
            $el.find("option").length <= 1
        );
    }

    function minimumResultsForSearchFor($el, optCount) {
        if ($el.hasClass("product-select")) {
            return 0;
        }
        return optCount > 25 ? 0 : Infinity;
    }

    /** After "add row": init unit / discount / tax with native widths (no 100% stretch in table cells). */
    window.initNewSalesLineNonProductSelect2 = function () {
        var $parent = getLineItemsDropdownParent();
        var $row = $("#salesTable tbody tr:last");
        if (!$row.length) {
            return;
        }
        $row.find("select.select-2:not(.product-select)").each(function () {
            var $el = $(this);
            if ($el.data("select2")) {
                try {
                    $el.select2("destroy");
                } catch (e) {
                    /* ignore */
                }
            }
            var optCount = $el.find("option").length;
            $el.select2({
                width: "resolve",
                dropdownParent: $parent,
                minimumResultsForSearch: minimumResultsForSearchFor($el, optCount),
            });
        });
    };

    window.initPrefilledSalesLineSelect2 = function () {
        var $table = $("#salesTable");
        if (!$table.length) {
            return;
        }

        var $parent = getLineItemsDropdownParent();

        $table.find("tbody tr.sales-line-row").each(function () {
            var $row = $(this);

            $row.find("select.select-2").each(function () {
                var $el = $(this);
                if (isEmptyAjaxProductSelect($el)) {
                    return;
                }
                if ($el.data("select2")) {
                    try {
                        $el.select2("destroy");
                    } catch (e) {
                        /* ignore */
                    }
                }
            });

            $row.find("select.select-2").each(function () {
                var $el = $(this);
                if (isEmptyAjaxProductSelect($el)) {
                    return;
                }

                var optCount = $el.find("option").length;
                // Product lists must always show search (duplicate quotation / prefilled rows).
                // Infinity hides the search box when option count is small — bad UX for SKU/name lookup.
                var minimumResultsForSearch = minimumResultsForSearchFor($el, optCount);
                // "100%" on unit/discount/tax blows up Select2 inside narrow <td>s and overlaps next columns.
                // "resolve" uses the native <select> width (inline styles in line-items.blade.php).
                var select2Width = $el.hasClass("product-select") ? "100%" : "resolve";
                $el.select2({
                    width: select2Width,
                    dropdownParent: $parent,
                    minimumResultsForSearch: minimumResultsForSearch,
                });

                if ($el.hasClass("unit") && $el.val()) {
                    $el.trigger("change.select2");
                }
            });
        });
    };
})(window, window.jQuery);
