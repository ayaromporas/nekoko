<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$id             = get_current_user_id();
$user           = get_userdata( $id );
$errors         = get_transient( 'nekoko_profile_errors_' . $id );
$notice         = get_transient( 'nekoko_profile_notice_' . $id );
$password_error = get_transient( 'nekoko_profile_password_error_' . $id );
$phone_full     = preg_replace( '/\D+/', '', (string) get_user_meta( $id, 'nekoko_phone', true ) );
$phone_country  = preg_replace( '/\D+/', '', (string) get_user_meta( $id, 'nekoko_phone_country', true ) );
$countries      = nekoko_core_phone_countries();
$display_format = get_user_meta( $id, 'nekoko_display_name_format', true );
$profile_image  = absint( get_user_meta( $id, 'nekoko_profile_image', true ) );

if ( ! in_array( $display_format, array( 'username', 'full_name', 'first_last_initial' ), true ) ) {
	$display_format = $user->display_name === $user->user_login ? 'username' : 'full_name';
}

if ( ! isset( $countries[ $phone_country ] ) ) {
	$phone_country = '381';
	foreach ( array_keys( $countries ) as $country_code ) {
		if ( str_starts_with( $phone_full, $country_code ) ) {
			$phone_country = $country_code;
			break;
		}
	}
}
$phone_number = preg_replace( '/^' . preg_quote( $phone_country, '/' ) . '/', '', $phone_full );
$phone_number = ltrim( $phone_number, '0' );

