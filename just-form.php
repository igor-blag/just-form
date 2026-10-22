<?php
/**
 * Plugin Name: Just Form
 * Description: Форма обратной связи для Gutenberg с многоуровневой защитой от спама и SMTP
 * Version: 1.0.1
 * Requires at least: 6.1
 * Requires PHP: 7.4
 * Text Domain: just-form
 */

defined('ABSPATH') || exit;

define('JF_VERSION', '1.0.1');
define('JF_DIR', plugin_dir_path(__FILE__));
define('JF_URL', plugin_dir_url(__FILE__));

spl_autoload_register(function ($class) {
    $prefix = 'JustForm\\';
    $base = JF_DIR . 'includes/';

    if (strncmp($prefix, $class, strlen($prefix)) !== 0) {
        return;
    }

    $relative_class = substr($class, strlen($prefix));
    $kebab = strtolower(preg_replace('/([a-z])([A-Z])/', '$1-$2', $relative_class));
    $file = $base . 'class-' . $kebab . '.php';

    if (file_exists($file)) {
        require $file;
    }
});

require_once JF_DIR . 'admin/class-admin.php';

add_action('plugins_loaded', function () {
    (new JustForm\Block())->init();
    (new JustForm\Handler())->init();
    (new JustForm\SMTP())->init();

    if (is_admin()) {
        (new JustForm\Admin())->init();
    }
});
