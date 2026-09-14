<?php
/** Reusable call to action banner for creating an Offer. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function nekoko_core_register_offer_cta_banner_block() {
	if ( ! function_exists( 'acf_register_block_type' ) ) { return; }
	acf_register_block_type( array(
		'name' => 'nekoko-offer-cta-banner', 'title' => 'Ponuda – CTA traka',
		'description' => 'CTA traka koja vodi ka objavi ponude.', 'category' => 'design', 'icon' => 'megaphone', 'keywords' => array( 'ponuda', 'cta', 'oglas', 'NekoKO' ),
		'render_template' => dirname( __DIR__ ) . '/blocks/offer-cta-banner/render.php', 'mode' => 'edit',
		'supports' => array( 'align' => false, 'anchor' => false, 'jsx' => true ), 'enqueue_assets' => 'nekoko_core_enqueue_offer_cta_banner_assets',
	) );
}
add_action( 'acf/init', 'nekoko_core_register_offer_cta_banner_block', 49 );

function nekoko_core_register_offer_cta_banner_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) { return; }
	acf_add_local_field_group( array(
		'key' => 'group_nekoko_offer_cta_banner', 'title' => 'Ponuda – CTA traka', 'fields' => array(
			array( 'key' => 'field_nekoko_offer_cta_banner_title', 'label' => 'Naslov', 'name' => 'offer_cta_banner_title', 'type' => 'text', 'default_value' => 'Želite da ponudite svoju uslugu?' ),
			array( 'key' => 'field_nekoko_offer_cta_banner_text', 'label' => 'Tekst', 'name' => 'offer_cta_banner_text', 'type' => 'textarea', 'rows' => 2, 'default_value' => 'Pridružite se zajednici NekoKO i lako objavite svoju ponudu.' ),
			array( 'key' => 'field_nekoko_offer_cta_banner_button_label', 'label' => 'Tekst dugmeta', 'name' => 'offer_cta_banner_button_label', 'type' => 'text', 'default_value' => 'Objavite ponudu' ),
			array( 'key' => 'field_nekoko_offer_cta_banner_button_link', 'label' => 'Link dugmeta', 'name' => 'offer_cta_banner_button_link', 'type' => 'link', 'return_format' => 'array' ),
			array( 'key' => 'field_nekoko_offer_cta_banner_note', 'label' => 'Dekorativni tekst desno', 'name' => 'offer_cta_banner_note', 'type' => 'textarea', 'rows' => 3, 'default_value' => 'Vaše veštine mogu nekome mnogo značiti' ),
			array( 'key' => 'field_nekoko_offer_cta_banner_background', 'label' => 'Boja pozadine', 'name' => 'offer_cta_banner_background', 'type' => 'color_picker', 'default_value' => '#e4f2ff', 'return_format' => 'string' ),
		), 'location' => array( array( array( 'param' => 'block', 'operator' => '==', 'value' => 'acf/nekoko-offer-cta-banner' ) ) ), 'active' => true,
	) );
}
add_action( 'acf/init', 'nekoko_core_register_offer_cta_banner_fields', 50 );

function nekoko_core_enqueue_offer_cta_banner_assets() {
	$plugin_dir = dirname( NEKOKO_CORE_FILE );
	wp_enqueue_style( 'nekoko-offer-cta-banner', plugins_url( 'assets/offer-cta-banner-block.css', NEKOKO_CORE_FILE ), array(), filemtime( $plugin_dir . '/assets/offer-cta-banner-block.css' ) );
}
