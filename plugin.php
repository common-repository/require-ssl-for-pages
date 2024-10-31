<?php
/*
Plugin Name: Define SSL Pages
Contributors: wpdavis
Tags: SSL, security
Requires at least: 3.0
Tested up to: 3.2
Version: 0.1
*/

// This plugin only works if you have forced SSL Login or SSL Admin
// The reason for this is in case you turn of SSL and forget to deactivate the plugin
if( ( defined( 'FORCE_SSL_LOGIN' ) && FORCE_SSL_LOGIN ) || ( defined( 'FORCE_SSL_ADMIN' ) && FORCE_SSL_ADMIN ) ) {

	// Function to add the meta box to the page edit screen
	add_action( 'add_meta_boxes', 'bdn_add_ssl_pages_metabox' );
	
	// Add action to save the meta box
	add_action( 'save_post', 'bdn_save_ssl_pages' );
	
	// Function to redirect to SSL version of the page if needed
	add_action( 'template_redirect', 'bdn_redirect_ssl_pages', 1 );
	
	// Filter the permalink
	add_filter( 'page_link', 'bdn_ssl_pages_permalink', 99, 2 );

}

//Actually hooks in the metabox
function bdn_add_ssl_pages_metabox( ) {
    add_meta_box(
        'bdn_ssl_page',
        __( 'Force SSL for page', 'bdn' ), 
        'bdn_ssl_pages_metabox',
        'page'
    );
}


//The actual metabox
function bdn_ssl_pages_metabox( $post ) {

	$force = get_post_meta( $post->ID, '_bdn_force_ssl', TRUE );
	
	wp_nonce_field( plugin_basename( __FILE__ ), 'bdn_ssl_pages_nonce' ); ?>
	
	Force SSL for page: <input type="checkbox" id="bdn_force_ssl" name="bdn_force_ssl" value="1" <?php if( !empty( $force ) ) { ?> checked="checked" <?php } ?> />
	
	<?php
}
 
//Save the metabox
function bdn_save_ssl_pages( $post_id ) {

	if ( empty( $_POST[ 'bdn_ssl_pages_nonce' ] ) || !wp_verify_nonce( $_POST[ 'bdn_ssl_pages_nonce' ], plugin_basename( __FILE__ ) ) )
		return $post_id;

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
		return $post_id;
	
	if ( !current_user_can( 'edit_post', $post_id ) )
		return $post_id;
 
	update_post_meta( $post_id , '_bdn_force_ssl', esc_attr( $_POST[ 'bdn_force_ssl' ] ) );
	
	return $post_id;
}


//Change the permalink to include SSL
function bdn_ssl_pages_permalink( $permalink, $post_id ) {
	

	//Check to see if we need SSL on this page
	$ssl = get_post_meta( $post_id, '_bdn_force_ssl', TRUE );
	
	//If so, replace http with https
	if( !empty( $ssl ) )
		$permalink = str_replace( 'http://', 'https://', $permalink );
	
	return $permalink;
	

}


//Redirect if you try to visit a non-SSL page
function bdn_redirect_ssl_pages( ) {

	global $post;
	
	//If we're already using HTTPS don't execute
	if( !empty( $_SERVER[ 'HTTPS' ] ) )
		return false;
		
	//If we're not dealing with a post object don't execute
	if( empty( $post ) )
		return false;
		
	//If we're not dealing with a page don't execute
	if( 'page' != $post->post_type )
		return false;
	
	//Check to see if SSL is required
	$ssl = get_post_meta( $post->ID, '_bdn_force_ssl', TRUE );
	
	//Redirect to the permalink, which we've rewritten to use SSL
	if( !empty( $ssl ) ) {
		wp_redirect( get_permalink( $post->ID ), 301 );
		exit();
	}
	
	return false;
}

