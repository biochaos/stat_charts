/**
 * Stat Charts extension for phpBB.
 *
 * @copyright (c) 2026, biochaos
 * @license GNU General Public License, version 2 (GPL-2.0)
 *
 * The stats page: four tabs (general, forum, topic, member) drawn from the
 * JSON endpoints with Chart.js. Everything that can be computed from the
 * overview payload - pies over a range, the 7-day average / per day /
 * cumulative toggle - is computed here, with no round-trip (SPEC section 8).
 *
 * Days are integers (days since 1970-01-01 in the board's timezone), plotted
 * as plain numbers and only formatted as dates in UTC, so no browser timezone
 * can shift a day. All text goes in through textContent or canvas text.
 */
(function () {
	'use strict';

	const root = document.getElementById('statcharts');
	const configEl = document.getElementById('statcharts-config');
	if (!root || !configEl || !window.Chart) {
		return;
	}

	const cfg = JSON.parse(configEl.textContent);
	const P = cfg.palette;
	const RTL = cfg.dir === 'rtl';
	const DAY = 86400000;
	const TABS = ['general', 'forum', 'topic', 'user'].filter((tab) => tab !== 'user' || cfg.can_profiles);

	const nf = new Intl.NumberFormat(cfg.locale);
	const nf1 = new Intl.NumberFormat(cfg.locale, {maximumFractionDigits: 1});
	const pf = new Intl.NumberFormat(cfg.locale, {style: 'percent', maximumFractionDigits: 1});
	const dateFmt = new Intl.DateTimeFormat(cfg.locale, {timeZone: 'UTC', day: 'numeric', month: 'numeric', year: 'numeric'});
	const monthFmt = new Intl.DateTimeFormat(cfg.locale, {timeZone: 'UTC', month: 'short', year: 'numeric'});

	for (const token of ['surface', 'ink', 'ink2', 'axis', 'grid']) {
		root.style.setProperty('--sc-' + token, P[token]);
	}
	const fontFamily = getComputedStyle(root).fontFamily;

	/* ------------------------------------------------------------------ */
	/* Small helpers                                                       */
	/* ------------------------------------------------------------------ */

	function t(key, vars) {
		let text = cfg.lang[key] || key;
		for (const name in vars || {}) {
			text = text.split('{' + name + '}').join(vars[name]);
		}
		return text;
	}

	/** t() with some placeholders filled by DOM nodes (links) rather than text. */
	function tNodes(key, vars) {
		const out = [];
		let rest = cfg.lang[key] || key;
		const pattern = /\{(\w+)\}/;
		let match;
		while ((match = pattern.exec(rest))) {
			out.push(rest.slice(0, match.index));
			const value = vars[match[1]];
			out.push(value instanceof Node ? value : String(value));
			rest = rest.slice(match.index + match[0].length);
		}
		out.push(rest);
		return out;
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

	const fmtDay = (day) => dateFmt.format(new Date(day * DAY));
	const isoDay = (day) => new Date(day * DAY).toISOString().slice(0, 10);
	const dayOfIso = (iso) => Math.floor(Date.parse(iso + 'T00:00:00Z') / DAY);

	function message(text) {
		return h('p', {class: 'sc-message', text: text});
	}

	/**
	 * What a failed request shows. A 401 means the phpBB session ended while the
	 * page stayed open (overnight, say); reloading goes through the login and
	 * back to this page.
	 */
	function failure(error) {
		if (error && error.status === 401) {
			return h('p', {class: 'sc-message'}, t('SESSION_EXPIRED') + ' ', h('a', {href: location.href, text: t('RELOAD')}));
		}
		return message(error && error.status === 403 ? t('NO_PROFILES') : t('ERROR'));
	}

	/* ------------------------------------------------------------------ */
	/* Data                                                                */
	/* ------------------------------------------------------------------ */

	const cache = new Map();

	function api(what, params) {
		const url = new URL(cfg.urls[what], location.href);
		for (const [key, value] of Object.entries(params || {})) {
			if (value !== null && value !== undefined) {
				url.searchParams.set(key, value);
			}
		}
		if (!cache.has(url.href)) {
			const request = fetch(url.href, {credentials: 'same-origin', headers: {Accept: 'application/json'}})
				.then((response) => response.json().then((body) => {
					if (!response.ok) {
						const error = new Error(body.error || String(response.status));
						error.status = response.status;
						throw error;
					}
					return body;
				}));
			request.catch(() => cache.delete(url.href));
			cache.set(url.href, request);
		}
		return cache.get(url.href);
	}

	/**
	 * Colour is pinned per entity, never per rank (SPEC section 7): a pinned
	 * slot from the ACP if it has one, otherwise the first free slot in display
	 * order. Past eight, entities share the grey residual bucket; the hidden
	 * entity (forums the viewer cannot see) is a paler grey, never named.
	 */
	function displayGroups(overview) {
		const disabled = new Set(cfg.disabled || []);
		const used = new Set();
		const slotOf = {};
		const visible = overview.entities.filter((entity) => entity.id !== 0);

		// The ACP may pin a forum to "Other" (no colour of its own) - SPEC section 7.
		const toOther = new Set();
		for (const entity of visible) {
			const slot = cfg.slots ? cfg.slots[entity.id] : undefined;
			if (slot === 'other') {
				toOther.add(entity.id);
			} else if (slot !== undefined && !disabled.has(slot) && !used.has(slot) && slot < P.slots.length) {
				slotOf[entity.id] = slot;
				used.add(slot);
			}
		}
		for (const entity of visible) {
			if (!(entity.id in slotOf) && !toOther.has(entity.id)) {
				const free = P.slots.findIndex((_, slot) => !used.has(slot) && !disabled.has(slot));
				if (free !== -1) {
					slotOf[entity.id] = free;
					used.add(free);
				}
			}
		}

		const groups = [];
		const other = {key: 'other', ids: [], names: [], colour: P.other, opacity: 1};
		for (const entity of visible) {
			if (entity.id in slotOf) {
				groups.push({key: 'e' + entity.id, id: entity.id, ids: [entity.id], name: entity.name, colour: P.slots[slotOf[entity.id]], opacity: 1});
			} else {
				other.ids.push(entity.id);
				other.names.push(entity.name);
			}
		}
		if (other.ids.length) {
			// Exactly one leftover keeps its own name (SPEC section 7, More entities than colours).
			other.name = other.ids.length === 1 ? other.names[0] : t('OTHER');
			groups.push(other);
		}
		if (overview.entities.some((entity) => entity.id === 0)) {
			groups.push({key: 'hidden', ids: [0], name: t('HIDDEN'), colour: P.other, opacity: 0.45});
		}
		return groups;
	}

	function groupOfEntity(groups, entityId) {
		return groups.find((group) => group.ids.indexOf(entityId) !== -1);
	}

	/** Sum of a sparse per-entity series over [from, to] for some entities. */
	function sumRange(series, ids, from, to) {
		let total = 0;
		for (const id of ids) {
			const s = series[id];
			if (!s) {
				continue;
			}
			for (let i = 0; i < s.d.length; i++) {
				if (s.d[i] >= from && s.d[i] <= to) {
					total += s.v[i];
				}
			}
		}
		return total;
	}

	/**
	 * Dense daily values over [first, last] from sparse {d, v} series. Values
	 * before `first` are kept as `carry`, so a cumulative line starts at its
	 * true value rather than at zero (SPEC section 15, 6c).
	 */
	function dense(sparseList, first, last) {
		const values = new Float64Array(last - first + 1);
		let carry = 0;
		for (const s of sparseList) {
			if (!s) {
				continue;
			}
			for (let i = 0; i < s.d.length; i++) {
				if (s.d[i] < first) {
					carry += s.v[i];
				} else if (s.d[i] <= last) {
					values[s.d[i] - first] += s.v[i];
				}
			}
		}
		return {values: values, carry: carry};
	}

	/** Per day, cumulative, or the trailing 7-day mean over [D-6, D]. */
	function applyMode(series, mode) {
		const v = series.values;
		const out = new Array(v.length);
		if (mode === 'cum') {
			let run = series.carry;
			for (let i = 0; i < v.length; i++) {
				run += v[i];
				out[i] = run;
			}
		} else if (mode === 'avg') {
			let run = 0;
			for (let i = 0; i < v.length; i++) {
				run += v[i] - (i >= 7 ? v[i - 7] : 0);
				out[i] = i >= 6 ? run / 7 : null;
			}
		} else {
			for (let i = 0; i < v.length; i++) {
				out[i] = v[i];
			}
		}
		return out;
	}

	/* ------------------------------------------------------------------ */
	/* Ranges (SPEC section 8, Time ranges)                                */
	/* ------------------------------------------------------------------ */

	const RANGES = ['all', 'year', 'month', 'week', 'today', 'custom'];

	function rangeDays(key, meta, custom) {
		const D = meta.as_of_day;
		const S = meta.start_day;
		switch (key) {
			case 'year':
				return [Math.max(S, D - 364), D];
			case 'month':
				return [Math.max(S, D - 29), D];
			case 'week':
				return [Math.max(S, D - 6), D];
			case 'today':
				return [D, D];
			case 'custom':
				return [Math.max(S, Math.min(custom[0], D)), Math.min(D, Math.max(custom[1], S))];
			default:
				return [S, D];
		}
	}

	function rangeLabel(range) {
		return range.key === 'custom'
			? fmtDay(range.from) + ' – ' + fmtDay(range.to)
			: t('RANGE_' + range.key.toUpperCase());
	}

	/*
	 * A range as chosen - a "spec" - is {key} for a relative range, which rolls
	 * forward with the data, or {key: 'custom', from, to} with ISO dates, which
	 * keeps its dates. Specs are what is remembered and put in the address;
	 * days are worked out from them against the data's as-of day each time.
	 */
	const isIso = (text) => /^\d{4}-\d{2}-\d{2}$/.test(text || '');

	function validSpec(spec) {
		if (!spec || RANGES.indexOf(spec.key) === -1) {
			return null;
		}
		if (spec.key !== 'custom') {
			return {key: spec.key};
		}
		return isIso(spec.from) && isIso(spec.to) ? {key: 'custom', from: spec.from, to: spec.to} : null;
	}

	const sameSpec = (a, b) => a.key === b.key && (a.key !== 'custom' || (a.from === b.from && a.to === b.to));

	/* Remembered for the browser tab's session only (SPEC section 8). */
	const SESSION = 'statcharts.range.';

	function remember(name, spec) {
		try {
			if (spec) {
				sessionStorage.setItem(SESSION + name, JSON.stringify(spec));
			} else {
				sessionStorage.removeItem(SESSION + name);
			}
		} catch (e) {
			// Storage blocked (private mode, policy): ranges simply are not remembered.
		}
	}

	function recall(name) {
		try {
			return validSpec(JSON.parse(sessionStorage.getItem(SESSION + name)));
		} catch (e) {
			return null;
		}
	}

	function forgetCards() {
		try {
			Object.keys(sessionStorage).filter((key) => key.indexOf(SESSION + 'card.') === 0)
				.forEach((key) => sessionStorage.removeItem(key));
		} catch (e) {
			// As above.
		}
	}

	/* The page range in the address, so a link shares it. */
	function specInUrl() {
		const query = new URLSearchParams(location.search);
		return validSpec({key: query.get('range'), from: query.get('from'), to: query.get('to')});
	}

	function writeUrl(spec) {
		const url = new URL(location.href);
		['range', 'from', 'to'].forEach((name) => url.searchParams.delete(name));
		if (spec.key !== 'all') {
			url.searchParams.set('range', spec.key);
			if (spec.key === 'custom') {
				url.searchParams.set('from', spec.from);
				url.searchParams.set('to', spec.to);
			}
		}
		history.replaceState(history.state, '', url.pathname + url.search + url.hash);
	}

	/**
	 * The page-wide range. Setting it is an action on every chart and table on
	 * the page: each is set to it, including any changed on its own before
	 * (SPEC section 8). Cards register while their tab is on screen.
	 */
	const pageRange = {
		spec: {key: 'all'},
		cards: new Map(),

		set(spec) {
			this.spec = spec;
			remember('page', spec);
			forgetCards();
			writeUrl(spec);
			this.cards.forEach((control) => control.apply(spec));
		},

		/** At load and on back/forward: the address wins over the session. */
		sync() {
			const inUrl = specInUrl();
			if (inUrl) {
				if (!sameSpec(inUrl, recall('page') || {key: 'all'})) {
					forgetCards();
				}
				this.spec = inUrl;
				remember('page', inUrl);
			} else {
				this.spec = recall('page') || {key: 'all'};
				writeUrl(this.spec);
			}
		},
	};

	/**
	 * A range selector. With an `id` it belongs to a card: it starts from the
	 * card's own remembered range, else the page's; a page change resets it;
	 * the card's own choice is remembered, and marked when it differs from the
	 * page's. Without one it is the page-wide selector.
	 */
	function rangeControl(meta, onChange, opts) {
		opts = opts || {};
		const id = opts.id || null;
		const cls = opts.page ? 'inputbox autowidth' : null;
		const select = h('select', {class: cls, 'aria-label': opts.label || t('RANGE_ALL')},
			RANGES.map((key) => h('option', {value: key, text: t('RANGE_' + key.toUpperCase())})));
		const dateInput = (label, day) => h('input', {type: 'date', class: cls, 'aria-label': label,
			min: isoDay(meta.start_day), max: isoDay(meta.as_of_day), value: isoDay(day), hidden: true});
		const from = dateInput(t('RANGE_FROM'), Math.max(meta.start_day, meta.as_of_day - 29));
		const to = dateInput(t('RANGE_TO'), meta.as_of_day);

		function show(spec) {
			select.value = spec.key;
			from.hidden = to.hidden = spec.key !== 'custom';
			if (spec.key === 'custom') {
				from.value = spec.from;
				to.value = spec.to;
			}
			if (id) {
				const differs = !sameSpec(spec, pageRange.spec);
				select.classList.toggle('sc-override', differs);
				select.title = differs ? t('RANGE_OVERRIDDEN') : '';
			}
		}

		function apply(spec) {
			show(spec);
			const custom = spec.key === 'custom' ? [dayOfIso(spec.from), dayOfIso(spec.to)] : null;
			const days = rangeDays(spec.key, meta, custom);
			onChange({key: spec.key, from: days[0], to: days[1]});
		}

		const complete = (input) => input.value !== '' && input.validity.valid;

		function chosen() {
			from.hidden = to.hidden = select.value !== 'custom';
			let spec = {key: select.value};
			if (spec.key === 'custom') {
				// A date input reports every keystroke that happens to form a
				// date (the year passes through 0002, 0020, 0202...). Only a
				// whole, valid pair applies; anything else waits for more typing.
				if (!complete(from) || !complete(to)) {
					return;
				}
				const pair = [from.value, to.value].sort();
				spec = {key: 'custom', from: pair[0], to: pair[1]};
			}
			if (id) {
				remember('card.' + id, sameSpec(spec, pageRange.spec) ? null : spec);
				apply(spec);
			} else {
				show(spec);
				pageRange.set(spec);
			}
		}

		let settling = null;
		const settle = () => {
			clearTimeout(settling);
			settling = setTimeout(chosen, 400);
		};
		select.addEventListener('change', chosen);
		from.addEventListener('change', settle);
		to.addEventListener('change', settle);

		const initial = (id && recall('card.' + id)) || pageRange.spec;
		if (id) {
			pageRange.cards.set(id, {apply: apply});
		}
		return {
			el: h('span', {class: 'sc-range'}, select, from, to),
			fire: () => (id ? apply(initial) : show(initial)),
		};
	}

	/* ------------------------------------------------------------------ */
	/* Cards, charts, tables                                               */
	/* ------------------------------------------------------------------ */

	let charts = [];

	function disposeCharts() {
		charts.forEach((chart) => chart.destroy());
		charts = [];
	}

	// Chart.js follows each chart's container on its own (responsive mode), so
	// grid reflows, window changes and the chart/table switch all resize it.
	Chart.defaults.font.family = fontFamily;
	Chart.defaults.color = P.ink;
	Chart.defaults.borderColor = P.grid;
	// Redrawn on every toggle and range change; instant reads better than motion.
	Chart.defaults.animation = false;

	/**
	 * A card, appended to `parent` at once, so any chart in it is created in a
	 * container that already has its size in the page.
	 */
	function card(parent, title, options) {
		options = options || {};
		const titleEl = h('h3', {class: 'sc-card-title', text: title});
		const controls = h('div', {class: 'sc-card-controls'});
		const body = h('div', {class: 'sc-card-body'});
		const caption = h('p', {class: 'sc-card-caption', hidden: true});
		const info = options.info ? h('span', {class: 'sc-info', title: options.info, 'aria-label': options.info, role: 'note', text: 'ⓘ'}) : null;
		const el = h('section', {class: 'sc-card' + (options.wide ? ' sc-wide' : '')},
			h('header', {class: 'sc-card-head'}, h('div', null, titleEl, info), controls),
			body, caption);
		parent.append(el);
		return {
			el: el,
			body: body,
			controls: controls,
			title: () => titleEl.textContent,
			setTitle: (text) => { titleEl.textContent = text; },
			setCaption: (text) => { caption.textContent = text || ''; caption.hidden = !text; },
		};
	}

	/**
	 * A chart with its table view (required in light mode, SPEC Appendix A) and
	 * the "no activity" state (an empty range never draws an empty chart).
	 */
	function chartView(c) {
		const canvas = h('canvas', {role: 'img'});
		const chartEl = h('div', {class: 'sc-chart'}, canvas);
		const emptyEl = h('div', {class: 'sc-empty', hidden: true, text: t('NO_ACTIVITY')});
		const tableEl = h('div', {class: 'sc-table-wrap', hidden: true});
		c.body.append(chartEl, emptyEl, tableEl);

		let chart = null;
		let tableFn = null;
		let asTable = false;
		let empty = false;

		const toggle = h('button', {type: 'button', 'aria-pressed': 'false', text: t('SHOW_TABLE')});
		toggle.addEventListener('click', () => {
			asTable = !asTable;
			toggle.textContent = t(asTable ? 'SHOW_CHART' : 'SHOW_TABLE');
			toggle.setAttribute('aria-pressed', String(asTable));
			sync();
		});
		c.controls.append(toggle);

		function sync() {
			emptyEl.hidden = !empty;
			chartEl.hidden = empty || asTable;
			tableEl.hidden = empty || !asTable;
			if (!tableEl.hidden && tableFn) {
				tableEl.replaceChildren(tableFn());
			}
		}

		return {
			draw: (config, makeTable) => {
				empty = !config;
				tableFn = makeTable;
				if (chart) {
					chart.destroy();
					charts.splice(charts.indexOf(chart), 1);
					chart = null;
				}
				sync();
				if (config) {
					canvas.setAttribute('aria-label', c.title());
					chart = new Chart(canvas, config);
					charts.push(chart);
				}
			},
		};
	}

	/**
	 * @param columns [{label, num, cell: (row, index) => node|string}]
	 */
	function table(columns, rows) {
		return h('table', {class: 'sc-table'},
			h('thead', null, h('tr', null, columns.map((col) => h('th', {class: col.num ? 'sc-num' : null, scope: 'col', text: col.label})))),
			h('tbody', null, rows.map((row, i) => h('tr', null, columns.map((col) => h('td', {class: col.num ? 'sc-num' : null}, col.cell(row, i)))))));
	}

	function swatch(group) {
		return h('span', {class: 'sc-swatch', 'aria-hidden': 'true', style: 'background:' + group.colour + ';opacity:' + group.opacity});
	}

	/** '#rrggbb' at an opacity, for Chart.js, which takes colours not opacities. */
	function rgba(hex, alpha) {
		const n = parseInt(hex.slice(1), 16);
		return 'rgba(' + (n >> 16 & 255) + ',' + (n >> 8 & 255) + ',' + (n & 255) + ',' + alpha + ')';
	}

	function tooltipBase() {
		return {
			backgroundColor: P.surface,
			borderColor: P.axis,
			borderWidth: 1,
			titleColor: P.ink,
			bodyColor: P.ink,
			footerColor: P.ink2,
			rtl: RTL,
			textDirection: RTL ? 'rtl' : 'ltr',
			boxPadding: 4,
		};
	}

	function axisDate(day) {
		const date = new Date(day * DAY);
		if (date.getUTCMonth() === 0 && date.getUTCDate() === 1) {
			return String(date.getUTCFullYear());
		}
		return date.getUTCDate() === 1 ? monthFmt.format(date) : dateFmt.format(date);
	}

	/**
	 * Ticks on calendar boundaries - years, or months (every 1, 2, 3 or 6), or
	 * days - whichever fits `count` ticks. Days are plain numbers on a linear
	 * axis, so no date library is needed and no browser timezone can shift one.
	 */
	function dayTicks(min, max, count) {
		const ticks = [];
		const span = max - min;
		const first = new Date(min * DAY);
		if (span > 730) {
			const step = Math.max(1, Math.ceil(span / 365.25 / count));
			for (let year = first.getUTCFullYear(); ; year++) {
				const day = Date.UTC(year, 0, 1) / DAY;
				if (day > max) {
					break;
				}
				if (day >= min && year % step === 0) {
					ticks.push(day);
				}
			}
		} else if (span > 60) {
			const months = span / 30.44;
			const step = [1, 2, 3, 6, 12].find((s) => months / s <= count) || 12;
			for (let m = first.getUTCFullYear() * 12 + first.getUTCMonth(); ; m++) {
				const day = Date.UTC(Math.floor(m / 12), m % 12, 1) / DAY;
				if (day > max) {
					break;
				}
				if (day >= min && m % step === 0) {
					ticks.push(day);
				}
			}
		} else {
			const step = Math.max(1, Math.ceil((span + 1) / count));
			for (let day = min; day <= max; day += step) {
				ticks.push(day);
			}
		}
		return ticks;
	}

	const MODE_KEYS = {avg: 'MODE_AVG', day: 'MODE_DAY', cum: 'MODE_CUM'};

	/**
	 * Time series. Time runs left to right in both directions of text, as in
	 * the old app and in Hebrew print; only the text is right-to-left. The
	 * legend is a row above the plot, in display order (the forum index's), and
	 * flows from the right in RTL.
	 *
	 * @param lines [{name, colour, opacity, series: dense()}]
	 */
	function timeConfig(lines, first, from, to, mode, opts) {
		// No partial windows: the average (and any 7-day series) starts once
		// seven days exist (SPEC section 15, 6a).
		const start = (mode === 'avg' || opts.window7) ? Math.max(from, first + 6) : from;
		if (start > to) {
			return null;
		}
		const format = mode === 'avg' ? (v) => nf1.format(v) : (v) => nf.format(v);
		// A line through one or two points is invisible or meaningless: a range
		// that short ("today") is drawn as bars, stacked where the chart stacks.
		const bars = (opts.bar && mode === 'day') || to - start < 3;
		const several = lines.length > 1;

		const datasets = lines.map((line, i) => {
			const values = applyMode(line.series, mode);
			const data = [];
			for (let day = start; day <= to; day++) {
				const v = values[day - first];
				data.push({x: day, y: v === null ? null : (mode === 'avg' ? Math.round(v * 10) / 10 : v)});
			}
			return {
				type: bars ? 'bar' : 'line',
				label: line.name,
				data: data,
				borderColor: rgba(line.colour, line.opacity),
				backgroundColor: rgba(line.colour, (opts.stacked ? 0.85 : 1) * line.opacity),
				borderWidth: opts.stacked ? 0.5 : 1.5,
				// A stacked area fills down to the series below it.
				fill: opts.stacked ? (i === 0 ? 'origin' : '-1') : false,
				pointRadius: 0,
				pointHoverRadius: 3,
				barPercentage: 1,
				categoryPercentage: 1,
				maxBarThickness: 12,
			};
		});

		return {
			data: {datasets: datasets},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				parsing: false,
				normalized: true,
				interaction: {mode: 'index', intersect: false},
				scales: {
					x: {
						type: 'linear',
						min: start,
						max: to,
						offset: bars,
						stacked: bars && !!opts.stacked,
						grid: {display: false},
						border: {color: P.axis},
						ticks: {color: P.ink2, autoSkip: false, maxRotation: 0, callback: (v) => axisDate(v)},
						// One label per ~90px of the chart's width at this layout.
						afterBuildTicks: (scale) => {
							scale.ticks = dayTicks(start, to, Math.max(3, Math.floor(scale.chart.width / 90))).map((day) => ({value: day}));
						},
					},
					y: {
						stacked: !!opts.stacked,
						beginAtZero: true,
						grid: {color: P.grid},
						border: {display: false},
						ticks: {color: P.ink2, callback: (v) => nf.format(v)},
					},
				},
				plugins: {
					legend: several ? {
						position: 'top',
						align: 'start',
						rtl: RTL,
						textDirection: RTL ? 'rtl' : 'ltr',
						labels: {color: P.ink, boxWidth: 12, boxHeight: 12, useBorderRadius: true, borderRadius: 2},
					} : {display: false},
					tooltip: Object.assign(tooltipBase(), {
						callbacks: {
							title: (items) => items.length ? fmtDay(items[0].parsed.x) + ' · ' + t(MODE_KEYS[mode]) : '',
							label: (item) => item.dataset.label + ': ' + format(item.parsed.y),
							footer: (items) => opts.stacked && items.length > 1
								? t('TOTAL') + ': ' + format(items.reduce((sum, item) => sum + (item.parsed.y || 0), 0))
								: '',
						},
					}),
				},
			},
		};
	}

	/** The table view of a time series: one row per month, newest first. */
	function monthTable(lines, first, from, to, mode) {
		const perLine = lines.map((line) => ({day: applyMode(line.series, 'day'), shown: applyMode(line.series, mode)}));
		const months = [];
		for (let day = from; day <= to; day++) {
			const date = new Date(day * DAY);
			const key = date.getUTCFullYear() * 12 + date.getUTCMonth();
			if (!months.length || months[months.length - 1].key !== key) {
				months.push({key: key, date: date, sums: lines.map(() => 0), last: lines.map(() => 0), days: 0});
			}
			const month = months[months.length - 1];
			month.days++;
			perLine.forEach((p, i) => {
				month.sums[i] += p.day[day - first];
				month.last[i] = p.shown[day - first];
			});
		}
		const value = (month, i) => mode === 'cum' ? month.last[i] : (mode === 'avg' ? month.sums[i] / month.days : month.sums[i]);
		const format = mode === 'avg' ? (v) => nf1.format(v) : (v) => nf.format(v);
		const columns = [{label: t('COL_MONTH'), cell: (m) => monthFmt.format(m.date)}]
			.concat(lines.map((line, i) => ({label: line.name, num: true, cell: (m) => format(value(m, i) || 0)})));
		if (lines.length > 1) {
			columns.push({label: t('TOTAL'), num: true, cell: (m) => format(lines.reduce((sum, _, i) => sum + (value(m, i) || 0), 0))});
		}
		return table(columns, months.reverse());
	}

	/**
	 * A time-series card with the 7-day average / per day / cumulative toggle.
	 * A mode is offered only where every series supports it (SPEC section 8).
	 */
	function seriesCard(c, lines, meta, opts) {
		const view = chartView(c);
		const first = opts.first !== undefined ? opts.first : meta.first_day;
		let from = opts.from !== undefined ? opts.from : meta.start_day;
		let to = opts.to !== undefined ? opts.to : meta.as_of_day;
		let mode = opts.mode;


		const buttons = {};
		if (opts.modes.length > 1) {
			const group = h('span', {class: 'sc-modes', role: 'group'});
			for (const m of opts.modes) {
				buttons[m] = h('button', {type: 'button', 'aria-pressed': String(m === mode), text: t(MODE_KEYS[m])});
				buttons[m].addEventListener('click', () => {
					mode = m;
					for (const other of opts.modes) {
						buttons[other].setAttribute('aria-pressed', String(other === mode));
					}
					draw();
				});
				group.append(buttons[m]);
			}
			c.controls.prepend(group);
		}

		const hasData = lines.some((line) => line.series.carry || line.series.values.some((v) => v));

		function draw() {
			const config = hasData ? timeConfig(lines, first, from, to, mode, opts) : null;
			view.draw(config, () => monthTable(lines, first, from, to, mode));
		}

		// A range here zooms the chart to that window. The values are still
		// computed over all the data: a cumulative line enters the window at its
		// true total, and the average counts the days before the window.
		// Prepended after the mode buttons, so it comes first, as on every card.
		if (opts.ranged === false) {
			draw();
			return;
		}
		const range = rangeControl(meta, (r) => {
			from = r.from;
			to = r.to;
			c.setCaption(r.key === 'today' ? t('TODAY_CAPTION', {time: asOfTime(meta)}) : '');
			draw();
		}, {id: opts.id});
		c.controls.prepend(range.el);
		range.fire();
	}

	/** Shortens text with an ellipsis until it fits `maxWidth` in the current font. */
	function fitText(ctx, text, maxWidth) {
		if (ctx.measureText(text).width <= maxWidth) {
			return text;
		}
		let cut = text;
		while (cut.length > 1 && ctx.measureText(cut + '…').width > maxWidth) {
			cut = cut.slice(0, -1);
		}
		return cut + '…';
	}

	/**
	 * Direct labels for doughnuts (required relief for the light palette,
	 * Appendix A): name and share outside the ring, at the card's edges, joined
	 * to their slice by a leader line. Names are cut to fit - the tooltip and
	 * the table view carry them in full - labels on each side keep two lines
	 * apart, and slices too thin to point at go unlabelled.
	 */
	const pieLabels = {
		id: 'scPieLabels',
		afterDraw(chart) {
			const meta = chart.getDatasetMeta(0);
			const values = chart.data.datasets[0].data;
			const total = values.reduce((sum, v) => sum + v, 0);
			const lineHeight = 14;
			const labels = [];
			meta.data.forEach((arc, i) => {
				const p = arc.getProps(['x', 'y', 'startAngle', 'endAngle', 'outerRadius'], true);
				if (p.endAngle - p.startAngle < 0.2) {
					return;
				}
				const mid = (p.startAngle + p.endAngle) / 2;
				const cos = Math.cos(mid);
				const sin = Math.sin(mid);
				labels.push({
					i: i,
					right: cos >= 0,
					ax: p.x + cos * p.outerRadius, ay: p.y + sin * p.outerRadius,
					ex: p.x + cos * (p.outerRadius + 8), y: p.y + sin * (p.outerRadius + 8),
				});
			});
			for (const side of [true, false]) {
				const list = labels.filter((l) => l.right === side).sort((a, b) => a.y - b.y);
				for (let k = 1; k < list.length; k++) {
					list[k].y = Math.max(list[k].y, list[k - 1].y + 2 * lineHeight + 2);
				}
				for (let k = list.length - 1; k >= 0; k--) {
					const limit = k === list.length - 1 ? chart.height - lineHeight - 2 : list[k + 1].y - 2 * lineHeight - 2;
					list[k].y = Math.max(lineHeight + 2, Math.min(list[k].y, limit));
				}
			}

			const ctx = chart.ctx;
			const room = labelRoom(chart.width) - 10;
			ctx.save();
			ctx.font = '12px ' + fontFamily;
			ctx.direction = RTL ? 'rtl' : 'ltr';
			ctx.fillStyle = P.ink;
			ctx.strokeStyle = P.axis;
			ctx.lineWidth = 1;
			for (const l of labels) {
				const name = fitText(ctx, chart.data.labels[l.i], room);
				const share = pf.format(values[l.i] / total);
				const x = l.right ? chart.width - 2 : 2;
				const width = Math.max(ctx.measureText(name).width, ctx.measureText(share).width);
				ctx.beginPath();
				ctx.moveTo(l.ax, l.ay);
				ctx.lineTo(l.ex, l.y);
				ctx.lineTo(l.right ? x - width - 4 : x + width + 4, l.y);
				ctx.stroke();
				ctx.textAlign = l.right ? 'right' : 'left';
				ctx.textBaseline = 'bottom';
				ctx.fillText(name, x, l.y - 1);
				ctx.textBaseline = 'top';
				ctx.fillText(share, x, l.y + 1);
			}
			ctx.restore();
		},
	};

	/**
	 * Room outside the ring for the labels, on both sides. Worked out at every
	 * layout from the chart's current width: the grid may still be reflowing
	 * when the chart is created.
	 */
	function labelRoom(width) {
		return Math.round(Math.min(110, Math.max(64, width * 0.26)));
	}

	function pieConfig(items) {
		const shown = items.filter((item) => item.value > 0);
		if (!shown.length) {
			return null;
		}
		const total = shown.reduce((sum, item) => sum + item.value, 0);
		return {
			type: 'doughnut',
			data: {
				labels: shown.map((item) => item.name),
				datasets: [{
					data: shown.map((item) => item.value),
					backgroundColor: shown.map((item) => rgba(item.colour, item.opacity)),
					borderColor: P.surface,
					borderWidth: 1,
					hoverOffset: 4,
				}],
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				cutout: '58%',
				layout: {padding: (context) => {
					const room = labelRoom(context.chart.width);
					return {left: room, right: room, top: 24, bottom: 24};
				}},
				plugins: {
					legend: {display: false},
					tooltip: Object.assign(tooltipBase(), {
						callbacks: {label: (item) => item.label + ': ' + nf.format(item.parsed) + ' (' + pf.format(item.parsed / total) + ')'},
					}),
				},
			},
			plugins: [pieLabels],
		};
	}

	function pieTable(items, valueLabel) {
		const total = items.reduce((sum, item) => sum + item.value, 0);
		return table([
			{label: t('COL_FORUM'), cell: (item) => [swatch(item), item.name]},
			{label: valueLabel, num: true, cell: (item) => nf.format(item.value)},
			{label: t('COL_SHARE'), num: true, cell: (item) => total ? pf.format(item.value / total) : ''},
		], items.filter((item) => item.value > 0));
	}

	/**
	 * A pie over a range, computed from the overview payload, instantly.
	 * Colour pinning pays off here: a "past week" pie with three forums keeps
	 * their own colours (SPEC section 8).
	 */
	function pieCard(parent, title, series, groups, meta, opts) {
		opts = opts || {};
		const c = card(parent, title, opts);
		const view = chartView(c);
		const range = rangeControl(meta, (r) => {
			const items = groups.map((group) => ({name: group.name, colour: group.colour, opacity: group.opacity,
				value: sumRange(series, group.ids, r.from, r.to)}));
			view.draw(pieConfig(items), () => pieTable(items, opts.valueLabel));
			if (opts.titleFor) {
				c.setTitle(opts.titleFor(r));
			}
			c.setCaption(r.key === 'today' ? t('TODAY_CAPTION', {time: asOfTime(meta)}) : (opts.caption || ''));
		}, {id: opts.id});
		c.controls.prepend(range.el);
		range.fire();
		return c;
	}

	/* ------------------------------------------------------------------ */
	/* Links and top-N tables                                              */
	/* ------------------------------------------------------------------ */

	function tabUrl(tab, id) {
		const url = new URL(location.href);
		url.searchParams.set('tab', tab);
		if (id) {
			url.searchParams.set('id', id);
		} else {
			url.searchParams.delete('id');
		}
		return url.pathname + url.search;
	}

	function tabLink(tab, id, text) {
		return h('a', {href: tabUrl(tab, id), 'data-tab': tab, 'data-id': id, text: text});
	}

	function externalLink(url) {
		return h('a', {class: 'sc-ext', href: url, target: '_blank', rel: 'noopener', title: t('OPEN_ON_FORUM')},
			h('i', {class: 'icon fa-external-link fa-fw', 'aria-hidden': 'true'}),
			h('span', {class: 'sc-visually-hidden', text: t('OPEN_ON_FORUM')}));
	}

	// cfg.viewtopic carries ?sid= when the board puts the session in URLs.
	const viewtopic = cfg.viewtopic + (cfg.viewtopic.includes('?') ? '&' : '?');
	const topicUrl = (topicId) => viewtopic + 't=' + topicId;
	const postUrl = (postId) => viewtopic + 'p=' + postId + '#p' + postId;

	function userCell(userId, name) {
		// The anonymous account is not a member to look up.
		return cfg.can_profiles && userId && userId !== 1 ? tabLink('user', userId, name || '—') : (name || '—');
	}

	function forumCell(row) {
		return row.entity ? tabLink('forum', row.entity, row.forum) : (row.forum || '—');
	}

	function topicCell(row, withForum) {
		return [
			tabLink('topic', row.topic_id, row.title || '—'),
			externalLink(topicUrl(row.topic_id)),
			withForum ? [' ', h('small', null, forumCell(row))] : null,
		];
	}

	const COL = {
		rank: {label: '#', num: true, cell: (row, i) => nf.format(i + 1)},
		topic: {label: t('COL_TOPIC'), cell: (row) => topicCell(row, true)},
		topicOnly: {label: t('COL_TOPIC'), cell: (row) => topicCell(row, false)},
		forum: {label: t('COL_FORUM'), cell: forumCell},
		author: {label: t('COL_USER'), cell: (row) => userCell(row.poster_id, row.poster)},
		member: {label: t('COL_USER'), cell: (row) => userCell(row.user_id, row.name)},
		post: {label: t('COL_POST'), cell: (row) => h('span', {class: 'sc-preview'}, row.preview || '—', externalLink(postUrl(row.post_id)))},
		views: {label: t('COL_VIEWS'), num: true, cell: (row) => nf.format(row.views)},
		posts: {label: t('COL_POSTS'), num: true, cell: (row) => nf.format(row.posts)},
		likes: {label: t('COL_LIKES'), num: true, cell: (row) => nf.format(row.likes)},
	};

	/**
	 * A server-side top-N table. With a meta, it has a range selector; ranking
	 * over an arbitrary range cannot be done client-side (SPEC section 8).
	 */
	function topCard(parent, title, request, columns, opts) {
		opts = opts || {};
		const c = card(parent, title, opts);
		const box = h('div', {class: 'sc-table-wrap sc-top-wrap'});
		c.body.append(box);
		let latest = 0;

		async function load(range) {
			const ticket = ++latest;
			// Title and caption follow the range chosen, at once: set after the
			// response instead, a slow reply to an earlier range would win.
			if (range && opts.titleFor) {
				c.setTitle(opts.titleFor(range));
			}
			if (range && opts.meta) {
				c.setCaption(range.key === 'today' ? t('TODAY_CAPTION', {time: asOfTime(opts.meta)}) : (opts.caption || ''));
			}
			box.replaceChildren(message(t('LOADING')));
			const params = Object.assign({}, request, range ? {from: range.from, to: range.to} : {});
			try {
				const rows = await api('top', params);
				if (ticket === latest) {
					box.replaceChildren(rows.length ? table(columns, rows) : message(range ? t('NO_ACTIVITY') : t('NO_ROWS')));
				}
			} catch (e) {
				if (ticket === latest) {
					box.replaceChildren(failure(e));
				}
			}
		}

		if (opts.meta) {
			const range = rangeControl(opts.meta, load, {id: opts.id});
			c.controls.prepend(range.el);
			range.fire();
		} else {
			c.setCaption(opts.caption || '');
			load(null);
		}
		return c;
	}

	/* ------------------------------------------------------------------ */
	/* Tabs                                                                */
	/* ------------------------------------------------------------------ */

	function asOfTime(meta) {
		return new Intl.DateTimeFormat(cfg.locale, {timeZone: meta.timezone, hour: '2-digit', minute: '2-digit'}).format(meta.built_at * 1000);
	}

	function asOfLine(meta) {
		const when = new Intl.DateTimeFormat(cfg.locale, {timeZone: meta.timezone, dateStyle: 'medium', timeStyle: 'short'}).format(meta.built_at * 1000);
		return t('AS_OF', {time: when});
	}

	function viewsTitle(range) {
		return range.key === 'all' ? t('VIEWS_BY_FORUM') : t('VIEWS_BY_FORUM_RANGE', {range: rangeLabel(range)});
	}

	function mostViewedTitle(range) {
		// The label changes exactly when the meaning does (reframing C, SPEC section 8).
		return range.key === 'all' ? t('MOST_VIEWED') : t('MOST_VIEWED_RANGE', {range: rangeLabel(range)});
	}

	function renderGeneral(view, ov) {
		const meta = ov.meta;
		const groups = displayGroups(ov);
		const lines = (series) => groups.map((group) => ({name: group.name, colour: group.colour, opacity: group.opacity,
			series: dense(group.ids.map((id) => series[id]), meta.first_day, meta.as_of_day)}));

		const top = h('div', {class: 'sc-grid'});
		const pies = h('div', {class: 'sc-grid'});
		const people = h('div', {class: 'sc-grid'});
		const tables = h('div', {class: 'sc-grid'});
		view.append(top, pies, people, tables);

		seriesCard(card(top, t('POSTS_OVER_TIME'), {wide: true}), lines(ov.posts), meta,
			{id: 'general.posts', modes: ['avg', 'day', 'cum'], mode: 'avg', stacked: true});

		pieCard(pies, t('POSTS_BY_FORUM'), ov.posts, groups, meta, {id: 'general.pie.posts', valueLabel: t('COL_POSTS')});
		pieCard(pies, t('TOPICS_BY_FORUM'), ov.topics, groups, meta, {id: 'general.pie.topics', valueLabel: t('COL_TOPICS')});
		if (meta.has_likes) {
			pieCard(pies, t('LIKES_BY_FORUM'), ov.likes, groups, meta,
				{id: 'general.pie.likes', valueLabel: t('COL_LIKES'), info: t('LIKES_EXPLAIN')});
		}
		pieCard(pies, t('VIEWS_BY_FORUM'), ov.views, groups, meta,
			{id: 'general.pie.views', valueLabel: t('COL_VIEWS'), titleFor: viewsTitle, caption: t('VIEWS_NOTE')});

		const u = ov.users;
		const userSeries = (values) => dense([{d: u.d, v: values}], meta.first_day, meta.as_of_day);
		seriesCard(card(people, t('USERS')), [
			{name: t('USERS_REGISTERED'), colour: P.slots[0], opacity: 1, series: userSeries(u.registered)},
			{name: t('USERS_POSTED'), colour: P.slots[1], opacity: 1, series: userSeries(u.registered_active)},
		], meta, {id: 'general.users', modes: ['cum', 'day', 'avg'], mode: 'cum'});

		// A rolling distinct count: never cumulated, never averaged again.
		seriesCard(card(people, t('ACTIVE_7D'), {info: t('ACTIVE_7D_EXPLAIN')}), [
			{name: t('ACTIVE_7D'), colour: P.slots[0], opacity: 1, series: userSeries(u.active_7d)},
		], meta, {id: 'general.active', modes: ['day'], mode: 'day', window7: true});

		topCard(tables, t('MOST_VIEWED'), {widget: 'viewed'}, [COL.rank, COL.topic, COL.views],
			{meta: meta, id: 'general.viewed', titleFor: mostViewedTitle, caption: t('VIEWS_NOTE')});
		topCard(tables, t('MOST_ACTIVE'), {widget: 'active'}, [COL.rank, COL.topic, COL.posts], {meta: meta, id: 'general.active_topics'});
		if (meta.has_likes) {
			topCard(tables, t('MOST_LIKED'), {widget: 'liked'}, [COL.rank, COL.forum, COL.topicOnly, COL.author, COL.post, COL.likes],
				{meta: meta, id: 'general.liked', wide: true, info: t('LIKES_EXPLAIN')});
		}
	}

	function renderForum(view, ov, id) {
		const meta = ov.meta;
		const groups = displayGroups(ov);
		const entities = ov.entities.filter((entity) => entity.id !== 0);
		if (!entities.length) {
			view.append(message(t('NO_ROWS')));
			return;
		}
		// The id is a group's, or any forum's: a link from a sub-forum's page
		// carries the sub-forum, which opens the group it is counted in.
		const entity = id
			? entities.find((e) => e.id === id) || entities.find((e) => (e.forums || []).includes(id))
			: entities[0];

		const select = h('select', {class: 'inputbox autowidth', 'aria-label': t('FORUM_SELECT')},
			(entity ? [] : [h('option', {value: '', selected: true, text: '—'})])
				.concat(entities.map((e) => h('option', {value: e.id, selected: !!entity && e.id === entity.id, text: e.name}))));
		select.addEventListener('change', () => go('forum', parseInt(select.value, 10)));
		if (!entity) {
			view.append(h('div', {class: 'sc-toolbar'}, h('label', null, t('FORUM_SELECT') + ' ', select)), message(t('FORUM_NOT_IN_STATS')));
			return;
		}
		const group = groupOfEntity(groups, entity.id);
		const totals = ov.totals[entity.id] || {posts: 0, topics: 0};

		view.append(
			h('div', {class: 'sc-toolbar'}, h('label', null, t('FORUM_SELECT') + ' ', select)),
			h('div', {class: 'sc-heading'},
				h('h3', null, swatch(group), entity.name),
				h('p', {text: t('FORUM_HEADER', {posts: nf.format(totals.posts), topics: nf.format(totals.topics)})})));

		const grid = h('div', {class: 'sc-grid'});
		view.append(grid);
		seriesCard(card(grid, t('POSTS_OVER_TIME'), {wide: true}), [{name: entity.name, colour: group.colour, opacity: group.opacity,
			series: dense([ov.posts[entity.id]], meta.first_day, meta.as_of_day)}], meta,
			{id: 'forum.posts', modes: ['avg', 'day', 'cum'], mode: 'avg'});
		// Tables share a row of their own, so they fill it: a wide card in the same
		// grid would fix the column count and leave the rest of the row empty.
		const tables = h('div', {class: 'sc-grid'});
		view.append(tables);
		topCard(tables, t('MOST_VIEWED'), {widget: 'viewed', scope: 'forum', id: entity.id}, [COL.rank, COL.topicOnly, COL.views],
			{meta: meta, id: 'forum.viewed', titleFor: mostViewedTitle, caption: t('VIEWS_NOTE')});
		topCard(tables, t('MOST_ACTIVE'), {widget: 'active', scope: 'forum', id: entity.id}, [COL.rank, COL.topicOnly, COL.posts],
			{meta: meta, id: 'forum.active_topics'});
		topCard(tables, t('FORUM_TOP_USERS'), {widget: 'posters', scope: 'forum', id: entity.id},
			meta.has_likes ? [COL.rank, COL.member, COL.posts, COL.likes] : [COL.rank, COL.member, COL.posts],
			{meta: meta, id: 'forum.posters'});
		if (meta.has_likes) {
			topCard(tables, t('FORUM_TOP_LIKED'), {widget: 'liked_members', scope: 'forum', id: entity.id},
				[COL.rank, COL.member, COL.likes, COL.posts], {meta: meta, id: 'forum.liked_members', info: t('LIKES_EXPLAIN')});
		}
	}

	/** A name search over a lazily loaded list, with a datalist for suggestions. */
	function searchBox(placeholder, load, onPick, notFound) {
		const listId = 'sc-list-' + Math.random().toString(36).slice(2);
		const input = h('input', {type: 'search', class: 'inputbox autowidth', list: listId, placeholder: placeholder, 'aria-label': placeholder, autocomplete: 'off'});
		const datalist = h('datalist', {id: listId});
		const note = h('span', {class: 'sc-note', role: 'status'});
		let entries = null;

		async function ensure() {
			if (!entries) {
				entries = await load();
				datalist.replaceChildren(...entries.map((entry) => h('option', {value: entry.name})));
			}
			return entries;
		}

		async function pick() {
			const wanted = input.value.trim().toLowerCase();
			const found = (await ensure()).find((entry) => entry.name.toLowerCase() === wanted);
			note.textContent = found ? '' : notFound;
			if (found) {
				onPick(found.id);
			}
		}

		input.addEventListener('focus', ensure, {once: true});
		input.addEventListener('keydown', (e) => {
			if (e.key === 'Enter') {
				e.preventDefault();
				pick();
			}
		});
		const button = h('button', {type: 'button', class: 'button', text: t('GO')});
		button.addEventListener('click', pick);
		return {
			el: h('span', {class: 'sc-search'}, input, datalist, ' ', button, ' ', note),
			reset: (list) => {
				entries = list;
				datalist.replaceChildren(...list.map((entry) => h('option', {value: entry.name})));
			},
		};
	}

	async function renderUser(view, ov, id) {
		const meta = ov.meta;
		const search = searchBox(t('USER_SEARCH'), async () => {
			const list = await api('users');
			return list.id.map((userId, i) => ({id: userId, name: list.name[i]}));
		}, (userId) => go('user', userId), t('USER_NOT_FOUND'));
		view.append(h('div', {class: 'sc-toolbar'}, search.el));

		const data = await api('user', id ? {id: id} : {});
		if (!data.user) {
			view.append(message(t('USER_PICK')));
			return;
		}

		const groups = displayGroups(ov);
		view.append(h('div', {class: 'sc-heading'},
			h('h3', {style: data.user.colour ? 'color:#' + data.user.colour : null, text: data.user.name}),
			h('p', {text: t('USER_HEADER', {posts: nf.format(data.totals.posts), likes: nf.format(data.totals.likes)})})));

		const grid = h('div', {class: 'sc-grid'});
		view.append(grid);

		const byEntity = data.posts || {};
		seriesCard(card(grid, t('POSTS_OVER_TIME'), {wide: true}), groups.map((group) => ({name: group.name, colour: group.colour, opacity: group.opacity,
			series: dense(group.ids.map((entityId) => byEntity[entityId]), meta.first_day, meta.as_of_day)}))
			.filter((line) => line.series.values.some((v) => v)), meta,
			{id: 'user.posts', modes: ['avg', 'day', 'cum'], mode: 'avg', stacked: true});

		// The pie and the tables share a row of their own, so they fill it.
		const row = h('div', {class: 'sc-grid'});
		view.append(row);
		pieCard(row, t('POSTS_BY_FORUM'), byEntity, groups, meta, {id: 'user.pie', valueLabel: t('COL_POSTS')});
		topCard(row, t('USER_TOP_TOPICS'), {widget: 'active', scope: 'user', id: data.user.id},
			[COL.rank, COL.topic, COL.posts], {meta: meta, id: 'user.topics'});
		if (meta.has_likes) {
			topCard(row, t('MOST_LIKED'), {widget: 'liked', scope: 'user', id: data.user.id},
				[COL.rank, COL.topic, COL.post, COL.likes], {meta: meta, id: 'user.liked', info: t('LIKES_EXPLAIN')});
		}
	}

	async function renderTopic(view, ov, id) {
		const meta = ov.meta;
		const groups = displayGroups(ov);
		const entities = ov.entities.filter((entity) => entity.id !== 0);

		let all = null;
		const loadTopics = async () => {
			if (!all) {
				const list = await api('topics');
				all = list.id.map((topicId, i) => ({id: topicId, name: list.title[i], forum: list.forum_id[i], replies: list.replies[i]}));
			}
			return filtered();
		};
		const forumSelect = h('select', {class: 'inputbox autowidth', 'aria-label': t('COL_FORUM')},
			h('option', {value: '0', text: t('TOPIC_ALL_FORUMS')}),
			entities.map((e) => h('option', {value: e.id, text: e.name})));
		const sortSelect = h('select', {class: 'inputbox autowidth', 'aria-label': t('SORT_TITLE')},
			h('option', {value: 'title', text: t('SORT_TITLE')}),
			h('option', {value: 'replies', text: t('SORT_REPLIES')}));
		const filtered = () => {
			const entityId = parseInt(forumSelect.value, 10);
			const entity = entities.find((e) => e.id === entityId);
			const list = entity ? all.filter((topic) => entity.forums.indexOf(topic.forum) !== -1) : all.slice();
			// Reply count is lifetime by design (SPEC section 15, 6b).
			return sortSelect.value === 'replies'
				? list.sort((a, b) => b.replies - a.replies)
				: list.sort((a, b) => a.name.localeCompare(b.name, cfg.locale));
		};
		const search = searchBox(t('TOPIC_SEARCH'), loadTopics, (topicId) => go('topic', topicId), t('TOPIC_NOT_FOUND'));
		const refilter = async () => {
			await loadTopics();
			search.reset(filtered());
		};
		forumSelect.addEventListener('change', refilter);
		sortSelect.addEventListener('change', refilter);
		view.append(h('div', {class: 'sc-toolbar'}, search.el, forumSelect, sortSelect));

		let data;
		try {
			data = await api('topic', id ? {id: id} : {});
		} catch (e) {
			if (e.status === 404) {
				view.append(message(t('TOPIC_NOT_FOUND')));
				return;
			}
			throw e;
		}
		const topic = data.topic;
		const group = (topic.entity !== null && groupOfEntity(groups, topic.entity)) || {colour: P.slots[0], opacity: 1};

		view.append(h('div', {class: 'sc-heading'},
			h('h3', null, topic.title, externalLink(topicUrl(topic.id))),
			h('p', null, tNodes('TOPIC_HEADER', {
				poster: userCell(topic.poster_id, topic.poster),
				date: fmtDay(topic.day),
				forum: topic.entity ? tabLink('forum', topic.entity, topic.forum) : topic.forum,
			}))));

		const grid = h('div', {class: 'sc-grid'});
		view.append(grid);
		const whole = card(grid, t('TOPIC_ACTIVITY'), {wide: true});
		const zoom = card(grid, t('TOPIC_ACTIVITY_ZOOM'), {wide: true});
		// The tables share a row of their own, so they fill it.
		const tables = h('div', {class: 'sc-grid'});
		view.append(tables);
		topCard(tables, t('TOPIC_TOP_USERS'), {widget: 'posters', scope: 'topic', id: topic.id},
			meta.has_likes ? [COL.rank, COL.member, COL.posts, COL.likes] : [COL.rank, COL.member, COL.posts],
			{meta: meta, id: 'topic.posters'});
		if (meta.has_likes) {
			topCard(tables, t('MOST_LIKED'), {widget: 'liked', scope: 'topic', id: topic.id},
				[COL.rank, COL.author, COL.post, COL.likes], {meta: meta, id: 'topic.liked', info: t('LIKES_EXPLAIN')});
		}

		const line = [{name: topic.title, colour: group.colour, opacity: group.opacity,
			series: dense([data.posts], meta.first_day, meta.as_of_day)}];
		seriesCard(whole, line, meta, {id: 'topic.posts', modes: ['day', 'cum', 'avg'], mode: 'day', bar: true});

		// Zoomed to the topic's own life, padded so a one-day topic still has a width.
		const days = data.posts.d;
		const firstDay = days.length ? days[0] : topic.day;
		const lastDay = days.length ? days[days.length - 1] : topic.day;
		const pad = Math.max(3, Math.round((lastDay - firstDay) * 0.03));
		seriesCard(zoom, line, meta, {modes: ['day', 'cum'], mode: 'day', bar: true, ranged: false,
			from: Math.max(meta.first_day, firstDay - pad), to: Math.min(meta.as_of_day, lastDay + pad)});
	}

	/* ------------------------------------------------------------------ */
	/* Shell and routing                                                   */
	/* ------------------------------------------------------------------ */

	const nav = h('nav', {class: 'sc-tabs', 'aria-label': t('TAB_GENERAL')});
	const asOf = h('p', {class: 'sc-asof', role: 'status'});
	const pageBar = h('div', {class: 'sc-pagerange'});
	const view = h('div', {class: 'sc-view'});
	root.replaceChildren(nav, asOf, pageBar, view);

	/** The page-wide selector, labelled as what it does (SPEC section 8). */
	function renderPageBar(meta) {
		const control = rangeControl(meta, () => {}, {page: true, label: t('PAGE_RANGE')});
		const labelId = 'sc-pagerange-label';
		pageBar.replaceChildren(
			h('span', {id: labelId, class: 'sc-pagerange-label', text: t('PAGE_RANGE')}),
			control.el,
			h('span', {class: 'sc-pagerange-hint', text: t('PAGE_RANGE_HINT')}));
		control.el.querySelector('select').setAttribute('aria-labelledby', labelId);
		control.fire();
	}

	function current() {
		const query = new URLSearchParams(location.search);
		const tab = TABS.indexOf(query.get('tab')) !== -1 ? query.get('tab') : 'general';
		return {tab: tab, id: parseInt(query.get('id'), 10) || 0};
	}

	function go(tab, id) {
		history.pushState(null, '', tabUrl(tab, id));
		render();
		root.scrollIntoView({block: 'start'});
	}

	root.addEventListener('click', (e) => {
		const link = e.target.closest('a[data-tab]');
		if (link && !e.ctrlKey && !e.metaKey && !e.shiftKey && e.button === 0) {
			e.preventDefault();
			go(link.getAttribute('data-tab'), parseInt(link.getAttribute('data-id'), 10) || 0);
		}
	});
	window.addEventListener('popstate', render);

	let rendering = 0;

	async function render() {
		const ticket = ++rendering;
		const state = current();
		disposeCharts();
		pageRange.cards.clear();
		pageRange.sync();

		nav.replaceChildren(...TABS.map((tab) => h('a', {
			class: 'button', href: tabUrl(tab, 0), 'data-tab': tab,
			'aria-current': tab === state.tab ? 'page' : null,
			text: t('TAB_' + tab.toUpperCase()),
		})));
		view.replaceChildren(message(t('LOADING')));

		try {
			const ov = await api('overview');
			if (ticket !== rendering) {
				return;
			}
			if (ov.built === false) {
				asOf.textContent = '';
				pageBar.replaceChildren();
				view.replaceChildren(message(t('NOT_BUILT')));
				return;
			}
			asOf.textContent = asOfLine(ov.meta);
			renderPageBar(ov.meta);
			view.replaceChildren();
			const renderers = {general: renderGeneral, forum: renderForum, topic: renderTopic, user: renderUser};
			await renderers[state.tab](view, ov, state.id);
		} catch (e) {
			if (ticket === rendering) {
				view.replaceChildren(failure(e));
			}
		}
	}

	render();
}());
