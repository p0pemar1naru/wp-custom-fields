<?php
/*------------------------------------------------------------------------------
This plugin standardizes the custom fields for specified content types, e.g.
post, page, and any other custom post-type you register via a plugin.

TO-DO: 
	Create a options page and a menu item
	read the $prefix from the database (? -- maybe not... changing it after posts
		have been created would be disasterous)
	read the $content_types_array from the database
	read the $custom_fields from the database
	more form element types?  E.g. date?
------------------------------------------------------------------------------*/
class NTStandardizedCustomContent {
	/*
	 * This prefix helps ensure unique keys in the $_POST array. It is used only to identify the form elements;
     * this prefix is *not* used as part of the meta_key when saving the field names to the database.
     * If you want your fields to be hidden from built-in WordPress functions, you can name them individually using "_" as the first character.
	 * If you omit a prefix entirely, your custom field names must steer clear of the built-in post field names (e.g. 'content').
	*/
	public static $prefix = 'custom_content_'; 
	
    /**
     * Which types of content do we want to standardize? Here you can list WP built-in content types (aka post-types)
     * e.g. 'page', 'post' or any custom post-types you define.
     * 
     * @var array Array of strings, each string being the name of a WP post-type
     */
	public static $content_types_array = array('post');
	
    /**
     *   The array of custom fields should have the following structure:
     *   $custom_fields =	array(
     *       array(
     *           // name and id of the form element & as the meta_key in the wp_postmeta table. 
     *           // Should contain lowercase letters, "-", and "_" only. Names beginning with "_"
     *           // will be hidden from built-in WP functions, e.g. the_meta()
     *           'name'			=> 'my_name', 
     *
     *           // used in the element's <label>
     *           'title'			=> 'This is the bold Text that appears above the Form Element!', 
     *
     *           // optional text will be wrapped in a <p> and appear below the element
     *           'description'	=> 'Shh... this is extra italic text...',	
     *
     *           // one of the supported element types: checkbox, dropdown,text,textarea,wysiwyg
     *           'type'			=> 'dropbox', 
     *
     *           // Include this ONLY when type = dropdown!! Options for the Drop down list.
     *           'options'		=> array('one','two','three'), 
     *       ),
     *   );
     * 
     * @var array Associative array of custom fields
     */
	public static $custom_fields_for_posts = array(
		array(
			'name'			=> 'external-link', 
			'title'			=> 'Full URL for the external link OR leave blank for an internal link to this post',
			'description'	=> 'NOTE: <em>Start with http://...</em>',	
			'type'			=> 'text'
        )
//        ,array(
//			'name'			=> 'short_text',
//			'title'			=> 'A short bit of text',
//			'description'	=> 'This is a textarea, without any formatting controls.',
//			'type'			=> 'textarea',
//		),
//		array(
//			'name'			=> 'gender',
//			'title'			=> 'Gender',
//			'description'	=> 'Sample dropdown menu',
//			'type'			=> 'dropdown',
//			'options'		=> array('Male','Female'),
//		),
//		array(
//			'name'			=> 'formatted_text',
//			'title'			=> 'Formatted Text',
//			'description'	=> 'This uses jQuery to add the formatting controls.',
//			'type'			=> 'wysiwyg',
//		),
//		array(
//			'name'			=> 'my_checkbox',
//			'title'			=> 'Do You Like This Checkbox?',
//			'description'	=> 'Checkboxes are tricky... they either have a value, or they are null.',
//			'type'			=> 'checkbox',
//		)
	);

    /**
     * This plugin is meant to be configured so it acts on a specified list of content
     * types, e.g. post, page, or any custom content types that is registered.
     * FUTURE: read this from the database.
     * 
     * @return array Array of strings, each string being the name of a WP post-type
     */
	private static function _get_active_content_types()	{
		return self::$content_types_array;
	}

    /**
     * Gets custom fields for this content type.
     * FUTURE: read these arrays from the database
     * 
     * @param string $content_type The name of the content type, e.g. post, page.
     * @return mixed Array of associative arrays where each associative array describes custom fields a custom field to be used for the $content_type specified
     */
	private static function _get_custom_fields($content_type) {
		return self::$custom_fields_for_posts;
	}

    /**
     * Generates a single checkbox form element
     * The checked value is hard-coded to 'yes' for simplicity.
     * 
     * @param array $data An associative array describing how the element should look with keys for name, title, description, and type
     * @return string Checkbox html with placeholders corresponding to the keys of the hash replaced by the values
     */
	private static function _get_checkbox_element($data) {
		$tpl ='<input type="checkbox" name="[+name+]" id="[+name+]" value="yes" [+is_checked+] style="width: auto;"/> 
		<label for="[+name+]" style="display:inline;"><strong>[+title+]</strong></label>';
		// Check if the box is checked
		if ( $data['value'] == "yes" )
			$data['is_checked'] = 'checked="checked"';
		else
			$data['is_checked'] = '';
	
		return self::parse($tpl, $data);
	}

