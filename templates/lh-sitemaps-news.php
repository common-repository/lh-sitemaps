<?php
header('Content-Type: application/xml; charset=' . get_option('blog_charset'), true);

echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>';
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
	xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"
	xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"
	xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
	xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
		http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd
		http://www.google.com/schemas/sitemap-news/0.9
		http://www.google.com/schemas/sitemap-news/0.9/sitemap-news.xsd
		http://www.google.com/schemas/sitemap-image/1.1
		http://www.google.com/schemas/sitemap-image/1.1/sitemap-image.xsd"><?php
		
		$args = array(
'post_type'        => 'post',
'posts_per_page'=> -1
);


$args = apply_filters('lh_sitemaps_news_args', $args);

$the_query = new WP_Query( $args );


// The Loop
if ( $the_query->have_posts() ) {

$count = 0;

	while ( $the_query->have_posts() ) {
	    
	    $the_query->the_post();
	    
	    
echo '
<url>
<loc>'.get_the_permalink().'</loc>';

echo '
<news:news>
<news:publication>
<news:name>'.get_bloginfo('name').'</news:name>
<news:publication_date>' . get_post_time( 'Y-m-d\TH:i:sP', true ) . '</news:publication_date>
<news:title>' . get_the_title(). '</news:title>
</news:publication>
</news:news>';




echo '
</url>';
	    
	    
	    
	}
	
	
	/* Restore original Post Data */
	wp_reset_postdata();
} else {
	// no posts found
}

?>
</urlset>