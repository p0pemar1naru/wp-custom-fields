<div class="form-wrap">

<!-- Checkbox -->
<label for="[+name+]" style="display:inline;"><b>[+title+]</b></label>
<input type="checkbox" name="[+name+]" id="[+name+]" value="yes" checked="checked" style="width: auto;" />

<!-- Text input -->
<label for="[+name+]"><b>[+title+]</b></label><br/>
<input type="text" name="[+name+]" id="[+name+]" value="[+value+]" />



<label for="[+name+]"><b>[+title+]</b></label>
<textarea name="[+name+]" id="[+name+]" columns="30" rows="3">
[+value+]
</textarea>
<script type="text/javascript">
	jQuery( document ).ready( function() {
		jQuery( "[+name+]" ).addClass( "mceEditor" );
		if ( typeof( tinyMCE ) == "object" && typeof( tinyMCE.execCommand ) == "function" ) {
			tinyMCE.execCommand( "mceAddControl", false, "[+name+]" );
		}
	});
</script>

</div>