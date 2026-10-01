<?php
/**
 * Provide a admin area view for the plugin
 *
 * @package InstagramGridPreview
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>

<div class="wrap">
    <h1 class="wp-heading-inline"><?php _e('Instagram Grids', 'instagram-grid-preview'); ?></h1>
    <a href="<?php echo admin_url('admin.php?page=instagram-grids-new'); ?>" class="page-title-action">
        <?php _e('Add New', 'instagram-grid-preview'); ?>
    </a>
    <hr class="wp-header-end">

    <?php if (empty($grids)): ?>
        <div class="notice notice-info">
            <p><?php _e('No grids found. Create your first Instagram grid!', 'instagram-grid-preview'); ?></p>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th scope="col" class="manage-column column-name column-primary">
                        <?php _e('Name', 'instagram-grid-preview'); ?>
                    </th>
                    <th scope="col" class="manage-column column-preview">
                        <?php _e('Preview', 'instagram-grid-preview'); ?>
                    </th>
                    <th scope="col" class="manage-column column-description">
                        <?php _e('Description', 'instagram-grid-preview'); ?>
                    </th>
                    <th scope="col" class="manage-column column-dimensions">
                        <?php _e('Dimensions', 'instagram-grid-preview'); ?>
                    </th>
                    <th scope="col" class="manage-column column-profile">
                        <?php _e('Profile', 'instagram-grid-preview'); ?>
                    </th>
                    <th scope="col" class="manage-column column-date">
                        <?php _e('Date', 'instagram-grid-preview'); ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($grids as $grid): ?>
                    <tr>
                        <td class="column-name column-primary">
                            <strong>
                                <a href="<?php echo esc_url(admin_url('admin.php?page=instagram-grids-new&grid_id=' . intval($grid->id))); ?>">
                                    <?php echo esc_html($grid->name); ?>
                                </a>
                            </strong>
                            <div class="row-actions">
                                <span class="edit">
                                    <a href="<?php echo esc_url(admin_url('admin.php?page=instagram-grids-new&grid_id=' . intval($grid->id))); ?>">
                                        <?php _e('Edit', 'instagram-grid-preview'); ?>
                                    </a> |
                                </span>
                                <span class="view">
                                    <a href="<?php echo esc_url(home_url('/instagram-grid/' . intval($grid->id) . '/')); ?>" target="_blank" rel="noopener noreferrer">
                                        <?php _e('View profile', 'instagram-grid-preview'); ?>
                                    </a> |
                                </span>
                                <span class="duplicate">
                                    <a href="#" class="igp-duplicate-grid" data-grid-id="<?php echo esc_attr($grid->id); ?>">
                                        <?php _e('Duplicate', 'instagram-grid-preview'); ?>
                                    </a> |
                                </span>
                                <span class="delete">
                                    <a href="#" class="igp-delete-grid" data-grid-id="<?php echo esc_attr($grid->id); ?>" style="color: #a00;">
                                        <?php _e('Delete', 'instagram-grid-preview'); ?>
                                    </a>
                                </span>
                            </div>
                            <button type="button" class="toggle-row">
                                <span class="screen-reader-text"><?php _e('Show more details', 'instagram-grid-preview'); ?></span>
                            </button>
                        </td>
                        <td class="column-preview" data-colname="<?php _e('Preview', 'instagram-grid-preview'); ?>">
                            <?php
                            $mini_posts = IGP_Grid_Model::normalize_grid_data(json_decode($grid->grid_data, true));
                            ksort($mini_posts);
                            $mini_cells = 0;
                            ?>
                            <div class="igp-mini-grid">
                                <?php foreach ($mini_posts as $mini_post) : ?>
                                    <?php
                                    if ($mini_cells >= 9) {
                                        break;
                                    }
                                    $thumb = $mini_post['media'][0]['thumbnail_url'];
                                    ?>
                                    <span><img src="<?php echo esc_url($thumb); ?>" alt="" /></span>
                                    <?php $mini_cells++; ?>
                                <?php endforeach; ?>
                                <?php while ($mini_cells < 9) : ?>
                                    <span></span>
                                    <?php $mini_cells++; ?>
                                <?php endwhile; ?>
                            </div>
                        </td>
                        <td class="column-description" data-colname="<?php _e('Description', 'instagram-grid-preview'); ?>">
                            <?php echo esc_html($grid->description); ?>
                        </td>
                        <td class="column-dimensions" data-colname="<?php _e('Dimensions', 'instagram-grid-preview'); ?>">
                            <?php echo intval($grid->columns) . ' × ' . intval($grid->rows); ?>
                        </td>
                        <td class="column-profile" data-colname="<?php _e('Profile', 'instagram-grid-preview'); ?>">
                            <a href="<?php echo esc_url(home_url('/instagram-grid/' . intval($grid->id) . '/')); ?>" target="_blank" rel="noopener noreferrer">
                                <?php echo esc_html(home_url('/instagram-grid/' . intval($grid->id) . '/')); ?>
                            </a>
                            <button type="button" class="button button-small igp-copy-profile" data-url="<?php echo esc_attr(home_url('/instagram-grid/' . intval($grid->id) . '/')); ?>">
                                <?php _e('Copy', 'instagram-grid-preview'); ?>
                            </button>
                        </td>
                        <td class="column-date" data-colname="<?php _e('Date', 'instagram-grid-preview'); ?>">
                            <?php echo date_i18n(get_option('date_format'), strtotime($grid->created_at)); ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle delete grid
    document.querySelectorAll('.igp-delete-grid').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (!confirm(igp_ajax.strings.confirm_delete)) {
                return;
            }
            
            const gridId = this.dataset.gridId;
            const row = this.closest('tr');
            
            fetch(igp_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'igp_delete_grid',
                    grid_id: gridId,
                    nonce: igp_ajax.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    row.remove();
                    // Show success message
                    const notice = document.createElement('div');
                    notice.className = 'notice notice-success is-dismissible';
                    const p = document.createElement('p');
                    p.textContent = data.data.message;
                    notice.appendChild(p);
                    document.querySelector('.wrap h1').after(notice);
                } else {
                    alert(data.data.message || igp_ajax.strings.error_occurred);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert(igp_ajax.strings.error_occurred);
            });
        });
    });
    
    // Handle duplicate grid
    document.querySelectorAll('.igp-duplicate-grid').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (!confirm(igp_ajax.strings.confirm_duplicate)) {
                return;
            }
            
            const gridId = this.dataset.gridId;
            
            fetch(igp_ajax.ajax_url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'igp_duplicate_grid',
                    grid_id: gridId,
                    nonce: igp_ajax.nonce
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.data.message || igp_ajax.strings.error_occurred);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert(igp_ajax.strings.error_occurred);
            });
        });
    });

    // Handle copy profile URL
    document.querySelectorAll('.igp-copy-profile').forEach(function(button) {
        button.addEventListener('click', function() {
            const url = this.dataset.url;
            navigator.clipboard.writeText(url).then(function() {
                const originalText = button.textContent;
                button.textContent = '<?php _e('Copied!', 'instagram-grid-preview'); ?>';
                setTimeout(function() {
                    button.textContent = originalText;
                }, 2000);
            });
        });
    });
});
</script>