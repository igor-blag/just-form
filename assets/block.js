(function (wp) {
    var el = wp.element.createElement;
    var __ = wp.i18n.__;
    var useBlockProps = wp.blockEditor.useBlockProps;
    var TextControl = wp.components.TextControl;

    wp.blocks.registerBlockType('just-form/feedback-form', {
        title: __('Форма обратной связи', 'just-form'),
        description: __('Форма обратной связи с защитой от спама', 'just-form'),
        icon: 'email-alt',
        category: 'widgets',
        keywords: [__('contact', 'just-form'), __('form', 'just-form'), __('feedback', 'just-form')],
        supports: {
            align: ['wide', 'full'],
            color: { background: true, text: true },
            spacing: { padding: true },
        },
        attributes: {
            privacyUrl: { type: 'string', default: '' },
            buttonText: { type: 'string', default: 'Отправить' },
            recipientEmail: { type: 'string', default: '' },
        },

        edit: function (props) {
            var attrs = props.attributes;
            var set = props.setAttributes;
            var blockProps = useBlockProps();

            var children = [
                el('div', { className: 'jf-block-preview' },
                    el('div', { className: 'jf-block-preview-icon' }, '✉'),
                    el('h3', null, __('Форма обратной связи', 'just-form')),
                    el('p', { className: 'jf-block-fields' },
                        __('Имя · Контакты · Сообщение', 'just-form')
                    ),
                    el('span', { className: 'jf-block-btn-preview' },
                        attrs.buttonText || 'Отправить'
                    )
                )
            ];

            if (props.isSelected) {
                children.push(
                    el('div', { className: 'jf-block-settings' },
                        el('div', { className: 'jf-block-settings-title' },
                            __('Настройки формы', 'just-form')
                        ),
                        el(TextControl, {
                            label: __('Email получателя', 'just-form'),
                            help: __('По умолчанию — email администратора', 'just-form'),
                            value: attrs.recipientEmail,
                            onChange: function (v) { set({ recipientEmail: v }); },
                        }),
                        el(TextControl, {
                            label: __('Ссылка на Политику ПД', 'just-form'),
                            help: __('URL страницы с политикой обработки персональных данных', 'just-form'),
                            value: attrs.privacyUrl,
                            onChange: function (v) { set({ privacyUrl: v }); },
                        }),
                        el(TextControl, {
                            label: __('Текст кнопки', 'just-form'),
                            value: attrs.buttonText,
                            onChange: function (v) { set({ buttonText: v }); },
                        })
                    )
                );
            }

            return el('div', blockProps,
                el('div', { className: 'jf-block-wrap' }, children)
            );
        },

        save: function () {
            return null;
        },
    });
})(window.wp);
