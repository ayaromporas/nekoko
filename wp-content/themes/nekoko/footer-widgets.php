<?php
/** Footer branding widget and local social icons. @package Nekoko */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Resolve icons by an optional social-* menu class, then by the menu label. */
function nekoko_footer_social_icon( $item ) {
	$icons = array(
		'facebook'  => '<path d="M14 22v-9h3l.5-4H14V7c0-1.2.3-2 2-2h2V1.4A25 25 0 0 0 15 1c-3 0-5 1.8-5 5v3H7v4h3v9z"/>',
		'linkedin'  => '<circle cx="4" cy="4" r="2"/><path d="M2 8h4v14H2zm7 0h4v2c1-1.6 2.4-2.4 4.2-2.4C21 7.6 22 10 22 14v8h-4v-8c0-2-.6-3-2.2-3-1.8 0-2.8 1.3-2.8 3v8H9z"/>',
		'instagram' => '<g fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="6"/><circle cx="12" cy="12" r="4.5"/></g><circle cx="18" cy="6" r="1.3"/>',
		'x'         => '<path d="M18.9 2H22l-6.8 7.8L23.2 22h-6.3L12 14.5 5.4 22H2.2l8.3-9.5L.8 2h6.5l4.9 6.5L18.9 2zm-1.1 18h1.7L6.4 4H4.6z"/>',
		'tiktok'    => '<path d="M16.6 1h-3.5v14.2a3 3 0 1 1-2.6-3V8.6a6.5 6.5 0 1 0 6.1 6.5V8.2A9 9 0 0 0 22 10V6.4A5.5 5.5 0 0 1 16.6 1z"/>',
	);
	$key = '';
	foreach ( array_keys( $icons ) as $network ) {
		if ( in_array( 'social-' . $network, (array) $item->classes, true ) ) {
			$key = $network;
			break;
		}
	}
	if ( ! $key ) {
		$key = preg_replace( '/[^a-z0-9]/', '', sanitize_title( wp_strip_all_tags( $item->title ) ) );
		$key = 'twitter' === $key ? 'x' : $key;
	}
	return isset( $icons[ $key ] ) ? '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">' . $icons[ $key ] . '</svg>' : '';
}

/** No fallback menu and no live links for unfinished social destinations. */
function nekoko_footer_social_menu() {
	$locations = get_nav_menu_locations();
	$items = ! empty( $locations['social-menu'] ) ? wp_get_nav_menu_items( $locations['social-menu'] ) : array();
	if ( ! $items ) {
		return;
	}
	?>
	<nav class="footer-social" aria-label="<?php esc_attr_e( 'Društvene mreže', 'nekoko' ); ?>">
		<ul class="footer-social__items">
			<?php foreach ( $items as $item ) :
				$label = wp_strip_all_tags( $item->title );
				$icon = nekoko_footer_social_icon( $item );
				$url = esc_url( trim( (string) $item->url ) );
				$disabled = '' === $url || '#' === $url;
				$new_tab = ! $disabled && '_blank' === $item->target;
				$accessible_label = $label;
				if ( $disabled ) {
					$accessible_label .= ' — ' . __( 'link nije podešen', 'nekoko' );
				} elseif ( $new_tab ) {
					$accessible_label .= ' — ' . __( 'otvara se u novoj kartici', 'nekoko' );
				}
				?>
				<li>
					<?php if ( $disabled ) : ?>
						<span class="footer-social__item" role="link" aria-disabled="true" aria-label="<?php echo esc_attr( $accessible_label ); ?>">
					<?php else : ?>
						<a class="footer-social__item" href="<?php echo $url; ?>" aria-label="<?php echo esc_attr( $accessible_label ); ?>"<?php if ( $new_tab ) : ?> target="_blank" rel="noopener noreferrer"<?php endif; ?>>
					<?php endif; ?>
						<?php
						// Only fixed, local SVG markup is returned; unknown labels stay visible.
						echo $icon ? $icon : esc_html( $label );
						?>
					<?php if ( $disabled ) : ?></span><?php else : ?></a><?php endif; ?>
				</li>
			<?php endforeach; ?>
		</ul>
	</nav>
	<?php
}

