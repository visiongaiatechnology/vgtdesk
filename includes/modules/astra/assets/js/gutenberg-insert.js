/**
 * VGTAstra — Gutenberg insert client (local-first content assist).
 */
(function (wp) {
    if (!wp || !wp.plugins || !wp.editPost || !wp.element || !wp.components || !wp.data) {
        return;
    }

    const { registerPlugin } = wp.plugins;
    const { PluginSidebar, PluginSidebarMoreMenuItem } = wp.editPost;
    const { createElement: el, useState } = wp.element;
    const { TextareaControl, Button, Notice } = wp.components;
    const { dispatch } = wp.data;
    const { __ } = wp.i18n || { __: (s) => s };

    function insertHtml(html, blocksMarkup) {
        try {
            if (blocksMarkup && dispatch('core/block-editor') && wp.blocks && wp.blocks.parse) {
                const parsed = wp.blocks.parse(blocksMarkup);
                if (parsed && parsed.length) {
                    dispatch('core/block-editor').insertBlocks(parsed);
                    return true;
                }
            }
        } catch (e) {
            /* fall through to raw HTML block */
        }
        try {
            if (wp.blocks && wp.blocks.createBlock) {
                const block = wp.blocks.createBlock('core/html', { content: html });
                dispatch('core/block-editor').insertBlocks(block);
                return true;
            }
        } catch (e2) {
            return false;
        }
        return false;
    }

    function Sidebar() {
        const [prompt, setPrompt] = useState('');
        const [busy, setBusy] = useState(false);
        const [error, setError] = useState('');
        const [ok, setOk] = useState('');

        const onGenerate = () => {
            setError('');
            setOk('');
            if (!window.vgtaGutenberg) {
                setError('Bridge config missing.');
                return;
            }
            setBusy(true);
            const body = new URLSearchParams();
            body.set('action', window.vgtaGutenberg.action);
            body.set('nonce', window.vgtaGutenberg.nonce);
            body.set('prompt', prompt);

            fetch(window.vgtaGutenberg.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                body: body.toString()
            })
                .then((r) => r.json())
                .then((json) => {
                    setBusy(false);
                    if (!json || !json.success) {
                        setError((json && json.data && json.data.message) || 'Generation failed.');
                        return;
                    }
                    const html = json.data.html || '';
                    const blocks = json.data.blocks || '';
                    if (!insertHtml(html, blocks)) {
                        setError('Could not insert into editor.');
                        return;
                    }
                    setOk(__('Content inserted into the block editor.', 'vgt-astra'));
                })
                .catch(() => {
                    setBusy(false);
                    setError('Network error.');
                });
        };

        return el(
            'div',
            { style: { padding: '12px' } },
            error ? el(Notice, { status: 'error', isDismissible: false }, error) : null,
            ok ? el(Notice, { status: 'success', isDismissible: false }, ok) : null,
            el(TextareaControl, {
                label: __('Describe content (HTML will be inserted)', 'vgt-astra'),
                value: prompt,
                onChange: setPrompt,
                rows: 6
            }),
            el(
                Button,
                {
                    isPrimary: true,
                    isBusy: busy,
                    disabled: busy || !prompt.trim(),
                    onClick: onGenerate
                },
                __('Generate & insert (local-first)', 'vgt-astra')
            ),
            el(
                'p',
                { style: { fontSize: '12px', color: '#646970', marginTop: '10px' } },
                __('No cloud required. Active tags and scripts are rejected server-side.', 'vgt-astra')
            )
        );
    }

    registerPlugin('vgta-gutenberg-insert', {
        render: function Render() {
            return el(
                wp.element.Fragment,
                null,
                el(
                    PluginSidebarMoreMenuItem,
                    { target: 'vgta-gutenberg-insert-sidebar', icon: 'superhero' },
                    __('VGTAstra Insert', 'vgt-astra')
                ),
                el(
                    PluginSidebar,
                    {
                        name: 'vgta-gutenberg-insert-sidebar',
                        title: __('VGTAstra Insert', 'vgt-astra'),
                        icon: 'superhero'
                    },
                    el(Sidebar, null)
                )
            );
        }
    });
})(window.wp);
