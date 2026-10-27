<?php
/** Test listings manager. Generated content is always marked and isolated. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

function nekoko_core_test_listing_is_test( $post_id ) { return '1' === get_post_meta( $post_id, '_nekoko_test_listing', true ); }
function nekoko_core_test_listing_badge( $post_id, $context = 'card' ) {
	if ( ! nekoko_core_test_listing_is_test( $post_id ) || '1' !== get_option( 'nekoko_test_listings_show_badge', '1' ) ) { return ''; }
	$title = get_option( 'nekoko_test_listings_badge_title', 'Primer oglasa' );
	$text = get_option( 'nekoko_test_listings_badge_text', 'Ovako moze izgledati vas oglas na NekoKO.' );
	if ( 'single' === $context ) { return '<aside class="nekoko-test-listing-notice"><strong>' . esc_html( $title ) . '</strong><span>' . esc_html( $text ) . '</span></aside>'; }
	return '<span class="nekoko-test-listing-badge">' . esc_html( $title ) . '</span>';
}

function nekoko_core_test_listing_ideas( $branch, $kind ) {
	$ideas = array(
		'ponuda' => array(
			'category' => array( 'Prakticne usluge', 'Kreativni projekti', 'Pomoc u domu', 'Usluge za ljubimce', 'Dvoriste i terasa', 'Ucenje i radionice', 'Organizacija prostora', 'Lokalna dostava' ),
			'subcategory' => array( 'Montaza i sitne popravke', 'Izrada po meri', 'Cuvanje i setnja', 'Sredjivanje biljaka', 'Planiranje dogadjaja', 'Pomoc oko tehnike', 'Personalizovani pokloni', 'Poducavanje jedan na jedan', 'Pakovanje i selidba', 'Odrzavanje prostora', 'Kreativna radionica', 'Pomoc vikendom' ),
		),
		'potraznja' => array(
			'category' => array( 'Pomoc koja je potrebna', 'Kucni projekti', 'Slobodno vreme', 'Ljubimci i priroda', 'Kreativne ideje', 'Ucenje i razvoj', 'Komisijske usluge', 'Komunikacija i podrska' ),
			'subcategory' => array( 'Pomoc oko kuce', 'Savet od strucne osobe', 'Drustvo za aktivnost', 'Cuvanje ljubimca', 'Podrska pri organizaciji', 'Prakticna pomoc vikendom', 'Ucenje nove vestine', 'Pomoc na terenu', 'Povremeni anga?man', 'Dogovor po potrebi', 'Mali lokalni zadaci', 'Jednokratna pomoc' ),
		),
		'tag' => array( 'Brz dogovor', 'Vikendom', 'Na terenu', 'Po preporuci', 'Fleksibilno vreme', 'Lokalno', 'Pazljivo', 'Kreativno', 'Pouzdano', 'Dogovor unapred', 'Za pocetnike', 'Licni pristup' ),
	);
	return $ideas[ $branch ][ $kind ] ?? array();
}

function nekoko_core_test_next_label( $branch, $kind ) {
	$ideas = nekoko_core_test_listing_ideas( $branch, $kind );
	if ( ! $ideas ) { return ''; }
	$key = 'nekoko_test_counter_' . sanitize_key( $branch . '_' . $kind );
	$counter = absint( get_option( $key, 0 ) );
	for ( $attempt = 0; $attempt < 100; $attempt++, $counter++ ) {
		$base = $ideas[ $counter % count( $ideas ) ];
		$cycle = (int) floor( $counter / count( $ideas ) );
		$label = $cycle ? $base . ' - ideja ' . ( $cycle + 1 ) : $base;
		if ( ! term_exists( $label, 'oglas_tag' ) && ! term_exists( $label, 'kategorija_oglasa' ) ) { update_option( $key, $counter + 1, false ); return $label; }
	}
	return '';
}

function nekoko_core_test_terms( $branch, $parent = 0 ) {
	return get_terms( array( 'taxonomy' => 'kategorija_oglasa', 'hide_empty' => false, 'parent' => $parent, 'meta_query' => array( array( 'key' => '_nekoko_test_content', 'value' => '1' ) ) ) );
}
function nekoko_core_test_create_term( $name, $parent = 0 ) {
	$created = wp_insert_term( $name, 'kategorija_oglasa', array( 'parent' => $parent ) );
	if ( is_wp_error( $created ) ) { return 0; }
	update_term_meta( $created['term_id'], '_nekoko_test_content', '1' );
	return (int) $created['term_id'];
}
function nekoko_core_test_generate_categories() {
	$made = 0;
	foreach ( array( 'ponuda', 'potraznja' ) as $branch ) {
		$roots = nekoko_core_category_roots(); if ( empty( $roots[ $branch ] ) ) { continue; }
		$name = nekoko_core_test_next_label( $branch, 'category' );
		if ( $name && nekoko_core_test_create_term( $name, $roots[ $branch ]->term_id ) ) { $made++; }
	}
	return $made;
}
function nekoko_core_test_generate_subcategories() {
	$made = 0;
	foreach ( array( 'ponuda', 'potraznja' ) as $branch ) {
		$roots = nekoko_core_category_roots(); if ( empty( $roots[ $branch ] ) ) { continue; }
		$categories = nekoko_core_test_terms( $branch, $roots[ $branch ]->term_id );
		if ( ! $categories ) { nekoko_core_test_generate_categories(); $categories = nekoko_core_test_terms( $branch, $roots[ $branch ]->term_id ); }
		if ( ! $categories ) { continue; }
		$key = 'nekoko_test_subcategory_parent_' . $branch; $index = absint( get_option( $key, 0 ) ); $parent = $categories[ $index % count( $categories ) ]; update_option( $key, $index + 1, false );
		$name = nekoko_core_test_next_label( $branch, 'subcategory' );
		if ( $name && nekoko_core_test_create_term( $name, $parent->term_id ) ) { $made++; }
	}
	return $made;
}
function nekoko_core_test_generate_tags( $count = 3 ) {
	$made = 0;
	for ( $i = 0; $i < $count; $i++ ) {
		$name = nekoko_core_test_next_label( 'tag', 'tag' ); if ( ! $name ) { continue; }
		$term = wp_insert_term( $name, 'oglas_tag' ); if ( ! is_wp_error( $term ) ) { update_term_meta( $term['term_id'], '_nekoko_test_content', '1' ); $made++; }
	}
	return $made;
}
function nekoko_core_test_ensure_branch_terms( $branch ) {
	$roots = nekoko_core_category_roots(); if ( empty( $roots[ $branch ] ) ) { return array(); }
	$categories = nekoko_core_test_terms( $branch, $roots[ $branch ]->term_id );
	if ( ! $categories ) { nekoko_core_test_generate_categories(); $categories = nekoko_core_test_terms( $branch, $roots[ $branch ]->term_id ); }
	$category = $categories ? reset( $categories ) : false; if ( ! $category ) { return array(); }
	$children = nekoko_core_test_terms( $branch, $category->term_id );
	if ( ! $children ) { nekoko_core_test_generate_subcategories(); $children = nekoko_core_test_terms( $branch, $category->term_id ); }
	$child = $children ? reset( $children ) : false;
	return array( $roots[ $branch ], $category, $child );
}
function nekoko_core_test_author() {
	$users = get_users( array( 'meta_key' => '_nekoko_test_author', 'meta_value' => '1', 'number' => 1 ) );
	if ( $users ) { return (int) $users[0]->ID; }
	$user_id = wp_insert_user( array( 'user_login' => 'nekoko-primer', 'user_pass' => wp_generate_password( 32, true, true ), 'user_email' => 'primeri@nekoko.invalid', 'display_name' => 'NekoKO Primer', 'first_name' => 'NekoKO', 'last_name' => 'Primer', 'role' => 'oglasivac' ) );
	if ( is_wp_error( $user_id ) ) { return get_current_user_id(); }
	update_user_meta( $user_id, '_nekoko_test_author', '1' ); update_user_meta( $user_id, 'nekoko_email_verified', '1' ); update_user_meta( $user_id, 'nekoko_phone', '064 555 0199' ); update_user_meta( $user_id, 'nekoko_city', 'Beograd' );
	return (int) $user_id;
}
function nekoko_core_test_listing_templates( $branch ) {
	$offer = array(
		array( 'Montaza polica i sitne popravke', 'Precizna pomoc za male kucne projekte, uz dogovor o terminu.', 'Donosim osnovni alat i pomazem oko polica, zavesa, drzaca i drugih sitnih popravki. Pre dolaska zajedno proveravamo sta je potrebno.', 'Beograd', 3800 ),
		array( 'Portret vaseg ljubimca po fotografiji', 'Rucno radjen ilustrativni portret za poklon ili uspomenu.', 'Radim digitalne portrete pasa, macaka i drugih ljubimaca. Saljem pregled pre zavrsne verzije i prilagodjavam boje prostoru.', 'Novi Sad', 5200 ),
		array( 'Cuvanje biljaka dok ste na putu', 'Redovno zalivanje i kratka provera stanja biljaka.', 'Dolazim po dogovoru, zalivam biljke prema vasim uputstvima i saljem kratku fotografiju nakon posete.', 'Zemun', 2500 ),
		array( 'Pomoc pri organizaciji ormara', 'Zajedno pravimo funkcionalniji raspored bez viska stvari.', 'Pomazem da sortirate odecu i sitnice, osmislite kutije i jednostavan sistem odrzavanja prostora.', 'Kragujevac', 4500 ),
		array( 'Setnja psa vikendom', 'Mirna i pazljiva setnja u dogovorenom terminu.', 'Dostupan sam za pojedinacne ili redovne setnje. Postujem rutinu ljubimca i saljem poruku nakon setnje.', 'Nis', 1800 ),
		array( 'Kreativna radionica za mali tim', 'Prakticna radionica izrade jednostavnih personalizovanih predmeta.', 'Donosim potreban materijal i vodim grupu kroz opustenu radionicu prilagodjenu uzrastu i interesovanju.', 'Subotica', 7600 ),
	);
	$demand = array(
		array( 'Trazim pomoc oko sredjivanja podruma', 'Potreban mi je neko organizovan za jedan popodnevni termin.', 'Potrebna je pomoc oko razvrstavanja kutija i iznosenja lakseg viska. Prostor je suv i pristupacan.', 'Beograd', '4 sata' ),
		array( 'Potreban setac za psa vikendom', 'Trazim pouzdanu osobu za jednu duzu setnju.', 'Pas je miran i naviknut na povodac. Vazan mi je dogovor unapred i kratka poruka nakon setnje.', 'Novi Sad', '2 sata' ),
		array( 'Trazim cas keramike za pocetnike', 'Volela bih individualni uvod u rad sa glinom.', 'Potrebno mi je strpljivo uvodno objasnjenje i mogucnost da probam osnovne tehnike u opustenoj atmosferi.', 'Pancevo', 'jedan dan' ),
		array( 'Potrebna pomoc oko montaze zavese', 'Jedan manji kucni posao za nekoga sa osnovnim alatom.', 'Zavesa i nosaci su kupljeni. Potrebno je precizno busenje i montaza u dogovorenom terminu.', 'Kragujevac', '2 sata' ),
		array( 'Trazim osobu za zalivanje biljaka', 'Kratka pomoc dok sam odsutan nekoliko dana.', 'Biljke su na dve lokacije u istom kraju. Uputstva su jasna i termin moze biti fleksibilan.', 'Nis', '3 dana' ),
		array( 'Potreban savet za organizaciju radnog kutka', 'Trazim praktican predlog za mali prostor kod kuce.', 'Voleo bih pomoc oko rasporeda stola, odlaganja i osvetljenja kako bi kutak bio prijatniji za rad.', 'Subotica', '1 sat' ),
	);
	return 'ponuda' === $branch ? $offer : $demand;
}
function nekoko_core_test_placeholder_image() {
	$option_key = 'nekoko_test_listing_placeholder_attachment';
	$attachment_id = absint( get_option( $option_key ) );
	if ( $attachment_id && 'attachment' === get_post_type( $attachment_id ) && wp_attachment_is_image( $attachment_id ) ) {
		return $attachment_id;
	}

	$file = plugin_dir_path( NEKOKO_CORE_FILE ) . 'assets/images/test-listing-placeholder.png';
	if ( ! is_readable( $file ) ) {
		return 0;
	}

	$upload = wp_upload_bits( 'nekoko-test-listing-placeholder.png', null, file_get_contents( $file ) );
	if ( ! empty( $upload['error'] ) ) {
		return 0;
	}

	$filetype = wp_check_filetype( $upload['file'], null );
	$attachment_id = wp_insert_attachment(
		array(
			'post_mime_type' => $filetype['type'],
			'post_title'     => 'NekoKO placeholder za test oglase',
			'post_status'    => 'inherit',
		),
		$upload['file']
	);
	if ( is_wp_error( $attachment_id ) ) {
		return 0;
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';
	wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );
	update_option( $option_key, $attachment_id, false );
	return (int) $attachment_id;
}
function nekoko_core_test_image( $serial ) {
	return nekoko_core_test_placeholder_image();
}
function nekoko_core_test_generate_listings( $branch, $count ) {
	$count = min( 12, max( 1, absint( $count ) ) ); $terms = nekoko_core_test_ensure_branch_terms( $branch ); if ( count( $terms ) < 2 ) { return 0; }
	$tags = get_terms( array( 'taxonomy' => 'oglas_tag', 'hide_empty' => false, 'meta_query' => array( array( 'key' => '_nekoko_test_content', 'value' => '1' ) ) ) );
	if ( count( $tags ) < 2 ) {
		nekoko_core_test_generate_tags( 6 );
		$tags = get_terms( array( 'taxonomy' => 'oglas_tag', 'hide_empty' => false, 'meta_query' => array( array( 'key' => '_nekoko_test_content', 'value' => '1' ) ) ) );
	}
	$templates = nekoko_core_test_listing_templates( $branch ); $key = 'nekoko_test_listing_counter_' . $branch; $serial = absint( get_option( $key, 0 ) ); $author = nekoko_core_test_author(); $made = 0;
	while ( $made < $count && $serial < 10000 ) {
		$item = $templates[ $serial % count( $templates ) ]; $round = (int) floor( $serial / count( $templates ) ); $variant = array( 'Licni pristup', 'Fleksibilan dogovor', 'Pazljivo planirano', 'Za komsiluk', 'Po meri potrebe', 'Prakticno resenje' )[ $serial % 6 ];
		$title = $item[0] . ( $round ? ' - ' . $variant . ' ' . ( $round + 1 ) : '' ); $excerpt = $item[1] . ' ' . $variant . '.'; $content = $item[2] . "

" . 'Ovo je test primer oglasa sa jedinstvenom kombinacijom podataka i jasnim opisom usluge.'; $fingerprint = hash( 'sha256', $branch . '|' . $title . '|' . $excerpt . '|' . $content . '|' . $item[3] );
		$exists = get_posts( array( 'post_type' => 'oglas', 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids', 'meta_key' => '_nekoko_test_fingerprint', 'meta_value' => $fingerprint ) ); if ( $exists ) { $serial++; continue; }
		$term_ids = array_filter( array_map( 'absint', array( $terms[0]->term_id, $terms[1]->term_id, ! empty( $terms[2] ) ? $terms[2]->term_id : 0 ) ) );
		$post_id = wp_insert_post( array( 'post_type' => 'oglas', 'post_status' => 'publish', 'post_author' => $author, 'post_title' => $title, 'post_excerpt' => $excerpt, 'post_content' => $content, 'tax_input' => array( 'kategorija_oglasa' => $term_ids ) ), true ); if ( is_wp_error( $post_id ) ) { $serial++; continue; }
		wp_set_object_terms( $post_id, $term_ids, 'kategorija_oglasa', false );
		if ( $tags ) { wp_set_object_terms( $post_id, array( $tags[ $serial % count( $tags ) ]->term_id, $tags[ ( $serial + 1 ) % count( $tags ) ]->term_id ), 'oglas_tag', false ); }
		update_post_meta( $post_id, '_nekoko_test_listing', '1' ); update_post_meta( $post_id, '_nekoko_test_branch', $branch ); update_post_meta( $post_id, '_nekoko_test_fingerprint', $fingerprint ); update_post_meta( $post_id, 'nekoko_listing_location', $item[3] ); update_post_meta( $post_id, 'nekoko_listing_currency', 'RSD' );
		if ( 'ponuda' === $branch ) { update_post_meta( $post_id, 'nekoko_listing_price', $item[4] ); update_post_meta( $post_id, 'nekoko_listing_price_agreed', '0' ); } else { update_post_meta( $post_id, 'nekoko_listing_duration', $item[4] ); update_post_meta( $post_id, 'nekoko_listing_duration_na', '0' ); }
		$image = nekoko_core_test_image( $serial ); if ( $image ) { set_post_thumbnail( $post_id, $image ); } $made++; $serial++;
	}
	update_option( $key, $serial, false ); return $made;
}
function nekoko_core_test_delete_listings( $branch = '' ) {
	$query = array( 'post_type' => 'oglas', 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'meta_query' => array( array( 'key' => '_nekoko_test_listing', 'value' => '1' ) ) ); if ( $branch ) { $query['meta_query'][] = array( 'key' => '_nekoko_test_branch', 'value' => $branch ); }
	$ids = get_posts( $query ); foreach ( $ids as $id ) { wp_delete_post( $id, true ); } return count( $ids );
}
function nekoko_core_test_delete_terms() {
	$deleted = 0; foreach ( array( 'kategorija_oglasa', 'oglas_tag' ) as $taxonomy ) { $terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false, 'meta_query' => array( array( 'key' => '_nekoko_test_content', 'value' => '1' ) ) ) ); usort( $terms, static function( $a, $b ) { return count( get_ancestors( $b->term_id, 'kategorija_oglasa' ) ) <=> count( get_ancestors( $a->term_id, 'kategorija_oglasa' ) ); } ); foreach ( $terms as $term ) { if ( ! is_wp_error( wp_delete_term( $term->term_id, $taxonomy ) ) ) { $deleted++; } } } return $deleted;
}
function nekoko_core_test_notice( $message = '' ) { if ( $message ) { set_transient( 'nekoko_test_admin_notice_' . get_current_user_id(), $message, MINUTE_IN_SECONDS ); } }
function nekoko_core_test_admin_action() {
	if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'nekoko_test_listings_action' ) ) { wp_die( 'Nemate dozvolu za ovu akciju.', 403 ); }
	$task = sanitize_key( wp_unslash( $_POST['nekoko_test_task'] ?? '' ) ); $count = absint( $_POST['nekoko_test_count'] ?? 6 ); $message = '';
	if ( 'save_settings' === $task ) { update_option( 'nekoko_test_listings_show_badge', empty( $_POST['nekoko_test_show_badge'] ) ? '0' : '1', false ); update_option( 'nekoko_test_listings_badge_title', sanitize_text_field( wp_unslash( $_POST['nekoko_test_badge_title'] ?? 'Primer oglasa' ) ), false ); update_option( 'nekoko_test_listings_badge_text', sanitize_text_field( wp_unslash( $_POST['nekoko_test_badge_text'] ?? 'Ovako moze izgledati vas oglas na NekoKO.' ) ), false ); $message = 'Podesavanja oznake su sacuvana.'; }
	if ( 'generate_categories' === $task ) { $message = 'Dodato test kategorija: ' . nekoko_core_test_generate_categories() . '.'; }
	if ( 'generate_subcategories' === $task ) { $message = 'Dodato test podkategorija: ' . nekoko_core_test_generate_subcategories() . '.'; }
	if ( 'generate_tags' === $task ) { $message = 'Dodato test tagova: ' . nekoko_core_test_generate_tags( 3 ) . '.'; }
	if ( 'generate_ponuda' === $task || 'generate_potraznja' === $task ) { $branch = str_replace( 'generate_', '', $task ); $message = 'Generisano test oglasa: ' . nekoko_core_test_generate_listings( $branch, $count ) . '.'; }
	if ( 'delete_ponuda' === $task || 'delete_potraznja' === $task ) { $branch = str_replace( 'delete_', '', $task ); $message = 'Obrisano test oglasa: ' . nekoko_core_test_delete_listings( $branch ) . '.'; }
	if ( 'delete_all' === $task ) { $posts = nekoko_core_test_delete_listings(); $terms = nekoko_core_test_delete_terms(); $message = 'Obrisano test oglasa: ' . $posts . '. Obrisano test kategorija i tagova: ' . $terms . '.'; }
	nekoko_core_test_notice( $message ?: 'Akcija nije prepoznata.' ); wp_safe_redirect( admin_url( 'admin.php?page=nekoko-test-listings' ) ); exit;
}
add_action( 'admin_post_nekoko_test_listings_action', 'nekoko_core_test_admin_action' );
function nekoko_core_test_form( $task, $label, $danger = false, $count = false ) {
	echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" class="nekoko-test-listings__form">'; wp_nonce_field( 'nekoko_test_listings_action' ); echo '<input type="hidden" name="action" value="nekoko_test_listings_action"><input type="hidden" name="nekoko_test_task" value="' . esc_attr( $task ) . '">'; if ( $count ) { echo '<label>Kolicina <select name="nekoko_test_count"><option value="3">3</option><option value="6" selected>6</option><option value="12">12</option></select></label>'; } echo '<button class="button ' . ( $danger ? 'button-link-delete' : 'button-primary' ) . '" type="submit"' . ( $danger ? ' onclick="return confirm(\'Ova akcija brise samo test sadrzaj. Nastaviti?\');"' : '' ) . '>' . esc_html( $label ) . '</button></form>';
}
function nekoko_core_render_test_listings_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; } $notice = get_transient( 'nekoko_test_admin_notice_' . get_current_user_id() ); delete_transient( 'nekoko_test_admin_notice_' . get_current_user_id() );
	$test_posts = get_posts( array( 'post_type' => 'oglas', 'post_status' => 'any', 'fields' => 'ids', 'posts_per_page' => -1, 'meta_key' => '_nekoko_test_listing', 'meta_value' => '1' ) ); $test_terms = get_terms( array( 'taxonomy' => array( 'kategorija_oglasa', 'oglas_tag' ), 'hide_empty' => false, 'meta_query' => array( array( 'key' => '_nekoko_test_content', 'value' => '1' ) ) ) );
	?><div class="wrap nekoko-test-listings"><h1>Test oglasi</h1><?php if ( $notice ) : ?><div class="notice notice-success is-dismissible"><p><?php echo esc_html( $notice ); ?></p></div><?php endif; ?><p>Generator pravi samo sadrzaj obelezen kao test. Svaki novi oglas dobija jedinstven naslov, opis i interni otisak, bez duplikata.</p><p><strong>Trenutno:</strong> <?php echo esc_html( count( $test_posts ) ); ?> test oglasa i <?php echo esc_html( is_wp_error( $test_terms ) ? 0 : count( $test_terms ) ); ?> test kategorija/tagova.</p><hr><h2>Oznaka na test oglasu</h2><form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"><?php wp_nonce_field( 'nekoko_test_listings_action' ); ?><input type="hidden" name="action" value="nekoko_test_listings_action"><input type="hidden" name="nekoko_test_task" value="save_settings"><p><label><input type="checkbox" name="nekoko_test_show_badge" value="1" <?php checked( '1', get_option( 'nekoko_test_listings_show_badge', '1' ) ); ?>> Prikazi oznaku na test oglasima</label></p><p><label>Naslov<br><input class="regular-text" name="nekoko_test_badge_title" value="<?php echo esc_attr( get_option( 'nekoko_test_listings_badge_title', 'Primer oglasa' ) ); ?>"></label></p><p><label>Tekst<br><input class="large-text" name="nekoko_test_badge_text" value="<?php echo esc_attr( get_option( 'nekoko_test_listings_badge_text', 'Ovako moze izgledati vas oglas na NekoKO.' ) ); ?>"></label></p><p><button class="button button-primary">Sacuvaj oznaku</button></p></form><hr><h2>Test struktura</h2><?php nekoko_core_test_form( 'generate_categories', 'Generisi test kategorije' ); nekoko_core_test_form( 'generate_subcategories', 'Generisi test podkategorije' ); nekoko_core_test_form( 'generate_tags', 'Generisi test tagove' ); ?><hr><h2>Test oglasi</h2><?php nekoko_core_test_form( 'generate_ponuda', 'Generisi test oglase u Ponuda', false, true ); nekoko_core_test_form( 'generate_potraznja', 'Generisi test oglase u Potraznja', false, true ); ?><hr><h2>Brisanje</h2><?php nekoko_core_test_form( 'delete_ponuda', 'Obrisi sve test oglase u Ponuda', true ); nekoko_core_test_form( 'delete_potraznja', 'Obrisi sve test oglase u Potraznja', true ); nekoko_core_test_form( 'delete_all', 'Obrisi sav test sadrzaj', true ); ?></div><?php
}
function nekoko_core_add_test_listings_menu() { add_submenu_page( 'nekoko-settings', 'Test oglasi', 'Test oglasi', 'manage_options', 'nekoko-test-listings', 'nekoko_core_render_test_listings_page' ); }
add_action( 'admin_menu', 'nekoko_core_add_test_listings_menu', 102 );
