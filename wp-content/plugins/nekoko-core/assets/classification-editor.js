(function (wp) {
	'use strict';
	var el = wp.element.createElement, useState = wp.element.useState, useEffect = wp.element.useEffect;
	var decode = wp.htmlEntities.decodeEntities;
	function Classification() {
		var termsState = useState(null), terms = termsState[0], setTerms = termsState[1];
		var errorState = useState(''), error = errorState[0], setError = errorState[1];
		var selected = wp.data.useSelect(function (select) { return select('core/editor').getEditedPostAttribute('kategorija_oglasa') || []; }, []);
		var actions = wp.data.useDispatch('core/editor');
		useEffect(function () {
			var active = true;
			async function load() {
				var all = [], page = 1, total = 1;
				do {
					var response = await wp.apiFetch({path: '/wp/v2/kategorija_oglasa?hide_empty=false&per_page=100&page=' + page, parse: false});
					total = Number(response.headers.get('X-WP-TotalPages') || 1);
					all = all.concat(await response.json()); page++;
				} while (page <= total);
				if (active) setTerms(all);
			}
			load().catch(function () { if (active) setError('Kategorije nisu učitane. Osvežite editor i pokušajte ponovo.'); });
			return function () { active = false; };
		}, []);
		var roots = terms ? terms.filter(function (t) { return !t.parent && ['ponuda', 'potraznja'].includes(t.slug); }) : [];
		function rootOf(id) {
			var term = terms && terms.find(function (t) { return t.id === id; }), visited = [];
			while (term && term.parent) {
				if (visited.includes(term.id)) return null;
				visited.push(term.id); term = terms.find(function (t) { return t.id === term.parent; });
			}
			return term && roots.some(function (r) { return r.id === term.id; }) ? term.id : null;
		}
		var assignedRoots = Array.from(new Set(selected.map(rootOf).filter(Boolean)));
		var rootId = assignedRoots.length === 1 ? assignedRoots[0] : null;
		var valid = !!rootId && selected.includes(rootId) && selected.every(function (id) { return rootOf(id) === rootId; });
		useEffect(function () {
			if (valid) actions.unlockPostSaving('nekoko-classification');
			else actions.lockPostSaving('nekoko-classification');
			return function () { actions.unlockPostSaving('nekoko-classification'); };
		}, [valid]);
		function tree(parent) {
			var children = terms.filter(function (t) { return t.parent === parent; });
			if (!children.length) return null;
			return el('ul', {className: 'nekoko-classification-tree'}, children.map(function (term) {
				return el('li', {key: term.id},
					el(wp.components.CheckboxControl, {label: decode(term.name), checked: selected.includes(term.id), onChange: function (checked) {
						var next = selected.filter(function (id) { return id !== term.id && rootOf(id) === rootId; });
						if (checked) next.push(term.id);
						actions.editPost({kategorija_oglasa: Array.from(new Set([rootId].concat(next)))});
					}}), tree(term.id));
			}));
		}
		return el(wp.editor.PluginDocumentSettingPanel, {name: 'nekoko-classification', title: 'Klasifikacija oglasa', className: 'nekoko-classification'},
			error ? el(wp.components.Notice, {status: 'error', isDismissible: false}, error) : !terms ? el(wp.components.Spinner) : el(wp.element.Fragment, null,
				el(wp.components.RadioControl, {label: 'Tip oglasa (obavezno)', selected: rootId ? String(rootId) : '', options: roots.map(function (t) { return {label: decode(t.name), value: String(t.id)}; }), onChange: function (value) { actions.editPost({kategorija_oglasa: [Number(value)]}); }}),
				!valid && el(wp.components.Notice, {status: 'warning', isDismissible: false}, 'Pre čuvanja izaberite jednu granu: Ponuda ili Potražnja.'),
				rootId && el('div', null, el('p', null, 'Kategorije iz izabrane grane:'), tree(rootId))
			));
	}
	wp.hooks.addFilter('editor.PostTaxonomyType', 'nekoko-core/classification', function (Original) {
		return function (props) { return props.slug === 'kategorija_oglasa' ? null : el(Original, props); };
	});
	wp.plugins.registerPlugin('nekoko-classification', {render: Classification});
})(window.wp);
