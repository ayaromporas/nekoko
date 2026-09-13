<?php
/** Site-wide settings navigation. Field definitions belong in this plugin, not the theme. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Add future global settings here as children, with the same access and storage. */
function nekoko_core_add_settings_subpage( $slug, $menu_title, $page_title ) {
	if ( ! function_exists( 'acf_add_options_sub_page' ) ) { return false; }

	return acf_add_options_sub_page( array(
		'page_title'      => $page_title,
		'menu_title'      => $menu_title,
		'menu_slug'       => $slug,
		'parent_slug'     => 'nekoko-settings',
		'capability'      => 'manage_options',
		'post_id'         => 'options',
		'redirect'        => false,
		'update_button'   => 'Sačuvaj podešavanja',
		'updated_message' => 'Podešavanja su sačuvana.',
	) );
}

function nekoko_core_register_settings_pages() {
	if ( ! function_exists( 'acf_add_options_page' ) || ! function_exists( 'acf_add_options_sub_page' ) ) { return; }

	acf_add_options_page( array(
		'page_title'      => 'NekoKO podešavanja',
		'menu_title'      => 'NekoKO',
		'menu_slug'       => 'nekoko-settings',
		'capability'      => 'manage_options',
		'icon_url'        => 'dashicons-admin-generic',
		'position'        => 61,
		// ACF's redirect mode changes the parent menu slug to the first child's slug.
		// Keep the requested parent slug stable for all present and future sections.
		'redirect'        => false,
		'post_id'         => 'options',
		'update_button'   => 'Sačuvaj podešavanja',
		'updated_message' => 'Podešavanja su sačuvana.',
	) );

	nekoko_core_add_settings_subpage( 'nekoko-settings-general', 'Opšte', 'Opšta podešavanja' );
	nekoko_core_add_settings_subpage( 'nekoko-settings-header', 'Header', 'Header podešavanja' );
	nekoko_core_add_settings_subpage( 'nekoko-settings-footer', 'Footer', 'Footer podešavanja' );
}
add_action( 'acf/init', 'nekoko_core_register_settings_pages' );

/** Remove WordPress's duplicate parent submenu. */
function nekoko_core_remove_settings_parent_submenu() {
	if ( ! function_exists( 'acf_add_options_page' ) ) { return; }
	remove_submenu_page( 'nekoko-settings', 'nekoko-settings' );
}
// ACF registers its admin menu at priority 99.
add_action( 'admin_menu', 'nekoko_core_remove_settings_parent_submenu', 100 );

/** Give site editors an unambiguous route to the editable Contact Form 7 list. */
function nekoko_core_add_contact_form_menu_link() {
	if ( ! function_exists( 'wpcf7_contact_form' ) ) { return; }
	add_submenu_page( 'nekoko-settings', 'Kontakt forma', 'Kontakt forma', 'manage_options', 'nekoko-contact-form', 'nekoko_core_redirect_to_contact_form' );
}
add_action( 'admin_menu', 'nekoko_core_add_contact_form_menu_link', 101 );

function nekoko_core_redirect_to_contact_form() {
	if ( ! current_user_can( 'wpcf7_edit_contact_forms' ) ) { wp_die( 'Nemate dozvolu za uređivanje kontakt forme.', '', array( 'response' => 403 ) ); }
	wp_safe_redirect( admin_url( 'admin.php?page=wpcf7' ) );
	exit;
}
