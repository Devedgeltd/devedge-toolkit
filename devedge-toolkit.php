<?php
/*
Plugin Name: DevEdge Toolkit
Description: A growing collection of useful WordPress admin enhancements by DevEdge Ltd, starting with search by post ID, displaying post IDs in admin lists, showing post IDs in comments, and making comments searchable by post ID. Now includes automatic GitHub update support.
Version: 1.5
Author: <a href="https://devedge.co.uk" target="_blank">DevEdge Ltd</a>
GitHub Plugin URI: https://github.com/YOUR-GITHUB-USERNAME/devedge-toolkit
*/

// Create DevEdge Toolkit settings page
add_action( 'admin_menu', function() {
    add_options_page(
        'DevEdge Toolkit Settings',
        'DevEdge Toolkit',
        'manage_options',
        'devedge-toolkit',
        'devedge_toolkit_settings_page'
    );
});

// Register settings
add_action( 'admin_init', function() {
    register_setting( 'devedge_toolkit_options', 'devedge_toolkit_options' );
});

// Settings page output
function devedge_toolkit_settings_page() {
    $options = get_option( 'devedge_toolkit_options' );
    ?>
    <div class="wrap">
        <h1>DevEdge Toolkit Settings</h1>
        <form method="post" action="options.php">
            <?php settings_fields( 'devedge_toolkit_options' ); ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Enable Search by Post ID</th>
                    <td><input type="checkbox" name="devedge_toolkit_options[search_by_id]" value="1" <?php checked( $options['search_by_id'], 1 ); ?> /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Show Post ID Column in Admin</th>
                    <td><input type="checkbox" name="devedge_toolkit_options[show_id_column]" value="1" <?php checked( $options['show_id_column'], 1 ); ?> /></td>
                </tr>
                <tr valign="top">
                    <th scope="row">Show Post ID in Comments & Make Searchable</th>
                    <td><input type="checkbox" name="devedge_toolkit_options[comments_post_id]" value="1" <?php checked( $options['comments_post_id'], 1 ); ?> /></td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}

// Conditionally enable search by post ID for posts
add_filter( 'posts_search', function( $search, $wp_query ) {
    $options = get_option( 'devedge_toolkit_options' );
    if ( isset( $options['search_by_id'] ) && $options['search_by_id'] && is_admin() && $wp_query->is_main_query() && !empty( $wp_query->query_vars['s'] ) ) {
        global $wpdb;
        $search_term = $wp_query->query_vars['s'];
        if ( is_numeric( $search_term ) ) {
            $search = $wpdb->prepare( " AND {$wpdb->posts}.ID = %d ", $search_term );
        }
    }
    return $search;
}, 10, 2 );

// Conditionally show ID column in post listings
function devedge_show_id_column_in_wp_admin( $columns ) {
    $columns['post_id'] = 'ID';
    return $columns;
}

function devedge_show_id_column_content( $column_name, $post_id ) {
    if ( $column_name === 'post_id' ) {
        echo $post_id;
    }
}

function devedge_add_id_column_to_post_types() {
    $options = get_option( 'devedge_toolkit_options' );
    if ( isset( $options['show_id_column'] ) && $options['show_id_column'] ) {
        $post_types = get_post_types( array( 'public' => true ), 'names' );
        foreach ( $post_types as $post_type ) {
            add_filter( "manage_{$post_type}_posts_columns", 'devedge_show_id_column_in_wp_admin' );
            add_action( "manage_{$post_type}_posts_custom_column", 'devedge_show_id_column_content', 10, 2 );
        }
    }
}
add_action( 'admin_init', 'devedge_add_id_column_to_post_types' );

// Add post ID column to comments admin table
add_filter( 'manage_edit-comments_columns', function( $columns ) {
    $options = get_option( 'devedge_toolkit_options' );
    if ( isset( $options['comments_post_id'] ) && $options['comments_post_id'] ) {
        $columns['post_id'] = 'Post ID';
    }
    return $columns;
});

add_action( 'manage_comments_custom_column', function( $column, $comment_ID ) {
    if ( $column === 'post_id' ) {
        $comment = get_comment( $comment_ID );
        echo '<a href="' . get_edit_post_link( $comment->comment_post_ID ) . '">' . $comment->comment_post_ID . '</a>';
    }
}, 10, 2 );

// Make searching comments by post ID display all comments for that post
add_action( 'pre_get_comments', function( $query ) {
    if ( is_admin() && $query->is_main_query() && !empty( $query->query_vars['search'] ) ) {
        $search_term = $query->query_vars['search'];
        if ( is_numeric( $search_term ) ) {
            $query->query_vars['post_id'] = $search_term;
            $query->query_vars['search'] = '';
        }
    }
});

// Auto-update integration from GitHub using GitHub Updater or similar system
