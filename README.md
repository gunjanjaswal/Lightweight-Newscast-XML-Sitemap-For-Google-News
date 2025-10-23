# Lightweight Newscast XML Sitemap For Google News

[![Buy Me Coffee](https://img.shields.io/badge/☕-Buy%20me%20a%20coffee-red.svg)](https://www.buymeacoffee.com/gunjanjaswal)
[![WordPress Plugin Version](https://img.shields.io/badge/WordPress-6.8%20tested-blue.svg)](https://wordpress.org/)
[![PHP Version](https://img.shields.io/badge/PHP-7.0%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

A lightweight WordPress plugin that generates a Google News compatible XML sitemap for your website. This sitemap helps Google discover and index your news content more efficiently, potentially improving your visibility in Google News search results.

## 🚀 Features

- **Google News Compatible**: Generates XML sitemaps in the exact format required by Google News
- **SEO Plugin Compatible**: Works seamlessly with Yoast SEO, Rank Math, and All in One SEO
- **Automatic Updates**: Sitemap updates automatically when you publish new content
- **Customizable Settings**: Configure post types, categories, publication details, and more
- **Dual URL Access**: Available via pretty permalinks and query parameters
- **Performance Optimized**: Lightweight code that doesn't slow down your site
- **Translation Ready**: Fully internationalized and ready for translation

## 📋 Requirements

- WordPress 5.0 or higher
- PHP 7.0 or higher
- Pretty permalinks enabled (recommended)

## 🔧 Installation

### Method 1: WordPress Admin (Recommended)
1. Download the plugin zip file
2. Go to your WordPress admin dashboard
3. Navigate to Plugins > Add New > Upload Plugin
4. Choose the zip file and click "Install Now"
5. Activate the plugin

### Method 2: Manual Installation
1. Upload the `lightweight-newscast-xml-sitemap-for-google-news` folder to `/wp-content/plugins/`
2. Activate the plugin through the WordPress admin Plugins page
3. Configure the settings at Settings > Lightweight Newscast XML Sitemap For Google News

## ⚙️ Configuration

After activation, go to **Settings > Lightweight Newscast XML Sitemap For Google News** to configure:

- **Publication Name**: Your site's name as it should appear in Google News
- **Publication Language**: ISO 639-1 language code (e.g., "en" for English)
- **Post Types**: Select which post types to include in the sitemap
- **Categories**: Choose specific categories or leave empty for all
- **Maximum Age**: How old posts can be (1-168 hours, default: 48)
- **Maximum Posts**: Maximum number of posts in sitemap (1-1000, default: 1000)

## 🔗 Sitemap URLs

Your Google News sitemap will be available at:

- **Primary URL**: `https://yoursite.com/lightweight-newscast-xml-sitemap-for-google-news.xml`
- **Alternative URL**: `https://yoursite.com/?news_sitemap_google_news=1`

Both URLs work regardless of which SEO plugins you have active.

## 📤 Submit to Google Search Console

1. Go to [Google Search Console](https://search.google.com/search-console/)
2. Add your website property and verify ownership
3. Navigate to **Sitemaps** in the left menu
4. Submit your sitemap URL: `https://yoursite.com/lightweight-newscast-xml-sitemap-for-google-news.xml`
5. Wait for Google to crawl and index your content

## 🔌 SEO Plugin Compatibility

This plugin is specifically designed to work alongside popular SEO plugins:

- ✅ **Yoast SEO**: Works alongside Yoast's XML sitemaps
- ✅ **Rank Math**: Compatible with Rank Math's SEO features  
- ✅ **All in One SEO**: Works with AIOSEO sitemaps
- ✅ **Other SEO plugins**: Designed to avoid conflicts

## 🎯 Perfect For

- News websites and blogs
- Magazine and publication sites
- Content creators who want better Google News visibility
- SEO professionals managing news content

## 🛠️ Technical Details

### Generated XML Format
The plugin generates XML sitemaps following Google News specifications:

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" 
        xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">
  <url>
    <loc>https://example.com/news-article/</loc>
    <news:news>
      <news:publication>
        <news:name>Your Publication Name</news:name>
        <news:language>en</news:language>
      </news:publication>
      <news:publication_date>2025-09-22T14:30:00Z</news:publication_date>
      <news:title>Article Title</news:title>
    </news:news>
  </url>
</urlset>
```

### Security Features
- Input sanitization using WordPress functions
- Proper escaping of output data
- Nonce verification where applicable
- Follows WordPress coding standards

## 🐛 Troubleshooting

### Sitemap Not Loading?
1. Check if pretty permalinks are enabled
2. Try the alternative URL: `/?news_sitemap_google_news=1`
3. Flush permalinks: Settings > Permalinks > Save Changes

### Empty Sitemap?
1. Ensure you have published posts within the maximum age limit
2. Check if selected post types have published content
3. Verify category filters aren't too restrictive

### SEO Plugin Conflicts?
The plugin is designed to avoid conflicts, but if issues occur:
1. Use the alternative query parameter URL
2. Check plugin load order
3. Contact support with specific error details

## 📝 Changelog

### 1.0.0
- Initial release with core functionality
- Added XML sitemap generation for Google News
- Implemented settings page with customization options
- Added support for pretty permalinks
- Added direct URL interception for SEO plugin compatibility
- Improved admin interface with better instructions
- Added Google News submission guidelines
- Fixed compatibility issues with Yoast SEO and All in One SEO
- Fixed empty publication name and language fields
- Added fallback values for required fields
- Improved error handling and validation
- Improved WordPress.org compliance with unique prefixes
- Enhanced Google News compatibility descriptions
- Added Buy Me Coffee donation support

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request. For major changes, please open an issue first to discuss what you would like to change.

### Development Setup
1. Clone the repository
2. Set up a local WordPress development environment
3. Install the plugin in your test site
4. Make your changes and test thoroughly

## 📄 License

This plugin is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## 👨‍💻 Author

**Gunjan Jaswaal**
- Website: [gunjanjaswal.me](https://gunjanjaswal.me)
- GitHub: [@gunjanjaswal](https://github.com/gunjanjaswal)

## ☕ Support Development

If this plugin has been helpful for your website, consider supporting its development:

[![Buy Me Coffee](https://img.shields.io/badge/☕-Buy%20me%20a%20coffee-red.svg)](https://www.buymeacoffee.com/gunjanjaswal)

## 📞 Support

For support, feature requests, or bug reports:
- Create an issue on [GitHub](https://github.com/gunjanjaswal/Google-News-Sitemap-Wordpress)
- Contact via [website](https://gunjanjaswal.me)

---

**Made with ❤️ for the WordPress community**
