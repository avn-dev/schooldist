/*
 * Util Klasse
 */
var OfficeGui2 = Class.create(ATG2, {

	requestCallbackHook: function($super, aData)
	{

		try {

			$super(aData);

			if (aData.action == 'load_report') {

				$('report_container').update(aData.report);

			} else if (aData.action == 'openDialog') {

				$('year_selection').observe('change', function() {
					this.request('&action=test&task=load_report&year=' + $F('year_selection'));
				}.bind(this));

			}

		} catch (exception) {
			console.debug(exception);
		}

	},
});
