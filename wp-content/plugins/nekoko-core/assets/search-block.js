(function () {
	'use strict';

	function setCategoryPlaceholder(select, label, disabled) {
		select.replaceChildren();
		var option = document.createElement('option');
		option.value = '';
		option.textContent = label;
		select.appendChild(option);
		select.disabled = disabled;
	}

	function initialise(form) {
		var type = form.querySelector('[data-nekoko-search-type]');
		var category = form.querySelector('[data-nekoko-search-category]');
		var status = form.querySelector('[data-nekoko-search-status]');
		var request;

		type.addEventListener('change', function () {
			if (request) { request.abort(); }
			status.textContent = '';
			if (!type.value) {
				setCategoryPlaceholder(category, 'Prvo izaberite tip oglasa', true);
				return;
			}

			setCategoryPlaceholder(category, 'Učitavanje kategorija…', true);
			request = new AbortController();
			var url = new URL(form.dataset.ajaxUrl, window.location.origin);
			url.search = new URLSearchParams({ action: 'nekoko_search_categories', branch: type.value }).toString();

			fetch(url, { credentials: 'same-origin', signal: request.signal })
				.then(function (response) {
					if (!response.ok) { throw new Error('Request failed'); }
					return response.json();
				})
				.then(function (response) {
					if (!response.success || !response.data || !Array.isArray(response.data.categories)) { throw new Error('Invalid response'); }
					setCategoryPlaceholder(category, 'Izaberite kategoriju', false);
					response.data.categories.forEach(function (item) {
						var option = document.createElement('option');
						option.value = item.id;
						option.textContent = item.label;
						category.appendChild(option);
					});
				})
				.catch(function (error) {
					if (error.name === 'AbortError') { return; }
					setCategoryPlaceholder(category, 'Kategorije nisu dostupne', true);
					status.textContent = 'Kategorije trenutno nije moguće učitati. Pokušajte ponovo.';
				});
		});
	}

	document.querySelectorAll('[data-nekoko-search-form]').forEach(initialise);
}());
