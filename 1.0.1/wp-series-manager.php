<?php
/*
Plugin Name: Wordpress Series Manager
Plugin URI: http://www.wordpress.org/extend/plugins/wp-series-manager/
Description: A plugin for managing tutorial series and any other thing that can be published in a serial way
Version: 1.0
Author: Omid Reza Vasheghani Farahani
Author URI: http://www.omidr.ir
License: GPL2
*/

/*  Copyright 2012  Omid Reza Vasheghani Farahani  (email : omidrezav@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
function wp_series_manager_init()
{
	$plugin_dir = basename(dirname(__FILE__));
	load_plugin_textdomain( 'wp-series-manager', false, $plugin_dir.'/lang' );
}
add_action('plugins_loaded', 'wp_series_manager_init');

function wp_series_manager_activate()
{
	series_post_type(); //registering post type
	flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'wp_series_manager_activate' );

function wp_series_manager_deactivate() 
{
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'wp_series_manager_deactivate' );
/////////////////////////////////////////////////////////////////////////////////////////////////
/*******************************Block1 - Declaring New Post Type********************************/
/////////////////////////////////////////////////////////////////////////////////////////////////
function series_post_type()
{
	$labels = array('name' => __('Series', 'wp-series-manager'),
					'singular_name' => __('Serie', 'wp-series-manager'),
					'add_new' => __('Add new serie', 'wp-series-manager'),
					'all_items' => __('All series', 'wp-series-manager'),
					'add_new_item' => __('Add new serie', 'wp-series-manager'),
					'edit_item' => __('Edit serie', 'wp-series-manager'),
					'view_item' => __('View Serie', 'wp-series-manager'),
					'search_items' => __('Search series', 'wp-series-manager'),
					'not_found' => __('No series found', 'wp-series-manager'),
					'not_found_in_trash' => __('No series found in trash', 'wp-series-manager'),
					'menu_name' => __('Series', 'wp-series-manager'));
	$support = array('title', 'editor', 'comments', 'taxonomies');
	$taxonomies = array('category', 'post_tag');
	$args = array('labels' => $labels,
					'public' => true,
					'show_ui' => true,
					'menu_position' => 5,
					'supports' => $support,
					'taxonomies' => $taxonomies,
					'can_export' => true);
	register_post_type('wp-series', $args);
}
add_action ('init', 'series_post_type');
/////////////////////////////////////////////////////////////////////////////////////////////////
/*******************************End of Block1///////////////////////////////////////////////////*
/////////////////////////////////////////////////////////////////////////////////////////////////

/////////////////////////////////////////////////////////////////////////////////////////////////
/*******************************Block2 - Add Serie Selection to Post Edit Page******************/
/////////////////////////////////////////////////////////////////////////////////////////////////
function add_custom_meta_box()
{
	add_meta_box('series_meta_box', __('Series Manager', 'wp-series-manager'),
				'show_series_meta_box', 'post', 'side', 'core');
}
add_action('admin_init', 'add_custom_meta_box');

//showing meta box contents
function show_series_meta_box()
{
	global $post;
	$serie = get_post_meta($post->ID, 'serie', true);
	$series_manager_nonce = wp_create_nonce('series_manager_nonce');
	echo '<input type="hidden" name="series_manager_nonce" value="'	. $series_manager_nonce . '" />';
	echo '<label for="serie">'. __('This Post Belongs to Serie with ID: ', 'wp-series-manager') .'</label>';
	echo '<input name="serie" id="serie" style="width:35px" value="'. $serie .'" />';
}

//saving meta box data
function save_series_meta_box($post_id)
{
	// verify nonce
	if (!wp_verify_nonce($_POST['series_manager_nonce'],'series_manager_nonce'))
	{
		return;
	}
 
	// check autosave
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
	{
		return;
	}
 
	// check permissions
	if (!current_user_can('edit_post' , $post_id))
	{
		return;
	}
 
	$old = get_post_meta($post_id, 'serie', true);
	$new = $_POST['serie'];
 
	if ($new && $new != $old)
	{
		update_post_meta($post_id, 'serie', $new);
	}
	elseif ('' == $new && $old)
	{
		delete_post_meta($post_id, 'serie', $old);
	}
}
add_action( 'save_post', 'save_series_meta_box' );
/////////////////////////////////////////////////////////////////////////////////////////////////
/*******************************End of Block2///////////////////////////////////////////////////*
/////////////////////////////////////////////////////////////////////////////////////////////////

/////////////////////////////////////////////////////////////////////////////////////////////////
/*******************************Block3 - Show Posts in a Serie**********************************/
/////////////////////////////////////////////////////////////////////////////////////////////////
function add_belonging_posts_box()
{
	add_meta_box('belonging_posts', __('Belonging Posts', 'wp-series-manager'),
				'show_belonging_posts_box', 'wp-series', 'normal', 'core');
}
add_action('admin_init', 'add_belonging_posts_box');

