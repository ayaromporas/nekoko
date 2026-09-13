<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function nekoko_core_add_frontend_settings_pages() {
	nekoko_core_add_settings_subpage( 'nekoko-settings-registration', 'Registracija i email', 'Registracija i email' );
	nekoko_core_add_settings_subpage( 'nekoko-settings-listings', 'Oglasi', 'Oglasi' );
}
add_action( 'acf/init', 'nekoko_core_add_frontend_settings_pages', 11 );

function nekoko_core_acf_json_paths( $paths ) { $paths[] = dirname( __DIR__ ) . '/acf-json'; return $paths; }
add_filter( 'acf/settings/load_json', 'nekoko_core_acf_json_paths' );
function nekoko_core_acf_json_save_path( $path ) { return dirname( __DIR__ ) . '/acf-json'; }
add_filter( 'acf/settings/save_json', 'nekoko_core_acf_json_save_path' );

function nekoko_core_register_frontend_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) || ! function_exists( 'acf_get_options_page' ) ) { return; }
	$registration = acf_get_options_page( 'nekoko-settings-registration' );
	$listings = acf_get_options_page( 'nekoko-settings-listings' );
	if ( $registration ) { acf_add_local_field_group( array( 'key' => 'group_nekoko_registration_options', 'title' => 'Registracija i email', 'active' => true, 'location' => array( array( array( 'param' => 'options_page', 'operator' => '==', 'value' => $registration['menu_slug'] ) ) ), 'fields' => array(
		array( 'key' => 'field_nekoko_verification_subject', 'label' => 'Naslov verifikacionog emaila', 'name' => 'nekoko_verification_subject', 'type' => 'text', 'default_value' => 'Potvrdite svoju NekoKO email adresu' ),
		array( 'key' => 'field_nekoko_verification_intro', 'label' => 'Uvodni tekst emaila', 'name' => 'nekoko_verification_intro', 'type' => 'textarea', 'default_value' => 'Hvala na registraciji. Potvrdite email adresu da biste koristili NekoKO.' ),
		array( 'key' => 'field_nekoko_verification_button', 'label' => 'Tekst dugmeta', 'name' => 'nekoko_verification_button', 'type' => 'text', 'default_value' => 'Potvrdite email adresu' ),
		array( 'key' => 'field_nekoko_registration_success', 'label' => 'Tekst uspešne registracije', 'name' => 'nekoko_registration_success', 'type' => 'textarea', 'default_value' => 'Registracija je uspešna. Poslali smo vam email sa linkom za potvrdu naloga. Proverite i Spam/Neželjenu poštu.' ),
		array( 'key' => 'field_nekoko_verification_success', 'label' => 'Tekst uspešne potvrde', 'name' => 'nekoko_verification_success', 'type' => 'text', 'default_value' => 'Email je uspešno potvrđen. Sada možete da se prijavite.' ),
		array( 'key' => 'field_nekoko_verification_hours', 'label' => 'Broj sati važenja linka', 'name' => 'nekoko_verification_hours', 'type' => 'number', 'default_value' => 48, 'min' => 1, 'max' => 168 ),
		array( 'key' => 'field_nekoko_login_page', 'label' => 'Stranica za prijavu', 'name' => 'nekoko_login_page', 'type' => 'page_link', 'post_type' => array( 'page' ), 'allow_null' => 1 ),
		array( 'key' => 'field_nekoko_registration_page', 'label' => 'Stranica za registraciju', 'name' => 'nekoko_registration_page', 'type' => 'page_link', 'post_type' => array( 'page' ), 'allow_null' => 1 ),
		array( 'key' => 'field_nekoko_verify_page', 'label' => 'Stranica za potvrdu emaila', 'name' => 'nekoko_verify_page', 'type' => 'page_link', 'post_type' => array( 'page' ), 'allow_null' => 1 ),
		array( 'key' => 'field_nekoko_dashboard_page', 'label' => 'Stranica Moji oglasi', 'name' => 'nekoko_dashboard_page', 'type' => 'page_link', 'post_type' => array( 'page' ), 'allow_null' => 1 ),
		array( 'key' => 'field_nekoko_profile_page', 'label' => 'Stranica Moj profil', 'name' => 'nekoko_profile_page', 'type' => 'page_link', 'post_type' => array( 'page' ), 'allow_null' => 1 ),
	) ) ); }
	if ( $listings ) { acf_add_local_field_group( array( 'key' => 'group_nekoko_listing_options', 'title' => 'Oglasi', 'active' => true, 'location' => array( array( array( 'param' => 'options_page', 'operator' => '==', 'value' => $listings['menu_slug'] ) ) ), 'fields' => array(
		array( 'key' => 'field_nekoko_listing_status', 'label' => 'Status novog oglasa', 'name' => 'nekoko_listing_status', 'type' => 'select', 'choices' => array( 'pending' => 'Čeka odobrenje', 'publish' => 'Odmah objavljen' ), 'default_value' => 'pending', 'return_format' => 'value' ),
		array( 'key' => 'field_nekoko_listing_success', 'label' => 'Poruka nakon uspešnog slanja', 'name' => 'nekoko_listing_success', 'type' => 'text', 'default_value' => 'Oglas je uspešno sačuvan.' ),
		array( 'key' => 'field_nekoko_max_images', 'label' => 'Maksimalan broj slika', 'name' => 'nekoko_max_images', 'type' => 'number', 'default_value' => 6, 'min' => 1, 'max' => 12 ),
		array( 'key' => 'field_nekoko_max_image_size', 'label' => 'Maksimalna veličina slike (MB)', 'name' => 'nekoko_max_image_size', 'type' => 'number', 'default_value' => 5, 'min' => 1, 'max' => 20 ),
	) ) ); }
	acf_add_local_field_group( array( 'key' => 'group_nekoko_listing_fields', 'title' => 'Oglas – detalji', 'active' => true, 'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'oglas' ) ) ), 'fields' => array(
		array( 'key' => 'field_nekoko_listing_location', 'label' => 'Lokacija', 'name' => 'nekoko_listing_location', 'type' => 'text', 'required' => 1 ),
		array( 'key' => 'field_nekoko_listing_price_agreed', 'label' => 'Cena po dogovoru', 'name' => 'nekoko_listing_price_agreed', 'type' => 'true_false', 'ui' => 1 ),
		array( 'key' => 'field_nekoko_listing_price', 'label' => 'Cena', 'name' => 'nekoko_listing_price', 'type' => 'number', 'conditional_logic' => array( array( array( 'field' => 'field_nekoko_listing_price_agreed', 'operator' => '!=', 'value' => '1' ) ) ) ),
		array( 'key' => 'field_nekoko_listing_currency', 'label' => 'Valuta', 'name' => 'nekoko_listing_currency', 'type' => 'select', 'choices' => array( 'RSD' => 'RSD', 'EUR' => 'EUR' ), 'default_value' => 'RSD', 'return_format' => 'value' ),
		array( 'key' => 'field_nekoko_listing_gallery', 'label' => 'Galerija', 'name' => 'nekoko_listing_gallery', 'type' => 'gallery', 'return_format' => 'id', 'library' => 'uploadedTo', 'max' => 6, 'mime_types' => 'jpg,jpeg,png,webp' ),
	) ) );
	acf_add_local_field_group( array( 'key' => 'group_nekoko_user_profile', 'title' => 'Korisnik – profil', 'active' => true, 'location' => array( array( array( 'param' => 'user_role', 'operator' => '==', 'value' => 'oglasivac' ) ) ), 'fields' => array(
		array( 'key' => 'field_nekoko_phone', 'label' => 'Telefon', 'name' => 'nekoko_phone', 'type' => 'text' ), array( 'key' => 'field_nekoko_city', 'label' => 'Grad/opština', 'name' => 'nekoko_city', 'type' => 'text' ), array( 'key' => 'field_nekoko_profile_bio', 'label' => 'Kratak opis', 'name' => 'nekoko_profile_bio', 'type' => 'textarea', 'maxlength' => 500 ), array( 'key' => 'field_nekoko_profile_image', 'label' => 'Profilna fotografija', 'name' => 'nekoko_profile_image', 'type' => 'image', 'return_format' => 'id', 'library' => 'uploadedTo', 'mime_types' => 'jpg,jpeg,png,webp' ), array( 'key' => 'field_nekoko_show_phone', 'label' => 'Dozvoljavam da moj telefon bude javno prikazan', 'name' => 'nekoko_show_phone', 'type' => 'true_false', 'ui' => 1 ),
	) ) );
}
add_action( 'acf/init', 'nekoko_core_register_frontend_fields', 30 );
