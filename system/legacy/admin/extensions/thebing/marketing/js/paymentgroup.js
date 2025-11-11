
var PaymentgroupGui = Class.create(PaymentConditionGui, {

	requestCallbackHook: function($super, aData) {

		// RequestCallback der Parent Klasse
		$super(aData);

		var sTask = aData.action;
		var sAction = aData.data.action;
		var aData = aData.data;

		if(	
			sTask == 'openDialog' ||
			sTask == 'saveDialogCallback'
		) {

			if(
				sAction == 'edit' ||
				sAction == 'new'
			) {
				this.setTabObserver(aData);
			}

		} else if(sTask == 'updatePaymentdataCallback') {

			if(
				aData.html &&
				aData.type &&
				$(aData.type+'_paymentgroup')
			){
				var oTableDiv = $(aData.type+'_paymentgroup');
				oTableDiv.update(aData.html);
				oTableDiv.show();
				this.setDialogObserver(aData);
			}

		}

	},

	/*
	 * Observer für Tab
	 */
	setTabObserver : function (aData){

		// Schulselect
		$$('#dialog_'+aData.id+'_'+this.hash+' .school_select').each(function(oSelect) {
			Event.observe(oSelect, 'change', function(e) {
				var iSchool = oSelect.value;
				// prüfen welches Tab
				var aMatch = oSelect.name.match(/^([a-z].*)\[([a-z].*)\]$/);
				var sTabType = aMatch[1];
				this.loadPaymentdata(iSchool, sTabType);
			}.bind(this));
		}.bind(this));

	},

	loadPaymentdata : function (iSchool, sTabType){

		if(iSchool > 0) {
			var strParameters = '&task=updatePaymentdataTab';
			strParameters += '&school_id='+iSchool;
			strParameters += '&type='+sTabType;
			this.request(strParameters);
		}

	},

    getStatusField: function(aData) {

        return $('saveid[status]');

    },

    getFinalDueStatusField: function(aData) {

        return $('saveid[final_due_status]');

    },

    getFinalDueDirectionField: function(aData) {

        return $('saveid[final_due_direction]');

    }
    
});
