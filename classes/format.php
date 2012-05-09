<?php

namespace SimpleAds;

class Format extends Custom_Taxonomy {
  protected static $post_types = array('simple-ad');
  protected static $taxonomy = 'formats';

  public static function edit_form_fields($term) {
    $term = parent::edit_form_fields($term);
    if (!$term) return false;
    ?>

    <tr>
      <th scope="row" valign="top">
        <label for="term-meta-width"><?php _e('Width', static::$plugin_name); ?></label>
      </th>
      <td>
        <input type="number" name="term_meta[width]" id="term-meta-width" value="<?php echo (int) $term->width; ?>"> px
      </td>
    </tr>
    <tr>
      <th scope="row" valign="top">
        <label for="term-meta-height"><?php _e('Height', static::$plugin_name); ?></label>
      </th>
      <td>
        <input type="number" name="term_meta[height]" id="term-meta-height" value="<?php echo (int) $term->height; ?>"> px
      </td>
    </tr>

    <?php
  }


  protected static function get_labels() {
    return array(
      'name' => _x( 'Formats', 'taxonomy general name', static::$plugin_name),
      'singular_name' => _x( 'Format', 'taxonomy singular name', static::$plugin_name),
      'search_items' =>  __( 'Search Formats', static::$plugin_name),
      'popular_items' => __( 'Popular Formats', static::$plugin_name),
      'all_items' => __( 'All Formats', static::$plugin_name),
      'parent_item' => __( 'Parent Format', static::$plugin_name),
      'parent_item_colon' => __( 'Parent Format:', static::$plugin_name),
      'edit_item' => __( 'Edit Format', static::$plugin_name),
      'update_item' => __( 'Update Format', static::$plugin_name),
      'add_new_item' => __( 'Add New Format', static::$plugin_name),
      'new_item_name' => __( 'New Format Name', static::$plugin_name),
    );
  }

  public static function init() {
    parent::init();
    static::register_formats();
  }

  public static function register_formats() {
    $formats = static::get_terms();
    foreach ($formats as $key => $format) {
      add_image_size('ad-' . $key, $format->width, $format->height);
    }
  }

  public function __toString() {
    return "{$this->name} ({$this->width}x{$this->height})";
  }
}