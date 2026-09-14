<?php
/** Reusable, independently editable blocks for the Potražnja landing page. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function nekoko_core_register_demand_page_blocks() {
	if ( ! function_exists( 'acf_register_block_type' ) ) { return; }
	$blocks = array(
		'nekoko-demand-hero' => array( 'Hero potražnja', 'cover-image' ),
		'nekoko-demand-categories' => array( 'Sve kategorije u potražnji', 'category' ),
		'nekoko-demand-featured-category' => array( 'Potražnja – istaknuta kategorija', 'heart' ),
		'nekoko-demand-cta-banner' => array( 'Potražnja – CTA traka', 'megaphone' ),
	);
	foreach ( $blocks as $name => $settings ) {
		acf_register_block_type( array( 'name' => $name, 'title' => $settings[0], 'description' => $settings[0], 'category' => 'design', 'icon' => $settings[1], 'keywords' => array( 'potražnja', 'NekoKO' ), 'render_template' => dirname( __DIR__ ) . '/blocks/demand-page/render.php', 'mode' => 'edit', 'supports' => array( 'align' => false, 'anchor' => false, 'jsx' => true ), 'enqueue_assets' => 'nekoko_core_enqueue_demand_page_assets' ) );
	}
}
add_action( 'acf/init', 'nekoko_core_register_demand_page_blocks', 51 );

function nekoko_core_register_demand_page_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) { return; }
	$groups = array(
		'hero' => array( 'Hero potražnja', array(
			array( 'label' => 'Tekst oznake', 'name' => 'label', 'type' => 'text', 'default_value' => 'Kategorije potražnje' ), array( 'label' => 'Naslov', 'name' => 'title', 'type' => 'text', 'default_value' => 'Potražnja' ), array( 'label' => 'Tekst', 'name' => 'text', 'type' => 'textarea', 'rows' => 3, 'default_value' => 'Pregledajte glavne kategorije potražnje i pronađite prilike da pomognete ljudima kojima su potrebne vaše veštine i vreme.' ), array( 'label' => 'Placeholder pretrage', 'name' => 'placeholder', 'type' => 'text', 'default_value' => 'Šta tražite?' ), array( 'label' => 'Oznaka kategorije', 'name' => 'category_label', 'type' => 'text', 'default_value' => 'Kategorija' ), array( 'label' => 'Tekst dugmeta', 'name' => 'submit', 'type' => 'text', 'default_value' => 'Pretraži' ), array( 'label' => 'Stranica rezultata', 'name' => 'search_url', 'type' => 'url', 'default_value' => home_url( '/napredna-pretraga/' ) ), array( 'label' => 'Slika kolaža', 'name' => 'image', 'type' => 'image', 'return_format' => 'id', 'library' => 'all', 'mime_types' => 'jpg,jpeg,png,webp' ),
		) ),
		'categories' => array( 'Sve kategorije u potražnji', array( array( 'label' => 'Naslov', 'name' => 'title', 'type' => 'text', 'default_value' => 'Sve kategorije u potražnji' ), array( 'label' => 'Kratki tekst', 'name' => 'text', 'type' => 'textarea', 'rows' => 2, 'default_value' => 'Istražite glavne kategorije u potražnji i odaberite oblast u kojoj možete da pomognete.' ), array( 'label' => 'Boja pozadine', 'name' => 'background', 'type' => 'color_picker', 'default_value' => '#f5f2eb', 'return_format' => 'string' ) ) ),
		'featured' => array( 'Potražnja – istaknuta kategorija', array( array( 'label' => 'Naslov', 'name' => 'title', 'type' => 'text', 'default_value' => 'Pomoć i svakodnevne usluge' ), array( 'label' => 'Opis', 'name' => 'text', 'type' => 'textarea', 'rows' => 2, 'default_value' => 'Pronađite ljude kojima su potrebni vreme, veštine i podrška u svakodnevnim obavezama.' ), array( 'label' => 'Ikonica', 'name' => 'icon', 'type' => 'image', 'return_format' => 'array', 'preview_size' => 'thumbnail', 'library' => 'all', 'instructions' => 'Opciono. Podrazumevano se prikazuje jednobojna outline ikonica ruku.' ), array( 'label' => 'Boja ikonice', 'name' => 'icon_color', 'type' => 'color_picker', 'default_value' => '#004682', 'return_format' => 'string' ), array( 'label' => 'Boja pozadine kartice', 'name' => 'background', 'type' => 'color_picker', 'default_value' => '#ffffff', 'return_format' => 'string' ), array( 'label' => 'Oznake kategorija', 'name' => 'pills', 'type' => 'repeater', 'layout' => 'table', 'button_label' => 'Dodajte oznaku', 'sub_fields' => array( array( 'label' => 'Tekst', 'name' => 'label', 'type' => 'text', 'required' => 1 ), array( 'label' => 'Link', 'name' => 'link', 'type' => 'link', 'return_format' => 'array', 'required' => 1 ) ) ), array( 'label' => 'Tekst glavnog linka', 'name' => 'link_label', 'type' => 'text', 'default_value' => 'Pogledaj oglase' ), array( 'label' => 'Glavni link', 'name' => 'link', 'type' => 'link', 'return_format' => 'array' ) ) ),
		'cta' => array( 'Potražnja – CTA traka', array( array( 'label' => 'Naslov', 'name' => 'title', 'type' => 'text', 'default_value' => 'Potrebna vam je pomoć?' ), array( 'label' => 'Tekst', 'name' => 'text', 'type' => 'textarea', 'rows' => 2, 'default_value' => 'Objavite potražnju i povežite se sa ljudima koji mogu da pomognu.' ), array( 'label' => 'Tekst dugmeta', 'name' => 'button_label', 'type' => 'text', 'default_value' => 'Objavite potražnju' ), array( 'label' => 'Link dugmeta', 'name' => 'button_link', 'type' => 'link', 'return_format' => 'array' ), array( 'label' => 'Dekorativni tekst desno', 'name' => 'note', 'type' => 'textarea', 'rows' => 3, 'default_value' => 'Prava pomoć može biti bliže nego što mislite' ), array( 'label' => 'Boja pozadine', 'name' => 'background', 'type' => 'color_picker', 'default_value' => '#fbe9e8', 'return_format' => 'string' ) ) ),
	);
	foreach ( $groups as $type => $group ) {
		$fields = array();
		foreach ( $group[1] as $field ) { $field['key'] = 'field_nekoko_demand_' . $type . '_' . $field['name']; $field['name'] = 'demand_' . $type . '_' . $field['name']; if ( ! empty( $field['sub_fields'] ) ) { foreach ( $field['sub_fields'] as &$sub ) { $sub['key'] = 'field_nekoko_demand_' . $type . '_' . $field['name'] . '_' . $sub['name']; } unset( $sub ); } $fields[] = $field; }
		acf_add_local_field_group( array( 'key' => 'group_nekoko_demand_' . $type, 'title' => $group[0], 'fields' => $fields, 'location' => array( array( array( 'param' => 'block', 'operator' => '==', 'value' => 'acf/nekoko-demand-' . ( 'categories' === $type ? 'categories' : ( 'featured' === $type ? 'featured-category' : ( 'cta' === $type ? 'cta-banner' : 'hero' ) ) ) ) ) ), 'active' => true ) );
	}
}
add_action( 'acf/init', 'nekoko_core_register_demand_page_fields', 52 );

function nekoko_core_enqueue_demand_page_assets() {
	$dir = dirname( NEKOKO_CORE_FILE ); $url = plugins_url( '/', NEKOKO_CORE_FILE );
	foreach ( array( 'offer-hero-block.css', 'offer-categories-block.css', 'offer-featured-category-block.css', 'offer-cta-banner-block.css', 'demand-page-block.css' ) as $file ) { wp_enqueue_style( 'nekoko-demand-' . md5( $file ), $url . 'assets/' . $file, array(), filemtime( $dir . '/assets/' . $file ) ); }
}

function nekoko_core_demand_category_cards() {
	$roots = nekoko_core_category_roots(); $root = $roots['potraznja'] ?? null; if ( ! $root ) { return array(); }
	$terms = get_terms( array( 'taxonomy' => 'kategorija_oglasa', 'hide_empty' => false, 'parent' => $root->term_id, 'orderby' => 'name' ) ); if ( is_wp_error( $terms ) ) { return array(); }
	$cards = array(); foreach ( $terms as $term ) { $children = get_terms( array( 'taxonomy' => 'kategorija_oglasa', 'hide_empty' => false, 'parent' => $term->term_id, 'orderby' => 'name' ) ); $cards[] = array( 'term' => $term, 'children' => is_wp_error( $children ) ? array() : $children, 'icon' => function_exists( 'nekoko_core_top_request_icon_for_term' ) ? nekoko_core_top_request_icon_for_term( $term ) : 'event' ); } return $cards;
}
