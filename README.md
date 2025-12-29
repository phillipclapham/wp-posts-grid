# Posts Grid

Lightweight, Customizer-configurable WordPress posts grid with duplicate prevention.

**Built to replace WordPress.com's proprietary `a8c/blog-posts` blocks for sites migrating to self-hosted WordPress.**

## The Problem

WordPress.com uses proprietary Gutenberg blocks (`wp:a8c/blog-posts`) that don't work on self-hosted WordPress. When you migrate a site from WordPress.com to self-hosted WordPress (Pressable, WP Engine, or any other host), these blocks render as empty content.

Existing post grid plugins don't offer the key feature that WordPress.com's blocks had: **duplicate prevention across multiple blocks on the same page**.

## The Solution

Posts Grid is a single-file plugin that:

- Tracks displayed posts across all shortcode instances on a page
- Automatically excludes already-displayed posts from subsequent grids
- Provides full WordPress Customizer integration for styling
- Works as a regular plugin OR as an mu-plugin

## Features

- **Duplicate Prevention** - Each post only appears once per page, even with multiple grids
- **Category Filtering** - Include or exclude specific categories
- **Post Pinning** - Pin specific posts to always appear, in your order
- **Full Customizer Integration** - 18 settings for typography, layout, colors, and defaults
- **Live Preview** - See changes instantly in the Customizer
- **LCP Optimized** - First image gets `fetchpriority="high"` for better Core Web Vitals
- **Responsive** - Stacks to single column on mobile
- **Lightweight** - Single file (~35KB), no dependencies

## Installation

### As Regular Plugin

1. Download `posts-grid.php`
2. Upload to `/wp-content/plugins/posts-grid/`
3. Activate via Plugins admin

### As Must-Use Plugin

1. Copy `posts-grid.php` to `/wp-content/mu-plugins/`
2. No activation needed - loads automatically

## Usage

### Basic

```
[posts_grid]
```

Displays 4 posts in a 2-column grid with default styling.

### With Options

```
[posts_grid count="6" columns="3" exclude_cats="15,23"]
```

6 posts, 3 columns, excluding categories 15 and 23.

### Pin Specific Posts

```
[posts_grid specific_posts="123,456,789"]
```

Shows only these posts, in the order specified.

### Multiple Grids (Duplicate Prevention)

```
[posts_grid count="4" columns="2"]
<!-- First 4 posts appear here -->

[posts_grid count="4" columns="2"]
<!-- Next 4 posts appear here (no duplicates) -->
```

## Shortcode Attributes

| Attribute | Default | Description |
|-----------|---------|-------------|
| `count` | 4 | Number of posts to display |
| `columns` | 2 | Grid columns (1-4) |
| `exclude_cats` | | Category IDs to exclude (comma-separated) |
| `include_cats` | | Only show posts from these categories |
| `specific_posts` | | Pin specific post IDs |
| `exclude_displayed` | true | Prevent duplicates across grids |
| `show_excerpt` | true | Display post excerpt |
| `show_date` | true | Display post date |
| `show_image` | true | Display featured image |
| `excerpt_length` | 25 | Words in excerpt |
| `layout` | grid | `grid` or `list` |
| `image_size` | medium_large | WordPress image size |
| `orderby` | date | Sort field |
| `order` | DESC | Sort direction |

### Style Override Attributes

These override Customizer settings per-shortcode:

| Attribute | Description |
|-----------|-------------|
| `title_size` | Title font size (px) |
| `title_weight` | Title font weight |
| `title_color` | Title color (hex) |
| `excerpt_size` | Excerpt font size (px) |
| `excerpt_color` | Excerpt color (hex) |
| `date_color` | Date color (hex) |
| `grid_gap` | Gap between items (rem) |
| `image_ratio` | `16-9`, `4-3`, `1-1`, or `auto` |

## Customizer Settings

Go to **Appearance > Customize > Posts Grid** to configure:

**Typography**
- Title size, weight, line height
- Excerpt size, line height
- Date size

**Layout**
- Grid gap
- Image aspect ratio
- Border radius
- Content padding

**Colors**
- Title color & hover color
- Excerpt color
- Date color

**Display Defaults**
- Default columns
- Show/hide excerpt, date, image
- Excerpt word count

## Backward Compatibility

The `[inc_posts]` shortcode is aliased to `[posts_grid]` for sites migrating from v1.0.

## Requirements

- WordPress 5.0+
- PHP 7.4+

## License

GPL v2 or later. See [LICENSE](LICENSE) for full text.

## Changelog

### 2.0.0
- Full WordPress Customizer integration
- Live preview in Customizer
- Typography, layout, and color controls
- Style override shortcode attributes
- Renamed from inc-posts to posts-grid
- Backward compatibility with `[inc_posts]`

### 1.0.0
- Initial release
- Duplicate prevention
- Category filtering
- Post pinning
- LCP-optimized image loading
