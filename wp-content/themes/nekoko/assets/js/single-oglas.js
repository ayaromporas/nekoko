document.addEventListener('DOMContentLoaded', function () {
	var listing = document.querySelector('.nekoko-listing-single');
	if (!listing || !window.nekokoListing) return;
	var config = window.nekokoListing;
	function request(action, data) {
		var body = new URLSearchParams(data || {});
		body.set('action', action);
		body.set('nonce', config.nonce);
		body.set('listing_id', config.listingId);
		return fetch(config.ajaxUrl, { method: 'POST', credentials: 'same-origin', headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' }, body: body.toString() }).then(function (response) { return response.json(); });
	}
	function openDialog(name) {
		var dialog = document.querySelector('[data-listing-dialog="' + name + '"]');
		if (!dialog) return;
		if (typeof dialog.showModal === 'function') dialog.showModal(); else dialog.setAttribute('open', 'open');
	}
	document.querySelectorAll('[data-listing-open-dialog]').forEach(function (button) { button.addEventListener('click', function () { openDialog(button.dataset.listingOpenDialog); }); });
	document.querySelectorAll('[data-listing-close-dialog]').forEach(function (button) { button.addEventListener('click', function () { var dialog = button.closest('dialog'); if (dialog && typeof dialog.close === 'function') dialog.close(); else if (dialog) dialog.removeAttribute('open'); }); });
	document.querySelectorAll('.nekoko-listing-dialog').forEach(function (dialog) { dialog.addEventListener('click', function (event) { if (event.target === dialog) { if (typeof dialog.close === 'function') dialog.close(); else dialog.removeAttribute('open'); } }); });
	document.querySelectorAll('[data-listing-save]').forEach(function (button) { button.addEventListener('click', function () {
		request('nekoko_toggle_saved_listing').then(function (result) {
			if (!result.success && result.data && result.data.login_url) { window.location.href = result.data.login_url; return; }
			if (!result.success) return;
			document.querySelectorAll('[data-listing-save]').forEach(function (control) { control.setAttribute('aria-pressed', result.data.saved ? 'true' : 'false'); });
			document.querySelectorAll('[data-listing-save-label]').forEach(function (label) { label.textContent = result.data.saved ? (label.textContent.indexOf('oglas') > -1 ? 'Sačuvano' : 'Sačuvano') : (label.textContent.indexOf('oglas') > -1 ? 'Sačuvaj oglas' : 'Sačuvaj'); });
		});
	}); });
	var form = document.querySelector('[data-listing-message-form]');
	if (form) form.addEventListener('submit', function (event) { event.preventDefault(); var status = form.querySelector('[data-listing-message-status]'); status.textContent = 'Slanje…'; request('nekoko_send_listing_message', { message: form.elements.message.value }).then(function (result) { if (!result.success && result.data && result.data.login_url) { window.location.href = result.data.login_url; return; } status.textContent = result.data && result.data.message ? result.data.message : 'Slanje nije uspelo.'; if (result.success) { form.reset(); setTimeout(function () { form.closest('dialog').close(); }, 1000); } }); });
	document.querySelectorAll('[data-listing-thumb]').forEach(function (thumb) { thumb.addEventListener('click', function () { var image = document.querySelector('[data-listing-main-image]'); if (!image) return; image.src = thumb.dataset.imageSrc; image.alt = thumb.dataset.imageAlt || ''; document.querySelectorAll('[data-listing-thumb]').forEach(function (item) { item.classList.toggle('is-active', item === thumb); }); var count = document.querySelector('[data-listing-image-count]'); if (count) count.textContent = thumb.dataset.imagePosition + ' / ' + document.querySelectorAll('[data-listing-thumb]').length; }); });
	var share = document.querySelector('[data-listing-share]');
	if (share) share.addEventListener('click', function () { var shareData = { title: document.title, url: window.location.href }; if (navigator.share) { navigator.share(shareData); } else if (navigator.clipboard) { navigator.clipboard.writeText(window.location.href); share.querySelector('span').textContent = 'Link kopiran'; } });
});
