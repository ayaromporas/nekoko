<?php
/**
 * Public profile page for a NekoKO member.
 *
 * @package Nekoko
 */

get_header();

$nekoko_profile_user = get_userdata( get_queried_object_id() );
if ( ! $nekoko_profile_user ) {
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	get_template_part( '404' );
	get_footer();
	return;
}

$nekoko_profile_id    = $nekoko_profile_user->ID;
$nekoko_profile_image = absint( get_user_meta( $nekoko_profile_id, 'nekoko_profile_image', true ) );
$nekoko_profile_images = function_exists( 'nekoko_core_profile_image_ids' ) ? nekoko_core_profile_image_ids( $nekoko_profile_id ) : array_filter( array( $nekoko_profile_image ) );
$nekoko_profile_gallery = $nekoko_profile_image ? array_values( array_diff( $nekoko_profile_images, array( $nekoko_profile_image ) ) ) : array();
$nekoko_profile_bio   = get_user_meta( $nekoko_profile_id, 'nekoko_profile_bio', true );
$nekoko_profile_city  = get_user_meta( $nekoko_profile_id, 'nekoko_city', true );
$nekoko_profile_phone = get_user_meta( $nekoko_profile_id, 'nekoko_phone', true );
$nekoko_show_email    = '1' === get_user_meta( $nekoko_profile_id, 'nekoko_show_email', true );
$nekoko_show_phone    = '1' === get_user_meta( $nekoko_profile_id, 'nekoko_show_phone', true );
$nekoko_show_city     = '1' === get_user_meta( $nekoko_profile_id, 'nekoko_show_city', true );
$nekoko_review_summary = function_exists( 'nekoko_core_review_summary' ) ? nekoko_core_review_summary( $nekoko_profile_id ) : array( 'count' => 0, 'average' => 0 );
$nekoko_confirmed_collaborations = function_exists( 'nekoko_core_confirmed_collaboration_count' ) ? nekoko_core_confirmed_collaboration_count( $nekoko_profile_id ) : 0;
$nekoko_user_reviews = function_exists( 'nekoko_core_user_reviews' ) ? nekoko_core_user_reviews( $nekoko_profile_id, 6 ) : array();
$nekoko_email_verified = function_exists( 'nekoko_core_is_verified_user' ) && nekoko_core_is_verified_user( $nekoko_profile_id );
$nekoko_phone_verified = '1' === get_user_meta( $nekoko_profile_id, 'nekoko_phone_verified', true );
$nekoko_profile_notice = is_user_logged_in() && function_exists( 'nekoko_core_take_collaboration_notice' ) ? nekoko_core_take_collaboration_notice() : false;
$nekoko_can_edit_profile = get_current_user_id() === (int) $nekoko_profile_id || current_user_can( 'manage_options' );
$nekoko_profile_edit_url = get_current_user_id() === (int) $nekoko_profile_id ? nekoko_core_page_url( 'moj-profil' ) : add_query_arg( 'nekoko_edit_user', $nekoko_profile_id, nekoko_core_page_url( 'moj-profil' ) );
$nekoko_collaboration_listings = get_posts( array( 'post_type' => 'oglas', 'post_status' => 'publish', 'author' => $nekoko_profile_id, 'numberposts' => -1, 'no_found_rows' => true ) );
$nekoko_can_request_collaboration = is_user_logged_in() && get_current_user_id() !== (int) $nekoko_profile_id && ! empty( $nekoko_collaboration_listings );
$nekoko_listings      = new WP_Query(
	array(
		'post_type'      => 'oglas',
		'post_status'    => 'publish',
		'author'         => $nekoko_profile_id,
		'posts_per_page' => 12,
	)
);
?>
<main id="primary" class="site-main">
	<div class="site-content site-content--full">
		<section class="content-area nekoko-public-profile" aria-labelledby="nekoko-public-profile-title">
			<header class="nekoko-public-profile__header">
				<?php if ( $nekoko_profile_image ) : ?>
					<?php echo wp_get_attachment_image( $nekoko_profile_image, 'medium', false, array( 'class' => 'nekoko-public-profile__image', 'alt' => '' ) ); ?>
				<?php endif; ?>
				<div class="nekoko-public-profile__identity">
					<h1 id="nekoko-public-profile-title"><?php echo esc_html( $nekoko_profile_user->display_name ); ?></h1>
					<div class="nekoko-public-profile__metrics"><p class="nekoko-public-profile__reviews"><span aria-hidden="true">★</span> <strong><?php echo esc_html( $nekoko_review_summary['count'] ? number_format_i18n( $nekoko_review_summary['average'], 1 ) : '—' ); ?></strong> <span>(<?php echo esc_html( $nekoko_review_summary['count'] ); ?> recenzija)</span></p><p class="nekoko-public-profile__collaborations"><span aria-hidden="true">✓</span> <strong><?php echo esc_html( $nekoko_confirmed_collaborations ); ?></strong> potvrđenih saradnji</p></div>
					<?php if ( $nekoko_show_city && $nekoko_profile_city ) : ?><p class="nekoko-public-profile__city"><svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path d="M20 10.2c0 5.6-8 10.3-8 10.3S4 15.8 4 10.2a8 8 0 1 1 16 0Z"></path><circle cx="12" cy="10" r="2.6"></circle></svg><span><?php echo esc_html( $nekoko_profile_city ); ?></span></p><?php endif; ?>
					<?php if ( ( $nekoko_show_email && $nekoko_profile_user->user_email ) || ( $nekoko_show_phone && $nekoko_profile_phone ) ) : ?>
						<div class="nekoko-public-profile__contact">
							<?php if ( $nekoko_show_email && $nekoko_profile_user->user_email ) : ?><a href="mailto:<?php echo esc_attr( antispambot( $nekoko_profile_user->user_email ) ); ?>"><svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="m3 7 9 6 9-6"></path></svg><span><?php echo esc_html( antispambot( $nekoko_profile_user->user_email ) ); ?></span><small class="nekoko-public-profile__verification nekoko-public-profile__verification--<?php echo $nekoko_email_verified ? 'verified' : 'unverified'; ?>"><?php echo $nekoko_email_verified ? 'Potvrđen' : 'Nije potvrđen'; ?></small></a><?php endif; ?>
							<?php if ( $nekoko_show_phone && $nekoko_profile_phone ) : ?><a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $nekoko_profile_phone ) ); ?>"><svg aria-hidden="true" viewBox="0 0 24 24" focusable="false"><path d="M6.6 3.8 9.4 3l1.7 4.5-1.8 1.3a15 15 0 0 0 6 6l1.3-1.8 4.5 1.7-.8 2.8a2.8 2.8 0 0 1-3 2C10.6 19 5 13.4 4.2 6.7a2.8 2.8 0 0 1 2.4-2.9Z"></path></svg><span><?php echo esc_html( $nekoko_profile_phone ); ?></span><small class="nekoko-public-profile__verification nekoko-public-profile__verification--<?php echo $nekoko_phone_verified ? 'verified' : 'unverified'; ?>"><?php echo $nekoko_phone_verified ? 'Potvrđen' : 'Nije potvrđen'; ?></small></a><?php endif; ?>
						</div>
					<?php endif; ?>
					<?php if ( $nekoko_profile_gallery && $nekoko_profile_bio ) : ?><p class="nekoko-public-profile__header-bio"><?php echo nl2br( esc_html( $nekoko_profile_bio ) ); ?></p><?php endif; ?>
					<?php if ( $nekoko_can_edit_profile ) : ?><a class="nekoko-public-profile__edit" href="<?php echo esc_url( $nekoko_profile_edit_url ); ?>">Izmenite profil</a><?php endif; ?>
				</div>
				<?php if ( $nekoko_profile_gallery ) : ?>
					<div class="nekoko-public-profile__gallery" aria-label="Galerija fotografija korisnika">
						<?php foreach ( $nekoko_profile_gallery as $nekoko_gallery_image_id ) : ?>
							<button class="nekoko-public-profile__gallery-button" type="button" data-profile-gallery-open data-image-src="<?php echo esc_url( wp_get_attachment_image_url( $nekoko_gallery_image_id, 'large' ) ); ?>" data-image-alt="<?php echo esc_attr( get_post_meta( $nekoko_gallery_image_id, '_wp_attachment_image_alt', true ) ); ?>"><?php echo wp_get_attachment_image( $nekoko_gallery_image_id, 'thumbnail', false, array( 'class' => 'nekoko-public-profile__gallery-image', 'alt' => '' ) ); ?></button>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
				<?php if ( get_current_user_id() !== (int) $nekoko_profile_id ) : ?><div class="nekoko-public-profile__actions"><a class="nekoko-public-profile__message" href="<?php echo esc_url( nekoko_core_conversation_url( $nekoko_profile_id ) ); ?>">Pošalji poruku</a><?php if ( $nekoko_can_request_collaboration ) : ?><button class="nekoko-public-profile__collaboration-button" type="button" data-profile-collaboration-open>Potvrdi saradnju</button><?php endif; ?></div><?php endif; ?>
			</header>
			<?php if ( $nekoko_profile_notice ) : ?><p class="nekoko-public-profile__notice nekoko-public-profile__notice--<?php echo esc_attr( $nekoko_profile_notice['type'] ); ?>"><?php echo esc_html( $nekoko_profile_notice['message'] ); ?></p><?php endif; ?>

			<?php if ( ! $nekoko_profile_gallery && $nekoko_profile_bio ) : ?>
				<div class="nekoko-public-profile__details">
					<?php if ( ! $nekoko_profile_gallery && $nekoko_profile_bio ) : ?><p><?php echo nl2br( esc_html( $nekoko_profile_bio ) ); ?></p><?php endif; ?>
				</div>
			<?php endif; ?>

			<section class="nekoko-public-profile__review-list" aria-labelledby="nekoko-public-profile-reviews-title">
				<h2 id="nekoko-public-profile-reviews-title">Recenzije <span>(<?php echo esc_html( $nekoko_review_summary['count'] ); ?>)</span></h2>
				<?php if ( $nekoko_user_reviews ) : ?>
					<div class="nekoko-public-profile__review-items">
						<?php foreach ( $nekoko_user_reviews as $nekoko_review ) : $nekoko_reviewer_image = absint( get_user_meta( $nekoko_review->reviewer_id, 'nekoko_profile_image', true ) ); ?>
							<article class="nekoko-public-profile__review">
								<?php if ( $nekoko_reviewer_image ) : ?><?php echo wp_get_attachment_image( $nekoko_reviewer_image, 'thumbnail', false, array( 'class' => 'nekoko-public-profile__review-avatar', 'alt' => '' ) ); ?><?php else : ?><?php echo get_avatar( $nekoko_review->reviewer_id, 64, '', '', array( 'class' => 'nekoko-public-profile__review-avatar' ) ); ?><?php endif; ?>
								<div class="nekoko-public-profile__review-body"><div class="nekoko-public-profile__review-heading"><strong><?php echo esc_html( $nekoko_review->display_name ); ?></strong><span class="nekoko-public-profile__review-stars" aria-label="Ocena <?php echo esc_attr( $nekoko_review->rating ); ?> od 5"><?php echo esc_html( str_repeat( '★', (int) $nekoko_review->rating ) . str_repeat( '☆', 5 - (int) $nekoko_review->rating ) ); ?></span><time datetime="<?php echo esc_attr( get_date_from_gmt( $nekoko_review->created_at, 'c' ) ); ?>"><?php echo esc_html( date_i18n( 'j. F Y.', strtotime( $nekoko_review->created_at ) ) ); ?></time></div><p class="nekoko-public-profile__review-listing">Za oglas: <?php echo esc_html( $nekoko_review->post_title ); ?></p><p><?php echo nl2br( esc_html( $nekoko_review->review_text ) ); ?></p></div>
							</article>
						<?php endforeach; ?>
					</div>
				<?php else : ?>
					<p class="nekoko-public-profile__review-empty">Korisnik još nema recenzija.</p>
				<?php endif; ?>
			</section>

			<section class="nekoko-public-profile__listings" aria-labelledby="nekoko-public-profile-listings">
				<h2 id="nekoko-public-profile-listings">Objavljeni oglasi</h2>
				<?php if ( $nekoko_listings->have_posts() ) : ?>
					<div class="nekoko-public-profile__listing-grid">
						<?php while ( $nekoko_listings->have_posts() ) : $nekoko_listings->the_post(); $nekoko_listing_id = get_the_ID(); $nekoko_collaboration = is_user_logged_in() && function_exists( 'nekoko_core_collaboration' ) ? nekoko_core_collaboration( $nekoko_listing_id, get_current_user_id(), $nekoko_profile_id ) : false; ?>
							<article class="nekoko-public-profile__listing">
								<?php if ( has_post_thumbnail() ) : ?><a href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'medium' ); ?></a><?php endif; ?>
								<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
								<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 20 ) ); ?></p>
								<div class="nekoko-public-profile__listing-actions">
								<?php if ( get_current_user_id() && get_current_user_id() !== $nekoko_profile_id ) : ?>
									<?php if ( $nekoko_collaboration ) : $nekoko_collaboration_confirmed = 'confirmed' === $nekoko_collaboration->status; ?><p class="nekoko-public-profile__collaboration-status nekoko-public-profile__collaboration-status--<?php echo $nekoko_collaboration_confirmed ? 'confirmed' : 'pending'; ?>"><?php echo esc_html( $nekoko_collaboration_confirmed ? 'Saradnja je potvrđena' : 'Čeka se potvrda saradnje' ); ?><?php if ( ! $nekoko_collaboration_confirmed ) : ?><span class="nekoko-public-profile__collaboration-info" tabindex="0" role="img" aria-label="Informacija: zahtev je poslat pružaocu usluge i čeka njegovu potvrdu." title="Zahtev je poslat pružaocu usluge i čeka njegovu potvrdu.">i</span><?php endif; ?></p><?php else : ?>
										<form class="nekoko-public-profile__collaboration-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
										<?php wp_nonce_field( 'nekoko_request_collaboration_' . $nekoko_listing_id ); ?><input type="hidden" name="action" value="nekoko_request_collaboration"><input type="hidden" name="listing_id" value="<?php echo esc_attr( $nekoko_listing_id ); ?>"><input type="hidden" name="requester_id" value="<?php echo esc_attr( get_current_user_id() ); ?>"><input type="hidden" name="provider_id" value="<?php echo esc_attr( $nekoko_profile_id ); ?>"><input type="hidden" name="profile_id" value="<?php echo esc_attr( $nekoko_profile_id ); ?>">
											<button type="submit">Potvrdi saradnju</button>
										</form>
									<?php endif; ?>
								<?php elseif ( ! is_user_logged_in() ) : ?><p class="nekoko-public-profile__collaboration-login"><a href="<?php echo esc_url( wp_login_url( get_author_posts_url( $nekoko_profile_id ) ) ); ?>">Prijavite se da biste potvrdili saradnju</a></p><?php endif; ?>
								<a class="nekoko-text-link" href="<?php the_permalink(); ?>">Pogledaj oglas →</a>
								</div>
							</article>
						<?php endwhile; ?>
					</div>
				<?php else : ?>
					<p>Trenutno nema objavljenih oglasa.</p>
				<?php endif; ?>
			</section>
		</section>
			<?php if ( $nekoko_profile_gallery ) : ?>
				<dialog class="nekoko-public-profile__gallery-dialog" data-profile-gallery-dialog aria-label="Pregled fotografije profila">
					<div class="nekoko-public-profile__gallery-dialog-box">
						<button class="nekoko-public-profile__gallery-dialog-close" type="button" data-dialog-close aria-label="Zatvori">×</button>
						<img src="" alt="" data-profile-gallery-dialog-image>
					</div>
				</dialog>
			<?php endif; ?>
			<?php if ( $nekoko_can_request_collaboration ) : ?>
				<dialog class="nekoko-public-profile__collaboration-dialog" data-profile-collaboration-dialog aria-labelledby="profile-collaboration-title">
					<div class="nekoko-public-profile__collaboration-dialog-box">
						<button class="nekoko-public-profile__collaboration-dialog-close" type="button" data-dialog-close aria-label="Zatvori">×</button>
						<h2 id="profile-collaboration-title">Potvrdi saradnju</h2>
						<p>Izaberite oglas na kome ste sarađivali sa <?php echo esc_html( $nekoko_profile_user->display_name ); ?>. Autor će dobiti zahtev za potvrdu.</p>
						<div class="nekoko-public-profile__collaboration-dialog-list">
							<?php foreach ( $nekoko_collaboration_listings as $nekoko_collaboration_listing ) : ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
									<?php wp_nonce_field( 'nekoko_request_collaboration_' . $nekoko_collaboration_listing->ID ); ?><input type="hidden" name="action" value="nekoko_request_collaboration"><input type="hidden" name="listing_id" value="<?php echo esc_attr( $nekoko_collaboration_listing->ID ); ?>"><input type="hidden" name="requester_id" value="<?php echo esc_attr( get_current_user_id() ); ?>"><input type="hidden" name="provider_id" value="<?php echo esc_attr( $nekoko_profile_id ); ?>"><input type="hidden" name="profile_id" value="<?php echo esc_attr( $nekoko_profile_id ); ?>">
									<button type="submit"><?php echo esc_html( $nekoko_collaboration_listing->post_title ); ?></button>
								</form>
							<?php endforeach; ?>
						</div>
					</div>
				</dialog>
			<?php endif; ?>
	</div>
</main>
<?php
wp_reset_postdata();
get_footer();
