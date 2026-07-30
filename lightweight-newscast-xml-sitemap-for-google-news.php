<?php
/**
 * Plugin Name: Lightweight Newscast XML Sitemap For Google News
 * Plugin URI: https://wordpress.org/plugins/lightweight-newscast-xml-sitemap-for-google-news/
 * Description: Generates a Google News compatible XML sitemap for WordPress sites to be submitted to Google Search Console for better news content indexing.
 * Version: 1.3.1
 * Author: Gunjan Jaswal
 * Author URI: https://gunjanjaswal.me
 * Donate link: https://ko-fi.com/gunjanjaswal
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: lightweight-newscast-xml-sitemap-for-google-news
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Tested up to: 7.0
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('NEWSSITEMAP_VERSION', '1.3.1');

// Transient key used to cache the rendered sitemap output.
define('NEWSSITEMAP_CACHE_KEY', 'newssitemap_cache');

// Post meta key used to flag a post as excluded from the news sitemap.
define('NEWSSITEMAP_EXCLUDE_META', '_newssitemap_exclude');

/**
 * The code that runs during plugin activation.
 */
function lnxsfgn_activate_news_sitemap() {
    // Set default options
    $default_options = array(
        'post_types' => array('post'),
        'categories' => array(),
        'publication_name' => get_bloginfo('name'),
        'publication_language' => 'en',
        'max_age' => 48, // Maximum age of posts in hours (Google News standard)
        'max_posts' => 1000 // Maximum number of posts in sitemap
    );

    add_option('newssitemap_options', $default_options);

    // Register rewrite rules
    add_rewrite_rule('^lightweight-newscast-xml-sitemap-for-google-news\.xml$', 'index.php?newssitemap_xml=1', 'top');
    add_rewrite_tag('%newssitemap_xml%', '([0-9]+)');

    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * The code that runs during plugin deactivation.
 */
function lnxsfgn_deactivate_news_sitemap() {
    // Clear any cached sitemap output.
    delete_transient(NEWSSITEMAP_CACHE_KEY);

    // Flush rewrite rules to clean up
    flush_rewrite_rules();
}

register_activation_hook(__FILE__, 'lnxsfgn_activate_news_sitemap');
register_deactivation_hook(__FILE__, 'lnxsfgn_deactivate_news_sitemap');

/**
 * Main plugin class
 */
class NewsSitemap_Generator {

    /**
     * Constructor
     */
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));

        // Add plugin action links
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_plugin_action_links'));

        // Add plugin row meta (Contact Developer)
        add_filter('plugin_row_meta', array($this, 'add_plugin_row_meta'), 10, 2);

        // Add rewrite rules and query vars
        $this->add_rewrite_rules();
        $this->add_query_var();

        // Hook into template_redirect to maybe generate sitemap
        add_action('template_redirect', array($this, 'maybe_generate_sitemap'));

        // Advertise the sitemap in robots.txt.
        add_filter('robots_txt', array($this, 'add_sitemap_to_robots'), 10, 2);

        // Bust the cache whenever content changes.
        add_action('save_post', array($this, 'flush_cache'));
        add_action('deleted_post', array($this, 'flush_cache'));
        add_action('trashed_post', array($this, 'flush_cache'));
        add_action('transition_post_status', array($this, 'flush_cache_on_status_change'), 10, 3);

        // Per-post "exclude from sitemap" controls.
        add_action('add_meta_boxes', array($this, 'add_exclude_meta_box'));
        add_action('save_post', array($this, 'save_exclude_meta_box'));

        // Register the WP-CLI command.
        if (defined('WP_CLI') && WP_CLI) {
            WP_CLI::add_command('news-sitemap', 'NewsSitemap_CLI_Command');
        }
    }

    /**
     * Initialize plugin
     */
    public function init() {
        // Text domain loading is handled automatically by WordPress.org for hosted plugins
        // load_plugin_textdomain() is no longer needed for WordPress.org plugins since WP 4.6
    }

    /**
     * Add direct URL handling without relying on rewrite rules
     */
    public function add_rewrite_rules() {
        // We'll use a direct template_redirect hook with very high priority
        // This approach doesn't rely on rewrite rules that can be overridden by SEO plugins
        add_action('template_redirect', array($this, 'intercept_sitemap_request'), 1); // Priority 1 to run before anything else

        // For backward compatibility, still register the rewrite rules
        add_action('init', function() {
            add_rewrite_rule('^lightweight-newscast-xml-sitemap-for-google-news\.xml$', 'index.php?newssitemap_xml=1', 'top');
            add_rewrite_tag('%newssitemap_xml%', '([0-9]+)');
        }, 999999);
    }

    /**
     * Add query var
     */
    public function add_query_var() {
        add_filter('query_vars', function($vars) {
            $vars[] = 'news_sitemap_google_news';
            $vars[] = 'newssitemap_xml';
            $vars[] = 'newssitemap_page';
            return $vars;
        });
    }

    /**
     * Direct intercept of sitemap requests
     * This method runs on template_redirect with priority 1
     * and directly checks the request URI without relying on rewrite rules
     */
    public function intercept_sitemap_request() {
        // Get the request URI
        $request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';

        // Get the path part of the URI (remove query string)
        $path = wp_parse_url($request_uri, PHP_URL_PATH);

        // Check if this is a direct request for the sitemap XML
        if ($path === '/lightweight-newscast-xml-sitemap-for-google-news.xml' ||
            preg_match('#/lightweight-newscast-xml-sitemap-for-google-news\.xml$#i', $path)) {
            // This is our sitemap request - generate it directly
            $this->generate_sitemap();
            exit;
        }
    }

    /**
     * Maybe generate sitemap
     * This is the fallback method that uses query vars
     */
    public function maybe_generate_sitemap() {
        global $wp_query;

        // Check if we need to generate the sitemap
        // Note: No nonce verification needed for public sitemap URLs
        if (isset($wp_query->query_vars['newssitemap_xml']) ||
            isset($wp_query->query_vars['news_sitemap_google_news']) ||
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public sitemap access
            get_query_var('news_sitemap_google_news') == '1') {
            $this->generate_sitemap();
            exit;
        }
    }

    /**
     * Read the requested sitemap page from the request.
     *
     * @return int Page number (0 means the base/index request).
     */
    private function get_requested_page() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public sitemap access, read-only.
        $page = isset($_GET['newssitemap_page']) ? absint(wp_unslash($_GET['newssitemap_page'])) : 0;
        return $page;
    }

    /**
     * Resolve and merge saved options with their defaults.
     *
     * @return array Normalized options.
     */
    private function get_resolved_options() {
        $options = get_option('newssitemap_options', array());

        $publication_name = !empty($options['publication_name']) ? $options['publication_name'] : get_bloginfo('name');
        if (empty($publication_name)) {
            $publication_name = 'Web';
        }

        return array(
            'post_types'           => !empty($options['post_types']) ? (array) $options['post_types'] : array('post'),
            'categories'           => !empty($options['categories']) ? (array) $options['categories'] : array(),
            'exclude_categories'   => !empty($options['exclude_categories']) ? (array) $options['exclude_categories'] : array(),
            'exclude_tags'         => !empty($options['exclude_tags']) ? (array) $options['exclude_tags'] : array(),
            'publication_name'     => $publication_name,
            'publication_language' => !empty($options['publication_language']) ? $options['publication_language'] : 'en',
            'max_age'              => !empty($options['max_age']) ? intval($options['max_age']) : 48,
            'max_posts'            => !empty($options['max_posts']) ? intval($options['max_posts']) : 1000,
            // Default both on; a saved settings form stores '0' when unchecked.
            'respect_noindex'      => !isset($options['respect_noindex']) ? 1 : (int) $options['respect_noindex'],
            'use_seo_title'        => !isset($options['use_seo_title']) ? 1 : (int) $options['use_seo_title'],
        );
    }

    /**
     * Build the WP_Query arguments for the news window.
     *
     * @param array $options  Resolved options.
     * @param int   $page     Page number (1-based) for pagination.
     * @param int   $per_page Posts per page.
     * @return array WP_Query arguments.
     */
    private function get_query_args($options, $page = 1, $per_page = 1000) {
        // Google News only accepts articles from the last 48 hours.
        // Build the threshold in UTC and compare it against post_date_gmt so the
        // window is a true rolling 48 hours regardless of the site's timezone.
        $google_max_age = min($options['max_age'], 48);
        $date_threshold = gmdate('Y-m-d H:i:s', time() - ($google_max_age * HOUR_IN_SECONDS));

        $args = array(
            'post_type'      => $options['post_types'],
            'post_status'    => 'publish',
            'posts_per_page' => $per_page,
            'paged'          => max(1, $page),
            'orderby'        => 'date',
            'order'          => 'DESC',
            'ignore_sticky_posts' => true,
            'date_query'     => array(
                'column' => 'post_date_gmt',
                array(
                    'after'     => $date_threshold,
                    'inclusive' => true,
                ),
            ),
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Required to honor the exclusion opt-outs; output is cached.
            'meta_query'     => $this->build_meta_query($options),
        );

        if (!empty($options['categories'])) {
            $args['cat'] = implode(',', array_map('absint', $options['categories']));
        }

        if (!empty($options['exclude_categories'])) {
            $args['category__not_in'] = array_map('absint', $options['exclude_categories']);
        }

        if (!empty($options['exclude_tags'])) {
            $args['tag__not_in'] = array_map('absint', $options['exclude_tags']);
        }

        // Multilingual: a Google News sitemap must list articles from every
        // language in one file (each <url> carries its own <news:language>).
        // Polylang otherwise filters WP_Query to the current front-end
        // language, which silently drops posts in other languages. An empty
        // string tells Polylang to return all languages.
        if (function_exists('pll_languages_list')) {
            $args['lang'] = '';
        }

        /**
         * Filter the WP_Query arguments used to build the news sitemap.
         *
         * @param array $args    Query arguments.
         * @param array $options Resolved plugin options.
         */
        return apply_filters('newssitemap_query_args', $args, $options);
    }

    /**
     * Build the meta_query that enforces the per-post opt-out and, when enabled,
     * the RankMath / Yoast "noindex" robots setting.
     *
     * Kept in one place so the health count and the rendered sitemap always
     * apply exactly the same eligibility rules.
     *
     * @param array $options Resolved options.
     * @return array meta_query clauses.
     */
    private function build_meta_query($options) {
        // Honor the plugin's own per-post "exclude from sitemap" checkbox.
        $clauses = array(
            'relation' => 'AND',
            array(
                'relation' => 'OR',
                array(
                    'key'     => NEWSSITEMAP_EXCLUDE_META,
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key'     => NEWSSITEMAP_EXCLUDE_META,
                    'value'   => '1',
                    'compare' => '!=',
                ),
            ),
        );

        // Drop posts marked noindex in a supported SEO plugin.
        if (!empty($options['respect_noindex']) && apply_filters('newssitemap_respect_noindex', true)) {
            // Yoast SEO: '1' means noindex.
            $clauses[] = array(
                'relation' => 'OR',
                array(
                    'key'     => '_yoast_wpseo_meta-robots-noindex',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key'     => '_yoast_wpseo_meta-robots-noindex',
                    'value'   => '1',
                    'compare' => '!=',
                ),
            );
            // Rank Math stores robots as a serialized array; 'noindex' appears in it.
            $clauses[] = array(
                'relation' => 'OR',
                array(
                    'key'     => 'rank_math_robots',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key'     => 'rank_math_robots',
                    'value'   => 'noindex',
                    'compare' => 'NOT LIKE',
                ),
            );
        }

        return $clauses;
    }

    /**
     * Generate sitemap
     *
     * Serves either a single <urlset>, a paginated child <urlset>, or a
     * <sitemapindex> when the number of eligible posts exceeds one page.
     */
    public function generate_sitemap() {
        // Set content type
        header('Content-Type: application/xml; charset=utf-8');

        $page = $this->get_requested_page();

        // Serve from cache when available.
        $cache = get_transient(NEWSSITEMAP_CACHE_KEY);
        $cache = is_array($cache) ? $cache : array();
        $cache_id = 'p' . $page;
        if (isset($cache[$cache_id])) {
            echo $cache[$cache_id]; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Pre-escaped cached XML.
            return false;
        }

        $options  = $this->get_resolved_options();
        $per_page = max(1, min(1000, $options['max_posts']));

        // Count eligible posts to decide between a single sitemap and an index.
        $count_query = new WP_Query(array_merge(
            $this->get_query_args($options, 1, $per_page),
            array('fields' => 'ids', 'posts_per_page' => $per_page, 'paged' => 1)
        ));
        $total = (int) $count_query->found_posts;
        $pages = max(1, (int) ceil($total / $per_page));

        if ($page === 0 && $pages > 1) {
            // Base URL with multiple pages -> serve a sitemap index.
            $output = $this->render_sitemap_index($pages);
        } else {
            // Single sitemap or a specific child page.
            $current = $page === 0 ? 1 : min($page, $pages);
            $output  = $this->render_urlset($options, $current, $per_page);
        }

        // Cache the rendered output. Short TTL because the 48-hour window
        // means posts roll out of scope over time even without edits.
        $ttl = (int) apply_filters('newssitemap_cache_ttl', 15 * MINUTE_IN_SECONDS);
        if ($ttl > 0) {
            $cache[$cache_id] = $output;
            set_transient(NEWSSITEMAP_CACHE_KEY, $cache, $ttl);
            // Record when the cache was last built (for the admin health panel).
            set_transient(NEWSSITEMAP_CACHE_KEY . '_built', time(), $ttl);
        }

        echo $output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- XML built with esc_* helpers below.

        return false;
    }

    /**
     * Render a <sitemapindex> pointing at the paginated child sitemaps.
     *
     * @param int $pages Total number of pages.
     * @return string XML.
     */
    private function render_sitemap_index($pages) {
        $base = home_url('/lightweight-newscast-xml-sitemap-for-google-news.xml');
        $now  = get_date_from_gmt(gmdate('Y-m-d H:i:s'), 'c');

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        for ($i = 1; $i <= $pages; $i++) {
            $loc = add_query_arg('newssitemap_page', $i, $base);
            $xml .= "  <sitemap>\n";
            $xml .= "    <loc>" . esc_url($loc) . "</loc>\n";
            $xml .= "    <lastmod>" . esc_html($now) . "</lastmod>\n";
            $xml .= "  </sitemap>\n";
        }

        $xml .= '</sitemapindex>';

        return $xml;
    }

    /**
     * Render a single <urlset> for the given page.
     *
     * @param array $options  Resolved options.
     * @param int   $page     Page number (1-based).
     * @param int   $per_page Posts per page.
     * @return string XML.
     */
    private function render_urlset($options, $page, $per_page) {
        $query = new WP_Query($this->get_query_args($options, $page, $per_page));

        $include_images   = (bool) apply_filters('newssitemap_include_images', true);
        $include_keywords = (bool) apply_filters('newssitemap_include_keywords', true);

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

        foreach ($query->posts as $post) {
            // Get post date in WordPress timezone and convert to ISO 8601 format
            $post_timestamp = get_post_time('U', false, $post);
            $post_date  = get_date_from_gmt(gmdate('Y-m-d H:i:s', $post_timestamp), 'c');
            $post_url   = get_permalink($post);
            $post_title = $this->get_post_news_title($post, $options);
            $language   = $this->get_post_language($post, $options['publication_language']);

            $xml .= "  <url>\n";
            $xml .= "    <loc>" . esc_url($post_url) . "</loc>\n";
            $xml .= "    <news:news>\n";
            $xml .= "      <news:publication>\n";
            $xml .= "        <news:name>" . esc_html($options['publication_name']) . "</news:name>\n";
            $xml .= "        <news:language>" . esc_html($language) . "</news:language>\n";
            $xml .= "      </news:publication>\n";
            $xml .= "      <news:publication_date>" . esc_html($post_date) . "</news:publication_date>\n";
            $xml .= "      <news:title>" . esc_html($post_title) . "</news:title>\n";

            if ($include_keywords) {
                $keywords = $this->get_post_keywords($post);
                if (!empty($keywords)) {
                    $xml .= "      <news:keywords>" . esc_html($keywords) . "</news:keywords>\n";
                }
            }

            $xml .= "    </news:news>\n";

            // Featured image (Google Image extension).
            if ($include_images) {
                $image_url = $this->get_post_image_url($post);
                if (!empty($image_url)) {
                    $xml .= "    <image:image>\n";
                    $xml .= "      <image:loc>" . esc_url($image_url) . "</image:loc>\n";
                    $xml .= "    </image:image>\n";
                }
            }

            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>';

        return $xml;
    }

    /**
     * Resolve a post's language, honoring Polylang / WPML when present.
     *
     * @param WP_Post $post    Post object.
     * @param string  $default Fallback language code.
     * @return string ISO 639-1 language code.
     */
    private function get_post_language($post, $default) {
        $language = $default;

        // Polylang.
        if (function_exists('pll_get_post_language')) {
            $pll = pll_get_post_language($post->ID, 'slug');
            if (!empty($pll)) {
                $language = $pll;
            }
        } elseif (defined('ICL_SITEPRESS_VERSION')) {
            // WPML. This is WPML's own public hook, not a hook defined by this plugin.
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Third-party (WPML) hook.
            $details = apply_filters('wpml_post_language_details', null, $post->ID);
            if (is_array($details) && !empty($details['language_code'])) {
                $language = $details['language_code'];
            }
        }

        /**
         * Filter the language code emitted for a given post.
         *
         * @param string  $language Resolved language code.
         * @param WP_Post $post     Post object.
         */
        return apply_filters('newssitemap_post_language', $language, $post);
    }

    /**
     * Resolve the headline for <news:title>, preferring a custom SEO title from
     * Rank Math or Yoast when one is set and the option is enabled.
     *
     * SEO titles often contain template variables (%%title%%, %sep%, ...). We
     * only use a custom title when we can resolve those safely; otherwise we
     * fall back to the plain post title so no raw placeholders leak into XML.
     *
     * @param WP_Post $post    Post object.
     * @param array   $options Resolved options.
     * @return string Headline.
     */
    private function get_post_news_title($post, $options) {
        $default = get_the_title($post);

        if (empty($options['use_seo_title']) || !apply_filters('newssitemap_use_seo_title', true, $post)) {
            return $default;
        }

        // Prefer whichever SEO plugin has a per-post title stored.
        $custom = get_post_meta($post->ID, 'rank_math_title', true);
        $engine = 'rankmath';
        if (empty($custom)) {
            $custom = get_post_meta($post->ID, '_yoast_wpseo_title', true);
            $engine = 'yoast';
        }

        if (empty($custom)) {
            return $default;
        }

        // Resolve template variables when the SEO plugin exposes a helper.
        if (strpos($custom, '%') !== false) {
            if ('rankmath' === $engine && class_exists('RankMath\\Helper') && method_exists('RankMath\\Helper', 'replace_vars')) {
                $custom = RankMath\Helper::replace_vars($custom, $post);
            } elseif ('yoast' === $engine && function_exists('wpseo_replace_vars')) {
                $custom = wpseo_replace_vars($custom, $post);
            } else {
                // Can't safely resolve placeholders; use the plain title.
                return $default;
            }
        }

        $custom = trim(wp_strip_all_tags($custom));

        /**
         * Filter the final headline used for <news:title>.
         *
         * @param string  $title The resolved headline.
         * @param WP_Post $post  Post object.
         */
        $title = apply_filters('newssitemap_post_title', ($custom !== '' ? $custom : $default), $post);

        return $title !== '' ? $title : $default;
    }

    /**
     * Build the comma-separated keyword string from a post's tags.
     *
     * Note: Google News no longer uses <news:keywords> for ranking, but the
     * tag remains valid and harmless. Disable via the
     * `newssitemap_include_keywords` filter if undesired.
     *
     * @param WP_Post $post Post object.
     * @return string Comma-separated keywords.
     */
    private function get_post_keywords($post) {
        $tags = get_the_tags($post->ID);
        if (empty($tags) || is_wp_error($tags)) {
            return '';
        }

        $names = wp_list_pluck($tags, 'name');
        return implode(', ', array_map('sanitize_text_field', $names));
    }

    /**
     * Get the featured image URL for a post.
     *
     * @param WP_Post $post Post object.
     * @return string Image URL, or empty string when none.
     */
    private function get_post_image_url($post) {
        $thumb_id = get_post_thumbnail_id($post->ID);
        if (!$thumb_id) {
            return '';
        }

        $url = wp_get_attachment_image_url($thumb_id, 'full');
        return $url ? $url : '';
    }

    /**
     * Flush the cached sitemap output.
     */
    public function flush_cache() {
        delete_transient(NEWSSITEMAP_CACHE_KEY);
        delete_transient(NEWSSITEMAP_CACHE_KEY . '_built');
    }

    /**
     * Flush the cache on a publish/unpublish status transition.
     *
     * @param string  $new_status New post status.
     * @param string  $old_status Old post status.
     * @param WP_Post $post       Post object.
     */
    public function flush_cache_on_status_change($new_status, $old_status, $post) {
        if ('publish' === $new_status || 'publish' === $old_status) {
            $this->flush_cache();
        }
    }

    /**
     * Append the sitemap URL to robots.txt (only when the site is public).
     *
     * @param string $output    Existing robots.txt content.
     * @param bool   $is_public Whether the site is public.
     * @return string Modified robots.txt content.
     */
    public function add_sitemap_to_robots($output, $is_public) {
        if ($is_public) {
            $url = home_url('/lightweight-newscast-xml-sitemap-for-google-news.xml');
            $output .= "\nSitemap: " . esc_url($url) . "\n";
        }
        return $output;
    }

    /**
     * Register the per-post "exclude from news sitemap" meta box.
     */
    public function add_exclude_meta_box() {
        $options = $this->get_resolved_options();
        foreach ($options['post_types'] as $post_type) {
            add_meta_box(
                'newssitemap_exclude',
                __('Google News Sitemap', 'lightweight-newscast-xml-sitemap-for-google-news'),
                array($this, 'render_exclude_meta_box'),
                $post_type,
                'side',
                'default'
            );
        }
    }

    /**
     * Render the exclude meta box.
     *
     * @param WP_Post $post Post object.
     */
    public function render_exclude_meta_box($post) {
        wp_nonce_field('newssitemap_exclude_save', 'newssitemap_exclude_nonce');
        $excluded = get_post_meta($post->ID, NEWSSITEMAP_EXCLUDE_META, true) === '1';
        ?>
        <label>
            <input type="checkbox" name="newssitemap_exclude" value="1" <?php checked($excluded); ?> />
            <?php esc_html_e('Exclude this post from the Google News sitemap', 'lightweight-newscast-xml-sitemap-for-google-news'); ?>
        </label>
        <?php
    }

    /**
     * Persist the exclude meta box value.
     *
     * @param int $post_id Post ID.
     */
    public function save_exclude_meta_box($post_id) {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!isset($_POST['newssitemap_exclude_nonce']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['newssitemap_exclude_nonce'])), 'newssitemap_exclude_save')) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['newssitemap_exclude'])) {
            update_post_meta($post_id, NEWSSITEMAP_EXCLUDE_META, '1');
        } else {
            delete_post_meta($post_id, NEWSSITEMAP_EXCLUDE_META);
        }
    }

    /**
     * Count posts currently eligible for the sitemap (for the health panel).
     *
     * @return int Number of eligible posts in the news window.
     */
    public function count_eligible_posts() {
        $options = $this->get_resolved_options();
        $query = new WP_Query(array_merge(
            $this->get_query_args($options, 1, 1),
            array('fields' => 'ids', 'posts_per_page' => 1, 'paged' => 1)
        ));
        return (int) $query->found_posts;
    }

    /**
     * Get a preview list of the posts currently eligible for the sitemap.
     *
     * Uses the exact same query as the rendered sitemap so the admin panel
     * shows precisely what Google will see.
     *
     * @param int $limit Maximum number of posts to return.
     * @return WP_Post[] Eligible posts, newest first.
     */
    public function get_eligible_posts_preview($limit = 10) {
        $options = $this->get_resolved_options();
        $query = new WP_Query(array_merge(
            $this->get_query_args($options, 1, $limit),
            array('posts_per_page' => $limit, 'paged' => 1, 'no_found_rows' => true)
        ));
        return $query->posts;
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            __('Lightweight Newscast XML Sitemap For Google News Settings', 'lightweight-newscast-xml-sitemap-for-google-news'),
            __('Lightweight Newscast XML Sitemap For Google News', 'lightweight-newscast-xml-sitemap-for-google-news'),
            'manage_options',
            'lightweight-newscast-xml-sitemap-for-google-news',
            array($this, 'admin_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('newssitemap_settings', 'newssitemap_options', array(
            'sanitize_callback' => array($this, 'sanitize_options')
        ));

        add_settings_section(
            'newssitemap_settings_section',
            __('Google News Sitemap Settings', 'lightweight-newscast-xml-sitemap-for-google-news'),
            array($this, 'settings_section_callback'),
            'newssitemap_settings'
        );

        add_settings_field(
            'publication_name',
            __('Publication Name', 'lightweight-newscast-xml-sitemap-for-google-news'),
            array($this, 'publication_name_callback'),
            'newssitemap_settings',
            'newssitemap_settings_section'
        );

        add_settings_field(
            'publication_language',
            __('Publication Language', 'lightweight-newscast-xml-sitemap-for-google-news'),
            array($this, 'publication_language_callback'),
            'newssitemap_settings',
            'newssitemap_settings_section'
        );

        add_settings_field(
            'post_types',
            __('Post Types', 'lightweight-newscast-xml-sitemap-for-google-news'),
            array($this, 'post_types_callback'),
            'newssitemap_settings',
            'newssitemap_settings_section'
        );

        add_settings_field(
            'categories',
            __('Categories', 'lightweight-newscast-xml-sitemap-for-google-news'),
            array($this, 'categories_callback'),
            'newssitemap_settings',
            'newssitemap_settings_section'
        );

        add_settings_field(
            'exclude_categories',
            __('Exclude Categories', 'lightweight-newscast-xml-sitemap-for-google-news'),
            array($this, 'exclude_categories_callback'),
            'newssitemap_settings',
            'newssitemap_settings_section'
        );

        add_settings_field(
            'exclude_tags',
            __('Exclude Tags', 'lightweight-newscast-xml-sitemap-for-google-news'),
            array($this, 'exclude_tags_callback'),
            'newssitemap_settings',
            'newssitemap_settings_section'
        );

        add_settings_field(
            'max_age',
            __('Maximum Age (hours)', 'lightweight-newscast-xml-sitemap-for-google-news'),
            array($this, 'max_age_callback'),
            'newssitemap_settings',
            'newssitemap_settings_section'
        );

        add_settings_field(
            'max_posts',
            __('Maximum Posts', 'lightweight-newscast-xml-sitemap-for-google-news'),
            array($this, 'max_posts_callback'),
            'newssitemap_settings',
            'newssitemap_settings_section'
        );

        add_settings_field(
            'respect_noindex',
            __('Respect SEO "noindex"', 'lightweight-newscast-xml-sitemap-for-google-news'),
            array($this, 'respect_noindex_callback'),
            'newssitemap_settings',
            'newssitemap_settings_section'
        );

        add_settings_field(
            'use_seo_title',
            __('Use SEO Title', 'lightweight-newscast-xml-sitemap-for-google-news'),
            array($this, 'use_seo_title_callback'),
            'newssitemap_settings',
            'newssitemap_settings_section'
        );
    }

    /**
     * Settings section callback
     */
    public function settings_section_callback() {
        echo '<p>Configure your Google News compatible sitemap settings.</p>';
        echo '<p>Your sitemap is available at: <a href="' . esc_url(home_url('/lightweight-newscast-xml-sitemap-for-google-news.xml')) . '" target="_blank">' . esc_url(home_url('/lightweight-newscast-xml-sitemap-for-google-news.xml')) . '</a></p>';
        echo '<p>Alternative URL: <a href="' . esc_url(home_url('/?news_sitemap_google_news=1')) . '" target="_blank">' . esc_url(home_url('/?news_sitemap_google_news=1')) . '</a></p>';
        echo '<p><strong>Note:</strong> The publication name and language are pulled from your settings with fallbacks to ensure they are never empty. If not set, the site name will be used for publication name, and "en" will be used for language.</p>';
        echo '<p><strong>Timezone:</strong> The publication_date in the sitemap uses the timezone configured in your WordPress General Settings (Settings > General > Timezone).</p>';

        // Check for active SEO plugins
        $active_seo_plugins = $this->get_active_seo_plugins();
        if (!empty($active_seo_plugins)) {
            echo '<div class="notice notice-info inline"><p><strong>SEO Plugin Detected:</strong> ' . esc_html(implode(', ', $active_seo_plugins)) . ' is active. This plugin is designed to work with SEO plugins, and both sitemap URLs should function correctly.</p></div>';
        }
    }

    /**
     * Get active SEO plugins
     */
    private function get_active_seo_plugins() {
        $seo_plugins = array();

        if (is_plugin_active('wordpress-seo/wp-seo.php')) {
            $seo_plugins[] = 'Yoast SEO';
        }

        if (is_plugin_active('seo-by-rank-math/rank-math.php')) {
            $seo_plugins[] = 'Rank Math';
        }

        if (is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php')) {
            $seo_plugins[] = 'All in One SEO';
        }

        return $seo_plugins;
    }

    /**
     * Publication name callback
     */
    public function publication_name_callback() {
        $options = get_option('newssitemap_options');
        $value = isset($options['publication_name']) ? $options['publication_name'] : get_bloginfo('name');
        echo '<input type="text" name="newssitemap_options[publication_name]" value="' . esc_attr($value) . '" class="regular-text" />';
        echo '<p class="description">The name of your publication as it should appear in Google News. Defaults to your site name.</p>';
    }

    /**
     * Publication language callback
     */
    public function publication_language_callback() {
        $options = get_option('newssitemap_options');
        $value = isset($options['publication_language']) ? $options['publication_language'] : 'en';
        echo '<input type="text" name="newssitemap_options[publication_language]" value="' . esc_attr($value) . '" class="small-text" />';
        echo '<p class="description">The language of your publication (ISO 639-1 code, e.g., "en" for English). Polylang/WPML per-post languages are detected automatically when active.</p>';
    }

    /**
     * Post types callback
     */
    public function post_types_callback() {
        $options = get_option('newssitemap_options');
        $selected = isset($options['post_types']) ? $options['post_types'] : array('post');
        $post_types = get_post_types(array('public' => true), 'objects');

        foreach ($post_types as $post_type) {
            $checked = in_array($post_type->name, $selected) ? 'checked="checked"' : '';
            echo '<label><input type="checkbox" name="newssitemap_options[post_types][]" value="' . esc_attr($post_type->name) . '" ' . esc_attr($checked) . ' /> ' . esc_html($post_type->label) . '</label><br />';
        }
        echo '<p class="description">Select which post types to include in the sitemap.</p>';
    }

    /**
     * Categories callback
     */
    public function categories_callback() {
        $options = get_option('newssitemap_options');
        $selected = isset($options['categories']) ? $options['categories'] : array();
        $categories = get_categories();

        echo '<select name="newssitemap_options[categories][]" multiple="multiple" size="5" class="regular-text">';
        echo '<option value="">All Categories</option>';
        foreach ($categories as $category) {
            $selected_attr = in_array($category->term_id, $selected) ? 'selected="selected"' : '';
            echo '<option value="' . esc_attr($category->term_id) . '" ' . esc_attr($selected_attr) . '>' . esc_html($category->name) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">Select specific categories to include, or leave empty for all categories.</p>';
    }

    /**
     * Exclude categories callback
     */
    public function exclude_categories_callback() {
        $options = get_option('newssitemap_options');
        $selected = isset($options['exclude_categories']) ? (array) $options['exclude_categories'] : array();
        $categories = get_categories();

        echo '<select name="newssitemap_options[exclude_categories][]" multiple="multiple" size="5" class="regular-text">';
        foreach ($categories as $category) {
            $selected_attr = in_array($category->term_id, $selected) ? 'selected="selected"' : '';
            echo '<option value="' . esc_attr($category->term_id) . '" ' . esc_attr($selected_attr) . '>' . esc_html($category->name) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">Posts in these categories are kept out of the sitemap (for example Sponsored or Press Releases). Exclusions win over the include list above.</p>';
    }

    /**
     * Exclude tags callback
     */
    public function exclude_tags_callback() {
        $options = get_option('newssitemap_options');
        $selected = isset($options['exclude_tags']) ? (array) $options['exclude_tags'] : array();
        $tags = get_tags(array('hide_empty' => false, 'number' => 200));

        if (empty($tags)) {
            echo '<p class="description">No tags found yet.</p>';
            return;
        }

        echo '<select name="newssitemap_options[exclude_tags][]" multiple="multiple" size="5" class="regular-text">';
        foreach ($tags as $tag) {
            $selected_attr = in_array($tag->term_id, $selected) ? 'selected="selected"' : '';
            echo '<option value="' . esc_attr($tag->term_id) . '" ' . esc_attr($selected_attr) . '>' . esc_html($tag->name) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">Posts carrying any of these tags are kept out of the sitemap.</p>';
    }

    /**
     * Respect noindex callback
     */
    public function respect_noindex_callback() {
        $options = get_option('newssitemap_options');
        $checked = !isset($options['respect_noindex']) ? true : (bool) $options['respect_noindex'];
        echo '<label><input type="checkbox" name="newssitemap_options[respect_noindex]" value="1" ' . checked($checked, true, false) . ' /> ';
        esc_html_e('Skip posts marked "noindex" in Rank Math or Yoast SEO', 'lightweight-newscast-xml-sitemap-for-google-news');
        echo '</label>';
        echo '<p class="description">Recommended. Keeps posts you have told search engines to ignore out of the news sitemap too.</p>';
    }

    /**
     * Use SEO title callback
     */
    public function use_seo_title_callback() {
        $options = get_option('newssitemap_options');
        $checked = !isset($options['use_seo_title']) ? true : (bool) $options['use_seo_title'];
        echo '<label><input type="checkbox" name="newssitemap_options[use_seo_title]" value="1" ' . checked($checked, true, false) . ' /> ';
        esc_html_e('Use the custom Rank Math / Yoast SEO title for the article headline when one is set', 'lightweight-newscast-xml-sitemap-for-google-news');
        echo '</label>';
        echo '<p class="description">Falls back to the normal post title when no SEO title is set or its template variables cannot be resolved.</p>';
    }

    /**
     * Max age callback
     */
    public function max_age_callback() {
        $options = get_option('newssitemap_options');
        $value = isset($options['max_age']) ? $options['max_age'] : 48;
        echo '<input type="number" name="newssitemap_options[max_age]" value="' . esc_attr($value) . '" min="1" max="48" class="small-text" />';
        echo '<p class="description">Maximum age of posts to include in hours (1-48). Google News only accepts articles published within the last 2 days (48 hours). Articles older than 48 hours will be ignored by Google News.</p>';
    }

    /**
     * Max posts callback
     */
    public function max_posts_callback() {
        $options = get_option('newssitemap_options');
        $value = isset($options['max_posts']) ? $options['max_posts'] : 1000;
        echo '<input type="number" name="newssitemap_options[max_posts]" value="' . esc_attr($value) . '" min="1" max="1000" class="small-text" />';
        echo '<p class="description">Maximum number of posts per sitemap page (1-1000). When more eligible posts exist, a sitemap index with paginated child sitemaps is served automatically.</p>';
    }

    /**
     * Sanitize options
     */
    public function sanitize_options($input) {
        $sanitized = array();

        if (isset($input['publication_name'])) {
            $sanitized['publication_name'] = sanitize_text_field($input['publication_name']);
        }

        if (isset($input['publication_language'])) {
            $sanitized['publication_language'] = sanitize_text_field($input['publication_language']);
        }

        if (isset($input['post_types']) && is_array($input['post_types'])) {
            $sanitized['post_types'] = array_map('sanitize_text_field', $input['post_types']);
        }

        if (isset($input['max_age'])) {
            $sanitized['max_age'] = absint($input['max_age']);
            if ($sanitized['max_age'] < 1) $sanitized['max_age'] = 1;
            if ($sanitized['max_age'] > 48) $sanitized['max_age'] = 48;
        }

        if (isset($input['max_posts'])) {
            $sanitized['max_posts'] = absint($input['max_posts']);
            if ($sanitized['max_posts'] < 1) $sanitized['max_posts'] = 1;
            if ($sanitized['max_posts'] > 1000) $sanitized['max_posts'] = 1000;
        }

        if (isset($input['categories']) && is_array($input['categories'])) {
            $sanitized['categories'] = array_filter(array_map('absint', $input['categories']));
        }

        if (isset($input['exclude_categories']) && is_array($input['exclude_categories'])) {
            $sanitized['exclude_categories'] = array_filter(array_map('absint', $input['exclude_categories']));
        }

        if (isset($input['exclude_tags']) && is_array($input['exclude_tags'])) {
            $sanitized['exclude_tags'] = array_filter(array_map('absint', $input['exclude_tags']));
        }

        // Checkboxes: present means enabled, absent means disabled.
        $sanitized['respect_noindex'] = !empty($input['respect_noindex']) ? 1 : 0;
        $sanitized['use_seo_title']   = !empty($input['use_seo_title']) ? 1 : 0;

        // Settings changed -> drop any cached output.
        delete_transient(NEWSSITEMAP_CACHE_KEY);
        delete_transient(NEWSSITEMAP_CACHE_KEY . '_built');

        return $sanitized;
    }

    /**
     * Admin page
     */
    public function admin_page() {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Handle a manual cache flush request.
        if (isset($_POST['newssitemap_flush_cache']) &&
            isset($_POST['newssitemap_flush_nonce']) &&
            wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['newssitemap_flush_nonce'])), 'newssitemap_flush_cache')) {
            $this->flush_cache();
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Sitemap cache cleared.', 'lightweight-newscast-xml-sitemap-for-google-news') . '</p></div>';
        }

        $eligible = $this->count_eligible_posts();
        $preview  = $this->get_eligible_posts_preview(20);

        // Human-readable cache age.
        $cache_present = false !== get_transient(NEWSSITEMAP_CACHE_KEY);
        $built_at = get_transient(NEWSSITEMAP_CACHE_KEY . '_built');
        if (!$cache_present) {
            $cache_label = __('Empty (will rebuild on next request)', 'lightweight-newscast-xml-sitemap-for-google-news');
        } elseif ($built_at) {
            /* translators: %s: human-readable time difference, e.g. "5 mins". */
            $cache_label = sprintf(__('Cached (built %s ago)', 'lightweight-newscast-xml-sitemap-for-google-news'), human_time_diff((int) $built_at, time()));
        } else {
            $cache_label = __('Cached', 'lightweight-newscast-xml-sitemap-for-google-news');
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('Lightweight Newscast XML Sitemap For Google News Settings', 'lightweight-newscast-xml-sitemap-for-google-news'); ?></h1>

            <form action="options.php" method="post">
                <?php
                settings_fields('newssitemap_settings');
                do_settings_sections('newssitemap_settings');
                submit_button('Save Settings');
                ?>
            </form>

            <hr>

            <h2><?php esc_html_e('Sitemap Health', 'lightweight-newscast-xml-sitemap-for-google-news'); ?></h2>
            <table class="widefat striped" style="max-width:640px;">
                <tbody>
                    <tr>
                        <td><strong><?php esc_html_e('Posts in the current 48-hour window', 'lightweight-newscast-xml-sitemap-for-google-news'); ?></strong></td>
                        <td><?php echo esc_html(number_format_i18n($eligible)); ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Cache status', 'lightweight-newscast-xml-sitemap-for-google-news'); ?></strong></td>
                        <td><?php echo esc_html($cache_label); ?></td>
                    </tr>
                </tbody>
            </table>
            <?php if (0 === $eligible) : ?>
                <div class="notice notice-warning inline"><p><?php esc_html_e('No posts currently fall within the 48-hour window, so the sitemap will be empty. This is normal if you have not published recently.', 'lightweight-newscast-xml-sitemap-for-google-news'); ?></p></div>
            <?php else : ?>
                <p class="description" style="margin-top:8px;"><?php esc_html_e('These are the articles currently in the sitemap (same query Google sees). If a post you expect is missing, check its age, category/tag exclusions, or a "noindex" setting.', 'lightweight-newscast-xml-sitemap-for-google-news'); ?></p>
                <table class="widefat striped" style="max-width:640px;">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Article', 'lightweight-newscast-xml-sitemap-for-google-news'); ?></th>
                            <th style="width:140px;"><?php esc_html_e('Age', 'lightweight-newscast-xml-sitemap-for-google-news'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($preview as $ppost) :
                            $p_gmt = get_post_time('U', true, $ppost);
                            /* translators: %s: human-readable time difference, e.g. "3 hours". */
                            $age   = sprintf(__('%s ago', 'lightweight-newscast-xml-sitemap-for-google-news'), human_time_diff($p_gmt, time()));
                            ?>
                            <tr>
                                <td><a href="<?php echo esc_url(get_permalink($ppost)); ?>" target="_blank"><?php echo esc_html(get_the_title($ppost)); ?></a></td>
                                <td><?php echo esc_html($age); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if ($eligible > count($preview)) : ?>
                    <p class="description">
                        <?php
                        /* translators: %d: number of additional posts not shown in the preview. */
                        echo esc_html(sprintf(_n('and %d more article in the sitemap.', 'and %d more articles in the sitemap.', $eligible - count($preview), 'lightweight-newscast-xml-sitemap-for-google-news'), $eligible - count($preview)));
                        ?>
                    </p>
                <?php endif; ?>
            <?php endif; ?>
            <form method="post" style="margin-top:10px;">
                <?php wp_nonce_field('newssitemap_flush_cache', 'newssitemap_flush_nonce'); ?>
                <button type="submit" name="newssitemap_flush_cache" value="1" class="button button-secondary"><?php esc_html_e('Clear Sitemap Cache', 'lightweight-newscast-xml-sitemap-for-google-news'); ?></button>
            </form>

            <hr>

            <h2>Sitemap URLs</h2>
            <p>Your Google News compatible sitemap is available at both of these URLs:</p>

            <ol>
                <li><strong>Primary URL:</strong> <a href="<?php echo esc_url(home_url('/lightweight-newscast-xml-sitemap-for-google-news.xml')); ?>" target="_blank"><?php echo esc_url(home_url('/lightweight-newscast-xml-sitemap-for-google-news.xml')); ?></a></li>
                <li><strong>Alternative URL:</strong> <a href="<?php echo esc_url(home_url('/?news_sitemap_google_news=1')); ?>" target="_blank"><?php echo esc_url(home_url('/?news_sitemap_google_news=1')); ?></a></li>
            </ol>

            <p>Both URLs should work regardless of which SEO plugins you have active. If you encounter any issues with the primary URL, you can use the alternative URL instead.</p>

            <h3>Submit to Google Search Console</h3>
            <p>To submit your sitemap to Google Search Console:</p>
            <ol>
                <li>Go to <a href="https://search.google.com/search-console/" target="_blank">Google Search Console</a></li>
                <li>Add your website property and verify ownership</li>
                <li>Navigate to Sitemaps section in the left menu</li>
                <li>Submit your sitemap URL</li>
                <li>Wait for Google to crawl and index your content</li>
            </ol>

            <h3>Support Development</h3>
            <p>If this plugin has been helpful for your website, consider supporting its development:</p>
            <p><a href="https://ko-fi.com/gunjanjaswal" target="_blank" class="button button-secondary">Support on Ko-fi</a></p>
        </div>
        <?php
    }

    /**
     * Add plugin action links
     */
    public function add_plugin_action_links($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=lightweight-newscast-xml-sitemap-for-google-news') . '">' . __('Settings', 'lightweight-newscast-xml-sitemap-for-google-news') . '</a>';
        $donate_link = '<a href="https://ko-fi.com/gunjanjaswal" target="_blank" style="color: #0073aa; font-weight: bold;">' . __('Support on Ko-fi', 'lightweight-newscast-xml-sitemap-for-google-news') . '</a>';

        array_unshift($links, $settings_link);
        array_push($links, $donate_link);

        return $links;
    }

    /**
     * Add Contact Developer link to plugin row meta on the Plugins screen.
     *
     * @param array  $links Existing plugin row meta links.
     * @param string $file  Plugin file name.
     * @return array Modified row meta links.
     */
    public function add_plugin_row_meta($links, $file) {
        if (plugin_basename(__FILE__) === $file) {
            $links[] = '<a href="https://wordpress.org/support/plugin/lightweight-newscast-xml-sitemap-for-google-news/" target="_blank">' . __('Plugin Support', 'lightweight-newscast-xml-sitemap-for-google-news') . '</a>';
            $links[] = '<a href="mailto:hello@gunjanjaswal.me">' . __('Contact Developer', 'lightweight-newscast-xml-sitemap-for-google-news') . '</a>';
        }
        return $links;
    }
}

/**
 * WP-CLI command: generate or flush the news sitemap.
 */
if (defined('WP_CLI') && WP_CLI) {

    class NewsSitemap_CLI_Command {

        /**
         * Print the generated news sitemap XML to STDOUT.
         *
         * ## EXAMPLES
         *
         *     wp news-sitemap generate
         *
         * @when after_wp_load
         */
        public function generate() {
            global $lnxsfgn_news_sitemap_generator;
            $generator = $lnxsfgn_news_sitemap_generator;
            if (!($generator instanceof NewsSitemap_Generator)) {
                WP_CLI::error('Sitemap generator is not available.');
            }
            // Ensure a fresh build rather than serving cached output.
            $generator->flush_cache();
            ob_start();
            $generator->generate_sitemap();
            $xml = ob_get_clean();
            WP_CLI::line($xml);
        }

        /**
         * Clear the cached news sitemap output.
         *
         * ## EXAMPLES
         *
         *     wp news-sitemap flush
         *
         * @when after_wp_load
         */
        public function flush() {
            delete_transient(NEWSSITEMAP_CACHE_KEY);
            WP_CLI::success('News sitemap cache cleared.');
        }
    }
}

// Initialize the plugin
$lnxsfgn_news_sitemap_generator = new NewsSitemap_Generator();
