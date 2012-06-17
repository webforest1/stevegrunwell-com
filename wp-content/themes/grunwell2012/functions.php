<?php
/**
 * Theme functions
 * @package WordPress
 * @subpackage grunwell2012
 * @author Steve Grunwell <steve@stevegrunwell.com>
 */

include_once dirname(__FILE__) . '/simple-twitter-timeline/twitter.class.php';

/** Set the page ID for 'portfolio', which all posts of type grunwell_portfolio will be treated as children of */
define('GRUNWELL_PORTFOLIO_PARENT', 30);

/** Register scripts and styles */
function grunwell_register_scripts_styles(){
  # Styles
  wp_register_style('site-styles', get_bloginfo('template_url') . '/css/base.css', null, null, 'all');

  # Scripts
  wp_register_script('site-scripts', get_bloginfo('template_url') . '/js/main.js', array('jquery', 'jquery-placeholder'), '', true);

  // jQuery Placeholder - https://github.com/mathiasbynens/jquery-placeholder
  wp_enqueue_script('jquery-placeholder', get_bloginfo('template_url') . '/js/jquery.placeholder.min.js', array('jquery'), '1.8.7', true);
}
add_action('init', 'grunwell_register_scripts_styles');

/**
* Creates the "Portfolio" custom post type
* @return void
*/
function grunwell_create_portfolio_post_type() {
  $args = array(
    'can_export' => true,
    'has_archive' => false,
    'hierarchical' => false,
    'labels' => array(
      'name' => 'Portfolio',
      'singular_name' => 'Portfolio piece',
      'add_new' => 'Add new',
      'all_items' => 'All entries',
      'add_new_item' => 'New entry',
      'edit_item' => 'Edit entry',
      'new_item' => 'New entry',
      'view_item' => 'View entry',
      'search_items' => 'Search portfolio',
      'not_found' => 'No portfolio entries found',
      'not_found_in_trash' => 'No portfolio entries found in trash',
      'parent_item_colon' => 'Portfolio',
      'menu_name' => 'Portfolio'
    ),
    'menu_icon' => null,
    'public' => true,
    'rewrite' => array(
      'slug' => 'portfolio',
      'with_front' => false
    ),
    'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'page-attributes'),
    'taxonomies' => array('post_tag')
  );
  register_post_type('grunwell_portfolio', $args);
  return;
}
add_action('init', 'grunwell_create_portfolio_post_type');

/**
 * Array filter callback to remove 'current_page_parent' and 'current_page_ancestor' CSS classes from menu items
 * @param str $class A single CSS class
 * @return bool True if $class is not in $filter, false otherwise
 */
function grunwell_remove_active_nav_classes( $class ) {
  $filter = array('current_page_item', 'current_page_parent', 'current_page_ancestor');
  return ! in_array( $class, $filter );
}

/**
 * Don't highlight "Blog" in the primary navigation when we're looking at grunwell_portfolio pages
 * @global $post
 * @param array $classes CSS classes to be applied to $item
 * @param object $item The WordPress menu item
 * @uses grunwell_remove_active_nav_classes()
 * @uses get_post_type()
 * @uses get_the_ID()
 * @see http://modal.us/blog/2011/04/28/single-custom-posts-can-highlight-nav-and-sub-nav-links-really/
 */
function grunwell_repair_nav_classes( $classes, $item ) {
  global $post;
  if ( get_post_type() === 'grunwell_portfolio' ) {
    if ( in_array( 'grunwell_portfolio', $classes ) ) {
      $classes[] = ( $item->ID == get_the_ID() ? 'current_page_parent' : 'current_page_ancestor' );
    } else { // Make sure nobody else has it
      $classes = array_filter( $classes, 'grunwell_remove_active_nav_classes' );
    }
  }
  return $classes;
}
add_action( 'nav_menu_css_class', 'grunwell_repair_nav_classes', 10, 2 );

/**
 * Force the post parent ID for posts of type grunwell_portfolio
 * @global $wpdb
 * @global GRUNWELL_PORTFOLIO_PARENT
 * @param int $id The ID of the post being saved
 * @return void
 * @uses wpdb::update()
 */
