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
$nekoko_profile_bio   = get_user_meta( $nekoko_profile_id, 'nekoko_profile_bio', true );
$nekoko_profile_city  = get_user_meta( $nekoko_profile_id, 'nekoko_city', true );
$nekoko_profile_phone = get_user_meta( $nekoko_profile_id, 'nekoko_phone', true );
$nekoko_show_phone    = '1' === get_user_meta( $nekoko_profile_id, 'nekoko_show_phone', true );
$nekoko_show_city     = '1' === get_user_meta( $nekoko_profile_id, 'nekoko_show_city', true );
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
				<div>
					<p class="nekoko-public-profile__eyebrow">NekoKO profil</p>
					<h1 id="nekoko-public-profile-title"><?php echo esc_html( $nekoko_profile_user->display_name ); ?></h1>
					<?php if ( $nekoko_show_city && $nekoko_profile_city ) : ?><p class="nekoko-public-profile__city"><?php echo esc_html( $nekoko_profile_city ); ?></p><?php endif; ?>
				</div>
			</header>

			<?php if ( $nekoko_profile_bio || ( $nekoko_show_phone && $nekoko_profile_phone ) ) : ?>
				<div class="nekoko-public-profile__details">
					<?php if ( $nekoko_profile_bio ) : ?><p><?php echo nl2br( esc_html( $nekoko_profile_bio ) ); ?></p><?php endif; ?>
					<?php if ( $nekoko_show_phone && $nekoko_profile_phone ) : ?><p><strong>Telefon:</strong> <a href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $nekoko_profile_phone ) ); ?>"><?php echo esc_html( $nekoko_profile_phone ); ?></a></p><?php endif; ?>
				</div>
			<?php endif; ?>

			<section class="nekoko-public-profile__listings" aria-labelledby="nekoko-public-profile-listings">
				<h2 id="nekoko-public-profile-listings">Objavljeni oglasi</h2>
				<?php if ( $nekoko_listings->have_posts() ) : ?>
					<div class="nekoko-public-profile__listing-grid">
						<?php while ( $nekoko_listings->have_posts() ) : $nekoko_listings->the_post(); ?>
							<article class="nekoko-public-profile__listing">
								<?php if ( has_post_thumbnail() ) : ?><a href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'medium' ); ?></a><?php endif; ?>
								<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
								<p><?php echo esc_html( wp_trim_words( get_the_excerpt(), 20 ) ); ?></p>
								<a class="nekoko-text-link" href="<?php the_permalink(); ?>">Pogledaj oglas →</a>
							</article>
						<?php endwhile; ?>
					</div>
				<?php else : ?>
					<p>Trenutno nema objavljenih oglasa.</p>
				<?php endif; ?>
			</section>
		</section>
	</div>
</main>
<?php
wp_reset_postdata();
get_footer();
