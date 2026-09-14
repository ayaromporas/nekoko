<?php
/** Reusable hero for the Advanced search landing page. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function nekoko_core_register_advanced_search_hero_block() {
	if ( ! function_exists( 'acf_register_block_type' ) ) { return; }
	acf_register_block_type( array( 'name' => 'nekoko-advanced-search-hero', 'title' => 'Hero napredna pretraga', 'description' => 'Uvodna sekcija stranice Napredna pretraga.', 'category' => 'design', 'icon' => 'search', 'keywords' => array( 'pretraga', 'hero', 'NekoKO' ), 'render_template' => dirname( __DIR__ ) . '/blocks/advanced-search-hero/render.php', 'mode' => 'edit', 'supports' => array( 'align' => false, 'anchor' => false, 'jsx' => true ), 'enqueue_assets' => 'nekoko_core_enqueue_advanced_search_hero_assets' ) );
}
add_action( 'acf/init', 'nekoko_core_register_advanced_search_hero_block', 53 );

function nekoko_core_register_advanced_search_hero_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) { return; }
	acf_add_local_field_group( array( 'key' => 'group_nekoko_advanced_search_hero', 'title' => 'Hero napredna pretraga', 'fields' => array(
		array( 'key' => 'field_nekoko_advanced_search_hero_label', 'label' => 'Tekst oznake', 'name' => 'advanced_search_hero_label', 'type' => 'text', 'default_value' => 'Napredna pretraga' ),
		array( 'key' => 'field_nekoko_advanced_search_hero_title', 'label' => 'Naslov', 'name' => 'advanced_search_hero_title', 'type' => 'text', 'default_value' => 'Pronađite tačno ono što vam treba' ),
		array( 'key' => 'field_nekoko_advanced_search_hero_text', 'label' => 'Tekst', 'name' => 'advanced_search_hero_text', 'type' => 'textarea', 'rows' => 4, 'default_value' => 'Pretražujte ponude i potražnje po ključnim rečima, tipu oglasa, kategoriji, lokaciji i drugim filterima. Brzo i jednostavno dođite do prave usluge ili osobe koja može da vam pomogne.' ),
		array( 'key' => 'field_nekoko_advanced_search_hero_image', 'label' => 'Slika kolaža', 'name' => 'advanced_search_hero_image', 'type' => 'image', 'return_format' => 'id', 'library' => 'all', 'mime_types' => 'jpg,jpeg,png,webp' ),
		array( 'key' => 'field_nekoko_advanced_search_hero_background', 'label' => 'Boja pozadine', 'name' => 'advanced_search_hero_background', 'type' => 'color_picker', 'default_value' => '#fffaf0', 'return_format' => 'string' ),
	), 'location' => array( array( array( 'param' => 'block', 'operator' => '==', 'value' => 'acf/nekoko-advanced-search-hero' ) ) ), 'active' => true ) );
}
add_action( 'acf/init', 'nekoko_core_register_advanced_search_hero_fields', 54 );

function nekoko_core_enqueue_advanced_search_hero_assets() {
	$dir = dirname( NEKOKO_CORE_FILE );
	wp_enqueue_style( 'nekoko-advanced-search-hero', plugins_url( 'assets/advanced-search-hero-block.css', NEKOKO_CORE_FILE ), array(), filemtime( $dir . '/assets/advanced-search-hero-block.css' ) );
}
