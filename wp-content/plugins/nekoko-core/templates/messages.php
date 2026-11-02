<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
global $wpdb;
$table = $wpdb->prefix . 'nekoko_messages';
$me = get_current_user_id();
$peer_id = is_page( 'poruke-sa-clanom' ) ? absint( $_GET['clan'] ?? 0 ) : 0;
$peer = $peer_id && $peer_id !== $me ? get_userdata( $peer_id ) : false;
$conversation_page = max( 1, absint( $_GET['razgovori'] ?? 1 ) );
$card_page = max( 1, absint( $_GET['kartice'] ?? 1 ) );
$requested_listing = absint( $_GET['oglas_id'] ?? 0 );
$where = $wpdb->prepare( '(sender_id=%d OR recipient_id=%d)', $me, $me );
$group = "SELECT CASE WHEN sender_id=$me THEN recipient_id ELSE sender_id END AS member_id, MAX(id) AS latest_id, SUM(CASE WHEN recipient_id=$me AND read_at IS NULL THEN 1 ELSE 0 END) AS unread FROM $table WHERE $where GROUP BY member_id";
$conversation_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM ($group) AS conversations" );
$conversation_page = min( $conversation_page, max( 1, (int) ceil( $conversation_count / 15 ) ) );
$conversations = $wpdb->get_results( $group . ' ORDER BY latest_id DESC LIMIT 15 OFFSET ' . ( ( $conversation_page - 1 ) * 15 ) );
$cards = array();
$card_count = 0;
if ( $peer ) {
	$pair = $wpdb->prepare( '((sender_id=%d AND recipient_id=%d) OR (sender_id=%d AND recipient_id=%d))', $me, $peer_id, $peer_id, $me );
	$card_group = "SELECT listing_id, MAX(id) AS latest_id FROM $table WHERE $pair GROUP BY listing_id";
	$card_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM ($card_group) AS threads" );
	// Land on the page containing the selected listing, retaining chronological ordering.
	if ( $requested_listing && ! isset( $_GET['kartice'] ) ) {
		$latest = (int) $wpdb->get_var( $wpdb->prepare( "SELECT MAX(id) FROM $table WHERE $pair AND listing_id=%d", $requested_listing ) );
		if ( $latest ) {
			$ahead = (int) $wpdb->get_var( "SELECT COUNT(*) FROM ($card_group) AS threads WHERE latest_id > $latest" );
			$card_page = (int) floor( $ahead / 10 ) + 1;
		}
	}
	$card_page = min( $card_page, max( 1, (int) ceil( $card_count / 10 ) ) );
	$cards = $wpdb->get_results( $card_group . ' ORDER BY latest_id DESC LIMIT 10 OFFSET ' . ( ( $card_page - 1 ) * 10 ) );
	$draft_listing = ! empty( $GLOBALS['nekoko_message_error'] ) ? absint( $_POST['listing_id'] ?? 0 ) : $requested_listing;
	$draft_exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $table WHERE $pair AND listing_id=%d LIMIT 1", $draft_listing ) );
	if ( ! $draft_exists && nekoko_core_message_listing_allowed( $draft_listing, $me, $peer_id ) && ( $requested_listing || ! $cards || isset( $GLOBALS['nekoko_message_error'] ) ) ) {
		array_unshift( $cards, (object) array( 'listing_id' => $draft_listing, 'latest_id' => 0 ) );
	}
	foreach ( $cards as $card ) {
		$card->parameter = 'istorija_' . $card->listing_id;
		$filter = $wpdb->prepare( "$pair AND listing_id=%d", $card->listing_id );
		$card->count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $table WHERE $filter" );
		$card->page = min( max( 1, absint( $_GET[ $card->parameter ] ?? 1 ) ), max( 1, (int) ceil( $card->count / 30 ) ) );
		$card->messages = array_reverse( $wpdb->get_results( "SELECT * FROM $table WHERE $filter ORDER BY id DESC LIMIT 30 OFFSET " . ( ( $card->page - 1 ) * 30 ) ) );
		$ids = array_map( 'absint', wp_list_pluck( $card->messages, 'id' ) );
		if ( $ids ) { $wpdb->query( $wpdb->prepare( "UPDATE $table SET read_at=%s WHERE recipient_id=%d AND read_at IS NULL AND id IN (" . implode( ',', $ids ) . ')', current_time( 'mysql', true ), $me ) ); }
	}
}
$pagination = static function( $parameter, $current, $total ) {
	if ( $total < 2 ) { return; }
	echo '<nav class="nekoko-messages__pagination" aria-label="Paginacija">';
	echo wp_kses_post( paginate_links( array( 'base' => add_query_arg( $parameter, '%#%' ), 'format' => '', 'current' => $current, 'total' => $total, 'prev_text' => '← Prethodna', 'next_text' => 'Sledeća →' ) ) );
	echo '</nav>';
};
get_header();
?>
<main id="primary" class="site-main nekoko-messages">
<h1><?php echo esc_html( $peer ? 'Poruke sa članom ' . $peer->display_name : 'Sve poruke' ); ?></h1>
<div class="nekoko-messages__layout">
<aside class="nekoko-messages__contacts" aria-label="Razgovori">
<h2><a href="<?php echo esc_url( home_url( '/sve-poruke/' ) ); ?>">Sve poruke</a></h2>
<?php if ( ! $conversations ) : ?><p>Još nemate poruka.</p><?php endif; ?>
<?php foreach ( $conversations as $conversation ) : $member = get_userdata( $conversation->member_id ); if ( ! $member ) { continue; } ?>
<a class="nekoko-messages__contact <?php echo (int) $member->ID === $peer_id ? 'is-active' : ''; ?>" href="<?php echo esc_url( nekoko_core_conversation_url( $member->ID ) ); ?>" <?php if ( (int) $member->ID === $peer_id ) { echo 'aria-current="page"'; } ?>><?php echo get_avatar( $member->ID, 40 ); ?><span><?php echo esc_html( $member->display_name ); ?></span><?php if ( $conversation->unread ) : ?><span class="nekoko-unread-dot"><span class="screen-reader-text">Nepročitane poruke</span></span><?php endif; ?></a>
<?php endforeach; ?>
<?php $pagination( 'razgovori', $conversation_page, (int) ceil( $conversation_count / 15 ) ); ?>
</aside>
<section class="nekoko-messages__thread" aria-label="Istorija poruka">
<?php if ( ! $peer ) : ?>
<p><?php echo is_page( 'poruke-sa-clanom' ) ? 'Član nije dostupan. Izaberite razgovor sa liste.' : 'Izaberite člana sa leve strane da biste otvorili razgovor. Novi razgovor možete započeti preko profila člana ili njegovog oglasa.'; ?></p>
<?php else : ?>
<h2><a href="<?php echo esc_url( get_author_posts_url( $peer_id ) ); ?>"><?php echo esc_html( $peer->display_name ); ?></a></h2>
<?php foreach ( $cards as $card ) :
$listing = get_post( $card->listing_id );
$listing_title = $listing ? get_the_title( $listing ) : $wpdb->get_var( $wpdb->prepare( "SELECT listing_title FROM $table WHERE $pair AND listing_id=%d AND listing_title<>'' ORDER BY id DESC LIMIT 1", $card->listing_id ) );
$card_error = isset( $GLOBALS['nekoko_message_error'] ) && absint( $_POST['listing_id'] ?? 0 ) === (int) $card->listing_id;
?>
<article class="nekoko-messages__card" id="razgovor-<?php echo absint( $card->listing_id ); ?>">
<h3><?php if ( $card->listing_id ) : ?>Razgovor u vezi — <?php if ( $listing && 'publish' === $listing->post_status ) : ?><a href="<?php echo esc_url( get_permalink( $listing ) ); ?>"><?php echo esc_html( $listing_title ); ?></a><?php else : ?><?php echo esc_html( $listing_title ?: 'Oglas više nije dostupan' ); ?><?php endif; ?><?php else : ?>Direktan razgovor<?php endif; ?></h3>
<?php if ( ! $card->messages ) : ?><p>Započnite razgovor slanjem prve poruke.</p><?php endif; ?>
<div class="nekoko-messages__history">
<?php foreach ( $card->messages as $message ) : $mine = (int) $message->sender_id === $me; ?>
<article class="nekoko-messages__bubble <?php echo $mine ? 'is-mine' : ''; ?>">
<strong><?php echo esc_html( $mine ? 'Vi' : $peer->display_name ); ?></strong>
<p><?php echo nl2br( esc_html( $message->body ) ); ?></p>
<time datetime="<?php echo esc_attr( str_replace( ' ', 'T', $message->created_at ) . 'Z' ); ?>"><?php echo esc_html( wp_date( 'd.m.Y. H:i', strtotime( $message->created_at . ' UTC' ) ) ); ?></time>
<?php if ( $mine ) : ?><small><?php echo $message->read_at ? 'Pročitano' : 'Poslato'; ?></small><?php endif; ?>
</article>
<?php endforeach; ?>
</div>
<?php $pagination( $card->parameter, $card->page, (int) ceil( $card->count / 30 ) ); ?>
<?php if ( $card_error ) : ?><p role="alert"><?php echo esc_html( $GLOBALS['nekoko_message_error'] ); ?></p><?php endif; ?>
<form method="post" action="<?php echo esc_url( nekoko_core_conversation_url( $peer_id, $card->listing_id ) ); ?>" class="nekoko-messages__compose">
<?php wp_nonce_field( 'nekoko_member_message' ); ?>
<input type="hidden" name="listing_id" value="<?php echo absint( $card->listing_id ); ?>">
<label for="member-message-<?php echo absint( $card->listing_id ); ?>">Vaša poruka</label>
<textarea id="member-message-<?php echo absint( $card->listing_id ); ?>" name="body" rows="4" maxlength="5000" required placeholder="Napišite poruku…"><?php echo $card_error ? esc_textarea( wp_unslash( $_POST['body'] ?? '' ) ) : ''; ?></textarea>
<button type="submit" name="nekoko_member_message" value="1">Pošalji poruku</button>
</form>
</article>
<?php endforeach; ?>
<?php $pagination( 'kartice', $card_page, (int) ceil( $card_count / 10 ) ); ?>
<?php endif; ?>
</section></div></main>
<?php get_footer(); ?>
