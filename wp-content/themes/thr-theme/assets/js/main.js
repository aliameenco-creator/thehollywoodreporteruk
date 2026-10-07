/**
 * The Hollywood Reporter UK - Main Vanilla JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
	initBreakingBar();
	initMegaMenu();
	initLiveSearch();
	initSubnav();
	initVideoFacade();
	initViewBeacon();
});

function storageGet(key) {
	try {
		return window.localStorage.getItem(key);
	} catch (e) {
		return null;
	}
}

function storageSet(key, value) {
	try {
		window.localStorage.setItem(key, value);
	} catch (e) {
		// Storage unavailable (private mode); dismissal just won't persist.
	}
}

function initBreakingBar() {
	const bar = document.querySelector('.thr-breaking-bar');
	if (!bar) return;

	const key = 'thr_dismissed_' + (bar.getAttribute('data-story-id') || 'default');
	if (storageGet(key)) {
		bar.hidden = true;
		return;
	}

	const closeBtn = bar.querySelector('.thr-breaking-bar__close');
	if (closeBtn) {
		closeBtn.addEventListener('click', () => {
			bar.hidden = true;
			storageSet(key, '1');
		});
	}
}

function initMegaMenu() {
	const menu = document.getElementById('thrMegaMenu');
	if (!menu) return;

	const openers = document.querySelectorAll('.js-thr-menu-open');
	const closers = menu.querySelectorAll('.js-thr-menu-close');
	const searchInput = menu.querySelector('.js-thr-search-input');
	let lastTrigger = null;

	const setExpanded = (value) => {
		openers.forEach((btn) => btn.setAttribute('aria-expanded', value ? 'true' : 'false'));
	};

	const open = (trigger) => {
		lastTrigger = trigger;
		menu.hidden = false;
		document.body.classList.add('thr-menu-open');
		setExpanded(true);
		// Next frame so the transition runs from the hidden state.
		requestAnimationFrame(() => {
			menu.classList.add('is-open');
			const focusSearch = trigger && trigger.hasAttribute('data-thr-focus-search');
			const target = focusSearch && searchInput ? searchInput : menu.querySelector('.thr-mega__close');
			if (target) target.focus();
		});
	};

	const close = () => {
		menu.classList.remove('is-open');
		document.body.classList.remove('thr-menu-open');
		setExpanded(false);
		window.setTimeout(() => {
			menu.hidden = true;
		}, 250);
		if (lastTrigger) lastTrigger.focus();
	};

	openers.forEach((btn) => btn.addEventListener('click', () => open(btn)));
	closers.forEach((el) => el.addEventListener('click', close));

	document.addEventListener('keydown', (e) => {
		if (e.key === 'Escape' && !menu.hidden) close();
	});

	// Mobile accordion: one column open at a time.
	menu.querySelectorAll('.js-thr-accordion').forEach((btn) => {
		btn.addEventListener('click', () => {
			const col = btn.closest('.thr-mega__col');
			const willOpen = !col.classList.contains('is-open');
			menu.querySelectorAll('.thr-mega__col.is-open').forEach((other) => {
				other.classList.remove('is-open');
				const otherBtn = other.querySelector('.js-thr-accordion');
				if (otherBtn) otherBtn.setAttribute('aria-expanded', 'false');
			});
			col.classList.toggle('is-open', willOpen);
			btn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
		});
	});
}

function initLiveSearch() {
	const input = document.querySelector('.js-thr-search-input');
	const box = document.querySelector('.js-thr-search-results');
	if (!input || !box) return;

	const settings = window.thrSettings || {};
	const endpoint = settings.searchApi || '/wp-json/thr/v1/search';
	const form = input.form;
	let timer;
	let controller;

	const el = (tag, className, text) => {
		const node = document.createElement(tag);
		if (className) node.className = className;
		if (text) node.textContent = text;
		return node;
	};

	const render = (query, results) => {
		box.replaceChildren();
		if (!results.length) {
			box.appendChild(el('div', 'thr-search-empty', settings.noResults || 'No matching stories found'));
			return;
		}
		results.forEach((item) => {
			const link = el('a', 'thr-search-item');
			link.href = item.url;
			if (item.thumbnail) {
				const img = el('img');
				img.src = item.thumbnail;
				img.alt = '';
				img.loading = 'lazy';
				link.appendChild(img);
			}
			const text = el('div');
			// Titles arrive HTML-encoded (e.g. &#8217;); decode safely via a detached textarea.
			const decoder = document.createElement('textarea');
			decoder.innerHTML = item.title;
			text.appendChild(el('div', 'thr-search-item__title', decoder.value));
			text.appendChild(el('div', 'thr-search-item__date', item.date));
			link.appendChild(text);
			box.appendChild(link);
		});
		if (form) {
			const all = el('a', 'thr-search-all', 'See all results →');
			all.href = form.action + '?q=' + encodeURIComponent(query);
			box.appendChild(all);
		}
	};

	input.addEventListener('input', () => {
		clearTimeout(timer);
		const query = input.value.trim();
		if (query.length < 2) {
			box.replaceChildren();
			return;
		}
		timer = setTimeout(() => {
			if (controller) controller.abort();
			controller = new AbortController();
			fetch(endpoint + '?q=' + encodeURIComponent(query), { signal: controller.signal })
				.then((res) => res.json())
				.then((data) => render(query, (data && data.results) || []))
				.catch(() => {});
		}, 250);
	});
}

function initSubnav() {
	// On narrow screens the subsection row scrolls sideways; keep the current one visible.
	const nav = document.querySelector('.thr-subnav');
	const active = nav && nav.querySelector('.is-active');
	if (!active || nav.scrollWidth <= nav.clientWidth) return;
	nav.scrollLeft = Math.max(0, active.offsetLeft - 20);
}

function initVideoFacade() {
	document.querySelectorAll('.thr-video-facade').forEach((facade) => {
		facade.addEventListener('click', function () {
			const videoId = this.getAttribute('data-youtube-id');
			if (!videoId) return;

			const iframe = document.createElement('iframe');
			iframe.src = 'https://www.youtube-nocookie.com/embed/' + encodeURIComponent(videoId) + '?autoplay=1&rel=0';
			iframe.title = 'YouTube video player';
			iframe.allow = 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture';
			iframe.allowFullscreen = true;
			iframe.className = 'thr-video-facade__iframe';

			this.replaceChildren(iframe);
		});
	});
}

function initViewBeacon() {
	const article = document.querySelector('article[data-post-id]');
	if (!article) return;

	const postId = parseInt(article.getAttribute('data-post-id'), 10);
	if (!postId) return;

	const beaconUrl = (window.thrSettings && window.thrSettings.trackApi) || '/wp-json/thr/v1/track-view';
	const payload = JSON.stringify({ post_id: postId });

	if (navigator.sendBeacon) {
		navigator.sendBeacon(beaconUrl, new Blob([payload], { type: 'application/json' }));
	} else {
		fetch(beaconUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: payload,
			keepalive: true,
		}).catch(() => {});
	}
}