    /**
     * Generates a single dropdown form element
     * It requires that you supply an array of options in its 'options' key.
     * The $tpl used internally here uses a custom [+options+] placeholder.
     * 
     * @param array $data An associative array describing how the element should look with keys for name, title, description, and type
     * @return string Dropdown html with placeholders corresponding to the keys of the hash replaced by the values
     */
	private static function _get_dropdown_element($data) {
		// Some error messaging.
		if ( !isset($data['options']) || !is_array($data['options']) )
			return '<p><strong>Custom Content Error:</strong> No options supplied for ' . $data['name'] . '</p>';
        
		$tpl =  '<label for="[+name+]"><strong>[+title+]</strong></label><br/>
                <select name="[+name+]" id="[+name+]">[+options+]</select>';

		$option_str = '<option value="">--- Please Select ---</option>';
		foreach ( $data['options'] as $option )	{
			$option = htmlspecialchars($option);
			$is_selected = '';
			if ( $data['value'] == $option )
				$is_selected = 'selected="selected"';
            
			$option_str .= '<option value="' . $option . '" ' . $is_selected . '>' . $option . '</option>';
		}
		
		unset($data['options']); // the parse function req's a simple hash.
		$data['options'] = $option_str; // prep for parsing
		
		return self::parse($tpl, $data);
	}
	
    /**
     * Generates a single text input form element
     * 
     * @param array $data An associative array describing how the element should look with keys for name, title, description, and type
     * @return string Text input html with placeholders corresponding to the keys of the hash replaced by the values
     */
	private static function _get_text_element($data) {
		$tpl = '<label for="[+name+]"><strong>[+title+]</strong></label>
				<input type="text" name="[+name+]" id="[+name+]" value="[+value+]" />';
        
		return self::parse($tpl, $data);
	}
	
    /**
     * Generates a single textarea input form element
     * 
     * @param array $data An associative array describing how the element should look with keys for name, title, description, and type
     * @return string Textarea html with placeholders corresponding to the keys of the hash replaced by the values
     */
	private static function _get_textarea_element($data) {
		$tpl = '<label for="[+name+]"><strong>[+title+]</strong></label><br/>
			<textarea name="[+name+]" id="[+name+]" columns="30" rows="3">[+value+]</textarea>';
        
		return self::parse($tpl, $data);
	}


	//------------------------------------------------------------------------------
	private static function _get_wysiwyg_element($data) {
		$tpl = '<label for="[+name+]"><strong>[+title+]</strong></label>
			<textarea name="[+name+]" id="[+name+]" columns="30" rows="3">[+value+]</textarea>
			<script type="text/javascript">
				jQuery( document ).ready( function() {
					jQuery( "[+name+]" ).addClass( "mceEditor" );
					if ( typeof( tinyMCE ) == "object" && typeof( tinyMCE.execCommand ) == "function" ) {
						tinyMCE.execCommand( "mceAddControl", false, "[+name+]" );
					}
				});
			</script>
			';
        
		return self::parse($tpl, $data);
	}

    
    /**
     * Creates the new Custom Fields meta box
     */
	public static function create_meta_box() {
		$content_types_array = self::_get_active_content_types();
		foreach ( $content_types_array as $content_type ) {
			add_meta_box(   'my-custom-fields',
                            'External Link',
                            'NTStandardizedCustomContent::print_custom_fields',
                            $content_type,
                            'normal',
                            'high',
                            $content_type   );
		}
	}


    /**
     * A simple parsing function for basic templating
     * 
     * @param string $tpl A string containing [+placeholders+]
     * @param array $hash An associative array('key' => 'value');
     * @return string Placeholders corresponding to the keys of the hash will be replaced with the values and the string will be returned
     */
	public static function parse($tpl, $hash) {
	    foreach ($hash as $key => $value) {
	        $tpl = str_replace('[+'.$key.'+]', $value, $tpl);
	    }
        
	    return $tpl;
	}

