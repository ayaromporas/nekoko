<?php
/** Read-only administrative review of member conversations. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'admin_menu', static function() {
	add_submenu_page( 'nekoko-settings', 'Poruke članova', 'Poruke članova', 'manage_options', 'nekoko-member-messages', 'nekoko_core_render_messages_admin' );
} );

function nekoko_core_admin_message_threads( $search = '', $page = 1 ) {
	if ( ! current_user_can( 'manage_options' ) ) { return new WP_Error( 'forbidden', 'Nemate dozvolu za pregled poruka.' ); }
	global $wpdb;
	$table = $wpdb->prefix . 'nekoko_messages';
	$where = '1=1';
	if ( '' !== $search ) {
		$like = '%' . $wpdb->esc_like( $search ) . '%';
		$where = $wpdb->prepare( '(sender.display_name LIKE %s OR sender.user_login LIKE %s OR recipient.display_name LIKE %s OR recipient.user_login LIKE %s OR listing.post_title LIKE %s OR m.listing_title LIKE %s OR CAST(m.sender_id AS CHAR)=%s OR CAST(m.recipient_id AS CHAR)=%s OR CAST(m.listing_id AS CHAR)=%s)', $like, $like, $like, $like, $like, $like, $search, $search, $search );
	}
	$group = "SELECT LEAST(m.sender_id,m.recipient_id) AS member_a, GREATEST(m.sender_id,m.recipient_id) AS member_b, m.listing_id, MAX(m.id) AS latest_id, COUNT(*) AS message_count FROM $table m LEFT JOIN {$wpdb->users} sender ON sender.ID=m.sender_id LEFT JOIN {$wpdb->users} recipient ON recipient.ID=m.recipient_id LEFT JOIN {$wpdb->posts} listing ON listing.ID=m.listing_id WHERE $where GROUP BY member_a,member_b,m.listing_id";
	$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM ($group) AS threads" );
	$page = min( max( 1, absint( $page ) ), max( 1, (int) ceil( $count / 20 ) ) );
	$rows = $wpdb->get_results( "SELECT threads.*, latest.created_at, latest.listing_title FROM ($group) AS threads INNER JOIN $table latest ON latest.id=threads.latest_id ORDER BY threads.latest_id DESC LIMIT 20 OFFSET " . ( ( $page - 1 ) * 20 ) );
	return array( 'rows' => $rows, 'count' => $count, 'page' => $page );
}

function nekoko_core_admin_thread_messages( $a, $b, $listing, $page = 1 ) {
	if ( ! current_user_can( 'manage_options' ) ) { return new WP_Error( 'forbidden', 'Nemate dozvolu za pregled poruka.' ); }
	global $wpdb;
	$table = $wpdb->prefix . 'nekoko_messages';
	$where = $wpdb->prepare( 'listing_id=%d AND ((sender_id=%d AND recipient_id=%d) OR (sender_id=%d AND recipient_id=%d))', absint( $listing ), absint( $a ), absint( $b ), absint( $b ), absint( $a ) );
	$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE $where" );
	$page = min( max( 1, absint( $page ) ), max( 1, (int) ceil( $count / 50 ) ) );
	$rows = array_reverse( $wpdb->get_results( "SELECT * FROM $table WHERE $where ORDER BY id DESC LIMIT 50 OFFSET " . ( ( $page - 1 ) * 50 ) ) );
	return array( 'rows' => $rows, 'count' => $count, 'page' => $page );
}

function nekoko_core_admin_message_member( $id ) {
	$user = get_userdata( $id );
	return $user ? $user->display_name . ' (#' . $id . ')' : 'Obrisan član (#' . $id . ')';
}

function nekoko_core_admin_message_listing( $id, $fallback = '' ) {
	if ( ! $id ) { return 'Direktan razgovor'; }
	return get_the_title( $id ) ?: ( $fallback ?: 'Obrisan oglas (#' . $id . ')' );
}

function nekoko_core_admin_message_pagination( $result, $size ) {
	if ( $result['count'] <= $size ) { return; }
	echo '<nav class="tablenav"><div class="tablenav-pages">' . wp_kses_post( paginate_links( array( 'base' => add_query_arg( 'paged', '%#%' ), 'format' => '', 'current' => $result['page'], 'total' => (int) ceil( $result['count'] / $size ), 'prev_text' => '← Prethodna', 'next_text' => 'Sledeća →' ) ) ) . '</div></nav>';
}

function nekoko_core_render_messages_admin() {
	if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'Nemate dozvolu za pregled poruka.', '', array( 'response' => 403 ) ); }
	nocache_headers();
	$a = absint( $_GET['member_a'] ?? 0 );
	$b = absint( $_GET['member_b'] ?? 0 );
	$listing = absint( $_GET['listing_id'] ?? 0 );
	$page = max( 1, absint( $_GET['paged'] ?? 1 ) );
	$base = admin_url( 'admin.php?page=nekoko-member-messages' );
	echo '<div class="wrap"><h1>Poruke članova</h1><p>Administratorski pregled razgovora. Otvaranje poruka ovde ne menja njihov status čitanja kod članova.</p>';
	if ( $a && $b ) {
		$result = nekoko_core_admin_thread_messages( $a, $b, $listing, $page );
		$fallback = $result['rows'] ? $result['rows'][0]->listing_title : '';
		echo '<p><a href="' . esc_url( $base ) . '">← Svi razgovori</a></p><h2>' . esc_html( nekoko_core_admin_message_member( $a ) . ' ↔ ' . nekoko_core_admin_message_member( $b ) ) . '</h2><h3>' . esc_html( 'Razgovor u vezi — ' . nekoko_core_admin_message_listing( $listing, $fallback ) ) . '</h3>';
		if ( $listing && get_post( $listing ) ) { echo '<p><a href="' . esc_url( get_permalink( $listing ) ) . '">Pogledajte oglas</a></p>'; }
		if ( ! $result['rows'] ) { echo '<p>Nema poruka u ovom razgovoru.</p>'; }
		foreach ( $result['rows'] as $message ) {
			echo '<article class="postbox" style="padding:16px;max-width:900px;overflow-wrap:anywhere"><strong>' . esc_html( nekoko_core_admin_message_member( $message->sender_id ) ) . '</strong> → ' . esc_html( nekoko_core_admin_message_member( $message->recipient_id ) ) . '<p>' . nl2br( esc_html( $message->body ) ) . '</p><small>' . esc_html( wp_date( 'd.m.Y. H:i', strtotime( $message->created_at . ' UTC' ) ) ) . ' · ' . esc_html( $message->read_at ? 'Pročitano' : 'Nije pročitano' ) . '</small></article>';
		}
		nekoko_core_admin_message_pagination( $result, 50 );
	} else {
		$search = sanitize_text_field( wp_unslash( $_GET['message_search'] ?? '' ) );
		$result = nekoko_core_admin_message_threads( $search, $page );
		echo '<form method="get"><input type="hidden" name="page" value="nekoko-member-messages"><p><label for="message-search">Član, korisničko ime, naslov oglasa ili ID</label></p><p><input type="search" id="message-search" name="message_search" value="' . esc_attr( $search ) . '" class="regular-text"> <button class="button">Pretražite</button> <a href="' . esc_url( $base ) . '">Poništite pretragu</a></p></form><p>Ukupno razgovora: ' . esc_html( number_format_i18n( $result['count'] ) ) . '</p><table class="widefat striped"><thead><tr><th>Članovi</th><th>Oglas</th><th>Poruke</th><th>Poslednja poruka</th><th>Pregled</th></tr></thead><tbody>';
		foreach ( $result['rows'] as $thread ) {
			$url = add_query_arg( array( 'member_a' => $thread->member_a, 'member_b' => $thread->member_b, 'listing_id' => $thread->listing_id ), $base );
			echo '<tr><td>' . esc_html( nekoko_core_admin_message_member( $thread->member_a ) . ' ↔ ' . nekoko_core_admin_message_member( $thread->member_b ) ) . '</td><td>' . esc_html( nekoko_core_admin_message_listing( $thread->listing_id, $thread->listing_title ) ) . '</td><td>' . esc_html( $thread->message_count ) . '</td><td>' . esc_html( wp_date( 'd.m.Y. H:i', strtotime( $thread->created_at . ' UTC' ) ) ) . '</td><td><a class="button" href="' . esc_url( $url ) . '">Otvorite razgovor</a></td></tr>';
		}
		if ( ! $result['rows'] ) { echo '<tr><td colspan="5">Nema razgovora za izabranu pretragu.</td></tr>'; }
		echo '</tbody></table>';
		nekoko_core_admin_message_pagination( $result, 20 );
	}
	echo '</div>';
}
