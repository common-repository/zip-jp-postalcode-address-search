(function( $ ) {

	jQuery('#zjpas_mfczipjp_tabs').tabs({});

	var defaults = {
		defaultColor: '#ffdddd',
	};
	jQuery('#zjpas_mfczipjpapiwarnBgColor').wpColorPicker(defaults);

	jQuery('#zjpas_mfczipjp_tabs a[href^="#"].jump').click(function() {
		var speed = 800;
		var href= jQuery(this).attr("href");
		var target = jQuery(href == "#" || href == "" ? 'html' : href);
		var position = target.offset().top - 50;
		jQuery('body,html').animate({scrollTop:position}, speed, 'swing');
		return false;
	});

	fnMfczipIndividualscriptmodeDisable = function() {
		if (jQuery('#zjpas_mfczipjp_tabs [name="zjpas_mfczipjpapi_pluginscript"]:checked').val() != "true") {
			jQuery("#zjpas_mfczipjp_tabs .zjpas_mfczip_individualscriptmode_disable").fadeOut();
		} else {
			jQuery("#zjpas_mfczipjp_tabs .zjpas_mfczip_individualscriptmode_disable").fadeIn();
		}
	}
	jQuery('#zjpas_mfczipjp_tabs [name="zjpas_mfczipjpapi_pluginscript"]').on('click', function(){
		fnMfczipIndividualscriptmodeDisable();
	});
	fnMfczipIndividualscriptmodeDisable();

	jQuery( 'input.insert-buttontag' ).click( function() {
		var form = jQuery( this ).closest( 'form.tag-generator-panel' );
		var strId = form.find( 'input[name="id"]' ).val().trim();
		if (strId != "") { strId = " id='"+strId+"'" ; }
		var strClass = form.find( 'input[name="class"]' ).val().trim();
		if (strClass != "") { strClass = " "+strClass; }
		
		var tag = '<button type="button" class="'+ form.find( 'input[name="class"]' ).attr("data-hidevalue")+strClass+'">'+form.find( 'input[name="values"]' ).val()+"</button>";

		wpcf7.taggen.insert( tag );
		tb_remove(); // close thickbox
		return false;
	} );

})( jQuery );
