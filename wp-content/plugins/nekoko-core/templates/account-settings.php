<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$user_id = get_current_user_id();
$enabled = '1' === get_user_meta( $user_id, 'nekoko_marketing_email_opt_in', true );
$notice = get_transient( 'nekoko_account_settings_notice_' . $user_id );
delete_transient( 'nekoko_account_settings_notice_' . $user_id );
?>
<section class="nekoko-dashboard nekoko-account-settings">
	<h2>Podešavanje naloga</h2>
	<?php if ( $notice ) { echo nekoko_core_form_notice( $notice ); } ?>
	<section class="nekoko-account-settings__option" aria-labelledby="nekoko-marketing-email-title">
		<h3 id="nekoko-marketing-email-title">Promo i informativni emailovi</h3>
		<p>Želim da primam promo i informativne emailove o novostima, korisnim savetima i ponudi na NekoKO.</p>
		<p class="nekoko-account-settings__status"><strong>Status:</strong> <?php echo esc_html( $enabled ? 'Uključeno' : 'Isključeno' ); ?></p>
		<form class="nekoko-account-settings__switch-form" method="post" action="">
			<?php wp_nonce_field( 'nekoko_account_settings' ); ?><input type="hidden" name="nekoko_account_settings" value="1">
			<label class="nekoko-account-settings__switch" for="nekoko-marketing-email-opt-in"><input id="nekoko-marketing-email-opt-in" type="checkbox" name="nekoko_marketing_email_opt_in" value="1" <?php checked( $enabled ); ?> onchange="this.form.submit()"><span class="nekoko-account-settings__switch-track" aria-hidden="true"><span></span></span><span class="nekoko-account-settings__switch-label"><?php echo esc_html( $enabled ? 'Uključeno' : 'Isključeno' ); ?></span></label>
			<noscript><button type="submit">Sačuvaj podešavanje</button></noscript>
		</form>
	</section>
</section>
