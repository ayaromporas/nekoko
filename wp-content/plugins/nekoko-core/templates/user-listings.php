<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$listings = get_posts( array( 'post_type' => 'oglas', 'post_status' => array( 'publish', 'pending', 'draft', 'reject' ), 'author' => get_current_user_id(), 'numberposts' => -1 ) );
$labels = array( 'publish' => 'Objavljen', 'pending' => 'Čeka odobrenje', 'draft' => 'Čeka odobrenje', 'reject' => 'Odbijen' );
 $success_notice = isset( $_GET['oglas_postavljen'] ) ? get_transient( 'nekoko_listing_success_' . get_current_user_id() ) : '';
if ( isset( $_GET['oglas_postavljen'] ) ) { delete_transient( 'nekoko_listing_success_' . get_current_user_id() ); }
$pending_label = nekoko_core_option( 'nekoko_pending_listing_label', 'Čeka odobrenje' );
$pending_info = nekoko_core_option( 'nekoko_pending_listing_info', 'Vaš oglas će biti javno vidljiv kada ga odobri moderator platforme.' );
?>
<section class="nekoko-dashboard">
	<h2>Moji oglasi</h2>
	<?php if ( $success_notice ) { echo nekoko_core_form_notice( $success_notice ); } ?>
	<div class="nekoko-listings">
		<?php foreach ( $listings as $listing ) :
			$categories = nekoko_core_oglas_categories( $listing->ID );
			$type = nekoko_core_oglas_classification( $listing->ID );
			// Published ads open publicly; unpublished ones open a front-end preview for their owner.
			$public_url = 'publish' === $listing->post_status ? get_permalink( $listing ) : get_preview_post_link( $listing );
			$is_pending = in_array( $listing->post_status, array( 'pending', 'draft' ), true );
		?>
			<article class="nekoko-listing-card">
				<?php if ( $public_url ) : ?><a class="nekoko-listing-card__link" href="<?php echo esc_url( $public_url ); ?>" aria-label="<?php echo esc_attr( $listing->post_title ); ?>"></a><?php endif; ?>
				<div class="nekoko-listing-card__image">
					<?php if ( $is_pending ) : ?><span class="nekoko-listing-card__pending-ribbon"><?php echo esc_html( $pending_label ); ?><button type="button" class="nekoko-listing-card__info" aria-label="Informacija o odobravanju oglasa"><span aria-hidden="true">i</span><span class="nekoko-listing-card__tooltip" role="tooltip"><?php echo esc_html( $pending_info ); ?></span></button></span><?php endif; ?>
					<?php if ( has_post_thumbnail( $listing ) ) : ?>
						<?php echo get_the_post_thumbnail( $listing, 'medium_large', array( 'alt' => '' ) ); ?>
					<?php else : ?><span aria-hidden="true">⌕</span><?php endif; ?>
				</div>
				<div class="nekoko-listing-card__body">
					<p class="nekoko-listing-card__type"><?php echo esc_html( is_array( $type ) ? $type['root']->name : 'Oglas' ); ?></p>
					<h3><?php echo esc_html( $listing->post_title ); ?></h3>
					<p class="nekoko-listing-card__category"><?php echo esc_html( $categories ? $categories[0]->name : 'Bez kategorije' ); ?></p>
					<p class="nekoko-listing-card__meta"><?php echo esc_html( get_the_date( 'j. F Y.', $listing ) ); ?><?php if ( ! $is_pending ) : ?> · <?php echo esc_html( $labels[ $listing->post_status ] ?? $listing->post_status ); ?><?php endif; ?></p>
					<div class="nekoko-listing-card__actions">
						<?php if ( $public_url ) : ?><a href="<?php echo esc_url( $public_url ); ?>">Pogledajte</a><?php endif; ?>
						<a class="nekoko-listing-card__action--edit" href="<?php echo esc_url( add_query_arg( 'oglas_id', $listing->ID, nekoko_core_page_url( 'postavite-oglas' ) ) ); ?>">Izmenite</a>
						<a class="nekoko-listing-card__action--delete" data-nekoko-confirm="Da li želite da premestite oglas u otpad?" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'nekoko_delete_listing', $listing->ID, nekoko_core_page_url( 'moji-oglasi' ) ), 'nekoko_delete_listing_' . $listing->ID ) ); ?>">Obrišite</a>
					</div>
				</div>
			</article>
		<?php endforeach; ?>
		<?php if ( ! $listings ) : ?><p>Još nemate oglase.</p><?php endif; ?>
	</div>
</section>
