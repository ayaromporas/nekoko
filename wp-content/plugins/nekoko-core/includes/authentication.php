<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function nekoko_core_authenticate_email_login( $user, $username, $password ) {
	if ( $user instanceof WP_User || ! $username ) { return $user; }
	if ( is_email( $username ) ) { $found = get_user_by( 'email', $username ); if ( $found ) { return wp_authenticate_username_password( null, $found->user_login, $password ); } }
	return $user;
}
add_filter( 'authenticate', 'nekoko_core_authenticate_email_login', 20, 3 );

function nekoko_core_frontend_login() {
	if ( empty( $_POST['nekoko_login'] ) ) { return; }
	if ( ! wp_verify_nonce( $_POST['_wpnonce'] ?? '', 'nekoko_login' ) ) { set_transient( 'nekoko_login_error', 'Forma je istekla. Pokušajte ponovo.', MINUTE_IN_SECONDS ); wp_safe_redirect( nekoko_core_page_url( 'prijava' ) ); exit; }
	$login = sanitize_text_field( wp_unslash( $_POST['log'] ?? '' ) ); $user = is_email( $login ) ? get_user_by( 'email', $login ) : get_user_by( 'login', $login );
	$result = wp_signon( array( 'user_login' => $login, 'user_password' => (string) ( $_POST['pwd'] ?? '' ), 'remember' => ! empty( $_POST['rememberme'] ) ), is_ssl() );
	if ( is_wp_error( $result ) ) { set_transient( 'nekoko_login_error', 'Email/korisničko ime ili lozinka nisu ispravni.', MINUTE_IN_SECONDS ); wp_safe_redirect( nekoko_core_page_url( 'prijava' ) ); exit; }
	wp_safe_redirect( nekoko_core_is_verified_user( $result->ID ) ? home_url( '/' ) : add_query_arg( 'nekoko_verify_notice', '1', home_url( '/' ) ) ); exit;
}
add_action( 'template_redirect', 'nekoko_core_frontend_login', 4 );

function nekoko_core_hide_admin_for_oglasivac() { if ( is_admin() && ! wp_doing_ajax() && current_user_can( 'read' ) && ! current_user_can( 'manage_options' ) ) { wp_safe_redirect( nekoko_core_page_url( 'moji-oglasi' ) ); exit; } }
add_action( 'admin_init', 'nekoko_core_hide_admin_for_oglasivac' );

/** Show the verification reminder on the landing page after a successful login. */
function nekoko_core_show_login_verification_notice() {
    return is_front_page() && is_user_logged_in() && ! nekoko_core_is_verified_user() && '1' === ( $_GET['nekoko_verify_notice'] ?? '' );
}
add_action( 'wp_enqueue_scripts', static function() {
    if ( ! nekoko_core_show_login_verification_notice() ) { return; }
    wp_enqueue_script( 'nekoko-verification-popup', plugins_url( 'assets/js/verification-popup.js', NEKOKO_CORE_FILE ), array(), filemtime( dirname( NEKOKO_CORE_FILE ) . '/assets/js/verification-popup.js' ), true );
} );
add_action( 'wp_footer', static function() {
    if ( ! nekoko_core_show_login_verification_notice() ) { return; }
    $profile_url = nekoko_core_page_url( 'moj-profil' ) . '#nekoko-email-verification';
    ?>
    <dialog id="nekoko-verification-popup" class="nekoko-verification-popup" aria-labelledby="nekoko-verification-popup-title" aria-describedby="nekoko-verification-popup-description">
        <div class="nekoko-verification-popup__icon" aria-hidden="true">!</div>
        <p class="nekoko-verification-popup__eyebrow">JOŠ JEDAN KORAK</p>
        <h2 id="nekoko-verification-popup-title">Potvrdite email i otključajte postavljanje oglasa</h2>
        <p id="nekoko-verification-popup-description">Uspešno ste se prijavili! Vaša email adresa još nije potvrđena, pa ne možete da kreirate oglase dok je ne verifikujete.</p>
        <p>Otvorite „Moj profil“, proverite email adresu svog naloga i kliknite na „Pošaljite ponovo email za potvrdu“. Zatim otvorite poruku i kliknite na link za verifikaciju. Dobićete i bedž „Potvrđen email“.</p>
        <a class="nekoko-verification-popup__action" href="<?php echo esc_url( $profile_url ); ?>" autofocus>Idite na Moj profil i potvrdite email <span aria-hidden="true">→</span></a>
        <button class="nekoko-verification-popup__later" type="button" data-verification-dismiss>Kasnije, nastaviću da pregledam sajt</button>
    </dialog>
    <?php
}, 5 );
