<?php if ( ! defined( 'ABSPATH' ) ) { exit; } $error = get_transient( 'nekoko_login_error' ); delete_transient( 'nekoko_login_error' ); $notice = get_transient( 'nekoko_verify_notice_' . wp_get_session_token() ); delete_transient( 'nekoko_verify_notice_' . wp_get_session_token() ); ?>
<form class="nekoko-form" method="post" action="">
	<h2>Prijava</h2><?php echo nekoko_core_login_status_notice(); ?><?php if ( $error ) { echo nekoko_core_form_notice( $error, 'error' ); } if ( $notice ) { echo nekoko_core_form_notice( $notice ); } if ( isset( $_GET['verification_sent'] ) ) { echo nekoko_core_form_notice( 'Ako nalog čeka potvrdu, poslali smo novi email kada je to dozvoljeno.' ); } ?>
	<?php wp_nonce_field( 'nekoko_login' ); ?><input type="hidden" name="nekoko_login" value="1">
	<p><label for="nekoko-log">Email ili korisničko ime <span aria-hidden="true">*</span></label><input id="nekoko-log" name="log" required autocomplete="username"></p>
	<p><label for="nekoko-pwd">Lozinka <span aria-hidden="true">*</span></label><input id="nekoko-pwd" type="password" name="pwd" required autocomplete="current-password"></p>
	<p><label><input type="checkbox" name="rememberme" value="1"> Zapamti me</label></p><p><button type="submit">Prijavite se</button></p>
	<p><a href="<?php echo esc_url( wp_lostpassword_url() ); ?>">Zaboravili ste lozinku?</a></p>
	<div class="nekoko-login-registration">
		<p class="nekoko-login-registration__title">Još nemate nalog?</p>
		<p class="nekoko-login-registration__description">Pridružite se NekoKO zajednici.</p>
		<a class="nekoko-login-registration__button" href="<?php echo esc_url( nekoko_core_page_url( 'registracija' ) ); ?>">Registracija <span aria-hidden="true">→</span></a>
	</div>
</form>
