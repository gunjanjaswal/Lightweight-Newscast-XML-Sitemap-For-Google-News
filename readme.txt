=== Lightweight Newscast XML Sitemap For Google News ===
Contributors: gunjanjaswal
Tags: sitemap, google news, news, xml sitemap, seo
Requires at least: 5.0
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.3.1
Donate link: https://ko-fi.com/gunjanjaswal
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generates a Google News compatible XML sitemap for WordPress sites to be submitted to Google Search Console for better news content indexing.

== Description ==

Lightweight Newscast XML Sitemap For Google News is a lightweight WordPress plugin that generates a Google News compatible XML sitemap for your website. This sitemap helps Google discover and index your news content more efficiently, potentially improving your visibility in Google News search results.

**Key Features:**

* **Google News Compatible**: Generates XML sitemaps in the exact format required by Google News
* **SEO Plugin Compatible**: Works seamlessly with Yoast SEO, Rank Math, and All in One SEO
* **Respects "noindex"**: Skips posts you have set to noindex in Rank Math or Yoast, so hidden content stays out of Google News
* **SEO Title Support**: Uses your custom Rank Math or Yoast title as the article headline when one is set
* **Include and Exclude Control**: Pick the categories to include, and exclude specific categories or tags such as Sponsored or Press Releases
* **Featured Images**: Adds the post's featured image via the Google Image sitemap extension
* **Automatic Updates**: Sitemap updates automatically when you publish new content, with cached output for fast delivery
* **Sitemap Health Panel**: See exactly which articles are in the sitemap, how old they are, and when the cache was last built
* **Dual URL Access**: Available via pretty permalinks and query parameters
* **Performance Optimized**: Lightweight code that doesn't slow down your site
* **Translation Ready**: Fully internationalized and ready for translation

**Perfect for:**
* News websites and blogs
* Magazine and publication sites
* Content creators who want better Google News visibility
* SEO professionals managing news content

This plugin is compatible with:

* **Yoast SEO**: Works alongside Yoast's XML sitemaps
* **Rank Math**: Compatible with Rank Math's SEO features
* **All in One SEO**: Works with AIOSEO sitemaps
* **Other SEO plugins**: Designed to avoid conflicts

== Installation ==

1. Upload the `lightweight-newscast-xml-sitemap-for-google-news` folder to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Go to Settings > Lightweight Newscast XML Sitemap For Google News to configure the plugin
4. Your sitemap will be automatically available at `/lightweight-newscast-xml-sitemap-for-google-news.xml`

== Frequently Asked Questions ==

= How do I submit my sitemap to Google News? =

