<?php
/**
 * Plugin Name: LH Sitemaps
 * Plugin URI: https://lhero.org/portfolio/lh-sitemap/
 * Description: HTML and xml sitemaps done simply and right
 * Author: Peter Shaw
 * Version: 1.02
 * Author URI: https://shawfactor.com/
 * Text Domain: lh_sitemaps
 * Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if (!class_exists('LH_Sitemaps_plugin')) {


class LH_Sitemaps_plugin {

private static $instance;


static function write_log($log) {
        if (true === WP_DEBUG) {
            if (is_array($log) || is_object($log)) {
                error_log(print_r($log, true));
            } else {
                error_log($log);
            }
        }
    }
    
static function ping_and_log($url) {
    
$response = wp_remote_request( $url );
		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 === $code ) {
			self::write_log("ping of ".$url." was succesful");
		} else {
		    self::write_log("ping of ".$url." failed");
		    
		}
        
        
    }


static function get_post_types($remove_types = array("attachment")) {
    
$posttypes = get_post_types( array('public'   => true ), 'names' );

$simple_array = array();

foreach ($posttypes as $posttype){
    
$simple_array[] = $posttype;
    
}

$posttypes = array_diff($simple_array, $remove_types);

return apply_filters( 'lh_sitemaps_posttypes_filter', $posttypes );
    
    
}

static function get_child_objects($parent_id, $post_type) {
    
$args = array(

    'post_parent'       => $parent_id, 
    'post_type'        => $post_type,
    'post_type' => 'any',
    'order'             => 'ASC',
    'orderby'           => 'menu_order',
    'ignore_sticky_posts' => 1,
    'posts_per_page'    => -1

);   


    
$children = new WP_Query($args);



//print_r($children);

if ( $children->have_posts() ) {
    
echo "
<ul>
";
    
    	while ( $children->have_posts() ) {
	    
        $children->the_post();
        
        ?>
<li><a href="<?php echo get_the_permalink(); ?>"><?php echo get_the_title(); ?></a><?php  self::get_child_objects(get_the_ID(), $post_type); ?></li>
<?php
        
}
    
echo "
</ul>
";
	
}
    
    
}

static function get_ping_urls() {
    
    $urls = array('https://www.google.com/ping','https://www.bing.com/ping');
    
    return apply_filters( 'lh_sitemaps_ping_urls_filter', $urls );
    
    
}



private function get_sitemap_general_url(){
    
return get_home_url(null,  '/sitemap-general.xml');
    
}


private function get_sitemap_news_url(){
    
    
return get_home_url(null,  '/sitemap-news.xml');
    
    
    
}




public function general_feed() {
    
load_template(dirname(__FILE__) . '/templates/lh-sitemaps-general.php');
    
    
    
}



public function news_feed() {
    
load_template(dirname(__FILE__) . '/templates/lh-sitemaps-news.php');
    
    
    
}

public function init() {
    
global $wp_rewrite; 
    
add_rewrite_rule('sitemap-general\.xml$', $wp_rewrite->index . '?feed=lh-sitemaps-general', 'top');
add_rewrite_rule('sitemap-news\.xml$', $wp_rewrite->index . '?feed=lh-sitemaps-news', 'top');

add_feed('lh-sitemaps-general', array($this, 'general_feed'));
add_feed('lh-sitemaps-news', array($this, 'news_feed'));



}

public function robots_txt( $output, $public ) {
    
$add = 'Sitemap: '.$this->get_sitemap_general_url().'
Sitemap: '.$this->get_sitemap_news_url();

$add .= '

';

$output = $add.$output;
    
    
   return $output; 
    
}

public function html_sitemap_output($atts, $content = "") {
    
$atts = extract( shortcode_atts( array(), $atts ) );
    
    
ob_start(); 

$types = LH_Sitemaps_plugin::get_post_types(array("attachment"));

foreach ( $types as $type ) {
    


$obj = get_post_type_object( $type );

if ($type == 'post'){

$orderby = 'date';  
    
} else {
    
$orderby = 'modified';     
    
}

$args = array(
'post_type'        => $type,
'posts_per_page'=> -1,
'orderby'     => $orderby,
'order'       => 'DESC',
'ignore_sticky_posts' => 1,
);

if (!empty($obj->hierarchical)) {
    
$args['post_parent'] = 0;
    
    
}

$the_query = new WP_Query( $args ); 



if ( $the_query->have_posts() ) {
    
echo "<h3>".$obj->labels->name."</h3>";
    
echo "<ul>";


	while ( $the_query->have_posts() ) {
	    
$the_query->the_post();

?>
<li><a href="<?php echo get_the_permalink(); ?>"><?php echo get_the_title(); ?></a><?php

if (!empty($obj->hierarchical)) {
    


self::get_child_objects(get_the_ID(), $type);

}
?></li>
<?php




}

echo "</ul>";




}
    
    
    
}

if (function_exists('bp_is_active') && bp_is_active('groups') ) {
    
$args = array( 'type' => 'alphabetical' ); 
    
$listed_groups = BP_Groups_Group::get($args); 


if ($listed_groups['groups'] > 0){

echo "<h3>Groups</h3>";
    
 echo '
 <ul>
 ';   

foreach ($listed_groups['groups'] as $group){ 
    
echo '<li><a href="'.bp_get_group_permalink( $group ) .'">'.$group->name.'</a></li>';
    
    
}

echo '
</ul>
';

}
    
}



$return_string = ob_get_contents();

ob_end_clean();

return $return_string;
    
    
}

public function schedule_ping( $post_ID, $post, $update ) {

		// verify if this is an auto save routine
		// do not take action until the form is submitted
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		
$post_types = self::get_post_types();


if (isset($post->post_type) && (in_array($post->post_type,$post_types) )){
    
wp_schedule_single_event( time(), 'lh_sitemaps_ping_search_engines' ); 
    
}


}

public function ping_search_engines(){
    
$ping_urls = self::get_ping_urls(); 

foreach ( $ping_urls as $ping_url ) {
    
$general_ping = add_query_arg( 'sitemap', urlencode($this->get_sitemap_general_url()), $ping_url );

self::ping_and_log($general_ping);

$news_ping = add_query_arg( 'sitemap', urlencode($this->get_sitemap_news_url()), $ping_url );

self::ping_and_log($news_ping);
    
}
    
    
}


public function register_shortcodes(){


//register the html sitemap shortcode
add_shortcode('lh_html_sitemap', array($this,'html_sitemap_output'));


}

public function plugins_loaded(){
         
//add the feeds and rewrite rules
add_action('init', array($this, 'init'));

//add the sitemaps to robots.txt
add_filter( 'robots_txt', array($this, 'robots_txt'), 10, 2 );

//schedule a ping when a public post is updated
add_action( 'save_post', array($this, 'schedule_ping'), 10, 3 );

//add processing to the cron job
add_action( 'lh_sitemaps_ping_search_engines', array($this, 'ping_search_engines'));

//register the html sitemap shortcode
add_action( 'init', array($this,'register_shortcodes'));



        
} 



  /**
     * Gets an instance of our plugin.
     *
     * using the singleton pattern
     */
    public static function get_instance(){
        if (null === self::$instance) {
            self::$instance = new self();
        }
 
        return self::$instance;
    }


public function __construct() {
    
    
	 //run our hooks on plugins loaded to as we may need checks       
    add_action( 'plugins_loaded', array($this,'plugins_loaded'));
    



    
    
}


}

$lh_sitemaps_instance = LH_Sitemaps_plugin::get_instance();

}



?>