<?php

namespace SimpleAds;

class Ad extends Custom_Post {
  /**
   * Custom Post Type identifier
   * @var string
   */
  protected static $post_type = 'simple-ad';

  /** Public API **/

  /**
   * Query a random Ad, filtering by location and/or format
   * @todo Add support for weights
   * @param string $location_slug Short name of location. Ex: header
   * @param string $format_slug Short name of location. Ex: leaderboard
   * @return array Ad[]
   */
  public static function query_random($location_slug=null, $format_slug=null) {
    $ads = self::query($location_slug, $format_slug);
    shuffle($ads);
    return current($ads);
  }

  /**
   * Query Ads, filtering by location and/or format
   * @param string $location_slug Short name of location. Ex: header
   * @param string $format_slug Short name of location. Ex: leaderboard
   * @return array Ad[]
   */
  public static function query($location_slug=null, $format_slug=null) {
    $options = array(
      'post_type' => static::$post_type,
    );
    if ($location_slug && ($location = Location::load($location_slug))) {
      $options['locations'] = $location_slug;
      if (!$format_slug) {
        // If format not specified, use from location to prevent ads with incompatible sizes
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

  /**
   * Render the Ad HTML, including the <a/>
   * If image or format is invalid, outputs nothing
   */
  public function render() {
    $image_html = $this->get_image_html();
    if (!$image_html) return;

    if ($this->link) {
      $link = esc_attr($this->link);
      $title = esc_attr($this->post_title);
      $target = $this->new_window ? ' target="_blank"' : '';
      echo <<<HTML

      <a href="$link" $target title="$title">
        $image_html
      </a>
HTML;
    } else {
      echo $image_html;
    }
  }

  public function get_image_html() {
    if (!$this->image || !$this->format) return false;
    // Image formats are registered in Format::register_formats()
    return wp_get_attachment_image($this->image, "ad-{$this->format}", false /* icon */, array('alt' => $this->post_title));
  }

  /** Wordpress hooks **/

  public static function add_meta_boxes() {
    static::add_meta_box('setup', __('Ad Setup', self::$plugin_name));
    static::add_meta_box('preview', __('Ad Preview', self::$plugin_name));
  }

  /**
   * Displays the banner as it would appear
   */
  public static function display_preview_meta_box($post) {
    $ad = static::load($post);
    if (!$ad) return;
    $ad->render();
  }

  /**
   * Main admin form
   */
  public static function display_setup_meta_box($post) {
    $ad = static::load($post);
    $link = esc_attr($ad->link);
    $new_window = checked($ad->new_window, true /* compare */, false /* output */);
    
    $formats_options = Format::get_option_tags($ad->format);

    $post_type = static::$post_type;
    echo <<<HTML

      <p>
        <label for="{$post_type}-format">Format</label>
        <select name="{$post_type}[format]" id="{$post_type}-format">
          $formats_options
        </select>
      </p>
      <p>
        <label for="{$post_type}-image">Image</label>
        <input type="file" name="{$post_type}-image" id="{$post_type}-image">
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

  /**
   * Cannot be factorized because the i18n utilities won’t find the strings
   */
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

  /**
   * @hook save_post
   */
  public static function save_post($post_id) {
    $post = parent::save_post($post_id);
    if (!$post) return false;

    $uploaded_key = self::$post_type . '-image';
    if (isset($_FILES[$uploaded_key])) {
      if (!preg_match('/^image\//', $_FILES[$uploaded_key]['type'])) {
        // Not an image, maybe support Flash later
        return false;
      }
      $media_id = media_handle_upload($uploaded_key, $post_id);
      $post->image = $media_id;
    }

    // Ensure the selected format is applied as a tag
    $format = Format::load($post->format);
    if ($format) {
      $post->update_post_terms($format->id, 'formats', false /* Replace existing (only one) */);
    }

  }
}
