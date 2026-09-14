<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function nekoko_core_can_edit_term_meta( $allowed, $key, $term_id ) {
	return current_user_can( 'edit_term', (int) $term_id );
}
function nekoko_core_register_category_meta() {
	foreach ( array( 'nekoko_icon_id' => 'ID ikonice kategorije.', 'nekoko_hero_id' => 'ID hero slike kategorije.' ) as $key => $description ) {
		register_term_meta( 'kategorija_oglasa', $key, array(
			'type' => 'integer', 'single' => true, 'default' => 0, 'description' => $description,
			'sanitize_callback' => 'absint', 'auth_callback' => 'nekoko_core_can_edit_term_meta',
			'show_in_rest' => array( 'schema' => array( 'type' => 'integer', 'minimum' => 0, 'default' => 0 ) ),
		) );
	}
	register_term_meta( 'kategorija_oglasa', 'nekoko_outline_icon', array(
		'type' => 'string', 'single' => true, 'default' => '', 'description' => 'Outline SVG ikonica kategorije.',
		'sanitize_callback' => 'sanitize_key', 'auth_callback' => 'nekoko_core_can_edit_term_meta',
		'show_in_rest' => array( 'schema' => array( 'type' => 'string', 'default' => '' ) ),
	) );
}
add_action( 'init', 'nekoko_core_register_category_meta', 20 );

