<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

$user_id = get_current_user_id();
$notice = function_exists( 'nekoko_core_take_collaboration_notice' ) ? nekoko_core_take_collaboration_notice( $user_id ) : false;
$requests = function_exists( 'nekoko_core_incoming_collaboration_requests' ) ? nekoko_core_incoming_collaboration_requests( $user_id ) : array();
?>
<section class="nekoko-dashboard nekoko-collaboration-requests" aria-labelledby="nekoko-collaboration-requests-title">
	<h2 id="nekoko-collaboration-requests-title">Zahtevi za saradnju</h2>
	<p>Potvrdite samo saradnju koja je zaista realizovana. Nakon potvrde, korisnik može da ostavi recenziju za odgovarajući oglas.</p>
	<?php if ( $notice ) { echo nekoko_core_form_notice( $notice['message'], 'error' === $notice['type'] ? 'error' : 'success' ); } ?>
	<?php if ( $requests ) : ?><div class="nekoko-collaboration-requests__list">
		<?php foreach ( $requests as $collaboration ) : ?>
			<article class="nekoko-collaboration-request">
				<div><strong><?php echo esc_html( $collaboration->display_name ); ?></strong><span>želi da potvrdi saradnju za oglas: <?php if ( $collaboration->post_title ) : ?><a href="<?php echo esc_url( get_permalink( $collaboration->listing_id ) ); ?>"><?php echo esc_html( $collaboration->post_title ); ?></a><?php else : ?>#<?php echo esc_html( $collaboration->listing_id ); ?><?php endif; ?></span></div>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'nekoko_confirm_collaboration_' . $collaboration->id ); ?><input type="hidden" name="action" value="nekoko_confirm_collaboration"><input type="hidden" name="collaboration_id" value="<?php echo esc_attr( $collaboration->id ); ?>">
					<button type="submit">Potvrdi saradnju</button>
				</form>
			</article>
		<?php endforeach; ?>
	</div><?php else : ?><p class="nekoko-collaboration-requests__empty">Trenutno nemate zahteva za saradnju.</p><?php endif; ?>
</section>
