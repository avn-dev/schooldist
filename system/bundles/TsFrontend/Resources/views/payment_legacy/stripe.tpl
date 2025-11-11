

<button type="button" onclick="executePayment();">{$sButtonLabel}</button>

<script src="https://js.stripe.com/v3/"></script>

<script>
	
	var checkout_session = "{$sCheckoutSessionId}";
		
	function executePayment() {		
		
		var stripe = Stripe("{$sApiKeyPublic}");
		
		stripe.redirectToCheckout({
			{* Make the id field from the Checkout Session creation API response
				available to this file, so you can provide it as parameter here*}
			sessionId: checkout_session
		}).then(function (result) {
			{* If `redirectToCheckout` fails due to a browser or network
			error, display the localized error message to your customer
			using `result.error.message`. *}
		});	
	}
</script>
