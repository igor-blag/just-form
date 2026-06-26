<?php

namespace JustForm;

class Admin
{
    private SMTP $smtp;

    public function __construct()
    {
        $this->smtp = new SMTP();
    }

    public function init(): void
    {
        add_action('admin_menu', [$this, 'add_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_filter('plugin_action_links_' . plugin_basename(JF_DIR . 'just-form.php'), [$this, 'action_links']);
    }

    public function action_links(array $links): array
    {
        $links[] = '<a href="' . admin_url('options-general.php?page=just-form') . '">Настройки SMTP</a>';
        return $links;
    }

    public function add_menu(): void
    {
        add_options_page(
            'Just Form — Настройки SMTP',
            'Just Form',
            'manage_options',
            'just-form',
            [$this, 'page']
        );
    }

    public function register_settings(): void
    {
        register_setting('jf_smtp', 'jf_smtp_host');
        register_setting('jf_smtp', 'jf_smtp_port', ['type' => 'integer', 'default' => 587]);
        register_setting('jf_smtp', 'jf_smtp_username');
        register_setting('jf_smtp', 'jf_smtp_password');
        register_setting('jf_smtp', 'jf_smtp_encryption', ['default' => 'tls']);
        register_setting('jf_smtp', 'jf_smtp_from_email');
        register_setting('jf_smtp', 'jf_smtp_from_name');

        add_action('pre_update_option_jf_smtp_password', function ($value) {
            return $value === '' ? get_option('jf_smtp_password') : $this->smtp->encrypt($value);
        });
    }

    public function page(): void
    {
        if (!current_user_can('manage_options')) {
            return;
        }
        ?>
        <div class="wrap">
            <h1>Just Form — Настройки SMTP</h1>
            <p>Настройте SMTP-сервер для отправки писем с формы обратной связи.</p>

            <form method="post" action="options.php">
                <?php settings_fields('jf_smtp'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="jf_smtp_host">SMTP-сервер</label></th>
                        <td>
                            <input type="text" id="jf_smtp_host" name="jf_smtp_host"
                                   value="<?php echo esc_attr(get_option('jf_smtp_host')); ?>"
                                   class="regular-text" placeholder="smtp.example.com">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="jf_smtp_port">Порт</label></th>
                        <td>
                            <input type="number" id="jf_smtp_port" name="jf_smtp_port"
                                   value="<?php echo esc_attr(get_option('jf_smtp_port', 587)); ?>"
                                   class="small-text" min="1" max="65535">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="jf_smtp_username">Имя пользователя</label></th>
                        <td>
                            <input type="text" id="jf_smtp_username" name="jf_smtp_username"
                                   value="<?php echo esc_attr(get_option('jf_smtp_username')); ?>"
                                   class="regular-text">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="jf_smtp_password">Пароль</label></th>
                        <td>
                            <input type="password" id="jf_smtp_password" name="jf_smtp_password"
                                   value="" class="regular-text"
                                   placeholder="Введите для изменения">
                            <p class="description">Пароль хранится в зашифрованном виде. Оставьте пустым, чтобы не менять.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="jf_smtp_encryption">Шифрование</label></th>
                        <td>
                            <select id="jf_smtp_encryption" name="jf_smtp_encryption">
                                <option value="tls" <?php selected(get_option('jf_smtp_encryption'), 'tls'); ?>>TLS</option>
                                <option value="ssl" <?php selected(get_option('jf_smtp_encryption'), 'ssl'); ?>>SSL</option>
                                <option value="" <?php selected(get_option('jf_smtp_encryption'), ''); ?>>Нет</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="jf_smtp_from_email">From Email</label></th>
                        <td>
                            <input type="email" id="jf_smtp_from_email" name="jf_smtp_from_email"
                                   value="<?php echo esc_attr(get_option('jf_smtp_from_email')); ?>"
                                   class="regular-text" placeholder="<?php echo esc_attr(get_option('admin_email')); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="jf_smtp_from_name">From Name</label></th>
                        <td>
                            <input type="text" id="jf_smtp_from_name" name="jf_smtp_from_name"
                                   value="<?php echo esc_attr(get_option('jf_smtp_from_name')); ?>"
                                   class="regular-text" placeholder="<?php echo esc_attr(get_bloginfo('name')); ?>">
                        </td>
                    </tr>
                </table>
                <?php submit_button('Сохранить настройки'); ?>
            </form>
        </div>
        <?php
    }
}
