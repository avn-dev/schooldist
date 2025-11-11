<style>

form {
 
}

input {
  border-radius: 6px;
  margin-bottom: 6px;
  padding: 12px;
  border: 1px solid rgba(50, 50, 93, 0.1);
  height: 44px;
  font-size: 16px;
  width: 100%;
  background: white;
}

.result-message {
  line-height: 22px;
  font-size: 16px;
}

.hidden {
  display: none;
}

#card-error {
  color: rgb(105, 115, 134);
  text-align: left;
  font-size: 13px;
  line-height: 17px;
  margin-top: 12px;
}

#card-element {
  border-radius: 4px 4px 0 0 ;
  padding: 12px;
  border: 1px solid rgba(50, 50, 93, 0.1);
  height: 44px;
  width: 100%;
  background: white;
}

#payment-request-button {
  margin-bottom: 32px;
}

/* Buttons and links */
button {
  background: #5469d4;
  color: #ffffff;
  font-family: Arial, sans-serif;
  border-radius: 0 0 4px 4px;
  border: 0;
  padding: 12px 16px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  display: block;
  transition: all 0.2s ease;
  box-shadow: 0px 4px 5.5px 0px rgba(0, 0, 0, 0.07);
  width: 100%;
}
button:hover {
  filter: contrast(115%);
}
button:disabled {
  opacity: 0.5;
  cursor: default;
}

/* spinner/processing state, errors */
.spinner,
.spinner:before,
.spinner:after {
  border-radius: 50%;
}
.spinner {
  color: #ffffff;
  font-size: 22px;
  text-indent: -99999px;
  margin: 0px auto;
  position: relative;
  width: 20px;
  height: 20px;
  box-shadow: inset 0 0 0 2px;
  -webkit-transform: translateZ(0);
  -ms-transform: translateZ(0);
  transform: translateZ(0);
}
.spinner:before,
.spinner:after {
  position: absolute;
  content: "";
}
.spinner:before {
  width: 10.4px;
  height: 20.4px;
  background: #5469d4;
  border-radius: 20.4px 0 0 20.4px;
  top: -0.2px;
  left: -0.2px;
  -webkit-transform-origin: 10.4px 10.2px;
  transform-origin: 10.4px 10.2px;
  -webkit-animation: loading 2s infinite ease 1.5s;
  animation: loading 2s infinite ease 1.5s;
}
.spinner:after {
  width: 10.4px;
  height: 10.2px;
  background: #5469d4;
  border-radius: 0 10.2px 10.2px 0;
  top: -0.1px;
  left: 10.2px;
  -webkit-transform-origin: 0px 10.2px;
  transform-origin: 0px 10.2px;
  -webkit-animation: loading 2s infinite ease;
  animation: loading 2s infinite ease;
}

@-webkit-keyframes loading {
  0% {
    -webkit-transform: rotate(0deg);
    transform: rotate(0deg);
  }
  100% {
    -webkit-transform: rotate(360deg);
    transform: rotate(360deg);
  }
}
@keyframes loading {
  0% {
    -webkit-transform: rotate(0deg);
    transform: rotate(0deg);
  }
  100% {
    -webkit-transform: rotate(360deg);
    transform: rotate(360deg);
  }
}

@media only screen and (max-width: 600px) {
  form {
    width: 80vw;
  }
}

</style>

<script src="https://js.stripe.com/v3/"></script>

<div class="container" style="margin-bottom: 20px;">
	
{if $action == 'payment'}
	<h2>Personal Data</h2>
	<div class="row">

		<div class="col-xs-12 col-md-4">
			<h3>General</h3>
			<p>{$customerCompany}<br />
			{if $contactPersonFirstname or $contactPersonLastname}
			{$contactPersonFirstname} {$contactPersonLastname}<br />
			{/if}
			{$customerAddress|nl2br}</p>
		</div>

		<div class="col-xs-12 col-md-4">
			<div class="col-xs-12 col-md-12">
				<h3>Customer number</h3>
				<p>{$customerNumber}</p>
			</div>
			<div class="col-xs-12 col-md-12">
				<h3>Invoice number</h3>
				<p id="document_number">{$documentNumber}</p>
			</div>
		</div>

		<div class="col-xs-12 col-md-4">
			<h3>Contact person</h3>
			<p>{$editorFirstname} {$editorLastname}</p>
			<p>E-Mail: {$editorEmail}</p>
			<p>Phone: {$editorPhone}</p>
		</div>
	</div>

	{if $cardError}
	<div id="creditcard_payment_errors" class="alert alert-danger">
		<h4><i class="icon fa fa-ban"></i> An error occured!</h4>
		{$cardError}
		<br />
		Please check your payment data and try again.
	</div>
	{/if}
	
	<h2>Please check your invoice</h2>
	<div class="table-responsive">
		<table class="table table-hover">
			<tbody>
				<tr>
					<td>Total amount</td>
					<td class="text-right">{$documentPrice|number_format:2:",":"."} {$cardCurrency}</td>
				</tr>
				<tr>
					<td>Already paid</td>
					<td class="text-right">{$documentAmountPaid|number_format:2:",":"."} {$cardCurrency}</td>
				</tr>
				<tr>
					<td><b>Remaining amount</b></td>
					<td class="text-right"><b>{$documentRemainingAmount|number_format:2:",":"."} {$cardCurrency}</b></td>
				</tr>
			</tbody>
		</table>
	</div>
	{if $invoice != 'draft'}
		<p>
			<a class="btn btn-default" target="_blank" href="?&next=show_invoice&hash={$documentHash}">Show invoice</a>
		</p>
	{/if}
	
	{if $invoice == 'payable'}
	    <!-- Display a payment form -->
		<form id="payment-form">
		  <div id="card-element"><!--Stripe.js injects the Card Element--></div>
		  <button id="submit" class="btn btn-primary">
			<div class="spinner hidden" id="spinner"></div>
			<span id="button-text">Pay now</span>
		  </button>
			<div id="card-error" class="hidden alert alert-danger" role="alert">
			  
			</div>
			<div class="result-message hidden alert alert-success">
				Payment succeeded.
			</div>
		</form>
	{/if}

	{if $invoice == 'paid'}
	<div class="alert alert-info">Invoice already paid!</div>
	{elseif $invoice == 'draft'}
	<div class="alert alert-info">Invoice is still a draft!</div>
	{/if}
	


