<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<section class="nekoko-saved-listings">
	<?php if ( $listings ) : ?><div class="nekoko-saved-listings__grid">
		<?php foreach ( $listings as $listing ) :
			$id = $listing->ID;
			$group = function_exists( 'nekoko_core_advanced_search_card_categories' ) ? nekoko_core_advanced_search_card_categories( $id ) : array( 'root' => false, 'categories' => array(), 'subcategories' => array() );
			$root = $group['root'];
			$demand = $root && 'potraznja' === $root->slug;
			$category_names = array_map( static function( $category ) { return $category->name; }, $group['categories'] );
			$location = get_post_meta( $id, 'nekoko_listing_location', true );
			$duration = get_post_meta( $id, 'nekoko_listing_duration', true );
			$duration_na = '1' === get_post_meta( $id, 'nekoko_listing_duration_na', true );
			$agreed = '1' === get_post_meta( $id, 'nekoko_listing_price_agreed', true );
			$price = get_post_meta( $id, 'nekoko_listing_price', true );
			$excerpt = function_exists( 'nekoko_core_listing_excerpt' ) ? nekoko_core_listing_excerpt( $id ) : $listing->post_excerpt;
		?>
			<article class="nekoko-saved-listings__card">
				<a class="nekoko-saved-listings__card-link" href="<?php echo esc_url( get_permalink( $listing ) ); ?>" aria-label="<?php echo esc_attr( $listing->post_title ); ?>"></a>
				<div class="nekoko-saved-listings__image">
					<?php if ( has_post_thumbnail( $listing ) ) { echo get_the_post_thumbnail( $listing, 'medium_large' ); } else { ?><span aria-hidden="true">⌕</span><?php } ?>
					<?php if ( $root ) : ?><span class="nekoko-saved-listings__ribbon<?php echo $demand ? ' nekoko-saved-listings__ribbon--demand' : ''; ?>"><?php echo esc_html( $root->name ); ?></span><?php endif; ?>
				</div>
				<div class="nekoko-saved-listings__body">
					<?php if ( $category_names ) : ?><p class="nekoko-saved-listings__category"><?php echo esc_html( implode( ' · ', $category_names ) ); ?></p><?php endif; ?>
					<?php if ( $group['subcategories'] ) : ?><div class="nekoko-saved-listings__pills"><?php foreach ( $group['subcategories'] as $subcategory ) : ?><span class="nekoko-saved-listings__pill<?php echo $demand ? ' nekoko-saved-listings__pill--demand' : ''; ?>"><?php echo esc_html( $subcategory->name ); ?></span><?php endforeach; ?></div><?php endif; ?>
					<h2><?php echo esc_html( $listing->post_title ); ?></h2>
					<?php if ( $excerpt ) : ?><p class="nekoko-saved-listings__excerpt"><?php echo esc_html( $excerpt ); ?></p><?php endif; ?>
					<p class="nekoko-saved-listings__meta"><?php echo esc_html( $location ?: 'Lokacija nije navedena' ); ?></p>
					<strong><?php echo esc_html( $demand ? ( $duration_na ? 'Trajanje nije primenljivo' : ( $duration ? 'Trajanje ' . $duration : 'Trajanje po dogovoru' ) ) : ( $agreed || '' === $price ? 'Po dogovoru' : number_format_i18n( (float) $price, 0 ) . ' RSD' ) ); ?></strong>
					<time datetime="<?php echo esc_attr( get_the_date( DATE_W3C, $listing ) ); ?>">Datum objave: <?php echo esc_html( get_the_date( 'j. F Y.', $listing ) ); ?></time>
				</div>
			</article>
		<?php endforeach; ?>
	</div><?php else : ?><div class="nekoko-saved-listings__empty"><h2>Još nemate sačuvane oglase.</h2><p>Kada sačuvate oglas, on će se pojaviti ovde.</p><a href="<?php echo esc_url( nekoko_core_page_url( 'napredna-pretraga' ) ); ?>">Istražite oglase</a></div><?php endif; ?>
</section>
