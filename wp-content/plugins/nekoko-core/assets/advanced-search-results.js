document.addEventListener('DOMContentLoaded', function () {
	document.querySelectorAll('[data-nekoko-advanced-search]').forEach(function (root) {
		var form = root.querySelector('[data-nekoko-advanced-form]');
		var target = root.querySelector('[data-nekoko-advanced-target]');
		var count = root.querySelector('[data-nekoko-advanced-count]');
		var category = form.querySelector('[data-nekoko-advanced-category]');
		var price = form.querySelector('[data-nekoko-advanced-price]');
		var duration = form.querySelector('[data-nekoko-advanced-duration]');
		var timer;
		var options = Array.prototype.slice.call(category.options).slice(1).map(function (option) {
			return { value: option.value, text: option.text, tip: option.dataset.tip };
		});

		function selectedType() {
			var checked = form.querySelector('[data-nekoko-advanced-type]:checked');
			return checked ? checked.value : 'ponuda';
		}

		function populateCategory(keep) {
			var previous = keep ? category.value : '';
			var type = selectedType();
			category.innerHTML = '';
			category.add(new Option('Sve kategorije', ''));
			options.forEach(function (option) {
				if (type && option.tip !== type) {
					return;
				}
				category.add(new Option(option.text, option.value));
			});
			category.value = previous;
			if (category.value !== previous) {
				category.value = '';
			}
		}

		function toggleTypeFields() {
			var demand = selectedType() === 'potraznja';
			price.hidden = demand;
			duration.hidden = !demand;
			if (demand) {
				Array.prototype.forEach.call(price.querySelectorAll('input'), function (input) {
					input.value = '';
				});
			}
		}

		function data() {
			var params = new URLSearchParams(new FormData(form));
			params.forEach(function (value, key) {
				if (!value) {
					params.delete(key);
				}
			});
			return params;
		}

		function update(push) {
			var params = data();
			if (push) {
				history.pushState({}, '', location.pathname + (params.toString() ? '?' + params.toString() : ''));
			}

			var request = new URLSearchParams(params);
			request.set('action', 'nekoko_advanced_search');
			request.set('empty_message', root.dataset.emptyMessage);
			root.classList.add('is-loading');
			fetch(root.dataset.ajaxUrl + '?' + request.toString(), { credentials: 'same-origin' })
				.then(function (response) { return response.json(); })
				.then(function (response) {
					if (!response.success) {
						throw new Error();
					}
					target.innerHTML = response.data.html;
					count.textContent = response.data.count + ' oglasa';
				})
				.catch(function () {
					target.innerHTML = '<p class="nekoko-advanced-results__empty">Rezultati trenutno nisu dostupni.</p>';
				})
				.finally(function () {
					root.classList.remove('is-loading');
				});
		}

		form.addEventListener('input', function (event) {
			if (event.target.matches('[data-nekoko-advanced-type]')) {
				return;
			}
			clearTimeout(timer);
			timer = setTimeout(function () {
				update(true);
			}, 300);
		});

		form.addEventListener('change', function (event) {
			clearTimeout(timer);
			if (event.target.matches('[data-nekoko-advanced-type]')) {
				populateCategory(false);
				toggleTypeFields();
			}
			update(true);
		});

		form.addEventListener('reset', function (event) {
			event.preventDefault();
			clearTimeout(timer);
			Array.prototype.forEach.call(form.elements, function (element) {
				if (!element.name) {
					return;
				}
				if ('radio' === element.type) {
					element.checked = 'ponuda' === element.value;
				} else if ('sort' === element.name) {
					element.value = 'novo';
				} else {
					element.value = '';
				}
			});
			populateCategory(false);
			toggleTypeFields();
			history.pushState({}, '', location.pathname);
			update(false);
		});

		window.addEventListener('popstate', function () {
			var params = new URLSearchParams(location.search);
			Array.prototype.forEach.call(form.elements, function (element) {
				if (!element.name) {
					return;
				}
				if ('radio' === element.type) {
					element.checked = (params.get(element.name) || 'ponuda') === element.value;
				} else {
					element.value = params.get(element.name) || '';
				}
			});
			populateCategory(true);
			toggleTypeFields();
			update(false);
		});

		populateCategory(true);
		toggleTypeFields();
	});
});
