<?php
/**
 * Platform Settings View
 *
 * @package WPT_Optica_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wpt_settings_nonce']) && wp_verify_nonce($_POST['wpt_settings_nonce'], 'wpt_save_settings')) {
    update_option('wpt_platform_name', sanitize_text_field($_POST['platform_name']));
    update_option('wpt_platform_email', sanitize_email($_POST['platform_email']));
    update_option('wpt_smtp_host', sanitize_text_field($_POST['smtp_host']));
    update_option('wpt_smtp_port', intval($_POST['smtp_port']));
    update_option('wpt_smtp_username', sanitize_text_field($_POST['smtp_username']));
    
    if (!empty($_POST['smtp_password'])) {
        update_option('wpt_smtp_password', sanitize_text_field($_POST['smtp_password']));
    }

    echo '<div class="notice notice-success"><p>' . __('Settings saved successfully', 'wpt-optica-core') . '</p></div>';
}

// Get current settings
$platform_name = get_option('wpt_platform_name', 'WPT Optica Platform');
$platform_email = get_option('wpt_platform_email', get_option('admin_email'));
$smtp_host = get_option('wpt_smtp_host', '');
$smtp_port = get_option('wpt_smtp_port', 587);
$smtp_username = get_option('wpt_smtp_username', '');
?>

<div class="wrap wpt-settings">
    <h1><?php _e('Platform Settings', 'wpt-optica-core'); ?></h1>

    <form method="post" action="">
        <?php wp_nonce_field('wpt_save_settings', 'wpt_settings_nonce'); ?>

        <h2 class="title"><?php _e('General Settings', 'wpt-optica-core'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="platform_name"><?php _e('Platform Name', 'wpt-optica-core'); ?></label>
                </th>
                <td>
                    <input type="text" id="platform_name" name="platform_name" class="regular-text" 
                           value="<?php echo esc_attr($platform_name); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="platform_email"><?php _e('Platform Email', 'wpt-optica-core'); ?></label>
                </th>
                <td>
                    <input type="email" id="platform_email" name="platform_email" class="regular-text" 
                           value="<?php echo esc_attr($platform_email); ?>">
                    <p class="description"><?php _e('Email address for platform notifications', 'wpt-optica-core'); ?></p>
                </td>
            </tr>
        </table>

        <h2 class="title"><?php _e('SMTP Settings', 'wpt-optica-core'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="smtp_host"><?php _e('SMTP Host', 'wpt-optica-core'); ?></label>
                </th>
                <td>
                    <input type="text" id="smtp_host" name="smtp_host" class="regular-text" 
                           value="<?php echo esc_attr($smtp_host); ?>" placeholder="smtp.example.com">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="smtp_port"><?php _e('SMTP Port', 'wpt-optica-core'); ?></label>
                </th>
                <td>
                    <input type="number" id="smtp_port" name="smtp_port" class="small-text" 
                           value="<?php echo esc_attr($smtp_port); ?>" placeholder="587">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="smtp_username"><?php _e('SMTP Username', 'wpt-optica-core'); ?></label>
                </th>
                <td>
                    <input type="text" id="smtp_username" name="smtp_username" class="regular-text" 
                           value="<?php echo esc_attr($smtp_username); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="smtp_password"><?php _e('SMTP Password', 'wpt-optica-core'); ?></label>
                </th>
                <td>
                    <input type="password" id="smtp_password" name="smtp_password" class="regular-text" 
                           placeholder="<?php _e('Leave blank to keep current password', 'wpt-optica-core'); ?>">
                </td>
            </tr>
        </table>

        <h2 class="title"><?php _e('System Information', 'wpt-optica-core'); ?></h2>
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Plugin Version', 'wpt-optica-core'); ?></th>
                <td><?php echo esc_html(WPT_CORE_VERSION); ?></td>
            </tr>
            <tr>
                <th scope="row"><?php _e('Database Version', 'wpt-optica-core'); ?></th>
                <td><?php echo esc_html(get_option('wpt_db_version', '1.0.0')); ?></td>
            </tr>
            <tr>
                <th scope="row"><?php _e('WordPress Version', 'wpt-optica-core'); ?></th>
                <td><?php echo esc_html(get_bloginfo('version')); ?></td>
            </tr>
            <tr>
                <th scope="row"><?php _e('PHP Version', 'wpt-optica-core'); ?></th>
                <td><?php echo esc_html(phpversion()); ?></td>
            </tr>
            <tr>
                <th scope="row"><?php _e('ACF Pro', 'wpt-optica-core'); ?></th>
                <td>
                    <?php if (function_exists('acf')): ?>
                        <span style="color: #46b450;">✓ <?php _e('Active', 'wpt-optica-core'); ?></span>
                    <?php else: ?>
                        <span style="color: #dc3232;">✗ <?php _e('Not Active', 'wpt-optica-core'); ?></span>
                    <?php endif; ?>
                </td>
            </tr>
        </table>

        <p class="submit">
            <input type="submit" name="submit" class="button button-primary" 
                   value="<?php _e('Save Settings', 'wpt-optica-core'); ?>">
        </p>
    </form>

    <hr>

    <h2 class="title"><?php _e('Maintenance Actions', 'wpt-optica-core'); ?></h2>
    <div class="wpt-maintenance-actions">
        <p>
            <a href="<?php echo admin_url('admin.php?page=wpt-settings&action=clear_cache'); ?>" 
               class="button button-secondary" 
               onclick="return confirm('<?php _e('Are you sure you want to clear all caches?', 'wpt-optica-core'); ?>');">
                <?php _e('Clear All Caches', 'wpt-optica-core'); ?>
            </a>
        </p>
        <p>
            <a href="<?php echo admin_url('admin.php?page=wpt-settings&action=regenerate_api_keys'); ?>" 
               class="button button-secondary" 
               onclick="return confirm('<?php _e('This will regenerate all API keys. Sites will need to be reconfigured. Continue?', 'wpt-optica-core'); ?>');">
                <?php _e('Regenerate All API Keys', 'wpt-optica-core'); ?>
            </a>
        </p>
        <p class="description">
            <?php _e('Use these actions carefully. They cannot be undone.', 'wpt-optica-core'); ?>
        </p>
    </div>
</div>
