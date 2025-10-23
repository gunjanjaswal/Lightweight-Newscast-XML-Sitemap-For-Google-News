<?php
/**
 * Plugin Name: Lightweight Newscast XML Sitemap For Google News
 * Description: Generates a Google News compatible XML sitemap for WordPress sites to be submitted to Google Search Console for better news content indexing.
 * Version: 1.0.0
 * Author: Gunjan Jaswaal
 * Author URI: https://gunjanjaswal.me
 * Donate link: https://www.buymeacoffee.com/gunjanjaswal
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: lightweight-newscast-xml-sitemap-for-google-news
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('NEWSSITEMAP_VERSION', '1.0.0');

/**
 * The code that runs during plugin activation.
 */
function activate_news_sitemap() {
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
function deactivate_news_sitemap() {
    // Flush rewrite rules to clean up
    flush_rewrite_rules();
}

register_activation_hook(__FILE__, 'activate_news_sitemap');
register_deactivation_hook(__FILE__, 'deactivate_news_sitemap');

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
        
        // Add rewrite rules and query vars
        $this->add_rewrite_rules();
        $this->add_query_var();
        
        // Hook into template_redirect to maybe generate sitemap
        add_action('template_redirect', array($this, 'maybe_generate_sitemap'));
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
     * Generate sitemap
     */
    public function generate_sitemap() {
        // Set content type
        header('Content-Type: application/xml; charset=utf-8');
        
        // Get options
        $options = get_option('newssitemap_options', array());
        
        // Set defaults if options are empty
        $post_types = !empty($options['post_types']) ? $options['post_types'] : array('post');
        $categories = !empty($options['categories']) ? $options['categories'] : array();
        $publication_name = !empty($options['publication_name']) ? $options['publication_name'] : get_bloginfo('name');
        $publication_language = !empty($options['publication_language']) ? $options['publication_language'] : 'en';
        $max_age = !empty($options['max_age']) ? intval($options['max_age']) : 48;
        $max_posts = !empty($options['max_posts']) ? intval($options['max_posts']) : 1000;
        
        // Fallback for publication name
        if (empty($publication_name)) {
            $publication_name = get_bloginfo('name');
            if (empty($publication_name)) {
                $publication_name = 'Web';
            }
        }
        
        // Calculate date threshold - Google News only accepts articles from last 48 hours
        // Enforce Google's 48-hour limit regardless of user setting
        $google_max_age = min($max_age, 48);
        $date_threshold = gmdate('Y-m-d H:i:s', strtotime('-' . $google_max_age . ' hours'));
        
        // Query arguments
        $args = array(
            'post_type' => $post_types,
            'post_status' => 'publish',
            'posts_per_page' => $max_posts,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        // Always add date filter for Google News compliance (max 48 hours)
        $args['date_query'] = array(
            array(
                'after' => $date_threshold,
                'inclusive' => true,
            ),
        );
        
        // Add category filter if specified
        if (!empty($categories)) {
            $args['cat'] = implode(',', $categories);
        }
        
        // Note: Removed meta_query for performance optimization
        // SEO plugins typically handle noindex via robots meta tags in HTML head
        // For sitemap generation, we rely on post_status='publish' filtering
        
        $posts = get_posts($args);
        
        // Start XML output
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">' . "\n";
        
        foreach ($posts as $post) {
            // Get post date in WordPress timezone and convert to ISO 8601 format
            $post_timestamp = get_post_time('U', false, $post);
            $post_date = get_date_from_gmt(gmdate('Y-m-d H:i:s', $post_timestamp), 'c');
            $post_url = get_permalink($post);
            $post_title = get_the_title($post);
            
            // Escape XML entities
            $post_title = esc_html($post_title);
            $post_url = esc_url($post_url);
            
            echo "  <url>\n";
            echo "    <loc>" . esc_url($post_url) . "</loc>\n";
            echo "    <news:news>\n";
            echo "      <news:publication>\n";
            echo "        <news:name>" . esc_html($publication_name) . "</news:name>\n";
            echo "        <news:language>" . esc_html($publication_language) . "</news:language>\n";
            echo "      </news:publication>\n";
            echo "      <news:publication_date>" . esc_html($post_date) . "</news:publication_date>\n";
            echo "      <news:title>" . esc_html($post_title) . "</news:title>\n";
            echo "    </news:news>\n";
            echo "  </url>\n";
        }
        
        echo '</urlset>';
        
        return false;
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
        echo '<p class="description">The language of your publication (ISO 639-1 code, e.g., "en" for English).</p>';
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
        echo '<p class="description">Maximum number of posts to include (1-1000). Google recommends no more than 1000 URLs.</p>';
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
            $sanitized['categories'] = array_map('absint', $input['categories']);
        }
        
        return $sanitized;
    }
    
    /**
     * Admin page
     */
    public function admin_page() {
        if (!current_user_can('manage_options')) {
            return;
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
            
            <h3>☕ Support Development</h3>
            <p>If this plugin has been helpful for your website, consider supporting its development:</p>
            <p><a href="https://www.buymeacoffee.com/gunjanjaswal" target="_blank" class="button button-secondary">☕ Buy me a coffee</a></p>
        </div>
        <?php
    }
    
    /**
     * Add plugin action links
     */
    public function add_plugin_action_links($links) {
        $settings_link = '<a href="' . admin_url('options-general.php?page=lightweight-newscast-xml-sitemap-for-google-news') . '">' . __('Settings', 'lightweight-newscast-xml-sitemap-for-google-news') . '</a>';
        $donate_link = '<a href="https://www.buymeacoffee.com/gunjanjaswal" target="_blank" style="color: #d63638; font-weight: bold;">' . __('☕ Buy me a coffee', 'lightweight-newscast-xml-sitemap-for-google-news') . '</a>';
        
        array_unshift($links, $settings_link);
        array_push($links, $donate_link);
        
        return $links;
    }
}

// Initialize the plugin
$news_sitemap_generator = new NewsSitemap_Generator();
