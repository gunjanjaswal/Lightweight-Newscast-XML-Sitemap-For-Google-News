=== Lightweight Newscast XML Sitemap For Google News ===
Contributors: gunjanjaswal
Tags: sitemap, google news, news, xml sitemap, seo
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Generates a Google News compatible XML sitemap for WordPress sites to be submitted to Google Search Console for better news content indexing.

== Description ==

Lightweight Newscast XML Sitemap For Google News is a lightweight WordPress plugin that generates a Google News compatible XML sitemap for your website. This sitemap helps Google discover and index your news content more efficiently, potentially improving your visibility in Google News search results.

**Key Features:**

* **Google News Compatible**: Generates XML sitemaps in the exact format required by Google News
* **SEO Plugin Compatible**: Works seamlessly with Yoast SEO, Rank Math, and All in One SEO
* **Automatic Updates**: Sitemap updates automatically when you publish new content
* **Customizable Settings**: Configure post types, categories, publication details, and more
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

Yes! You can configure post types, maximum age of posts, categories, and the maximum number of posts to include in the sitemap.

= Is this compatible with Google News requirements? =

Yes! The plugin generates XML sitemaps in the exact format required by Google News, including proper publication name and language tags with fallback values.

= What timezone is used for publication dates? =

The plugin uses the timezone configured in your WordPress General Settings (Settings > General > Timezone).

== Screenshots ==

1. Plugin settings page with configuration options
2. Sitemap URLs and Google News submission instructions
3. Example of generated XML sitemap

== Changelog ==

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

= 1.0.0 =
Initial release with all features including XML sitemap generation for Google News, SEO plugin compatibility, and customization options.

== Support ==

For support, feature requests, or bug reports, please visit the [plugin's GitHub repository](https://github.com/gunjanjaswal/Google-News-Sitemap-Wordpress).

== Author ==

This plugin is developed and maintained by [Gunjan Jaswaal](https://gunjanjaswal.me).

If this plugin has been helpful, consider [buying me a coffee](https://www.buymeacoffee.com/gunjanjaswal) to support development!