    /**
     * Display the new Custom Fields meta box
     * 
     * @param object $post The post object is always passed to this callback function
     * @param string $callback_args   will always have a copy of this object passed (I'm not sure why),
     *                              but in $callback_args['args'] will be the 7th parameter from the add_meta_box() function.
     *                              We are using this argument to pass the content_type.
     */
	public static function print_custom_fields($post, $callback_args = '') {
		$content_type = $callback_args['args']; // the 7th arg from add_meta_box()
		$custom_fields = self::_get_custom_fields($content_type);
		$output = '';		
		
		foreach ( $custom_fields as $field ) {

			$output_this_field = '';			
			
			$field['value'] = htmlspecialchars( get_post_meta( $post->ID, $field['name'], true ) );
			$field['name'] = self::$prefix . $field['name']; // this ensures unique keys in $_POST
			
			switch ( $field['type'] ) {
				case 'checkbox':
					$output_this_field .= self::_get_checkbox_element($field);
					break;
				case 'dropdown':
					$output_this_field .= self::_get_dropdown_element($field);
					break;
				case 'textarea':
					$output_this_field .= self::_get_textarea_element($field);
					break;
				case 'wysiwyg':
					$output_this_field .= self::_get_wysiwyg_element($field);
					break;
				case 'text':
				default:
					$output_this_field .= self::_get_text_element($field);
					break;
			}
			// optionally add description
			if ( $field['description'] ) {
				$output_this_field .= '<p>' . $field['description'] . '</p>';
			}
			
			$output .= '<div class="form-field form-required">' . $output_this_field . '</div>';
		}
 		// Print the form
 		echo '<div class="form-wrap">';
	 	wp_nonce_field('update_custom_content_fields', 'custom_content_fields_nonce');
	 	echo $output;
	 	echo '</div>';
	}

	public static function print_custom_fields2222($post, $callback_args='') {
		$content_type = $callback_args['args'];
		$custom_fields = self::_get_custom_fields($content_type);
		$output = '';
		
		foreach ( $custom_fields as $field ) {
			$output_this_field = '';
			switch ( $field['type'] ) {
				case 'checkbox':
					$output_this_field .= "<p>I'm a checkbox!</p>";
					break;
				case 'dropdown':
					$output_this_field .= "<p>I'm a dropdown!</p>";
					break;
				case 'textarea':
					$output_this_field .= "<p>I'm a textarea!</p>";
					break;
				case 'wysiwyg':
					$output_this_field .= "<p>I'm a WYSIWYG!</p>";
					break;
				case 'text':
				default:
					$output_this_field .= "<p>I'm a text input!</p>";
					break;
			}
			// optionally add description
			if ( $field['description'] ) {
				$output_this_field .= '<p>'.$field['description'].'</p>';
			}
			
			$output .= '<div class="form-field form-required">'.$output_this_field.'</div>';
		}
 		// Print the form
 		print '<div class="form-wrap">';
	 	print $output;
	 	print '</div>';
 
	}

    /**
     * Remove the default Custom Fields meta box. Only affects the content types that have been activated.
     * INPUTS: sent from WordPress
     * 
     * @param string $type The name of the post-type being edited, e.g. 'post'
     * @param string $context Identifies a type of meta-box, e.g. 'normal', 'advanced', 'side'
     * @param object $post The current post, with attributes e.g. $post->ID and $post->post_name
     */
	public static function remove_default_custom_fields( $type, $context, $post ) {
		$content_types_array = self::_get_active_content_types();
		foreach ( array( 'normal', 'advanced', 'side' ) as $context ) {
			foreach ( $content_types_array as $content_type ) {
				remove_meta_box( 'postcustom', $content_type, $context );
			}
		}
	}

    /**
     * Save the new Custom Fields values
     * 
     * @param int $post_id id of the post these custom fields are associated with
     * @param object $post The post object
     */
	public static function save_custom_fields( $post_id, $post ) {
        $content_types = self::_get_active_content_types();
        // is the current post type being edited a standardized post type?
        if( in_array( $post->post_type, $content_types ) ) {
		// The 2nd arg here is important because there are multiple nonces on the page
            if ( !empty($_POST) && check_admin_referer('update_custom_content_fields', 'custom_content_fields_nonce') ) {		
                $custom_fields = self::_get_custom_fields($post->post_type);
                foreach ( $custom_fields as $field ) {
                    if ( isset( $_POST[ self::$prefix . $field['name'] ] ) ) {
                        $value = trim($_POST[ self::$prefix . $field['name'] ]);
                        // Auto-paragraphs for any WYSIWYG
                        if ( $field['type'] == 'wysiwyg' ) {
                            $value = wpautop( $value );
                        }
                        update_post_meta( $post_id, $field['name'], $value );
                    }
                    // if not set, then it's an unchecked checkbox, so blank out the value.
                    else {
                        update_post_meta( $post_id, $field['name'], '' );
                    }
                }
            }
        }
	}


} // End Class