//showing box contents
function show_belonging_posts_box()
{
	global $post;
	$id = $post -> ID;
	global $wpdb;
	$query = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'serie' AND meta_value = $id ORDER BY post_id ASC";
	$belonging_posts = $wpdb->get_results($query);
	echo '<ol>';
	foreach($belonging_posts as $belonging_post)
	{
		$id = $belonging_post->post_id;
		$belonging_item = get_post($id);
		echo '<li><a href="'. get_permalink($id) .'">'. $belonging_item->post_title .'</a> ';
		edit_post_link(__('Edit This'), null, null, $id);
		echo '</li>';
	}
	echo '</ol>';
}
/////////////////////////////////////////////////////////////////////////////////////////////////
/*******************************End of Block3///////////////////////////////////////////////////*
/////////////////////////////////////////////////////////////////////////////////////////////////

/////////////////////////////////////////////////////////////////////////////////////////////////
/*******************************Block4 - Adding Series Column To Posts Page*********************/
/////////////////////////////////////////////////////////////////////////////////////////////////
function series_column($columns)
{
	$screen = get_current_screen();
	$screen_id = $screen->id;
	if ($screen_id == 'edit-post')
	{
		$columns['serie'] = __('Belongs to', 'wp-series-manager');
	}
	return $columns;
}
add_filter('manage_posts_columns', 'series_column');

//displaying column contents
function series_column_contents($name)
{
	global $post;
	$screen = get_current_screen();
	$screen_id = $screen->id;
	if ($screen_id == 'edit-post')
	{
		if ($name == 'serie')
		{
			$serie = get_post_meta($post->ID, 'serie', True);
			echo $serie ? $serie : __('Nothing', 'wp-series-manager');
		}
	}
}
add_action('manage_posts_custom_column', 'series_column_contents');

/////////////////////////////////////////////////////////////////////////////////////////////////
/*******************************End of Block4///////////////////////////////////////////////////*
/////////////////////////////////////////////////////////////////////////////////////////////////

/////////////////////////////////////////////////////////////////////////////////////////////////
/*******************************Block5 - Showing a Serie in Front End***************************/
/////////////////////////////////////////////////////////////////////////////////////////////////
function front_end_serie($content)
{
	if(!is_single())
		return $content;
	if (get_post_type() == 'wp-series')
	{
		$id = get_the_ID();
		global $wpdb;
		$query = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'serie' AND meta_value = $id ORDER BY post_id ASC";
		$belonging_posts = $wpdb->get_results($query);
		$total_posts = $wpdb->num_rows;
		if ($total_posts == 0)
		{
			return $content;
		}
		$serie = '<div id="belonging"><p>'. __('These are posts belonging to this serie:', 'wp-series-manager') .'</p><ol>';
		$hidden_posts = 0;
		foreach($belonging_posts as $belonging_post)
		{
			$id = $belonging_post->post_id;
			$belonging_item = get_post($id);
			if ($belonging_item->post_status == 'publish')
			{
				$serie .= '<li><a href="'. get_permalink($id) .'">'. $belonging_item->post_title .'</a> ';
				$serie .= '</li>';
			}
			else
			{
				$hidden_posts++;
			}
		} 
		$serie .= '</ol></div>';
		if ($hidden_posts == $total_posts)
		{
			return $content;
		}
		$content .= $serie;
	}
	elseif (get_post_type() == 'post')
	{
		$post_id = get_the_ID();
		if(!(get_post_meta($post_id, 'serie', True) == ''))
		{
			$id = get_post_meta($post_id, 'serie', True);
			$content .= '<div id="parent-serie">';
			$content .= __('This post belongs to ', 'wp-series-manager').'<a href="'. get_permalink($id) .'">'. get_the_title($id) .'</a></div>';
			global $wpdb;
			$query = "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = 'serie' AND meta_value = $id ORDER BY post_id ASC";
			$belonging_posts = $wpdb->get_results($query);
			$total_posts = $wpdb->num_rows;
			if ($total_posts == 1)
			{
				return $content;
			}
			$serie .= '<div id="belonging"><p>'. __('These are posts belonging to the same serie:', 'wp-series-manager'). '</p><ol>';
			$hidden_posts = 1;
			foreach($belonging_posts as $belonging_post)
			{
				$id = $belonging_post->post_id;
				$belonging_item = get_post($id);
				if (($belonging_item->post_status == 'publish') && ($belonging_item->ID != $post_id))
				{
					$serie .= '<li><a href="'. get_permalink($id) .'">'. $belonging_item->post_title .'</a> ';
					$serie .= '</li>';
				}
				if ($belonging_item->post_status != 'publish')
					$hidden_posts++;
			}
			$serie .= '</ol></div>';
			if ($hidden_posts == $total_posts )
			{
				return $content;
			}
			$content .= $serie;
		}
	}
	return $content;
}
add_filter('the_content', 'front_end_serie');