<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
nekoko_core_require_login();
$post_id = absint( $_GET['oglas_id'] ?? 0 );
if ( $post_id && ! nekoko_core_current_user_can_manage_listing( $post_id ) ) { echo nekoko_core_form_notice( 'Nemate dozvolu za ovaj oglas.', 'error' ); return; }

$post = $post_id ? get_post( $post_id ) : null;
$roots = nekoko_core_category_roots();
$terms = get_terms( array( 'taxonomy' => 'kategorija_oglasa', 'hide_empty' => false ) );
$taxonomy_selection = $post ? nekoko_core_listing_taxonomy_selection( $post_id ) : array( 'branch' => '', 'category' => 0, 'subcategory' => 0 );
$selected_branch = $taxonomy_selection['branch'];
$selected_category = $taxonomy_selection['category'];
$selected_subcategory = $taxonomy_selection['subcategory'];
$root_ids = array();
foreach ( $roots as $root ) { $root_ids[ (int) $root->term_id ] = $root->slug; }
$category_terms = array();
foreach ( $terms as $term ) { if ( isset( $root_ids[ (int) $term->parent ] ) ) { $category_terms[ (int) $term->term_id ] = $root_ids[ (int) $term->parent ]; } }
$subcategory_terms = array();
foreach ( $terms as $term ) { if ( isset( $category_terms[ (int) $term->parent ] ) ) { $subcategory_terms[] = $term; } }
$price_agreed = $post && '1' === get_post_meta( $post_id, 'nekoko_listing_price_agreed', true );
$duration = $post ? get_post_meta( $post_id, 'nekoko_listing_duration', true ) : '';
$duration_na = $post && '1' === get_post_meta( $post_id, 'nekoko_listing_duration_na', true );
$images = $post ? nekoko_core_listing_image_ids( $post_id ) : array();
$error = get_transient( 'nekoko_listing_error_' . get_current_user_id() );
delete_transient( 'nekoko_listing_error_' . get_current_user_id() );
?>
<form class="nekoko-form" method="post" action="" enctype="multipart/form-data" data-nekoko-listing-taxonomy>
	<?php if ( $error ) { echo nekoko_core_form_notice( $error, 'error' ); } wp_nonce_field( 'nekoko_listing_form' ); ?>
	<input type="hidden" name="nekoko_listing_form" value="1"><input type="hidden" name="oglas_id" value="<?php echo esc_attr( $post_id ); ?>">
	<p><label>Tip oglasa *<select name="branch" required data-nekoko-listing-branch><option value="">Izaberite tip oglasa</option><?php foreach ( $roots as $slug => $root ) { ?><option value="<?php echo esc_attr( $slug ); ?>"<?php selected( $selected_branch, $slug ); ?>><?php echo esc_html( $root->name ); ?></option><?php } ?></select></label></p>
	<p><label>Kategorija *<select name="listing_category" required data-nekoko-listing-category><option value="">Izaberite kategoriju</option><?php foreach ( $category_terms as $term_id => $branch_slug ) { $term = get_term( $term_id, 'kategorija_oglasa' ); ?><option value="<?php echo esc_attr( $term_id ); ?>" data-branch="<?php echo esc_attr( $branch_slug ); ?>"<?php selected( $selected_category, $term_id ); ?>><?php echo esc_html( $term->name ); ?></option><?php } ?></select></label></p>
	<p><label>Podkategorija <select name="categories[]" data-nekoko-listing-subcategory><option value="">Izaberite podkategoriju (opciono)</option><?php foreach ( $subcategory_terms as $term ) { ?><option value="<?php echo esc_attr( $term->term_id ); ?>" data-category="<?php echo esc_attr( $term->parent ); ?>"<?php selected( $selected_subcategory, $term->term_id ); ?>><?php echo esc_html( $term->name ); ?></option><?php } ?></select></label></p>
	<p><label>Naslov *<input name="post_title" required value="<?php echo esc_attr( $post->post_title ?? '' ); ?>"></label></p>
	<p><label>Kratak opis <textarea name="post_excerpt" rows="3" maxlength="240" placeholder="U nekoliko reči opišite oglas."><?php echo esc_textarea( $post->post_excerpt ?? '' ); ?></textarea><span class="nekoko-form__help">Ovaj tekst se prikazuje uz cenu na stranici oglasa i na karticama pretrage.</span></label></p>
	<p><label>Opis *<textarea name="post_content" required rows="8"><?php echo esc_textarea( $post->post_content ?? '' ); ?></textarea></label></p>
	<p><label>Tagovi (zarezom odvojeni)<input name="tags" value="<?php echo esc_attr( $post ? implode( ', ', wp_get_object_terms( $post_id, 'oglas_tag', array( 'fields' => 'names' ) ) ) : '' ); ?>"></label></p>
	<p><label>Lokacija *<input name="nekoko_listing_location" required value="<?php echo esc_attr( $post ? get_post_meta( $post_id, 'nekoko_listing_location', true ) : '' ); ?>"></label></p>
	<p data-nekoko-listing-price<?php echo 'potraznja' === $selected_branch ? ' hidden' : ''; ?>><label>Cena (RSD)<input type="number" name="nekoko_listing_price" min="0" value="<?php echo esc_attr( $post ? get_post_meta( $post_id, 'nekoko_listing_price', true ) : '' ); ?>"></label> <label><input type="checkbox" name="nekoko_listing_price_agreed" value="1"<?php checked( $price_agreed ); ?>> Cena po dogovoru</label></p>
	<p data-nekoko-listing-duration<?php echo 'potraznja' === $selected_branch && ! $duration_na ? '' : ' hidden'; ?>><label>Trajanje *<input name="nekoko_listing_duration" value="<?php echo esc_attr( $duration ); ?>" placeholder="Npr. 2 sata, jedan dan, vikendom"<?php echo 'potraznja' === $selected_branch && ! $duration_na ? ' required' : ''; ?>></label></p>
	<p data-nekoko-listing-duration-option<?php echo 'potraznja' === $selected_branch ? '' : ' hidden'; ?>><label><input type="checkbox" name="nekoko_listing_duration_na" value="1" data-nekoko-listing-duration-na<?php checked( $duration_na ); ?>> Trajanje nije primenljivo <span class="nekoko-form__help">Označite ako objavljujete proizvod ili trajanje nije važno.</span></label></p>
	<?php if ( $images ) : ?><fieldset class="nekoko-listing-images"><legend>Postojeće slike</legend><div class="nekoko-listing-images__grid"><?php foreach ( $images as $image_id ) : ?><figure class="nekoko-listing-images__item"><button class="nekoko-listing-images__remove" type="button" data-nekoko-remove-image aria-pressed="false" aria-label="Ukloni sliku iz oglasa"><span aria-hidden="true">×</span></button><input type="hidden" name="remove_images[]" value="<?php echo esc_attr( $image_id ); ?>" disabled><?php echo wp_get_attachment_image( $image_id, 'thumbnail', false, array( 'alt' => '' ) ); ?><?php if ( (int) get_post_thumbnail_id( $post_id ) === (int) $image_id ) : ?><figcaption>Glavna slika</figcaption><?php endif; ?></figure><?php endforeach; ?></div><p class="nekoko-form__help">Kliknite ×, pa sačuvajte izmene, da biste sliku uklonili samo iz ovog oglasa.</p></fieldset><?php endif; ?>
	<p><span class="nekoko-file-input__label">Dodajte slike</span><label class="nekoko-file-input"><input class="nekoko-file-input__native" type="file" name="listing_images[]" accept="image/jpeg,image/png,image/webp" multiple data-nekoko-file-input><span class="nekoko-file-input__button">Izaberite slike</span><span class="nekoko-file-input__status" data-nekoko-file-status>Nijedna slika nije izabrana</span></label><span class="nekoko-form__help">Možete dodati više JPG, PNG ili WebP slika.</span></p>
	<p><button type="submit"><?php echo $post ? 'Sačuvajte izmene' : 'Postavite oglas'; ?></button></p>
</form>
