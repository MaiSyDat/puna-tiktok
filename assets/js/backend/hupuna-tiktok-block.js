(function (wp) {
	const { registerBlockType } = wp.blocks;
	const { __ } = wp.i18n;
	const { useState, createElement: el } = wp.element;
	const { dispatch } = wp.data;
	const { MediaUpload, MediaUploadCheck, BlockIcon } = wp.blockEditor || wp.editor;
	const { Button, Placeholder } = wp.components;

	const editorStore = dispatch('core/editor');

	registerBlockType('puna/hupuna-tiktok', {
		title: __('HUPUNA TIKTOK', 'puna-tiktok'),
		description: __('Tải lên và xem trước video cho bài đăng kiểu TikTok.', 'puna-tiktok'),
		icon: 'video-alt3',
		category: 'media',
		keywords: [ __('tiktok', 'puna-tiktok'), __('hupuna', 'puna-tiktok'), __('video', 'puna-tiktok') ],
		supports: { html: false, reusable: false },
		attributes: { videoId: { type: 'number' }, videoUrl: { type: 'string' } },
		edit: (props) => {
			const { attributes, setAttributes, className } = props;
			const [isReplacing, setIsReplacing] = useState(false);

			const onSelectVideo = (media) => {
				if (!media || !media.id) return;
				setAttributes({ videoId: media.id, videoUrl: media.url });
				if (editorStore && editorStore.editPost) {
					editorStore.editPost({ meta: { _puna_tiktok_video_file_id: media.id } });
				}
			};

			if (attributes.videoUrl) {
				return el('div', { className }, [
					el('video', { controls: true, style: { width: '100%', height: 'auto' }, src: attributes.videoUrl }),
					el('div', { style: { marginTop: 8 } }, [
						el(MediaUploadCheck, null,
							el(MediaUpload, {
								onSelect: onSelectVideo,
								allowedTypes: ['video'],
								value: attributes.videoId,
								render: ({ open }) => el(Button, { onClick: open, isSecondary: true }, __('Thay video', 'puna-tiktok'))
							})
						),
						el(Button, {
							isLink: true,
							isDestructive: true,
							onClick: () => {
								setAttributes({ videoId: undefined, videoUrl: undefined });
								if (editorStore && editorStore.editPost) {
									editorStore.editPost({ meta: { _puna_tiktok_video_file_id: null } });
								}
							},
							style: { marginLeft: 8 }
						}, __('Xóa', 'puna-tiktok'))
					])
				]);
			}

			return el(Placeholder, {
				icon: el(BlockIcon, { icon: 'video-alt3' }),
				label: __('Hupuna Tiktok', 'puna-tiktok'),
				instructions: __('Tải lên một tệp video để hiển thị.', 'puna-tiktok')
			},
				el(MediaUploadCheck, null,
					el(MediaUpload, {
						onSelect: onSelectVideo,
						allowedTypes: ['video'],
						render: ({ open }) => el(Button, { variant: 'primary', onClick: open }, __('Tải video lên', 'puna-tiktok'))
					})
				)
			);
		},
		save: () => null,
	});
})(window.wp);
