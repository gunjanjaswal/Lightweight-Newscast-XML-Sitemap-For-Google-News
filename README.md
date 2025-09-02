# News Sitemap for WordPress

[![WordPress Compatible](https://img.shields.io/badge/WordPress-5.0%2B-blue.svg)](https://wordpress.org/)
[![PHP Version](https://img.shields.io/badge/PHP-7.0%2B-purple.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

A lightweight, SEO plugin compatible WordPress plugin that generates a Google News compatible sitemap for your website. Submit this sitemap to Google Search Console to have your content included in Google News.

## 🚀 Features

- **SEO Plugin Compatible**: Works seamlessly with Yoast SEO, Rank Math, and All in One SEO
- **Dual URL Support**: Access your sitemap via pretty permalink or query parameter
- **Google News Compatible Format**: Ensures proper publication name and language tags
- **Fallback System**: Never outputs empty required fields
- **Customizable**: Configure post types, age limits, and more
- **No Coding Required**: Simple admin interface for all settings

## 📋 Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- Pretty permalinks enabled (recommended but not required)

## 💻 Installation

1. Download the latest release from the [releases page](https://github.com/gunjanjaswal/Google-News-Sitemap-Wordpress/releases)
2. Upload the `news-sitemap` folder to the `/wp-content/plugins/` directory
3. Activate the plugin through the 'Plugins' menu in WordPress
4. Go to Settings > News Sitemap to configure the plugin

## 🔧 Configuration

The plugin provides several configuration options:

- **Publication Name**: Your publication's name as it should appear in Google News
  - Fallback: Your WordPress site name
  - Final fallback: "Web"

- **Publication Language**: The language of your publication (ISO 639-1 code)
  - Fallback: "en" (English)

- **Post Types**: Which post types to include in the sitemap
  - Default: Posts only

- **Maximum Age**: Maximum age of posts to include (in hours)
  - Default: 48 hours

- **Maximum Posts**: Maximum number of posts to include
  - Default: 1000 (Google's recommended limit)

## 🔗 Accessing Your Sitemap

After installation, your Google News compatible sitemap will be available at two URLs:

- **Pretty Permalink**: `https://your-site.com/news-sitemap.xml`
- **Query Parameter**: `https://your-site.com/?news_sitemap=1`

Both URLs output the same content and work regardless of which SEO plugins you have active.

## 📊 Submitting to Google Search Console

1. Go to [Google Search Console](https://search.google.com/search-console/)
2. Add your website property and verify ownership
3. Navigate to Sitemaps section in the left menu
4. Submit your sitemap URL
5. Wait for Google to crawl and index your content

## 🔍 Compatibility

This plugin is compatible with:

- **Yoast SEO**: Works alongside Yoast's XML sitemaps
- **Rank Math**: Compatible with Rank Math's SEO features
- **All in One SEO**: Works with AIOSEO sitemaps
- **Other SEO plugins**: Designed to avoid conflicts

## 🐞 Troubleshooting

- If the pretty permalink URL doesn't work, try the query parameter URL
- Ensure your publication name and language are set correctly
- Check that your posts meet Google News requirements (recent, newsworthy content)
- **Timezone**: Publication dates use the timezone from WordPress General Settings (Settings > General > Timezone)

## 👨‍💻 Developer Notes

- The plugin uses direct URL interception to ensure compatibility with SEO plugins
- Custom hooks are available for extending functionality
- Minimal database impact with efficient option storage

## 📝 License

This plugin is licensed under the [GPL v2 or later](https://www.gnu.org/licenses/gpl-2.0.html).

## 👤 Author

**Gunjan Jaswaal**

- Website: [gunjanjaswal.me](https://gunjanjaswal.me)
- Email: hello@gunjanjaswal.me
- Twitter: [@gunjanjaswal](https://twitter.com/gunjanjaswal)

## 🤝 Contributing

Contributions, issues, and feature requests are welcome! Feel free to check the [issues page](https://github.com/gunjanjaswal/Google-News-Sitemap-Wordpress/issues).

## ⭐ Show your support

Give a ⭐️ if this project helped you!

## ☕ Support Development

If this plugin has been helpful for your website, consider supporting its development:

[![Buy me a coffee](https://img.shields.io/badge/Buy%20me%20a%20coffee-FFDD00?style=for-the-badge&logo=buy-me-a-coffee&logoColor=black)](https://www.buymeacoffee.com/gunjanjaswal)

## 📜 Changelog

### 1.0.3 (January 2025)
- Renamed plugin to 'News Sitemap' for WordPress.org compatibility
- Updated all URLs to use news-sitemap.xml format
- Added Buy Me Coffee donation support
- Improved WordPress.org compliance with unique prefixes
- Enhanced Google News compatibility descriptions

### 1.0.2 (July 2025)
- Added direct URL interception for SEO plugin compatibility
- Improved admin interface with better instructions
- Added Google News submission guidelines
- Fixed compatibility issues with Yoast SEO and All in One SEO

### 1.0.1 (June 2025)
- Fixed empty publication name and language fields
- Added fallback values for required fields
- Improved error handling and validation

### 1.0.0 (May 2025)
- Initial release
