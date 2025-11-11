<style type="text/css">
	/* Pages */
	{"form.$sFormIdentifier"} .page-container {
		position: relative;
		padding: 10px;
	}
	{"form.$sFormIdentifier"} .page-content {
		border: 1px dotted #7b7b7b;
		padding: 10px;
	}

	/* Load overlay */
	{"form.$sFormIdentifier"} .page-container .page-loading-overlay {
		position: absolute;
		top: 0;
		bottom: 0;
		left: 0;
		right: 0;
		width: 100%;
		height: 100%;
		z-index: 1000;
		display: none;
		/*border: 1px solid gray;*/
		background: rgba(255,255,255,0.9);
	}
	{"form.$sFormIdentifier"} .page-container .page-loading-overlay.shown {
		display: block;
	}
	{"form.$sFormIdentifier"} .page-container .page-loading-overlay span {
		display: block;
		position: absolute;
		text-align: center;
		top: 49%;
		width: 100%;
	}

	/* Page list */
	{"form.$sFormIdentifier"} .pages-list h1 {
		display: inline-block;
		padding-left: 25px;
		padding-right: 25px;
	}
	{"form.$sFormIdentifier"} .pages-list h1.page-current {
		border-bottom: 1px solid #cf0000;
	}
	{"form.$sFormIdentifier"} .pages-list h1.page-previous {
		color: green;
	}
	{"form.$sFormIdentifier"} .pages-list h1.page-following {
		color: grey;
	}

	/* Page navigation */
	{"form.$sFormIdentifier"} .page-navigation {
		text-align: right;
	}
	{"form.$sFormIdentifier"} .page-navigation .page-navigation-back {
	}
	{"form.$sFormIdentifier"} .page-navigation .page-navigation-next {
	}
	{"form.$sFormIdentifier"} .page-navigation .page-navigation-submit {
	}

	/* Headlines */
	{"form.$sFormIdentifier"} .block-headline {
		color: #cf0000;
	}

	/* Input: Wrong input */
	{"form.$sFormIdentifier"} .validate-error {
		border: 1px solid #cf0000;
	}

	/* Input: Correct input */
	{"form.$sFormIdentifier"} .validate-success {
		border: 1px solid green;
	}

	/* Multi column area */
	{"form.$sFormIdentifier"} .block-multiple-areas {
      position: relative;
	}
	{"form.$sFormIdentifier"} .block-multiple-areas-single {
	}
	{"form.$sFormIdentifier"} .block-multiple-areas-multiple {
	}
	{"form.$sFormIdentifier"} .block-multiple-areas-multiple-first {
	}
	{"form.$sFormIdentifier"} .block-multiple-areas-multiple-following {
		padding-left: 10px;
	}

	{"form.$sFormIdentifier"} .block-multiple-areas .block-area-overlay {
		position: absolute;
		width: 100%;
		height: 100%;
		z-index: 999;
		display: none;
		background: rgba(255,255,255,0.9);
	}

	{"form.$sFormIdentifier"} .block-multiple-areas .block-area-overlay.shown {
		display: block;
	}

	{"form.$sFormIdentifier"} .block-multiple-areas .block-area-overlay span {
		display: block;
		position: absolute;
		text-align: center;
		top: 49%;
		width: 100%;
	}

	/* Error messages for pages */
	{"form.$sFormIdentifier"} .validate-error-page .form-message {
		color: #cf0000;
	}
	{"form.$sFormIdentifier"} .form-message ul {
		list-style-type: none;
		padding: 10px;
		margin: 0;
		color: #cf0000;
	}

	/* Error messages for blocks (fields) */
	{"form.$sFormIdentifier"} .validate-error-block .block-message {
		color: #cf0000;
	}

	/* Input blocks (fields) */
	{"form.$sFormIdentifier"} .block-content {
		border-bottom: 1px dotted #7b7b7b;
	}
	{"form.$sFormIdentifier"} .block-content .block-content-title {
		float: left;
		width: 25%;
	}
	{"form.$sFormIdentifier"} .block-content .block-content-text {
		padding-left: 30%;
	}
	{"form.$sFormIdentifier"} .block-content .block-content-text-with-info-message {
		padding-right: 25px;
	}
	{"form.$sFormIdentifier"} .block-content .block-content-info-message {
		float: right;
		width: 20px;
		position: relative;
		display: inline-block;
	}
	{"form.$sFormIdentifier"} .block-content .block-content-info-message img {
		max-height: 19px;
		max-width: 19px;
	}
	{"form.$sFormIdentifier"} .block-content .block-content-info-message > span {
		visibility: hidden;
		width: 200px;
		background-color: black;
		color: #fff;
		text-align: center;
		border-radius: 5px;
		padding: 5px;
		position: absolute;
		z-index: 1;
		top: -5px;
		left: 110%;
	}
	{"form.$sFormIdentifier"} .block-content .block-content-info-message:hover > span {
		visibility: visible;
	}

	{"form.$sFormIdentifier"} .block-content-radio-group {
		display: flex;
	}

	{"form.$sFormIdentifier"} .block-content-radio-group > div {
		flex-grow: 1;
		text-align: center;
	}

	{"form.$sFormIdentifier"} .block-content-radio-group .block-content-radio-group-label {
		display: block;
		margin-bottom: 3px;
	}

	{"form.$sFormIdentifier"} .block-content-radio-group input {
		width: auto;
	}

	/* Input fields */
	{"form.$sFormIdentifier"} input[type=text],
	{"form.$sFormIdentifier"} input[type=email],
	{"form.$sFormIdentifier"} input[type=tel],
	{"form.$sFormIdentifier"} textarea {
		width: 100%;
	}
	{"form.$sFormIdentifier"} input[type=text].datepicker {
		width: auto;
	}
	{"form.$sFormIdentifier"} textarea {
		height: 100px;
	}
	{"form.$sFormIdentifier"} select {
		max-width: 100%;
	}

	/* Link of a download block */
	{"form.$sFormIdentifier"} .block-input-download .block-content-text a {
	}

	/* Add/remove-buttons of duplicate areas */
	{"form.$sFormIdentifier"} .block-special-duplicator-controls {
		text-align: right;
	}
	{"form.$sFormIdentifier"} .duplicator-controls-add {
	}
	{"form.$sFormIdentifier"} .duplicator-controls-remove {
	}

	/* Price block */
	{"form.$sFormIdentifier"} .block-area-prices .prices {
		width: 100%;
	}
	{"form.$sFormIdentifier"} .block-area-prices .prices .title {
		width: 75%;
	}
	{"form.$sFormIdentifier"} .block-area-prices .prices .title .note {
		font-size: 0.85em;
		color: grey;
	}
	{"form.$sFormIdentifier"} .block-area-prices .prices .text {
		text-align: right;
		vertical-align: top;
	}
	{"form.$sFormIdentifier"} .block-area-prices .prices .primary {
		font-weight: bold;
		color: #cf0000;
	}
	{"form.$sFormIdentifier"} .block-area-prices .prices .secondary.main-item {
		font-weight: bold;
	}

	{"form.$sFormIdentifier"} .block-prices-currency {
		display: none;
	}
	{"form.$sFormIdentifier"} .block-prices-print {
		float: right;
	}
	{"form.$sFormIdentifier"} .block-prices-print img {
		cursor: pointer;
	}

	/* Reset-link of confirmation page */
	{"form.$sFormIdentifier"} .page-navigation-submit-reset {
		display: block;
	}

	@media print {
		{"form.$sFormIdentifier"} * {
			visibility: hidden;
		}
		{"form.$sFormIdentifier"} .block-area-prices,
		{"form.$sFormIdentifier"} .block-area-prices * {
			 visibility: visible;
		}
		{"form.$sFormIdentifier"} .block-area-prices {
			position: absolute;
			left: 0;
			top: 0;
		}
	}

	/* Style of date selects */
	{"form.$sFormIdentifier"} .block-area-accommodations option.select-date-non-extra {
		font-weight: bold;
	}
	{"form.$sFormIdentifier"} .block-area-accommodations option.select-date-extra {
	}

</style>
