<?php
/*
Plugin Name: My Pro Plugin
Description: A plugin with license key validation and GitHub-based Pro version update.
Version: 92.0.0
Author: Your Name
*/

// ========== License Validation ==========
function my_plugin_validate_license_key($license_key) {
    $github_api_url = 'https://api.github.com/repos/Justin9930-ops/Test2/contents/README.md';

    $response = wp_remote_get($github_api_url, [
        'headers' => [
            'User-Agent' => 'GitHub API Integration with Fine-Grained PAT/1.0',
            'Authorization' => 'token ' . $license_key
        ]
    ]);

    if (is_wp_error($response)) {
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body);

    return isset($data->type) && $data->type === 'file';
}

function my_plugin_license_page() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['my_plugin_license_key'])) {
        $license_key = sanitize_text_field($_POST['my_plugin_license_key']);
        if (my_plugin_validate_license_key($license_key)) {
            update_option('my_plugin_license_key', $license_key);
            echo '<div class="updated"><p>License Key is valid. Pro features unlocked!</p></div>';
        } else {
            echo '<div class="error"><p>Invalid License Key. Please try again.</p></div>';
        }
    }
    ?>
    <div class="wrap">
        <h2>Enter your License Key</h2>
        <form method="post" action="">
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">License Key</th>
                    <td><input type="text" name="my_plugin_license_key" value="<?php echo esc_attr(get_option('my_plugin_license_key')); ?>" /></td>
                </tr>
            </table>
            <?php submit_button('Save License Key'); ?>
        </form>
    </div>
    <?php
}

function my_plugin_is_pro_version() {
    $license_key = get_option('my_plugin_license_key');
    return !empty($license_key) && my_plugin_validate_license_key($license_key);
}

function my_plugin_register_settings() {
    register_setting('my_plugin_license_group', 'my_plugin_license_key');
}

add_action('admin_menu', function() {
    add_menu_page('My Plugin', 'My Plugin', 'manage_options', 'my_plugin_license_page', 'my_plugin_license_page');
});

add_action('admin_init', 'my_plugin_register_settings');

// ========== Plugin Updater ==========
add_filter('site_transient_update_plugins', 'my_plugin_github_update_checker');

function my_plugin_github_update_checker($transient) {
    $plugin_slug = plugin_basename(__FILE__);
    $current_version = '1.0.0';
    $github_user = 'Justin9930-ops';
    $github_repo = 'Test2';
    $license_key = get_option('my_plugin_license_key');

    if (empty($transient->checked)) return $transient;

    $response = wp_remote_get("https://api.github.com/repos/$github_user/$github_repo/releases/latest", [
        'headers' => [
            'Authorization' => 'token ' . $license_key,
            'User-Agent'    => 'WordPressPluginUpdater'
        ]
    ]);

    if (is_wp_error($response)) return $transient;

    $body = json_decode(wp_remote_retrieve_body($response));

    if (!isset($body->tag_name)) return $transient;

    $latest_version = ltrim($body->tag_name, 'v');
    $download_url = $body->zipball_url;

    if (version_compare($current_version, $latest_version, '<')) {
        $transient->response[$plugin_slug] = (object) [
            'slug'        => $plugin_slug,
            'plugin'      => $plugin_slug,
            'new_version' => $latest_version,
            'package'     => $download_url,
            'url'         => "https://github.com/$github_user/$github_repo"
        ];
    }

    return $transient;
}

// ========== Features ==========
if (my_plugin_is_pro_version()) {
    add_action('admin_notices', function() {
        echo '<div class="updated"><p><strong>Pro Features Enabled!</strong></p></div>';
    });
    
    function my_plugin_pro_feature() {
        echo 'This is a Pro feature!';
    }
} else {
    function my_plugin_free_feature() {
        echo 'Upgrade to Pro for more features!';
    }
}