{elseif $action == 'success'}
	<br>
	<div class="alert alert-success">
		<h4><i class="icon fa fa-check"></i> Transfer successful!</h4>
		Thank you for using online credit card payment.
	</div>
{/if}

</div>


<script>
	
// A reference to Stripe.js initialized with a fake API key.
// Sign in to see examples pre-filled with your key.
var stripe = Stripe("{$publicStripeKey}", {
  locale: 'en'
});

var clientSecret = '{$clientSecret}';

// Disable the button until we have Stripe set up on the page
document.querySelector("button").disabled = true;

    var elements = stripe.elements();
    var style = {
      base: {
        color: "#32325d",
        fontFamily: 'Arial, sans-serif',
        fontSmoothing: "antialiased",
        fontSize: "16px",
        "::placeholder": {
          color: "#32325d"
        }
      },
      invalid: {
        fontFamily: 'Arial, sans-serif',
        color: "#fa755a",
        iconColor: "#fa755a"
      }
    };
    var card = elements.create("card", { style: style });
    // Stripe injects an iframe into the DOM
    card.mount("#card-element");
    card.on("change", function (event) {
      // Disable the Pay button if there are no card details in the Element
      document.querySelector("button").disabled = event.empty;
	  
	  errorMessage = event.error ? event.error.message : "";
	  
	  if(errorMessage) {
		document.querySelector("#card-error").classList.remove("hidden");
	  } else {
		  document.querySelector("#card-error").classList.add("hidden");
	  }
	  
      document.querySelector("#card-error").textContent = errorMessage;
    });
    var form = document.getElementById("payment-form");
    form.addEventListener("submit", function(event) {
      event.preventDefault();
      // Complete payment when the submit button is clicked
      payWithCard(stripe, card, clientSecret);
    });
  
// Calls stripe.confirmCardPayment
// If the card requires authentication Stripe shows a pop-up modal to
// prompt the user to enter authentication details without leaving your page.
var payWithCard = function(stripe, card, clientSecret) {
  loading(true);
  stripe
    .confirmCardPayment(clientSecret, {
      payment_method: {
        card: card
      }
    })
    .then(function(result) {
      if (result.error) {
        // Show error to your customer
        showError(result.error.message);
      } else {
        // The payment succeeded!
        orderComplete(result.paymentIntent.id);
      }
    });
};
/* ------- UI helpers ------- */
// Shows a success message when the payment is complete
var orderComplete = function(paymentIntentId) {
	
  loading(false);
  document.querySelector(".result-message").classList.remove("hidden");
  document.querySelector("button").disabled = true;
  
  document.location.href = '?&next=confirm&hash={$documentHash}&paymentIntentId='+paymentIntentId;
  
};

// Show the customer the error from Stripe if their card fails to charge
var showError = function(errorMsgText) {
  loading(false);
  var errorMsg = document.querySelector("#card-error");
  errorMsg.textContent = errorMsgText;
  if(errorMsgText) {
		errorMsg.classList.remove("hidden");
  } else {
	  errorMsg.classList.add("hidden");
  }
  setTimeout(function() {
    errorMsg.textContent = "";
	errorMsg.classList.add("hidden");
  }, 4000);
};
// Show a spinner on payment submission
var loading = function(isLoading) {
  if (isLoading) {
    // Disable the button and show a spinner
    document.querySelector("button").disabled = true;
    document.querySelector("#spinner").classList.remove("hidden");
    document.querySelector("#button-text").classList.add("hidden");
  } else {
    document.querySelector("button").disabled = false;
    document.querySelector("#spinner").classList.add("hidden");
    document.querySelector("#button-text").classList.remove("hidden");
  }
};
	
</script>