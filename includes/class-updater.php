<?php

namespace JustForm;

class Updater
{
    private const REPOSITORY = 'igor-blag/just-form';
    private const UPDATE_URI = 'https://github.com/igor-blag/just-form';
    private const RELEASE_TRANSIENT = 'jf_github_latest_release';

    public function init(): void
    {
        add_filter('update_plugins_github.com', [$this, 'filter_update'], 10, 4);
    }

    public function filter_update($update, array $plugin_data, string $plugin_file, array $locales)
    {
        if ($plugin_file !== plugin_basename(JF_DIR . 'just-form.php')) {
            return $update;
        }

        $release = $this->get_latest_release();
        $installed_version = $plugin_data['Version'] ?? JF_VERSION;

        if (!$release || version_compare($release['version'], $installed_version, '<=')) {
            return false;
        }

        return [
            'id'          => self::UPDATE_URI,
            'slug'        => 'just-form',
            'version'     => $release['version'],
            'url'         => $release['url'],
            'package'     => $release['package'],
            'requires'    => '6.1',
            'requires_php'=> '7.4',
        ];
    }

    private function get_latest_release(): ?array
    {
        $cached = get_transient(self::RELEASE_TRANSIENT);
        if (is_array($cached)) {
            return !empty($cached['available']) ? $cached : null;
        }

        $response = wp_remote_get(
            'https://api.github.com/repos/' . self::REPOSITORY . '/releases/latest',
            [
                'timeout' => 5,
                'headers' => [
                    'Accept'               => 'application/vnd.github+json',
                    'User-Agent'           => 'Just-Form-WordPress-Updater',
                    'X-GitHub-Api-Version' => '2022-11-28',
                ],
            ]
        );

        if (is_wp_error($response) || 200 !== wp_remote_retrieve_response_code($response)) {
            set_transient(self::RELEASE_TRANSIENT, ['available' => false], 15 * MINUTE_IN_SECONDS);
            return null;
        }

        $release = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($release) || !empty($release['draft']) || !empty($release['prerelease'])) {
            set_transient(self::RELEASE_TRANSIENT, ['available' => false], 15 * MINUTE_IN_SECONDS);
            return null;
        }

        $tag = (string) ($release['tag_name'] ?? '');
        $version = preg_replace('/^v/i', '', $tag);
        if (!$version || !preg_match('/^[0-9]+(?:\.[0-9]+){1,3}(?:[-+][a-zA-Z0-9.-]+)?$/D', $version)) {
            set_transient(self::RELEASE_TRANSIENT, ['available' => false], 15 * MINUTE_IN_SECONDS);
            return null;
        }

        $asset_name = 'just-form-' . $version . '.zip';
        $package = '';
        foreach ((array) ($release['assets'] ?? []) as $asset) {
            if (!is_array($asset) || ($asset['name'] ?? '') !== $asset_name) {
                continue;
            }

            $candidate = (string) ($asset['browser_download_url'] ?? '');
            $parts = wp_parse_url($candidate);
            if (is_array($parts) && 'https' === ($parts['scheme'] ?? '') && 'github.com' === ($parts['host'] ?? '')) {
                $package = esc_url_raw($candidate);
                break;
            }
        }

        $release_url = esc_url_raw((string) ($release['html_url'] ?? ''));
        if ('' === $package || '' === $release_url) {
            set_transient(self::RELEASE_TRANSIENT, ['available' => false], 15 * MINUTE_IN_SECONDS);
            return null;
        }

        $data = [
            'available' => true,
            'version'   => $version,
            'url'       => $release_url,
            'package'   => $package,
        ];

        set_transient(self::RELEASE_TRANSIENT, $data, 6 * HOUR_IN_SECONDS);
        return $data;
    }
}
