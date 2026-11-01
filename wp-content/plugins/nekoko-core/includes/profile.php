<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function nekoko_core_phone_countries() {
	return array(
		'381' => array( 'flag' => '🇷🇸', 'label' => 'Srbija', 'code' => '+381' ),
		'387' => array( 'flag' => '🇧🇦', 'label' => 'Bosna i Hercegovina', 'code' => '+387' ),
		'385' => array( 'flag' => '🇭🇷', 'label' => 'Hrvatska', 'code' => '+385' ),
		'382' => array( 'flag' => '🇲🇪', 'label' => 'Crna Gora', 'code' => '+382' ),
		'389' => array( 'flag' => '🇲🇰', 'label' => 'Severna Makedonija', 'code' => '+389' ),
		'386' => array( 'flag' => '🇸🇮', 'label' => 'Slovenija', 'code' => '+386' ),
		'43'  => array( 'flag' => '🇦🇹', 'label' => 'Austrija', 'code' => '+43' ),
		'49'  => array( 'flag' => '🇩🇪', 'label' => 'Nemačka', 'code' => '+49' ),
		'41'  => array( 'flag' => '🇨🇭', 'label' => 'Švajcarska', 'code' => '+41' ),
		'1'   => array( 'flag' => '🇺🇸', 'label' => 'SAD i Kanada', 'code' => '+1' ),
	);
}

function nekoko_core_profile_display_name( $format, $user_login, $first_name, $last_name ) {
	$format = in_array( $format, array( 'username', 'full_name', 'first_last_initial' ), true ) ? $format : 'full_name';

	if ( 'username' === $format ) {
		return $user_login;
	}
	if ( 'first_last_initial' === $format ) {
		$initial = $last_name ? mb_substr( $last_name, 0, 1 ) . '.' : '';
		return trim( $first_name . ' ' . $initial );
	}

	return trim( $first_name . ' ' . $last_name );
}

function nekoko_core_profile_image_ids( $user_id ) {
	$primary = absint( get_user_meta( $user_id, 'nekoko_profile_image', true ) );
	$gallery = get_user_meta( $user_id, 'nekoko_profile_gallery', true );
	$gallery = is_array( $gallery ) ? array_map( 'absint', $gallery ) : array();
	if ( $primary ) { array_unshift( $gallery, $primary ); }

	return array_values(
		array_filter(
			array_unique( $gallery ),
			static function( $image_id ) {
				return $image_id && 'attachment' === get_post_type( $image_id ) && wp_attachment_is_image( $image_id );
			}
		)
	);
}

