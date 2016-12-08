// javascript file

jQuery(document).ready(function() {
	jQuery('#errorMessage').hide();
});

function validateForm()
{
	var validateFlag = true;
	jQuery('#errorMessage').hide();
	var userInput = jQuery('#commentTextArea').val();

	if (jQuery.trim(userInput) === '') {
		jQuery('#errorMessage').show();
		validateFlag = false;
	}

	return validateFlag;
}