/** Editable copyright text; the year token is resolved when the widget renders. */
class Nekoko_Footer_Copyright_Widget extends WP_Widget {
	public function __construct() {
		parent::__construct( 'nekoko_footer_copyright', __( 'Footer dno', 'nekoko' ), array(
			'description'                 => __( 'Copyright tekst sa automatskom godinom.', 'nekoko' ),
			'customize_selective_refresh' => true,
			'show_instance_in_rest'       => true,
		) );
	}

	public static function default_text() {
		return __( '© {godina} NekoKO. Sva prava zadržana.', 'nekoko' );
	}

	public function widget( $args, $instance ) {
		$text = isset( $instance['text'] ) ? (string) $instance['text'] : self::default_text();
		$text = str_replace( '{godina}', wp_date( 'Y' ), $text );
		// Sidebar wrappers are registered by the theme; only plain text is editable.
		echo $args['before_widget'];
		echo '<p class="footer-copyright">' . nl2br( esc_html( $text ) ) . '</p>';
		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$text = isset( $instance['text'] ) ? $instance['text'] : self::default_text();
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'text' ) ); ?>"><?php esc_html_e( 'Copyright tekst', 'nekoko' ); ?></label>
			<textarea class="widefat" rows="3" id="<?php echo esc_attr( $this->get_field_id( 'text' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'text' ) ); ?>"><?php echo esc_textarea( $text ); ?></textarea>
		</p>
		<p class="description"><?php esc_html_e( 'Oznaka {godina} automatski prikazuje tekuću godinu.', 'nekoko' ); ?></p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		return array( 'text' => sanitize_textarea_field( $new_instance['text'] ?? '' ) );
	}
}

class Nekoko_Footer_Brand_Widget extends WP_Widget {
	public function __construct() {
		parent::__construct( 'nekoko_footer_brand', __( 'NekoKO – logo i društvene mreže', 'nekoko' ), array(
			'description'                 => __( 'Logo sajta, kratak opis portala i ikonice iz Social Menu lokacije.', 'nekoko' ),
			'customize_selective_refresh' => true,
			'show_instance_in_rest'       => true,
		) );
	}

	public static function default_description() {
		return __( 'NekoKO je portal za neobične poslove, usluge i prilike koje je teško pronaći na drugim mestima.', 'nekoko' );
	}

	public function widget( $args, $instance ) {
		$description = isset( $instance['description'] ) ? (string) $instance['description'] : self::default_description();
		// Wrappers are provided by the registered sidebar, not user content.
		echo $args['before_widget'];
		?>
		<div class="footer-brand">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<a class="footer-brand__name" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></a>
			<?php endif; ?>
			<?php if ( '' !== trim( $description ) ) : ?>
				<div class="footer-brand__description"><?php echo wpautop( esc_html( $description ) ); ?></div>
			<?php endif; ?>
			<?php nekoko_footer_social_menu(); ?>
		</div>
		<?php
		echo $args['after_widget'];
	}

	public function form( $instance ) {
		$description = isset( $instance['description'] ) ? $instance['description'] : self::default_description();
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'description' ) ); ?>"><?php esc_html_e( 'Kratak opis portala', 'nekoko' ); ?></label>
			<textarea class="widefat" rows="5" id="<?php echo esc_attr( $this->get_field_id( 'description' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'description' ) ); ?>"><?php echo esc_textarea( $description ); ?></textarea>
		</p>
		<?php
	}

	public function update( $new_instance, $old_instance ) {
		return array( 'description' => sanitize_textarea_field( $new_instance['description'] ?? '' ) );
	}
}
