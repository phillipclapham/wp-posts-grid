<?php
/**
 * Plugin Name: Posts Grid
 * Plugin URI: https://github.com/phillipclapham/wp-posts-grid
 * Description: Lightweight, Customizer-configurable posts grid with duplicate prevention. Replaces WordPress.com a8c/blog-posts blocks for migrated sites.
 * Version: 2.0.0
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * Author: Phillip Clapham
 * Author URI: https://github.com/phillipclapham
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: posts-grid
 * Domain Path: /languages
 *
 * @package PostsGrid
 *
 * INSTALLATION:
 *   - As regular plugin: Upload to /wp-content/plugins/posts-grid/ and activate
 *   - As mu-plugin: Copy posts-grid.php to /wp-content/mu-plugins/
 *
 * CONFIGURATION:
 *   Go to Appearance > Customize > Posts Grid to configure styling.
 *
 * USAGE:
 *   [posts_grid]                                    - Basic: 4 posts, 2 columns
 *   [posts_grid count="6" columns="3"]              - 6 posts in 3 columns
 *   [posts_grid exclude_cats="123,456"]             - Exclude categories by ID
 *   [posts_grid include_cats="789"]                 - Only show from specific categories
 *   [posts_grid exclude_displayed="true"]           - Prevent duplicates across blocks
 *   [posts_grid specific_posts="101,102,103"]       - Show specific posts (pinned/featured)
 *   [posts_grid title_size="32" title_color="#333"] - Override styling per-instance
 *
 * BACKWARD COMPATIBILITY:
 *   [inc_posts] shortcode is aliased to [posts_grid] for sites migrating from v1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Plugin constants
define( 'POSTS_GRID_VERSION', '2.0.0' );
define( 'POSTS_GRID_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'POSTS_GRID_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Load plugin text domain for translations
 */
