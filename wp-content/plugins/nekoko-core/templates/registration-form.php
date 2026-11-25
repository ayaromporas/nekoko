<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$errors = $GLOBALS['nekoko_registration_errors'] ?? false;
$values = $GLOBALS['nekoko_registration_values'] ?? array();
$receipt_key = sanitize_key( wp_unslash( $_GET['registracija_zavrsena'] ?? '' ) );
$receipt = $receipt_key ? get_transient( 'nekoko_registration_receipt_' . $receipt_key ) : false;
if ( is_array( $receipt ) ) : ?>
<section class="nekoko-form nekoko-registration-complete" aria-labelledby="registration-complete-title">
<h2 id="registration-complete-title">Uspešno ste se registrovali!</h2>
<?php if ( $receipt['mail_sent'] ) : ?>
<div class="nekoko-form__notice" role="status">Poslali smo vam email sa linkom za verifikaciju email adrese.</div>
<p>Otvorite email i kliknite na link za potvrdu da biste aktivirali nalog. Proverite i Spam/Neželjenu poštu.</p>
<?php else : ?>
<div class="nekoko-form__notice nekoko-form__notice--error" role="alert">Nalog je kreiran, ali email za verifikaciju trenutno nije moguće poslati.</div>
<p>Ne morate ponovo da se registrujete. Prijavite se i zatražite novi link za potvrdu emaila na strani Moj profil.</p>
<?php endif; ?>
<p><a href="<?php echo esc_url( nekoko_core_page_url( 'prijava' ) ); ?>">Idite na prijavu</a></p>
<p>Niste dobili email? <a href="<?php echo esc_url( nekoko_core_page_url( 'moj-profil' ) . '#nekoko-email-verification' ); ?>">Zatražite novi link za potvrdu</a>.</p>
</section>
<?php return; endif; ?>
<form class="nekoko-form" method="post" action="<?php echo esc_url( nekoko_core_page_url( 'registracija' ) ); ?>" novalidate data-nekoko-registration><h2>Registracija</h2><?php if ( $errors instanceof WP_Error ) : ?><div role="alert" tabindex="-1" data-registration-errors><?php echo nekoko_core_form_errors( $errors ); ?></div><?php endif; ?><?php wp_nonce_field( 'nekoko_registration' ); ?><input type="hidden" name="nekoko_registration" value="1"><p class="nekoko-form__honeypot" aria-hidden="true"><label>Website<input name="website" tabindex="-1" autocomplete="off"></label></p>
	<p><label for="nekoko-first">Ime *</label><input id="nekoko-first" name="first_name" required autocomplete="given-name" value="<?php echo esc_attr( $values['first_name'] ?? '' ); ?>"></p><p><label for="nekoko-last">Prezime *</label><input id="nekoko-last" name="last_name" required autocomplete="family-name" value="<?php echo esc_attr( $values['last_name'] ?? '' ); ?>"></p><p><label for="nekoko-email">Email *</label><input id="nekoko-email" type="email" name="email" required autocomplete="email" value="<?php echo esc_attr( $values['email'] ?? '' ); ?>"></p><p><label for="nekoko-password">Lozinka *</label><input id="nekoko-password" type="password" name="password" required minlength="10" autocomplete="new-password" aria-describedby="nekoko-password-help"><span id="nekoko-password-help" class="nekoko-form__help">Najmanje 10 znakova, uključujući slovo i broj.</span></p><p><label for="nekoko-password-repeat">Ponovite lozinku *</label><input id="nekoko-password-repeat" type="password" name="password_repeat" required minlength="10" autocomplete="new-password"></p><p><label><input type="checkbox" name="terms" value="1" required> Prihvatam <a href="<?php echo esc_url( nekoko_core_page_url( 'pravila-koriscenja' ) ); ?>">uslove korišćenja</a> i <a href="<?php echo esc_url( get_privacy_policy_url() ); ?>">politiku privatnosti</a>.</label></p><p><button type="submit">Registrujte se</button><span class="nekoko-form__help" role="status" data-registration-status></span></p><div class="nekoko-login-registration" aria-label="Prijava na postojeći nalog">
    <p class="nekoko-login-registration__title">Već imate nalog?</p>
    <p class="nekoko-login-registration__description">Prijavite se i nastavite tamo gde ste stali.</p>
    <a class="nekoko-login-registration__button" href="<?php echo esc_url( nekoko_core_page_url( 'prijava' ) ); ?>">Ulogujte se ovde <span aria-hidden="true">→</span></a>
</div></form>
