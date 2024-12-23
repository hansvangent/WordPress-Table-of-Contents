# WordPress Table of Contents
This WordPress plugin integrates a customizable Table of Contents (TOC) with Rank Math SEO, supporting shortcode insertion, extensive styling options, and automatic 'SiteNavigationElement' Schema generation for improved search visibility.

## Features
- **Shortcode Driven:** Insert `[toc]` in your posts/pages for an automatic TOC.
- **Extensive Customization:**
  - Adjust width, font size, background, and more.
  - Exclude specific headings or use patterns to exclude ranges.
  - Enable hierarchical view or number list styles.
  - Smooth scroll with customizable offset for better navigation.
- **SEO Enhancement:** Integrates with Rank Math to generate `SiteNavigationElement` Schema.

## Quick Start

1. Clone this repository into your WordPress theme's directory.
2. Add the class to your theme's `functions.php`:

```php
require_once get_template_directory() . '/inc/usergrowth-toc.php';
new TableOfContents();
```

If you're using a child theme use the following code:

```php
require_once get_stylesheet_directory() . '/inc/usergrowth-toc.php';
new TableOfContents();
```

## Usage
After installation, simply insert the `[toc]` shortcode wherever you need a TOC. The shortcode will generate a TOC styled according to the customizations defined in the class while adhering to font styles and colors set in your theme.

## Get Help

- Reach out on [Twitter](https://twitter.com/jcvangent)
- Open an [issue on GitHub](https://github.com/hansvangent/WordPress-Table-of-Contents/issues/new)

## Contribute

#### Issues

For a bug report, bug fix, or a suggestion, please feel free to open an issue.

#### Pull request

Pull requests are always welcome, and I'll do my best to do reviews as quickly as possible.
