<?php
/**
 * Module Categories Management View
 *
 * @package WPT_Optica_Core
 */

if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;

$wpt_module_categories_base = $GLOBALS['wpt_module_categories_base'] ?? array(
    'page'  => 'wpt-module-categories',
    'query' => array(),
);
$wpt_module_categories_embed = !empty($GLOBALS['wpt_module_categories_embed']);

if (!function_exists('wpt_module_categories_admin_url')) {
    function wpt_module_categories_admin_url( $args = array() ) {
        global $wpt_module_categories_base;

        $params = array_merge( $wpt_module_categories_base['query'], $args );
        $params = array_filter(
            $params,
            static function ( $value ) {
                return $value !== '' && $value !== null;
            }
        );

        $query = http_build_query( $params );
        $base  = 'admin.php?page=' . $wpt_module_categories_base['page'];
        if ( $query ) {
            $base .= '&' . $query;
        }

        return admin_url( $base );
    }
}

// Get category for edit mode
if ($action === 'edit' && $category_id > 0) {
    $category = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}wpt_module_categories WHERE id = %d",
        $category_id
    ));

    if (!$category) {
        echo '<div class="notice notice-error"><p>' . __('Category not found', 'wpt-optica-core') . '</p></div>';
        $action = 'list';
    }
}

if ( 'list' === $action ) {
    wp_enqueue_script( 'jquery-ui-sortable' );
}
?>

