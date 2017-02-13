<?php
/**
 * Plugin Name: DW Shortlinks
 * Description: Simplify your outbound links.
 * Version: 1.0.0
 * Author: DesignWall
 * Author URI: https://www.designwall.com
 *
 * Text Domain: dwsl
 */

add_action( 'created_dw-shortlink_type', 'dwsl_flush_rewrite_rules' );
add_action( 'delete_dw-shortlink_type', 'dwsl_flush_rewrite_rules' );
add_action( 'edited_dw-shortlink_type', 'dwsl_flush_rewrite_rules' );
add_action( 'plugins_loaded', 'dwsl_load_domaintext' );

function dwsl_load_domaintext() {
	$locale = get_locale();
	$mo = 'dwsl-' . $locale . '.mo';
	
	load_textdomain( 'dwsl', WP_LANG_DIR . '/dwsl/' . $mo );
	load_textdomain( 'dwsl', plugin_dir_path( __FILE__ ) . $mo );
	load_plugin_textdomain( 'dwsl' );
}

function dwsl_flush_rewrite_rules() {
	update_option( 'dwsl_flush_rewrite_rules', true );
}

function dwsl_init() {

	$flush = get_option( 'dwsl_flush_rewrite_rules', false );
	if ( $flush ) {
		flush_rewrite_rules( true );
		delete_option( 'dwsl_flush_rewrite_rules' );
	}

	$slug = apply_filters( 'dwsl_get_slug', get_option( 'dwsl_permalink', 'shortlink' ) );

	register_taxonomy( 'dw-shortlink_type', 'dw-shortlink', array(
		'labels' => array(
			'name'                       => __( 'Type', 'dwsl' ),
			'singular_name'              => __( 'Type', 'dwsl' ),
			'search_items'               => __( 'Search type', 'dwsl' ),
			'popular_items'              => __( 'Popular type', 'dwsl' ),
			'all_items'                  => __( 'All type', 'dwsl' ),
			'edit_item'                  => __( 'Edit type', 'dwsl' ),
			'update_item'                => __( 'Update type', 'dwsl' ),
			'add_new_item'               => __( 'Add New type', 'dwsl' ),
			'new_item_name'              => __( 'New type Name', 'dwsl' ),
			'separate_items_with_commas' => __( 'Separate type with commas', 'dwsl' ),
			'add_or_remove_items'        => __( 'Add or remove type', 'dwsl' ),
			'choose_from_most_used'      => __( 'Choose from the most used type', 'dwsl' ),
			'not_found'                  => __( 'No type found', 'dwsl' ),
			'menu_name'                  => __( 'Type', 'dwsl' ),
		),
		'public' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'hierarchical' => true,
		'query_var' => true,
		'rewrite' => true
	) );

	// add custom post type
	register_post_type( 'dw-shortlink', array(
		'labels' => array(
			'name' => __( 'Shortlinks', 'dwsl' ),
			'singular_name' => __( 'Shortlink', 'dwsl' ),
			'add_new' => __( 'Add New', 'dwsl' ),
			'add_new_item' => __( 'Add New', 'dwsl' ),
			'edit_item' => __( 'Edit', 'dwsl' ),
			'new_item'	=> __( 'New', 'dwsl' ),
			'view_item' => __( 'View', 'dwsl' ),
			'search_items' => __( 'Search', 'dwsl' ),
			'not_found' => __( 'Shortlinks not found', 'dwsl' ),
			'not_found_in_trash' => __( 'Shortlinks not found in trash', 'dwsl' ),
			'parent_item_colon' => __( 'Parent:', 'dwsl' ),
			'menu_name' => __( 'Shortlinks', 'dwsl' )
		),
		'hierarchical' => false,
		'public' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_nav_menus' => false,
		'show_in_admin_bar' => false,
		'publicly_queryable' => true,
		'exclude_from_search' => true,
		'has_archive' => false,
		'query_var' => false,
		'can_export' => true,
		'rewrite' => array( 'slug' => $slug ),
		'capability_type' => 'post',
		'supports' => array( 'title' )
	) );
}
add_action( 'init', 'dwsl_init' );

