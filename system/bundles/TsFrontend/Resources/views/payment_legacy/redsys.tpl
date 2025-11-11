{* 
	Formular generieren 
*}

{$oRedsys->createForm()}

{*
	Formular muss mit JS umpositioniert werden, da ein Formular in einem Formular
	nicht funktioniert.
*}

<script>
	var form = {$sJQueryName}('.form-inquiry');
	
	var paymentFormContainer = {$sJQueryName}('#redsys_form');
	
	paymentFormContainer.detach().insertAfter(form);
	
</script>