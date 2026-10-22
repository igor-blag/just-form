<?php

namespace JustForm;

class Block
{
    public function init(): void
    {
        add_action('init', [$this, 'register_block']);
        add_action('enqueue_block_assets', [$this, 'enqueue_frontend_assets']);
    }

    public function register_block(): void
    {
        wp_register_script(
            'just-form-block',
            JF_URL . 'assets/block.js',
            ['wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n'],
            JF_VERSION
        );

        register_block_type(JF_DIR . 'block.json', [
            'render_callback' => [$this, 'render'],
            'editor_script'   => 'just-form-block',
        ]);
    }

    public function enqueue_frontend_assets(): void
    {
        if (!is_admin()) {
            $spam = new SpamProtection();

            wp_enqueue_style('just-form', JF_URL . 'assets/form.css', [], JF_VERSION);
            wp_enqueue_script('just-form', JF_URL . 'assets/form.js', [], JF_VERSION, true);

            wp_localize_script('just-form', 'JF', [
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => $spam->generate_nonce(),
            ]);
        }
    }

    public function render(array $attributes): string
    {
        $privacy_url    = !empty($attributes['privacyUrl']) ? esc_url($attributes['privacyUrl']) : '';
        $button_text    = !empty($attributes['buttonText']) ? esc_html($attributes['buttonText']) : 'Отправить';
        $recipient = !empty($attributes['recipientEmail'])
            ? sanitize_email($attributes['recipientEmail'])
            : '';
        if (!is_email($recipient)) {
            $recipient = get_option('admin_email');
        }

        $spam = new SpamProtection();
        $token = $spam->generate_token($recipient);

        $html = '<form class="jf-form" novalidate>';

        $html .= '<input type="hidden" name="jf-token" value="' . esc_attr($token) . '">';
        $html .= '<input type="hidden" name="jf-recipient" value="' . esc_attr($recipient) . '">';

        $html .= '<div class="jf-field">
            <label for="jf-name">Имя (необязательно)</label>
            <input type="text" id="jf-name" name="jf-name" minlength="2" placeholder="Ваше имя">
        </div>';

        $html .= '<div class="jf-field">
            <label for="jf-contact">Контактные данные</label>
            <input type="text" id="jf-contact" name="jf-contact" required placeholder="Email или телефон">
        </div>';

        $html .= '<div class="jf-field">
            <label for="jf-message">Сообщение</label>
            <textarea id="jf-message" name="jf-message" required minlength="10" placeholder="Ваше сообщение" rows="5"></textarea>
        </div>';

        $html .= '<div class="jf-field jf-field-checkbox">';

        if ($privacy_url) {
            $html .= '<label>
                <input type="checkbox" name="jf-consent" required value="1">
                Я даю согласие на <a href="' . $privacy_url . '" target="_blank" rel="noopener noreferrer">обработку персональных данных</a>
            </label>';
        } else {
            $html .= '<label>
                <input type="checkbox" name="jf-consent" required value="1">
                Я даю согласие на обработку персональных данных
            </label>';
        }

        $html .= '</div>';

        $html .= '<div class="jf-honeypot" aria-hidden="true">
            <label for="jf-website">Не заполнять</label>
            <input type="text" id="jf-website" name="jf-website" tabindex="-1" autocomplete="off">
        </div>';

        $html .= '<button type="submit" class="jf-submit">' . $button_text . '</button>';

        $html .= '<div class="jf-response" aria-live="polite"></div>';

        $html .= '</form>';

        return $html;
    }
}
