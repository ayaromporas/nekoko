<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function nekoko_core_option( $name, $default = null ) {
	$value = function_exists( 'get_field' ) ? get_field( $name, 'option' ) : null;
	return null === $value || '' === $value ? $default : $value;
}

function nekoko_core_page_url( $slug, $default = '' ) {
	$page = get_page_by_path( $slug, OBJECT, 'page' );
	return $page ? get_permalink( $page ) : ( $default ? $default : home_url( '/' . trim( $slug, '/' ) . '/' ) );
}

function nekoko_core_safe_redirect_url( $url, $fallback = '' ) {
	$fallback = $fallback ? $fallback : nekoko_core_page_url( 'moji-oglasi' );
	return wp_validate_redirect( esc_url_raw( (string) $url ), $fallback );
}

function nekoko_core_form_notice( $message, $type = 'success' ) {
	return '<div class="nekoko-form__notice nekoko-form__notice--' . esc_attr( $type ) . '" role="' . ( 'error' === $type ? 'alert' : 'status' ) . '">' . esc_html( $message ) . '</div>';
}

function nekoko_core_form_errors( WP_Error $errors ) {
	$output = '';
	foreach ( $errors->get_error_messages() as $message ) { $output .= nekoko_core_form_notice( $message, 'error' ); }
	return $output;
}

function nekoko_core_password_is_strong( $password ) {
	return is_string( $password ) && strlen( $password ) >= 10 && preg_match( '/[A-Za-z]/', $password ) && preg_match( '/\d/', $password );
}

function nekoko_core_unique_login_from_email( $email ) {
	$base = sanitize_user( strtok( strtolower( $email ), '@' ), true );
	$base = $base ? $base : 'oglasivac';
	$login = $base; $number = 2;
	while ( username_exists( $login ) ) { $login = $base . '-' . $number++; }
	return $login;
}

function nekoko_core_is_trusted_user( $user_id ) {
	$user = get_userdata( $user_id );
	return $user && ( user_can( $user, 'manage_options' ) || ! in_array( 'oglasivac', (array) $user->roles, true ) );
}

function nekoko_core_is_verified_user( $user_id = 0 ) {
	$user_id = $user_id ?: get_current_user_id();
	return $user_id && ( nekoko_core_is_trusted_user( $user_id ) || '1' === get_user_meta( $user_id, 'nekoko_email_verified', true ) );
}

function nekoko_core_require_login( $redirect_to = '' ) {
	if ( is_user_logged_in() ) { return true; }
	$target = nekoko_core_safe_redirect_url( $redirect_to ?: home_url( add_query_arg( array(), $GLOBALS['wp']->request ?? '' ) ) );
	wp_safe_redirect( add_query_arg( 'redirect_to', rawurlencode( $target ), nekoko_core_page_url( 'prijava' ) ) );
	exit;
}

function nekoko_core_register_oglasivac_role() {
	if ( get_role( 'oglasivac' ) ) { return; }
	add_role( 'oglasivac', 'Oglašivač', array( 'read' => true, 'upload_files' => true ) );
}

function nekoko_core_current_user_can_manage_listing( $post_id ) {
	$post = get_post( $post_id );
	return $post && 'oglas' === $post->post_type && ( current_user_can( 'manage_options' ) || ( get_current_user_id() && (int) $post->post_author === get_current_user_id() ) );
}

