<?php

namespace JustForm;

class Handler
{
    public function init(): void
    {
        add_action('wp_ajax_just_form_submit', [$this, 'handle']);
        add_action('wp_ajax_nopriv_just_form_submit', [$this, 'handle']);
    }

    public function handle(): void
    {
        $spam = new SpamProtection();

        if (!$spam->verify_nonce($this->post_string('jf_nonce'))) {
            wp_send_json_error(['message' => 'Ошибка безопасности. Обновите страницу.']);
        }

        $recipient = trim($this->post_string('jf-recipient'));

        if (!$spam->check_honeypot($this->post_string('jf-website'))) {
            wp_send_json_error(['message' => 'Обнаружен спам']);
        }

        $name    = trim(wp_strip_all_tags($this->post_string('jf-name')));
        $contact = trim(wp_strip_all_tags($this->post_string('jf-contact')));
        $message = trim(wp_strip_all_tags($this->post_string('jf-message')));
        $consent = $this->post_string('jf-consent');

        $errors = [];

        if ($name !== '' && mb_strlen($name) < 2) {
            $errors[] = 'Имя должно содержать минимум 2 символа';
        }

        if (!preg_match('/^[+\d][\d\s\-().]{7,20}$/', $contact) && !is_email($contact)) {
            $errors[] = 'Укажите корректный email или номер телефона';
        }

        if (mb_strlen($message) < 10) {
            $errors[] = 'Сообщение должно содержать минимум 10 символов';
        }

        if ($consent !== '1') {
            $errors[] = 'Необходимо дать согласие на обработку персональных данных';
        }

        if (!is_email($recipient)) {
            wp_send_json_error(['message' => 'Не удалось определить адрес получателя. Обновите страницу.']);
        }

        if (!empty($errors)) {
            wp_send_json_error(['message' => implode('. ', $errors)]);
        }

        $token     = $this->post_string('jf-token');
        $load_time = $this->post_string('jf_load_time');
        [$token_ok, $token_error] = $spam->verify_token($token, $load_time, $recipient);

        if (!$token_ok) {
            wp_send_json_error(['message' => $token_error]);
        }

        $ip = isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '';
        if (!$spam->check_rate_limit($ip)) {
            wp_send_json_error(['message' => 'Слишком много отправок. Попробуйте через 10 минут.']);
        }

        $subject = 'Новое сообщение с сайта ' . get_bloginfo('name');

        $mail_name    = esc_html($name !== '' ? $name : 'Не указано');
        $mail_contact = esc_html($contact);

        $email_body = "
            <h2>Новое сообщение с формы обратной связи</h2>
            <table>
                <tr><td><strong>Имя:</strong></td><td>{$mail_name}</td></tr>
                <tr><td><strong>Контакты:</strong></td><td>{$mail_contact}</td></tr>
                <tr><td><strong>Сообщение:</strong></td>
                    <td>" . nl2br(esc_html($message)) . "</td></tr>
                <tr><td><strong>Согласие на ПД:</strong></td><td>Да</td></tr>
                <tr><td><strong>IP:</strong></td><td>" . esc_html($_SERVER['REMOTE_ADDR'] ?? '') . "</td></tr>
                <tr><td><strong>Дата:</strong></td><td>" . wp_date('d.m.Y H:i') . "</td></tr>
            </table>";

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_option('admin_email') . '>',
        ];

        if (is_email($contact)) {
            $headers[] = 'Reply-To: ' . $contact;
        }

        $sent = wp_mail($recipient, $subject, $email_body, $headers);

        if ($sent) {
            wp_send_json_success(['message' => 'Сообщение отправлено. Мы свяжемся с вами в ближайшее время.']);
        } else {
            wp_send_json_error(['message' => 'Ошибка отправки. Попробуйте позже или свяжитесь с нами по телефону.']);
        }
    }

    private function post_string(string $key): string
    {
        $value = $_POST[$key] ?? '';
        return is_string($value) ? wp_unslash($value) : '';
    }
}
