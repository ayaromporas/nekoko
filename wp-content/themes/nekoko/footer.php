<?php
/** Site footer. @package Nekoko */
?>
<footer class="site-footer">
	<div class="site-footer__inner">
		<?php for ( $nekoko_footer_column = 1; $nekoko_footer_column <= 4; $nekoko_footer_column++ ) : ?>
			<div class="site-footer__column">
				<?php dynamic_sidebar( 'footer-' . $nekoko_footer_column ); ?>
			</div>
		<?php endfor; ?>
	</div>
	<?php if ( is_active_sidebar( 'footer-dno' ) ) : ?>
		<div class="site-footer__bottom">
			<?php dynamic_sidebar( 'footer-dno' ); ?>
		</div>
	<?php endif; ?>
</footer>
<?php wp_footer(); ?>
</body>
</html>
