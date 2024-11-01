<div class="control-box">
<fieldset>
<legend><?php echo sprintf( esc_html( $description ), $desc_link ); ?></legend>

<table class="form-table">
<tbody>
	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-values' ); ?>"><?php echo esc_html( __( 'Label', 'zip-jp-postalcode-address-search' ) ); ?></label></th>
	<td><input type="text" name="values" class="oneline" id="<?php echo esc_attr( $args['content'] . '-values' ); ?>" value="住所(地名)⇒郵便番号検索" /></td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-id' ); ?>"><?php echo esc_html( __( 'Id attribute', 'zip-jp-postalcode-address-search' ) ); ?></label></th>
	<td><input type="text" name="id" class="idvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-id' ); ?>" /></td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-class' ); ?>"><?php echo esc_html( __( 'Class attribute', 'zip-jp-postalcode-address-search' ) ); ?></label></th>
	<td><input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-class' ); ?>" value="" data-hidevalue="mfczip_findzipcode" /></td>
	</tr>

</tbody>
</table>
</fieldset>
</div>

<div class="insert-box">
<!-- 	<input type="text" name="mfczipbtn" class="tag code" readonly="readonly" onfocus="this.select()" /> -->

	<div class="submitbox">
	<input type="button" class="button button-primary insert-buttontag" value="<?php echo esc_attr( __( 'Insert Tag', 'zip-jp-postalcode-address-search' ) ); ?>" />
	</div>
</div>