function grunwell_portfolio_set_parent_id($id){
  global $wpdb;
  if( isset($_POST['post_type']) && $_POST['post_type'] == 'grunwell_portfolio' ){
    $wpdb->update($wpdb->posts, array('post_parent' => GRUNWELL_PORTFOLIO_PARENT), array('ID' => $id), array('%d'), array('%d'));
  }
  return;
}
add_action('save_post', 'grunwell_portfolio_set_parent_id');

/**
 * Register custom WordPress menu positions
 * @return void
 */
function grunwell_custom_menus(){
  register_nav_menus(
    array(
      'primary-nav' => 'Primary Navigation',
      'social-networks' => 'Social Networks'
    )
  );
  return;
}
add_action('init', 'grunwell_custom_menus');

/**
 * Remove admin menus we don't need (Links)
 * @return void
 */
function grunwell_remove_menus(){
  global $menu;
  $restricted = array(__('Links'));
  end($menu);
  while( prev($menu) ){
    $value = explode(' ',$menu[key($menu)][0]);
    if( in_array($value['0'] != null ? $value[0] : '' , $restricted) ){
      unset($menu[key($menu)]);
    }
  }
  return;
}
add_action('admin_menu', 'grunwell_remove_menus');

/**
 * Get the tag for #site-logo
 * Will use a <h1> on the front page and <div> on the others
 * @return str
 */
function grunwell_sitelogo(){
  $tag = ( is_front_page() ? 'h1' : 'div' );
  return sprintf('<%s id="site-logo"><a href="%s" rel="home"><img src="%s/img/site-logo.png" alt="%s" /></a></%s>', $tag, home_url('/'), get_bloginfo('template_url'), esc_attr(get_bloginfo('template_url')), $tag);
}

/**
 * Wrap "st" and "th" in <sup> (useful for dates)
 * @param $str The string to search/filter
 * @return str
 */
function grunwell_superscript_dates($str){
  if( preg_match_all('/\d(st|nd|rd|th)/i', $str, $matches) ){
    foreach( $matches['0'] as $k=>$v ){
      $replacement = str_replace($matches['1'][$k], sprintf('<sup>%s</sup>', $matches['1'][$k]), $v);
      $str = str_replace($v, $replacement, $str);
    }
  }
  return $str;
}
add_filter('get_the_date', 'grunwell_superscript_dates');

/**
 * Get a custom field stored in the Advanced Custom Fields plugin
 * By running it through this function, we ensure that we don't die if the plugin is uninstalled/disabled (and thus the function is undefined)
 * @global $post
 * @param str $key The key to look for
 * @param int $id The post ID
 * @param mixed $default What to return if there's nothing
 * @return mixed (dependent upon $echo)
 * @uses get_field()
 */
function grunwell_get_custom_field($key, $id=false, $default=''){
  global $post;
  $key = trim(filter_var($key, FILTER_SANITIZE_STRING));
  $result = '';

  if( function_exists('get_field') ){
    if( intval($id) > 0 ){
      $result = get_field($key, intval($id));
    } else if( isset($post->ID) ){
      $result = get_field($key);
    }

    if( $result == '' ){
      $result = $default;
    }
  } else { // get_field() is undefined, most likely due to the plugin being inactive
    $result = $default;
  }
  return $result;
}

/** Shortcut for echo grunwell_get_custom_field() */
function grunwell_custom_field($key, $id=false, $default=''){
  echo grunwell_get_custom_field($key, $id, $default);
  return;
}

/**
 * Get Tweets using the SimpleTwitterTimeline class
 * @return array
 * @uses SimpleTwitterTimeline::get_timeline()
 */
function grunwell_get_tweets(){
  if( class_exists('SimpleTwitterTimeline') ){
    $args = array(
      'exclude_replies' => false,
      'limit' => 3,
      'parse_links' => true,
      'use_cache' => true,
      'cache_path' => dirname(__FILE__)
    );
    $twitter = new SimpleTwitterTimeline('stevegrunwell', $args);
    return $twitter->get_timeline();
  }
  return array();
}

?>