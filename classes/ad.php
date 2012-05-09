<?php

namespace SimpleAds;

class Ad extends Custom_Post {
  protected static $post_type = 'simple-ad';

  public static function query_random($location_slug=null, $format_slug=null) {
    $ads = self::query($location_slug, $format_slug);
    shuffle($ads);
    return current($ads);
  }

  public static function query($location_slug=null, $format_slug=null) {
    $options = array(
      'post_type' => static::$post_type,
    );
    if ($location_slug && ($location = Location::load($location_slug))) {
      $options['locations'] = $location_slug;
      if (!$format_slug) {
        if ($location->format) {
          $format_slug = $location->format;
        }
      }
    }
    if ($format_slug) {
      $options['meta_key'] = static::get_meta_key('format');
      $options['meta_value'] = $format_slug;
    }
    $posts = get_posts($options);
    $ads = array();
    foreach ($posts as $post) {
      $ads[] = self::load($post);
    }
    return $ads;
  }

  public function render() {
    if ($this->link) {
      $link = esc_attr($this->link);
      $title = esc_attr($this->post_title);
      $target = $this->new_window ? ' target="_blank"' : '';
      echo "<a href=\"$link\" $target title=\"$title\">";
      echo $this->get_image_html();
      echo "</a>";
    } else {
      echo $this->get_image_html();
    }
  }

  public function get_image_html() {
    if (!$this->image || !$this->format) return false;
    return wp_get_attachment_image($this->image, "ad-{$this->format}", false, array('title' => $this->post_title));
  }


  public static function add_meta_boxes() {
    static::add_meta_box('setup', __( 'Ad Setup', self::$plugin_name));
    static::add_meta_box('preview', __( 'Ad Preview', self::$plugin_name));
  }

  public static function display_preview_meta_box($post) {
    static::load($post)->render();
  }

  public static function display_setup_meta_box($post) {
    $ad = static::load($post);
    $post_type = static::$post_type;
    $link = esc_attr($ad->link);
    $new_window = checked($ad->new_window, true, false);
    $zone = $ad->zone;
    $format = $ad->format;
    
    $formats = Format::get_terms();

    $formats_options = '';
    foreach ($formats as $key => $f) {
      $name = esc_attr($f);
      $formats_options .= '<option value="' . esc_attr($key) . '"'.selected($key, $format, false). ' label="'.$name.'">'.$name.'</option>';
    }

    echo <<<HTML

      <p>
        <label for="{$post_type}-format">Format</label>
        <select name="{$post_type}[format]" id="{$post_type}-format">
          $formats_options
        </select>
      </p>
      <p>
        <label for="{$post_type}-image">Image</label>
        <input type="file" name="{$post_type}-image" id="{$post_type}-image">$image
      </p>
      <p>
        <label for="{$post_type}-link">Link</label>
        <input type="text" name="{$post_type}[link]" value="{$link}" id="{$post_type}-link">
      </p>
      <p>
        <label for="{$post_type}-new_window">Open in a new window</label>
        <input type="checkbox" name="{$post_type}[new_window]" id="{$post_type}-new_window" {$new_window} value="1">
      </p>
HTML;
  }

  protected static function get_labels() {
    return array(
      'name' => _x('Ads', 'post type general name', static::$plugin_name),
      'singular_name' => _x('Ad', 'post type singular name', static::$plugin_name),
      'add_new' => __('Add New'),
      'add_new_item' => __('Add New Ad', static::$plugin_name),
      'edit_item' => __('Edit Ad', static::$plugin_name),
      'new_item' => __('New Ad', static::$plugin_name),
      'all_items' => __('All Ads', static::$plugin_name),
      'view_item' => __('View Ad', static::$plugin_name),
      'search_items' => __('Search Ads', static::$plugin_name),
      'not_found' =>  __('No Ad found', static::$plugin_name),
      'not_found_in_trash' => __('No Ad found in Trash', static::$plugin_name), 
      'parent_item_colon' => '',
      'menu_name' => _x('Ads', 'post type general name', static::$plugin_name),
    );
  }

  public static function save_post($post_id) {
    $post = parent::save_post($post_id);
    if (!$post) return false;

    if (isset($_FILES[self::$post_type . '-image']) && preg_match('/^image\//', $_FILES[self::$post_type . '-image']['type'])) {
      $media_id = media_handle_upload(self::get_meta_key("image"), $post_id);
      $post->image = $media_id;
    }

    $format = $post->format;
    if ($format) {
      $f = Format::load($format);
      $id = (int) $f->id;
      wp_set_post_terms($post_id, $id, 'formats', false);
    }

  }
}
