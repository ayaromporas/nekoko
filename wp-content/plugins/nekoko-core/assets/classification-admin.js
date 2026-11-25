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
	document.addEventListener('change', function (event) {
		var radio = event.target.closest('[data-nekoko-listing-status]');
		if (!radio || !window.nekokoListingApproval) return;
		var group = radio.closest('.nekoko-listing-status');
		var previous = group.dataset.currentStatus;
		var inputs = group.querySelectorAll('input[type="radio"]');
		var feedback = group.querySelector('[role="status"]');
		function restore() { inputs.forEach(function (input) { input.checked = input.value === previous; }); }
		if (radio.value === 'reject') {
			restore();
			window.location.assign(group.dataset.rejectUrl);
			return;
		}
		inputs.forEach(function (input) { input.disabled = true; });
		group.setAttribute('aria-busy', 'true');
		feedback.textContent = 'Čuvanje…';
		var data = new URLSearchParams({action: 'nekoko_toggle_listing_approval', nonce: window.nekokoListingApproval.nonce, post_id: group.dataset.postId, status: radio.value});
		fetch(window.nekokoListingApproval.ajaxUrl, {method: 'POST', credentials: 'same-origin', headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'}, body: data.toString()})
		.then(function (response) { return response.json(); })
		.then(function (response) {
			if (!response.success) throw new Error(response.data && response.data.message || window.nekokoListingApproval.error);
			group.dataset.currentStatus = response.data.status;
			feedback.textContent = 'Sačuvano.';
			// Refresh status filters, row ordering and the title's post-state label together.
			window.location.reload();
		}).catch(function (error) {
			restore();
			feedback.textContent = error.message || window.nekokoListingApproval.error;
			inputs.forEach(function (input) { input.disabled = false; });
			group.removeAttribute('aria-busy');
		});
	});
})();