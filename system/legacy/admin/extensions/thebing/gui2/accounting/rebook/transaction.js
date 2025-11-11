/*
 * Util Klasse
 */
var RebookGui = Class.create(UtilGui, {
	
	requestCallbackHook: function($super, aData) {
		
		$super(aData);
		
		
		this.bCheckRebookAmount = true;
		
		var oAmount0 = $('currency_amount_row_input_0');
		var oAmount1 = $('currency_amount_row_input_1');
		var oAmount2 = $('currency_amount_row_input_2');
		
		var oSelect0 = $('currency_amount_row_select_0');
		var oSelect1 = $('currency_amount_row_select_1');
		var oSelect2 = $('currency_amount_row_select_2');
		
		
		// Verändern der Amountfelder
		if(oAmount0){			
			Event.stopObserving(oAmount0, 'keyup');		
			Event.observe(oAmount0, 'keyup', function() {
				this.calculateRebookAmount(1);
				this.prepareCheckSameRebookAmount(oAmount0);
			}.bind(this));		
		}
		
		if(oAmount1){			
			Event.stopObserving(oAmount1, 'keyup');			
			Event.observe(oAmount1, 'keyup', function() {
				//this.calculateRebookAmount(2);
				this.prepareCheckSameRebookAmount(oAmount1);
			}.bind(this));		
		}

		if(oAmount2){			
			Event.stopObserving(oAmount2, 'keyup');			
			Event.observe(oAmount2, 'keyup', function() {
				this.prepareCheckSameRebookAmount(oAmount2);
			}.bind(this));		
		}
		
		// Verändern der Selectfelder
		if(oSelect0){			
			Event.stopObserving(oSelect0, 'change');		
			Event.observe(oSelect0, 'change', function() {
				this.calculateRebookAmount(1);
				this.updateHiddenField(oSelect0);
			}.bind(this));		
		}
		
		if(oSelect1){			
			Event.stopObserving(oSelect1, 'change');			
			Event.observe(oSelect1, 'change', function() {
				//this.calculateRebookAmount(2);
				this.prepareCheckSameRebookAmount(oAmount1);
				this.updateHiddenField(oSelect1);
			}.bind(this));		
		}

		if(oSelect2){
			Event.stopObserving(oSelect2, 'change');			
			Event.observe(oSelect2, 'change', function() {
				this.prepareCheckSameRebookAmount(oAmount2);
				this.updateHiddenField(oSelect2);
			}.bind(this));	
			
			// Nur Buchhaltungswährung erlaubt -> alle anderen sperren
			var aOptions = oSelect2.childElements();
			
			aOptions.each(function(oOption){

				oOption.disabled = false;
				if(
					oOption.selected != true 
				){
					oOption.disabled = true;
				}
			});
		}

		if(aData.action=='openDialog' && aData.data.action=='edit'){
			var oSelect = $('save['+this.hash+']['+aData.data.id+'][from_account_id]');
			this.reloadRebookCurrencySelect(aData.data,oSelect,true);
			var oSelect2 = $('save['+this.hash+']['+aData.data.id+'][account_id]');
			this.reloadRebookCurrencySelect(aData.data,oSelect2,true);
		}
		
	},
	
	updateHiddenField : function(oSelect){
		var oInput = oSelect.next();
		
		if(oInput.tagName == 'INPUT'){
			oInput.value = $F(oSelect);
		}
	},
	
	prepareCheckSameRebookAmount : function(oCurrentInput){
		
		if(this.getUpdateCheckSameRebookAmount) {
			clearTimeout(this.getUpdateCheckSameRebookAmount);
		}

		this.getUpdateCheckSameRebookAmount = setTimeout(this.checkSameRebookAmount.bind(this), 1000, oCurrentInput);
		
	},
		
	checkSameRebookAmount : function(oCurrentInput){
		
		this.fDisplayErrorDuration = 0;
		
		var oAmount0 = $('currency_amount_row_input_0');
		var oAmount1 = $('currency_amount_row_input_1');
		var oAmount2 = $('currency_amount_row_input_2');
		
		var fAmount0 = $F(oAmount0);
		var fAmount1 = $F(oAmount1);
		var fAmount2 = $F(oAmount2);
		

		var iCurrency0 = $F('currency_amount_row_select_0');
		var iCurrency1 = $F('currency_amount_row_select_1');
		var iCurrency2 = $F('currency_amount_row_select_2');

		var aErrors = new Array();
		var bError = false;

		// Beim Haben Konto -> Soll Konto Prüfen
		if(oCurrentInput.id == 'currency_amount_row_input_0'){
			
			if(
				iCurrency1 == iCurrency0 && 
				fAmount1 != fAmount0
			){
				oAmount1.value = this.thebingNumberFormat(oCurrentInput.value.parseNumber());
	
			} 
			
			
			if(
				iCurrency2 == iCurrency0 && 
				fAmount2 != fAmount0
			){
				oAmount2.value = this.thebingNumberFormat(oCurrentInput.value.parseNumber());	 	
			}

			oCurrentInput.value = this.thebingNumberFormat(oCurrentInput.value.parseNumber());
		}else if(oCurrentInput.id == 'currency_amount_row_input_1'){
			
			if(
				iCurrency0 == iCurrency1 && 
				fAmount0 != fAmount1
			){
				
				oAmount0.value = this.thebingNumberFormat(oCurrentInput.value.parseNumber());
				this.calculateRebookAmount(1);

			}
			
			if(
				iCurrency1 == iCurrency2 && 
				fAmount1 != fAmount2
			){
				oAmount2.value = this.thebingNumberFormat(oCurrentInput.value.parseNumber());
			}
			
		// Beim Buchhaltungskonto -> Haben und Soll Prüfen
		} else if(oCurrentInput.id == 'currency_amount_row_input_2'){
			
			if(
				iCurrency0 == iCurrency2 && 
				fAmount0 != fAmount2
			){
				oAmount0.value = this.thebingNumberFormat(oCurrentInput.value.parseNumber());
				this.calculateRebookAmount(1);

			}
			
			if(
				iCurrency1 == iCurrency2 && 
				fAmount1 != fAmount2
			){
				oAmount1.value = this.thebingNumberFormat(oCurrentInput.value.parseNumber());
				this.calculateRebookAmount(2);
			}

			
		}

	},
	
	
	
	prepareCalculateRebookAmount : function(iType){
		
		if(
			this.aCalculateReebookAmountTimout[iType] 
		){
			clearTimeout(this.aCalculateReebookAmountTimout[iType]);
		}

		if(!this.aCalculateReebookAmountTimout){
			this.aAmountInputCurrencyConvert = new Array();
		}
		
		this.aCalculateReebookAmountTimout[iType] = setTimeout(this.calculateRebookAmount.bind(this), 500, iType);
		
	},
	
	calculateRebookAmount : function(iType){
	
		var fAmount = 0;
		var iFromCurrency = 1;
		var iToCurrency = 1;
		var sAction = '';
		var iInquiry = 0;
		var sToInput = 'currency_amount_row_input_1';
		var iSchool = 0;
		
		// Soll -> Haben
		if(iType == 1){
			fAmount = $F('currency_amount_row_input_0');
			iFromCurrency = $F('currency_amount_row_select_0');
			iToCurrency = $F('currency_amount_row_select_1');
			sAction = '';
			iInquiry = 0;
			sToInput = 'currency_amount_row_input_1';
			iSchool = 0;
		// Haben -> Buchhaltung
		} else {
			fAmount = $F('currency_amount_row_input_1');
			iFromCurrency = $F('currency_amount_row_select_1');
			iToCurrency = $F('currency_amount_row_select_2');
			sAction = '';
			iInquiry = 0;
			sToInput = 'currency_amount_row_input_2';
			iSchool = 0;
		}
				
		this.calculateSchoolAmount(fAmount, iFromCurrency, iToCurrency, sAction, iInquiry, sToInput, iSchool);
				
	},
	
	reloadRebookCurrencySelect : function(aData, oSelect, bDontCalculate){
		
		var oSelect0 = $('currency_amount_row_select_0');
		var oSelect1 = $('currency_amount_row_select_1');
		
		// Einnahmen/Ausgaben Select
		var oTypeSelect = $('save['+this.hash+']['+aData.id+'][type]');
		var sType = 'income';
		if(oTypeSelect){
			sType = oTypeSelect.value;
		}

		var aCurrencyData = aData.account_currency_data;
		
		var iAccountCurrency = 1; 
		
		aCurrencyData.each(function(aData){
			if(aData.account == $F(oSelect)){
				iAccountCurrency = aData.currency;
			}
		});
		
		if(iAccountCurrency <= 0){
			iAccountCurrency = 1;
		}



		// Updaten der Währungen der Selects
		if(oSelect.name == 'save[from_account_id]'){
			
			var aOptions = oSelect0.childElements();
			aOptions.each(function(oOption){
				
				// Alle Optionen entsperren
				oOption.disabled = false;

				if(
					oOption.value == iAccountCurrency 
				){
					oOption.selected = true
				}else{
					if(sType == 'clearing'){
						// Verrechnungskonten dürfen NUR die eigene Währung auswählbar haben
						oOption.disabled = true;
					}
				}
			});

			oSelect0.next('input').value = iAccountCurrency;
		} else {
			var aOptions = oSelect1.childElements();
			aOptions.each(function(oOption){
				
				// Alle Optionen entsperren
				oOption.disabled = false;
				
				if(
					oOption.value == iAccountCurrency 
				){
					oOption.selected = true
				}else{
					if(sType == 'clearing'){
						// Verrechnungskonten dürfen NUR die eigene Währung auswählbar haben
						oOption.disabled = true;
					}
				}
			});
			
			oSelect1.next('input').value = iAccountCurrency;
		}

		if(!bDontCalculate){
			this.bCheckRebookAmount = false;
			this._fireEvent('keyup', $('currency_amount_row_input_0'));
			this.bCheckRebookAmount = true;
		}
	}
	
});