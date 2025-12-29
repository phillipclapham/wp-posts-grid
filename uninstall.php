<?php
/**
 * Posts Grid Uninstall
 *
 * Fired when the plugin is deleted (not deactivated).
 * Removes all plugin options from the database.
 *
 * @package PostsGrid
 */

// Exit if not called by WordPress
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

/**
 * Remove all plugin options from the database
 */
function posts_grid_uninstall() {
    // All option names used by the plugin
    $options = array(
        // Typography
        'posts_grid_title_size',
        'posts_grid_title_weight',
        'posts_grid_title_line_height',
        'posts_grid_excerpt_size',
        'posts_grid_excerpt_line_height',
        'posts_grid_date_size',
        // Layout
        'posts_grid_grid_gap',
        'posts_grid_image_ratio',
        'posts_grid_border_radius',
        'posts_grid_content_padding',
        // Colors
        'posts_grid_title_color',
        'posts_grid_title_hover_color',
        'posts_grid_excerpt_color',
        'posts_grid_date_color',
        // Display Defaults
        'posts_grid_default_columns',
        'posts_grid_default_show_excerpt',
        'posts_grid_default_show_date',
        'posts_grid_default_show_image',
        'posts_grid_default_excerpt_length',
    );

    // Delete each option
    foreach ( $options as $option ) {
        delete_option( $option );
    }

    // For multisite, also clean up each blog
    if ( is_multisite() ) {
        $sites = get_sites( array( 'fields' => 'ids' ) );
        foreach ( $sites as $site_id ) {
            switch_to_blog( $site_id );
            foreach ( $options as $option ) {
                delete_option( $option );
            }
            restore_current_blog();
        }
    }
}

posts_grid_uninstall();
