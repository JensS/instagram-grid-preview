<?php
/**
 * Bunny Stream integration.
 *
 * Reads the same options as the "Bunny Stream Shortcodes" plugin
 * (`bunny_stream_shortcodes_options`) so the site only has to be configured
 * once. When the theme's `Slay_Element_Bunny` helper is available its URL
 * builders are reused so token signing matches the rest of the site.
 *
 * @package InstagramGridPreview
 */

/**
 * Bunny Stream helper.
 */
class IGP_Bunny {

    /**
     * Get the shared Bunny Stream options.
     *
     * @since    1.3.0
     * @return   array
     */
    public static function get_options() {
        static $options = null;

        if (null === $options) {
            $options = get_option('bunny_stream_shortcodes_options', array());
            if (!is_array($options)) {
                $options = array();
            }
        }

        return $options;
    }

    /**
     * Whether Bunny Stream is configured well enough to build URLs.
     *
     * @since    1.3.0
     * @return   bool
     */
    public static function is_configured() {
        return '' !== self::cdn_hostname();
    }

    /**
     * The Bunny pull-zone hostname.
     *
     * @since    1.3.0
     * @return   string
     */
    public static function cdn_hostname() {
        $options = self::get_options();

        if (!empty($options['cdn_hostname'])) {
            return $options['cdn_hostname'];
        }

        if (!empty($options['default_library_id'])) {
            return 'vz-' . $options['default_library_id'] . '.b-cdn.net';
        }

        return '';
    }

    /**
     * Whether a value looks like a Bunny Stream video GUID.
     *
     * @since    1.3.0
     * @param    string    $video_id
     * @return   bool
     */
    public static function is_valid_video_id($video_id) {
        return is_string($video_id) && 1 === preg_match('/^[a-f0-9-]{16,64}$/i', $video_id);
    }

    /**
     * Extract a video GUID from a raw ID or any Bunny URL.
     *
     * @since    1.3.0
     * @param    string    $input
     * @return   string    GUID or empty string
     */
    public static function video_id_from_input($input) {
        $input = trim((string) $input);

        if ('' === $input) {
            return '';
        }

        // Plain GUID.
        if (self::is_valid_video_id($input)) {
            return $input;
        }

        // UUID anywhere in the string (embed URLs, player URLs, …).
        if (preg_match('/([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})/i', $input, $matches)) {
            return $matches[1];
        }

        // /embed/{library}/{guid}
        if (preg_match('#/embed/\d+/([a-f0-9-]{16,64})#i', $input, $matches)) {
            return $matches[1];
        }

        return '';
    }

    /**
     * Thumbnail (poster) URL for a video.
     *
     * @since    1.3.0
     * @param    string    $video_id
     * @return   string
     */
    public static function thumbnail_url($video_id) {
        if (self::theme_helper_has('thumbnail_url')) {
            return Slay_Element_Bunny::thumbnail_url($video_id);
        }

        return self::signed_file_url($video_id, 'thumbnail.jpg');
    }

    /**
     * HLS playlist URL for a video.
     *
     * @since    1.3.0
     * @param    string    $video_id
     * @return   string
     */
    public static function hls_url($video_id) {
        if (self::theme_helper_has('hls_url')) {
            return Slay_Element_Bunny::hls_url($video_id);
        }

        return self::signed_file_url($video_id, 'playlist.m3u8');
    }

    /**
     * MP4 fallback URL for a video.
     *
     * @since    1.3.0
     * @param    string    $video_id
     * @param    int       $resolution
     * @return   string
     */
    public static function mp4_url($video_id, $resolution = 720) {
        if (self::theme_helper_has('mp4_url')) {
            return Slay_Element_Bunny::mp4_url($video_id, $resolution);
        }

        return self::signed_file_url($video_id, sprintf('play_%dp.mp4', absint($resolution)));
    }

    /**
     * Playback sources for a video.
     *
     * @since    1.3.0
     * @param    string    $video_id
     * @return   array     array('hls' => …, 'mp4' => …)
     */
    public static function playback($video_id) {
        if (!self::is_valid_video_id($video_id)) {
            return array();
        }

        $hls = self::hls_url($video_id);
        $mp4 = self::mp4_url($video_id);

        if ('' === $hls && '' === $mp4) {
            return array();
        }

        return array(
            'hls' => $hls,
            'mp4' => $mp4,
        );
    }

    /**
     * Resolve the poster for a slide, preferring a fresh (signed) Bunny
     * thumbnail unless a custom poster was set.
     *
     * @since    1.3.0
     * @param    array    $slide
     * @return   string
     */
    public static function resolve_poster($slide) {
        // An explicitly chosen custom poster always wins.
        if (!empty($slide['poster_url'])) {
            return $slide['poster_url'];
        }

        $video_id = isset($slide['bunny_id']) ? $slide['bunny_id'] : '';
        $custom = isset($slide['thumbnail_url']) ? $slide['thumbnail_url'] : '';

        if ($video_id) {
            $bunny = self::thumbnail_url($video_id);
            if ($bunny && ('' === $custom || self::is_bunny_url($custom))) {
                return $bunny;
            }
        }

        return $custom;
    }

    /**
     * Whether a URL points at the Bunny pull zone.
     *
     * @since    1.3.0
     * @param    string    $url
     * @return   bool
     */
    private static function is_bunny_url($url) {
        $cdn = self::cdn_hostname();
        return $cdn && false !== strpos($url, $cdn);
    }

    /**
     * Whether the theme's Bunny helper exposes a given method.
     *
     * @since    1.3.0
     * @param    string    $method
     * @return   bool
     */
    private static function theme_helper_has($method) {
        return class_exists('Slay_Element_Bunny') && method_exists('Slay_Element_Bunny', $method);
    }

    /**
     * Build a CDN file URL, signed when CDN security is enabled.
     *
     * @since    1.3.0
     * @param    string    $video_id
     * @param    string    $file
     * @return   string
     */
    private static function signed_file_url($video_id, $file) {
        $cdn = self::cdn_hostname();

        if ('' === $cdn || empty($video_id)) {
            return '';
        }

        $url = sprintf('https://%s/%s/%s', $cdn, rawurlencode($video_id), $file);

        $options = self::get_options();
        if (!empty($options['cdn_security_key'])) {
            $url = self::sign_cdn_url($url, $video_id, $options);
        }

        return $url;
    }

    /**
     * Sign a CDN URL with Bunny token authentication.
     *
     * @since    1.3.0
     * @param    string    $url
     * @param    string    $video_id
     * @param    array     $options
     * @return   string
     */
    private static function sign_cdn_url($url, $video_id, $options) {
        $key = $options['cdn_security_key'];
        $ttl = isset($options['default_token_ttl']) ? intval($options['default_token_ttl']) : 3600;
        $expires = time() + $ttl;
        $dir = '/' . $video_id . '/';

        $hash = hash('sha256', $key . $dir . $expires, true);
        $token = str_replace(array('+', '/', '='), array('-', '_', ''), base64_encode($hash));

        $separator = (false !== strpos($url, '?')) ? '&' : '?';

        return $url . $separator . sprintf(
            'token=%s&token_path=%s&expires=%d',
            $token,
            urlencode($dir),
            $expires
        );
    }
}
