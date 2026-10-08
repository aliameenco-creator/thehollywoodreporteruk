/**
 * THR story sidebar for the block editor: a publish checklist, the story
 * settings, and review details (only for reviews). Plain wp.* globals, no build step.
 */
(function (wp) {
	const { createElement: el, Fragment } = wp.element;
	const { registerPlugin } = wp.plugins;
	const { useSelect, useDispatch } = wp.data;
	const { TextControl, TextareaControl, SelectControl, ToggleControl } = wp.components;
	const PluginDocumentSettingPanel = (wp.editor && wp.editor.PluginDocumentSettingPanel) || wp.editPost.PluginDocumentSettingPanel;
	const { __ } = wp.i18n;

	const POST_TYPES = ['post', 'thr_list', 'thr_gallery', 'thr_video'];
	const DEK_MAX = 160;

	const STORY_TYPES = [
		{ value: 'standard', label: __('Standard news story', 'thr-core') },
		{ value: 'review', label: __('Review (adds the review box)', 'thr-core') },
		{ value: 'feature', label: __('Feature', 'thr-core') },
		{ value: 'cover-story', label: __('Cover story', 'thr-core') },
		{ value: 'interview', label: __('Interview / Q&A', 'thr-core') },
		{ value: 'podcast', label: __('Podcast', 'thr-core') },
	];

	const REVIEW_FIELDS = [
		['thr_review_subject', __('Title of the film / show / album', 'thr-core'), __('e.g. Northern Line', 'thr-core')],
		['thr_review_bottom_line', __('The bottom line (one sentence)', 'thr-core'), __('e.g. A quick, warm comedy with a terrific ensemble.', 'thr-core')],
		['thr_review_director', __('Director', 'thr-core'), ''],
		['thr_review_screenwriter', __('Writer', 'thr-core'), ''],
		['thr_review_cast', __('Cast', 'thr-core'), __('Names separated by commas', 'thr-core')],
		['thr_review_venue', __('Where to watch / release date', 'thr-core'), __('e.g. Halcyon TV (Thursdays)', 'thr-core')],
		['thr_review_rating_time', __('Rating & running time', 'thr-core'), __('e.g. 15, 1 hour 58 minutes', 'thr-core')],
	];

	function useStory() {
		const data = useSelect((select) => {
			const editor = select('core/editor');
			const core = select('core');
			const imageId = editor.getEditedPostAttribute('featured_media');
			const image = imageId ? core.getMedia(imageId) : null;
			return {
				postType: editor.getCurrentPostType(),
				meta: editor.getEditedPostAttribute('meta') || {},
				title: editor.getEditedPostAttribute('title') || '',
				categories: editor.getEditedPostAttribute('categories') || [],
				tags: editor.getEditedPostAttribute('tags') || [],
				imageId,
				imageCredit: image ? image.thr_credit : null,
			};
		}, []);
		const { editPost } = useDispatch('core/editor');
		const setMeta = (key, value) => editPost({ meta: { [key]: value } });
		return { ...data, setMeta };
	}

	function Check({ done, label, hint }) {
		return el('li', { className: 'thr-check' + (done ? ' is-done' : '') },
			el('span', { className: 'thr-check__mark', 'aria-hidden': true }, done ? '✓' : '○'),
			el('span', null, label, !done && hint ? el('small', null, hint) : null)
		);
	}

	function ChecklistPanel() {
		const s = useStory();
		if (!POST_TYPES.includes(s.postType)) return null;
		const isReview = s.meta.thr_article_type === 'review';
		const items = [
			[s.title.trim().length > 10, __('Headline', 'thr-core'), __('Write the headline above.', 'thr-core')],
			[(s.meta.thr_dek || '').trim().length > 0, __('Dek (summary line)', 'thr-core'), __('Shown under the headline, in cards and in Google.', 'thr-core')],
			[s.postType !== 'post' || s.categories.length > 0, __('Section chosen', 'thr-core'), __('Pick one subsection, e.g. Movie News.', 'thr-core')],
			[s.tags.length > 0, __('At least one topic', 'thr-core'), __('Topics power "Read More About" and related stories.', 'thr-core')],
			[!!s.imageId, __('Featured image', 'thr-core'), __('Needed for the homepage and social sharing.', 'thr-core')],
			[!s.imageId || !!s.imageCredit, __('Image credit', 'thr-core'), __('Add a credit to the image in the Media Library.', 'thr-core')],
		];
		if (isReview) {
			items.push([!!s.meta.thr_review_subject && !!s.meta.thr_review_bottom_line, __('Review box filled in', 'thr-core'), __('Title and bottom line, in Review Details below.', 'thr-core')]);
		}
		const done = items.filter((i) => i[0]).length;

		return el(PluginDocumentSettingPanel, {
			name: 'thr-checklist',
			title: __('Story checklist', 'thr-core') + ' (' + done + '/' + items.length + ')',
			className: 'thr-checklist-panel',
			initialOpen: true,
		}, el('ul', { className: 'thr-checklist' }, items.map(([ok, label, hint]) => el(Check, { key: label, done: ok, label, hint }))));
	}

	function SettingsPanel() {
		const s = useStory();
		if (!POST_TYPES.includes(s.postType)) return null;
		const dek = s.meta.thr_dek || '';
		const type = s.meta.thr_article_type || 'standard';

		return el(PluginDocumentSettingPanel, { name: 'thr-story', title: __('Story settings', 'thr-core'), initialOpen: true },
			s.postType === 'post' && el(SelectControl, {
				label: __('Story type', 'thr-core'),
				value: type,
				options: STORY_TYPES,
				onChange: (v) => s.setMeta('thr_article_type', v),
				__nextHasNoMarginBottom: true,
			}),
			el(TextareaControl, {
				label: __('Dek (one-line summary)', 'thr-core'),
				help: dek.length + ' / ' + DEK_MAX + ' ' + __('characters. Also used as the Google description.', 'thr-core'),
				value: dek,
				rows: 3,
				onChange: (v) => s.setMeta('thr_dek', v),
				__nextHasNoMarginBottom: true,
			}),
			el(TextControl, {
				label: __('Label above headline (optional)', 'thr-core'),
				help: __('e.g. EXCLUSIVE. Leave blank to show the section name.', 'thr-core'),
				value: s.meta.thr_kicker || '',
				onChange: (v) => s.setMeta('thr_kicker', v),
				__nextHasNoMarginBottom: true,
			}),
			el(ToggleControl, {
				label: __('Top story on the homepage', 'thr-core'),
				help: __('The newest story with this on leads the homepage.', 'thr-core'),
				checked: !!s.meta.thr_featured,
				onChange: (v) => s.setMeta('thr_featured', v),
				__nextHasNoMarginBottom: true,
			}),
			el(ToggleControl, {
				label: __('Show in the red Breaking News bar', 'thr-core'),
				help: __('Turn off when the story is no longer breaking.', 'thr-core'),
				checked: !!s.meta.thr_breaking,
				onChange: (v) => s.setMeta('thr_breaking', v),
				__nextHasNoMarginBottom: true,
			}),
			(s.postType === 'thr_video' || type === 'podcast') && el(TextControl, {
				label: s.postType === 'thr_video' ? __('YouTube link', 'thr-core') : __('Podcast / audio link', 'thr-core'),
				type: 'url',
				value: s.meta.thr_media_url || '',
				onChange: (v) => s.setMeta('thr_media_url', v),
				__nextHasNoMarginBottom: true,
			})
		);
	}

	function ReviewPanel() {
		const s = useStory();
		if (s.postType !== 'post' || s.meta.thr_article_type !== 'review') return null;

		return el(PluginDocumentSettingPanel, { name: 'thr-review', title: __('Review details', 'thr-core'), initialOpen: true },
			REVIEW_FIELDS.map(([key, label, placeholder]) => el(TextControl, {
				key,
				label,
				placeholder,
				value: s.meta[key] || '',
				onChange: (v) => s.setMeta(key, v),
				__nextHasNoMarginBottom: true,
			})),
			el(TextareaControl, {
				label: __('Full credits (optional)', 'thr-core'),
				value: s.meta.thr_review_full_credits || '',
				rows: 4,
				onChange: (v) => s.setMeta('thr_review_full_credits', v),
				__nextHasNoMarginBottom: true,
			})
		);
	}

	registerPlugin('thr-story-sidebar', {
		render: () => el(Fragment, null, el(ChecklistPanel), el(SettingsPanel), el(ReviewPanel)),
	});
})(window.wp);
