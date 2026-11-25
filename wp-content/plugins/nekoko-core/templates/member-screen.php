<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="nekoko-form">
<?php if ( is_page( 'odjava' ) ) : ?>
<?php if ( is_user_logged_in() ) : ?>
<p>Da li želite da se odjavite sa svog naloga?</p>
<form method="post" action="<?php echo esc_url( home_url( '/odjava/' ) ); ?>">
<?php wp_nonce_field( 'log-out' ); ?>
<p><button type="submit">Odjavite se</button></p>
</form>
<?php else : ?><p>Odjavljeni ste sa svog naloga.</p><p><a href="<?php echo esc_url( home_url( '/prijava/' ) ); ?>">Prijavite se ponovo</a></p><?php endif; ?>
<?php else : ?>
<?php if ( ! empty( $GLOBALS['nekoko_member_screen_error'] ) ) { echo nekoko_core_form_notice( $GLOBALS['nekoko_member_screen_error'], 'error' ); } ?>
<?php if ( ! empty( $GLOBALS['nekoko_member_screen_notice'] ) ) { echo nekoko_core_form_notice( $GLOBALS['nekoko_member_screen_notice'] ); } ?>
<?php endif; ?>
<p><a href="<?php echo esc_url( home_url( '/' ) ); ?>">Nazad na početnu stranicu</a></p>
</div>
