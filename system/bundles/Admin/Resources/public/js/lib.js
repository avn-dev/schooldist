
function initPasswordInputs(sInputFieldId) {

	sInputFieldId = (typeof sInputFieldId !== 'undefined') ?  sInputFieldId : 'password_new';

	jQuery(document.getElementById(sInputFieldId)).keyup(function() {

		var aResult = zxcvbn(this.value);

		jQuery('#password_strength_meter').removeClass('progress-bar-primary');
		jQuery('#password_strength_meter').removeClass('progress-bar-info');
		jQuery('#password_strength_meter').removeClass('progress-bar-warning');
		jQuery('#password_strength_meter').removeClass('progress-bar-danger');

		var sInfo = '';

		switch(aResult.score) {
			case 0:
				jQuery('#password_strength_meter').css('width', '0%');
				jQuery('#password_strength_meter').addClass('progress-bar-danger');
				sInfo = oPasswordStrengthTranslations.very_week;
				break;
			case 1:
				jQuery('#password_strength_meter').css('width', '25%');
				jQuery('#password_strength_meter').addClass('progress-bar-danger');
				sInfo = oPasswordStrengthTranslations.week;
				break;
			case 2:
				jQuery('#password_strength_meter').css('width', '50%');
				jQuery('#password_strength_meter').addClass('progress-bar-warning');
				sInfo = oPasswordStrengthTranslations.sufficient;
				break;
			case 3:
				jQuery('#password_strength_meter').css('width', '75%');
				jQuery('#password_strength_meter').addClass('progress-bar-info');
				sInfo = oPasswordStrengthTranslations.good;
				break;
			case 4:
				jQuery('#password_strength_meter').css('width', '100%');
				jQuery('#password_strength_meter').addClass('progress-bar-success');
				sInfo = oPasswordStrengthTranslations.very_good;
				break;
		}

		jQuery('#password_strength_info').text(oPasswordStrengthTranslations.password_strength+': '+sInfo);

	});

}