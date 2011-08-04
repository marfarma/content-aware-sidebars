<?php
/**
 * @package Content Aware Sidebars
 */
/*
Plugin Name: Content Aware Sidebars
Plugin URI: http://www.intox.dk/
Description: Manage and show sidebars according to the content being viewed.
Version: 0.2
Author: Joachim Jensen
Author URI: http://www.intox.dk/
License:

    Copyright 2011  Joachim Jensen  (email : jv@intox.dk)

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
class ContentAwareSidebars {
	
	public $version = 0.2;
	public $settings = array();
	
	/**
	 *
	 * Constructor
	 *
	 */
	public function __construct() {
		
		add_filter('wp',			array(&$this,'replace_sidebar'));
		add_action('init',			array(&$this,'init_sidebar_type'));
		add_action('widgets_init',		array(&$this,'create_sidebars'));
		add_action('admin_init',		array(&$this,'create_meta_boxes'));
		add_action('admin_head',		array(&$this,'init_settings'));
		add_action( 'admin_menu', array(&$this,'clear_admin_menu') );

		add_action('save_post', 		array(&$this,'save_post'));
		
		register_activation_hook(__FILE__,	array(&$this,'upon_activation'));
		
	}
	
	/**
	 *
	 * Create post meta fields
	 * Loaded in admin_head due to $post. Should be loaded even later if possible.
	 *
	 */
	public function init_settings() {
		global $post, $wp_registered_sidebars;

		// List of sidebars
		$sidebar_list = array();
		foreach($wp_registered_sidebars as $sidebar) {
			if(isset($post) && $sidebar['id'] != 'ca-sidebar-'.$post->ID)
				$sidebar_list[$sidebar['id']] = $sidebar['name'];
		}
		
		// List of public post types
		$post_type_list = array();
		foreach(get_post_types(array('public'=>true),'objects') as $post_type)
			$post_type_list[$post_type->name] = $post_type->label; 
		
		// Meta fields
		$this->settings = array(
			'post_types'	=> array(
				'name'	=> 'Post Types',
				'id'	=> 'post_types',
				'desc'	=> '',
				'val'	=> '',
				'type'	=> 'select-multi',
				'list'	=> $post_type_list
			),
			'handle'	=> array(
				'name'	=> 'Handle',
				'id'	=> 'handle',
				'desc'	=> 'Replace host sidebar, merge with it or add sidebar manually.',
				'val'	=> 0,
				'type'	=> 'select',
				'list'	=> array('Replace','Merge','Manual')
			),
			'host'		=> array(
				'name'	=> 'Host Sidebar',
				'id'	=> 'host',
				'desc'	=> 'The sidebar that should be handled with. Nesting is possible. Manual handling makes this option superfluous.',
				'val'	=> 'sidebar-1',
				'type'	=> 'select',
				'list'	=> $sidebar_list
			),
			'merge-pos'	=> array(
				'name'	=> 'Merge position',
				'id'	=> 'merge-pos',
				'desc'	=> 'Place sidebar on top or bottom of host when merging.',
				'val'	=> 1,
				'type'	=> 'select',
				'list'	=> array('Top','Bottom')
			)
		);
	}
	
	/**
	 *
	 * Custom Post Type: Sidebar
	 *
	 */
	public function init_sidebar_type() {
		global $submenu;
		register_post_type('sidebar',array(
			'labels'	=> array(
				'name'			=> _x('Sidebars', 'post type general name'),
				'singular_name'		=> _x('Sidebar', 'post type singular name'),
				'add_new'		=> _x('Add New', 'sidebar'),
				'add_new_item'		=> __('Add New Sidebar'),
				'edit_item'		=> __('Edit Sidebar'),
				'new_item'		=> __('New Sidebar'),
				'all_items'		=> __('All Sidebars'),
				'view_item'		=> __('View Sidebar'),
				'search_items'		=> __('Search Sidebars'),
				'not_found'		=>  __('No sidebars found'),
				'not_found_in_trash'	=> __('No sidebars found in Trash'), 
				'parent_item_colon'	=> '',
				'menu_name'		=> 'Sidebars'
			),
			'show_ui'	=> true, 
			'query_var'	=> false,
			'rewrite'	=> false,
			'menu_position' => null,
			'supports'	=> array('title','excerpt','page-attributes'),
			'taxonomies'	=> get_taxonomies(array('public'=>true))
		));
		
		
		
	}

	/**
	 *
	 * Remove taxonomy shortcuts from menu. Gets too cluttered.
	 *
	 */
	function clear_admin_menu() {
		$taxonomies = get_taxonomies(array('public'=>true));
		foreach($taxonomies as $tax)
			remove_submenu_page('edit.php?post_type=sidebar','edit-tags.php?taxonomy='.$tax.'&amp;post_type=sidebar');
		

	}
	
	/**
	 *
	 * Create sidebars from content types
	 *
	 */
	public function create_sidebars() {
		$posts = get_posts(array(
			'numberposts'	=> 0,
			'post_type'	=> 'sidebar',
			'post_status'	=> array('publish','future')
		));
		foreach($posts as $post)
			register_sidebar( array(
				'name'		=> $post->post_title,
				'description'	=> $post->post_excerpt,
				'id'		=> 'ca-sidebar-'.$post->ID,
				'before_widget'	=> '<li id="%1$s" class="widget-container %2$s">',
				'after_widget'	=> '</li>',
				'before_title'	=> '<h3 class="widget-title">',
				'after_title'	=> '</h3>',
			));
	}
	
	/**
	 *
	 * Replace a sidebar with content aware sidebars
	 * Handles: replace, merge.
	 *
	 */
	public function replace_sidebar() {
		global  $wp_query, $post_type, $_wp_sidebars_widgets;
		
		// Archives are not supported yet.
		if(!is_singular())
			return;
		
		$handled_already = array();
		$content_type = get_post_type();
		$post_terms = get_object_taxonomies($content_type);
		
		$posts = get_posts(array(
			'numberposts'	=> 0,
			'post_type'	=> 'sidebar',
			'orderby'	=> 'menu_order meta_value',
			'meta_key'	=> 'handle',
			'order'		=> 'ASC',
			'meta_query'	=> array(
				array(
					'key'		=> 'handle',
					'value'		=> '2',
					'compare'	=> '!='
				)
			)
		));
		
		foreach($posts as $post) {
			
			$continue = 1;
			$id = 'ca-sidebar-'.$post->ID;
			
			// Check if sidebar exists
			if (!isset($_wp_sidebars_widgets[$id]))
				continue;
			
			$post_types = (array) unserialize(get_post_meta($post->ID, 'post_types', true));
			
			// Check if current post type is part of rules
			if(in_array($content_type,$post_types)) {
				$continue--;
			// Check if post has any taxonomies at all
			} elseif($post_terms) {
					
				$sorted_terms = array();
				$post_terms = wp_get_object_terms(get_the_ID(),$post_terms);
				
				//Grab posts terms and split them in taxonomies
				foreach($post_terms as $term)
					$sorted_terms[$term->taxonomy][] = $term->slug;
				
				//Check if any of current terms is part of rules
				foreach($sorted_terms as $taxonomy => $terms) {
					if(has_term($terms,$taxonomy,$post->ID)) {
						$continue--;
						break;
					}
				}
			}
			
			// Final check
			if($continue)
				continue;
			
			$host = get_post_meta($post->ID, 'host', true);
			$handle = get_post_meta($post->ID, 'handle', true);	
			
			// If host has already been replaced, merge with it instead. Might change in future.
			if($handle || isset($handled_already[$host])) {
				$merge_pos = get_post_meta($post->ID, 'merge-pos', true);
				if($merge_pos)
					$_wp_sidebars_widgets[$host] = array_merge($_wp_sidebars_widgets[$host],$_wp_sidebars_widgets[$id]);
				else
					$_wp_sidebars_widgets[$host] = array_merge($_wp_sidebars_widgets[$id],$_wp_sidebars_widgets[$host]);
			} else {
				$_wp_sidebars_widgets[$host] = $_wp_sidebars_widgets[$id];
				$handled_already[$host] = 1;
			}		
		}		
	}
	
	/**
	 *
	 * Meta boxes for edit post
	 *
	 */
	public function create_meta_boxes() {
		global $post;
		
		add_meta_box(
			'ca-sidebar',
			'Options',
			array(&$this,'meta_box_content'),
			'sidebar',
			'normal',
			'high'
		);
		
	}
	
	public function meta_box_content() {
		
		$this->form_fields(array('post_types','handle','merge-pos','host'));
		
	}
	
	/**
	 *
	 * Create form fields
	 *
	 */
	private function form_fields($array) {
		global $post;
		
		// Use nonce for verification
		wp_nonce_field(basename(__FILE__),'_ca-sidebar-nonce');
		?>
		<table class="form-table"> 
		<?php
		$array = array_intersect_key($this->settings,array_flip($array));
		foreach($array as $setting) :
		
		$meta = get_post_meta($post->ID, $setting['id'], true);
		$current = $meta != '' ? $meta : $setting['val'];
		?>
			<tr valign="top">
				<th scope="row"><?php echo $setting['name'] ?></th>
				<td>
			<?php switch($setting['type']) :
				case 'select' :			
					echo '<select style="width:200px;" name="'.$setting['id'].'">'."\n";
					foreach($setting['list'] as $key => $value) {
						echo '<option value="'.$key.'"'.($key == $current ? ' selected="selected"' : '').'>'.$value.'</option>'."\n";
					}
					echo '</select>'."\n";
					break;
				case 'select-multi' :
					echo '<select multiple="multiple" size="5" style="width:200px;height:60px;" name="'.$setting['id'].'[]">'."\n";
					foreach($setting['list'] as $key => $value) {
						echo '<option value="'.$key.'"'.(in_array($key,unserialize($current)) ? ' selected="selected"' : '').'>'.$value.'</option>'."\n";
					}
					echo '</select>'."\n";
					break;
				case 'text' :
				default :
					echo '<input style="width:200px;" type="text" name="'.$setting['id'].'" value="'.implode(",",unserialize($current)).'" />'."\n";
					break;
			endswitch; ?>
				<br /><span class="description"><?php echo $setting['desc'] ?></span>
				</td>
			</tr>	
		<?php endforeach; ?>			
		</table>
	<?php
	}
	
	/**
	 *
	 * Save meta values for post
	 *
	 */
	public function save_post($post_id) {
		
		if(get_post_type($post_id) != 'sidebar')
			return $post_id;
		
		// Save button pressed
		if(!isset($_POST['original_publish'])) {
			return $post_id;
		}
		
		// Verify nonce
		if (!check_admin_referer(basename(__FILE__),'_ca-sidebar-nonce')) {
			return $post_id;
		}
		
		// Check permissions
		if (!current_user_can('edit_post', $post_id)) {
			return $post_id;
		}
		
		// Check autosave
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
			return $post_id;
		}
		
		// Load settings manually here. This ought to be done with action/filter
		$this->init_settings();
		
		// Update values
		foreach ($this->settings as $field) {
			$old = get_post_meta($post_id, $field['id'], true);			
			$new = $_POST[$field['id']];
			
			switch($field['id']) {	
				case 'post_types' :
					$new = serialize($new);
					break;
				default :
					break;
			}
			
			if ($new != '' && $new != $old) {
				update_post_meta($post_id, $field['id'], $new);		
			}elseif ($new == '' && $old != '') {
				delete_post_meta($post_id, $field['id'], $old);	
			}
		}
	}
	
	/**
	 *
	 * Flush rewrite rules on plugin activation
	 *
	 */
	public function upon_activation() {
		$this->init_sidebar_type();
		flush_rewrite_rules();
	}
	
}

