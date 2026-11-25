<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$reset = is_page( 'nova-lozinka' );
$errors = $GLOBALS['nekoko_password_errors'] ?? new WP_Error();
$notice = $GLOBALS['nekoko_password_notice'] ?? '';
?>
<div class="nekoko-form">
<?php if ( $errors->has_errors() ) { echo nekoko_core_form_errors( $errors ); } ?>
<?php if ( $notice ) { echo nekoko_core_form_notice( $notice ); } ?>
<?php if ( ! $notice && ( ! $reset || ! empty( $GLOBALS['nekoko_password_reset_valid'] ) ) ) : ?>
<form method="post" action="<?php echo esc_url( home_url( $reset ? '/nova-lozinka/' : '/zaboravljena-lozinka/' ) ); ?>">
<?php wp_nonce_field( 'nekoko_password_recovery' ); ?>
<input type="hidden" name="nekoko_password_recovery" value="1">
<?php if ( $reset ) : ?>
<p>Unesite novu lozinku za svoj nalog.</p>
<p><label for="new-password">Nova lozinka</label><input id="new-password" type="password" name="pass1" required minlength="10" autocomplete="new-password" aria-describedby="password-help"><span id="password-help" class="nekoko-form__help">Najmanje 10 znakova, uključujući slovo i broj.</span></p>
<p><label for="repeat-password">Ponovite novu lozinku</label><input id="repeat-password" type="password" name="pass2" required minlength="10" autocomplete="new-password"></p>
<p><button type="submit">Sačuvajte novu lozinku</button></p>
<?php else : ?>
<p>Unesite email adresu ili korisničko ime. Poslaćemo vam link za promenu lozinke.</p>
<p><label for="recovery-login">Email adresa ili korisničko ime</label><input id="recovery-login" type="text" name="user_login" required autocomplete="username" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_POST['user_login'] ?? '' ) ) ); ?>"></p>
<p><button type="submit">Pošaljite link za promenu lozinke</button></p>
<?php endif; ?>
</form>
<?php endif; ?>
<?php if ( $reset && $errors->has_errors() && empty( $GLOBALS['nekoko_password_reset_valid'] ) ) : ?><p><a href="<?php echo esc_url( home_url( '/zaboravljena-lozinka/' ) ); ?>">Zatražite novi link</a></p><?php endif; ?>
<p><a href="<?php echo esc_url( home_url( '/prijava/' ) ); ?>">Nazad na prijavu</a></p>
</div>
