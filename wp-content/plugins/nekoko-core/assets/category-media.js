(function ($) {
	'use strict';
	$(document).on('click', '.nekoko-media-select', function () {
		var field = $(this).closest('.nekoko-media-field');
		var picker = wp.media({ title: 'Izaberi sliku kategorije', button: { text: 'Koristi ovu sliku' }, library: { type: 'image' }, multiple: false });
		picker.on('select', function () {
			var image = picker.state().get('selection').first().toJSON();
			field.find('input[type=hidden]').val(image.id);
			var src = image.sizes && image.sizes.thumbnail ? image.sizes.thumbnail.url : image.url;
			field.find('.nekoko-media-preview').empty().append($('<img>', { src: src, alt: image.alt || '', width: 150 }));
		});
		picker.open();
	});
	$(document).on('click', '.nekoko-media-remove', function () {
		var field = $(this).closest('.nekoko-media-field');
		field.find('input[type=hidden]').val(0);
		field.find('.nekoko-media-preview').empty();
	});
	// Clear add-form media only after WordPress confirms a successful term creation.
	$(document).ajaxSuccess(function (event, xhr, settings) {
		if (typeof settings.data !== 'string' || !/(^|&)action=add-tag(&|$)/.test(settings.data) || !/(^|&)taxonomy=kategorija_oglasa(&|$)/.test(settings.data)) { return; }
		var xml = xhr.responseXML;
		if (xml && !xml.getElementsByTagName('wp_error').length && xml.getElementsByTagName('taxonomy').length) {
			$('#addtag .nekoko-media-field input[type=hidden]').val(0);
			$('#addtag .nekoko-media-preview').empty();
		}
	});
})(jQuery);