// Launch plugin
global $ca_sidebars;
$ca_sidebars = new ContentAwareSidebars();

// Template function
function display_ca_sidebar($args) {
	global $wp_query, $post_type, $_wp_sidebars_widgets;
	
	$defaults = array (
 		'before'	=> '<div id="sidebar" class="widget-area"><ul class="xoxo">',
		'after'		=> '</ul></div>'
	);
	$args = wp_parse_args($args,$defaults);
	extract( $args, EXTR_SKIP );
	
	$posts = get_posts(array(
		'numberposts'	=> 0,
		'post_type'	=> 'sidebar',
		'orderby'	=> 'menu_order meta_value',
		'meta_key'	=> 'handle',
		'order'		=> 'ASC',
		'meta_query'	=> array(
			array(
				'key'		=> 'handle',
				'value'		=> '2',
				'compare'	=> '=='
			)
		)
	));
	$content_type = $post_type;
	if(!$content_type)
		$content_type = get_post_type();
		
	$i = $host = 0;
	foreach($posts as $post) {
			
		$id = 'ca-sidebar-'.$post->ID;
		$post_types = (array) unserialize(get_post_meta($post->ID, 'post_types', true));
		
		// Check if current post type is part of rules
		if(!in_array($content_type,$post_types))
			continue;	
		
		if($i > 0) {
							
			// Check if sidebar is active
			if (!isset($_wp_sidebars_widgets[$id]))
				continue;
				
			$merge_pos = get_post_meta($post->ID, 'merge-pos', true);
			if($merge_pos)
				$_wp_sidebars_widgets[$host] = array_merge($_wp_sidebars_widgets[$host],$_wp_sidebars_widgets[$id]);
			else
				$_wp_sidebars_widgets[$host] = array_merge($_wp_sidebars_widgets[$id],$_wp_sidebars_widgets[$host]);		
		} else {
			$host = $id;
		}
		$i++;
	}
	
	if ($host && is_active_sidebar($host)) {
		echo $before;
		dynamic_sidebar($host);
		echo $after;
	}
	
}

