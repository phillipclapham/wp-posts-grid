=== Posts Grid ===
Contributors: phillipclapham
Tags: posts, grid, shortcode, customizer, blog posts, duplicate prevention
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Lightweight, Customizer-configurable posts grid with duplicate prevention. Perfect replacement for WordPress.com a8c/blog-posts blocks.

== Description ==

Posts Grid is a lightweight shortcode plugin that displays your posts in a customizable grid layout. It was specifically designed to replace WordPress.com's proprietary `a8c/blog-posts` blocks for sites migrating to self-hosted WordPress.

= Key Features =

* **Duplicate Prevention** - When multiple grids appear on the same page, each post only shows once. First grid gets first pick, subsequent grids skip already-displayed posts.
* **Category Filtering** - Include or exclude specific categories by ID.
* **Post Pinning** - Pin specific posts to always appear in a grid, in your specified order.
* **Full Customizer Integration** - Adjust all styling from Appearance > Customize > Posts Grid.
* **Live Preview** - See changes instantly in the Customizer before publishing.
* **LCP Optimized** - First image gets priority loading for better Core Web Vitals.
* **Responsive** - Automatically stacks to single column on mobile.
* **Lightweight** - Single file, no dependencies, no bloat.

= Why This Plugin Exists =

WordPress.com uses proprietary Gutenberg blocks (`wp:a8c/blog-posts`) that don't work on self-hosted WordPress. When you migrate a site from WordPress.com to self-hosted WordPress (including Pressable, WP Engine, or any other host), these blocks render as empty content.

No standard plugin offers the key feature that WordPress.com's blocks had: **duplicate prevention across multiple blocks on the same page**. This plugin fills that gap.

= Customizer Settings =

All styling is controlled from Appearance > Customize > Posts Grid:

**Typography**
* Title size, weight, and line height
* Excerpt size and line height
* Date size

**Layout**
* Grid gap between items
* Image aspect ratio (16:9, 4:3, 1:1, or auto)
* Image border radius
* Content padding

**Colors**
* Title color and hover color
* Excerpt color
* Date color

**Display Defaults**
* Default number of columns
* Show/hide excerpt, date, and image
* Default excerpt word count

= Basic Usage =

`[posts_grid]`

Displays 4 posts in a 2-column grid with default styling.

= Advanced Usage =

`[posts_grid count="6" columns="3" exclude_cats="15,23" exclude_displayed="true"]`

Displays 6 posts in 3 columns, excluding categories 15 and 23, and preventing duplicates if other grids exist on the page.

= Backward Compatibility =

The `[inc_posts]` shortcode is aliased to `[posts_grid]` for sites migrating from version 1.0.

== Installation ==

= From WordPress Admin =

1. Go to Plugins > Add New
2. Search for "Posts Grid"
3. Click Install Now, then Activate

= Manual Installation =

1. Download the plugin zip file
2. Go to Plugins > Add New > Upload Plugin
3. Upload the zip file and click Install Now
4. Activate the plugin

= As Must-Use Plugin =

For managed hosting or to prevent accidental deactivation:

1. Copy `posts-grid.php` to `/wp-content/mu-plugins/`
2. The plugin loads automatically (no activation needed)

== Frequently Asked Questions ==

= How do I find category IDs? =

In WordPress Admin, go to Posts > Categories. Hover over a category name and look at the URL - you'll see `tag_ID=XX` where XX is the ID.

Or use WP-CLI: `wp term list category --fields=term_id,name`

= Can I use multiple grids on the same page? =

Yes! That's what this plugin is designed for. Use `exclude_displayed="true"` (the default) and each post will only appear in one grid. The first grid gets first pick of posts.

= How do I pin specific posts? =

Use the `specific_posts` attribute with comma-separated post IDs:

`[posts_grid specific_posts="123,456,789"]`

Posts appear in the order you specify.

= Can I override Customizer settings per-shortcode? =

Yes. Shortcode attributes override Customizer defaults. For example:

`[posts_grid title_size="32" columns="3"]`

This uses 32px titles and 3 columns regardless of Customizer settings.

= Does this work with custom post types? =

Currently, only standard posts are supported. Custom post type support may be added in a future version.

= Will this slow down my site? =

No. The plugin is very lightweight (~35KB single file), outputs minimal CSS, and uses WordPress's built-in query caching. First images are optimized with `fetchpriority="high"` for better LCP scores.

== Screenshots ==

1. Customizer panel showing typography and layout options
2. Example 2-column grid on a news site
3. Example 3-column grid with image hover effect
4. Mobile responsive single-column layout

== Changelog ==

= 2.0.0 =
* Added: Full WordPress Customizer integration
* Added: Live preview in Customizer
* Added: Typography controls (title, excerpt, date sizing)
* Added: Layout controls (grid gap, aspect ratio, border radius, padding)
* Added: Color controls (title, hover, excerpt, date)
* Added: Display default controls
* Added: Style override shortcode attributes
* Added: Activation hook to set default options
* Changed: Renamed from inc-posts to posts-grid
* Changed: Class names updated (inc-* to posts-grid-*)
* Maintained: Full backward compatibility with [inc_posts] shortcode

= 1.0.0 =
* Initial release
* Core shortcode functionality
* Duplicate prevention across multiple blocks
* Category filtering (include/exclude)
* Post pinning (specific_posts)
* LCP-optimized image loading
* Responsive grid layout

== Upgrade Notice ==

= 2.0.0 =
Major update with full Customizer integration. All v1.0 shortcodes continue to work. No action required - just enjoy the new styling options!
