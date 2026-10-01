<?php
/**
 * Standalone Instagram-style profile page.
 *
 * Loaded via template_include for the /instagram-grid/{id} route.
 *
 * @package InstagramGridPreview
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

$grid = get_query_var('igp_profile_grid');

if (empty($grid)) {
    wp_die(esc_html__('Profile not found.', 'instagram-grid-preview'));
}

$profile = IGP_Public::get_profile($grid);
$posts = IGP_Grid_Model::normalize_grid_data($grid['grid_data']);

$date = date_i18n(get_option('date_format'), strtotime($grid['created_at']));
$posts_json = array();
$needs_hls = false;

foreach ($posts as $index => $post) {
    $media = array();
    foreach ($post['media'] as $slide) {
        $item = array(
            'type' => $slide['type'],
            'source' => isset($slide['source']) ? $slide['source'] : 'upload',
            'bunny_id' => isset($slide['bunny_id']) ? $slide['bunny_id'] : '',
            'url' => $slide['url'],
            'display_url' => IGP_Public::get_display_url($slide),
            'display_srcset' => IGP_Public::get_display_srcset($slide),
            'thumbnail_url' => $slide['thumbnail_url'],
            'alt' => $slide['alt'],
        );

        if ('bunny' === $item['source'] && !empty($item['bunny_id'])) {
            $playback = IGP_Bunny::playback($item['bunny_id']);
            $item['hls'] = isset($playback['hls']) ? $playback['hls'] : '';
            $item['mp4'] = isset($playback['mp4']) ? $playback['mp4'] : '';
            $item['thumbnail_url'] = IGP_Bunny::resolve_poster($slide);
            if (!empty($item['hls'])) {
                $needs_hls = true;
            }
        }

        $media[] = $item;
    }

    $posts_json[$index] = array(
        'index' => $index,
        'media' => $media,
        'caption' => $post['caption'],
        'likes' => $post['likes'],
        'comments' => $post['comments'],
        'link_url' => $post['link_url'],
        'media_type' => $post['media_type'],
        'date' => $date,
    );
}

$initial = mb_substr($profile['display_name'] !== '' ? $profile['display_name'] : $profile['username'], 0, 1);

$igp_data = array(
    'id' => $profile['id'],
    'profile' => $profile,
    'posts' => $posts_json,
    'date' => $date,
    'i18n' => array(
        'likes' => __('likes', 'instagram-grid-preview'),
        'comments' => __('comments', 'instagram-grid-preview'),
        'liked_by' => __('Liked by', 'instagram-grid-preview'),
        'and_others' => __('and others', 'instagram-grid-preview'),
        'add_comment' => __('Add a comment...', 'instagram-grid-preview'),
        'view_all' => __('View all', 'instagram-grid-preview'),
        'post' => __('Post', 'instagram-grid-preview'),
        'prev' => __('Previous', 'instagram-grid-preview'),
        'next' => __('Next', 'instagram-grid-preview'),
        'close' => __('Close', 'instagram-grid-preview'),
    ),
);
?><!doctype html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="robots" content="noindex, nofollow, noarchive, nosnippet, noimageindex">
<meta name="googlebot" content="noindex, nofollow">
<meta name="theme-color" content="#ffffff">
<title><?php echo esc_html($profile['username']); ?> &bull; Instagram</title>
<link rel="stylesheet" href="<?php echo esc_url(IGP_PLUGIN_URL . 'public/css/igp-public.css?ver=' . IGP_VERSION); ?>">
<link rel="stylesheet" href="<?php echo esc_url(IGP_PLUGIN_URL . 'public/css/igp-profile.css?ver=' . IGP_VERSION); ?>">
</head>
<body class="igp-body">

<div class="igp-app">

    <header class="igp-topbar">
        <span class="igp-topbar-side" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
        </span>
        <span class="igp-topbar-title">
            <?php echo esc_html($profile['username']); ?>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M6 9l6 6 6-6"/></svg>
        </span>
        <span class="igp-topbar-side" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>
        </span>
    </header>

    <header class="igp-header">
        <div class="igp-avatar">
            <?php if (!empty($profile['avatar_url'])) : ?>
                <img src="<?php echo esc_url($profile['avatar_url']); ?>" alt="<?php echo esc_attr($profile['username']); ?>">
            <?php else : ?>
                <span class="igp-avatar-fallback"><?php echo esc_html($initial); ?></span>
            <?php endif; ?>
        </div>

        <section class="igp-header-info">
            <div class="igp-username-row">
                <h1 class="igp-username"><?php echo esc_html($profile['username']); ?></h1>
                <div class="igp-header-buttons">
                    <a class="igp-btn igp-btn--primary" href="#"><?php esc_html_e('Follow', 'instagram-grid-preview'); ?></a>
                    <a class="igp-btn" href="#"><?php esc_html_e('Message', 'instagram-grid-preview'); ?></a>
                </div>
            </div>

            <ul class="igp-stats">
                <li><strong><?php echo esc_html(IGP_Public::format_count($profile['posts_count'])); ?></strong> <?php esc_html_e('posts', 'instagram-grid-preview'); ?></li>
                <li><strong><?php echo esc_html(IGP_Public::format_count($profile['followers'])); ?></strong> <?php esc_html_e('followers', 'instagram-grid-preview'); ?></li>
                <li><strong><?php echo esc_html(IGP_Public::format_count($profile['following'])); ?></strong> <?php esc_html_e('following', 'instagram-grid-preview'); ?></li>
            </ul>

            <div class="igp-bio">
                <div class="igp-display-name"><?php echo esc_html($profile['display_name']); ?></div>
                <?php if (!empty($profile['bio'])) : ?>
                    <div class="igp-bio-text"><?php echo nl2br(esc_html($profile['bio'])); ?></div>
                <?php endif; ?>
                <?php if (!empty($profile['website'])) : ?>
                    <a class="igp-website" href="<?php echo esc_url($profile['website']); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html(preg_replace('#^https?://#', '', untrailingslashit($profile['website']))); ?></a>
                <?php endif; ?>
            </div>

            <div class="igp-header-buttons igp-header-buttons--mobile">
                <a class="igp-btn igp-btn--primary" href="#"><?php esc_html_e('Follow', 'instagram-grid-preview'); ?></a>
                <a class="igp-btn" href="#"><?php esc_html_e('Message', 'instagram-grid-preview'); ?></a>
            </div>
        </section>
    </header>

    <nav class="igp-tabs" aria-label="<?php esc_attr_e('Profile sections', 'instagram-grid-preview'); ?>">
        <button type="button" class="igp-tab is-active">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            <span><?php esc_html_e('Posts', 'instagram-grid-preview'); ?></span>
        </button>
        <button type="button" class="igp-tab">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M10 8.5l6 3.5-6 3.5z" fill="currentColor" stroke="none"/></svg>
            <span><?php esc_html_e('Reels', 'instagram-grid-preview'); ?></span>
        </button>
        <button type="button" class="igp-tab">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            <span><?php esc_html_e('Tagged', 'instagram-grid-preview'); ?></span>
        </button>
    </nav>

    <main class="igp-feed">
        <?php echo IGP_Public::render_grid($grid); ?>
    </main>

</div>

<script type="application/json" id="igp-profile-data"><?php echo wp_json_encode($igp_data); ?></script>
<?php if ($needs_hls) : ?>
<script src="https://cdn.jsdelivr.net/npm/hls.js@1.5.17/dist/hls.min.js" integrity="sha384-9v3HcdYrO3D+OPDTjZ40RXocgE4GtXVCd3/mCS62JsM93JXgI1afJVuwjFvsu6ni" crossorigin="anonymous"></script>
<?php endif; ?>
<script src="<?php echo esc_url(IGP_PLUGIN_URL . 'public/js/igp-profile.js?ver=' . IGP_VERSION); ?>"></script>
</body>
</html>