function dwsl_add_meta_box() {
	add_meta_box(
		'dwsl_metabox',
		__( 'Shortlink', 'dwsl' ),
		'dwsl_metabox',
		'dw-shortlink',
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'dwsl_add_meta_box' );

function dwsl_metabox() {
	$url = get_post_meta( get_the_ID(), '_dwsl_url', true );
	?>
	<table class="form-table">
		<tr>
			<th><?php _e( 'URL', 'dwsl' ) ?></th>
			<td><input type="text" value="<?php echo esc_url( $url ) ?>" class="widefat" name="dw_shortlinks_url" /></td>
		</tr>
	</table>
	<?php
}

add_action( 'admin_init', 'dwsl_add_setting' );
function dwsl_add_setting() {
	add_settings_field( 
		'dwsl_permalink', 
		__( 'Shortlink base', 'dwsl' ), 
		'dwsl_setting_fields',
		'permalink',
		'optional'
	);
}

function dwsl_setting_fields() {
	$shortlinks_base = get_option( 'dwsl_permalink', 'shortlink' );
	?>
	<input type="text" name="dwsl_permalink" value="<?php echo esc_attr( $shortlinks_base ) ?>"></input><code>/my-shortlink</code>
	<?php wp_nonce_field( 'dwsl_permalink', 'dwsl_permalink_nonce' ); ?>
	<?php
}

add_action( 'admin_init', 'dwsl_save_permalink' );
function dwsl_save_permalink() {
	if ( !isset( $_POST['permalink_structure'] ) && !isset( $_POST['category_base'] ) ) return;

	if ( !current_user_can( 'manage_options' ) ) return;

	if ( !isset( $_POST['dwsl_permalink_nonce'] ) || !wp_verify_nonce( $_POST['dwsl_permalink_nonce'], 'dwsl_permalink' ) ) return;

	if ( isset( $_POST['dwsl_permalink'] ) ) {
		update_option( 'dwsl_permalink', sanitize_text_field( $_POST['dwsl_permalink'] ) );
		flush_rewrite_rules();
	}
}

add_action( 'save_post', 'dwsl_save_post', 10, 2 );
function dwsl_save_post( $post_id, $post ) {
	if ( !$post_id || empty( $post ) ) {
		return;
	}

	if ( 'dw-shortlink' !== $post->post_type ) {
		return;
	}

	if ( !current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( isset( $_POST['dw_shortlinks_url'] ) ) {
		update_post_meta( $post_id, '_dwsl_url', esc_url( $_POST['dw_shortlinks_url'] ) );
		flush_rewrite_rules( true );
	}
}

add_action( 'template_redirect', 'dwsl_template_redirect' );
function dwsl_template_redirect() {
	if ( is_singular( 'dw-shortlink' ) ) {
		$post_id = get_the_ID();
		$redirect_url = get_post_meta( get_the_ID(), '_dwsl_url', true );
		$redirect_url = apply_filters( 'dwls_redirect_url', $redirect_url, $post_id );
		dwsl_tracking( $post_id );
		if ( filter_var( $redirect_url, FILTER_VALIDATE_URL ) ) { 
			wp_redirect( esc_url( $redirect_url ) );
		} else {
			wp_redirect( home_url() );
		}
		die;
	}
}

add_filter( 'manage_dw-shortlink_posts_columns', 'dwsl_shortlink_columns' );
function dwsl_shortlink_columns( $columns ) {
	return array(
		'cb' => $columns['cb'],
		'title' => $columns['title'],
		'url' => __( 'URL', 'dwsl' ),
		'tracking' => __( 'Tracking', 'dwsl' )
	);
}

add_action( 'manage_dw-shortlink_posts_custom_column', 'dwsl_custom_columns', 10, 2 );
function dwsl_custom_columns( $columns, $post_id ) {
	switch ( $columns ) {
		case 'url':
			$url = get_post_meta( get_the_ID(), '_dwsl_url', true );
			echo '<code>' . esc_url( get_permalink( $post_id ) ) . '</code>';
			break;
		case 'tracking':
			$tracking = get_post_meta( get_the_ID(), '_dwsl_tracking', true );
			echo '<strong>';
			echo $tracking && !empty( $tracking ) ? absint( $tracking ) : absint( 0 );
			echo '</strong>';
			echo ' ' . __( 'click', 'dwsl' );
			break;
	}
}

function dwsl_tracking( $post_id ) {
	if ( 'dw-shortlink' !== get_post_type( $post_id ) ) {
		return;
	}

	$current_click = (int) get_post_meta( $post_id, '_dwsl_tracking', true );

	if ( !$current_click || empty( $current_click ) ) {
		$current_click = intval( 0 );
	}

	update_post_meta( $post_id, '_dwsl_tracking', $current_click + 1 );
}

add_action( 'init', 'dwsl_rewrite', 10 );
function dwsl_rewrite() {
	$terms = get_terms( array(
		'taxonomy' => 'dw-shortlink_type',
		'hide_empty' => false,
		'fields' => 'id=>slug'
	) );

	if ( !empty( $terms ) && !is_wp_error( $terms ) ) {
		foreach ( $terms as $term ) {
			add_rewrite_rule( '^' . $term . '/([^/]+)/?$', 'index.php?post_type=dw-shortlink&name=$matches[1]', 'top' );
		}
	}
}

add_filter( 'get_sample_permalink', 'dwsl_get_sample_link', 10, 2 );
add_filter( 'post_type_link', 'dwsl_get_sample_link', 10, 2 );
function dwsl_get_sample_link( $permalink, $post ) {
	$filter = current_filter();

	switch ( $filter ) {
		case 'get_sample_permalink':
			if ( 'dw-shortlink' === get_post_type( $post ) ) {
				global $wp_rewrite;
				$terms = wp_get_post_terms( $post, 'dw-shortlink_type' );
				$term_slug = false;
				if ( !empty( $terms ) && !is_wp_error( $terms ) ) {
					$term_slug = $terms[0]->slug;
				}

				if ( $term_slug ) {
					if ( $wp_rewrite->using_permalinks() ) {
						$permalink[0] = home_url( $term_slug . '/%postname%' );
					}
				}
			}
			break;
		
		case 'post_type_link':
		default:
			if ( 'dw-shortlink' === get_post_type( $post->ID ) ) {
				global $wp_rewrite;
				$terms = wp_get_post_terms( $post->ID, 'dw-shortlink_type' );

				$term_slug = false;
				if ( !empty( $terms ) && !is_wp_error( $terms ) ) {
					$term_slug = $terms[0]->slug;
				}

				if ( $term_slug ) {
					if ( $wp_rewrite->using_permalinks() ) {
						$permalink = home_url( $term_slug . '/' . $post->post_name );
					}
				}
			}
			break;
	}

	return $permalink;
}