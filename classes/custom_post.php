<?php

namespace SimpleAds;

abstract class Custom_Post extends Plugin {
  /**
   * Needs to be overridden
   */
  protected static $post_type = null;

  protected $post = null;

  protected static function init() {
    static::register_post_type();
    static::register_meta_boxes();
  }

  public function __construct($post=null) {
    if (is_object($post)) {
      $this->post = $post;
    }
  }

  public static function load($post) {
    if (!is_object($post)) {
      $post = get_post($post);
    }
    if ($post) {
      $class = get_called_class();
      return new $class($post);
    } else {
      return false;
    }
  }

  public function __get($name) {
    if (method_exists($this, $getter = "get_$name")) {
      return $this->$getter();
    } elseif (isset($this->post->$name)) {
      return $this->post->$name;
    } else {
      return $this->get_post_meta($name);
    }
  }

  public function __set($name, $value) {
    if (method_exists($this, $setter = "get_$name")) {
      return $this->$setter($value);
    } elseif (isset($this->post->$name)) {
      return $this->post->$name = $value;
    } else {
      return $this->update_post_meta($name, $value);
    }
  }

  protected function get_post_meta($name) {
    if (!empty($this->post->ID)) {
      return get_post_meta($this->post->ID, static::get_meta_key($name), true);
    } else {
      return false;
    }
  }

  protected function update_post_meta($name, $value) {
    if (!empty($this->post->ID)) {
      return update_post_meta($this->post->ID, static::get_meta_key($name), $value);
    } else {
      return false;
    }
  }

  public static function get_meta_key($meta_field) {
    return static::$post_type . '-' . $meta_field;
  }

  protected static function add_meta_box($id, $title, $callback=false, $section='normal', $priority='high') {
    if (!$callback) {
      $callback = array(get_called_class(), "display_{$id}_meta_box");
    }
    add_meta_box(
      static::$post_type . '-' . $id, // Box Id
      $title, // Box title
      $callback, // Display callback
      static::$post_type, // Post type
      $section, // Section
      $priority // Priority
    );
  }

  /**
   * Needs to be overridden
   * Cannot be factorized because the i18n utilities won’t find the strings
   */
  protected static function get_labels() {
    return array(
      'name' => _x('Posts', 'post type general name', static::$plugin_name),
      'singular_name' => _x('Post', 'post type singular name', static::$plugin_name),
      'add_new' => __('Add New'),
      'add_new_item' => __('Add New Post', static::$plugin_name),
      'edit_item' => __('Edit Post', static::$plugin_name),
      'new_item' => __('New Post', static::$plugin_name),
      'all_items' => __('All Posts', static::$plugin_name),
      'view_item' => __('View Post', static::$plugin_name),
      'search_items' => __('Search Posts', static::$plugin_name),
      'not_found' =>  __('No Post found', static::$plugin_name),
      'not_found_in_trash' => __('No Post found in Trash', static::$plugin_name), 
      'parent_item_colon' => '',
      'menu_name' => _x('Posts', 'post type general name', static::$plugin_name),
    );
  }

  protected static function get_post_type_args() {
    $labels = static::get_labels();

    return array(
      'label' => $labels['name'],
      'labels' => $labels,
      'public' => true,
      'publicly_queryable' => false,
      'show_ui' => true, 
      'show_in_menu' => true, 
      'query_var' => true,
      'rewrite' => false,
      'capability_type' => 'post',
      'has_archive' => true, 
      'hierarchical' => false,
      'menu_position' => null,
      'supports' => array('title', 'custom-fields')
    ); 
  }

  protected static function register_post_type() {
    register_post_type(static::$post_type, static::get_post_type_args());
  }

  protected static function register_meta_boxes() {
    add_action('add_meta_boxes', array(get_called_class(), 'add_meta_boxes'));
    add_action('save_post', array(get_called_class(), 'save_post'));
  }

  public static function add_meta_boxes() {}

  public static function save_post($post_id) {
    // verify if this is an auto save routine. 
    // If it is our form has not been submitted, so we dont want to do anything
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
      return false;

    // Check permissions
    if ( !current_user_can( 'edit_post', $post_id ) )
      return false;

    $post = static::load($post_id);

    $data = $_POST[static::$post_type];

    foreach ($data as $key => $value) {
      $post->$key = $value;
    }

    return $post;
  }

  public function __toString() {
    return "" . $this->post_title;
  }

}
