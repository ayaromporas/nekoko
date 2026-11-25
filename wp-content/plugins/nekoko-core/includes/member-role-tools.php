<?php
/** On-demand role conversion, restricted to administrators with promotion rights. */
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'acf/init', static function() {
	if ( ! function_exists( 'acf_add_local_field' ) ) { return; }
	acf_add_local_field( array( 'key' => 'field_nekoko_member_role_tools', 'name' => '', 'label' => 'Provera uloga korisnika', 'type' => 'message', 'parent' => 'group_nekoko_moderation_settings', 'menu_order' => 1, 'message' => '' ) );
}, 32 );

add_action( 'acf/render_field/key=field_nekoko_member_role_tools', static function() {
	if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'promote_users' ) ) { return; }
	echo '<p>Proverava sve korisnike i menja naloge koji imaju samo ulogu Pretplatnik u Oglašivač. Administratori i druge uloge ostaju nepromenjeni. Pokreće se tek klikom na dugme, nezavisno od čuvanja podešavanja.</p><button type="button" class="button button-secondary" id="nekoko-convert-subscribers">Proveri uloge i promeni pretplatnike u oglašivače</button><p id="nekoko-role-conversion-status" role="status" aria-live="polite"></p>';
} );

add_action( 'admin_enqueue_scripts', static function() {
	if ( 'nekoko-settings-general' !== ( $_GET['page'] ?? '' ) || ! current_user_can( 'manage_options' ) || ! current_user_can( 'promote_users' ) ) { return; }
	wp_enqueue_script( 'nekoko-member-role-tools', plugins_url( 'assets/member-role-tools.js', NEKOKO_CORE_FILE ), array(), filemtime( dirname( __DIR__ ) . '/assets/member-role-tools.js' ), true );
	wp_localize_script( 'nekoko-member-role-tools', 'nekokoMemberRoleTools', array( 'url' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'nekoko_convert_subscribers' ) ) );
} );

/** Stable ID cursor avoids skipping users as their roles change between batches. */
function nekoko_core_convert_subscriber_batch( $after = 0 ) {
	if ( ! current_user_can( 'manage_options' ) || ! current_user_can( 'promote_users' ) ) { return new WP_Error( 'forbidden', 'Nemate dozvolu za promenu uloga korisnika.' ); }
	nekoko_core_register_oglasivac_role();
	$after = absint( $after );
	$cursor_filter = static function( $query ) use ( $after ) {
		if ( empty( $query->query_vars['nekoko_role_scan'] ) ) { return; }
		global $wpdb;
		$query->query_where .= $wpdb->prepare( " AND {$wpdb->users}.ID > %d", $after );
	};
	add_action( 'pre_user_query', $cursor_filter );
	try { $ids = get_users( array( 'nekoko_role_scan' => true, 'number' => 100, 'orderby' => 'ID', 'order' => 'ASC', 'fields' => 'ID', 'count_total' => false ) ); }
	finally { remove_action( 'pre_user_query', $cursor_filter ); }
	$changed = 0;
	foreach ( $ids as $id ) {
		$user = get_userdata( $id );
		if ( $user && count( $user->roles ) === 1 && in_array( 'subscriber', $user->roles, true ) && current_user_can( 'promote_user', $id ) ) {
			$user->set_role( 'oglasivac' );
			++$changed;
		}
	}
	return array( 'checked' => count( $ids ), 'changed' => $changed, 'cursor' => $ids ? (int) end( $ids ) : $after, 'done' => count( $ids ) < 100 );
}

add_action( 'wp_ajax_nekoko_convert_subscribers', static function() {
	check_ajax_referer( 'nekoko_convert_subscribers', 'nonce' );
	$result = nekoko_core_convert_subscriber_batch( absint( $_POST['cursor'] ?? 0 ) );
	if ( is_wp_error( $result ) ) { wp_send_json_error( array( 'message' => $result->get_error_message() ), 403 ); }
	wp_send_json_success( $result );
} );
