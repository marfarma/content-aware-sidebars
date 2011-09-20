<?php
/**
 * @package Content Aware Sidebars
 */
/*
Plugin Name: Content Aware Sidebars
Plugin URI: http://www.intox.dk/
Description: Manage and show sidebars according to the content being viewed.
Version: 0.6.1
Author: Joachim Jensen
Author URI: http://www.intox.dk/
Text Domain: content-aware-sidebars
Domain Path: /lang/
License: GPL2

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
	
	protected $settings		= array();
	protected $post_types		= array();
	protected $post_type_objects	= array();
	protected $taxonomies		= array();
	protected $taxonomy_objects	= array();
	
	/**
	 *
	 * Constructor
	 *
	 */
	public function __construct() {
		
		$this->load_dependencies();
		
		add_filter('wp',					array(&$this,'replace_sidebar'));
		add_filter('request',					array(&$this,'admin_column_orderby'));
		add_filter('default_hidden_meta_boxes',			array(&$this,'change_default_hidden'),10,2);	
		add_filter('manage_edit-sidebar_columns',		array(&$this,'admin_column_headers'));
		add_filter('manage_edit-sidebar_sortable_columns',	array(&$this,'admin_column_headers'));
		add_filter('manage_posts_custom_column',		array(&$this,'admin_column_rows'),10,3);
		add_filter('post_row_actions',				array(&$this,'sidebar_row_actions'),10,2);
		add_filter('post_updated_messages', 			array(&$this,'sidebar_updated_messages'));
		
		add_action('init',					array(&$this,'init_sidebar_type'),50);
		add_action('widgets_init',				array(&$this,'create_sidebars'));
		add_action('admin_init',				array(&$this,'create_meta_boxes'));
		add_action('admin_head',				array(&$this,'init_metadata'));
		add_action('admin_menu',				array(&$this,'clear_admin_menu'));
		add_action('save_post', 				array(&$this,'save_post'));
		
		register_activation_hook(__FILE__,			array(&$this,'upon_activation'));
		
	}

	
	/**
	 *
	 * Initiate lists
	 *
	 */
	private function init_settings() {		
		// Public post types
		foreach(get_post_types(array('public'=>true),'objects') as $post_type) {
			$this->post_types[$post_type->name] = $post_type->label;
			$this->post_type_objects[$post_type->name] = $post_type;
		}
		
		// Public taxonomies
		foreach(get_taxonomies(array('public'=>true),'objects') as $tax) {
			$this->taxonomies[$tax->name] = $tax->label;
			$this->taxonomy_objects[$tax->name] = $tax;
		}
	}
	
	/**
	 *
	 * Create post meta fields
	 * Loaded in admin_head due to $post. Should be loaded even later if possible.
	 *
	 */
	public function init_metadata() {
		global $post, $wp_registered_sidebars;

		// List of sidebars
		$sidebar_list = array();
		foreach($wp_registered_sidebars as $sidebar) {
			if(isset($post) && $sidebar['id'] != 'ca-sidebar-'.$post->ID)
				$sidebar_list[$sidebar['id']] = $sidebar['name'];
		}
		
		// Meta fields
		$this->settings = array(
			'post_types'	=> array(
				'name'	=> __('Post Types', 'content-aware-sidebars'),
				'id'	=> 'post_types',
				'desc'	=> '',
				'val'	=> array(),
				'type'	=> 'checkbox',
				'list'	=> $this->post_types
			),
			'taxonomies'	=> array(
				'name'	=> __('Taxonomies', 'content-aware-sidebars'),
				'id'	=> 'taxonomies',
				'desc'	=> '',
				'val'	=> array(),
				'type'	=> 'checkbox',
				'list'	=> $this->taxonomies
			),
			'static'	=> array(
				'name'	=> __('Static Pages', 'content-aware-sidebars'),
				'id'	=> 'static',
				'desc'	=> '',
				'val'	=> array(),
				'type'	=> 'checkbox',
				'list'	=> array(
					'front-page'	=> __('Front Page', 'content-aware-sidebars'),
					'search'	=> __('Search Results', 'content-aware-sidebars'),
					'404'		=> __('404 Page', 'content-aware-sidebars')
				)
			),
			'exposure'	=> array(
				'name'	=> __('Exposure', 'content-aware-sidebars'),
				'id'	=> 'exposure',
				'desc'	=> __('Affects post types, taxonomies and taxonomy terms.', 'content-aware-sidebars'),
				'val'	=> 1,
				'type'	=> 'select',
				'list'	=> array(
					 __('Singular', 'content-aware-sidebars'),
					 __('Singular & Archive', 'content-aware-sidebars'),
					 __('Archive', 'content-aware-sidebars')
				 )
			),
			'handle'	=> array(
				'name'	=> _x('Handle','option', 'content-aware-sidebars'),
				'id'	=> 'handle',
				'desc'	=> __('Replace host sidebar, merge with it or add sidebar manually.', 'content-aware-sidebars'),
				'val'	=> 0,
				'type'	=> 'select',
				'list'	=> array(
					__('Replace', 'content-aware-sidebars'),
					__('Merge', 'content-aware-sidebars'),
					__('Manual', 'content-aware-sidebars')
				)
			),
			'host'		=> array(
				'name'	=> __('Host Sidebar', 'content-aware-sidebars'),
				'id'	=> 'host',
				'desc'	=> __('The sidebar that should be handled. Nesting is possible. Manual handling makes this option superfluous.', 'content-aware-sidebars'),
				'val'	=> 'sidebar-1',
				'type'	=> 'select',
				'list'	=> $sidebar_list
			),
			'merge-pos'	=> array(
				'name'	=> __('Merge position', 'content-aware-sidebars'),
				'id'	=> 'merge-pos',
				'desc'	=> __('Place sidebar on top or bottom of host when merging.', 'content-aware-sidebars'),
				'val'	=> 1,
				'type'	=> 'select',
				'list'	=> array(
					__('Top', 'content-aware-sidebars'),
					__('Bottom', 'content-aware-sidebars')
				)
			)
		);
	}
	
	/**
	 *
	 * Custom Post Type: Sidebar
	 *
	 */
	public function init_sidebar_type() {
		
		load_plugin_textdomain('content-aware-sidebars', false, dirname( plugin_basename(__FILE__)).'/lang/');
		
		$this->init_settings();
		
		register_post_type('sidebar',array(
			'labels'	=> array(
				'name'			=> __('Sidebars', 'content-aware-sidebars'),
				'singular_name'		=> __('Sidebar', 'content-aware-sidebars'),
				'add_new'		=> _x('Add New', 'sidebar', 'content-aware-sidebars'),
				'add_new_item'		=> __('Add New Sidebar', 'content-aware-sidebars'),
				'edit_item'		=> __('Edit Sidebar', 'content-aware-sidebars'),
				'new_item'		=> __('New Sidebar', 'content-aware-sidebars'),
				'all_items'		=> __('All Sidebars', 'content-aware-sidebars'),
				'view_item'		=> __('View Sidebar', 'content-aware-sidebars'),
				'search_items'		=> __('Search Sidebars', 'content-aware-sidebars'),
				'not_found'		=> __('No sidebars found', 'content-aware-sidebars'),
				'not_found_in_trash'	=> __('No sidebars found in Trash', 'content-aware-sidebars')
			),
			'show_ui'	=> true, 
			'query_var'	=> false,
			'rewrite'	=> false,
			'menu_position' => null,
			'supports'	=> array('title','excerpt','page-attributes'),
			'taxonomies'	=> array_flip($this->taxonomies),
			'menu_icon'	=> WP_PLUGIN_URL.'/'.plugin_basename(dirname(__FILE__)).'/icon-16.png'
		));		
	}
	
	/**
	 *
	 * Create update messages
	 *
	 */
	function sidebar_updated_messages( $messages ) {
		global $post;
		$messages['sidebar'] = array(
			0 => '',
			1 => sprintf(__('Sidebar updated. <a href="%s">Manage widgets</a>','content-aware-sidebars'),'widgets.php'),
			2 => '',
			3 => '',
			4 => __('Sidebar updated.','content-aware-sidebars'),
			5 => '',
			6 => sprintf(__('Sidebar published. <a href="%s">Manage widgets</a>','content-aware-sidebars'), 'widgets.php'),
			7 => __('Sidebar saved.','content-aware-sidebars'),
			8 => sprintf(__('Sidebar submitted. <a href="%s">Manage widgets</a>','content-aware-sidebars'),'widgets.php'),
			9 => sprintf(__('Sidebar scheduled for: <strong>%1$s</strong>. <a href="%2$s">Manage widgets</a>','content-aware-sidebars'),
			// translators: Publish box date format, see http://php.net/date
			date_i18n(__('M j, Y @ G:i'),strtotime($post->post_date)),'widgets.php'),
			10 => sprintf(__('Sidebar draft updated. <a href="%s">Manage widgets</a>','content-aware-sidebars'),'widgets.php'),
		);
		return $messages;
	}

	/**
	 *
	 * Remove taxonomy shortcuts from menu and standard meta boxes.
	 *
	 */
	function clear_admin_menu() {
		foreach($this->taxonomies as $key => $value) {
			remove_submenu_page('edit.php?post_type=sidebar','edit-tags.php?taxonomy='.$key.'&amp;post_type=sidebar');
			remove_meta_box('tagsdiv-'.$key, 'sidebar', 'side');
			remove_meta_box($key.'div', 'sidebar', 'side');
		}
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
			'post_status'	=> array('publish','private','future')
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
	 * Add (sortable) admin column headers
	 *
	 */
	public function admin_column_headers($columns) {
		unset($columns['categories'],$columns['tags']);
		return array_merge(
			array_slice($columns, 0, 2, true),
			array(
				'exposure'	=> __('Exposure', 'content-aware-sidebars'),
				'handle'	=> _x('Handle','option', 'content-aware-sidebars'),
				'merge-pos'	=> __('Merge position', 'content-aware-sidebars')
			),
			$columns
		);
	}
	
	/**
	 * Manage custom column sorting
	 */
	public function admin_column_orderby($vars) {
		if (isset($vars['orderby']) && in_array($vars['orderby'],array('exposure','handle','merge-pos'))) {
			$vars = array_merge( $vars, array(
				'meta_key'	=> $vars['orderby'],
				'orderby'	=> 'meta_value'
			) );
		}
		return $vars;
	}
	
	/**
	 *
	 * Add admin column rows
	 *
	 */
	public function admin_column_rows($column_name,$post_id) {
		
		// Fix for quick edit
		if(!$this->settings) $this->init_metadata();
		
		$current = get_post_meta($post_id,$column_name,true);
		$current_from_list = $this->settings[$column_name]['list'][$current];
		
		switch($column_name) {
			case 'handle':		
				$host = $this->settings['host']['list'][get_post_meta($post_id,'host',true)];		
				if($current == 0) {
					printf(__("Replace %s",'content-aware-sidebars'),$host);
				} elseif($current == 1) {
					printf(__("Merge with %s",'content-aware-sidebars'),$host);
				} else {
					echo $current_from_list;
				}	
				break;
			case 'exposure':
			case 'merge-pos':
				echo $current_from_list;
				break;
		}
	}
	
	/**
	 *
	 * Add admin rows actions
	 *
	 */
	public function sidebar_row_actions($actions, $post) {
		if($post->post_type == 'sidebar') {
			return array_merge(
				array_slice($actions, 0, 2, true),
				array(
				      'mng_widgets' => 	'<a href="widgets.php" title="'.esc_html(__( 'Manage Widgets','content-aware-sidebars')).'">'.__( 'Manage Widgets','content-aware-sidebars').'</a>'
				),
				$actions
			);
		}
		return $actions;
	}

	/**
	 *
	 * Replace a sidebar with content aware sidebars
	 * Handles: replace, merge.
	 *
	 */
	public function replace_sidebar() {
		global $_wp_sidebars_widgets;
		
		$posts = $this->get_sidebars();
		if(!$posts)
			return;
		
		foreach($posts as $post) {
	
			$id = 'ca-sidebar-'.$post->ID;
			
			// Check if sidebar exists
			if (!isset($_wp_sidebars_widgets[$id]))
				continue;
			
			// If host has already been replaced, merge with it instead. Might change in future.
			if($post->handle || isset($handled_already[$post->host])) {
				if($post->merge_pos)
					$_wp_sidebars_widgets[$post->host] = array_merge($_wp_sidebars_widgets[$post->host],$_wp_sidebars_widgets[$id]);
				else
					$_wp_sidebars_widgets[$post->host] = array_merge($_wp_sidebars_widgets[$id],$_wp_sidebars_widgets[$post->host]);
			} else {
				$_wp_sidebars_widgets[$post->host] = $_wp_sidebars_widgets[$id];
				$handled_already[$post->host] = 1;
			}		
		}
	}
	
	/**
	 *
	 * Query sidebars according to content
	 * @return array|bool
	 *
	 */
	public function get_sidebars($handle = "!= '2'") {
		global $wpdb, $post_type;
		
		$errors = 1;
		
		$joins = "";
		$where = "";
		
		// Front page
		if(is_front_page()) {
			
			$joins .= "LEFT JOIN $wpdb->postmeta static ON static.post_id = posts.ID AND static.meta_key = 'static' ";
			
			$where .= "(static.meta_value LIKE '%".serialize('front-page')."%') AND ";
			$where .= "exposure.meta_value <= '1' AND ";
			
			$errors--;	
		
		// Single content
		} elseif(is_singular()) {
			
			$joins .= "LEFT JOIN $wpdb->postmeta post_types ON post_types.post_id = posts.ID AND post_types.meta_key = 'post_types' ";
			$where .= "(post_types.meta_value LIKE '%".serialize(get_post_type())."%'";			
			$where .= " OR post_types.meta_value LIKE '%".serialize((string)get_the_ID())."%'";
			
			$post_taxonomies = get_object_taxonomies(get_post_type());
			
			// Check if content has any taxonomies supported
			if($post_taxonomies) {
				$post_terms = wp_get_object_terms(get_the_ID(),$post_taxonomies);
				// Check if content has any actual taxonomy terms
				if($post_terms) {
					$terms = array();
					$taxonomies = array();
					
					//Grab posts terms and make where rules for taxonomies.
					foreach($post_terms as $term) {
						$terms[] = $term->slug;
						if(!isset($taxonomies[$term->taxonomy])) {
							$where .= " OR post_tax.meta_value LIKE '%".$taxonomies[$term->taxonomy] = $term->taxonomy."%'";
						}
					}
					
					$joins .= "LEFT JOIN $wpdb->term_relationships term ON term.object_id = posts.ID ";
					$joins .= "LEFT JOIN $wpdb->term_taxonomy taxonomy ON taxonomy.term_taxonomy_id = term.term_taxonomy_id ";
					$joins .= "LEFT JOIN $wpdb->terms terms ON terms.term_id = taxonomy.term_id ";
					$joins .= "LEFT JOIN $wpdb->postmeta post_tax ON post_tax.post_id = posts.ID AND post_tax.meta_key = 'taxonomies'";
					
					$where .= " OR terms.slug IN('".implode("','",$terms)."')";
				}
			}
					
			$where .= ") AND ";
			$where .= "exposure.meta_value <= '1' AND ";
			
			$errors--;
			
		// Taxonomy archives
		} elseif(is_tax() || is_category() || is_tag()) {
			
			$term = get_queried_object();
			
			$joins .= "LEFT JOIN $wpdb->term_relationships term ON term.object_id = posts.ID ";
			$joins .= "LEFT JOIN $wpdb->term_taxonomy taxonomy ON taxonomy.term_taxonomy_id = term.term_taxonomy_id ";
			$joins .= "LEFT JOIN $wpdb->terms terms ON terms.term_id = taxonomy.term_id ";	
			$joins .= "LEFT JOIN $wpdb->postmeta post_tax ON post_tax.post_id = posts.ID AND post_tax.meta_key = 'taxonomies'";
				
			$where .= "(terms.slug = '$term->slug'";
			$where .= " OR post_tax.meta_value LIKE '%$term->taxonomy%'";
			$where .= ") AND ";
			$where .= "exposure.meta_value >= '1' AND ";
			
			$errors--;
			
		// Post Type archives
		} elseif(is_post_type_archive() || is_home()) {
			
			// Home has post as default post type
			if(!$post_type) $post_type = 'post';
			
			$joins .= "LEFT JOIN $wpdb->postmeta post_types ON post_types.post_id = posts.ID AND post_types.meta_key = 'post_types' ";
			
			$where .= "(post_types.meta_value LIKE '%$post_type%') AND ";
			$where .= "exposure.meta_value >= '1' AND ";
			
			$errors--;
		
		// Search
		} elseif(is_search()) {
			
			$joins .= "LEFT JOIN $wpdb->postmeta static ON static.post_id = posts.ID AND static.meta_key = 'static' ";
			
			$where .= "(static.meta_value LIKE '%".serialize('search')."%') AND ";
			$where .= "exposure.meta_value <= '1' AND ";
			
			$errors--;
			
		// 404
		} elseif(is_404()) {
			
			$joins .= "LEFT JOIN $wpdb->postmeta static ON static.post_id = posts.ID AND static.meta_key = 'static' ";
			
			$where .= "(static.meta_value LIKE '%".serialize('404')."%') AND ";
			$where .= "exposure.meta_value <= '1' AND ";
			
			$errors--;
		}
		
		// Check if any errors are left
		if($errors)
			return false;
		
		// Show private sidebars or not
		if(current_user_can('read_private_posts'))
			$post_status = "IN('publish','private')";
		else
			$post_status = "= 'publish'";		
		$where .= "posts.post_status ".$post_status." AND ";
		
		// Return proper sidebars
		return $wpdb->get_results("
			SELECT
				posts.ID,
				handle.meta_value handle,
				host.meta_value host,
				merge_pos.meta_value merge_pos
			FROM $wpdb->posts posts
			LEFT JOIN $wpdb->postmeta handle
				ON handle.post_id = posts.ID
				AND handle.meta_key = 'handle'
			LEFT JOIN $wpdb->postmeta host
				ON host.post_id = posts.ID
				AND host.meta_key = 'host'
			LEFT JOIN $wpdb->postmeta merge_pos
				ON merge_pos.post_id = posts.ID
				AND merge_pos.meta_key = 'merge-pos'
			LEFT JOIN $wpdb->postmeta exposure
				ON exposure.post_id = posts.ID
				AND exposure.meta_key = 'exposure'
			$joins
			WHERE
				posts.post_type = 'sidebar' AND
				$where
				handle.meta_value $handle
			GROUP BY posts.ID
			ORDER BY posts.menu_order ASC, handle.meta_value DESC, posts.post_date DESC
		");
	}
	
	/**
	 *
	 * Meta boxes for sidebar edit
	 *
	 */
	public function create_meta_boxes() {
		// Author Words
		add_meta_box(
			'ca-sidebar-author-words',
			__('Words from the author', 'content-aware-sidebars'),
			array(&$this,'meta_box_author_words'),
			'sidebar',
			'side',
			'high'
		);
		// Post Types
		foreach($this->post_type_objects as $post_type) {
			add_meta_box(
				'ca-sidebar-post-type-'.$post_type->name,
				$post_type->label,
				array(&$this,'meta_box_post_type'),
				'sidebar',
				'normal',
				'high',
				$post_type
			);
		}
		// Taxonomies
		foreach($this->taxonomy_objects as $tax) {
			add_meta_box(
				'ca-sidebar-tax-'.$tax->name,
				$tax->label,
				array(&$this,'meta_box_taxonomy'),
				'sidebar',
				'side',
				'default',
				$tax
			);
		}
		// Options
		add_meta_box(
			'ca-sidebar',
			__('Options', 'content-aware-sidebars'),
			array(&$this,'meta_box_content'),
			'sidebar',
			'normal',
			'high'
		);
	}
	
	/**
	 *
	 * Options content
	 *
	 */
	public function meta_box_content() {
		$this->form_fields();
	}
	
	/**
	 *
	 * Author words content
	 *
	 */
	public function meta_box_author_words() {
		// Use nonce for verification
		wp_nonce_field(basename(__FILE__),'_ca-sidebar-nonce');
		?>
		<div style="text-align:center;">
		<div><p><?php _e('If you love this plugin, please consider donating.', 'content-aware-sidebars'); ?></p></div>
		<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=KPZHE6A72LEN4&lc=US&item_name=WordPress%20Plugin%3a%20Content%20Aware%20Sidebars&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHosted"
		   target="_blank" title="PayPal - The safer, easier way to pay online!">
			<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" width="147" height="47" alt="PayPal - The safer, easier way to pay online!">	
		</a>
		</div>
		<?php
	}
	
	public function meta_box_taxonomy($post, $tax) {
		$meta = get_post_meta($post->ID, 'taxonomies', true);
		$current = $meta != '' ? $meta : array();
		
		$taxonomy = $tax['args']->name;
		
		$terms = get_terms( $taxonomy);

		if ( ! $terms || is_wp_error($terms) ) {
			echo '<p>' . __( 'No items.' ) . '</p>';
			
		} else {
		
			?>
	<div id="taxonomy-<?php echo $taxonomy; ?>" class="categorydiv">
		<ul id="<?php echo $taxonomy; ?>-tabs" class="category-tabs">
			<li class="hide-if-no-js"><a href="#<?php echo $taxonomy; ?>-pop" tabindex="3"><?php _e( 'Most Used' ); ?></a></li>
			<li class="tabs"><a href="#<?php echo $taxonomy; ?>-all" tabindex="3"><?php _e('View All');; ?></a></li>
		</ul>

		<div id="<?php echo $taxonomy; ?>-pop" class="tabs-panel" style="display: none;height:inherit;max-height:200px;">
			<ul id="<?php echo $taxonomy; ?>checklist-pop" class="categorychecklist form-no-clear" >
				<?php $popular_ids = cas_popular_terms_checklist($tax['args']); ?>
			</ul>
		</div>
		
		<div id="<?php echo $taxonomy; ?>-all" class="tabs-panel" style="height:inherit;max-height:200px;">
			<?php
            $name = ( $taxonomy == 'category' ) ? 'post_category[]' : 'tax_input[' . $taxonomy . ']';
            echo "<input type='hidden' name='{$name}' value='0' />"; // Allows for an empty term set to be sent. 0 is an invalid Term ID and will be ignored by empty() checks.
            ?>
			<ul id="<?php echo $taxonomy; ?>checklist" class="list:<?php echo $taxonomy?> categorychecklist form-no-clear">
				<?php cas_terms_checklist($post->ID, array( 'taxonomy' => $taxonomy, 'popular_cats' => $popular_ids ) ) ?>
			</ul>
		</div>
	</div>
	<?php
		}
	
		echo '<p style="padding:6px 0 4px;">'."\n";
		echo '<label><input type="checkbox" name="taxonomies[]" value="'.$tax['args']->name.'"'.(in_array($tax['args']->name,$current) ? ' checked="checked"' : '').' /> '.sprintf(__('Show with %s'),$tax['args']->labels->all_items).'</label>'."\n";
		echo '</p>'."\n";
	
	}
	
	public function meta_box_post_type($post, $box) {
		$meta = get_post_meta($post->ID, 'post_types', true);
		$current = $meta != '' ? $meta : array();
		$post_type = $box['args'];
		
		$exclude = array();
		if($post_type->name == 'page' && 'page' == get_option( 'show_on_front')) {
			$exclude[] = get_option('page_on_front');
			$exclude[] = get_option('page_for_posts');
		}
		
		$posts = get_posts(array('post_type'=>$post_type->name,'exclude'=>$exclude));
		
		if ( ! $posts || is_wp_error($posts) ) {
			echo '<p>' . __( 'No items.' ) . '</p>';
			
		} else {
		
		?>
		
		<div id="posttype-<?php echo $post_type->name; ?>" class="categorydiv">
		<ul id="posttype-<?php echo $post_type->name; ?>-tabs" class="category-tabs">
			<li class="tabs"><a href="#<?php echo $post_type->name; ?>-all" tabindex="3"><?php _e('View All');; ?></a></li>
		</ul>
		
		<div id="<?php echo $post_type->name; ?>-all" class="tabs-panel" style="height:inherit;max-height:200px;">
			<ul id="<?php echo $post_type->name; ?>checklist" class="list:<?php echo $post_type->name?> categorychecklist form-no-clear">
				<?php cas_posts_checklist($post->ID, array( 'post_type' => $post_type->name) ) ?>
			</ul>
		</div>
		</div>
		
		<?php
		
		}
		
		echo '<p style="padding:6px 0 4px;">'."\n";
		echo '<label><input type="checkbox" name="post_types[]" value="'.$post_type->name.'"'.(in_array($post_type->name,$current) ? ' checked="checked"' : '').' /> '.sprintf(__('Show with %s'),$post_type->labels->all_items).'</label>'."\n";
		echo '</p>'."\n";

	}
	
	/**
	 *
	 * Hide some meta boxes from start
	 *
	 */
	function change_default_hidden( $hidden, $screen ) {
	        
	    if ($screen->base == 'sidebar' && get_user_option( 'metaboxhidden_sidebar' ) === false) {
		
		$hidden_meta_boxes = array('pageparentdiv','ca-sidebar-tax-post_format','ca-sidebar-post-type-attachment');
		$hidden = array_merge($hidden,$hidden_meta_boxes);
		
		$user = wp_get_current_user();
		update_user_option( $user->ID, 'metaboxhidden_sidebar', $hidden, true );
		
	    }
	    return $hidden;
	}
	
	/**
	 *
	 * Create form fields
	 *
	 */
	private function form_fields($array = array(), $show_name = 1) {
		global $post;

		?>
		<table class="form-table"> 
		<?php
		if(!empty($array)) {
			$array = array_intersect_key($this->settings,array_flip($array));
		} else {
			$array = $this->settings;
			unset($array['taxonomies']);
			unset($array['post_types']);
		}
				
		foreach($array as $setting) :
		
			$meta = get_post_meta($post->ID, $setting['id'], true);
			$current = $meta != '' ? $meta : $setting['val'];
			?>
			
			<tr valign="top">
			<?php if($show_name) echo '<th scope="row">'.$setting['name'].'</th>'; ?>
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
						echo '<option value="'.$key.'"'.(in_array($key,$current) ? ' selected="selected"' : '').'>'.$value.'</option>'."\n";
					}
					echo '</select>'."\n";
					break;
				case 'checkbox' :
					echo '<ul>'."\n";
					foreach($setting['list'] as $key => $value) {
						echo '<li><label><input type="checkbox" name="'.$setting['id'].'[]" value="'.$key.'"'.(in_array($key,$current) ? ' checked="checked"' : '').' /> '.$value.'</label></li>'."\n";
					}
					echo '</ul>'."\n";
					break;
				case 'text' :
				default :
					echo '<input style="width:200px;" type="text" name="'.$setting['id'].'" value="'.$current.'" />'."\n";
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
		
		// Save button pressed
		if(!isset($_POST['original_publish']) && !isset($_POST['save_post']))
			return;
		
		// Only sidebar type
		if(get_post_type($post_id) != 'sidebar')
			return;	
		
		// Verify nonce
		if (!check_admin_referer(basename(__FILE__),'_ca-sidebar-nonce'))
			return;
		
		// Check permissions
		if (!current_user_can('edit_post', $post_id))
			return;
		
		// Check autosave
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
			return;
		
		// Load metadata
		$this->init_metadata();
		
		// Update metadata
		foreach ($this->settings as $field) {
			$old = get_post_meta($post_id, $field['id'], true);			
			$new = isset($_POST[$field['id']]) ? $_POST[$field['id']] : '';

			if ($new != '' && $new != $old) {
				update_post_meta($post_id, $field['id'], $new);		
			} elseif ($new == '' && $old != '') {
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
	
	public function load_dependencies() {
		
		require_once('walker.php');
		
	}
	
}

// Launch plugin
global $ca_sidebars;
$ca_sidebars = new ContentAwareSidebars();

// Template function
function display_ca_sidebar($args = array()) {
	global $ca_sidebars, $_wp_sidebars_widgets;
	
	// Grab args or defaults	
	$defaults = array (
 		'before'	=> '<div id="sidebar" class="widget-area"><ul class="xoxo">',
		'after'		=> '</ul></div>'
	);
	$args = wp_parse_args($args,$defaults);
	extract($args,EXTR_SKIP);
		
	$posts = $ca_sidebars->get_sidebars("='2'");
	if(!$posts)
		return;
	
	$i = $host = 0;	
	foreach($posts as $post) {
		
		$id = 'ca-sidebar-'.$post->ID;
			
		// Check if sidebar exists
		if (!isset($_wp_sidebars_widgets[$id]))
			continue;
		
		// Merge if more than one. First one is host.
		if($i > 0) {
			if($post->merge_pos)
				$_wp_sidebars_widgets[$host] = array_merge($_wp_sidebars_widgets[$host],$_wp_sidebars_widgets[$id]);
			else
				$_wp_sidebars_widgets[$host] = array_merge($_wp_sidebars_widgets[$id],$_wp_sidebars_widgets[$host]);
		} else {
			$host = $id;
		}
		$i++;
	}
	
	if ($host) {
		echo $before;
		dynamic_sidebar($host);
		echo $after;
	}
}