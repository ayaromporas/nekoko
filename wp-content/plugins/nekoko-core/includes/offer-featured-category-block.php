<?php
/** Reusable compact featured Offer category callout. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function nekoko_core_register_offer_featured_category_block() {
	if ( ! function_exists( 'acf_register_block_type' ) ) { return; }
	acf_register_block_type( array(
		'name' => 'nekoko-offer-featured-category', 'title' => 'Ponuda – istaknuta kategorija',
		'description' => 'Kompaktna istaknuta kategorija sa ikoncom, oznakama i linkom.',
		'category' => 'design', 'icon' => 'heart', 'keywords' => array( 'ponuda', 'kategorija', 'istaknuto', 'NekoKO' ),
		'render_template' => dirname( __DIR__ ) . '/blocks/offer-featured-category/render.php', 'mode' => 'edit',
		'supports' => array( 'align' => false, 'anchor' => false, 'jsx' => true ), 'enqueue_assets' => 'nekoko_core_enqueue_offer_featured_category_assets',
	) );
}
add_action( 'acf/init', 'nekoko_core_register_offer_featured_category_block', 47 );

function nekoko_core_register_offer_featured_category_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) { return; }
	acf_add_local_field_group( array(
		'key' => 'group_nekoko_offer_featured_category', 'title' => 'Ponuda – istaknuta kategorija', 'fields' => array(
			array( 'key' => 'field_nekoko_offer_featured_category_title', 'label' => 'Naslov', 'name' => 'offer_featured_category_title', 'type' => 'text', 'default_value' => 'Pomoć i svakodnevne usluge' ),
			array( 'key' => 'field_nekoko_offer_featured_category_text', 'label' => 'Opis', 'name' => 'offer_featured_category_text', 'type' => 'textarea', 'rows' => 2, 'default_value' => 'Podrška u svakodnevnim obavezama kada vam je potrebna.' ),
			array( 'key' => 'field_nekoko_offer_featured_category_icon', 'label' => 'Ikonica', 'instructions' => 'Opciono. Ako je ne odaberete, prikazuje se jednobojna outline ikonica ruku koje se rukuju.', 'name' => 'offer_featured_category_icon', 'type' => 'image', 'return_format' => 'array', 'preview_size' => 'thumbnail', 'library' => 'all' ),
			array( 'key' => 'field_nekoko_offer_featured_category_icon_color', 'label' => 'Boja ikonice', 'name' => 'offer_featured_category_icon_color', 'type' => 'color_picker', 'default_value' => '#004682', 'return_format' => 'string' ),
			array( 'key' => 'field_nekoko_offer_featured_category_background', 'label' => 'Boja pozadine kartice', 'name' => 'offer_featured_category_background', 'type' => 'color_picker', 'default_value' => '#ffffff', 'return_format' => 'string' ),
			array( 'key' => 'field_nekoko_offer_featured_category_pills', 'label' => 'Oznake kategorija', 'name' => 'offer_featured_category_pills', 'type' => 'repeater', 'layout' => 'table', 'min' => 0, 'button_label' => 'Dodajte oznaku', 'sub_fields' => array(
				array( 'key' => 'field_nekoko_offer_featured_category_pill_label', 'label' => 'Tekst', 'name' => 'label', 'type' => 'text', 'required' => 1 ),
				array( 'key' => 'field_nekoko_offer_featured_category_pill_link', 'label' => 'Link', 'name' => 'link', 'type' => 'link', 'return_format' => 'array', 'required' => 1 ),
			) ),
			array( 'key' => 'field_nekoko_offer_featured_category_link_label', 'label' => 'Tekst glavnog linka', 'name' => 'offer_featured_category_link_label', 'type' => 'text', 'default_value' => 'Pogledaj oglase' ),
			array( 'key' => 'field_nekoko_offer_featured_category_link', 'label' => 'Glavni link', 'name' => 'offer_featured_category_link', 'type' => 'link', 'return_format' => 'array' ),
		), 'location' => array( array( array( 'param' => 'block', 'operator' => '==', 'value' => 'acf/nekoko-offer-featured-category' ) ) ), 'active' => true,
	) );
}
add_action( 'acf/init', 'nekoko_core_register_offer_featured_category_fields', 48 );

function nekoko_core_enqueue_offer_featured_category_assets() {
	$plugin_dir = dirname( NEKOKO_CORE_FILE );
	wp_enqueue_style( 'nekoko-offer-featured-category', plugins_url( 'assets/offer-featured-category-block.css', NEKOKO_CORE_FILE ), array(), filemtime( $plugin_dir . '/assets/offer-featured-category-block.css' ) );
}

function nekoko_core_handshake_icon() {
	return '<svg viewBox="0 0 48 48" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2.25" stroke-linecap="round" stroke-linejoin="round"><path d="m18 19 4-4a6 6 0 0 1 8.5 0l3.5 3.5"/><path d="m14 34-6-6 10-10 11 11a3 3 0 0 1-4 4l-1.5-1.5"/><path d="m34 14 6 6-9.5 9.5"/><path d="m16 30 4 4m0-6 4 4m0-6 3 3"/><path d="m18 19-3-3a6 6 0 0 0-8.5 0L4 18"/></svg>';
}