function nekoko_core_handle_user_image_file( $file, $user_id, $post_id = 0 ) {
	if ( empty( $file['name'] ) || UPLOAD_ERR_NO_FILE === (int) ( $file['error'] ?? UPLOAD_ERR_NO_FILE ) ) { return 0; }
	if ( UPLOAD_ERR_OK !== (int) $file['error'] ) { return new WP_Error( 'upload_error', 'Slika nije uspešno otpremljena.' ); }
	$max = max( 1, (int) nekoko_core_option( 'nekoko_max_image_size', 5 ) ) * MB_IN_BYTES;
	if ( (int) $file['size'] > $max ) { return new WP_Error( 'image_size', 'Slika je veća od dozvoljene veličine.' ); }
	$info = wp_check_filetype_and_ext( $file['tmp_name'], $file['name'] );
	if ( ! in_array( $info['type'] ?? '', array( 'image/jpeg', 'image/png', 'image/webp' ), true ) ) { return new WP_Error( 'image_type', 'Dozvoljene su samo JPG, PNG i WebP slike.' ); }
	require_once ABSPATH . 'wp-admin/includes/file.php'; require_once ABSPATH . 'wp-admin/includes/media.php'; require_once ABSPATH . 'wp-admin/includes/image.php';
	$id = media_handle_sideload( $file, $post_id, '', array( 'test_form' => false ) );
	if ( is_wp_error( $id ) ) { return $id; }
	wp_update_post( array( 'ID' => $id, 'post_author' => $user_id ) ); return (int) $id;
}

function nekoko_core_handle_user_image( $field, $user_id, $post_id = 0 ) {
	return isset( $_FILES[ $field ] ) ? nekoko_core_handle_user_image_file( $_FILES[ $field ], $user_id, $post_id ) : 0;
}

function nekoko_core_handle_user_images( $field, $user_id, $post_id = 0 ) {
	if ( empty( $_FILES[ $field ]['name'] ) ) { return array(); }
	$files = $_FILES[ $field ];
	$names = is_array( $files['name'] ) ? $files['name'] : array( $files['name'] );
	$images = array();
	foreach ( array_keys( $names ) as $index ) {
		$file = array();
		foreach ( array( 'name', 'type', 'tmp_name', 'error', 'size' ) as $key ) { $file[ $key ] = is_array( $files[ $key ] ) ? ( $files[ $key ][ $index ] ?? '' ) : $files[ $key ]; }
		$image = nekoko_core_handle_user_image_file( $file, $user_id, $post_id );
		if ( is_wp_error( $image ) ) { return $image; }
		if ( $image ) { $images[] = $image; }
	}
	return $images;
}

function nekoko_core_listing_gallery_ids( $post_id ) {
	$gallery = function_exists( 'get_field' ) ? get_field( 'nekoko_listing_gallery', $post_id ) : get_post_meta( $post_id, 'nekoko_listing_gallery', true );
	$gallery = is_array( $gallery ) ? $gallery : array();
	$ids = array_map( 'absint', $gallery );
	$attachments = get_children( array( 'post_parent' => $post_id, 'post_type' => 'attachment', 'post_mime_type' => 'image', 'fields' => 'ids', 'orderby' => 'menu_order ID', 'order' => 'ASC' ) );
	return array_values( array_unique( array_filter( array_merge( $ids, array_map( 'absint', $attachments ) ) ) ) );
}

function nekoko_core_listing_image_ids( $post_id ) {
	$featured = get_post_thumbnail_id( $post_id );
	$ids = nekoko_core_listing_gallery_ids( $post_id );
	if ( $featured ) { array_unshift( $ids, (int) $featured ); }
	return array_values( array_unique( array_filter( $ids ) ) );
}

/** Returns the editor-provided short description, with a safe legacy fallback. */
function nekoko_core_listing_excerpt( $post_id, $words = 22 ) {
	$excerpt = trim( (string) get_post_field( 'post_excerpt', $post_id ) );
	if ( '' !== $excerpt ) { return $excerpt; }
	$content = wp_strip_all_tags( (string) get_post_field( 'post_content', $post_id ) );
	return wp_trim_words( $content, $words, '…' );
}

function nekoko_core_mail_failure_log( $error ) {
	if ( $error instanceof WP_Error ) { error_log( 'NekoKO mail failure: ' . sanitize_text_field( $error->get_error_message() ) ); }
}
add_action( 'wp_mail_failed', 'nekoko_core_mail_failure_log' );
