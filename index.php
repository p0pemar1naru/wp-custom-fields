<?php
/*
Plugin Name: CJI Custom fields for Sk8Park
Plugin URI: http://cjinteractive.ca/
Description: Forces post-types to use a standard list of custom fields with options for checkboxes, dropdowns, and various text fields.
Author: Nick Tetcu
Version: 1.0.1
Author URI: http://www.tetcu.com/
Based on work by Steve Taylor: http://sltaylor.co.uk/blog/control-your-own-wordpress-custom-fields/
*/

include_once('classes/NTStandardizedCustomContent.php');

add_action('admin_menu', 'NTStandardizedCustomContent::create_meta_box');

add_action('save_post', 'NTStandardizedCustomContent::save_custom_fields', 1, 2);

add_action('do_meta_boxes', 'NTStandardizedCustomContent::remove_default_custom_fields', 10, 3);
