<?php
/**
 * Plugin Name: Google News Sitemap
 * Description: Generates a Google News sitemap for WordPress sites to be submitted to Google Search Console.
 * Version: 1.0.2
 * Author: Gunjan Jaswaal
 * Author URI: https://gunjanjaswal.me
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: google-news-sitemap
 * Domain Path: /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('GNS_VERSION', '1.0.1');
define('GNS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('GNS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('GNS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * The code that runs during plugin activation.
 */
function activate_google_news_sitemap() {
    // Set default options
    $default_options = array(
        'post_types' => array('post'),
        'categories' => array(),
        'publication_name' => get_bloginfo('name'),
        'publication_language' => 'en',
        'max_age' => 48, // Maximum age of posts in hours
        'max_posts' => 1000 // Maximum number of posts in sitemap
    );
    
    add_option('gns_options', $default_options);
    
    // Register rewrite rules
    add_rewrite_rule('^google-news-sitemap\.xml$', 'index.php?gns_sitemap=1', 'top');
    add_rewrite_tag('%gns_sitemap%', '([0-9]+)');
    
    // Flush rewrite rules
    flush_rewrite_rules();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_google_news_sitemap() {
    // Flush rewrite rules
    flush_rewrite_rules();
}

// Register activation and deactivation hooks
register_activation_hook(__FILE__, 'activate_google_news_sitemap');
register_deactivation_hook(__FILE__, 'deactivate_google_news_sitemap');

/**
 * Simple Google News Sitemap Generator
 */
class Simple_Google_News_Sitemap {
    /**
     * Plugin options
     */
    private $options;
    
    /**
     * Initialize the plugin
     */
    public function __construct() {
        // Load options
        $this->options = get_option('gns_options', array());
        
        // Add rewrite rules
        add_action('init', array($this, 'add_rewrite_rules'));
        
        // Add query var
        add_action('init', array($this, 'add_query_var'));
        
        // Generate sitemap
        add_action('template_redirect', array($this, 'maybe_generate_sitemap'));
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
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
            add_rewrite_rule('^google-news-sitemap\.xml$', 'index.php?gns_sitemap=1', 'top');
            add_rewrite_tag('%gns_sitemap%', '([0-9]+)');
        }, 999999);
    }
    
    /**
     * Add query var
     */
    public function add_query_var() {
        add_filter('query_vars', function($vars) {
            $vars[] = 'google_news_sitemap';
            $vars[] = 'gns_sitemap';
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
        $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        
        // Get the path part of the URI (remove query string)
        $path = parse_url($request_uri, PHP_URL_PATH);
        
        // Check if this is a direct request for the sitemap XML
        if ($path === '/google-news-sitemap.xml' || 
            preg_match('#/google-news-sitemap\.xml$#i', $path)) {
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
        if (isset($wp_query->query_vars['gns_sitemap']) || 
            isset($wp_query->query_vars['google_news_sitemap']) || 
            isset($_GET['google_news_sitemap'])) {
            $this->generate_sitemap();
            exit;
        }
    }
    
    /**
     * Generate sitemap
     */
    public function generate_sitemap() {
        // Set content type
        header('Content-Type: application/xml; charset=UTF-8');
        
        // Start XML output
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" 
               xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">' . "\n";
        
        // Add notice
        echo "<!-- Google News Sitemap generated by Google News Sitemap Plugin -->\n";
        
        // Get posts
        $args = array(
            'post_type' => isset($this->options['post_types']) ? $this->options['post_types'] : array('post'),
            'post_status' => 'publish',
            'posts_per_page' => isset($this->options['max_posts']) ? intval($this->options['max_posts']) : 1000,
            'orderby' => 'date',
            'order' => 'DESC'
        );
        
        // Add date filter if max_age is set
        if (isset($this->options['max_age']) && intval($this->options['max_age']) > 0) {
            $args['date_query'] = array(
                array(
                    'after' => date('Y-m-d H:i:s', strtotime('-' . intval($this->options['max_age']) . ' hours'))
                )
            );
        }
        
        // Add category filter if categories are set
        if (isset($this->options['categories']) && !empty($this->options['categories'])) {
            $args['category__in'] = $this->options['categories'];
        }
        
        $posts = get_posts($args);
        $post_count = 0;
        
        // Loop through posts
        foreach ($posts as $post) {
            // Skip posts with noindex
            if ($this->is_post_noindex($post->ID)) {
                continue;
            }
            
            $post_url = get_permalink($post);
            $post_title = get_the_title($post);
            $post_date = get_the_date('Y-m-d\TH:i:sP', $post);
            
            // Get post categories
            $categories = get_the_category($post->ID);
            $category_names = array();
            
            if (!empty($categories)) {
                foreach ($categories as $category) {
                    $category_names[] = $category->name;
                }
            }
            
            // Output URL entry
            echo "\t<url>\n";
            echo "\t\t<loc>" . esc_url($post_url) . "</loc>\n";
            echo "\t\t<news:news>\n";
            echo "\t\t\t<news:publication>\n";
            
            // Get values from database with fallbacks
            $publication_name = isset($this->options['publication_name']) ? $this->options['publication_name'] : '';
            if (empty(trim($publication_name))) {
                $publication_name = get_bloginfo('name');
                if (empty(trim($publication_name))) {
                    $publication_name = 'Web';
                }
            }
            
            $language_code = isset($this->options['publication_language']) ? $this->options['publication_language'] : '';
            if (empty(trim($language_code)) || strlen($language_code) !== 2) {
                $language_code = 'en';
            }
            
            echo "\t\t\t\t<news:name>" . esc_html($publication_name) . "</news:name>\n";
            echo "\t\t\t\t<news:language>" . esc_html($language_code) . "</news:language>\n";
            
            echo "\t\t\t</news:publication>\n";
            echo "\t\t\t<news:publication_date>" . esc_html($post_date) . "</news:publication_date>\n";
            echo "\t\t\t<news:title>" . esc_html($post_title) . "</news:title>\n";
            
            // Add keywords if we have categories
            if (!empty($category_names)) {
                echo "\t\t\t<news:keywords>" . esc_html(implode(', ', $category_names)) . "</news:keywords>\n";
            }
            
            echo "\t\t</news:news>\n";
            echo "\t</url>\n";
            
            $post_count++;
        }
        
        // Add post count
        echo "<!-- Total posts in sitemap: " . $post_count . " -->\n";
        
        // End XML output
        echo '</urlset>';
    }
    
    /**
     * Check if post has noindex
     */
    public function is_post_noindex($post_id) {
        // Check Yoast SEO
        if (function_exists('get_post_meta')) {
            $yoast_noindex = get_post_meta($post_id, '_yoast_wpseo_meta-robots-noindex', true);
            if ($yoast_noindex === '1') {
                return true;
            }
        }
        
        // Check Rank Math
        if (function_exists('get_post_meta')) {
            $rank_math_robots = get_post_meta($post_id, 'rank_math_robots', true);
            if (is_array($rank_math_robots) && in_array('noindex', $rank_math_robots)) {
                return true;
            }
        }
        
        // Check All in One SEO
        if (function_exists('get_post_meta')) {
            $aioseo_noindex = get_post_meta($post_id, '_aioseo_noindex', true);
            if ($aioseo_noindex === 'on' || $aioseo_noindex === '1' || $aioseo_noindex === true) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            'Google News Sitemap',
            'Google News Sitemap',
            'manage_options',
            'google-news-sitemap',
            array($this, 'admin_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('gns_settings', 'gns_options');
        
        add_settings_section(
            'gns_settings_section',
            'Google News Sitemap Settings',
            array($this, 'settings_section_callback'),
            'gns_settings'
        );
        
        add_settings_field(
            'publication_name',
            'Publication Name',
            array($this, 'publication_name_callback'),
            'gns_settings',
            'gns_settings_section'
        );
        
        add_settings_field(
            'publication_language',
            'Publication Language',
            array($this, 'publication_language_callback'),
            'gns_settings',
            'gns_settings_section'
        );
        
        add_settings_field(
            'post_types',
            'Post Types',
            array($this, 'post_types_callback'),
            'gns_settings',
            'gns_settings_section'
        );
        
        add_settings_field(
            'max_age',
            'Maximum Age (hours)',
            array($this, 'max_age_callback'),
            'gns_settings',
            'gns_settings_section'
        );
        
        add_settings_field(
            'max_posts',
            'Maximum Posts',
            array($this, 'max_posts_callback'),
            'gns_settings',
            'gns_settings_section'
        );
    }
    
    /**
     * Settings section callback
     */
    public function settings_section_callback() {
        echo '<p>Configure your Google News Sitemap settings.</p>';
        echo '<p>Your sitemap is available at: <a href="' . home_url('/google-news-sitemap.xml') . '" target="_blank">' . home_url('/google-news-sitemap.xml') . '</a></p>';
        echo '<p>Alternative URL: <a href="' . home_url('/?google_news_sitemap=1') . '" target="_blank">' . home_url('/?google_news_sitemap=1') . '</a></p>';
        echo '<p><strong>Note:</strong> The publication name and language are pulled from your settings with fallbacks to ensure they are never empty. If not set, the site name will be used for publication name, and "en" will be used for language.</p>';
        
        // Check for active SEO plugins
        $active_seo_plugins = $this->get_active_seo_plugins();
        if (!empty($active_seo_plugins)) {
            echo '<div class="notice notice-info inline"><p><strong>SEO Plugin Detected:</strong> ' . implode(', ', $active_seo_plugins) . ' is active. This plugin is designed to work with SEO plugins, and both sitemap URLs should function correctly.</p></div>';
        }
    }
    
    /**
     * Get active SEO plugins
     */
    public function get_active_seo_plugins() {
        $active_plugins = [];
        
        // Make sure the function is available
        if (!function_exists('is_plugin_active')) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        
        if (function_exists('is_plugin_active')) {
            if (is_plugin_active('wordpress-seo/wp-seo.php') || is_plugin_active('wordpress-seo-premium/wp-seo-premium.php')) {
                $active_plugins[] = 'Yoast SEO';
            }
            
            if (is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php') || is_plugin_active('all-in-one-seo-pack-pro/all_in_one_seo_pack.php')) {
                $active_plugins[] = 'All in One SEO';
            }
            
            if (is_plugin_active('seo-by-rank-math/rank-math.php') || is_plugin_active('seo-by-rank-math-pro/rank-math-pro.php')) {
                $active_plugins[] = 'Rank Math SEO';
            }
        }
        
        return $active_plugins;
    }
    
    /**
     * Publication name callback
     */
    public function publication_name_callback() {
        $publication_name = isset($this->options['publication_name']) ? $this->options['publication_name'] : get_bloginfo('name');
        echo '<input type="text" name="gns_options[publication_name]" value="' . esc_attr($publication_name) . '" class="regular-text">';
        echo '<p class="description">The name of your publication. If left empty, your site name will be used as a fallback.</p>';
    }
    
    /**
     * Publication language callback
     */
    public function publication_language_callback() {
        $publication_language = isset($this->options['publication_language']) ? $this->options['publication_language'] : 'en';
        
        $languages = array(
            'af' => 'Afrikaans',
            'ar' => 'Arabic',
            'bg' => 'Bulgarian',
            'bn' => 'Bengali',
            'ca' => 'Catalan',
            'cs' => 'Czech',
            'cy' => 'Welsh',
            'da' => 'Danish',
            'de' => 'German',
            'el' => 'Greek',
            'en' => 'English',
            'es' => 'Spanish',
            'et' => 'Estonian',
            'fa' => 'Persian',
            'fi' => 'Finnish',
            'fr' => 'French',
            'gu' => 'Gujarati',
            'he' => 'Hebrew',
            'hi' => 'Hindi',
            'hr' => 'Croatian',
            'hu' => 'Hungarian',
            'id' => 'Indonesian',
            'it' => 'Italian',
            'ja' => 'Japanese',
            'kn' => 'Kannada',
            'ko' => 'Korean',
            'lt' => 'Lithuanian',
            'lv' => 'Latvian',
            'ml' => 'Malayalam',
            'mr' => 'Marathi',
            'ne' => 'Nepali',
            'nl' => 'Dutch',
            'no' => 'Norwegian',
            'pa' => 'Punjabi',
            'pl' => 'Polish',
            'pt' => 'Portuguese',
            'ro' => 'Romanian',
            'ru' => 'Russian',
            'sk' => 'Slovak',
            'sl' => 'Slovenian',
            'sr' => 'Serbian',
            'sv' => 'Swedish',
            'sw' => 'Swahili',
            'ta' => 'Tamil',
            'te' => 'Telugu',
            'th' => 'Thai',
            'tl' => 'Tagalog',
            'tr' => 'Turkish',
            'uk' => 'Ukrainian',
            'ur' => 'Urdu',
            'vi' => 'Vietnamese',
            'zh' => 'Chinese'
        );
        
        echo '<select name="gns_options[publication_language]">';
        foreach ($languages as $code => $name) {
            echo '<option value="' . esc_attr($code) . '" ' . selected($publication_language, $code, false) . '>' . esc_html($name) . ' (' . esc_html($code) . ')</option>';
        }
        echo '</select>';
        echo '<p class="description">The language of your publication. Must be a valid two-letter ISO 639-1 code. If invalid or empty, "en" will be used as a fallback.</p>';
    }
    
    /**
     * Post types callback
     */
    public function post_types_callback() {
        $post_types = isset($this->options['post_types']) ? $this->options['post_types'] : array('post');
        $available_post_types = get_post_types(array('public' => true), 'objects');
        
        foreach ($available_post_types as $post_type) {
            echo '<label><input type="checkbox" name="gns_options[post_types][]" value="' . esc_attr($post_type->name) . '" ' . checked(in_array($post_type->name, $post_types), true, false) . '> ' . esc_html($post_type->labels->name) . '</label><br>';
        }
    }
    
    /**
     * Max age callback
     */
    public function max_age_callback() {
        $max_age = isset($this->options['max_age']) ? intval($this->options['max_age']) : 48;
        echo '<input type="number" name="gns_options[max_age]" value="' . esc_attr($max_age) . '" class="small-text"> hours';
        echo '<p class="description">Maximum age of posts to include in the sitemap (in hours). Set to 0 to include all posts.</p>';
    }
    
    /**
     * Max posts callback
     */
    public function max_posts_callback() {
        $max_posts = isset($this->options['max_posts']) ? intval($this->options['max_posts']) : 1000;
        echo '<input type="number" name="gns_options[max_posts]" value="' . esc_attr($max_posts) . '" class="small-text">';
        echo '<p class="description">Maximum number of posts to include in the sitemap. Google News recommends no more than 1,000 posts.</p>';
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
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <form action="options.php" method="post">
                <?php
                settings_fields('gns_settings');
                do_settings_sections('gns_settings');
                submit_button('Save Settings');
                ?>
            </form>
            
            <hr>
            
            <h2>Sitemap URLs</h2>
            <p>Your Google News sitemap is available at both of these URLs:</p>
            
            <ol>
                <li><strong>Primary URL:</strong> <a href="<?php echo home_url('/google-news-sitemap.xml'); ?>" target="_blank"><?php echo home_url('/google-news-sitemap.xml'); ?></a></li>
                <li><strong>Alternative URL:</strong> <a href="<?php echo home_url('/?google_news_sitemap=1'); ?>" target="_blank"><?php echo home_url('/?google_news_sitemap=1'); ?></a></li>
            </ol>
            
            <p>Both URLs should work regardless of which SEO plugins you have active. If you encounter any issues with the primary URL, you can use the alternative URL instead.</p>
            
            <h3>Submit to Google News</h3>
            <p>To submit your sitemap to Google News:</p>
            <ol>
                <li>Go to <a href="https://news.google.com/publisher-center/" target="_blank">Google News Publisher Center</a></li>
                <li>Add your publication and verify ownership</li>
                <li>Submit your sitemap URL</li>
            </ol>
        </div>
        <?php
    }
}

// Initialize the plugin
$simple_google_news_sitemap = new Simple_Google_News_Sitemap();
