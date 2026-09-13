(function () {
	'use strict';
	document.querySelectorAll('.nekoko-classification').forEach(function (box) {
		function update() {
			var root = box.querySelector('[data-nekoko-root]:checked');
			box.querySelectorAll('[data-nekoko-branch]').forEach(function (group) {
				var active = root && group.dataset.nekokoBranch === root.value;
				group.hidden = !active;
				group.querySelectorAll('input[type="checkbox"]').forEach(function (input) {
					input.disabled = !active;
					if (!active) input.checked = false;
				});
			});
		}
		box.addEventListener('change', update);
		update();
	});
	var branch = document.getElementById('nekoko-branch-filter');
	var category = document.getElementById('nekoko-category-filter');
	if (branch && category) {
		function filter() {
			category.disabled = !branch.value;
			Array.from(category.options).forEach(function (option) {
				if (!option.value) return;
				option.hidden = option.disabled = option.dataset.branch !== branch.value;
				if (option.disabled && option.selected) category.value = '';
			});
		}
		branch.addEventListener('change', filter);
		filter();
	}
})();
