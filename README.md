# wp-simple-ads

Wordpress plugin to manage and display custom ads manually

## Requirements

 * Wordpress 3.0+
 * PHP 5.3+

## Installation

 1. Activate plugin
 2. Add Ad Formats
   * Leaderboard
   * Skyscraper
   * See http://en.wikipedia.org/wiki/Web_banner#Standard_sizes
 3. Add Ad Locations and select a Format for each Location
   * Header
   * Footer

## Usage

### Adding Ads

 1. Go to Ads » Add New
 2. Select a Format
   * Selecting a Format will apply this term and overwrite any other « Format » term because only one Format is possible at any time
   * TODO: Format detection on upload
 3. Upload the banner
   * TODO: Support Flash
 4. Optionnally add a link

### Displaying Ads

TODO: Add weights in the random

Use `SimpleAds\Ad::query_random($location)` or `SimpleAds\Ad::query($location)` for all possible banners

Example: 
```php
<?php
  $banner = SimpleAds\Ad::query_random('header');
  if ($banner) $banner->render();
?>
```

Which outputs:

```html
<a href="/banner-target.html" title="Awesome banner">
  <img width="728" height="90" src="http://example.com/wp-content/uploads/2012/05/banner.png" class="attachment-ad-leaderboard" alt="Awesome banner">
</a>
```