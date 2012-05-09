<?php

namespace SimpleAds;

abstract class Plugin {
  protected static $plugin_name = 'simple-ads';
  protected static $basename;

  public static function plugin_init() {
    self::$basename = plugin_basename(__FILE__);
    self::load_textdomain();

    Ad::init();
    Location::init();
    Format::init();
  }

  private static function load_textdomain() {
    load_plugin_textdomain(self::$plugin_name, false,  self::$basename . "/languages");
  }
}
