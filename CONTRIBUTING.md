# Contributing to Lightweight Newscast XML Sitemap For Google News

Thank you for your interest in contributing to Lightweight Newscast XML Sitemap For Google News! This document provides guidelines and information for contributors.

## 🤝 How to Contribute

### Reporting Bugs
1. Check if the bug has already been reported in [Issues](https://github.com/gunjanjaswal/Google-News-Sitemap-Wordpress/issues)
2. If not, create a new issue with:
   - Clear description of the bug
   - Steps to reproduce
   - Expected vs actual behavior
   - WordPress version, PHP version, and active plugins
   - Screenshots if applicable

### Suggesting Features
1. Check existing [Issues](https://github.com/gunjanjaswal/Google-News-Sitemap-Wordpress/issues) for similar requests
2. Create a new issue with:
   - Clear description of the feature
   - Use case and benefits
   - Possible implementation approach

### Code Contributions

#### Prerequisites
- WordPress development environment
- PHP 7.0 or higher
- Git knowledge
- Understanding of WordPress coding standards

#### Development Setup
1. Fork the repository
2. Clone your fork locally:
   ```bash
   git clone https://github.com/yourusername/Google-News-Sitemap-Wordpress.git
   ```
3. Create a new branch for your feature:
   ```bash
   git checkout -b feature/your-feature-name
   ```
4. Set up a local WordPress development environment
5. Install the plugin in your test site

#### Coding Standards
- Follow [WordPress Coding Standards](https://developer.wordpress.org/coding-standards/)
- Use proper PHP DocBlocks for all functions and classes
- Sanitize all inputs and escape all outputs
- Use WordPress functions instead of native PHP where possible
- Maintain backward compatibility when possible

#### Code Style Guidelines
- Use tabs for indentation (WordPress standard)
- Use meaningful variable and function names
- Keep functions focused and single-purpose
- Add comments for complex logic
- Follow PSR-4 autoloading standards where applicable

#### Security Guidelines
- Always sanitize user inputs using WordPress functions:
  - `sanitize_text_field()`
  - `sanitize_email()`
  - `absint()` for integers
- Always escape outputs:
  - `esc_html()` for HTML content
  - `esc_attr()` for HTML attributes
  - `esc_url()` for URLs
- Use nonces for form submissions
- Validate user capabilities with `current_user_can()`

#### Testing
- Test your changes thoroughly in different scenarios
- Test with popular SEO plugins (Yoast SEO, Rank Math, All in One SEO)
- Test with different WordPress themes
- Verify sitemap XML output is valid
- Test both sitemap URL formats

#### Pull Request Process
1. Ensure your code follows WordPress coding standards
2. Update documentation if needed
3. Add or update tests if applicable
4. Update the changelog in README.md
5. Create a pull request with:
   - Clear title and description
   - Reference any related issues
   - List of changes made
   - Screenshots for UI changes

## 📝 Development Guidelines

### Plugin Structure
```
lightweight-newscast-xml-sitemap-for-google-news/
├── lightweight-newscast-xml-sitemap-for-google-news.php  # Main plugin file
├── uninstall.php                          # Cleanup on uninstall
├── readme.txt                             # WordPress.org readme
├── README.md                              # GitHub readme
├── CONTRIBUTING.md                        # This file
├── languages/                             # Translation files
│   └── lightweight-newscast-xml-sitemap-for-google-news.pot
└── assets/                                # Screenshots, banners
    ├── screenshot-1.png
    ├── screenshot-2.png
    └── banner-772x250.png
```

### Key Functions
- `NewsSitemap_Generator::generate_sitemap()` - Core sitemap generation
- `NewsSitemap_Generator::intercept_sitemap_request()` - URL handling
- `NewsSitemap_Generator::sanitize_options()` - Input sanitization
- `NewsSitemap_Generator::admin_page()` - Settings interface

### WordPress Hooks Used
- `init` - Plugin initialization
- `admin_menu` - Add admin menu
- `admin_init` - Register settings
- `template_redirect` - Handle sitemap requests
- `plugin_action_links_*` - Add plugin action links

### Database Options
- `newssitemap_options` - Main plugin settings array

## 🔍 Code Review Checklist

Before submitting, ensure your code meets these criteria:

### Security
- [ ] All inputs are sanitized
- [ ] All outputs are escaped
- [ ] User capabilities are checked
- [ ] Nonces are used for forms
- [ ] No SQL injection vulnerabilities
- [ ] No XSS vulnerabilities

### WordPress Standards
- [ ] Uses WordPress coding standards
- [ ] Uses WordPress functions over native PHP
- [ ] Proper text domain usage
- [ ] Internationalization ready
- [ ] No deprecated functions used

### Functionality
- [ ] Code works as expected
- [ ] No PHP errors or warnings
- [ ] Compatible with latest WordPress version
- [ ] Doesn't break existing functionality
- [ ] Proper error handling

### Performance
- [ ] No unnecessary database queries
- [ ] Efficient code execution
- [ ] Proper caching where applicable
- [ ] No memory leaks

## 🌐 Internationalization

### Adding Translatable Strings
- Use `__()` for returning translated strings
- Use `esc_html__()` for escaped HTML output
- Use `_e()` for echoing translated strings
- Use `esc_html_e()` for escaped HTML echo
- Always use the correct text domain: `lightweight-newscast-xml-sitemap-for-google-news`

### Example:
```php
// Good
echo '<h1>' . esc_html__('Settings', 'lightweight-newscast-xml-sitemap-for-google-news') . '</h1>';

// Bad
echo '<h1>Settings</h1>';
```

## 📋 Issue Labels

We use these labels to categorize issues:
- `bug` - Something isn't working
- `enhancement` - New feature or request
- `documentation` - Improvements or additions to documentation
- `good first issue` - Good for newcomers
- `help wanted` - Extra attention is needed
- `question` - Further information is requested

## 🚀 Release Process

1. Update version numbers in:
   - Main plugin file header
   - readme.txt stable tag
   - README.md badges
2. Update changelog in both README.md and readme.txt
3. Test thoroughly with different WordPress versions
4. Create GitHub release with tag
5. Submit to WordPress.org (maintainer only)

## 📞 Getting Help

If you need help with development:
- Check existing [Issues](https://github.com/gunjanjaswal/Google-News-Sitemap-Wordpress/issues)
- Create a new issue with the `question` label
- Contact the maintainer via [website](https://gunjanjaswal.me)

## 📄 License

By contributing to this project, you agree that your contributions will be licensed under the GPL v2 or later license.

---

Thank you for contributing to Lightweight Newscast XML Sitemap For Google News! 🎉
