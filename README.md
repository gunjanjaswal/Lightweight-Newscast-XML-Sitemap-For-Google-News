# Lightweight Newscast XML Sitemap For Google News

[![Support on Ko-fi](https://img.shields.io/badge/Ko--fi-Support-FF5E5B?style=flat&logo=ko-fi&logoColor=white)](https://ko-fi.com/gunjanjaswal)
[![WordPress Plugin Version](https://img.shields.io/badge/WordPress-7.0%20tested-blue.svg)](https://wordpress.org/)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)](https://php.net/)
[![License](https://img.shields.io/badge/License-GPL%20v2%2B-green.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

A small WordPress plugin that produces the one sitemap Google News actually wants: a `news:`-namespaced XML feed of your recent articles, ready to drop into Search Console.

## Why this exists

Google News doesn't read your regular XML sitemap. It expects a separate feed built to its [News sitemap spec](https://support.google.com/news/publisher-center/answer/9606710) — the `news:` namespace, a publication block, and only articles from roughly the last two days. Most of the big SEO plugins either don't generate that feed, hide it behind a premium tier, or bundle it with a lot of other machinery you may not want running on a news site.

I wanted something that did that one job and nothing else. No dashboard takeover, no upsells, no fifty settings. Install it, fill in your publication name, submit the URL to Search Console, done. That's the whole plugin.

## What it does

At its simplest it exposes a sitemap at `/lightweight-newscast-xml-sitemap-for-google-news.xml` containing your posts from the last 48 hours, formatted the way Google News parses it. Around that core it handles the things that actually bite you in production:

- **Recent-articles window.** Only posts newer than your configured limit (1–48 hours) are included, which is what Google News wants. The cutoff is measured in UTC against `post_date_gmt`, so it doesn't drift when your site runs on a non-UTC timezone.
- **Caching.** The rendered XML is stored in a transient and served from cache until you publish, update, trash, or delete a post, at which point the cache is cleared. On a busy site being crawled often, this is the difference between a cheap request and a full `WP_Query` every hit.
- **Plays nicely with your SEO plugin.** It runs alongside Yoast, Rank Math, or All in One SEO instead of fighting them for the sitemap route. If a post is set to `noindex` in Rank Math or Yoast, it stays out of the news feed too. And if you've set a custom SEO title, that headline is used for `<news:title>`, falling back to the post title when the custom one is empty or its template variables can't be resolved.
- **Category and tag control.** Include only the categories you choose, and exclude specific categories or tags — handy for keeping Sponsored posts or press releases out of Google News.
- **Per-post opt-out.** A single checkbox in the post editor sidebar keeps any individual article out of the sitemap.
- **Featured images.** Each article's thumbnail is added through the Google Image sitemap extension (`<image:image>`).
- **Big archives.** When the number of eligible posts goes past the per-page limit, the plugin serves a sitemap index with paginated child sitemaps instead of one enormous file.
- **Multilingual.** On Polylang and WPML sites, each article's language is detected per post rather than assuming the site default, so a mixed-language feed reports the right `<news:language>` for every entry.
- **robots.txt.** The sitemap is advertised in `robots.txt` automatically so crawlers can find it.
- **WP-CLI.** `wp news-sitemap generate` and `wp news-sitemap flush` for scripting and deploys.
- **Health panel.** The settings screen lists the articles currently in the feed with their age and shows when the cache was last built, so you can see what Google will see.

It's translation-ready, and the `.pot` file ships in `languages/`.

## Requirements

- WordPress 5.0 or higher
- PHP 7.4 or higher
- Pretty permalinks (recommended — the `.xml` URL needs them; there's a query-string fallback if you can't enable them)

## Installation

From the WordPress admin: go to **Plugins > Add New > Upload Plugin**, choose the zip, install, and activate.

Manually: drop the `lightweight-newscast-xml-sitemap-for-google-news` folder into `/wp-content/plugins/`, then activate it from the Plugins screen.

Either way, finish up under **Settings > Lightweight Newscast XML Sitemap For Google News**.

## Configuration

The settings screen is short on purpose:

- **Publication Name** — your site's name exactly as it should appear in Google News.
- **Publication Language** — an ISO 639-1 code, e.g. `en`. On Polylang/WPML sites this is only the fallback; per-post language wins.
- **Post Types** — which post types to include.
- **Categories** — include only these, or leave empty to include everything.
- **Exclude Categories / Tags** — keep these out of the feed.
- **Respect SEO "noindex"** — skip posts marked noindex in Rank Math or Yoast. On by default.
- **Use SEO Title** — use the Rank Math/Yoast title as the headline. On by default.
- **Maximum Age** — how recent a post must be, 1–48 hours. Default 48.
- **Maximum Posts** — per-page cap, 1–1000. Default 1000. Go over it and the index/pagination kicks in.

## Sitemap URLs

- Primary: `https://yoursite.com/lightweight-newscast-xml-sitemap-for-google-news.xml`
- Fallback (no pretty permalinks): `https://yoursite.com/?news_sitemap_google_news=1`

Both work no matter which SEO plugins are active.

## Submitting to Google Search Console

1. Open [Search Console](https://search.google.com/search-console/) and add/verify your site if you haven't.
2. Go to **Sitemaps** in the sidebar.
3. Enter the sitemap URL above and submit.
4. Give Google time to crawl. Note that news content is time-sensitive by design — old articles fall out of the feed once they pass the age window, and that's expected.

## How it works under the hood

When WordPress boots, the plugin registers a rewrite rule for the `.xml` path (and watches for the query-string fallback). A request to that URL is intercepted before the normal template loads, the cached XML is returned if it's warm, and otherwise a `WP_Query` gathers the eligible posts, the XML is built and cached, then sent with the right content type. Publishing or editing a post clears that cached copy through the standard post-transition hooks, so the next crawl rebuilds it.

The output follows the Google News schema:

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

Text going into the XML is decoded from HTML entities and re-escaped for XML, so a curly apostrophe stored as `&rsquo;` becomes a real character rather than an entity that XML parsers reject. Output is escaped, input is sanitised with core WordPress functions, and the code follows the WordPress coding standards.

## Developer hooks

Everything worth overriding is filterable. All filters are prefixed `newssitemap_`:

| Filter | What it changes |
| --- | --- |
| `newssitemap_query_args` | The `WP_Query` args used to select posts |
| `newssitemap_cache_ttl` | Transient cache lifetime |
| `newssitemap_include_images` | Toggle the `<image:image>` extension |
| `newssitemap_include_keywords` | Toggle `<news:keywords>` built from post tags |
| `newssitemap_post_language` | Override the language reported for a post |
| `newssitemap_respect_noindex` | Whether to honour Rank Math/Yoast noindex |
| `newssitemap_use_seo_title` | Whether to use the SEO title as the headline |
| `newssitemap_post_title` | The final headline used for `<news:title>` |

WP-CLI:

```bash
wp news-sitemap generate   # rebuild the sitemap now
wp news-sitemap flush      # clear the cached copy
```

## Troubleshooting

**The sitemap 404s.** Almost always permalinks. Make sure pretty permalinks are on, then re-save them at **Settings > Permalinks**. If you can't use them, hit the `?news_sitemap_google_news=1` URL instead.

**The sitemap is empty.** Check that you actually have posts published inside the age window — a quiet news day past 48 hours legitimately produces an empty feed. Then confirm the selected post types have content and that your category/tag filters aren't excluding everything.

**Search Console won't read it.** Open the URL in a browser and make sure it's valid XML with no PHP notices printed above the declaration. If another plugin is claiming the route, switch to the query-string URL to confirm.

## Changelog

### 1.3.3
- **Fix**: Article titles containing HTML entities such as `&rsquo;` (a curly apostrophe added by `wptexturize`) no longer break the sitemap. These entities are valid in HTML but not in XML, which caused Google Search Console to reject the whole sitemap with "We were unable to read your Sitemap." Titles, publication name and keywords are now decoded to real characters and re-escaped for XML.

### 1.3.2
- **Maintenance**: Renamed the last few functions and the one global that still used an old prefix, so everything now sits behind the same `newssitemap` name. Internal tidying that clears the naming warnings from Plugin Check; the sitemap output and your settings are untouched.

### 1.3.1
- **Fix**: On Polylang sites the sitemap now includes articles from every language, not just the site's default language. Posts in secondary languages were filtered out on the front end, which could leave the sitemap empty even when recent articles existed.

### 1.3.0
- **New**: Respects the "noindex" robots setting from Rank Math and Yoast SEO, so posts hidden from search stay out of the news sitemap. Toggle on the settings screen.
- **New**: Uses your custom Rank Math or Yoast SEO title as the article headline when one is set, with a safe fallback to the post title.
- **New**: Exclude specific categories and tags from the sitemap, for example Sponsored or Press Releases.
- **New**: The Sitemap Health panel now lists the actual articles in the sitemap with their age, and shows how long ago the cache was built.
- **Fix**: The 48-hour window is now measured in UTC against `post_date_gmt`, so it stays accurate regardless of the site's timezone.
- **New**: Developer filters `newssitemap_respect_noindex`, `newssitemap_use_seo_title`, and `newssitemap_post_title`.

### 1.2.0
- **New**: Cached sitemap output (transient) with automatic cache busting on publish, update, trash and delete — major performance win for frequently crawled news sites.
- **New**: Per-post "Exclude from Google News sitemap" checkbox in the post editor sidebar.
- **New**: Featured image included per article via the Google Image sitemap extension (`<image:image>`).
- **New**: `<news:keywords>` generated from post tags (toggle via the `newssitemap_include_keywords` filter).
- **New**: Sitemap is advertised automatically in `robots.txt`.
- **New**: Automatic sitemap index with paginated child sitemaps when eligible posts exceed the per-page limit.
- **New**: WP-CLI commands `wp news-sitemap generate` and `wp news-sitemap flush`.
- **New**: Per-post language detection for Polylang and WPML (falls back to the global publication language).
- **New**: Sitemap Health panel on the settings screen (posts in window, cache status, manual cache clear).
- **New**: Developer filters — `newssitemap_query_args`, `newssitemap_cache_ttl`, `newssitemap_include_images`, `newssitemap_include_keywords`, `newssitemap_post_language`.

### 1.1.2
- Updated "Tested up to" to WordPress 7.0.
- Replaced Buy Me a Coffee donation link with Ko-fi (https://ko-fi.com/gunjanjaswal).
- Added "Contact Developer" link to plugin row meta on the Plugins screen.
- Author display name updated to "Gunjan Jaswal".

### 1.1.1
- Fixed WordPress coding standards: added proper prefixes to all global functions and variables.
- Function names now use the `lnxsfgn_` prefix for compliance.
- Improved code quality and WordPress.org plugin check compatibility.

### 1.1.0
- Updated for WordPress 6.9 compatibility.
- Updated minimum PHP requirement to 7.4.
- Added proper plugin headers (Plugin URI, Domain Path, Requires at least, Requires PHP, Tested up to).
- Enhanced WordPress coding standards compliance.
- Verified compatibility with WordPress 6.9 UTF-8 and frontend performance improvements.

### 1.0.0
- Initial release: XML sitemap generation for Google News.
- Settings page with customization options.
- Pretty-permalink support with a direct-URL fallback for SEO plugin compatibility.
- Fallback values for required fields (publication name and language) plus basic validation.
- Compatibility fixes for Yoast SEO and All in One SEO.

## Contributing

Pull requests are welcome. For anything substantial, open an issue first so we can talk through the approach before you spend time on it. To work on it locally, clone the repo into a test WordPress install, activate the plugin, and test your changes against real posts.

## License

GPL v2 or later.

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

## Author

**Gunjan Jaswal**
- Website: [gunjanjaswal.me](https://gunjanjaswal.me)
- GitHub: [@gunjanjaswal](https://github.com/gunjanjaswal)
- Email: hello@gunjanjaswal.me

## Support

Bug reports and feature requests go on [GitHub](https://github.com/gunjanjaswal/Lightweight-Newscast-XML-Sitemap-For-Google-News). If the plugin has saved you some trouble and you'd like to say thanks, there's a [Ko-fi](https://ko-fi.com/gunjanjaswal).
