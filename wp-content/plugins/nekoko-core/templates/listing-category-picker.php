<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<fieldset class="nekoko-category-picker" data-nekoko-category-picker>
<legend>Kategorije i podkategorije</legend>
<p class="nekoko-form__help">Pretražite i izaberite jednu ili više kategorija. Zatim izaberite podkategorije koje odgovaraju vašem oglasu.</p>
<p><label for="listing-categories">Kategorije *</label>
<select id="listing-categories" name="listing_categories[]" multiple data-listing-categories <?php disabled( ! $selected_branch ); ?>>
<?php foreach ( $terms as $term ) : $root = nekoko_core_category_root( $term->term_id ); if ( ! $root || (int) $term->parent !== (int) $root->term_id ) { continue; } ?>
<option value="<?php echo absint( $term->term_id ); ?>" data-branch="<?php echo esc_attr( $root->slug ); ?>" <?php selected( in_array( (int) $term->term_id, $selected_categories, true ) ); ?>><?php echo esc_html( $term->name ); ?></option>
<?php endforeach; ?>
</select></p>
<p><label for="listing-subcategories">Podkategorije</label>
<select id="listing-subcategories" name="categories[]" multiple data-listing-subcategories <?php disabled( ! $selected_categories ); ?>>
<?php foreach ( $terms as $term ) :
$root = nekoko_core_category_root( $term->term_id );
if ( ! $root || ! $term->parent || (int) $term->parent === (int) $root->term_id ) { continue; }
$ancestors = array_reverse( get_ancestors( $term->term_id, 'kategorija_oglasa', 'taxonomy' ) );
$parent_category = count( $ancestors ) > 1 ? (int) $ancestors[1] : 0;
?>
<option value="<?php echo absint( $term->term_id ); ?>" data-category="<?php echo absint( $parent_category ); ?>" <?php selected( in_array( (int) $term->term_id, $selected_subcategories, true ) ); ?>><?php echo esc_html( nekoko_core_category_path( $term->term_id ) ); ?></option>
<?php endforeach; ?>
</select></p>
<p class="nekoko-form__validation" data-category-picker-error role="alert" hidden>Izaberite najmanje jednu kategoriju.</p>
</fieldset>