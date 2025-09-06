<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       https://gunjanjaswal.me
 * @since      1.0.0
 *
 * @package    News_Sitemap_XML_For_Google_News
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/**
 * Clean up plugin data on uninstall
 */
function news_sitemap_xml_for_google_news_uninstall() {
    // Delete plugin options
    delete_option('newssitemap_options');
    
    // For multisite installations, delete options from all sites
    if (is_multisite()) {
        $sites = get_sites();
        
        foreach ($sites as $site) {
            switch_to_blog($site->blog_id);
            delete_option('newssitemap_options');
            restore_current_blog();
        }
    }
    
    // Remove rewrite rules
    flush_rewrite_rules();
}

// Run the uninstall function
news_sitemap_xml_for_google_news_uninstall();
