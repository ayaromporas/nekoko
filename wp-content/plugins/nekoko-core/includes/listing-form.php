<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function nekoko_core_listing_form_submit() {
	if ( empty( $_POST['nekoko_listing_form'] ) ) { return; }
	if ( ! is_user_logged_in() || ! nekoko_core_is_verified_user() || ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'nekoko_listing_form' ) ) { wp_die( 'Nevažeći zahtev.', 403 ); }
	$post_id = absint( $_POST['oglas_id'] ?? 0 );
	if ( $post_id && ! nekoko_core_current_user_can_manage_listing( $post_id ) ) { wp_die( 'Nemate dozvolu za ovaj oglas.', 403 ); }
	$redirect_url = $post_id ? add_query_arg( 'oglas_id', $post_id, nekoko_core_page_url( 'postavite-oglas' ) ) : nekoko_core_page_url( 'postavite-oglas' );
	$upload_check = nekoko_core_validate_image_uploads( 'listing_images' );
	if ( is_wp_error( $upload_check ) ) { set_transient( 'nekoko_listing_error_' . get_current_user_id(), $upload_check->get_error_message(), MINUTE_IN_SECONDS ); wp_safe_redirect( $redirect_url ); exit; }
	$title = sanitize_text_field( wp_unslash( $_POST['post_title'] ?? '' ) ); $content = wp_kses_post( wp_unslash( $_POST['post_content'] ?? '' ) ); $branch = sanitize_key( $_POST['branch'] ?? '' ); $tags = sanitize_text_field( wp_unslash( $_POST['tags'] ?? '' ) );
	$category_ids = isset( $_POST['listing_categories'] ) ? wp_unslash( $_POST['listing_categories'] ) : array( $_POST['listing_category'] ?? 0 );
	$subcategory_ids = array_values( array_filter( (array) wp_unslash( $_POST['categories'] ?? array() ), static function( $id ) { return '' !== $id; } ) );
	$classified = nekoko_core_classify_form_categories( $branch, $category_ids, $subcategory_ids );
	$duration = sanitize_text_field( wp_unslash( $_POST['nekoko_listing_duration'] ?? '' ) ); $duration_na = ! empty( $_POST['nekoko_listing_duration_na'] );
	if ( ! $title || ! $content || is_wp_error( $classified ) || ( 'potraznja' === $branch && ! $duration && ! $duration_na ) ) { set_transient( 'nekoko_listing_error_' . get_current_user_id(), 'Izaberite tip oglasa, najmanje jednu kategoriju i podkategorije iz izabranih kategorija. Sve moraju pripadati istom tipu oglasa. Za Potražnju unesite trajanje ili označite da nije primenljivo.', MINUTE_IN_SECONDS ); wp_safe_redirect( $redirect_url ); exit; }
	$excerpt = sanitize_textarea_field( wp_unslash( $_POST['post_excerpt'] ?? '' ) );
	// Every member-facing edit is a new review, regardless of automatic approval.
	$post_status = ! $post_id && ( nekoko_core_listing_auto_approval_enabled() || current_user_can( 'manage_options' ) ) ? 'publish' : 'pending';
	$data = array( 'ID' => $post_id, 'post_type' => 'oglas', 'post_title' => $title, 'post_excerpt' => $excerpt, 'post_content' => $content, 'post_author' => $post_id ? (int) get_post_field( 'post_author', $post_id ) : get_current_user_id(), 'post_status' => $post_status, 'tax_input' => array( 'kategorija_oglasa' => $classified['ids'] ) );
	$saved = wp_insert_post( wp_slash( $data ), true ); if ( is_wp_error( $saved ) ) { set_transient( 'nekoko_listing_error_' . get_current_user_id(), 'Oglas nije moguće sačuvati.', MINUTE_IN_SECONDS ); wp_safe_redirect( $redirect_url ); exit; }
	wp_set_object_terms( $saved, $classified['ids'], 'kategorija_oglasa', false ); wp_set_object_terms( $saved, array_filter( array_map( 'trim', explode( ',', $tags ) ) ), 'oglas_tag', false );
	if ( isset( $_POST['nekoko_listing_location'] ) ) { $value = sanitize_text_field( wp_unslash( $_POST['nekoko_listing_location'] ) ); function_exists( 'update_field' ) ? update_field( 'nekoko_listing_location', $value, $saved ) : update_post_meta( $saved, 'nekoko_listing_location', $value ); }
	update_post_meta( $saved, 'nekoko_listing_currency', 'RSD' );
	if ( 'potraznja' === $branch ) { function_exists( 'update_field' ) ? update_field( 'nekoko_listing_duration', $duration_na ? '' : $duration, $saved ) : update_post_meta( $saved, 'nekoko_listing_duration', $duration_na ? '' : $duration, $saved ); function_exists( 'update_field' ) ? update_field( 'nekoko_listing_duration_na', $duration_na ? '1' : '0', $saved ) : update_post_meta( $saved, 'nekoko_listing_duration_na', $duration_na ? '1' : '0', $saved ); delete_post_meta( $saved, 'nekoko_listing_price' ); delete_post_meta( $saved, 'nekoko_listing_price_agreed' ); } else { $price = isset( $_POST['nekoko_listing_price'] ) ? sanitize_text_field( wp_unslash( $_POST['nekoko_listing_price'] ) ) : ''; $price_agreed = ! empty( $_POST['nekoko_listing_price_agreed'] ) || '' === $price ? '1' : '0'; if ( '1' === $price_agreed ) { delete_post_meta( $saved, 'nekoko_listing_price' ); } else { function_exists( 'update_field' ) ? update_field( 'nekoko_listing_price', $price, $saved ) : update_post_meta( $saved, 'nekoko_listing_price', $price ); } function_exists( 'update_field' ) ? update_field( 'nekoko_listing_price_agreed', $price_agreed, $saved ) : update_post_meta( $saved, 'nekoko_listing_price_agreed', $price_agreed ); delete_post_meta( $saved, 'nekoko_listing_duration' ); delete_post_meta( $saved, 'nekoko_listing_duration_na' ); }
	$existing_images = $post_id ? nekoko_core_listing_image_ids( $post_id ) : array();
	$remove_images = array_values( array_intersect( $existing_images, array_filter( array_map( 'absint', (array) ( $_POST['remove_images'] ?? array() ) ) ) ) );
	$current_featured = (int) get_post_thumbnail_id( $saved );
	$current_gallery = array_values( array_diff( nekoko_core_listing_gallery_ids( $saved ), array( $current_featured ), $remove_images ) );
	foreach ( $remove_images as $remove_image ) { wp_update_post( array( 'ID' => $remove_image, 'post_parent' => 0 ) ); }
	if ( in_array( $current_featured, $remove_images, true ) ) { delete_post_thumbnail( $saved ); $current_featured = 0; }
	if ( ! $current_featured && $current_gallery ) { $current_featured = array_shift( $current_gallery ); set_post_thumbnail( $saved, $current_featured ); }
	$max_images = max( 1, (int) nekoko_core_option( 'nekoko_max_images', 6 ) );
	$new_image_count = isset( $_FILES['listing_images']['name'] ) ? count( array_filter( (array) $_FILES['listing_images']['name'] ) ) : 0;
	if ( count( array_unique( array_merge( $current_featured ? array( $current_featured ) : array(), $current_gallery ) ) ) + $new_image_count > $max_images ) { set_transient( 'nekoko_listing_error_' . get_current_user_id(), sprintf( 'Možete imati najviše %d slika po oglasu.', $max_images ), MINUTE_IN_SECONDS ); wp_safe_redirect( $redirect_url ); exit; }
	$new_images = nekoko_core_handle_user_images( 'listing_images', get_current_user_id(), $saved );
	if ( is_wp_error( $new_images ) ) { set_transient( 'nekoko_listing_error_' . get_current_user_id(), $new_images->get_error_message(), MINUTE_IN_SECONDS ); wp_safe_redirect( $redirect_url ); exit; }
	foreach ( $new_images as $image ) { if ( ! $current_featured ) { $current_featured = $image; set_post_thumbnail( $saved, $image ); } else { $current_gallery[] = $image; } }
	$current_gallery = array_values( array_unique( array_diff( $current_gallery, array( $current_featured ) ) ) );
	// Persist the gallery before confirming completion to the author.
	function_exists( 'update_field' ) ? update_field( 'nekoko_listing_gallery', $current_gallery, $saved ) : update_post_meta( $saved, 'nekoko_listing_gallery', $current_gallery );
	nekoko_core_send_listing_email( $saved, 'publish' === $post_status ? 'published' : ( $post_id ? 'updated' : 'submitted' ) );
	set_transient( 'nekoko_listing_success_' . get_current_user_id(), ( 'pending' === $post_status ? 'Oglas je uspešno poslat na pregled i trenutno čeka odobrenje administratora/moderatora. Do odobrenja nije javno vidljiv. Kada bude odobren i javno vidljiv, poslaćemo vam potvrdu na email adresu vašeg naloga.' : nekoko_core_option( 'nekoko_listing_success', 'Oglas je uspešno sačuvan.' ) ), MINUTE_IN_SECONDS ); wp_safe_redirect( add_query_arg( 'oglas_postavljen', '1', nekoko_core_page_url( 'moji-oglasi' ) ) ); exit;
}
add_action( 'template_redirect', 'nekoko_core_listing_form_submit', 6 );

function nekoko_core_shortcode_listing_form() { return nekoko_core_template( 'listing-form' ); }
add_shortcode( 'nekoko_listing_form', 'nekoko_core_shortcode_listing_form' );
