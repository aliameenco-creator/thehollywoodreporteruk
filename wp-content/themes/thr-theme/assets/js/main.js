/**
 * The Hollywood Reporter UK - Main Vanilla JavaScript
 */

document.addEventListener('DOMContentLoaded', () => {
	initBreakingBar();
	initMenuDrawer();
	initSearchExpand();
	initVideoFacade();
	initViewBeacon();
});

function initBreakingBar() {
	const bar = document.querySelector('.thr-breaking-bar');
	if (!bar) return;

	const closeBtn = bar.querySelector('.thr-breaking-bar__close');
	const storyId = bar.getAttribute('data-story-id') || 'breaking-default';

	if (localStorage.getItem('thr_dismissed_' + storyId)) {
		bar.style.display = 'none';
		return;
	}

	if (closeBtn) {
		closeBtn.addEventListener('click', () => {
			bar.style.display = 'none';
			localStorage.setItem('thr_dismissed_' + storyId, 'true');
		});
	}
}

function initMenuDrawer() {
	const trigger = document.querySelector('.thr-header__menu-btn');
	const drawer = document.querySelector('.thr-mega-drawer');
	const closeBtn = document.querySelector('.thr-mega-drawer__close');

	if (!trigger || !drawer) return;

	const openDrawer = () => {
		drawer.classList.add('is-active');
		drawer.setAttribute('aria-hidden', 'false');
		trigger.setAttribute('aria-expanded', 'true');
		document.body.style.overflow = 'hidden';
	};

	const closeDrawer = () => {
		drawer.classList.remove('is-active');
		drawer.setAttribute('aria-hidden', 'true');
		trigger.setAttribute('aria-expanded', 'false');
		document.body.style.overflow = '';
	};

	trigger.addEventListener('click', openDrawer);
	if (closeBtn) closeBtn.addEventListener('click', closeDrawer);

	document.addEventListener('keydown', (e) => {
		if (e.key === 'Escape' && drawer.classList.contains('is-active')) {
			closeDrawer();
		}
	});
}

function initSearchExpand() {
	const toggle = document.querySelector('.thr-search-toggle');
	const container = document.querySelector('.thr-search-bar');
	const input = document.querySelector('.thr-search-bar__input');
	const resultsBox = document.querySelector('.thr-search-autocomplete-results');

	if (!toggle || !container || !input) return;

	toggle.addEventListener('click', () => {
		const isExpanded = container.classList.toggle('is-open');
		toggle.setAttribute('aria-expanded', isExpanded);
		if (isExpanded) {
			input.focus();
		}
	});

	let debounceTimer;
	input.addEventListener('input', (e) => {
		clearTimeout(debounceTimer);
		const query = e.target.value.trim();

		if (query.length < 2) {
			if (resultsBox) resultsBox.innerHTML = '';
			return;
		}

		debounceTimer = setTimeout(() => {
			const apiEndpoint = window.thrSettings?.searchApi || '/wp-json/thr/v1/search';
			fetch(`${apiEndpoint}?q=${encodeURIComponent(query)}`)
				.then((res) => res.json())
				.then((data) => {
					if (!resultsBox) return;
					if (data.results && data.results.length > 0) {
						resultsBox.innerHTML = data.results
							.map(
								(item) => `
							<a href="${item.url}" class="thr-search-item">
								${item.thumbnail ? `<img src="${item.thumbnail}" alt="" />` : ''}
								<div>
									<div class="thr-search-item__title">${item.title}</div>
									<div class="thr-search-item__date">${item.date}</div>
								</div>
							</a>
						`
							)
							.join('');
					} else {
						resultsBox.innerHTML = '<div class="thr-search-empty">No matching stories found</div>';
					}
				})
				.catch((err) => console.error(err));
		}, 250);
	});
}

function initVideoFacade() {
	document.querySelectorAll('.thr-video-facade').forEach((facade) => {
		facade.addEventListener('click', function () {
			const videoId = this.getAttribute('data-youtube-id');
			if (!videoId) return;

			const iframe = document.createElement('iframe');
			iframe.setAttribute('src', `https://www.youtube-nocookie.com/embed/${videoId}?autoplay=1&rel=0`);
			iframe.setAttribute('frameborder', '0');
			iframe.setAttribute('allow', 'accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture');
			iframe.setAttribute('allowfullscreen', 'true');
			iframe.style.width = '100%';
			iframe.style.height = '100%';
			iframe.style.position = 'absolute';
			iframe.style.top = '0';
			iframe.style.left = '0';

			this.innerHTML = '';
			this.appendChild(iframe);
		});
	});
}

function initViewBeacon() {
	const article = document.querySelector('article[data-post-id]');
	if (!article) return;

	const postId = article.getAttribute('data-post-id');
	if (!postId) return;

	const beaconUrl = window.thrSettings?.trackApi || '/wp-json/thr/v1/track-view';
	const payload = JSON.stringify({ post_id: parseInt(postId, 10) });

	if (navigator.sendBeacon) {
		const blob = new Blob([payload], { type: 'application/json' });
		navigator.sendBeacon(beaconUrl, blob);
	} else {
		fetch(beaconUrl, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: payload,
		}).catch(() => {});
	}
}