function nekoko_core_profile_submit() {
	if ( empty( $_POST['nekoko_profile_form'] ) ) {
		return;
	}

	nekoko_core_require_login();
	$id = get_current_user_id();
	if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'nekoko_profile_form' ) ) {
		wp_die( 'Nevažeći zahtev.', 403 );
	}

	$errors  = new WP_Error();
	$user    = get_userdata( $id );
	$first   = sanitize_text_field( wp_unslash( $_POST['first_name'] ?? '' ) );
	$last    = sanitize_text_field( wp_unslash( $_POST['last_name'] ?? '' ) );
	$display_format = sanitize_key( wp_unslash( $_POST['nekoko_display_name_format'] ?? 'full_name' ) );
	$display = nekoko_core_profile_display_name( $display_format, $user->user_login, $first, $last );
	$email   = sanitize_email( wp_unslash( $_POST['user_email'] ?? '' ) );
	$country = preg_replace( '/\D+/', '', (string) wp_unslash( $_POST['nekoko_phone_country'] ?? '381' ) );
	$phone   = preg_replace( '/\D+/', '', (string) wp_unslash( $_POST['nekoko_phone'] ?? '' ) );

	if ( ! $first || ! $last || ! $display ) {
		$errors->add( 'required', 'Ime i prezime su obavezni.' );
	}
	if ( ! is_email( $email ) ) {
		$errors->add( 'email', 'Unesite ispravnu email adresu.' );
	} elseif ( email_exists( $email ) && (int) email_exists( $email ) !== $id ) {
		$errors->add( 'email_taken', 'Ovu email adresu već koristi drugi nalog.' );
	}
	if ( $email !== $user->user_email && ! wp_check_password( (string) ( $_POST['current_password'] ?? '' ), $user->user_pass, $id ) ) {
		$errors->add( 'current_password', 'Trenutna lozinka nije ispravna.' );
	}
	if ( $phone && ( ! isset( nekoko_core_phone_countries()[ $country ] ) || ! preg_match( '/^[1-9][0-9]{7,14}$/', $phone ) ) ) {
		$errors->add( 'phone', 'Unesite telefon bez početne nule, samo cifre (na primer 641234567).' );
	}
	if ( $errors->has_errors() ) {
		set_transient( 'nekoko_profile_errors_' . $id, $errors, MINUTE_IN_SECONDS );
		wp_safe_redirect( nekoko_core_page_url( 'moj-profil' ) );
		exit;
	}

	$current_profile_images = nekoko_core_profile_image_ids( $id );
	$requested_removals = array_filter( array_map( 'absint', (array) ( $_POST['nekoko_remove_profile_images'] ?? array() ) ) );
	if ( ! empty( $_POST['nekoko_remove_profile_image'] ) ) {
		$requested_removals[] = absint( get_user_meta( $id, 'nekoko_profile_image', true ) );
	}
	$requested_removals = array_values( array_intersect( $requested_removals, $current_profile_images ) );
	$remaining_profile_images = array_values( array_diff( $current_profile_images, $requested_removals ) );
	$uploaded_names = isset( $_FILES['nekoko_profile_images']['name'] ) ? array_filter( (array) $_FILES['nekoko_profile_images']['name'] ) : array();
	$max_profile_images = 6;
	if ( count( $remaining_profile_images ) + count( $uploaded_names ) > $max_profile_images ) {
		$errors->add( 'profile_images', sprintf( 'Možete imati najviše %d profilnih fotografija.', $max_profile_images ) );
		set_transient( 'nekoko_profile_errors_' . $id, $errors, MINUTE_IN_SECONDS );
		wp_safe_redirect( nekoko_core_page_url( 'moj-profil' ) );
		exit;
	}

	$new_profile_images = nekoko_core_handle_user_images( 'nekoko_profile_images', $id );
	if ( is_wp_error( $new_profile_images ) ) {
		set_transient( 'nekoko_profile_errors_' . $id, $new_profile_images, MINUTE_IN_SECONDS );
		wp_safe_redirect( nekoko_core_page_url( 'moj-profil' ) );
		exit;
	}

	wp_update_user( array( 'ID' => $id, 'first_name' => $first, 'last_name' => $last, 'display_name' => $display ) );
	update_user_meta( $id, 'nekoko_display_name_format', $display_format );
	update_user_meta( $id, 'nekoko_phone', $phone ? '+' . $country . $phone : '' );
	update_user_meta( $id, 'nekoko_phone_country', $country );
	update_user_meta( $id, 'nekoko_city', sanitize_text_field( wp_unslash( $_POST['nekoko_city'] ?? '' ) ) );
	update_user_meta( $id, 'nekoko_profile_bio', sanitize_textarea_field( wp_unslash( $_POST['nekoko_profile_bio'] ?? '' ) ) );
	update_user_meta( $id, 'nekoko_show_email', ! empty( $_POST['nekoko_show_email'] ) ? '1' : '0' );
	update_user_meta( $id, 'nekoko_show_phone', ! empty( $_POST['nekoko_show_phone'] ) ? '1' : '0' );
	update_user_meta( $id, 'nekoko_show_city', ! empty( $_POST['nekoko_show_city'] ) ? '1' : '0' );
	$profile_images = array_values( array_unique( array_merge( $remaining_profile_images, $new_profile_images ) ) );
	$requested_primary = absint( $_POST['nekoko_profile_primary_image'] ?? 0 );
	$current_primary = absint( get_user_meta( $id, 'nekoko_profile_image', true ) );
	if ( $requested_primary && in_array( $requested_primary, $profile_images, true ) ) {
		$primary_profile_image = $requested_primary;
	} elseif ( $current_primary && in_array( $current_primary, $profile_images, true ) ) {
		$primary_profile_image = $current_primary;
	} else {
		$primary_profile_image = $profile_images ? $profile_images[0] : 0;
	}
	if ( $profile_images ) {
		update_user_meta( $id, 'nekoko_profile_gallery', $profile_images );
		update_user_meta( $id, 'nekoko_profile_image', $primary_profile_image );
	} else {
		delete_user_meta( $id, 'nekoko_profile_gallery' );
		delete_user_meta( $id, 'nekoko_profile_image' );
	}

	if ( $email !== $user->user_email ) {
		update_user_meta( $id, 'nekoko_pending_email', $email );
		nekoko_core_issue_verification( $id, 'email_change', $email );
		set_transient( 'nekoko_profile_notice_' . $id, 'Poslali smo link za potvrdu na novu email adresu. Vaša trenutna email adresa ostaje aktivna dok ne potvrdite novu.', MINUTE_IN_SECONDS );
	} else {
		set_transient( 'nekoko_profile_notice_' . $id, 'Profil je uspešno sačuvan.', MINUTE_IN_SECONDS );
	}

	wp_safe_redirect( nekoko_core_page_url( 'moj-profil' ) );
	exit;
}
add_action( 'template_redirect', 'nekoko_core_profile_submit', 8 );

