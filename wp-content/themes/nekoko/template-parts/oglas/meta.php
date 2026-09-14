<?php
/** Shared listing facts for cards and single pages. @package Nekoko */
?>
<dl class="entry-meta">
	<dt>Tip</dt>
	<dd><?php
		$nekoko_classification = function_exists( 'nekoko_core_oglas_classification' ) ? nekoko_core_oglas_classification( get_the_ID() ) : null;
		if ( $nekoko_classification && ! is_wp_error( $nekoko_classification ) ) {
			$nekoko_root = $nekoko_classification['root'];
			echo '<a href="' . esc_url( get_term_link( $nekoko_root ) ) . '">' . esc_html( $nekoko_root->name ) . '</a>';
		} else { echo 'Nije izabrano'; }
	?></dd>
	<dt>Kategorije</dt>
	<dd><?php
		$nekoko_links = array();
		if ( function_exists( 'nekoko_core_oglas_categories' ) ) {
			foreach ( nekoko_core_oglas_categories( get_the_ID() ) as $nekoko_category ) {
				$nekoko_links[] = '<a href="' . esc_url( get_term_link( $nekoko_category ) ) . '">' . esc_html( nekoko_core_category_path( $nekoko_category->term_id ) ) . '</a>';
			}
		}
		echo $nekoko_links ? implode( ', ', $nekoko_links ) : 'Nije navedeno';
	?></dd>
	<dt>Tagovi</dt>
	<dd><?php
		$nekoko_tags = get_the_term_list( get_the_ID(), 'oglas_tag', '', ', ', '' );
		echo $nekoko_tags && ! is_wp_error( $nekoko_tags ) ? wp_kses_post( $nekoko_tags ) : 'Nije navedeno';
	?></dd>
	<dt>Autor</dt>
	<dd><a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>"><?php echo esc_html( get_the_author() ); ?></a></dd>
	<dt>Datum objave</dt>
	<dd><time datetime="<?php echo esc_attr( get_the_date( DATE_W3C ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time></dd>
</dl>