1. Go to [Google Search Console](https://search.google.com/search-console/)
2. Add your website property and verify ownership
3. Navigate to Sitemaps section in the left menu
4. Submit your sitemap URL (`http://yoursite.com/lightweight-newscast-xml-sitemap-for-google-news.xml`)
5. Wait for Google to crawl and index your content

= Does this work with SEO plugins? =

Yes! This plugin is specifically designed to work alongside popular SEO plugins like Yoast SEO, Rank Math, and All in One SEO without conflicts.

= What if the pretty permalink doesn't work? =

If you encounter issues with the pretty permalink (`/lightweight-newscast-xml-sitemap-for-google-news.xml`), you can use the alternative query parameter URL (`/?news_sitemap_google_news=1`).

= Can I customize which posts are included? =

Yes! You can configure post types, maximum age of posts, categories to include, categories and tags to exclude, and the maximum number of posts to include in the sitemap. You can also exclude an individual post using the "Exclude from Google News sitemap" checkbox in the post editor.

= A post is not showing in the sitemap. Why? =

Open Settings > Lightweight Newscast XML Sitemap For Google News and check the Sitemap Health panel, which lists the articles currently in the sitemap and their age. A post is left out when any of these apply:

* It was published more than 48 hours ago (Google News only accepts articles from the last 2 days).
* It sits in an excluded category or carries an excluded tag, or has the per-post exclude box ticked.
* It is set to "noindex" in Rank Math or Yoast, and "Respect SEO noindex" is enabled.
* The sitemap is being served from cache or a page cache/CDN. Click "Clear Sitemap Cache" and purge any caching plugin or CDN, then reload.

= Does it respect my SEO plugin's noindex setting? =

Yes. When a post is marked noindex in Rank Math or Yoast SEO, it is kept out of the news sitemap by default. You can turn this off on the settings screen.

= Is this compatible with Google News requirements? =

Yes! The plugin generates XML sitemaps in the exact format required by Google News, including proper publication name and language tags with fallback values.

= What timezone is used for publication dates? =

The plugin uses the timezone configured in your WordPress General Settings (Settings > General > Timezone).

== Screenshots ==

1. Plugin settings page with configuration options
2. Sitemap URLs and Google News submission instructions
3. Example of generated XML sitemap

== Changelog ==

= 1.3.1 =
* Fix: On Polylang sites the sitemap now includes articles from every language, not just the site's default language. Posts in secondary languages were being filtered out on the front end, which could leave the sitemap empty even when recent articles existed.

= 1.3.0 =
* New: Respects the "noindex" robots setting from Rank Math and Yoast SEO, so posts you have hidden from search stay out of the news sitemap. Can be toggled on the settings screen.
* New: Uses your custom Rank Math or Yoast SEO title as the article headline when one is set, falling back to the post title when it is empty or its template variables cannot be resolved.
* New: Exclude specific categories and tags from the sitemap, for example Sponsored or Press Releases.
* New: The Sitemap Health panel now lists the actual articles in the sitemap with their age, and shows how long ago the cache was built.
* Fix: The 48-hour window is now measured in UTC against post_date_gmt, so it stays accurate no matter which timezone the site runs in.
* New: Developer filters `newssitemap_respect_noindex`, `newssitemap_use_seo_title`, and `newssitemap_post_title`.

= 1.2.0 =
* New: Cached sitemap output (transient) with automatic cache busting on publish, update, trash and delete. Big performance win for frequently crawled news sites.
* New: Per-post "Exclude from Google News sitemap" checkbox in the post editor sidebar.
* New: Featured image included per article via the Google Image sitemap extension (<image:image>).
* New: <news:keywords> generated from post tags (toggle via the `newssitemap_include_keywords` filter).
* New: Sitemap is advertised automatically in robots.txt.
* New: Automatic sitemap index with paginated child sitemaps when eligible posts exceed the per-page limit.
* New: WP-CLI commands `wp news-sitemap generate` and `wp news-sitemap flush`.
* New: Per-post language detection for Polylang and WPML (falls back to the global publication language).
* New: Sitemap Health panel on the settings screen (posts in window, cache status, manual cache clear).
* New: Developer filters: `newssitemap_query_args`, `newssitemap_cache_ttl`, `newssitemap_include_images`, `newssitemap_include_keywords`, `newssitemap_post_language`.

= 1.1.2 =
* Updated "Tested up to" to WordPress 7.0.
* Replaced Buy Me a Coffee donation link with Ko-fi (https://ko-fi.com/gunjanjaswal).
* Added "Contact Developer" link to plugin row meta on the Plugins screen.
* Author display name updated to "Gunjan Jaswal".

= 1.1.1 =
* Fixed WordPress coding standards: Added proper prefixes to all global functions and variables
* Function names now use 'lnxsfgn_' prefix for compliance
* Improved code quality and WordPress.org plugin check compatibility

= 1.1.0 =
* Updated for WordPress 6.9 compatibility
* Updated minimum PHP requirement to 7.4
* Added proper plugin headers (Plugin URI, Domain Path, Requires at least, Requires PHP, Tested up to)
* Enhanced WordPress coding standards compliance
* Verified compatibility with WordPress 6.9 UTF-8 improvements
* Confirmed compatibility with WordPress 6.9 frontend performance enhancements

= 1.0.0 =
* Initial release with core functionality
* Added XML sitemap generation for Google News
* Implemented settings page with customization options
* Added support for pretty permalinks
* Added direct URL interception for SEO plugin compatibility
* Improved admin interface with better instructions
* Added Google News submission guidelines
* Fixed compatibility issues with Yoast SEO and All in One SEO
* Fixed empty publication name and language fields
* Added fallback values for required fields
* Improved error handling and validation
* Improved WordPress.org compliance with unique prefixes
* Enhanced Google News compatibility descriptions
* Added Buy Me Coffee donation support

== Upgrade Notice ==

= 1.3.1 =
Fixes empty sitemaps on Polylang multilingual sites by including articles from every language.

= 1.3.0 =
Respects SEO noindex, adds category and tag exclusions, optional SEO-title headlines, a clearer health panel, and a timezone-accurate 48-hour window.

= 1.2.0 =
Adds caching, per-post exclusion, featured images, robots.txt advertising, sitemap index pagination, WP-CLI, and Polylang/WPML language detection.

= 1.1.2 =
Compatibility with WordPress 7.0; donation link moved to Ko-fi; Contact Developer row meta added.

= 1.1.1 =
Coding standards fix for WordPress.org plugin check compliance.

= 1.1.0 =
Compatibility update for WordPress 6.9. Requires PHP 7.4 or higher.

= 1.0.0 =
Initial release with all features including XML sitemap generation for Google News, SEO plugin compatibility, and customization options.

== Support ==

For support, feature requests, or bug reports, please visit the [plugin's GitHub repository](https://github.com/gunjanjaswal/Lightweight-Newscast-XML-Sitemap-For-Google-News) or the [WordPress.org support forum](https://wordpress.org/support/plugin/lightweight-newscast-xml-sitemap-for-google-news/).

== Author ==

This plugin is developed and maintained by [Gunjan Jaswal](https://gunjanjaswal.me).

If this plugin has been helpful, consider [supporting on Ko-fi](https://ko-fi.com/gunjanjaswal) to back the development!
