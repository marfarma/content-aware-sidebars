<?php

/**
 * TODO: Clean this mess...
 */

/**
 *
 * Custom Walker Class
 *
 */
class CAS_Walker_Tax_Checklist extends Walker {
	var $tree_type = 'category';
	var $db_fields = array ('parent' => 'parent', 'id' => 'term_id');

	public function start_lvl(&$output, $depth, $args) {
		$indent = str_repeat("\t", $depth);
		$output .= "$indent<ul class='children'>\n";
	}

	public function end_lvl(&$output, $depth, $args) {
		$indent = str_repeat("\t", $depth);
		$output .= "$indent</ul>\n";
	}

	public function start_el(&$output, $term, $depth, $args) {
		extract($args);
		
                if ( empty($taxonomy) ) {
			$output .= "\n<li>";
                        return;
		}

                $name = $taxonomy->name == 'category' ? 'post_category' : 'tax_input['.$taxonomy->name.']';                   
                $value = $taxonomy->hierarchical ? 'term_id' : 'slug';
		$class = in_array( $term->term_id, $popular_cats ) ? ' class="popular-category"' : '';
                
		$output .= "\n".'<li id="'.$taxonomy->name.'-'.$term->term_id.'"$class><label class="selectit"><input value="'.$term->$value.'" type="checkbox" name="'.$name.'[]" id="in-'.$taxonomy->name.'-'.$term->term_id.'"'.checked(in_array($term->term_id,$selected_cats),true,false).disabled(empty($disabled),false,false).'/>'.esc_html( apply_filters('the_category', $term->name )) . '</label>';
	}

	public function end_el(&$output, $term, $depth, $args) {
		$output .= "</li>\n";
	}
}

/**
 *
 * Custom Walker Class
 *
 */
class CAS_Walker_Post_Checklist extends Walker {
	var $tree_type = 'post';
	var $db_fields = array ('parent' => 'post_parent', 'id' => 'ID');

	public function start_lvl(&$output, $depth, $args) {
		$indent = str_repeat("\t", $depth);
		$output .= "$indent<ul class='children'>\n";
	}

	public function end_lvl(&$output, $depth, $args) {
		$indent = str_repeat("\t", $depth);
		$output .= "$indent</ul>\n";
	}

	public function start_el(&$output, $term, $depth, $args) {
		extract($args);
		
                if ( empty($post_type) ) {
			$output .= "\n<li>";
                        return;
		}

		$output .= "\n".'<li id="'.$post_type->name.'-'.$term->ID.'"><label class="selectit"><input value="'.$term->ID.'" type="checkbox" name="post_types[]" id="in-'.$post_type->name.'-'.$term->ID.'"'.checked( in_array($term->ID,$selected_cats),true,false).disabled(empty($disabled),false,false).'/>'.esc_html( $term->post_title ) . '</label>';
	}

	public function end_el(&$output, $term, $depth, $args) {
		$output .= "</li>\n";
	}
}

/**
 *
 * Show terms checklist
 *
 */
function cas_terms_checklist($post_id = 0, $args = array()) {
 	$defaults = array(
		'descendants_and_self' => 0,
		'selected_cats' => false,
		'popular_cats' => false,
		'walker' => null,
		'taxonomy' => 'category',
		'checked_ontop' => true
	);
	extract( wp_parse_args($args, $defaults), EXTR_SKIP );

	if ( empty($walker) || !is_a($walker, 'Walker') )
		$walker = new CAS_Walker_Tax_Checklist();

	$descendants_and_self = (int) $descendants_and_self;

	$tax = get_taxonomy($taxonomy);
        
        $args = array('taxonomy' => $tax);
	$args['disabled'] = !current_user_can($tax->cap->assign_terms);

	if ( is_array( $selected_cats ) )
		$args['selected_cats'] = $selected_cats;
	elseif ( $post_id )
		$args['selected_cats'] = wp_get_object_terms($post_id, $taxonomy, array_merge($args, array('fields' => 'ids')));
	else
		$args['selected_cats'] = array();

	if ( is_array( $popular_cats ) )
		$args['popular_cats'] = $popular_cats;
	else
		$args['popular_cats'] = get_terms( $taxonomy, array( 'fields' => 'ids', 'orderby' => 'count', 'order' => 'DESC', 'number' => 10, 'hierarchical' => false ) );

	if ( $descendants_and_self ) {
		$categories = (array) get_terms($taxonomy, array( 'child_of' => $descendants_and_self, 'hierarchical' => 0, 'hide_empty' => 0 ) );
		$self = get_term( $descendants_and_self, $taxonomy );
		array_unshift( $categories, $self );
	} else {
		$categories = (array) get_terms($taxonomy, array('get' => 'all'));
	}

	if ( $checked_ontop ) {
		// Post process $categories rather than adding an exclude to the get_terms() query to keep the query the same across all posts (for any query cache)
		$checked_categories = array();
		$keys = array_keys( $categories );

		foreach( $keys as $k ) {
			if ( in_array( $categories[$k]->term_id, $args['selected_cats'] ) ) {
				$checked_categories[] = $categories[$k];
				unset( $categories[$k] );
			}
		}

		// Put checked cats on top
		echo call_user_func_array(array(&$walker, 'walk'), array($checked_categories, 0, $args));
	}
	// Then the rest of them
	echo call_user_func_array(array(&$walker, 'walk'), array($categories, 0, $args));
}