function nekoko_core_category_media_control( $key, $label, $id ) {
	echo '<div class="nekoko-media-field">';
	printf( '<input type="hidden" id="%1$s" name="%1$s" value="%2$d">', esc_attr( $key ), (int) $id );
	echo '<div class="nekoko-media-preview">';
	if ( $id ) { echo wp_get_attachment_image( $id, 'thumbnail' ); }
	echo '</div>';
	printf( '<button type="button" class="button nekoko-media-select" aria-label="%1$s">Izaberi ili otpremi sliku</button> ', esc_attr( $label ) );
	echo '<button type="button" class="button-link nekoko-media-remove">Ukloni sliku</button>';
	echo '<p class="description">Koristi sliku iz Biblioteke medija. Polje je dostupno i podkategorijama.</p></div>';
}
function nekoko_core_category_add_fields() {
	wp_nonce_field( 'nekoko_category_media', 'nekoko_category_media_nonce' );
	foreach ( array( 'nekoko_icon_id' => 'Ikonica kategorije', 'nekoko_hero_id' => 'Hero slika arhive' ) as $key => $label ) {
		printf( '<div class="form-field"><label for="%1$s">%2$s</label>', esc_attr( $key ), esc_html( $label ) );
		nekoko_core_category_media_control( $key, $label, 0 );
		echo '</div>';
	}
	if ( function_exists( 'nekoko_core_outline_icon_choices' ) ) {
		echo '<div class="form-field"><label for="nekoko_outline_icon">Outline ikonica</label>';
		echo '<select id="nekoko_outline_icon" name="nekoko_outline_icon"><option value="">Automatski izbor</option>';
		foreach ( nekoko_core_outline_icon_choices() as $value => $label ) { printf( '<option value="%1$s">%2$s</option>', esc_attr( $value ), esc_html( $label ) ); }
		echo '</select><p class="description">Koristi se u blokovima Top kategorije.</p></div>';
	}
}
add_action( 'kategorija_oglasa_add_form_fields', 'nekoko_core_category_add_fields' );
function nekoko_core_category_edit_fields( $term ) {
	wp_nonce_field( 'nekoko_category_media', 'nekoko_category_media_nonce' );
	foreach ( array( 'nekoko_icon_id' => 'Ikonica kategorije', 'nekoko_hero_id' => 'Hero slika arhive' ) as $key => $label ) {
		printf( '<tr class="form-field"><th scope="row"><label for="%1$s">%2$s</label></th><td>', esc_attr( $key ), esc_html( $label ) );
		nekoko_core_category_media_control( $key, $label, (int) get_term_meta( $term->term_id, $key, true ) );
		echo '</td></tr>';
	}
	if ( function_exists( 'nekoko_core_outline_icon_choices' ) ) {
		$current = get_term_meta( $term->term_id, 'nekoko_outline_icon', true );
		echo '<tr class="form-field"><th scope="row"><label for="nekoko_outline_icon">Outline ikonica</label></th><td><select id="nekoko_outline_icon" name="nekoko_outline_icon"><option value="">Automatski izbor</option>';
		foreach ( nekoko_core_outline_icon_choices() as $value => $label ) { printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $value ), selected( $current, $value, false ), esc_html( $label ) ); }
		echo '</select><p class="description">Koristi se u blokovima Top kategorije.</p></td></tr>';
	}
}
add_action( 'kategorija_oglasa_edit_form_fields', 'nekoko_core_category_edit_fields' );
function nekoko_core_save_category_media( $term_id ) {
	if ( ! isset( $_POST['nekoko_category_media_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nekoko_category_media_nonce'] ) ), 'nekoko_category_media' ) || ! current_user_can( 'edit_term', $term_id ) ) { return; }
	foreach ( array( 'nekoko_icon_id', 'nekoko_hero_id' ) as $key ) {
		if ( ! isset( $_POST[ $key ] ) ) { continue; }
		$id = absint( $_POST[ $key ] );
		if ( $id && ! wp_attachment_is_image( $id ) ) { continue; }
		update_term_meta( $term_id, $key, $id );
	}
	if ( isset( $_POST['nekoko_outline_icon'] ) && function_exists( 'nekoko_core_outline_icon_choices' ) ) {
		$icon = sanitize_key( wp_unslash( $_POST['nekoko_outline_icon'] ) );
		if ( '' === $icon || array_key_exists( $icon, nekoko_core_outline_icon_choices() ) ) { update_term_meta( $term_id, 'nekoko_outline_icon', $icon ); }
	}
}
add_action( 'created_kategorija_oglasa', 'nekoko_core_save_category_media' );
add_action( 'edited_kategorija_oglasa', 'nekoko_core_save_category_media' );
function nekoko_core_validate_category_media_rest( $response, $handler, $request ) {
	// The core terms controller does not handle WP_Error from rest_pre_insert_*.
	// Reject invalid media before its create/update callback can mutate a term.
	if ( null !== $response || ! in_array( $request->get_method(), array( 'POST', 'PUT', 'PATCH' ), true ) || ! preg_match( '~^/wp/v2/kategorija_oglasa(?:/[0-9]+)?/?$~', $request->get_route() ) ) { return $response; }
	$meta = $request->get_param( 'meta' );
	foreach ( array( 'nekoko_icon_id', 'nekoko_hero_id' ) as $key ) {
		if ( isset( $meta[ $key ] ) && (int) $meta[ $key ] && ! wp_attachment_is_image( (int) $meta[ $key ] ) ) {
			return new WP_Error( 'nekoko_invalid_image', 'Ikonica i hero slika moraju biti slike iz Biblioteke medija.', array( 'status' => 400 ) );
		}
	}
	return $response;
}
add_filter( 'rest_request_before_callbacks', 'nekoko_core_validate_category_media_rest', 10, 3 );
function nekoko_core_enqueue_category_media() {
	$screen = get_current_screen();
	if ( ! $screen || 'kategorija_oglasa' !== $screen->taxonomy ) { return; }
	wp_enqueue_media();
	wp_enqueue_script( 'nekoko-category-media', plugins_url( 'assets/category-media.js', NEKOKO_CORE_FILE ), array( 'jquery', 'media-editor' ), filemtime( dirname( NEKOKO_CORE_FILE ) . '/assets/category-media.js' ), true );
}
add_action( 'admin_enqueue_scripts', 'nekoko_core_enqueue_category_media' );
