<?php

namespace JustForm;

class SpamProtection
{
    private string $secret;

    public function __construct()
    {
        $this->secret = wp_salt('nonce') . '::just-form';
    }

    public function generate_nonce(): string
    {
        return wp_create_nonce('jf_submit');
    }

    public function verify_nonce(string $nonce): bool
    {
        return (bool) wp_verify_nonce($nonce, 'jf_submit');
    }

    public function generate_token(): string
    {
        $nonce     = wp_generate_password(16, false);
        $timestamp = time();
        $payload   = $nonce . '|' . $timestamp;
        $sig       = hash_hmac('sha256', $payload, $this->secret);

        set_transient('jf_nonce_' . $nonce, true, 30 * MINUTE_IN_SECONDS);

        return $payload . '|' . $sig;
    }

    public function verify_token(string $token, string $load_time): array
    {
        $parts = explode('|', $token);
        if (count($parts) !== 3) {
            return [false, 'Недействительный токен'];
        }

        [$nonce, $timestamp, $sig] = $parts;

        if (!hash_equals(hash_hmac('sha256', $nonce . '|' . $timestamp, $this->secret), $sig)) {
            return [false, 'Недействительная подпись'];
        }

        if (!get_transient('jf_nonce_' . $nonce)) {
            return [false, 'Токен уже использован или истёк'];
        }

        delete_transient('jf_nonce_' . $nonce);

        $elapsed = time() - (int) $timestamp;
        if ($elapsed < 2) {
            return [false, 'Слишком быстро — похоже на бота'];
        }

        if ($elapsed > 30 * 60) {
            return [false, 'Время ожидания истекло, обновите страницу'];
        }

        if ($load_time) {
            $form_age = time() - ((int) $load_time / 1000);
            if ($form_age < 2) {
                return [false, 'Форма загружена слишком недавно'];
            }
        }

        return [true, ''];
    }

    public function check_honeypot(string $value): bool
    {
        return $value === '';
    }
}
