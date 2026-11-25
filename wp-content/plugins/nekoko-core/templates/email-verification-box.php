<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
if ( ! is_user_logged_in() || nekoko_core_is_verified_user() ) { return; }
$verification_user = wp_get_current_user();
$verification_notice = get_transient( 'nekoko_resend_notice_' . $verification_user->ID );
delete_transient( 'nekoko_resend_notice_' . $verification_user->ID );
?>
<section id="nekoko-email-verification" class="nekoko-verification-box" aria-labelledby="nekoko-verification-title">
<h2 id="nekoko-verification-title">Potvrdite svoju email adresu</h2>
<p>Verifikujte svoju email adresu kako biste dobili bedž „Potvrđen email“ na svom profilu i mogli da postavljate oglase.</p>
<p>Otvorite email i kliknite na link za potvrdu. Ako poruka nije stigla, proverite Spam/Neželjenu poštu ili zatražite novi link.</p>
<?php if ( $verification_notice ) { echo nekoko_core_form_notice( $verification_notice['message'], $verification_notice['type'] ); } ?>
<form class="nekoko-form nekoko-form--compact" method="post" action="">
<?php wp_nonce_field( 'nekoko_resend_verification' ); ?>
<input type="hidden" name="nekoko_resend_verification" value="1">
<label for="nekoko-resend-email">Email adresa vašeg naloga</label>
<input id="nekoko-resend-email" type="email" name="email" value="<?php echo esc_attr( $verification_user->user_email ); ?>" required readonly autocomplete="email" aria-describedby="nekoko-resend-help">
<p id="nekoko-resend-help" class="nekoko-form__help">Link šaljemo na email adresu vašeg naloga. Novi link možete zatražiti jednom u 5 minuta.</p>
<button type="submit">Pošaljite ponovo email za potvrdu</button>
</form>
</section>
