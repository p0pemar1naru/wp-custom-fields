<?php if ( ! defined('WP_CONTENT_DIR')) exit('No direct script access allowed'); ?>

<div class="wrap">
	<?php screen_icon(); ?>
	<h2>Custom Content Administration</h2>
	
	<?php print $msg; ?>

	<form action="" method="post" id="content_rotation_admin_options_form">
		<h3><label for="separator">Content Types</label></h3>
		<p>Also known as "post-types"<br/>
		<input type="text" id="separator" name="separator" value="<?php print esc_attr( get_option('content_rotation_content_separator') ); ?>" /></p>
		
		<h3><label for="content_block">Content Block</label></h3>
		<p>
			<br/>
			<textarea rows="5" cols="50" id="content_block" name="content_block"><?php print get_option('content_rotation_content_block'); ?></textarea>
		</p>
		<p class="submit"><input type="submit" name="submit" value="Update" /></p>
		<?php wp_nonce_field('content_rotation_admin_options_update'); ?>
	</form>	
</div>