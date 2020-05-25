function rcl_check_profile_form() {

	var rclFormFactory = new RclForm( jQuery( 'form#your-profile' ) );

	rclFormFactory.addChekForm( 'checkPass', {
		isValid: function() {
			var valid = true;
			if ( this.form.find( '#primary_pass' ).val() ) {

				var user_pass = this.form.find( '#primary_pass' );
				var repeat_pass = this.form.find( '#repeat_pass' );

				if ( user_pass.val() != repeat_pass.val() ) {

					this.shake( user_pass );
					this.shake( repeat_pass );
					this.addError( 'checkPass', Rcl.local.no_repeat_pass );
					valid = false;

				} else {

					this.noShake( user_pass );
					this.noShake( repeat_pass );

				}

			}
			return valid;
		}

	} );

	return rclFormFactory.validate();

}