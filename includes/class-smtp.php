<?php

namespace JustForm;

class SMTP
{
    public function init(): void
    {
        add_action('phpmailer_init', [$this, 'configure']);
        add_filter('wp_mail_from', [$this, 'from_email']);
        add_filter('wp_mail_from_name', [$this, 'from_name']);
    }

    private function is_configured(): bool
    {
        return !empty(get_option('jf_smtp_host'));
    }

    public function configure(\PHPMailer\PHPMailer\PHPMailer $phpmailer): void
    {
        if (!$this->is_configured()) {
            return;
        }

        $phpmailer->isSMTP();
        $phpmailer->Host       = get_option('jf_smtp_host');
        $phpmailer->Port       = (int) get_option('jf_smtp_port', 587);
        $phpmailer->SMTPAuth   = true;
        $phpmailer->Username   = get_option('jf_smtp_username');
        $phpmailer->Password   = $this->decrypt(get_option('jf_smtp_password', ''));
        $phpmailer->SMTPSecure = get_option('jf_smtp_encryption', 'tls');
        $phpmailer->Timeout    = 30;
        $phpmailer->CharSet    = 'UTF-8';
    }

    public function from_email(string $email): string
    {
        $custom = get_option('jf_smtp_from_email');
        return $custom ?: $email;
    }

    public function from_name(string $name): string
    {
        $custom = get_option('jf_smtp_from_name');
        return $custom ?: $name;
    }

    public function encrypt(string $data): string
    {
        if ($data === '') {
            return '';
        }

        $key    = md5(wp_salt('auth'));
        $iv     = openssl_random_pseudo_bytes(16);
        $cipher = @openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);

        return base64_encode($iv . $cipher);
    }

    public function decrypt(string $data): string
    {
        if ($data === '') {
            return '';
        }

        $key = md5(wp_salt('auth'));
        $raw = base64_decode($data, true);

        if ($raw === false || strlen($raw) < 16) {
            return '';
        }

        $iv     = substr($raw, 0, 16);
        $cipher = substr($raw, 16);

        return @openssl_decrypt($cipher, 'aes-256-cbc', $key, 0, $iv) ?: '';
    }
}
