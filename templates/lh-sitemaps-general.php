<?php
header('Content-Type: application/xml; charset=' . get_option('blog_charset'), true);

echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>
';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.google.com/schemas/sitemap-image/1.1 http://www.google.com/schemas/sitemap-image/1.1/sitemap-image.xsd"><?php 

$types = LH_Sitemaps_plugin::get_post_types(array("attachment"));

$exclude = array();

if ( class_exists( 'woocommerce' ) ) { 
    
$exclude[] = wc_get_page_id( 'cart' );
$exclude[] = wc_get_page_id( 'checkout' );
    
    
}


$exclude = array_unique(array_filter($exclude, 'is_int'));

$args = array(
'post_type'        => $types,
'posts_per_page'=> -1,
'post__not_in' => $exclude,
'orderby'     => 'modified',
'order'       => 'DESC',
'tax_query'  => array(
    'taxonomy' => 'lh_html_meta_tags-noindex', 
    'terms' => 'yes',
    'field' => 'slug',
   'operator' => 'NOT IN',
    
    )

);


$args = apply_filters('lh_sitemaps_general_args', $args);

$the_query = new WP_Query( $args ); 

// The Loop
if ( $the_query->have_posts() ) {

$count = 0;

	while ( $the_query->have_posts() ) {
	    
 $count++;
		$the_query->the_post();
		echo '
<url>
<loc>' . get_the_permalink() . '</loc>
<lastmod>' . get_post_modified_time( 'Y-m-d\TH:i:sP', true ) . '</lastmod>';
		
if ($count<15) {
echo '
<changefreq>daily</changefreq>
<priority>0.8</priority>';
 } else {
     
echo '
<changefreq>weekly</changefreq>
<priority>0.5</priority>';
     
 }
 
 $images = get_children( array (
		'post_parent' => $post->ID,
		'post_type' => 'attachment',
		'post_mime_type' => 'image'
	));

 if ( empty($images) ) {
		// no attachments here
	} else {
	    
	    
		foreach ( $images as $image ) {
		    echo '
		    <image:image>
		    <image:loc>'.strtok(wp_get_attachment_image_url( $image->ID, 'full' ), '?').'</image:loc>
		    <image:title>'.get_the_title($image->ID ).'</image:title>
		    </image:image>';
		}
		
		  
	}
 
 unset($images);
 
		
echo '
</url>';
	}

	/* Restore original Post Data */
	wp_reset_postdata();
} else {
	// no posts found
}

if (function_exists('bp_is_active') && bp_is_active('groups') ) {
    
$listed_groups = BP_Groups_Group::get(); 

foreach ($listed_groups['groups'] as $group){ 
    
    echo '
<url>
<loc>' . bp_get_group_permalink( $group ) . '</loc>
<lastmod>' . date( 'Y-m-d\TH:i:sP', strtotime(groups_get_groupmeta( $group->id, 'last_activity'))). '</lastmod>
<changefreq>weekly</changefreq>
<priority>0.7</priority>
</url>';
    
    
}
    
}

do_action( 'lh_sitemaps_after_loop' );

?>
</urlset>