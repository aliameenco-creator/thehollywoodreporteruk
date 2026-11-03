/**
 * THR Audio player: play/pause, seek, ±15s, speed and voice switching.
 *
 * A voice may have a full recording, only its opening clip, or nothing yet. Picking a voice
 * without a full recording asks the site to make it (thr-audio/v1/voice) and:
 *  - at the start of the article, plays the opening clip at once and swaps to the full
 *    recording at the same moment when it is ready;
 *  - mid-article, keeps the current voice playing and switches at the same point when ready.
 * Speed and voice choices are remembered in localStorage (optional; works without it).
 */
(() => {
	const SPEEDS = [1, 1.25, 1.5, 2];
	const KEY_SPEED = 'thr_audio_speed';
	const KEY_VOICE = 'thr_audio_voice';
	const POLL_MS = 3000;
	const SLOW_MS = 30000;
	const GIVE_UP_MS = 150000;

	const store = {
		get(key) {
			try {
				return window.localStorage.getItem(key);
			} catch (e) {
				return null;
			}
		},
		set(key, value) {
			try {
				window.localStorage.setItem(key, value);
			} catch (e) {
				// Storage blocked: the setting just isn't remembered.
			}
		},
	};

	const formatTime = (seconds) => {
		const s = Math.max(0, Math.floor(seconds || 0));
		return String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0');
	};

	const readVoice = (el) => ({
		el,
		value: el.value || el.dataset.value,
		label: el.dataset.label || '',
		src: el.dataset.src || '',
		duration: Number(el.dataset.duration) || 0,
		preview: el.dataset.preview || '',
		previewDuration: Number(el.dataset.previewDuration) || 0,
		canMake: el.dataset.canMake === '1',
	});

	const initPlayer = (root) => {
		const audio = root.querySelector('[data-thr-audio-el]');
		const playBtn = root.querySelector('[data-thr-audio-play]');
		const seek = root.querySelector('[data-thr-audio-seek]');
		const current = root.querySelector('[data-thr-audio-current]');
		const total = root.querySelector('[data-thr-audio-total]');
		const length = root.querySelector('[data-thr-audio-length]');
		const speedBtn = root.querySelector('[data-thr-audio-speed]');
		const select = root.querySelector('[data-thr-audio-voice]');
		const status = root.querySelector('[data-thr-audio-status]');
		const statusText = root.querySelector('[data-thr-audio-status-text]');
		const retryBtn = root.querySelector('[data-thr-audio-retry]');
		const estimate = Number(root.dataset.estimate) || 0;

		const voices = select
			? Array.from(select.options).map(readVoice)
			: [readVoice(root.querySelector('[data-thr-audio-only]'))];

		let voice = voices[select ? select.selectedIndex : 0]; // What is loaded in the <audio>.
		let mode = voice.src ? 'full' : 'preview'; // full | preview.
		let pending = null; // { voice, timer, started, onReady, slowShown }.
		let waitingForRest = false; // Opening clip finished before the full recording arrived.
		let seeking = false;

		/* ----- Display ----- */

		const showStatus = (text, { busy = false, retry = false } = {}) => {
			statusText.textContent = text;
			status.classList.toggle('is-busy', busy);
			retryBtn.hidden = !retry;
			status.hidden = false;
		};
		const hideStatus = () => {
			status.hidden = true;
		};

		const setDuration = (seconds) => {
			if (!seconds || !isFinite(seconds)) {
				return;
			}
			seek.max = Math.floor(seconds);
			total.textContent = formatTime(seconds);
			if (length) {
				length.textContent = length.textContent.replace(/\d+/, Math.max(1, Math.round(seconds / 60)));
			}
		};

		const paintProgress = () => {
			const max = Number(seek.max) || 1;
			root.style.setProperty('--thr-audio-progress', Math.min(100, (Number(seek.value) / max) * 100) + '%');
		};

		const setPlaying = (playing) => {
			root.classList.toggle('is-playing', playing);
			playBtn.setAttribute('aria-label', playing ? playBtn.dataset.labelPause : playBtn.dataset.labelPlay);
		};

		/* ----- Speed ----- */

		let speed = Number(store.get(KEY_SPEED));
		if (!SPEEDS.includes(speed)) {
			speed = 1;
		}
		const applySpeed = () => {
			audio.defaultPlaybackRate = speed;
			audio.playbackRate = speed;
			speedBtn.textContent = speed + 'x';
		};
		applySpeed();

		speedBtn.addEventListener('click', () => {
			speed = SPEEDS[(SPEEDS.indexOf(speed) + 1) % SPEEDS.length];
			store.set(KEY_SPEED, String(speed));
			applySpeed();
		});

		/* ----- Loading files ----- */

		const play = () => {
			document.querySelectorAll('[data-thr-audio-el]').forEach((other) => other !== audio && other.pause());
			audio.play().catch(() => setPlaying(false));
		};

		// Load a file and continue from $at seconds.
		const load = (url, at, autoplay) => {
			audio.src = url;
			applySpeed();
			if (at > 0) {
				audio.addEventListener(
					'loadedmetadata',
					() => {
						audio.currentTime = Math.min(at, Math.max(0, audio.duration - 1));
						applySpeed();
					},
					{ once: true }
				);
			}
			audio.load();
			if (autoplay) {
				play();
			}
		};

		const useFull = (v, at, autoplay) => {
			voice = v;
			mode = 'full';
			waitingForRest = false;
			seek.disabled = false;
			setDuration(v.duration || estimate);
			load(v.src, at, autoplay);
		};

		const usePreview = (v, at, autoplay) => {
			voice = v;
			mode = 'preview';
			// Seeking past the opening clip is not possible until the full recording exists.
			seek.disabled = true;
			setDuration(estimate);
			load(v.preview, at, autoplay);
		};

		/* ----- Asking the site for a full recording ----- */

		const ask = (v, create) => {
			const body = new URLSearchParams({ post: root.dataset.post, voice: v.value, create: create ? '1' : '0' });
			return fetch(root.dataset.endpoint, { method: 'POST', body, credentials: 'same-origin' }).then((r) => {
				if (!r.ok) {
					throw new Error('HTTP ' + r.status);
				}
				return r.json();
			});
		};

		const stopPending = () => {
			if (pending) {
				clearTimeout(pending.timer);
				pending = null;
			}
		};

		const failPending = (message) => {
			const failed = pending;
			stopPending();
			waitingForRest = false;
			showStatus(message, { retry: true });
			retryBtn.onclick = () => {
				hideStatus();
				chooseVoice(failed.voice, true);
			};
			// The menu shows what is actually playing.
			if (select) {
				select.value = voice.value;
			}
		};

		const handle = (reply) => {
			if (!pending) {
				return;
			}
			const v = pending.voice;

			if (reply.state === 'ready') {
				v.src = reply.url;
				v.duration = Number(reply.duration) || 0;
				v.el.dataset.src = v.src;
				v.el.dataset.duration = String(v.duration);
				const done = pending.onReady;
				stopPending();
				hideStatus();
				done(v);
				return;
			}

			if (reply.state === 'busy') {
				failPending(root.dataset.msgBusy);
				return;
			}
			if (reply.state !== 'generating') {
				failPending(root.dataset.msgFailed);
				return;
			}

			const waited = Date.now() - pending.started;
			if (waited > GIVE_UP_MS) {
				failPending(root.dataset.msgFailed);
				return;
			}
			if (waited > SLOW_MS && !pending.slowShown) {
				pending.slowShown = true;
				showStatus(root.dataset.msgSlow, { busy: true });
			}
			pending.timer = setTimeout(() => {
				ask(v, false).then(handle).catch(() => failPending(root.dataset.msgFailed));
			}, POLL_MS);
		};

		// Make sure $v has a full recording, then call onReady(v).
		const ensureFull = (v, onReady, message) => {
			if (v.src) {
				onReady(v);
				return;
			}
			if (pending && pending.voice === v) {
				pending.onReady = onReady;
				return;
			}
			stopPending();
			pending = { voice: v, onReady, started: Date.now(), timer: null, slowShown: false };
			if (message) {
				showStatus(message, { busy: true });
			}
			ask(v, true)
				.then(handle)
				.catch(() => failPending(root.dataset.msgFailed));
		};

		// Full recording arrived while the opening clip is playing (or has just finished).
		const swapToFull = (v) => {
			if (voice !== v || mode !== 'preview') {
				return;
			}
			const wasPlaying = !audio.paused || waitingForRest;
			useFull(v, audio.currentTime, wasPlaying);
		};

		const preparing = (v) => root.dataset.msgPreparing.replace('%s', v.label || '');

		/* ----- Choosing a voice ----- */

		const chooseVoice = (v, userAction) => {
			if (userAction) {
				store.set(KEY_VOICE, v.value);
			}
			if (select) {
				select.value = v.value;
			}

			const wasPlaying = !audio.paused;
			const at = audio.currentTime || 0;
			const ratio = audio.duration && isFinite(audio.duration) ? at / audio.duration : 0;

			// Ready: switch straight away, at the same point in the article.
			if (v.src) {
				stopPending();
				hideStatus();
				useFull(v, mode === 'full' ? ratio * (v.duration || 0) || at : at, wasPlaying);
				return;
			}

			// Still within the opening: play this voice's clip now, swap to full when ready.
			const inOpening = v.preview && (at < 1 || (mode === 'preview' && at < (v.previewDuration || 15) - 1));
			if (inOpening) {
				usePreview(v, at, wasPlaying);
				ensureFull(v, swapToFull);
				return;
			}

			// Mid-article: keep the current voice playing and switch when the new one is ready.
			if (!v.canMake) {
				failPending(root.dataset.msgFailed);
				return;
			}
			ensureFull(
				v,
				(ready) => {
					const pos = audio.currentTime || 0;
					const r = audio.duration && isFinite(audio.duration) ? pos / audio.duration : 0;
					useFull(ready, mode === 'full' && ready.duration ? r * ready.duration : pos, !audio.paused);
				},
				preparing(v)
			);
		};

		if (select) {
			const saved = store.get(KEY_VOICE);
			const match = saved && voices.find((v) => v.value === saved && (v.src || v.preview));
			if (match && match !== voice) {
				select.value = match.value;
				voice = match;
				mode = match.src ? 'full' : 'preview';
				audio.src = match.src || match.preview;
				applySpeed();
			}
			select.addEventListener('change', () => {
				chooseVoice(voices[select.selectedIndex], true);
			});
		}

		setDuration(voice.duration || estimate);
		seek.disabled = mode === 'preview';

		/* ----- Playback ----- */

		playBtn.addEventListener('click', () => {
			if (!audio.paused) {
				audio.pause();
				return;
			}
			// Opening clip finished and the rest is on its way: it will continue by itself.
			if (waitingForRest) {
				return;
			}
			// Nothing loaded for this voice yet: make it, then play.
			if (!voice.src && !voice.preview) {
				ensureFull(voice, (v) => useFull(v, 0, true), preparing(voice));
				return;
			}
			// Playing the opening clip: start making the rest now.
			if (mode === 'preview' && voice.canMake) {
				ensureFull(voice, swapToFull);
			}
			play();
		});

		root.querySelectorAll('[data-thr-audio-skip]').forEach((btn) => {
			btn.addEventListener('click', () => {
				const end = mode === 'preview' ? audio.duration || 0 : audio.duration || Number(seek.max);
				audio.currentTime = Math.min(Math.max(0, audio.currentTime + Number(btn.dataset.thrAudioSkip)), end);
			});
		});

		seek.addEventListener('input', () => {
			seeking = true;
			current.textContent = formatTime(seek.value);
			paintProgress();
		});
		seek.addEventListener('change', () => {
			audio.currentTime = Number(seek.value);
			seeking = false;
		});

		audio.addEventListener('play', () => setPlaying(true));
		audio.addEventListener('pause', () => {
			if (!waitingForRest) {
				setPlaying(false);
			}
		});
		audio.addEventListener('ended', () => {
			// The opening clip ran out before the full recording arrived: wait, then carry on.
			if (mode === 'preview' && pending && pending.voice === voice) {
				waitingForRest = true;
				showStatus(root.dataset.msgRest, { busy: true });
				return;
			}
			setPlaying(false);
		});
		audio.addEventListener('loadedmetadata', () => {
			if (mode === 'full') {
				setDuration(audio.duration);
			}
		});
		audio.addEventListener('timeupdate', () => {
			if (seeking) {
				return;
			}
			seek.value = Math.floor(audio.currentTime);
			current.textContent = formatTime(audio.currentTime);
			paintProgress();
		});
		audio.addEventListener('error', () => {
			if (!audio.getAttribute('src')) {
				return;
			}
			setPlaying(false);
			showStatus(root.dataset.msgFailed);
		});

		paintProgress();
	};

	const init = () => document.querySelectorAll('[data-thr-audio]').forEach(initPlayer);

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
