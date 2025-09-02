<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package News_Sitemap
 */

// If uninstall not called from WordPress, then exit.
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('newssitemap_options');

// Clean up any transients or cached data
delete_transient('newssitemap_cache');

// Remove any custom database tables if they were created (none in this plugin)
// global $wpdb;
// $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}newssitemap_table");

// Clear any scheduled hooks
wp_clear_scheduled_hook('newssitemap_cron_hook');

// Flush rewrite rules to clean up custom endpoints
flush_rewrite_rules();