delete_transient( 'nekoko_profile_errors_' . $id );
delete_transient( 'nekoko_profile_notice_' . $id );
delete_transient( 'nekoko_profile_password_error_' . $id );
?>
<section class="nekoko-dashboard">
	<h2>Moj profil</h2>
	<?php echo nekoko_core_dashboard_nav(); ?>
	<p class="nekoko-public-profile-link"><a class="button" href="<?php echo esc_url( get_author_posts_url( $id ) ); ?>">Pogledaj javni profil</a></p>
	<?php
	if ( $notice ) {
		echo nekoko_core_form_notice( $notice );
	}
	if ( $errors instanceof WP_Error ) {
		echo nekoko_core_form_errors( $errors );
	}
	if ( $password_error ) {
		echo nekoko_core_form_notice( $password_error, 'error' );
	}
	?>

	<form class="nekoko-form" method="post" action="" enctype="multipart/form-data">
		<?php wp_nonce_field( 'nekoko_profile_form' ); ?>
		<input type="hidden" name="nekoko_profile_form" value="1">
		<p><label>Korisničko ime</label><input value="<?php echo esc_attr( $user->user_login ); ?>" disabled></p>
		<p><label>Ime *<input name="first_name" required autocomplete="given-name" value="<?php echo esc_attr( $user->first_name ); ?>"></label></p>
		<p><label>Prezime *<input name="last_name" required autocomplete="family-name" value="<?php echo esc_attr( $user->last_name ); ?>"></label></p>
		<fieldset class="nekoko-display-name-options">
			<legend>Prikazano ime</legend>
			<label><input type="radio" name="nekoko_display_name_format" value="username" <?php checked( $display_format, 'username' ); ?>> Korisničko ime <span class="nekoko-form__help">Primer: <?php echo esc_html( $user->user_login ); ?></span></label>
			<label><input type="radio" name="nekoko_display_name_format" value="full_name" <?php checked( $display_format, 'full_name' ); ?>> Ime i prezime <span class="nekoko-form__help">Primer: <?php echo esc_html( trim( $user->first_name . ' ' . $user->last_name ) ); ?></span></label>
			<label><input type="radio" name="nekoko_display_name_format" value="first_last_initial" <?php checked( $display_format, 'first_last_initial' ); ?>> Ime i prvo slovo prezimena <span class="nekoko-form__help">Primer: <?php echo esc_html( nekoko_core_profile_display_name( 'first_last_initial', $user->user_login, $user->first_name, $user->last_name ) ); ?></span></label>
		</fieldset>
		<p><label>Email adresa *<input type="email" name="user_email" required autocomplete="email" value="<?php echo esc_attr( $user->user_email ); ?>" aria-describedby="nekoko-email-validation" data-nekoko-validation-message="nekoko-email-validation"></label><span id="nekoko-email-validation" class="nekoko-form__validation" hidden>Email mora biti u formatu ime@domen.rs.</span></p>
		<p><label>Trenutna lozinka (samo za promenu emaila)<input type="password" name="current_password" autocomplete="current-password"></label></p>
		<p class="nekoko-phone-field">
			<span class="nekoko-phone-field__label">Telefon</span>
			<span class="nekoko-phone-field__controls">
				<label class="screen-reader-text" for="nekoko-phone-country">Zemlja i pozivni broj</label>
				<select id="nekoko-phone-country" name="nekoko_phone_country" data-nekoko-phone-country>
					<?php foreach ( $countries as $country_code => $country ) { ?>
						<option value="<?php echo esc_attr( $country_code ); ?>" <?php selected( $phone_country, $country_code ); ?>><?php echo esc_html( $country['flag'] . ' ' . $country['code'] . ' ' . $country['label'] ); ?></option>
					<?php } ?>
				</select>
				<label class="screen-reader-text" for="nekoko-phone-number">Broj telefona bez pozivnog broja</label>
				<input id="nekoko-phone-number" class="nekoko-phone-field__number" type="tel" name="nekoko_phone" value="<?php echo esc_attr( $phone_number ); ?>" inputmode="numeric" autocomplete="tel-national" pattern="[1-9][0-9]{7,14}" minlength="8" maxlength="15" placeholder="641234567" aria-describedby="nekoko-phone-help" data-nekoko-phone-number data-nekoko-validation-message="nekoko-phone-help">
			</span>
			<span id="nekoko-phone-help" class="nekoko-form__validation" hidden>Unesite samo cifre, bez početne nule i bez pozivnog broja, npr. 641234567.</span>
		</p>
		<p><label>Grad/opština<input name="nekoko_city" value="<?php echo esc_attr( get_user_meta( $id, 'nekoko_city', true ) ); ?>"></label></p>
		<p><label><input type="checkbox" name="nekoko_show_city" value="1" <?php checked( get_user_meta( $id, 'nekoko_show_city', true ), '1' ); ?>> Dozvoljavam da se moja lokacija vidi na mom profilu</label></p>
		<div class="nekoko-profile-image-field">
			<span class="nekoko-file-input__label">Profilna fotografija</span>
			<?php if ( $profile_image ) { ?>
				<figure class="nekoko-profile-image-preview">
					<button class="nekoko-profile-image-preview__remove" type="button" data-nekoko-remove-profile-image aria-pressed="false" aria-label="Ukloni profilnu fotografiju"><span aria-hidden="true">×</span></button>
					<input type="hidden" name="nekoko_remove_profile_image" value="1" disabled>
					<?php echo wp_get_attachment_image( $profile_image, 'thumbnail', false, array( 'alt' => '' ) ); ?>
				</figure>
				<span class="nekoko-form__help">Kliknite ×, pa sačuvajte profil, da biste uklonili fotografiju.</span>
			<?php } ?>
			<label class="nekoko-file-input"><input class="nekoko-file-input__native" type="file" name="nekoko_profile_image_upload" accept="image/jpeg,image/png,image/webp" data-nekoko-file-input><span class="nekoko-file-input__button"><?php echo $profile_image ? 'Zamenite sliku' : 'Izaberite sliku'; ?></span><span class="nekoko-file-input__status" data-nekoko-file-status>Nijedna slika nije izabrana</span></label>
			<span class="nekoko-form__help">Dozvoljene su JPG, PNG i WebP slike.</span>
		</div>
		<p><label>U par reči o meni<textarea name="nekoko_profile_bio" maxlength="500"><?php echo esc_textarea( get_user_meta( $id, 'nekoko_profile_bio', true ) ); ?></textarea></label></p>
		<p><label><input type="checkbox" name="nekoko_show_phone" value="1" <?php checked( get_user_meta( $id, 'nekoko_show_phone', true ), '1' ); ?>> Dozvoljavam da se moj telefon vidi na mom profilu</label></p>
		<button type="submit">Sačuvajte profil</button>
	</form>

	<?php if ( get_user_meta( $id, 'nekoko_pending_email', true ) ) { ?>
		<p>Čeka potvrdu: <?php echo esc_html( get_user_meta( $id, 'nekoko_pending_email', true ) ); ?> · <a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'nekoko_cancel_email', '1', nekoko_core_page_url( 'moj-profil' ) ), 'nekoko_cancel_email' ) ); ?>">Otkažite promenu</a></p>
	<?php } ?>

	<form class="nekoko-form" method="post" action="">
		<h3>Promenite lozinku</h3>
		<?php wp_nonce_field( 'nekoko_profile_password' ); ?>
		<input type="hidden" name="nekoko_profile_password" value="1">
		<p><label>Trenutna lozinka *<input type="password" name="current_password" required autocomplete="current-password"></label></p>
		<p class="nekoko-profile-password-help"><a class="nekoko-profile-lost-password" href="<?php echo esc_url( wp_lostpassword_url() ); ?>">Zaboravili ste lozinku?</a></p>
		<p><label>Nova lozinka *<input type="password" name="new_password" required minlength="10" autocomplete="new-password"></label></p>
		<p><label>Ponovite novu lozinku *<input type="password" name="new_password_repeat" required minlength="10" autocomplete="new-password"></label></p>
		<button type="submit">Promenite lozinku</button>
	</form>
</section>
