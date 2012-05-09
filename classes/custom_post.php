<?php

namespace SimpleAds;

abstract class Custom_Post extends Plugin {
  /**
   * Custom Post Type identifier
   * Needs to be overridden
   * @var string
   */
  protected static $post_type = null;

  /**
   * Wordpress post object
   * @var stdClass
   */
  protected $post = null;

  protected static function init() {
    static::register_post_type();
    static::register_meta_boxes();
  }

  /**
   * @param stdClass $post Wordpress post object
   */
  public function __construct($post=null) {
    if (is_object($post)) {
      $this->post = $post;
    }
  }

  /**
   * Loads a Custom_Post
   * @param stdClass|string|int $post
   * @return Custom_Post
   */
  public static function load($post) {
    if (!$post) {
      return false;
    }
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

  public function get_id() {
    if (isset($this->post->term_id)) {
      return $this->post->term_id;
    } else {
      return false;
    }
  }


  /**
   * Magic getter
   *  1. Using a getter get_$name
   *  2. Using a $post property
   *  3. Using post metas
   */
  public function __get($name) {
    if (method_exists($this, $getter = "get_$name")) {
      return $this->$getter();
    } elseif (property_exists($this->post, $name)) {
      return $this->post->$name;
    } else {
      return $this->get_post_meta($name);
    }
  }

  /**
   * Magic setter
   *  1. Using a setter set_$name
   *  2. Using a $post property
   *  3. Using post metas
   */
  public function __set($name, $value) {
    if (method_exists($this, $setter = "get_$name")) {
      return $this->$setter($value);
    } elseif (property_exists($this->post, $name)) {
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

  public function update_post_terms($terms, $taxonomy='post_tag', $append=true) {
    if (empty($this->post->ID)) {
      return false;
    } else {
      return wp_set_post_terms($this->post->ID, $id, $taxonomy, $append);
    }
  }

  /**
   * Return the field key for a meta
   * Format: $post_type-$meta_field
   * @return string
   */
  public static function get_meta_key($meta_field) {
    return static::$post_type . '-' . $meta_field;
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

  /**
   * Post type args for register_post_type
   */
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

  /**
   * Wordpress hook to add fields to custom type edit page
   * Use static::add_meta_box
   * @hook add_meta_boxes
   */
  public static function add_meta_boxes() {}
  
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
   * Wordpress hook on post save to be able to save for custom fields
   * @hook save_post
   * @return Custom_Post
   */
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
