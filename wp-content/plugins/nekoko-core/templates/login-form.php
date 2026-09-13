<?php if ( ! defined( 'ABSPATH' ) ) { exit; } $error = get_transient( 'nekoko_login_error' ); delete_transient( 'nekoko_login_error' ); $notice = get_transient( 'nekoko_verify_notice_' . wp_get_session_token() ); delete_transient( 'nekoko_verify_notice_' . wp_get_session_token() ); ?>
<form class="nekoko-form" method="post" action="">
	<h2>Prijava</h2><?php if ( $error ) { echo nekoko_core_form_notice( $error, 'error' ); } if ( $notice ) { echo nekoko_core_form_notice( $notice ); } if ( isset( $_GET['verification_sent'] ) ) { echo nekoko_core_form_notice( 'Ako nalog čeka potvrdu, poslali smo novi email kada je to dozvoljeno.' ); } ?>
	<?php wp_nonce_field( 'nekoko_login' ); ?><input type="hidden" name="nekoko_login" value="1"><input type="hidden" name="redirect_to" value="<?php echo esc_attr( nekoko_core_safe_redirect_url( wp_unslash( $_GET['redirect_to'] ?? '' ) ) ); ?>">
	<p><label for="nekoko-log">Email ili korisničko ime <span aria-hidden="true">*</span></label><input id="nekoko-log" name="log" required autocomplete="username"></p>
	<p><label for="nekoko-pwd">Lozinka <span aria-hidden="true">*</span></label><input id="nekoko-pwd" type="password" name="pwd" required autocomplete="current-password"></p>
	<p><label><input type="checkbox" name="rememberme" value="1"> Zapamti me</label></p><p><button type="submit">Prijavite se</button></p>
	<p><a href="<?php echo esc_url( wp_lostpassword_url() ); ?>">Zaboravili ste lozinku?</a> · <a href="<?php echo esc_url( nekoko_core_page_url( 'registracija' ) ); ?>">Registracija</a></p>
</form>
<form class="nekoko-form nekoko-form--compact" method="post" action=""><?php wp_nonce_field( 'nekoko_resend_verification' ); ?><input type="hidden" name="nekoko_resend_verification" value="1"><label for="nekoko-resend-email">Pošaljite ponovo email za potvrdu</label><input id="nekoko-resend-email" type="email" name="email" required autocomplete="email"><button type="submit">Pošaljite link</button></form>
