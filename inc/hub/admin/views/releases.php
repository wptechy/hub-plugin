<?php
/**
 * Releases Manager View
 *
 * @package WPT_Optica_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$action = isset($_GET['action']) ? sanitize_text_field($_GET['action']) : 'list';
?>

<div class="wrap wpt-releases">
    <h1 class="wp-heading-inline"><?php _e('Releases Manager', 'wpt-optica-core'); ?></h1>

    <?php if ($action === 'list'): ?>
        <a href="<?php echo admin_url('admin.php?page=wpt-releases&action=new'); ?>" class="page-title-action">
            <?php _e('Upload New Release', 'wpt-optica-core'); ?>
        </a>
        <hr class="wp-header-end">

        <?php
        $releases = $wpdb->get_results("
            SELECT * FROM {$wpdb->prefix}wpt_releases 
            ORDER BY created_at DESC
        ");
        ?>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 100px;"><?php _e('Type', 'wpt-optica-core'); ?></th>
                    <th style="width: 100px;"><?php _e('Version', 'wpt-optica-core'); ?></th>
                    <th><?php _e('Changelog', 'wpt-optica-core'); ?></th>
                    <th style="width: 120px;"><?php _e('Status', 'wpt-optica-core'); ?></th>
                    <th style="width: 150px;"><?php _e('Released', 'wpt-optica-core'); ?></th>
                    <th style="width: 150px;"><?php _e('Actions', 'wpt-optica-core'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($releases)): ?>
                    <tr>
                        <td colspan="6"><?php _e('No releases found', 'wpt-optica-core'); ?></td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($releases as $release): ?>
                        <tr>
                            <td>
                                <span class="wpt-type-badge wpt-type-<?php echo esc_attr($release->type); ?>">
                                    <?php echo esc_html(ucfirst($release->type)); ?>
                                </span>
                            </td>
                            <td><strong><?php echo esc_html($release->version); ?></strong></td>
                            <td><?php echo esc_html(wp_trim_words($release->changelog, 15)); ?></td>
                            <td>
                                <span class="wpt-status-badge wpt-status-<?php echo esc_attr($release->status); ?>">
                                    <?php echo esc_html(ucfirst($release->status)); ?>
                                </span>
                            </td>
                            <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($release->created_at))); ?></td>
                            <td>
                                <a href="<?php echo admin_url('admin.php?page=wpt-releases&action=view&id=' . $release->id); ?>" class="button button-small">
                                    <?php _e('View', 'wpt-optica-core'); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

    <?php elseif ($action === 'new'): ?>
        <hr class="wp-header-end">

        <div class="notice notice-info">
            <p><?php _e('Upload new plugin, theme, or module release package. The system will notify all sites of the available update.', 'wpt-optica-core'); ?></p>
        </div>

        <form method="post" enctype="multipart/form-data" action="<?php echo admin_url('admin-post.php'); ?>">
            <?php wp_nonce_field('wpt_upload_release', 'wpt_release_nonce'); ?>
            <input type="hidden" name="action" value="wpt_upload_release">

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="type"><?php _e('Release Type', 'wpt-optica-core'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <select id="type" name="type" required>
                            <option value=""><?php _e('Select type', 'wpt-optica-core'); ?></option>
                            <option value="plugin"><?php _e('Plugin (wpt-optica-core)', 'wpt-optica-core'); ?></option>
                            <option value="theme"><?php _e('Theme (wpt-directory)', 'wpt-optica-core'); ?></option>
                            <option value="module"><?php _e('Module', 'wpt-optica-core'); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="version"><?php _e('Version', 'wpt-optica-core'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <input type="text" id="version" name="version" class="regular-text" 
                               placeholder="1.0.0" pattern="[0-9]+\.[0-9]+\.[0-9]+" required>
                        <p class="description"><?php _e('Semantic versioning (e.g., 1.0.0)', 'wpt-optica-core'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="changelog"><?php _e('Changelog', 'wpt-optica-core'); ?></label>
                    </th>
                    <td>
                        <textarea id="changelog" name="changelog" rows="10" class="large-text"></textarea>
                        <p class="description"><?php _e('Describe what changed in this version', 'wpt-optica-core'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="package_file"><?php _e('Package File', 'wpt-optica-core'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <input type="file" id="package_file" name="package_file" accept=".zip" required>
                        <p class="description"><?php _e('Upload ZIP file', 'wpt-optica-core'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="min_wp_version"><?php _e('Minimum WordPress Version', 'wpt-optica-core'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="min_wp_version" name="min_wp_version" class="small-text" 
                               value="6.0" placeholder="6.0">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="min_php_version"><?php _e('Minimum PHP Version', 'wpt-optica-core'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="min_php_version" name="min_php_version" class="small-text" 
                               value="7.4" placeholder="7.4">
                    </td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" name="submit" class="button button-primary" 
                       value="<?php _e('Upload Release', 'wpt-optica-core'); ?>">
                <a href="<?php echo admin_url('admin.php?page=wpt-releases'); ?>" class="button button-secondary">
                    <?php _e('Cancel', 'wpt-optica-core'); ?>
                </a>
            </p>
        </form>

    <?php elseif ($action === 'view'): ?>
        <?php
        $release_id = intval($_GET['id']);
        $release = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}wpt_releases WHERE id = %d",
            $release_id
        ));

        if (!$release) {
            echo '<div class="notice notice-error"><p>' . __('Release not found', 'wpt-optica-core') . '</p></div>';
        } else {
        ?>
            <hr class="wp-header-end">

            <div class="wpt-release-details">
                <h2><?php echo esc_html(ucfirst($release->type)); ?> v<?php echo esc_html($release->version); ?></h2>

                <table class="form-table">
                    <tr>
                        <th><?php _e('Status', 'wpt-optica-core'); ?></th>
                        <td>
                            <span class="wpt-status-badge wpt-status-<?php echo esc_attr($release->status); ?>">
                                <?php echo esc_html(ucfirst($release->status)); ?>
                            </span>
                        </td>
                    </tr>
                    <tr>
                        <th><?php _e('Released', 'wpt-optica-core'); ?></th>
                        <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($release->created_at))); ?></td>
                    </tr>
                    <tr>
                        <th><?php _e('Package URL', 'wpt-optica-core'); ?></th>
                        <td><code><?php echo esc_html($release->package_url); ?></code></td>
                    </tr>
                    <tr>
                        <th><?php _e('Requirements', 'wpt-optica-core'); ?></th>
                        <td>
                            WordPress <?php echo esc_html($release->min_wp_version); ?>+ | 
                            PHP <?php echo esc_html($release->min_php_version); ?>+
                        </td>
                    </tr>
                </table>

                <h3><?php _e('Changelog', 'wpt-optica-core'); ?></h3>
                <div class="wpt-changelog">
                    <?php echo nl2br(esc_html($release->changelog)); ?>
                </div>
            </div>
        <?php } ?>

    <?php endif; ?>
</div>
