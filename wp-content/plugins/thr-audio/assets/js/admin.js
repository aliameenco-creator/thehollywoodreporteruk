/**
 * Article Audio box: actions run straight away over AJAX and the box redraws itself.
 * While anything is queued or being made, the box refreshes every few seconds.
 */
(() => {
	const POLL_MS = 4000;
	let timer = null;

	const panel = () => document.querySelector('[data-thr-audio-panel]');

	const send = (data) => {
		const root = panel();
		if (!root) {
			return Promise.resolve();
		}
		const body = new URLSearchParams(
			Object.assign({ action: 'thr_audio_admin', _ajax_nonce: root.dataset.nonce, post: root.dataset.post }, data)
		);
		root.classList.add('is-loading');

		return fetch(window.ajaxurl, { method: 'POST', credentials: 'same-origin', body })
			.then((r) => r.json())
			.then((r) => {
				if (r.success) {
					root.innerHTML = r.data;
				} else {
					// eslint-disable-next-line no-alert
					window.alert(r.data || 'Something went wrong.');
				}
			})
			.catch(() => {
				// eslint-disable-next-line no-alert
				window.alert('Could not reach the server. Please try again.');
			})
			.finally(() => {
				root.classList.remove('is-loading');
				schedule();
			});
	};

	// Keep refreshing while audio is queued or being made.
	const schedule = () => {
		clearTimeout(timer);
		const busy = panel() && panel().querySelector('[data-thr-audio-busy="1"]');
		if (busy) {
			timer = setTimeout(() => send({ do: 'refresh' }), POLL_MS);
		}
	};

	const pickMp3 = (voice) => {
		if (!window.wp || !window.wp.media) {
			return;
		}
		const frame = window.wp.media({
			title: 'Choose an MP3 for this voice',
			library: { type: 'audio' },
			button: { text: 'Use this MP3' },
			multiple: false,
		});
		frame.on('select', () => {
			const file = frame.state().get('selection').first().toJSON();
			send({ do: 'own', voice, attachment: file.id });
		});
		frame.open();
	};

	document.addEventListener('click', (event) => {
		const button = event.target.closest('[data-thr-audio-panel] [data-thr-audio-action]');
		if (!button) {
			return;
		}
		event.preventDefault();

		const action = button.dataset.thrAudioAction;
		const voice = button.dataset.voice || '';

		if (action === 'own') {
			pickMp3(voice);
			return;
		}
		if (action === 'remove') {
			// eslint-disable-next-line no-alert
			if (!window.confirm('Remove ' + (button.dataset.label || 'this voice') + ' from this article? The MP3 stays in the Media Library.')) {
				return;
			}
		}
		// Audio is made from the saved article, so unsaved edits in the block editor are saved first.
		const editor = window.wp && window.wp.data && window.wp.data.select('core/editor');
		if ((action === 'generate' || action === 'publish_set') && editor && editor.isEditedPostDirty()) {
			// eslint-disable-next-line no-alert
			if (!window.confirm('The article has unsaved changes. Save it now so the audio uses the latest text?')) {
				return;
			}
			window.wp.data.dispatch('core/editor').savePost().then(() => {
				if (editor.didPostSaveRequestFail()) {
					// eslint-disable-next-line no-alert
					window.alert('The article could not be saved, so the audio was not made.');
					return;
				}
				send({ do: action, voice });
			});
			return;
		}
		send({ do: action, voice });
	});

	document.addEventListener('change', (event) => {
		const toggle = event.target.closest('[data-thr-audio-panel] [data-thr-audio-toggle]');
		if (toggle) {
			send({ do: 'toggle', voice: toggle.dataset.thrAudioToggle, on: toggle.checked ? '1' : '' });
		}
	});

	// Block editor: after a save (e.g. Publish, which starts the audio), refresh to show progress.
	if (window.wp && window.wp.data && window.wp.data.select('core/editor')) {
		let wasSaving = false;
		window.wp.data.subscribe(() => {
			const editor = window.wp.data.select('core/editor');
			const saving = editor.isSavingPost() && !editor.isAutosavingPost();
			if (wasSaving && !saving) {
				setTimeout(() => send({ do: 'refresh' }), 1500);
			}
			wasSaving = saving;
		});
	}

	const start = () => schedule();
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', start);
	} else {
		start();
	}
})();
