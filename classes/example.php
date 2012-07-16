<?php

class NTStandardizedCustomContent {
	/**
	* @var  string  $prefix  The prefix for storing custom fields in the postmeta table
	*/
	var $prefix = '_mcf_';
	
	// Which types of content do we want to standardize?
	var $content_types_array = array('post','page','book');
	
	/**
	* @var  array  $fields  Defines the custom fields available
	*/
	var $custom_fields =	array(
		array(
			'name'			=> "block-of-text", // used as the name and id for the form element. Should be lowercase letters and -_ only.
			'title'			=> "A block of text", // used in the element's <label>
			'description'	=> "",	// appears in a <p> after the element
			'type'			=> "textarea", // one of the supported element types
			'capability'	=> "edit_pages" // used in WP's current_user_can() function
		),
		array(
			'name'			=> 'short-text',
			'title'			=> 'A short bit of text',
			'description'	=> '',
			'type'			=> 'text',
			'capability'	=> 'edit_posts'
		),
		array(
			'name'			=> 'checkbox',
			'title'			=> 'Checkbox',
			'description'	=> '',
			'type'			=> 'checkbox',
			'capability'	=> 'manage_options'
		)
	);
	/**
	* PHP 5 Constructor
	*/
	function __construct() {

	}

	
	/*------------------------------------------------------------------------------
	* Create the new Custom Fields meta box
	------------------------------------------------------------------------------*/
	public function create_meta_box() {
		foreach ( $this->content_types_array as $content_type ) {
			add_meta_box( 'my-custom-fields'
				, 'Custom Fields'
				, array( &$this, 'print_custom_fields' )
				, $content_type, 'normal'
				, 'high'
				, $content_type 
			);
		}
	}


	/*------------------------------------------------------------------------------
	Get custom fields for this content type.
	INPUT: $content_type (str) the name of the content type, e.g. post, page.
	OUTPUT: array of associative arrays where each associative array describes 
		a custom field.
	------------------------------------------------------------------------------*/
	public function get_custom_fields($content_type) {
		return $this->custom_fields;
	}

	/*------------------------------------------------------------------------------
	Display the new Custom Fields meta box
	INPUT: $callback_args will always have a copy of this object passed (I'm not sure why),
		but in $callback_args['args'] will be the 7th parameter from the add_meta_box() function.
		We are using this argument to pass the content_type.
	------------------------------------------------------------------------------*/
	public function print_custom_fields($post, $callback_args='') {
		$content_type = $callback_args['args'];

		$custom_fields = $this->get_custom_fields($content_type);
		

			wp_nonce_field( 'my-custom-fields', 'my-custom-fields_wpnonce', false, true );
			foreach ( $custom_fields as $field ) {
				// Check scope

				$output = false;
				
				// Check capability
				if ( !current_user_can( $field['capability'], $post->ID ) ) {
					continue; // skip this field if the user doesn't have the privs to edit it.
				}
				switch ( $field[ 'type' ] ) {
					case "checkbox":
						// Checkbox
						echo '<label for="' . $this->prefix . $field[ 'name' ] .'" style="display:inline;"><b>' . $field[ 'title' ] . '</b></label>&nbsp;&nbsp;';
						echo '<input type="checkbox" name="' . $this->prefix . $field['name'] . '" id="' . $this->prefix . $field['name'] . '" value="yes"';
						if ( get_post_meta( $post->ID, $this->prefix . $field['name'], true ) == "yes" )
							echo ' checked="checked"';
						echo '" style="width: auto;" />';
						break;
					}
					case "textarea":
					case "wysiwyg":
						break;

					default: {
						// Plain text field
						echo '<label for="' . $this->prefix . $field[ 'name' ] .'"><b>' . $field[ 'title' ] . '</b></label>';
						echo '<input type="text" name="' . $this->prefix . $field[ 'name' ] . '" id="' . $this->prefix . $field[ 'name' ] . '" value="' . htmlspecialchars( get_post_meta( $post->ID, $this->prefix . $field[ 'name' ], true ) ) . '" />';
						break;
					}
				}
				if ( $field[ 'description' ] ) echo '<p>' . $field[ 'description' ] . '</p>';
			}
		}
	}
	

	/*------------------------------------------------------------------------------
	* Remove the default Custom Fields meta box
	------------------------------------------------------------------------------*/
	public function remove_default_custom_fields( $type, $context, $post ) {
		foreach ( array( 'normal', 'advanced', 'side' ) as $context ) {
			foreach ( $this->content_types_array as $content_type )	{
				remove_meta_box( 'postcustom', $content_type, $context );
			}
		}
	}
	
	/*------------------------------------------------------------------------------
	* Save the new Custom Fields values
	------------------------------------------------------------------------------*/
	public function save_custom_fields( $post_id, $post ) {
		// content_type
		if ( empty($_POST) ) {
			return;
		}
		if ( empty($_POST[ 'my-custom-fields_wpnonce' ]) || !wp_verify_nonce( $_POST[ 'my-custom-fields_wpnonce' ], 'my-custom-fields' ) ) {
			return;
		}
		if ( !current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( $post->post_type != 'page' && $post->post_type != 'post' ) {
			return;
		}
		foreach ( $this->custom_fields as $field ) {
			if ( current_user_can( $field['capability'], $post_id ) ) {
				if ( isset( $_POST[ $this->prefix . $field['name'] ] ) && trim( $_POST[ $this->prefix . $field['name'] ] ) ) {
					$value = $_POST[ $this->prefix . $field['name'] ];
					// Auto-paragraphs for any WYSIWYG
					if ( $field['type'] == 'wysiwyg' ) $value = wpautop( $value );
					update_post_meta( $post_id, $this->prefix . $field[ 'name' ], $value );
				} else {
					delete_post_meta( $post_id, $this->prefix . $field[ 'name' ] );
				}
			}
		}
	}

} // End Class