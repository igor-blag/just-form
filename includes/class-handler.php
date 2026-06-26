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

        if (!$spam->verify_nonce($_POST['jf_nonce'] ?? '')) {
            wp_send_json_error(['message' => 'Ошибка безопасности. Обновите страницу.']);
        }

        $token     = $_POST['jf-token'] ?? '';
        $load_time = $_POST['jf_load_time'] ?? '';
        [$token_ok, $token_error] = $spam->verify_token($token, $load_time);

        if (!$token_ok) {
            wp_send_json_error(['message' => $token_error]);
        }

        if (!$spam->check_honeypot($_POST['jf-website'] ?? '')) {
            wp_send_json_error(['message' => 'Обнаружен спам']);
        }

        $name      = trim(wp_strip_all_tags($_POST['jf-name'] ?? ''));
        $contact   = trim(wp_strip_all_tags($_POST['jf-contact'] ?? ''));
        $message   = trim(wp_strip_all_tags($_POST['jf-message'] ?? ''));
        $consent   = $_POST['jf-consent'] ?? '';
        $recipient = trim($_POST['jf-recipient'] ?? '');

        $errors = [];

        if (mb_strlen($name) < 2) {
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

        if ($recipient !== '' && !is_email($recipient)) {
            $recipient = get_option('admin_email');
        } elseif ($recipient === '') {
            $recipient = get_option('admin_email');
        }

        if (!empty($errors)) {
            wp_send_json_error(['message' => implode('<br>', $errors)]);
        }

        $subject = 'Новое сообщение с сайта ' . get_bloginfo('name');

        $mail_name    = esc_html($name);
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
}
