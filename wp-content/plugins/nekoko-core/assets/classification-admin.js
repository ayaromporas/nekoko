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
		var checkbox = event.target.closest('[data-nekoko-listing-approval]');
		if (!checkbox || !window.nekokoListingApproval) return;
		var previous = !checkbox.checked;
		var status = checkbox.parentNode.querySelector('[data-nekoko-listing-approval-status]');
		checkbox.disabled = true;
		var data = new URLSearchParams();
		data.set('action', 'nekoko_toggle_listing_approval');
		data.set('nonce', window.nekokoListingApproval.nonce);
		data.set('post_id', checkbox.dataset.postId);
		data.set('approved', checkbox.checked ? '1' : '0');
		fetch(window.nekokoListingApproval.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: data.toString()
		}).then(function (response) {
			return response.json();
		}).then(function (response) {
			if (!response.success) throw new Error();
			checkbox.checked = response.data.approved;
			checkbox.setAttribute('aria-label', response.data.approved ? 'Oglas je odobren. Odznačite za vraćanje na čekanje.' : 'Oglas je na čekanju. Označite za odobravanje.');
			if (status) status.textContent = response.data.label;
		}).catch(function () {
			checkbox.checked = previous;
			window.alert(window.nekokoListingApproval.error);
		}).finally(function () {
			checkbox.disabled = false;
		});
	});
})();
