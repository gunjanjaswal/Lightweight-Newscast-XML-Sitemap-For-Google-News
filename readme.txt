=== News Sitemap ===
Contributors: gunjanjaswal
Tags: sitemap, google news, news, xml sitemap, seo
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.0
Stable tag: 1.0.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generates a Google News compatible sitemap for WordPress sites to be submitted to Google Search Console for better news content indexing.

== Description ==

News Sitemap is a lightweight, SEO plugin compatible WordPress plugin that generates a Google News compatible sitemap for your website. Submit this sitemap to Google Search Console to have your content included in Google News.

= Key Features =

* **SEO Plugin Compatible**: Works seamlessly with Yoast SEO, Rank Math, and All in One SEO
* **Dual URL Support**: Access your sitemap via pretty permalink or query parameter
* **Google News Compatible Format**: Ensures proper publication name and language tags
* **Fallback System**: Never outputs empty required fields
* **Customizable**: Configure post types, age limits, and more
* **No Coding Required**: Simple admin interface for all settings

= Sitemap URLs =

After installation, your Google News compatible sitemap will be available at:

* **Pretty Permalink**: `https://your-site.com/news-sitemap.xml`
* **Query Parameter**: `https://your-site.com/?news_sitemap=1`

Both URLs output the same content and work regardless of which SEO plugins you have active.

= Configuration Options =

* **Publication Name**: Your publication's name as it should appear in Google News
* **Publication Language**: The language of your publication (ISO 639-1 code)
* **Post Types**: Which post types to include in the sitemap
* **Maximum Age**: Maximum age of posts to include (in hours)
* **Maximum Posts**: Maximum number of posts to include (Google recommends max 1,000)

= Compatibility =

This plugin is compatible with:

* **Yoast SEO**: Works alongside Yoast's XML sitemaps
* **Rank Math**: Compatible with Rank Math's SEO features
* **All in One SEO**: Works with AIOSEO sitemaps
* **Other SEO plugins**: Designed to avoid conflicts

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/news-sitemap` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Settings > News Sitemap screen to configure the plugin
4. Your sitemap will be automatically available at `/news-sitemap.xml`

== Frequently Asked Questions ==

= How do I submit my sitemap to Google News? =

1. Go to [Google Search Console](https://search.google.com/search-console/)
2. Add your website property and verify ownership
3. Navigate to Sitemaps section in the left menu
4. Submit your sitemap URL (`https://your-site.com/news-sitemap.xml`)
5. Wait for Google to crawl and index your content

= Does this work with SEO plugins? =

Yes! This plugin is specifically designed to work alongside popular SEO plugins like Yoast SEO, Rank Math, and All in One SEO without conflicts.

= What if the pretty permalink doesn't work? =

If you encounter issues with the pretty permalink (`/news-sitemap.xml`), you can use the alternative query parameter URL (`/?news_sitemap=1`).

= Can I customize which posts are included? =

Yes! You can configure post types, maximum age of posts, categories, and the maximum number of posts to include in the sitemap.

= Is this compatible with Google News requirements? =

Yes! The plugin generates XML sitemaps in the exact format required by Google News, including proper publication name and language tags with fallback values.

= What timezone is used for publication dates? =

The publication_date in the sitemap uses the timezone configured in your WordPress General Settings (Settings > General > Timezone). Make sure your timezone is set correctly for accurate timestamps.

== Screenshots ==

1. Plugin settings page with configuration options
2. Sitemap URLs and Google News submission instructions
3. Example of generated XML sitemap

== Changelog ==

= 1.0.3 =
* Renamed plugin to 'News Sitemap' for WordPress.org compatibility
* Updated all URLs to use news-sitemap.xml format
* Added Buy Me Coffee donation support
* Improved WordPress.org compliance with unique prefixes
* Enhanced Google News compatibility descriptions

= 1.0.2 =
* Added direct URL interception for SEO plugin compatibility
* Improved admin interface with better instructions
* Added Google News submission guidelines
* Fixed compatibility issues with Yoast SEO and All in One SEO

= 1.0.1 =
* Fixed empty publication name and language fields
* Added fallback values for required fields
* Improved error handling and validation

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.3 =
This version improves WordPress.org compatibility and updates the plugin name. Your sitemap URL will change from google-news-sitemap.xml to news-sitemap.xml.

== Support ==

For support, feature requests, or bug reports, please visit the [plugin's GitHub repository](https://github.com/gunjanjaswal/Google-News-Sitemap-Wordpress).

== Author ==

This plugin is developed and maintained by [Gunjan Jaswaal](https://gunjanjaswal.me).

If this plugin has been helpful, consider [buying me a coffee](https://www.buymeacoffee.com/gunjanjaswal) to support development!
