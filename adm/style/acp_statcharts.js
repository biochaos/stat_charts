/**
 * Stat Charts extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * The ACP settings page: the order-and-colours table, live colour checks, the
 * palette editors, and the "Build now" progress loop.
 *
 * Colour checks are computed, never eyeballed (SPEC section 7, Appendix A):
 * OKLab distances, protan/deutan simulated with Machado, Oliveira & Fernandes
 * (2009) at full severity, WCAG contrast. Thresholds are those the presets were
 * validated with. They warn; they never block saving.
 */
(function () {
	'use strict';

	const root = document.getElementById('statcharts-acp');
	const configEl = document.getElementById('statcharts-acp-config');
	if (!root || !configEl) {
		return;
	}
	const cfg = JSON.parse(configEl.textContent);
	const L = cfg.lang;
	const SLOTS = 8;

	function t(key) {
		let text = L[key] || key;
		const args = Array.prototype.slice.call(arguments, 1);
		let i = 0;
		text = text.replace(/%(\d)\$[sd]/g, (_, n) => String(args[n - 1]));
		return text.replace(/%[sd]/g, () => String(args[i++]));
	}

	function h(tag, props) {
		const el = document.createElement(tag);
		for (const [key, value] of Object.entries(props || {})) {
			if (value === null || value === undefined || value === false) {
				continue;
			}
			if (key === 'text') {
				el.textContent = value;
			} else if (key === 'class') {
				el.className = value;
			} else if (key.startsWith('on')) {
				el.addEventListener(key.slice(2), value);
			} else {
				el.setAttribute(key, value === true ? '' : value);
			}
		}
		for (const child of Array.prototype.slice.call(arguments, 2).flat(Infinity)) {
			if (child !== null && child !== undefined && child !== false) {
				el.append(child instanceof Node ? child : String(child));
			}
		}
		return el;
	}

	/* ------------------------------------------------------------------ */
	/* Colour maths                                                        */
	/* ------------------------------------------------------------------ */

	const BAND = {light: [0.43, 0.77], dark: [0.48, 0.67]};
	const CHROMA_FLOOR = 0.10;
	const CVD_TARGET = 8;
	const CVD_FLOOR = 6;
	const NORMAL_FLOOR = 15;
	const CONTRAST_MIN = 3;
	const MACHADO = {
		protan: [[0.152286, 1.052583, -0.204868], [0.114503, 0.786281, 0.099216], [-0.003882, -0.048116, 1.051998]],
		deutan: [[0.367322, 0.860646, -0.227968], [0.280085, 0.672501, 0.047413], [-0.011820, 0.042940, 0.968881]],
	};

	const channel = (hex, i) => parseInt(hex.slice(1 + 2 * i, 3 + 2 * i), 16) / 255;
	const toLinear = (c) => c <= 0.04045 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
	const linear = (hex) => [0, 1, 2].map((i) => toLinear(channel(hex, i)));

	function oklab(rgb) {
		const l = Math.cbrt(0.4122214708 * rgb[0] + 0.5363325363 * rgb[1] + 0.0514459929 * rgb[2]);
		const m = Math.cbrt(0.2119034982 * rgb[0] + 0.6806995451 * rgb[1] + 0.1073969566 * rgb[2]);
		const s = Math.cbrt(0.0883024619 * rgb[0] + 0.2817188376 * rgb[1] + 0.6299787005 * rgb[2]);
		return [
			0.2104542553 * l + 0.7936177850 * m - 0.0040720468 * s,
			1.9779984951 * l - 2.4285922050 * m + 0.4505937099 * s,
			0.0259040371 * l + 0.7827717662 * m - 0.8086757660 * s,
		];
	}

	function simulate(rgb, kind) {
		const M = MACHADO[kind];
		const clamp = (c) => Math.max(0, Math.min(1, c));
		return M.map((row) => clamp(row[0] * rgb[0] + row[1] * rgb[1] + row[2] * rgb[2]));
	}

	/** OKLab distance ×100; with a kind, under that simulated colour blindness. */
	function deltaE(a, b, kind) {
		const x = oklab(kind ? simulate(linear(a), kind) : linear(a));
		const y = oklab(kind ? simulate(linear(b), kind) : linear(b));
		return 100 * Math.hypot(x[0] - y[0], x[1] - y[1], x[2] - y[2]);
	}

	function luminance(hex) {
		const [r, g, b] = linear(hex);
		return 0.2126 * r + 0.7152 * g + 0.0722 * b;
	}

	function contrast(a, b) {
		const [hi, lo] = [luminance(a), luminance(b)].sort((x, y) => y - x);
		return (hi + 0.05) / (lo + 0.05);
	}

	const isDark = (palette) => oklab(linear(palette.surface))[0] < 0.6;

	/**
	 * Problems of a pair of neighbours, worst first: the normal-vision floor,
	 * then CVD separation (below 6 a problem, 6-8 a caution).
	 */
	function pairProblems(a, b, nameA, nameB) {
		const out = [];
		const normal = deltaE(a, b);
		if (normal < NORMAL_FLOOR) {
			out.push({level: 'bad', text: t('CHECK_NORMAL', nameA, nameB, normal.toFixed(1))});
		}
		const cvd = Math.min(deltaE(a, b, 'protan'), deltaE(a, b, 'deutan'));
		if (cvd < CVD_FLOOR) {
			out.push({level: 'bad', text: t('CHECK_CVD', nameA, nameB, cvd.toFixed(1))});
		} else if (cvd < CVD_TARGET) {
			out.push({level: 'warn', text: t('CHECK_CVD_FLOOR', nameA, nameB, cvd.toFixed(1))});
		}
		return out;
	}

	/** The six categorical checks of a palette's eight slots, in slot order. */
	function checkPalette(palette) {
		const band = BAND[isDark(palette) ? 'dark' : 'light'];
		const name = (i) => t('SLOT', i + 1);
		const out = [];
		palette.slots.forEach((hex, i) => {
			const lab = oklab(linear(hex));
			if (lab[0] < band[0] || lab[0] > band[1]) {
				out.push({level: 'bad', text: t('CHECK_BAND', name(i))});
			}
			if (Math.hypot(lab[1], lab[2]) < CHROMA_FLOOR) {
				out.push({level: 'bad', text: t('CHECK_CHROMA', name(i))});
			}
			const ratio = contrast(hex, palette.surface);
			if (ratio < CONTRAST_MIN) {
				out.push({level: 'warn', text: t('CHECK_CONTRAST', name(i), ratio.toFixed(2))});
			}
		});
		for (let i = 0; i + 1 < palette.slots.length; i++) {
			out.push(...pairProblems(palette.slots[i], palette.slots[i + 1], name(i), name(i + 1)));
		}
		return out;
	}

	function report(el, problems, okText) {
		el.replaceChildren(problems.length
			? h('ul', {class: 'sc-problems'}, problems.map((p) => h('li', {class: 'sc-' + p.level, text: p.text})))
			: h('p', {class: 'sc-ok', text: okText}));
	}

	/* ------------------------------------------------------------------ */
	/* Palettes in use                                                     */
	/* ------------------------------------------------------------------ */

	const palettes = cfg.palettes;
	const paletteName = (ref) => {
		const p = palettes[ref];
		if (!p) {
			return ref;
		}
		return p.custom ? p.name : (ref === 'preset:dark' ? L.PRESET_DARK || 'Dark' : L.PRESET_LIGHT || 'Light');
	};

	/**
	 * Palettes in use, from the assignment form as it stands (saved or not):
	 * [ref => names of the styles it paints, "default" first where it applies].
	 * A style left on "(default)" counts under the default palette.
	 */
	function palettesInUse() {
		const form = root.querySelector('.sc-palettes-form');
		const def = form.querySelector('select[name="palette_default"]');
		const defaultRef = def ? def.value : cfg.assignments['*'];
		const used = {[defaultRef]: [t('DEFAULT')]};
		for (const select of form.querySelectorAll('select[data-style]')) {
			const ref = select.value || defaultRef;
			(used[ref] = used[ref] || []).push(select.getAttribute('data-name'));
		}
		return {used: used, defaultRef: defaultRef};
	}

	/* ------------------------------------------------------------------ */
	/* Order and colours                                                   */
	/* ------------------------------------------------------------------ */

	const colourForm = root.querySelector('.sc-colours-form');
	const table = colourForm.querySelector('.sc-entities');
	let entities = cfg.entities.slice();
	const disabled = new Set(cfg.disabled);

	/**
	 * The columns are the eight colour positions and "Other". A forum is given
	 * a position, and each palette paints the positions its own way, so the
	 * head shows one row per palette in use; "Don't use" switches a whole
	 * column off, in every palette at once.
	 */
	function renderHead() {
		const {used} = palettesInUse();
		const numbers = [h('th', {colspan: 2})];
		for (let slot = 0; slot < SLOTS; slot++) {
			numbers.push(h('th', {class: 'sc-col' + (disabled.has(slot) ? ' sc-off' : ''), text: String(slot + 1)}));
		}
		numbers.push(h('th', {class: 'sc-col', text: t('OTHER')}));

		const rows = [h('tr', null, numbers)];
		for (const ref of Object.keys(used)) {
			const palette = palettes[ref];
			if (!palette) {
				continue;
			}
			const cells = [h('th', {colspan: 2, class: 'sc-palette-name'}, paletteName(ref),
				h('span', {class: 'sc-small', text: ' ' + used[ref].join(', ')}))];
			for (let slot = 0; slot < SLOTS; slot++) {
				cells.push(h('td', {class: 'sc-col' + (disabled.has(slot) ? ' sc-off' : ''), title: t('SLOT', slot + 1)},
					h('span', {class: 'sc-swatch', style: 'background:' + palette.slots[slot]})));
			}
			cells.push(h('td', {class: 'sc-col'}, h('span', {class: 'sc-swatch', style: 'background:' + palette.other})));
			rows.push(h('tr', {class: 'sc-palette-row'}, cells));
		}

		const toggles = [h('th', {colspan: 2, class: 'sc-palette-name'}, L.DISABLED_SLOTS,
			h('span', {class: 'sc-small', text: ' ' + L.DISABLED_SLOTS_EXPLAIN}))];
		for (let slot = 0; slot < SLOTS; slot++) {
			toggles.push(h('td', {class: 'sc-col' + (disabled.has(slot) ? ' sc-off' : '')},
				h('input', {type: 'checkbox', name: 'disabled[]', value: slot, checked: disabled.has(slot),
					title: L.DISABLED_SLOTS + ': ' + (slot + 1), 'aria-label': L.DISABLED_SLOTS + ': ' + (slot + 1),
					onchange: (e) => {
						if (e.target.checked) {
							disabled.add(slot);
						} else {
							disabled.delete(slot);
						}
						renderEntities();
					}})));
		}
		toggles.push(h('td'));
		rows.push(h('tr', {class: 'sc-toggle-row'}, toggles));
		table.querySelector('thead').replaceChildren(...rows);
	}

	function renderEntities() {
		renderHead();
		table.querySelector('tbody').replaceChildren(...entities.map((entity, index) => {
			const radio = (value, off) => h('td', {class: 'sc-col' + (off ? ' sc-off' : '')},
				h('input', {type: 'radio', name: 'slot[' + entity.id + ']', value: value,
					'aria-label': entity.name + ': ' + (value === 'other' ? t('OTHER') : t('SLOT', value + 1)),
					checked: value === 'other' ? (entity.slot === 'other' || entity.slot === null) : entity.slot === value,
					disabled: off, onchange: () => { entity.slot = value; checkNeighbours(); }}));
			const cells = [];
			for (let slot = 0; slot < SLOTS; slot++) {
				cells.push(radio(slot, disabled.has(slot)));
			}
			cells.push(radio('other', false));

			const move = (delta) => () => {
				const target = index + delta;
				if (target >= 0 && target < entities.length) {
					[entities[index], entities[target]] = [entities[target], entities[index]];
					renderEntities();
				}
			};
			return h('tr', null,
				h('td', {class: 'sc-move'},
					h('button', {type: 'button', class: 'button2', title: t('MOVE_UP'), 'aria-label': t('MOVE_UP'), disabled: index === 0, onclick: move(-1)}, '▲'),
					h('button', {type: 'button', class: 'button2', title: t('MOVE_DOWN'), 'aria-label': t('MOVE_DOWN'), disabled: index === entities.length - 1, onclick: move(1)}, '▼')),
				h('td', {class: 'sc-entity'}, h('strong', {text: entity.name, dir: 'auto'}),
					entity.new ? h('span', {class: 'sc-badge', text: t('NEW_FORUM')}) : null,
					entity.unordered ? h('span', {class: 'sc-badge', text: t('NEW_IN_ORDER')}) : null),
				cells);
		}));
		colourForm.querySelector('input[name="order"]').value = entities.map((e) => e.id).join(',');
		checkNeighbours();
	}

	/**
	 * Neighbours as they will actually be drawn - order decides which colours
	 * touch (SPEC section 7) - for every palette in use: forums with colours in
	 * display order, then the grey "Other" if any, and the wrap-around pair.
	 */
	function checkNeighbours() {
		const box = colourForm.querySelector('.sc-neighbours');
		const {used} = palettesInUse();
		const sequence = entities.filter((e) => Number.isInteger(e.slot) && !disabled.has(e.slot));
		const others = entities.length - sequence.length;
		box.replaceChildren(...Object.keys(used).map((ref) => {
			const palette = palettes[ref];
			if (!palette) {
				return null;
			}
			const drawn = sequence.map((e) => ({name: e.name, hex: palette.slots[e.slot]}));
			if (others) {
				drawn.push({name: t('OTHER'), hex: palette.other});
			}
			const problems = [];
			for (let i = 0; i + 1 < drawn.length; i++) {
				problems.push(...pairProblems(drawn[i].hex, drawn[i + 1].hex, drawn[i].name, drawn[i + 1].name));
			}
			if (drawn.length > 2) {
				const last = drawn[drawn.length - 1];
				pairProblems(last.hex, drawn[0].hex, last.name, drawn[0].name)
					.forEach((p) => problems.push({level: p.level, text: p.text + ' ' + t('WRAP')}));
			}
			const el = h('div', {class: 'sc-check'});
			report(el, problems, t('NEIGHBOURS_OK', paletteName(ref)));
			if (problems.length) {
				el.prepend(h('p', {text: t('NEIGHBOURS', paletteName(ref))}));
			}
			return el;
		}));
	}

	/* ------------------------------------------------------------------ */
	/* Grouping: how many groups each mode makes                           */
	/* ------------------------------------------------------------------ */

	function checkGrouping() {
		for (const el of root.querySelectorAll('.sc-count')) {
			el.textContent = '(' + cfg.counts[el.getAttribute('data-mode')] + ')';
		}
		const chosen = root.querySelector('input[name="grouping"]:checked');
		const warning = root.querySelector('.sc-too-many');
		const count = chosen ? cfg.counts[chosen.value] : 0;
		// "More entities than colours": warn at the point of decision, with the
		// alternative spelled out (SPEC section 7).
		if (count > SLOTS) {
			const alt = Object.keys(cfg.counts).filter((mode) => cfg.counts[mode] < count)
				.sort((a, b) => cfg.counts[a] - cfg.counts[b])[0];
			warning.querySelector('p').textContent = t('TOO_MANY', count)
				+ (alt ? ' ' + t('TOO_MANY_ALT', t('MODE_' + alt.toUpperCase()), cfg.counts[alt]) : '');
			warning.hidden = false;
		} else {
			warning.hidden = true;
		}
	}
	for (const radio of root.querySelectorAll('input[name="grouping"]')) {
		radio.addEventListener('change', checkGrouping);
	}

	/* ------------------------------------------------------------------ */
	/* Palettes: assignment and custom palettes                            */
	/* ------------------------------------------------------------------ */

	function paletteOptions(selected, withDefault) {
		const options = withDefault ? [h('option', {value: '', text: '(' + t('DEFAULT') + ')', selected: !selected})] : [];
		for (const ref of Object.keys(palettes)) {
			options.push(h('option', {value: ref, text: paletteName(ref), selected: ref === selected}));
		}
		return options;
	}

	function renderAssignments() {
		const box = root.querySelector('.sc-assignments');
		const rows = [h('dl', null,
			h('dt', null, h('label', {text: L.PALETTE_DEFAULT || t('DEFAULT')})),
			h('dd', null, h('select', {name: 'palette_default', onchange: afterPaletteChange}, paletteOptions(cfg.assignments['*'], false))))];
		for (const style of cfg.styles) {
			rows.push(h('dl', null,
				h('dt', null, h('label', {text: style.name + (style.active ? '' : ' ' + (L.PALETTE_INACTIVE || ''))})),
				h('dd', null, h('select', {name: 'palette_style[' + style.path + ']', 'data-style': style.path, 'data-name': style.name,
					onchange: afterPaletteChange}, paletteOptions(cfg.assignments[style.path] || '', true)))));
		}
		box.replaceChildren(...rows);
		for (const select of root.querySelectorAll('.sc-palette-select')) {
			select.replaceChildren(...paletteOptions('preset:light', false));
		}
	}

	function afterPaletteChange() {
		renderEntities();
		const {used} = palettesInUse();
		for (const el of root.querySelectorAll('.sc-palette-users')) {
			const users = used[el.getAttribute('data-ref')];
			el.textContent = users ? ' ' + t('PALETTE_FOR', users.join(', ')) : '';
		}
	}

	/** The page's form token, copied into forms this script builds. */
	function tokenFields() {
		return Array.from(colourForm.querySelectorAll('input[name="creation_time"], input[name="form_token"]'))
			.map((input) => input.cloneNode());
	}

	const TOKENS = ['other', 'surface', 'ink', 'ink2', 'axis', 'grid'];

	function renderCustomPalettes() {
		const list = root.querySelector('.sc-palette-list');
		const customs = Object.keys(palettes).filter((ref) => palettes[ref].custom);
		list.replaceChildren(...customs.map((ref) => {
			const palette = JSON.parse(JSON.stringify(palettes[ref]));
			const id = ref.slice('custom:'.length);
			const checks = h('div', {class: 'sc-check'});
			const preview = h('div', {class: 'sc-preview'});

			function refresh() {
				preview.style.background = palette.surface;
				preview.style.borderColor = palette.axis;
				preview.replaceChildren(...palette.slots.map((hex) => h('span', {class: 'sc-swatch', style: 'background:' + hex})),
					h('span', {class: 'sc-swatch sc-swatch-other', style: 'background:' + palette.other}),
					h('span', {style: 'color:' + palette.ink, text: ' Aa'}), h('span', {style: 'color:' + palette.ink2, text: ' Aa'}));
				report(checks, checkPalette(palette), t('PALETTE_OK'));
				// The palette object feeds the neighbour checks too, live.
				palettes[ref] = Object.assign({}, palettes[ref], palette);
				if (table.querySelector('thead').childElementCount) {
					renderHead();
					checkNeighbours();
				}
			}

			const colourInput = (name, value, onInput) => h('input', {type: 'color', name: name, value: value, oninput: (e) => { onInput(e.target.value); refresh(); }});
			const slots = palette.slots.map((hex, i) => h('label', {class: 'sc-colour', title: t('SLOT', i + 1)},
				String(i + 1), ' ', colourInput('slots[' + i + ']', hex, (v) => { palette.slots[i] = v; })));
			const tokens = TOKENS.map((token) => h('label', {class: 'sc-colour'},
				(L['TOKEN_' + token.toUpperCase()] || token), ' ', colourInput(token, palette[token], (v) => { palette[token] = v; })));

			const users = (palettesInUse().used[ref] || []).join(', ');
			const form = h('form', {method: 'post', action: colourForm.getAttribute('action'), class: 'sc-palette-editor'},
				h('p', null, h('input', {type: 'text', name: 'name', value: palette.name, maxlength: 60, class: 'inputbox autowidth'}),
					h('span', {class: 'sc-small sc-palette-users', 'data-ref': ref, text: users ? ' ' + t('PALETTE_FOR', users) : ''})),
				h('div', {class: 'sc-colours'}, slots), h('div', {class: 'sc-colours'}, tokens),
				preview, checks,
				h('input', {type: 'hidden', name: 'action', value: 'custom_save'}),
				h('input', {type: 'hidden', name: 'palette_id', value: id}),
				h('p', {class: 'submit-buttons'}, h('input', {type: 'submit', class: 'button1', value: L.SUBMIT || 'Submit'})),
				tokenFields());
			const del = h('form', {method: 'post', action: colourForm.getAttribute('action'), class: 'sc-inline-form'},
				h('input', {type: 'hidden', name: 'action', value: 'custom_delete'}),
				h('input', {type: 'hidden', name: 'palette_id', value: id}),
				h('input', {type: 'submit', class: 'button2', value: L.DELETE_PALETTE || 'Delete'}),
				tokenFields());
			refresh();
			return h('div', {class: 'sc-palette'}, form, del);
		}));
	}

	/* ------------------------------------------------------------------ */
	/* Build now                                                           */
	/* ------------------------------------------------------------------ */

	const build = root.querySelector('.sc-build');
	const buildButton = build.querySelector('.sc-build-button');
	const progress = build.querySelector('.sc-progress');
	const buildStatus = build.querySelector('.sc-build-status');
	const sleep = (ms) => new Promise((resolve) => setTimeout(resolve, ms));

	buildButton.addEventListener('click', async () => {
		buildButton.disabled = true;
		progress.hidden = false;
		buildStatus.textContent = t('BUILDING');
		const url = root.getAttribute('data-rebuild-url').replace(/&amp;/g, '&');
		try {
			for (;;) {
				const response = await fetch(url, {credentials: 'same-origin', headers: {'X-Requested-With': 'XMLHttpRequest'}});
				const step = await response.json();
				if (step.error) {
					throw new Error(step.error);
				}
				progress.value = step.progress;
				if (step.done) {
					buildStatus.textContent = t('BUILT');
					location.reload();
					return;
				}
				// Another run (the scheduled task) holds the lock: wait for it.
				buildStatus.textContent = step.result === 'locked' ? t('LOCKED') : step.label;
				if (step.result === 'locked') {
					await sleep(3000);
				}
			}
		} catch (e) {
			buildStatus.textContent = t('BUILD_FAILED');
			buildButton.disabled = false;
		}
	});

	/* ------------------------------------------------------------------ */

	renderAssignments();
	renderCustomPalettes();
	renderEntities();
	checkGrouping();
}());