<div class="<?php echo $wpt_module_categories_embed ? 'wpt-module-categories wpt-module-categories--embedded' : 'wrap wpt-module-categories'; ?>">
    <h1 class="wp-heading-inline"><?php _e('Module Categories', 'wpt-optica-core'); ?></h1>

    <?php if ($action === 'list'): ?>
        <a href="<?php echo esc_url( wpt_module_categories_admin_url( array( 'action' => 'new' ) ) ); ?>" class="page-title-action">
            <?php _e('Add New Category', 'wpt-optica-core'); ?>
        </a>
        <hr class="wp-header-end">

        <p class="description">
            <?php _e('Categoriile modulelor ajută la organizarea și gruparea modulelor disponibile. Poți reordona categoriile prin drag & drop.', 'wpt-optica-core'); ?>
        </p>

        <?php
        // Get all categories with module counts
        $categories = $wpdb->get_results("
            SELECT c.*,
                COUNT(m.id) as module_count
            FROM {$wpdb->prefix}wpt_module_categories c
            LEFT JOIN {$wpdb->prefix}wpt_available_modules m ON c.id = m.category_id
            GROUP BY c.id
            ORDER BY c.sort_order ASC
        ");
        ?>

        <?php if (empty($categories)): ?>
            <div class="notice notice-info">
                <p><?php _e('Nu există categorii. Adaugă prima categorie.', 'wpt-optica-core'); ?></p>
            </div>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped wpt-categories-table">
                <thead>
                    <tr>
                        <th style="width: 40px;"><?php _e('Order', 'wpt-optica-core'); ?></th>
                        <th style="width: 60px;"><?php _e('Icon', 'wpt-optica-core'); ?></th>
                        <th><?php _e('Name', 'wpt-optica-core'); ?></th>
                        <th><?php _e('Slug', 'wpt-optica-core'); ?></th>
                        <th><?php _e('Description', 'wpt-optica-core'); ?></th>
                        <th style="width: 100px;"><?php _e('Modules', 'wpt-optica-core'); ?></th>
                        <th style="width: 150px;"><?php _e('Actions', 'wpt-optica-core'); ?></th>
                    </tr>
                </thead>
                <tbody id="wpt-categories-sortable">
                    <?php foreach ($categories as $cat): ?>
                        <tr data-category-id="<?php echo $cat->id; ?>">
                            <td class="drag-handle">
                                <span class="dashicons dashicons-menu" style="cursor: move;"></span>
                            </td>
                            <td class="category-icon-cell">
                                <span class="dashicons dashicons-<?php echo esc_attr($cat->icon); ?>" style="font-size: 24px; color: #2271b1;"></span>
                            </td>
                            <td>
                                <strong><?php echo esc_html($cat->name); ?></strong>
                            </td>
                            <td>
                                <code><?php echo esc_html($cat->slug); ?></code>
                            </td>
                            <td>
                                <?php echo esc_html(wp_trim_words($cat->description, 15)); ?>
                            </td>
                            <td class="text-center">
                                <strong><?php echo intval($cat->module_count); ?></strong>
                            </td>
                            <td>
                                <a href="<?php echo esc_url( wpt_module_categories_admin_url( array( 'action' => 'edit', 'id' => $cat->id ) ) ); ?>" class="button button-small">
                                    <?php _e('Edit', 'wpt-optica-core'); ?>
                                </a>
                                <?php if ($cat->module_count == 0): ?>
                                    <a href="<?php echo esc_url( wp_nonce_url( wpt_module_categories_admin_url( array( 'action' => 'delete', 'id' => $cat->id ) ), 'wpt_delete_category_' . $cat->id ) ); ?>"
                                       class="button button-small button-link-delete"
                                       onclick="return confirm('<?php esc_attr_e('Are you sure you want to delete this category?', 'wpt-optica-core'); ?>');">
                                        <?php _e('Delete', 'wpt-optica-core'); ?>
                                    </a>
                                <?php else: ?>
                                    <span class="description"><?php _e('Has modules', 'wpt-optica-core'); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

    <?php elseif ($action === 'new' || $action === 'edit'): ?>
        <hr class="wp-header-end">

        <p><a href="<?php echo esc_url( wpt_module_categories_admin_url() ); ?>">&larr; <?php _e('Back to Categories', 'wpt-optica-core'); ?></a></p>

        <form method="post" action="<?php echo esc_url( wpt_module_categories_admin_url( array_merge( array( 'action' => $action ), $category_id > 0 ? array( 'id' => $category_id ) : array() ) ) ); ?>">
            <?php wp_nonce_field('wpt_save_category', 'wpt_category_nonce'); ?>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="name"><?php _e('Category Name', 'wpt-optica-core'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <input type="text" id="name" name="name" class="regular-text" required
                               value="<?php echo isset($category) ? esc_attr($category->name) : ''; ?>"
                               placeholder="<?php _e('E.g., Content & Pagini', 'wpt-optica-core'); ?>">
                        <p class="description"><?php _e('Numele categoriei așa cum va fi afișat', 'wpt-optica-core'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="slug"><?php _e('Slug', 'wpt-optica-core'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <input type="text" id="slug" name="slug" class="regular-text" required
                               value="<?php echo isset($category) ? esc_attr($category->slug) : ''; ?>"
                               placeholder="<?php _e('content-pages', 'wpt-optica-core'); ?>"
                               pattern="[a-z0-9-]+" title="<?php _e('Only lowercase letters, numbers and hyphens', 'wpt-optica-core'); ?>">
                        <p class="description"><?php _e('URL-friendly identificator (doar litere mici, numere și liniuțe)', 'wpt-optica-core'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="description"><?php _e('Description', 'wpt-optica-core'); ?></label>
                    </th>
                    <td>
                        <textarea id="description" name="description" class="large-text" rows="3"
                                  placeholder="<?php _e('Short description of this category...', 'wpt-optica-core'); ?>"><?php echo isset($category) ? esc_textarea($category->description) : ''; ?></textarea>
                        <p class="description"><?php _e('Descriere scurtă a categoriei', 'wpt-optica-core'); ?></p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="icon"><?php _e('Dashicon', 'wpt-optica-core'); ?> <span class="required">*</span></label>
                    </th>
                    <td>
                        <input type="text" id="icon" name="icon" class="regular-text" required
                               value="<?php echo isset($category) ? esc_attr($category->icon) : ''; ?>"
                               placeholder="admin-generic">
                        <p class="description">
                            <?php _e('Numele iconului Dashicons (fără prefixul "dashicons-"). ', 'wpt-optica-core'); ?>
                            <a href="https://developer.wordpress.org/resource/dashicons/" target="_blank"><?php _e('Browse Dashicons', 'wpt-optica-core'); ?></a>
                        </p>
                        <div class="icon-preview">
                            <strong><?php _e('Preview:', 'wpt-optica-core'); ?></strong>
                            <span class="dashicons dashicons-<?php echo isset($category) ? esc_attr($category->icon) : 'admin-generic'; ?>" id="icon-preview" style="font-size: 32px; color: #2271b1;"></span>
                        </div>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="sort_order"><?php _e('Sort Order', 'wpt-optica-core'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="sort_order" name="sort_order" class="small-text" min="0"
                               value="<?php echo isset($category) ? esc_attr($category->sort_order) : '0'; ?>">
                        <p class="description"><?php _e('Ordinea de afișare (număr mai mic = afișat mai sus). Poate fi modificată și prin drag & drop în listă.', 'wpt-optica-core'); ?></p>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <input type="submit" name="submit" class="button button-primary"
                       value="<?php echo $action === 'edit' ? __('Update Category', 'wpt-optica-core') : __('Create Category', 'wpt-optica-core'); ?>">
                <a href="<?php echo esc_url( wpt_module_categories_admin_url() ); ?>" class="button button-secondary">
                    <?php _e('Cancel', 'wpt-optica-core'); ?>
                </a>
            </p>
        </form>

    <?php endif; ?>
</div>

<style>
.wpt-categories-table .drag-handle {
    cursor: move;
    text-align: center;
}

.wpt-categories-table .category-icon-cell {
    text-align: center;
}

.wpt-categories-table .text-center {
    text-align: center;
}

.wpt-categories-table tbody tr {
    transition: background-color 0.2s;
}

.wpt-categories-table tbody tr.ui-sortable-helper {
    background-color: #f0f0f1;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.wpt-module-categories--embedded {
    margin-top: 20px;
}

.icon-preview {
    margin-top: 10px;
    padding: 10px;
    background: #f0f0f1;
    border-radius: 4px;
    display: inline-block;
}

.required {
    color: #d63638;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Live icon preview
    $('#icon').on('input', function() {
        const iconName = $(this).val() || 'admin-generic';
        $('#icon-preview').attr('class', 'dashicons dashicons-' + iconName);
    });

    // Auto-generate slug from name
    $('#name').on('input', function() {
        if ($('#slug').val() === '' || !$('#slug').data('manually-edited')) {
            const slug = $(this).val()
                .toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .trim();
            $('#slug').val(slug);
        }
    });

    $('#slug').on('input', function() {
        $(this).data('manually-edited', true);
    });

    // Sortable categories
    <?php if ($action === 'list' && !empty($categories)): ?>
    $('#wpt-categories-sortable').sortable({
        handle: '.drag-handle',
        placeholder: 'ui-state-highlight',
        update: function(event, ui) {
            const order = [];
            $('#wpt-categories-sortable tr').each(function() {
                order.push($(this).data('category-id'));
            });

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wpt_reorder_categories',
                    nonce: '<?php echo wp_create_nonce('wpt_reorder_categories'); ?>',
                    order: order
                },
                success: function(response) {
                    if (response.success) {
                        // Show temporary success message
                        const $notice = $('<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>');
                        $('.wpt-module-categories h1').after($notice);
                        setTimeout(function() {
                            $notice.fadeOut(function() { $(this).remove(); });
                        }, 3000);
                    }
                }
            });
        }
    });
    <?php endif; ?>
});
</script>