function posts_grid_load_textdomain() {
    load_plugin_textdomain( 'posts-grid', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'posts_grid_load_textdomain' );

/**
 * Plugin activation hook - set default options
 */
function posts_grid_activate() {
    // Only set defaults if options don't exist (preserve user settings on reactivation)
    $defaults = array(
        'posts_grid_title_size'            => 28,
        'posts_grid_title_weight'          => '700',
        'posts_grid_title_line_height'     => 1.2,
        'posts_grid_excerpt_size'          => 18,
        'posts_grid_excerpt_line_height'   => 1.6,
        'posts_grid_date_size'             => 14,
        'posts_grid_grid_gap'              => 1.5,
        'posts_grid_image_ratio'           => '16-9',
        'posts_grid_border_radius'         => 4,
        'posts_grid_content_padding'       => 0.75,
        'posts_grid_title_color'           => '',
        'posts_grid_title_hover_color'     => '',
        'posts_grid_excerpt_color'         => '#444444',
        'posts_grid_date_color'            => '#666666',
        'posts_grid_default_columns'       => '2',
        'posts_grid_default_show_excerpt'  => true,
        'posts_grid_default_show_date'     => true,
        'posts_grid_default_show_image'    => true,
        'posts_grid_default_excerpt_length' => 25,
    );

    foreach ( $defaults as $option => $value ) {
        if ( false === get_option( $option ) ) {
            add_option( $option, $value );
        }
    }
}
register_activation_hook( __FILE__, 'posts_grid_activate' );

/**
 * Track displayed post IDs across multiple shortcode instances on same page
 */
class Posts_Grid_Tracker {
    private static $displayed_ids = array();
    private static $instance_count = 0;

    public static function add( $id ) {
        self::$displayed_ids[] = (int) $id;
    }

    public static function get_all() {
        return self::$displayed_ids;
    }

    public static function reset() {
        self::$displayed_ids = array();
        self::$instance_count = 0;
    }

    public static function get_instance_id() {
        return ++self::$instance_count;
    }
}

// =============================================================================
// CUSTOMIZER SETTINGS
// =============================================================================

/**
 * Register Customizer settings, controls, and section
 */
function posts_grid_customize_register( $wp_customize ) {

    // Add Section
    $wp_customize->add_section( 'posts_grid_section', array(
        'title'       => __( 'Posts Grid', 'posts-grid' ),
        'description' => __( 'Configure the Posts Grid shortcode appearance. Changes apply to all [posts_grid] shortcodes unless overridden by shortcode attributes.', 'posts-grid' ),
        'priority'    => 160,
    ) );

    // =========================================================================
    // TYPOGRAPHY SETTINGS
    // =========================================================================

    // Title Size
    $wp_customize->add_setting( 'posts_grid_title_size', array(
        'default'           => 28,
        'type'              => 'option',
        'capability'        => 'edit_theme_options',
        'sanitize_callback' => 'absint',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'posts_grid_title_size', array(
        'label'       => __( 'Title Size (px)', 'posts-grid' ),
        'section'     => 'posts_grid_section',
        'type'        => 'number',
        'input_attrs' => array( 'min' => 12, 'max' => 72, 'step' => 1 ),
    ) );

    // Title Weight
    $wp_customize->add_setting( 'posts_grid_title_weight', array(
        'default'           => '700',
        'type'              => 'option',
        'capability'        => 'edit_theme_options',
        'sanitize_callback' => 'posts_grid_sanitize_weight',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'posts_grid_title_weight', array(
        'label'   => __( 'Title Weight', 'posts-grid' ),
        'section' => 'posts_grid_section',
        'type'    => 'select',
        'choices' => array(
            '400' => __( 'Normal (400)', 'posts-grid' ),
            '500' => __( 'Medium (500)', 'posts-grid' ),
            '600' => __( 'Semi-Bold (600)', 'posts-grid' ),
            '700' => __( 'Bold (700)', 'posts-grid' ),
        ),
    ) );

    // Title Line Height
    $wp_customize->add_setting( 'posts_grid_title_line_height', array(
        'default'           => 1.2,
        'type'              => 'option',
        'capability'        => 'edit_theme_options',
        'sanitize_callback' => 'posts_grid_sanitize_float',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'posts_grid_title_line_height', array(
        'label'       => __( 'Title Line Height', 'posts-grid' ),
        'section'     => 'posts_grid_section',
        'type'        => 'number',
        'input_attrs' => array( 'min' => 0.8, 'max' => 2.5, 'step' => 0.1 ),
    ) );

    // Excerpt Size
    $wp_customize->add_setting( 'posts_grid_excerpt_size', array(
        'default'           => 18,
        'type'              => 'option',
        'capability'        => 'edit_theme_options',
        'sanitize_callback' => 'absint',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'posts_grid_excerpt_size', array(
        'label'       => __( 'Excerpt Size (px)', 'posts-grid' ),
        'section'     => 'posts_grid_section',
        'type'        => 'number',
        'input_attrs' => array( 'min' => 10, 'max' => 36, 'step' => 1 ),
    ) );

    // Excerpt Line Height
    $wp_customize->add_setting( 'posts_grid_excerpt_line_height', array(
        'default'           => 1.6,
        'type'              => 'option',
        'capability'        => 'edit_theme_options',
        'sanitize_callback' => 'posts_grid_sanitize_float',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'posts_grid_excerpt_line_height', array(
        'label'       => __( 'Excerpt Line Height', 'posts-grid' ),
        'section'     => 'posts_grid_section',
        'type'        => 'number',
        'input_attrs' => array( 'min' => 1.0, 'max' => 2.5, 'step' => 0.1 ),
    ) );

    // Date Size
    $wp_customize->add_setting( 'posts_grid_date_size', array(
        'default'           => 14,
        'type'              => 'option',
        'capability'        => 'edit_theme_options',
        'sanitize_callback' => 'absint',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'posts_grid_date_size', array(
        'label'       => __( 'Date Size (px)', 'posts-grid' ),
        'section'     => 'posts_grid_section',
        'type'        => 'number',
        'input_attrs' => array( 'min' => 10, 'max' => 24, 'step' => 1 ),
    ) );

    // =========================================================================
    // LAYOUT SETTINGS
    // =========================================================================

    // Grid Gap
    $wp_customize->add_setting( 'posts_grid_grid_gap', array(
        'default'           => 1.5,
        'type'              => 'option',
        'capability'        => 'edit_theme_options',
        'sanitize_callback' => 'posts_grid_sanitize_float',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'posts_grid_grid_gap', array(
        'label'       => __( 'Grid Gap (rem)', 'posts-grid' ),
        'section'     => 'posts_grid_section',
        'type'        => 'number',
        'input_attrs' => array( 'min' => 0.5, 'max' => 4, 'step' => 0.25 ),
    ) );

    // Image Aspect Ratio
    $wp_customize->add_setting( 'posts_grid_image_ratio', array(
        'default'           => '16-9',
        'type'              => 'option',
        'capability'        => 'edit_theme_options',
        'sanitize_callback' => 'posts_grid_sanitize_ratio',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'posts_grid_image_ratio', array(
        'label'   => __( 'Image Aspect Ratio', 'posts-grid' ),
        'section' => 'posts_grid_section',
        'type'    => 'select',
        'choices' => array(
            '16-9' => __( '16:9 (Widescreen)', 'posts-grid' ),
            '4-3'  => __( '4:3 (Standard)', 'posts-grid' ),
            '1-1'  => __( '1:1 (Square)', 'posts-grid' ),
            'auto' => __( 'Auto (Natural)', 'posts-grid' ),
        ),
    ) );

    // Border Radius
    $wp_customize->add_setting( 'posts_grid_border_radius', array(
        'default'           => 4,
        'type'              => 'option',
        'capability'        => 'edit_theme_options',
        'sanitize_callback' => 'absint',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'posts_grid_border_radius', array(
        'label'       => __( 'Image Border Radius (px)', 'posts-grid' ),
        'section'     => 'posts_grid_section',
        'type'        => 'number',
        'input_attrs' => array( 'min' => 0, 'max' => 30, 'step' => 1 ),
    ) );

    // Content Padding
    $wp_customize->add_setting( 'posts_grid_content_padding', array(
        'default'           => 0.75,
        'type'              => 'option',
        'capability'        => 'edit_theme_options',
        'sanitize_callback' => 'posts_grid_sanitize_float',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( 'posts_grid_content_padding', array(
        'label'       => __( 'Content Padding (rem)', 'posts-grid' ),
        'section'     => 'posts_grid_section',
        'type'        => 'number',
        'input_attrs' => array( 'min' => 0, 'max' => 3, 'step' => 0.25 ),
    ) );

    // =========================================================================
    // COLOR SETTINGS
    // =========================================================================

    // Title Color
    $wp_customize->add_setting( 'posts_grid_title_color', array(
        'default'           => '',
        'type'              => 'option',
        'capability'        => 'edit_theme_options',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control(
        $wp_customize,
        'posts_grid_title_color',
        array(
            'label'       => __( 'Title Color', 'posts-grid' ),
            'description' => __( 'Leave empty to inherit from theme', 'posts-grid' ),
            'section'     => 'posts_grid_section',
        )
    ) );

    // Title Hover Color
    $wp_customize->add_setting( 'posts_grid_title_hover_color', array(
        'default'           => '',
        'type'              => 'option',
        'capability'        => 'edit_theme_options',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control(
        $wp_customize,
        'posts_grid_title_hover_color',
        array(
            'label'       => __( 'Title Hover Color', 'posts-grid' ),
            'description' => __( 'Leave empty to inherit from theme', 'posts-grid' ),
            'section'     => 'posts_grid_section',
        )
    ) );

    // Excerpt Color
    $wp_customize->add_setting( 'posts_grid_excerpt_color', array(
        'default'           => '#444444',
        'type'              => 'option',
        'capability'        => 'edit_theme_options',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control(
        $wp_customize,
        'posts_grid_excerpt_color',
        array(
            'label'   => __( 'Excerpt Color', 'posts-grid' ),
            'section' => 'posts_grid_section',
        )
    ) );

    // Date Color
    $wp_customize->add_setting( 'posts_grid_date_color', array(
        'default'           => '#666666',
        'type'              => 'option',
        'capability'        => 'edit_theme_options',
        'sanitize_callback' => 'sanitize_hex_color',
        'transport'         => 'postMessage',
    ) );
    $wp_customize->add_control( new WP_Customize_Color_Control(
        $wp_customize,
        'posts_grid_date_color',
        array(
            'label'   => __( 'Date Color', 'posts-grid' ),
            'section' => 'posts_grid_section',
        )
    ) );

    // =========================================================================
    // DISPLAY DEFAULT SETTINGS
    // =========================================================================

    // Default Columns
    $wp_customize->add_setting( 'posts_grid_default_columns', array(
        'default'           => '2',
        'type'              => 'option',
        'capability'        => 'edit_theme_options',
        'sanitize_callback' => 'posts_grid_sanitize_columns',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'posts_grid_default_columns', array(
        'label'   => __( 'Default Columns', 'posts-grid' ),
        'section' => 'posts_grid_section',
        'type'    => 'select',
        'choices' => array(
            '1' => __( '1 Column', 'posts-grid' ),
            '2' => __( '2 Columns', 'posts-grid' ),
            '3' => __( '3 Columns', 'posts-grid' ),
            '4' => __( '4 Columns', 'posts-grid' ),
        ),
    ) );

    // Show Excerpt
    $wp_customize->add_setting( 'posts_grid_default_show_excerpt', array(
        'default'           => true,
        'type'              => 'option',
        'capability'        => 'edit_theme_options',
        'sanitize_callback' => 'posts_grid_sanitize_checkbox',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'posts_grid_default_show_excerpt', array(
        'label'   => __( 'Show Excerpt by Default', 'posts-grid' ),
        'section' => 'posts_grid_section',
        'type'    => 'checkbox',
    ) );

    // Show Date
    $wp_customize->add_setting( 'posts_grid_default_show_date', array(
        'default'           => true,
        'type'              => 'option',
        'capability'        => 'edit_theme_options',
        'sanitize_callback' => 'posts_grid_sanitize_checkbox',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'posts_grid_default_show_date', array(
        'label'   => __( 'Show Date by Default', 'posts-grid' ),
        'section' => 'posts_grid_section',
        'type'    => 'checkbox',
    ) );

    // Show Image
    $wp_customize->add_setting( 'posts_grid_default_show_image', array(
        'default'           => true,
        'type'              => 'option',
        'capability'        => 'edit_theme_options',
        'sanitize_callback' => 'posts_grid_sanitize_checkbox',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'posts_grid_default_show_image', array(
        'label'   => __( 'Show Image by Default', 'posts-grid' ),
        'section' => 'posts_grid_section',
        'type'    => 'checkbox',
    ) );

    // Default Excerpt Length
    $wp_customize->add_setting( 'posts_grid_default_excerpt_length', array(
        'default'           => 25,
        'type'              => 'option',
        'capability'        => 'edit_theme_options',
        'sanitize_callback' => 'absint',
        'transport'         => 'refresh',
    ) );
    $wp_customize->add_control( 'posts_grid_default_excerpt_length', array(
        'label'       => __( 'Default Excerpt Length (words)', 'posts-grid' ),
        'section'     => 'posts_grid_section',
        'type'        => 'number',
        'input_attrs' => array( 'min' => 5, 'max' => 100, 'step' => 5 ),
    ) );
}
add_action( 'customize_register', 'posts_grid_customize_register' );

// =============================================================================
// SANITIZATION CALLBACKS
// =============================================================================

/**
 * Sanitize font weight selection
 */
function posts_grid_sanitize_weight( $value ) {
    $valid = array( '400', '500', '600', '700' );
    return in_array( $value, $valid, true ) ? $value : '700';
}

/**
 * Sanitize aspect ratio selection
 */
function posts_grid_sanitize_ratio( $value ) {
    $valid = array( '16-9', '4-3', '1-1', 'auto' );
    return in_array( $value, $valid, true ) ? $value : '16-9';
}

/**
 * Sanitize columns selection
 */
function posts_grid_sanitize_columns( $value ) {
    $valid = array( '1', '2', '3', '4' );
    return in_array( $value, $valid, true ) ? $value : '2';
}

/**
 * Sanitize float values
 */
function posts_grid_sanitize_float( $value ) {
    return floatval( $value );
}

/**
 * Sanitize checkbox values
 */
function posts_grid_sanitize_checkbox( $value ) {
    return (bool) $value;
}

// =============================================================================
// HELPER: GET SETTINGS WITH DEFAULTS
// =============================================================================

/**
 * Get all plugin settings with defaults
 *
 * @return array Settings array
 */
function posts_grid_get_settings() {
    return array(
        // Typography
        'title_size'          => (int) get_option( 'posts_grid_title_size', 28 ),
        'title_weight'        => get_option( 'posts_grid_title_weight', '700' ),
        'title_line_height'   => (float) get_option( 'posts_grid_title_line_height', 1.2 ),
        'excerpt_size'        => (int) get_option( 'posts_grid_excerpt_size', 18 ),
        'excerpt_line_height' => (float) get_option( 'posts_grid_excerpt_line_height', 1.6 ),
        'date_size'           => (int) get_option( 'posts_grid_date_size', 14 ),
        // Layout
        'grid_gap'            => (float) get_option( 'posts_grid_grid_gap', 1.5 ),
        'image_ratio'         => get_option( 'posts_grid_image_ratio', '16-9' ),
        'border_radius'       => (int) get_option( 'posts_grid_border_radius', 4 ),
        'content_padding'     => (float) get_option( 'posts_grid_content_padding', 0.75 ),
        // Colors
        'title_color'         => get_option( 'posts_grid_title_color', '' ),
        'title_hover_color'   => get_option( 'posts_grid_title_hover_color', '' ),
        'excerpt_color'       => get_option( 'posts_grid_excerpt_color', '#444444' ),
        'date_color'          => get_option( 'posts_grid_date_color', '#666666' ),
        // Display Defaults
        'default_columns'        => get_option( 'posts_grid_default_columns', '2' ),
        'default_show_excerpt'   => (bool) get_option( 'posts_grid_default_show_excerpt', true ),
        'default_show_date'      => (bool) get_option( 'posts_grid_default_show_date', true ),
        'default_show_image'     => (bool) get_option( 'posts_grid_default_show_image', true ),
        'default_excerpt_length' => (int) get_option( 'posts_grid_default_excerpt_length', 25 ),
    );
}

// =============================================================================
// DYNAMIC CSS OUTPUT
// =============================================================================

/**
 * Output dynamic CSS styles based on Customizer settings
 */
function posts_grid_dynamic_styles() {
    $s = posts_grid_get_settings();

    // Convert image ratio to CSS value
    $ratio_map = array(
        '16-9' => '16/9',
        '4-3'  => '4/3',
        '1-1'  => '1/1',
        'auto' => 'auto',
    );
    $aspect_ratio = isset( $ratio_map[ $s['image_ratio'] ] ) ? $ratio_map[ $s['image_ratio'] ] : '16/9';
    $object_fit = 'auto' === $s['image_ratio'] ? 'contain' : 'cover';

    // Build color rules (empty = inherit from theme)
    $title_color_rule   = $s['title_color'] ? "color: {$s['title_color']};" : '';
    $title_hover_rule   = $s['title_hover_color'] ? "color: {$s['title_hover_color']};" : '';
    $excerpt_color_rule = $s['excerpt_color'] ? "color: {$s['excerpt_color']};" : '';
    $date_color_rule    = $s['date_color'] ? "color: {$s['date_color']};" : '';
    ?>
    <style id="posts-grid-styles">
    /* Posts Grid - Dynamic Styles (v<?php echo esc_attr( POSTS_GRID_VERSION ); ?>) */
    .posts-grid-wrap {
        display: grid;
        gap: <?php echo esc_attr( $s['grid_gap'] ); ?>rem;
        margin: 1.5rem 0;
    }

    /* Column configurations */
    .posts-grid-cols-1 { grid-template-columns: 1fr; }
    .posts-grid-cols-2 { grid-template-columns: repeat(2, 1fr); }
    .posts-grid-cols-3 { grid-template-columns: repeat(3, 1fr); }
    .posts-grid-cols-4 { grid-template-columns: repeat(4, 1fr); }

    /* List layout override */
    .posts-grid-list {
        grid-template-columns: 1fr !important;
    }
    .posts-grid-list .posts-grid-item {
        flex-direction: row;
        gap: 1rem;
    }
    .posts-grid-list .posts-grid-image {
        flex: 0 0 200px;
    }

    /* Responsive - stack on mobile */
    @media (max-width: 768px) {
        .posts-grid-cols-2,
        .posts-grid-cols-3,
        .posts-grid-cols-4 {
            grid-template-columns: 1fr;
        }
        .posts-grid-list .posts-grid-item {
            flex-direction: column;
        }
        .posts-grid-list .posts-grid-image {
            flex: none;
        }
    }

    /* Tablet - 2 columns max */
    @media (min-width: 769px) and (max-width: 1024px) {
        .posts-grid-cols-3,
        .posts-grid-cols-4 {
            grid-template-columns: repeat(2, 1fr);
        }
    }

    /* Post item */
    .posts-grid-item {
        display: flex;
        flex-direction: column;
    }

    /* Image container */
    .posts-grid-image {
        display: block;
        overflow: hidden;
        border-radius: <?php echo esc_attr( $s['border_radius'] ); ?>px;
    }
    .posts-grid-image img {
        width: 100%;
        height: auto;
        aspect-ratio: <?php echo esc_attr( $aspect_ratio ); ?>;
        object-fit: <?php echo esc_attr( $object_fit ); ?>;
        transition: transform 0.3s ease;
    }
    .posts-grid-image:hover img {
        transform: scale(1.03);
    }

    /* Content */
    .posts-grid-content {
        padding: <?php echo esc_attr( $s['content_padding'] ); ?>rem 0;
    }

    /* Title */
    .posts-grid-title {
        margin: 0 0 0.5rem 0;
        font-size: <?php echo esc_attr( $s['title_size'] ); ?>px;
        font-weight: <?php echo esc_attr( $s['title_weight'] ); ?>;
        line-height: <?php echo esc_attr( $s['title_line_height'] ); ?>;
    }
    .posts-grid-title a {
        text-decoration: none;
        <?php echo $title_color_rule; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    }
    .posts-grid-title a:hover {
        text-decoration: underline;
        <?php echo $title_hover_rule; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
    }

    /* Date */
    .posts-grid-date {
        display: block;
        font-size: <?php echo esc_attr( $s['date_size'] ); ?>px;
        <?php echo $date_color_rule; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        margin-bottom: 0.5rem;
    }

    /* Excerpt */
    .posts-grid-excerpt {
        font-size: <?php echo esc_attr( $s['excerpt_size'] ); ?>px;
        line-height: <?php echo esc_attr( $s['excerpt_line_height'] ); ?>;
        <?php echo $excerpt_color_rule; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
        margin: 0;
    }
    </style>
    <?php
}
add_action( 'wp_head', 'posts_grid_dynamic_styles', 5 );

// =============================================================================
// CUSTOMIZER LIVE PREVIEW
// =============================================================================

/**
 * Enqueue Customizer live preview JavaScript
 */
function posts_grid_customize_preview_js() {
    ?>
    <script>
    (function($) {
        if (typeof wp === 'undefined' || typeof wp.customize === 'undefined') return;

        // Typography
        wp.customize('posts_grid_title_size', function(value) {
            value.bind(function(newval) {
                $('.posts-grid-title').css('font-size', newval + 'px');
            });
        });
        wp.customize('posts_grid_title_weight', function(value) {
            value.bind(function(newval) {
                $('.posts-grid-title').css('font-weight', newval);
            });
        });
        wp.customize('posts_grid_title_line_height', function(value) {
            value.bind(function(newval) {
                $('.posts-grid-title').css('line-height', newval);
            });
        });
        wp.customize('posts_grid_excerpt_size', function(value) {
            value.bind(function(newval) {
                $('.posts-grid-excerpt').css('font-size', newval + 'px');
            });
        });
        wp.customize('posts_grid_excerpt_line_height', function(value) {
            value.bind(function(newval) {
                $('.posts-grid-excerpt').css('line-height', newval);
            });
        });
        wp.customize('posts_grid_date_size', function(value) {
            value.bind(function(newval) {
                $('.posts-grid-date').css('font-size', newval + 'px');
            });
        });

        // Layout
        wp.customize('posts_grid_grid_gap', function(value) {
            value.bind(function(newval) {
                $('.posts-grid-wrap').css('gap', newval + 'rem');
            });
        });
        wp.customize('posts_grid_border_radius', function(value) {
            value.bind(function(newval) {
                $('.posts-grid-image').css('border-radius', newval + 'px');
            });
        });
        wp.customize('posts_grid_content_padding', function(value) {
            value.bind(function(newval) {
                $('.posts-grid-content').css('padding', newval + 'rem 0');
            });
        });
        wp.customize('posts_grid_image_ratio', function(value) {
            value.bind(function(newval) {
                var ratioMap = {'16-9': '16/9', '4-3': '4/3', '1-1': '1/1', 'auto': 'auto'};
                var ratio = ratioMap[newval] || '16/9';
                var fit = newval === 'auto' ? 'contain' : 'cover';
                $('.posts-grid-image img').css({'aspect-ratio': ratio, 'object-fit': fit});
            });
        });

        // Colors
        wp.customize('posts_grid_title_color', function(value) {
            value.bind(function(newval) {
                if (newval) {
                    $('.posts-grid-title a').css('color', newval);
                } else {
                    $('.posts-grid-title a').css('color', '');
                }
            });
        });
        wp.customize('posts_grid_title_hover_color', function(value) {
            value.bind(function(newval) {
                $('.posts-grid-title a').data('hover-color', newval);
            });
        });
        wp.customize('posts_grid_excerpt_color', function(value) {
            value.bind(function(newval) {
                $('.posts-grid-excerpt').css('color', newval || '');
            });
        });
        wp.customize('posts_grid_date_color', function(value) {
            value.bind(function(newval) {
                $('.posts-grid-date').css('color', newval || '');
            });
        });
    })(jQuery);
    </script>
    <?php
}
add_action( 'customize_preview_init', function() {
    add_action( 'wp_footer', 'posts_grid_customize_preview_js', 100 );
} );

// =============================================================================
// SHORTCODE
// =============================================================================

/**
 * Main shortcode handler
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML output.
 */
function posts_grid_shortcode( $atts ) {
    $s = posts_grid_get_settings();

    // Merge shortcode attributes with Customizer defaults
    $atts = shortcode_atts( array(
        // Query attributes
        'count'             => 4,
        'columns'           => $s['default_columns'],
        'exclude_cats'      => '',
        'include_cats'      => '',
        'specific_posts'    => '',
        'exclude_displayed' => 'true',
        'offset'            => 0,
        'show_excerpt'      => $s['default_show_excerpt'] ? 'true' : 'false',
        'excerpt_length'    => $s['default_excerpt_length'],
        'show_date'         => $s['default_show_date'] ? 'true' : 'false',
        'show_image'        => $s['default_show_image'] ? 'true' : 'false',
        'image_size'        => 'medium_large',
        'layout'            => 'grid',
        'id'                => '',
        'class'             => '',
        'orderby'           => 'date',
        'order'             => 'DESC',
        // Style override attributes (new in v2.0)
        'title_size'        => '',
        'title_weight'      => '',
        'title_color'       => '',
        'excerpt_size'      => '',
        'excerpt_color'     => '',
        'date_color'        => '',
        'grid_gap'          => '',
        'image_ratio'       => '',
    ), $atts, 'posts_grid' );

    // Sanitize query inputs
    $count          = max( 1, min( 50, intval( $atts['count'] ) ) );
    $columns        = max( 1, min( 4, intval( $atts['columns'] ) ) );
    $offset         = max( 0, intval( $atts['offset'] ) );
    $excerpt_length = max( 1, min( 100, intval( $atts['excerpt_length'] ) ) );
    $order          = 'ASC' === strtoupper( $atts['order'] ) ? 'ASC' : 'DESC';

    // Build query args
    $query_args = array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => $count,
        'offset'         => $offset,
        'orderby'        => sanitize_key( $atts['orderby'] ),
        'order'          => $order,
        'no_found_rows'  => true,
    );

    // Category exclusions
    if ( ! empty( $atts['exclude_cats'] ) ) {
        $exclude_ids = array_map( 'intval', array_filter( explode( ',', $atts['exclude_cats'] ) ) );
        if ( ! empty( $exclude_ids ) ) {
            $query_args['category__not_in'] = $exclude_ids;
        }
    }

    // Category inclusions
    if ( ! empty( $atts['include_cats'] ) ) {
        $include_ids = array_map( 'intval', array_filter( explode( ',', $atts['include_cats'] ) ) );
        if ( ! empty( $include_ids ) ) {
            $query_args['category__in'] = $include_ids;
        }
    }

    // Specific posts
    if ( ! empty( $atts['specific_posts'] ) ) {
        $specific_ids = array_map( 'intval', array_filter( explode( ',', $atts['specific_posts'] ) ) );
        if ( ! empty( $specific_ids ) ) {
            $query_args['post__in'] = $specific_ids;
            $query_args['orderby']  = 'post__in';
            unset( $query_args['offset'] );
        }
    }

    // Duplicate prevention
    if ( 'true' === $atts['exclude_displayed'] ) {
        $displayed = Posts_Grid_Tracker::get_all();
        if ( ! empty( $displayed ) ) {
            $existing_exclude            = isset( $query_args['post__not_in'] ) ? $query_args['post__not_in'] : array();
            $query_args['post__not_in'] = array_merge( $existing_exclude, $displayed );
        }
    }

    // Run query
    $query = new WP_Query( $query_args );

    if ( ! $query->have_posts() ) {
        wp_reset_postdata();
        return '<!-- posts_grid: no posts found -->';
    }

    // Build inline styles for shortcode overrides
    $inline_styles = posts_grid_build_inline_styles( $atts );

    // Build wrapper attributes
    $instance_id     = Posts_Grid_Tracker::get_instance_id();
    $wrapper_id      = ! empty( $atts['id'] ) ? sanitize_html_class( $atts['id'] ) : 'posts-grid-' . $instance_id;
    $wrapper_classes = array( 'posts-grid-wrap', 'posts-grid-cols-' . $columns );

    if ( 'list' === $atts['layout'] ) {
        $wrapper_classes[] = 'posts-grid-list';
    }

    if ( ! empty( $atts['class'] ) ) {
        $wrapper_classes[] = sanitize_html_class( $atts['class'] );
    }

    // Build output
    $output = sprintf(
        '<div id="%s" class="%s"%s>',
        esc_attr( $wrapper_id ),
        esc_attr( implode( ' ', $wrapper_classes ) ),
        $inline_styles ? ' style="' . esc_attr( $inline_styles ) . '"' : ''
    );

    $is_first = true;
    while ( $query->have_posts() ) {
        $query->the_post();
        $post_id = get_the_ID();

        // Track this post
        Posts_Grid_Tracker::add( $post_id );

        $output .= '<article class="posts-grid-item">';

        // Featured image
        if ( 'true' === $atts['show_image'] && has_post_thumbnail() ) {
            $img_attr = array();

            // First image in first instance gets priority loading
            if ( $is_first && 1 === $instance_id ) {
                $img_attr['fetchpriority'] = 'high';
                $img_attr['loading']       = 'eager';
                $is_first                  = false;
            } else {
                $img_attr['loading'] = 'lazy';
            }

            $output .= sprintf(
                '<a href="%s" class="posts-grid-image">%s</a>',
                esc_url( get_permalink() ),
                get_the_post_thumbnail( $post_id, $atts['image_size'], $img_attr )
            );
        }

        $output .= '<div class="posts-grid-content">';

        // Title
        $output .= sprintf(
            '<h3 class="posts-grid-title"><a href="%s">%s</a></h3>',
            esc_url( get_permalink() ),
            esc_html( get_the_title() )
        );

        // Date
        if ( 'true' === $atts['show_date'] ) {
            $output .= sprintf(
                '<time class="posts-grid-date" datetime="%s">%s</time>',
                esc_attr( get_the_date( 'c' ) ),
                esc_html( get_the_date( 'F j, Y' ) )
            );
        }

        // Excerpt
        if ( 'true' === $atts['show_excerpt'] ) {
            $excerpt = wp_trim_words( get_the_excerpt(), $excerpt_length, '&hellip;' );
            $output .= sprintf(
                '<p class="posts-grid-excerpt">%s</p>',
                esc_html( $excerpt )
            );
        }

        $output .= '</div>'; // .posts-grid-content
        $output .= '</article>';
    }

    $output .= '</div>'; // .posts-grid-wrap

    wp_reset_postdata();

    return $output;
}

/**
 * Build inline styles for shortcode overrides
 *
 * @param array $atts Shortcode attributes.
 * @return string CSS styles string.
 */
function posts_grid_build_inline_styles( $atts ) {
    $styles = array();

    if ( ! empty( $atts['grid_gap'] ) ) {
        $styles[] = 'gap: ' . floatval( $atts['grid_gap'] ) . 'rem';
    }

    return implode( '; ', $styles );
}

// Register shortcodes
add_shortcode( 'posts_grid', 'posts_grid_shortcode' );
add_shortcode( 'inc_posts', 'posts_grid_shortcode' ); // Backward compatibility alias

/**
 * Reset tracker on each page load
 */
function posts_grid_reset_tracker() {
    Posts_Grid_Tracker::reset();
}
add_action( 'wp', 'posts_grid_reset_tracker' );