/**
 *
 * Show checklist for popular terms
 *
 */
function cas_popular_terms_checklist( $taxonomy, $default = 0, $number = 10, $echo = true ) {
	global $post_ID;

	if ( $post_ID )
		$checked_terms = wp_get_object_terms($post_ID, $taxonomy->name, array('fields'=>'ids'));
	else
		$checked_terms = array();

	$terms = get_terms( $taxonomy->name, array( 'orderby' => 'count', 'order' => 'DESC', 'number' => $number, 'hierarchical' => false ) );

        $disabled = current_user_can($taxonomy->cap->assign_terms) ? '' : ' disabled="disabled"';
        
        $popular_ids = array();
	foreach ( (array) $terms as $term ) {
		$popular_ids[] = $term->term_id;
		if ( !$echo ) // hack for AJAX use
			continue;
		$id = "popular-$taxonomy->name-$term->term_id";      
               ?>

		<li id="<?php echo $id; ?>" class="popular-category">
			<label class="selectit">
			<input id="in-<?php echo $id; ?>" type="checkbox"<?php echo in_array( $term->term_id, $checked_terms ) ? ' checked="checked"' : ''; ?> value="<?php echo $term->term_id; ?>"<?php echo $disabled ?>/>
				<?php echo esc_html( apply_filters( 'the_category', $term->name ) ); ?>
			</label>
		</li>

		<?php
	}
	return $popular_ids;
}

/**
 *
 * Show terms checklist
 *
 */
function cas_posts_checklist($post_id = 0, $args = array()) {
 	$defaults = array(
		'descendants_and_self' => 0,
		'selected_cats' => false,
		'walker' => null,
		'post_type' => 'post',
		'checked_ontop' => true
	);
	extract( wp_parse_args($args, $defaults), EXTR_SKIP );

	if ( empty($walker) || !is_a($walker, 'Walker') )
		$walker = new CAS_Walker_Post_Checklist();

	$descendants_and_self = (int) $descendants_and_self;

	$ptype = get_post_type_object($post_type);
        
        $args = array('post_type' => $ptype);
	$args['disabled'] = !current_user_can($ptype->cap->edit_post);

	if ( is_array( $selected_cats ) )
		$args['selected_cats'] = $selected_cats;
	elseif ( $post_id )
		$args['selected_cats'] = (array)get_post_meta($post_id, 'post_types', true);
	else
		$args['selected_cats'] = array();

	if ( $descendants_and_self ) {          
		$categories = (array) get_posts(array('post_type'=>$post_type,'post_parent' => $descendants_and_self, 'numberposts'=>-1) );
		$self = get_post($descendants_and_self);
		array_unshift($categories, $self);
	} else {
		$categories = (array) get_posts(array('post_type'=>$post_type, 'numberposts'=>-1));
	}

	if ( $checked_ontop ) {
		//Post process $categories rather than adding an exclude to the get_terms() query to keep the query the same across all posts (for any query cache)
		$checked_categories = array();
		$keys = array_keys( $categories );
	
		foreach( $keys as $k ) {
			if ( in_array( $categories[$k]->ID, $args['selected_cats'] ) ) {
				$checked_categories[] = $categories[$k];
				unset( $categories[$k] );
			}
                        elseif($post_type == 'page' && 'page' == get_option( 'show_on_front') && (get_option( 'page_on_front' ) == $categories[$k]->ID || get_option('page_for_posts') == $categories[$k]->ID))
                                unset( $categories[$k] );
		}
	
		//Put checked cats on top
		echo call_user_func_array(array(&$walker, 'walk'), array($checked_categories, 0, $args));
	}
	// Then the rest of them
	echo call_user_func_array(array(&$walker, 'walk'), array($categories, 0, $args));
}

?>