<?php
/**
 * Plans & Pricing - Feature Mappings Tab
 * Manage feature mappings (what each feature key represents)
 *
 * @package WPT_Optica_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['wpt_mapping_nonce']) && wp_verify_nonce($_POST['wpt_mapping_nonce'], 'wpt_save_mapping')) {
    $mapping_data = array(
        'feature_key' => sanitize_key($_POST['feature_key']),
        'feature_name' => sanitize_text_field($_POST['feature_name']),
        'feature_type' => sanitize_text_field($_POST['feature_type']),
        'target_identifier' => !empty($_POST['target_identifier']) ? sanitize_text_field($_POST['target_identifier']) : null,
        'is_quota' => isset($_POST['is_quota']) ? 1 : 0,
        'description' => !empty($_POST['description']) ? sanitize_textarea_field($_POST['description']) : null,
    );

    $result = WPT_Feature_Mapping_Manager::save_mapping($mapping_data);

    if ($result) {
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Mapare salvată cu succes!', 'wpt-optica-core') . '</p></div>';
    } else {
        echo '<div class="notice notice-error is-dismissible"><p>' . __('Eroare la salvarea mapării.', 'wpt-optica-core') . '</p></div>';
    }
}

// Handle delete action
if (isset($_GET['action']) && $_GET['action'] === 'delete_mapping' && !empty($_GET['feature_key'])) {
    check_admin_referer('wpt_delete_mapping_' . $_GET['feature_key']);

    $result = WPT_Feature_Mapping_Manager::delete_mapping($_GET['feature_key']);

    if ($result) {
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Mapare ștearsă cu succes!', 'wpt-optica-core') . '</p></div>';
    } else {
        echo '<div class="notice notice-error is-dismissible"><p>' . __('Eroare la ștergerea mapării.', 'wpt-optica-core') . '</p></div>';
    }
}

// Get all mappings
$mappings = WPT_Feature_Mapping_Manager::get_mappings();

// Get editing mapping if in edit mode
$action = isset($_GET['mapping_action']) ? sanitize_text_field($_GET['mapping_action']) : 'list';
$editing_mapping = null;
if ($action === 'edit' && !empty($_GET['feature_key'])) {
    $editing_mapping = WPT_Feature_Mapping_Manager::get_mapping($_GET['feature_key']);
}
?>

<div class="wpt-mappings-container">
    <?php if ($action === 'list'): ?>
        <!-- List View -->
        <div class="wpt-mappings-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <h3 style="margin: 0;">📋 Feature Mappings</h3>
                <p style="margin: 5px 0 0 0; color: #666;">
                    Definește ce reprezintă fiecare feature din planuri (post types, capabilities, boolean features)
                </p>
            </div>
            <a href="<?php echo esc_url(add_query_arg(array('tab' => 'mappings', 'mapping_action' => 'add'), admin_url('admin.php?page=wpt-plans'))); ?>"
               class="button button-primary">
                ➕ Adaugă Mapare Nouă
            </a>
        </div>

        <!-- Mappings Table -->
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 15%;">Feature Key</th>
                    <th style="width: 20%;">Nume</th>
                    <th style="width: 15%;">Tip</th>
                    <th style="width: 20%;">Target</th>
                    <th style="width: 10%;">Quota?</th>
                    <th style="width: 10%;">Acțiuni</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($mappings)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px;">
                            <p style="margin: 0; color: #666;">Nu există mapări definite.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($mappings as $mapping): ?>
                        <?php
                        $icon = WPT_Feature_Mapping_Manager::get_feature_icon($mapping->feature_type);
                        ?>
                        <tr>
                            <td>
                                <code><?php echo esc_html($mapping->feature_key); ?></code>
                            </td>
                            <td>
                                <span class="dashicons <?php echo esc_attr($icon); ?>" style="margin-right: 5px;"></span>
                                <strong><?php echo esc_html($mapping->feature_name); ?></strong>
                                <?php if ($mapping->description): ?>
                                    <br><small style="color: #666;"><?php echo esc_html($mapping->description); ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="wpt-badge wpt-badge-<?php echo esc_attr($mapping->feature_type); ?>">
                                    <?php echo esc_html(ucfirst($mapping->feature_type)); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($mapping->target_identifier): ?>
                                    <code><?php echo esc_html($mapping->target_identifier); ?></code>
                                <?php else: ?>
                                    <span style="color: #999;">—</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($mapping->is_quota): ?>
                                    <span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span>
                                <?php else: ?>
                                    <span class="dashicons dashicons-minus" style="color: #ddd;"></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="<?php echo esc_url(add_query_arg(array('tab' => 'mappings', 'mapping_action' => 'edit', 'feature_key' => $mapping->feature_key), admin_url('admin.php?page=wpt-plans'))); ?>"
                                   class="button button-small">
                                    ✏️ Editează
                                </a>
                                <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(array('tab' => 'mappings', 'action' => 'delete_mapping', 'feature_key' => $mapping->feature_key), admin_url('admin.php?page=wpt-plans')), 'wpt_delete_mapping_' . $mapping->feature_key)); ?>"
                                   class="button button-small button-link-delete"
                                   onclick="return confirm('Sigur vrei să ștergi această mapare?');">
                                    🗑️ Șterge
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

    <?php else: ?>
        <!-- Add/Edit Form -->
        <div class="wpt-mapping-form">
            <h3><?php echo $editing_mapping ? '✏️ Editează Mapare' : '➕ Adaugă Mapare Nouă'; ?></h3>

            <form method="post" action="">
                <?php wp_nonce_field('wpt_save_mapping', 'wpt_mapping_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th><label for="feature_key">Feature Key *</label></th>
                        <td>
                            <input type="text"
                                   id="feature_key"
                                   name="feature_key"
                                   value="<?php echo $editing_mapping ? esc_attr($editing_mapping->feature_key) : ''; ?>"
                                   <?php echo $editing_mapping ? 'readonly' : ''; ?>
                                   class="regular-text"
                                   required
                                   placeholder="ex: offers, jobs, candidati_access">
                            <p class="description">
                                Cheia unică folosită în JSON-ul planurilor (ex: "offers", "jobs", "candidati_access")
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="feature_name">Nume Afișat *</label></th>
                        <td>
                            <input type="text"
                                   id="feature_name"
                                   name="feature_name"
                                   value="<?php echo $editing_mapping ? esc_attr($editing_mapping->feature_name) : ''; ?>"
                                   class="regular-text"
                                   required
                                   placeholder="ex: Oferte, Acces Candidați">
                            <p class="description">Numele vizibil în interfață (limba română)</p>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="feature_type">Tip Feature *</label></th>
                        <td>
                            <select id="feature_type" name="feature_type" required>
                                <option value="">— Selectează —</option>
                                <option value="post_type" <?php echo ($editing_mapping && $editing_mapping->feature_type === 'post_type') ? 'selected' : ''; ?>>
                                    Post Type (ex: offer, job, location)
                                </option>
                                <option value="taxonomy" <?php echo ($editing_mapping && $editing_mapping->feature_type === 'taxonomy') ? 'selected' : ''; ?>>
                                    Taxonomy (ex: categorie, tag)
                                </option>
                                <option value="capability" <?php echo ($editing_mapping && $editing_mapping->feature_type === 'capability') ? 'selected' : ''; ?>>
                                    Capability (ex: manage_candidates)
                                </option>
                                <option value="boolean" <?php echo ($editing_mapping && $editing_mapping->feature_type === 'boolean') ? 'selected' : ''; ?>>
                                    Boolean (Da/Nu)
                                </option>
                                <option value="numeric" <?php echo ($editing_mapping && $editing_mapping->feature_type === 'numeric') ? 'selected' : ''; ?>>
                                    Numeric (valoare arbitrară)
                                </option>
                            </select>
                            <p class="description">Tipul de feature (determină cum e interpretat)</p>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="target_identifier">Target Identifier</label></th>
                        <td>
                            <input type="text"
                                   id="target_identifier"
                                   name="target_identifier"
                                   value="<?php echo $editing_mapping ? esc_attr($editing_mapping->target_identifier) : ''; ?>"
                                   class="regular-text"
                                   placeholder="ex: offer, manage_candidates">
                            <p class="description">
                                Post type slug, capability name sau taxonomy slug (opțional pentru boolean/numeric)
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="is_quota">Este Quota?</label></th>
                        <td>
                            <label>
                                <input type="checkbox"
                                       id="is_quota"
                                       name="is_quota"
                                       value="1"
                                       <?php echo ($editing_mapping && $editing_mapping->is_quota) ? 'checked' : ''; ?>>
                                Da, această feature reprezintă o limită numerică (quota)
                            </label>
                            <p class="description">
                                Bifează dacă feature-ul limitează numărul de resurse (ex: 20 oferte, 10 joburi)
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th><label for="description">Descriere</label></th>
                        <td>
                            <textarea id="description"
                                      name="description"
                                      rows="3"
                                      class="large-text"
                                      placeholder="Descriere detaliată a feature-ului..."><?php echo $editing_mapping ? esc_textarea($editing_mapping->description) : ''; ?></textarea>
                            <p class="description">Explicație clară despre ce face acest feature</p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary">
                        💾 <?php echo $editing_mapping ? 'Actualizează Maparea' : 'Adaugă Maparea'; ?>
                    </button>
                    <a href="<?php echo esc_url(add_query_arg(array('tab' => 'mappings'), admin_url('admin.php?page=wpt-plans'))); ?>"
                       class="button">
                        ← Înapoi la Listă
                    </a>
                </p>
            </form>
        </div>
    <?php endif; ?>
</div>

<style>
.wpt-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}
.wpt-badge-post_type {
    background: #e3f2fd;
    color: #1976d2;
}
.wpt-badge-taxonomy {
    background: #f3e5f5;
    color: #7b1fa2;
}
.wpt-badge-capability {
    background: #fff3e0;
    color: #f57c00;
}
.wpt-badge-boolean {
    background: #e8f5e9;
    color: #388e3c;
}
.wpt-badge-numeric {
    background: #fce4ec;
    color: #c2185b;
}
</style>
