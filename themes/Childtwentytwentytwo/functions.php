<?php
add_action( 'wp_enqueue_scripts', 'my_theme_enqueue_styles' );
function my_theme_enqueue_styles() {
	$parenthandle = 'parent-style'; // This is 'childtwentytwentytwo-style' for the Child Twenty Twenty two theme.
	$theme        = wp_get_theme();
	wp_enqueue_style( $parenthandle,
		get_template_directory_uri() . '/style.css',
		array(),  // If the parent theme code has a dependency, copy it to here.
		$theme->parent()->get( 'Version' )
	);
	wp_enqueue_style( 'child-style',
		get_stylesheet_uri(),
		array( $parenthandle ),
		$theme->get( 'Version' ) // This only works if you have Version defined in the style header.
	);
}

function kitchen_function() {
    
	$post_list = get_posts( array(
		'orderby'    => 'menu_order',
		'sort_order' => 'asc',
		'post_type'	=>'kitchen_customers'
	) );
	
	$posts = array();
	
	$result = '<table border="1" cellspacing="0" cellpadding="5">';
	foreach ( $post_list as $post ) {
	$result .= '<tr><td>'.$post->ID.'</td>';
	$result .= '<td>';
	$myvals = get_post_meta($post->ID);
	foreach($myvals as $key=>$val)
	{
		if($key == 'pack_data'){
			$pack_data = json_decode($val[0], TRUE);
                
            foreach ($pack_data as $pack_d) {
			$result .= $pack_d['ingredient'] . ' - ' . $pack_d['inventory_code'] . ' - ' . $pack_d['quantity'] . ' - ' . $pack_d['unit'] .'<br/><br/>' ;
			}
		}
	}
	$result .= '</td>';
	$result .= '</tr>';
	}
	$result .= '</table>';
	
	return $result;
	 
 }
 
 add_shortcode('kitchen', 'kitchen_function');