<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function nekoko_core_listing_form_submit() {
	if ( empty( $_POST['nekoko_listing_form'] ) ) { return; }
	if ( ! is_user_logged_in() || ! nekoko_core_is_verified_user() || ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'nekoko_listing_form' ) ) { wp_die( 'Nevažeći zahtev.', 403 ); }
	$post_id = absint( $_POST['oglas_id'] ?? 0 );
	if ( $post_id && ! nekoko_core_current_user_can_manage_listing( $post_id ) ) { wp_die( 'Nemate dozvolu za ovaj oglas.', 403 ); }
	$redirect_url = $post_id ? add_query_arg( 'oglas_id', $post_id, nekoko_core_page_url( 'postavite-oglas' ) ) : nekoko_core_page_url( 'postavite-oglas' );
	$title = sanitize_text_field( wp_unslash( $_POST['post_title'] ?? '' ) ); $content = wp_kses_post( wp_unslash( $_POST['post_content'] ?? '' ) ); $branch = sanitize_key( $_POST['branch'] ?? '' ); $category_id = absint( $_POST['listing_category'] ?? 0 ); $subcategory_values = (array) ( $_POST['categories'] ?? array() ); $subcategory_id = absint( $subcategory_values[0] ?? 0 ); $tags = sanitize_text_field( wp_unslash( $_POST['tags'] ?? '' ) );
	$roots = nekoko_core_category_roots(); $root = $roots[ $branch ] ?? null; $category = $category_id ? get_term( $category_id, 'kategorija_oglasa' ) : null; $subcategory = $subcategory_id ? get_term( $subcategory_id, 'kategorija_oglasa' ) : null;
	$selected_category = $subcategory_id ?: $category_id; $classified = nekoko_core_classify_categories( $selected_category ? array( $selected_category ) : array() );
	$valid_category = $root && $category && ! is_wp_error( $category ) && (int) $category->parent === (int) $root->term_id;
	$valid_subcategory = ! $subcategory_id || ( $subcategory && ! is_wp_error( $subcategory ) && (int) $subcategory->parent === $category_id );
	if ( ! $title || ! $content || ! $valid_category || ! $valid_subcategory || is_wp_error( $classified ) || $classified['root']->slug !== $branch ) { set_transient( 'nekoko_listing_error_' . get_current_user_id(), 'Izaberite tip oglasa i kategoriju iz tog tipa.', MINUTE_IN_SECONDS ); wp_safe_redirect( $redirect_url ); exit; }
	$data = array( 'ID' => $post_id, 'post_type' => 'oglas', 'post_title' => $title, 'post_content' => $content, 'post_author' => get_current_user_id(), 'post_status' => $post_id ? get_post_status( $post_id ) : ( 'publish' === nekoko_core_option( 'nekoko_listing_status', 'pending' ) ? 'publish' : 'pending' ), 'tax_input' => array( 'kategorija_oglasa' => $classified['ids'] ) );
	$saved = wp_insert_post( wp_slash( $data ), true ); if ( is_wp_error( $saved ) ) { set_transient( 'nekoko_listing_error_' . get_current_user_id(), 'Oglas nije moguće sačuvati.', MINUTE_IN_SECONDS ); wp_safe_redirect( $redirect_url ); exit; }
	wp_set_object_terms( $saved, $classified['ids'], 'kategorija_oglasa', false ); wp_set_object_terms( $saved, array_filter( array_map( 'trim', explode( ',', $tags ) ) ), 'oglas_tag', false );
	foreach ( array( 'nekoko_listing_location', 'nekoko_listing_price', 'nekoko_listing_currency' ) as $field ) { if ( isset( $_POST[ $field ] ) ) { $value = sanitize_text_field( wp_unslash( $_POST[ $field ] ) ); function_exists( 'update_field' ) ? update_field( $field, $value, $saved ) : update_post_meta( $saved, $field, $value ); } }
	$price_agreed = ! empty( $_POST['nekoko_listing_price_agreed'] ) ? '1' : '0'; function_exists( 'update_field' ) ? update_field( 'nekoko_listing_price_agreed', $price_agreed, $saved ) : update_post_meta( $saved, 'nekoko_listing_price_agreed', $price_agreed );
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
	function_exists( 'update_field' ) ? update_field( 'nekoko_listing_gallery', $current_gallery, $saved ) : update_post_meta( $saved, 'nekoko_listing_gallery', $current_gallery );
	set_transient( 'nekoko_listing_success_' . get_current_user_id(), nekoko_core_option( 'nekoko_listing_success', 'Oglas je uspešno sačuvan.' ), MINUTE_IN_SECONDS ); wp_safe_redirect( add_query_arg( 'oglas_postavljen', '1', nekoko_core_page_url( 'moji-oglasi' ) ) ); exit;
}
add_action( 'template_redirect', 'nekoko_core_listing_form_submit', 6 );

function nekoko_core_shortcode_listing_form() { return nekoko_core_template( 'listing-form' ); }
add_shortcode( 'nekoko_listing_form', 'nekoko_core_shortcode_listing_form' );
