<?php

/**
 * Uninstall pgfc.
 *
 * Remove:
 * - pgfc  meta
 *
 * @since 1.0.0
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}
delete_option('pipeDriveApiToken');

$pgfc_posts = get_posts(
	[
		'post_type'   => [ 'pgfc'],
		'post_status' => 'any',
		'numberposts' => - 1,
		'fields'      => 'ids',
	]
);


if ( $pgfc_posts ) {
	foreach ( $pgfc_posts as $pgfc_post ) {
		wp_delete_post( $pgfc_post, true );
	}
}
// Delete extra fields
$users = get_users();    
if($users){
	foreach ($users as $user) {
		// delete use meta here
	}
}