function nekoko_core_profile_password_submit() {
	if ( empty( $_POST['nekoko_profile_password'] ) ) {
		return;
	}

	nekoko_core_require_login();
	$id   = get_current_user_id();
	$user = get_userdata( $id );
	if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'nekoko_profile_password' ) || ! wp_check_password( (string) ( $_POST['current_password'] ?? '' ), $user->user_pass, $id ) ) {
		set_transient( 'nekoko_profile_password_error_' . $id, 'Trenutna lozinka nije ispravna.', MINUTE_IN_SECONDS );
	} elseif ( $_POST['new_password'] !== $_POST['new_password_repeat'] || ! nekoko_core_password_is_strong( $_POST['new_password'] ?? '' ) ) {
		set_transient( 'nekoko_profile_password_error_' . $id, 'Nova lozinka mora imati najmanje 10 karaktera, slovo i broj, a oba unosa moraju biti ista.', MINUTE_IN_SECONDS );
	} else {
		wp_set_password( $_POST['new_password'], $id );
		wp_set_auth_cookie( $id, true );
		set_transient( 'nekoko_profile_notice_' . $id, 'Lozinka je uspešno promenjena.', MINUTE_IN_SECONDS );
	}
	wp_safe_redirect( nekoko_core_page_url( 'moj-profil' ) );
	exit;
}
add_action( 'template_redirect', 'nekoko_core_profile_password_submit', 9 );

function nekoko_core_cancel_email_change() {
	if ( empty( $_GET['nekoko_cancel_email'] ) || ! is_user_logged_in() || ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'nekoko_cancel_email' ) ) {
		return;
	}

	$id = get_current_user_id();
	foreach ( array( 'nekoko_pending_email', 'nekoko_email_change_token_hash', 'nekoko_email_change_expires' ) as $key ) {
		delete_user_meta( $id, $key );
	}
	set_transient( 'nekoko_profile_notice_' . $id, 'Promena email adrese je otkazana.', MINUTE_IN_SECONDS );
	wp_safe_redirect( nekoko_core_page_url( 'moj-profil' ) );
	exit;
}
add_action( 'template_redirect', 'nekoko_core_cancel_email_change', 10 );

function nekoko_core_shortcode_profile() {
	nekoko_core_require_login();
	return nekoko_core_template( 'profile-form' );
}
add_shortcode( 'nekoko_profile_form', 'nekoko_core_shortcode_profile' );

function nekoko_core_account_settings_submit() {
	if ( empty( $_POST['nekoko_account_settings'] ) ) { return; }
	nekoko_core_require_login();
	if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'nekoko_account_settings' ) ) { wp_die( 'Nevažeći zahtev.', 403 ); }
	$enabled = ! empty( $_POST['nekoko_marketing_email_opt_in'] ) && '1' === (string) $_POST['nekoko_marketing_email_opt_in'];
	update_user_meta( get_current_user_id(), 'nekoko_marketing_email_opt_in', $enabled ? '1' : '0' );
	set_transient( 'nekoko_account_settings_notice_' . get_current_user_id(), $enabled ? 'Primanje promo i informativnih emailova je uključeno.' : 'Primanje promo i informativnih emailova je isključeno.', MINUTE_IN_SECONDS );
	wp_safe_redirect( nekoko_core_page_url( 'podesavanje-naloga' ) );
	exit;
}
add_action( 'template_redirect', 'nekoko_core_account_settings_submit', 11 );

function nekoko_core_shortcode_account_settings() {
	nekoko_core_require_login();
	return nekoko_core_template( 'account-settings' );
}
add_shortcode( 'nekoko_account_settings', 'nekoko_core_shortcode_account_settings' );
