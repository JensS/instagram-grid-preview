<?php
/**
 * Provide a admin area view for the grid editor
 *
 * @package InstagramGridPreview
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

$is_edit = $grid !== null;
$grid_name = $is_edit ? $grid['name'] : '';
$grid_description = $is_edit ? $grid['description'] : '';
$grid_columns = $is_edit ? $grid['columns'] : 3;
$grid_rows = $is_edit ? $grid['rows'] : 3;
$grid_aspect_ratio = $is_edit ? $grid['aspect_ratio'] : '1:1';
$grid_id = $is_edit ? $grid['id'] : 0;

// Normalize stored grid data into posts (media arrays + captions + counts).
$raw_grid_data = ($is_edit && is_array($grid['grid_data'])) ? $grid['grid_data'] : array();
$grid_data = IGP_Grid_Model::normalize_grid_data($raw_grid_data);

$profile = ($is_edit && !empty($grid['profile_data']) && is_array($grid['profile_data'])) ? $grid['profile_data'] : array();
$profile = wp_parse_args($profile, array(
    'username' => '',
    'display_name' => '',
    'bio' => '',
    'website' => '',
    'avatar_url' => '',
    'followers' => 0,
    'following' => 0,
));

$profile_url = $is_edit ? home_url('/instagram-grid/' . intval($grid_id) . '/') : '';
?>

<div class="wrap">
    <h1><?php echo $is_edit ? __('Edit Profile', 'instagram-grid-preview') : __('Add New Profile', 'instagram-grid-preview'); ?></h1>

    <form id="igp-grid-form" method="post">
        <input type="hidden" id="grid-id" value="<?php echo esc_attr($grid_id); ?>">

        <h2><?php esc_html_e('Profile', 'instagram-grid-preview'); ?></h2>
        <p class="description"><?php esc_html_e('These details are shown at the top of the public Instagram-style profile page.', 'instagram-grid-preview'); ?></p>

        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row"><?php esc_html_e('Avatar', 'instagram-grid-preview'); ?></th>
                    <td>
                        <div class="igp-avatar-field">
                            <div class="igp-avatar-preview <?php echo empty($profile['avatar_url']) ? 'is-empty' : ''; ?>" id="igp-avatar-preview">
                                <?php if (!empty($profile['avatar_url'])) : ?>
                                    <img src="<?php echo esc_url($profile['avatar_url']); ?>" alt="">
                                <?php else : ?>
                                    <span class="dashicons dashicons-format-image"></span>
                                <?php endif; ?>
                            </div>
                            <div class="igp-avatar-actions">
                                <button type="button" class="button" id="igp-select-avatar"><?php esc_html_e('Select image', 'instagram-grid-preview'); ?></button>
                                <button type="button" class="button-link" id="igp-remove-avatar" <?php echo empty($profile['avatar_url']) ? 'style="display:none"' : ''; ?>><?php esc_html_e('Remove', 'instagram-grid-preview'); ?></button>
                            </div>
                        </div>
                        <input type="hidden" id="igp-profile-avatar-url" value="<?php echo esc_attr($profile['avatar_url']); ?>">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="igp-profile-username"><?php esc_html_e('Username', 'instagram-grid-preview'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="igp-profile-username" value="<?php echo esc_attr($profile['username']); ?>" class="regular-text" placeholder="jenssage.de">
                        <p class="description"><?php esc_html_e('The @handle shown at the top of the profile.', 'instagram-grid-preview'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="igp-profile-display-name"><?php esc_html_e('Display name', 'instagram-grid-preview'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="igp-profile-display-name" value="<?php echo esc_attr($profile['display_name']); ?>" class="regular-text" placeholder="Jens Sage">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="igp-profile-bio"><?php esc_html_e('Bio', 'instagram-grid-preview'); ?></label>
                    </th>
                    <td>
                        <textarea id="igp-profile-bio" rows="3" class="large-text" placeholder="Kuchenopa with a camera 📷"><?php echo esc_textarea($profile['bio']); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="igp-profile-website"><?php esc_html_e('Website', 'instagram-grid-preview'); ?></label>
                    </th>
                    <td>
                        <input type="url" id="igp-profile-website" value="<?php echo esc_attr($profile['website']); ?>" class="regular-text" placeholder="https://example.com">
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e('Stats', 'instagram-grid-preview'); ?></th>
                    <td>
                        <label for="igp-profile-followers" style="margin-right:16px">
                            <?php esc_html_e('Followers', 'instagram-grid-preview'); ?>
                            <input type="number" id="igp-profile-followers" value="<?php echo esc_attr($profile['followers']); ?>" min="0" class="small-text">
                        </label>
                        <label for="igp-profile-following">
                            <?php esc_html_e('Following', 'instagram-grid-preview'); ?>
                            <input type="number" id="igp-profile-following" value="<?php echo esc_attr($profile['following']); ?>" min="0" class="small-text">
                        </label>
                        <p class="description"><?php esc_html_e('The number of posts is calculated automatically from the grid.', 'instagram-grid-preview'); ?></p>
                    </td>
                </tr>
            </tbody>
        </table>

        <h2><?php esc_html_e('Grid Settings', 'instagram-grid-preview'); ?></h2>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="grid-name"><?php esc_html_e('Internal name', 'instagram-grid-preview'); ?></label>
                    </th>
                    <td>
                        <input type="text" id="grid-name" name="grid_name" value="<?php echo esc_attr($grid_name); ?>" class="regular-text" required>
                        <p class="description"><?php esc_html_e('Used in the admin list and as a fallback for the profile name.', 'instagram-grid-preview'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="grid-description"><?php esc_html_e('Description', 'instagram-grid-preview'); ?></label>
                    </th>
                    <td>
                        <textarea id="grid-description" name="grid_description" rows="3" class="large-text"><?php echo esc_textarea($grid_description); ?></textarea>
                        <p class="description"><?php esc_html_e('Optional description. Used as the bio fallback.', 'instagram-grid-preview'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="grid-columns"><?php esc_html_e('Columns', 'instagram-grid-preview'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="grid-columns" name="grid_columns" value="<?php echo esc_attr($grid_columns); ?>" min="1" max="6" class="small-text">
                        <p class="description"><?php esc_html_e('Number of columns (1-6). Default is 3 for the Instagram-style layout.', 'instagram-grid-preview'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="grid-rows"><?php esc_html_e('Rows', 'instagram-grid-preview'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="grid-rows" name="grid_rows" value="<?php echo esc_attr($grid_rows); ?>" min="1" max="10" class="small-text">
                        <p class="description"><?php esc_html_e('Number of rows (1-10).', 'instagram-grid-preview'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="grid-aspect-ratio"><?php esc_html_e('Aspect ratio', 'instagram-grid-preview'); ?></label>
                    </th>
                    <td>
                        <select id="grid-aspect-ratio" name="grid_aspect_ratio" class="regular-text">
                            <option value="1:1" <?php selected($grid_aspect_ratio, '1:1'); ?>><?php esc_html_e('1:1 (Square)', 'instagram-grid-preview'); ?></option>
                            <option value="3:4" <?php selected($grid_aspect_ratio, '3:4'); ?>><?php esc_html_e('3:4 (Portrait)', 'instagram-grid-preview'); ?></option>
                        </select>
                        <p class="description"><?php esc_html_e('Choose the aspect ratio for grid tiles. 1:1 for the classic Instagram grid, 3:4 for the newer layout.', 'instagram-grid-preview'); ?></p>
                    </td>
                </tr>
            </tbody>
        </table>

        <h2><?php esc_html_e('Posts', 'instagram-grid-preview'); ?></h2>
        <p><?php esc_html_e('Click an empty tile to add images or a video. Click a filled tile to edit the caption, media type, likes and comments. Drag and drop to reorder.', 'instagram-grid-preview'); ?></p>
        <p class="description"><?php esc_html_e('Videos have no native poster image in WordPress/ClassicPress. Open a post and use the camera button on a video slide to choose the poster image shown in the grid and viewer.', 'instagram-grid-preview'); ?></p>

        <div id="igp-grid-container">
            <div id="igp-grid-editor" class="igp-grid-editor" data-columns="<?php echo esc_attr($grid_columns); ?>" data-rows="<?php echo esc_attr($grid_rows); ?>" data-aspect-ratio="<?php echo esc_attr($grid_aspect_ratio); ?>">
                <!-- Grid cells will be generated by JavaScript -->
            </div>
        </div>

        <p class="submit">
            <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php echo $is_edit ? __('Update Profile', 'instagram-grid-preview') : __('Save Profile', 'instagram-grid-preview'); ?>">
            <a href="<?php echo admin_url('admin.php?page=instagram-grids'); ?>" class="button button-secondary">
                <?php esc_html_e('Cancel', 'instagram-grid-preview'); ?>
            </a>
        </p>

        <div class="igp-profile-url-display" id="igp-profile-url-display" <?php echo $is_edit ? '' : 'style="display:none"'; ?>>
            <h3><?php esc_html_e('Public profile page', 'instagram-grid-preview'); ?></h3>
            <p><?php esc_html_e('This profile is live at the URL below. Clicking a tile opens the Instagram-style post viewer.', 'instagram-grid-preview'); ?></p>
            <code id="igp-profile-url"><?php echo esc_html($profile_url); ?></code>
            <button type="button" class="button button-small igp-copy-url" data-url="<?php echo esc_attr($profile_url); ?>">
                <?php esc_html_e('Copy URL', 'instagram-grid-preview'); ?>
            </button>
            <a href="<?php echo esc_url($profile_url); ?>" target="_blank" rel="noopener noreferrer" class="button button-small" id="igp-view-profile" <?php echo $is_edit ? '' : 'style="display:none"'; ?>>
                <?php esc_html_e('View profile', 'instagram-grid-preview'); ?>
            </a>
        </div>
    </form>
</div>

<script>
// Pass grid + profile data to JavaScript
window.igpGridData = <?php echo wp_json_encode($grid_data); ?>;
window.igpProfileData = <?php echo wp_json_encode($profile); ?>;
window.igpIsEdit = <?php echo $is_edit ? 'true' : 'false'; ?>;
window.igpProfileBase = <?php echo wp_json_encode(home_url('/instagram-grid/')); ?>;
</script>
