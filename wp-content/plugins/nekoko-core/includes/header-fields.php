<?php
/** Header field definitions are site-owned; the theme only reads their values. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function nekoko_core_register_header_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) || ! function_exists( 'acf_get_options_page' ) ) { return; }
	$page = acf_get_options_page( 'nekoko-settings-header' );
	if ( ! $page ) { return; }

	acf_add_local_field_group( array(
		'key' => 'group_nekoko_header_settings',
		'title' => 'Header podešavanja',
		'fields' => array(
			array( 'key' => 'field_nekoko_header_login_label', 'name' => 'header_login_label', 'label' => 'Naziv dugmeta za prijavu', 'type' => 'text', 'default_value' => 'Prijava' ),
			array( 'key' => 'field_nekoko_header_login_link', 'name' => 'header_login_link', 'label' => 'Link za prijavu', 'type' => 'link', 'return_format' => 'array', 'instructions' => 'Ako je prazno, koristi se WordPress prijava preko /wp-admin/.' ),
			array( 'key' => 'field_nekoko_header_logout_label', 'name' => 'header_logout_label', 'label' => 'Naziv dugmeta za odjavu', 'type' => 'text', 'default_value' => 'Odjava' ),
			array( 'key' => 'field_nekoko_header_logout_redirect', 'name' => 'header_logout_redirect', 'label' => 'Stranica nakon odjave', 'type' => 'page_link', 'post_type' => array( 'page' ), 'allow_null' => 1, 'multiple' => 0, 'instructions' => 'Određuje samo stranicu nakon bezbedne WordPress odjave. Ako je prazno, koristi se početna strana.' ),
			array( 'key' => 'field_nekoko_header_post_ad_label', 'name' => 'header_post_ad_label', 'label' => 'Naziv dugmeta za postavljanje oglasa', 'type' => 'text', 'default_value' => 'Postavite oglas' ),
			array( 'key' => 'field_nekoko_header_post_ad_link', 'name' => 'header_post_ad_link', 'label' => 'Link za postavljanje oglasa', 'type' => 'link', 'return_format' => 'array', 'instructions' => 'Ako je prazno, koristi se /postavite-oglas/. Tekst dugmeta određuje prethodno polje.' ),
		),
		'location' => array( array( array( 'param' => 'options_page', 'operator' => '==', 'value' => $page['menu_slug'] ) ) ),
		'active' => true,
		'show_in_rest' => 0,
	) );
}
add_action( 'acf/init', 'nekoko_core_register_header_fields', 20 );
