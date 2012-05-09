<?php

namespace SimpleAds;

class Location extends Custom_Term {
  protected static $post_types = array('simple-ad');
  protected static $taxonomy = 'locations';

  /**
   * Cannot be factorized because the i18n utilities won’t find the strings
   */
  protected static function get_labels() {
    return array(
      'name' => _x( 'Locations', 'taxonomy general name', static::$plugin_name),
      'singular_name' => _x( 'Location', 'taxonomy singular name', static::$plugin_name),
      'search_items' =>  __( 'Search Locations', static::$plugin_name),
      'popular_items' => __( 'Popular Locations', static::$plugin_name),
      'all_items' => __( 'All Locations', static::$plugin_name),
      'parent_item' => __( 'Parent Location', static::$plugin_name),
      'parent_item_colon' => __( 'Parent Location:', static::$plugin_name),
      'edit_item' => __( 'Edit Location', static::$plugin_name),
      'update_item' => __( 'Update Location', static::$plugin_name),
      'add_new_item' => __( 'Add New Location', static::$plugin_name),
      'new_item_name' => __( 'New Location Name', static::$plugin_name),
    );
  }

  /**
   * @hook $taxonomy_edit_form_fields
   */
  public static function edit_form_fields($term) {
    $term = parent::edit_form_fields($term);
    if (!$term) return false;

    $formats = Format::get_terms();

    ?>

    <tr class="form-field">
      <th scope="row" valign="top">
        <label for="term-meta-format"><?php _e('Format', static::$plugin_name); ?></label>
      </th>
      <td>
        <select name="term_meta[format]" id="term-meta-format">
          <?php 
            foreach ($formats as $key => $f) {
              $name = esc_attr($f);
              echo '<option value="' . esc_attr($key) . '"'.selected($key, $term->format, false). ' label="'.$name.'">'.$name.'</option>';
            }
          ?>
        </select>
      </td>
    </tr>

    <?php

  }
}