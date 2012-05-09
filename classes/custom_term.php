<?php

namespace SimpleAds;

abstract class Custom_Term extends Plugin {
  /**
   * Array of post types identifiers to bind to
   * Needs to be overridden
   * @var array
   */
  protected static $post_types = array();
  /**
   * Custom Taxonomy identifier
   * Needs to be overridden
   * @var string
   */
  protected static $taxonomy = null;

  /**
   * Wordpress term object
   * @var stdClass
   */
  protected $term = null;
  /**
   * Array containing term metas
   * @var array
   */
  protected $metas = null;

  public function __construct($term=null) {
    if (is_object($term)) {
      $this->term = $term;
    }
  }

  /**
   * Loads a Custom_Term
   * @param stdClass|string|int $post
   * @return Custom_Term
   */
  public static function load($term) {
    if (!$term) {
      return false;
    }
    if (is_numeric($term)) {
      $term = get_term($term, static::$taxonomy);
    } elseif (is_string($term)) {
      $term = static::get_term_by($term);
    }
    if (!$term) return false;

    $class = get_called_class();
    return new $class($term);
  }

  public function __get($name) {
    if (method_exists($this, $getter = "get_$name")) {
      return $this->$getter();
    } elseif (property_exists($this->term, $name)) {
      return $this->term->$name;
    } else {
      return $this->get_term_meta($name);
    }
  }

  public function __set($name, $value) {
    if (method_exists($this, $setter = "get_$name")) {
      return $this->$setter($value);
    } elseif (property_exists($this->term, $name)) {
      return $this->term->$name = $value;
    } else {
      return $this->update_term_meta($name, $value);
    }
  }

  public function get_id() {
    if (isset($this->term->term_id)) {
      return $this->term->term_id;
    } else {
      return false;
    }
  }

  protected function get_option_id() {
    return $this->id ? "taxonomy_term_{$this->id}_meta" : false;
  }

  public function load_metas() {
    // Already loaded
    if (!is_null($this->metas)) return;

    $option_id = $this->get_option_id();
    $this->metas = array();
    if ($option_id) {
      $option = get_option($option_id);
      if (!empty($option)) {
        $this->metas = $option;
      }
    }
  }

  public function get_term_meta($name) {
    $this->load_metas();
    if (isset($this->metas[$name])) {
      return $this->metas[$name];
    } else {
      return false;
    }
  }

  public function update_term_meta($name, $value) {
    $this->update_metas(array($name => $value));
    return $value;
  }

  public function update_metas(array $options) {
    $this->load_metas();
    $options = array_merge($this->metas, $options);
    return update_option($this->get_option_id(), $options);
  }


  public static function get_term_by($slug, $type='slug') {
    return get_term_by($type, $slug, static::$taxonomy);
  }

  public static function get_terms() {
    $terms = get_terms(static::$taxonomy, array('hide_empty' => false));
    $objects = array();
    foreach ($terms as $term) {
      $object = static::load($term);
      if ($object) {
        $objects[$term->slug] = $object;
      }
    }
    return $objects;
  }

  /**
   * HTML for <option/> tag, to put in a <select/>, listing all values
   * @param string $selected Current selected value
   */
  public static function get_option_tags($selected=null) {
    $terms = static::get_terms();
    $terms_options = '';

    foreach ($terms as $key => $f) {
      $terms_options .= 
        '<option value="' . esc_attr($key) . '"'
          . selected($key, $term, false)
          . ' label="'.esc_attr($f).'">'
          .   esc_html($f)
        .'</option>';
    }
    
    return $terms_options;
  }

  public static function init() {
    static::register_taxonomy();
    static::register_custom_fields();
  }

  /**
   * Needs to be overridden
   * Cannot be factorized because the i18n utilities won’t find the strings
   */
  protected static function get_labels() {
    return array(
      'name' => _x( 'Terms', 'taxonomy general name', static::$plugin_name),
      'singular_name' => _x( 'Term', 'taxonomy singular name', static::$plugin_name),
      'search_items' =>  __( 'Search Terms', static::$plugin_name),
      'popular_items' => __( 'Popular Terms', static::$plugin_name),
      'all_items' => __( 'All Terms', static::$plugin_name),
      'parent_item' => __( 'Parent Term', static::$plugin_name),
      'parent_item_colon' => __( 'Parent Term:', static::$plugin_name),
      'edit_item' => __( 'Edit Term', static::$plugin_name),
      'update_item' => __( 'Update Term', static::$plugin_name),
      'add_new_item' => __( 'Add New Term', static::$plugin_name),
      'new_item_name' => __( 'New Term Name', static::$plugin_name),
    );
  }

  protected static function get_taxonomy_args() {
    $labels = static::get_labels();

    return array(
      'label' => $labels['name'],
      'hierarchical' => true,
      'labels' => $labels,
      'show_ui' => true,
      'query_var' => true,
    ); 
  }

  protected static function register_taxonomy() {
    register_taxonomy(static::$taxonomy, static::$post_types, static::get_taxonomy_args());
  }

  protected static function register_custom_fields() {
    add_action(static::$taxonomy . '_edit_form_fields', array(get_called_class(), 'edit_form_fields'), 10, 2);
    // add_action(static::$taxonomy . '_add_form_fields', array(get_called_class(), 'display_custom_fields'), 10, 2);
    add_action('edited_' . static::$taxonomy, array(get_called_class(), 'save_custom_fields'), 10, 2);
    add_action('created_' . static::$taxonomy, array(get_called_class(), 'save_custom_fields'), 10, 2);
  }


  public static function edit_form_fields($term) {
    return static::load($term);
  }


  public static function save_custom_fields($term_id) {
    if (isset($_POST['term_meta']) ) {
      $term = static::load($term_id);
      if ($term) {
        $term->update_metas((array) $_POST['term_meta']);
      }
    }
  }

  public function __toString() {
    return "" . $this->name;
  }

}
