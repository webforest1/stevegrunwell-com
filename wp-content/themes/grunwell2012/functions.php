<?php
/**
 * Theme functions
 * @package WordPress
 * @subpackage grunwell2012
 * @author Steve Grunwell <steve@stevegrunwell.com>
 */

include_once dirname(__FILE__) . '/simple-twitter-timeline/twitter.class.php';

/** Register scripts and styles */
function grunwell_register_scripts_styles(){
  wp_enqueue_script('site-scripts', get_bloginfo('template_url') . '/js/main.js', array('jquery', 'jquery-placeholder', 'syntax-highlighter'), '', true);
  wp_localize_script('site-scripts', 'themeSettings', array('templatePath' => get_bloginfo('template_url')));
  
  // jQuery Placeholder - https://github.com/mathiasbynens/jquery-placeholder
  wp_enqueue_script('jquery-placeholder', get_bloginfo('template_url') . '/js/jquery.placeholder.min.js', array('jquery'), '1.8.7', true);
  
  // Syntax Highlighter - http://alexgorbatchev.com/SyntaxHighlighter/
  wp_enqueue_style('syntax-highlighter', get_bloginfo('template_url') . '/css/syntax-highlighter/shCore.css', null, '3.0.83', 'screen');
  wp_enqueue_style('syntax-highlighter-default', get_bloginfo('template_url') . '/css/syntax-highlighter/shThemeDefault.css', array('syntax-highlighter'), '3.0.83', 'screen');
  wp_enqueue_script('syntax-highlighter', get_bloginfo('template_url') . '/js/syntax-highlighter/shCore.js', null, '3.0.83', true);
  wp_enqueue_script('syntax-highlighter-autoloader', get_bloginfo('template_url') . '/js/syntax-highlighter/shAutoloader.js', null, '3.0.83', true);
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
    'supports' => array('title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'comments', 'page-attributes'),
    'taxonomies' => array('post_tag')
  );
  register_post_type('grunwell_portfolio', $args);
  return;
}
add_action('init', 'grunwell_create_portfolio_post_type');

function grunwell_portfolio_set_parent_id($id){
  if( isset($_POST['post_type']) && $_POST['post_type'] == 'grunwell_portfolio' ){
    update_post_meta($id, 'parent_id', 30);
  }
  return;
}
add_action('save_post', 'grunwell_portfolio_set_parent_id');

function grunwell_custom_menus(){
  register_nav_menus(
    array('primary-nav' => 'Primary Navigation')
  );
  return;
}
add_action('init', 'grunwell_custom_menus');

/**
 * Assemble the page's <title> attribute
 * @param str $sep The string separator to use
 * @return str
 */
function grunwell_page_title($sep='|'){
  if( is_front_page() ):
    return get_bloginfo('name') . " $sep " . get_bloginfo('description', 'display');
  else:
    return wp_title($sep, false, 'right') . get_bloginfo('name');
  endif;
}

/**
 * Get the tag for #site-logo
 * Will use a <h1> on the front page and <div> on the others
 * @return str
 */
function grunwell_sitelogo(){
  $tag = ( is_front_page() ? 'h1' : 'div' );
  return sprintf('<%s id="site-logo"><a href="%s"><img src="%s/img/site-logo.png" alt="%s" /></a></%s>', $tag, home_url('/'), get_bloginfo('template_url'), esc_attr(get_bloginfo('template_url')), $tag);
}

/**
 * Get the formatted date string for the post and output it wrapped in the HTML5 <time> element
 * When used within the loop the $date parameter is unnecessary
 * @global $post
 * @param str $date The date to format
 * @param bool $inc_time Include the post time? (default: true)
 * @param str $class CSS classes to apply to the <time> element
 * @return str
 */
function grunwell_get_the_date($date='', $inc_time=true, $class=''){
  global $post;
  $gmt = false;
  $format = 'F jS, Y' . ( $inc_time ? ' \a\\t g:ia' : '' );

  if( strtolower($date) <= 0 ){
    if( isset($post->post_date, $post->post_date_gmt) && strtotime($post->post_date) > 0 ){ // Use global $post object
      $date = $post->post_date;
      $gmt = strtotime($post->post_date_gmt);
    } else { // Use current time
      $date = date('Y-m-d H:i:s');
    }
  }
  $date = strtotime($date);

  if( !$gmt ){
    $offset = floatval(get_bloginfo('gmt_offset'));
    $gmt = $date + ($offset*60*60);
  }

  if( $class != '' ){
    $class = sprintf(' class="%s"', $class);
  }

  $return = sprintf('<time datetime="%s"%s>%s</time>', date('c', $gmt), $class, date($format, $date));
  return apply_filters('grunwell_get_the_date', $return);
}

/** Shortcut for echo grunwell_the_date() */
function grunwell_the_date($date='', $inc_time=true, $class=''){
  echo grunwell_get_the_date($date, $inc_time, $class);
  return;
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
add_filter('grunwell_get_the_date', 'grunwell_superscript_dates');

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
      'exclude_replies' => true,
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