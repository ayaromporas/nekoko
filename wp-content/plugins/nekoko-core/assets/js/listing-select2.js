(function ($) {
    'use strict';
    $(function () {
        $('[data-nekoko-category-picker]').each(function () {
            var picker = $(this), form = picker.closest('form');
            var branch = form.find('[data-nekoko-listing-branch]');
            var categories = picker.find('[data-listing-categories]');
            var subcategories = picker.find('[data-listing-subcategories]');
            var error = picker.find('[data-category-picker-error]');
            function options(select, attribute) {
                return select.find('option').map(function () {
                    return {id: this.value, text: this.textContent, parent: this.getAttribute(attribute), selected: this.selected};
                }).get();
            }
            var categoryOptions = options(categories, 'data-branch');
            var subcategoryOptions = options(subcategories, 'data-category');
            function populate(select, available, selected, enabled) {
                select.empty();
                available.forEach(function (option) {
                    select.append(new Option(option.text, option.id, false, selected.indexOf(option.id) !== -1));
                });
                select.prop('disabled', !enabled).trigger('change.select2');
            }
            function refreshSubcategories(clear) {
                var parents = categories.val() || [];
                populate(subcategories, subcategoryOptions.filter(function (option) { return parents.indexOf(option.parent) !== -1; }), clear ? [] : (subcategories.val() || []), parents.length > 0);
            }
            function refreshCategories(clear) {
                populate(categories, categoryOptions.filter(function (option) { return option.parent === branch.val(); }), clear ? [] : (categories.val() || []), !!branch.val());
                refreshSubcategories(clear);
            }
            refreshCategories(false);
            if ($.fn.select2) {
                var settings = {
                    width: '100%', closeOnSelect: false, dropdownParent: picker,
                    language: {noResults: function () { return 'Nema rezultata.'; }, searching: function () { return 'Pretraga…'; }, removeItem: function () { return 'Uklonite izbor'; }}
                };
                categories.select2($.extend({}, settings, {placeholder: 'Pretražite i izaberite kategorije'}));
                subcategories.select2($.extend({}, settings, {placeholder: 'Pretražite i izaberite podkategorije'}));
                categories.next('.select2').find('.select2-search__field').attr('aria-label', 'Pretražite kategorije');
                subcategories.next('.select2').find('.select2-search__field').attr('aria-label', 'Pretražite podkategorije');
            }
            branch.on('change.nekokoCategories', function () { refreshCategories(true); error.prop('hidden', true); });
            categories.on('change.nekokoCategories', function () { refreshSubcategories(false); error.prop('hidden', true); });
            form.on('submit.nekokoCategories', function (event) {
                if (!(categories.val() || []).length) {
                    event.preventDefault(); error.prop('hidden', false);
                    if ($.fn.select2 && !categories.prop('disabled')) categories.select2('open');
                    error[0].scrollIntoView({block: 'center'});
                }
            });
        });
    });
})(jQuery);
