<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** One message per completed listing action; reasons are supplied after being saved. */
function nekoko_core_send_listing_email( $post_id, $event, $reason = '' ) {
	$post = get_post( $post_id );
	if ( ! $post || 'oglas' !== $post->post_type ) { return false; }
	$owner = get_userdata( (int) $post->post_author );
	if ( ! $owner || ! is_email( $owner->user_email ) ) { return false; }
	$title = esc_html( $post->post_title );
	$url = nekoko_core_page_url( 'moji-oglasi' );
	$link_label = 'Pogledajte svoje oglase';
	switch ( $event ) {
		case 'submitted':
			$subject = 'Hvala što ste postavili oglas – čeka odobrenje';
			$content = '<p>Hvala što ste postavili oglas „' . $title . '“ na NekoKO!</p><p>Vaš oglas je uspešno primljen i čeka pregled administratora. Još nije javno vidljiv. Kada bude odobren i objavljen, dobićete email potvrdu sa linkom do oglasa.</p>';
			break;
		case 'updated':
			$subject = 'Izmene oglasa su sačuvane – čeka ponovno odobrenje';
			$content = '<p>Izmene vašeg oglasa „' . $title . '“ uspešno su sačuvane.</p><p>Oglas je poslat na ponovno odobrenje i do završetka pregleda nije javno vidljiv. Kada administrator odobri i objavi oglas, poslaćemo vam email potvrdu sa linkom.</p>';
			break;
		case 'published':
			$subject = 'Vaš oglas je odobren i objavljen';
			$content = '<p>Vaš oglas „' . $title . '“ odobren je i objavljen na NekoKO sajtu. Sada je javno dostupan i drugi korisnici mogu da ga pogledaju i kontaktiraju vas.</p>';
			$url = get_permalink( $post );
			$link_label = 'Pogledajte svoj oglas';
			break;
		case 'rejected':
			$subject = 'Obaveštenje o vašem oglasu – oglas je odbijen';
			$content = '<p>Vaš oglas „' . $title . '“ pregledan je i trenutno nije odobren za objavljivanje. Oglas nije javno vidljiv.</p><p><strong>Razlog odbijanja:</strong><br>' . nl2br( esc_html( $reason ) ) . '</p><p>Oglas možete izmeniti u skladu sa navedenim razlogom i ponovo ga poslati na odobrenje.</p><p>Ukoliko mislite da je došlo do greške, pišite nam na <a href="mailto:info@nekoko.rs">info@nekoko.rs</a>. U poruci navedite naslov i broj oglasa: ' . absint( $post->ID ) . '.</p>';
			$url = add_query_arg( 'oglas_id', $post->ID, nekoko_core_page_url( 'postavite-oglas' ) );
			$link_label = 'Izmenite i ponovo pošaljite oglas';
			break;
		default:
			return false;
	}
	$body = '<div style="background:#fff;color:#17233c;padding:24px;font-family:Arial,sans-serif;line-height:1.6">';
	$body .= function_exists( 'nekoko_core_email_logo_html' ) ? nekoko_core_email_logo_html() : '';
	$body .= '<p>Zdravo ' . esc_html( $owner->first_name ?: $owner->display_name ) . ',</p>' . $content;
	$body .= '<p><a href="' . esc_url( $url ) . '" style="color:#004682;font-weight:bold">' . esc_html( $link_label ) . '</a></p><p style="font-size:13px">Ako link ne možete da otvorite, kopirajte ovu adresu u pregledač:<br><a href="' . esc_url( $url ) . '">' . esc_html( $url ) . '</a></p><p>Hvala što ste deo NekoKO zajednice!<br>Vaš NekoKO tim</p></div>';
	return wp_mail( $owner->user_email, $subject . ' | NekoKO', $body, array( 'Content-Type: text/html; charset=UTF-8', 'Reply-To: NekoKO <info@nekoko.rs>' ) );
}

/** Frontend forms send only after the gallery and all other fields are saved. */
function nekoko_core_listing_admin_email( $post_id, $post, $update, $post_before ) {
	if ( 'oglas' !== $post->post_type || wp_is_post_revision( $post_id ) || ! empty( $_POST['nekoko_listing_form'] ) ) { return; }
	$previous = $post_before ? $post_before->post_status : 'new';
	if ( $previous === $post->post_status ) { return; }
	if ( 'publish' === $post->post_status ) {
		nekoko_core_send_listing_email( $post_id, 'published' );
	} elseif ( 'pending' === $post->post_status ) {
		nekoko_core_send_listing_email( $post_id, in_array( $previous, array( 'publish', 'reject' ), true ) ? 'updated' : 'submitted' );
	}
}
add_action( 'wp_after_insert_post', 'nekoko_core_listing_admin_email', 20, 4 );
