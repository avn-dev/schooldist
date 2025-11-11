
/*
 * Util Klasse
 */
var UtilGui = Class.create(CoreGUI, {
	oCurrencyFactorCache: {},

	requestCallbackHook: function($super, aData) {
		// RequestCallback der Parent Klasse

		$super(aData);
		
		var sTask = aData.action;
		var sAction = '';
		
		if(
			aData.data &&
			aData.data.action
		){
			sAction = aData.data.action;
		}

		if(aData.number_format){
			// laden der Schuleinstellungen für das zahlenformat der Schule
			this.numberFormat = aData.number_format;
		}

		if(sTask === 'getCurrencyConversionFactorCallback') {
			this.getCurrencyConversionFactorCallback(aData.data);
		} else if(sTask == 'calculateSchoolAmountCallback'){

			var fAmount			= aData.data.value_old.parseNumber();
			var fAmountSchool	= aData.data.value_new.parseNumber();

			var sInput = aData.data.input;

			var iFromCurrency = aData.data.vars.currency;
			var iToCurrency = aData.data.vars.currency_school;

			//Caching!
			if(!this.aCalculateSchoolAmountFactorCache){
				this.aCalculateSchoolAmountFactorCache = new Array();
			}

			if(!this.aCalculateSchoolAmountFactorCache[iFromCurrency]){
				this.aCalculateSchoolAmountFactorCache[iFromCurrency] = new Array();
			}

			if(!this.aCalculateSchoolAmountFactorCache[iFromCurrency][iToCurrency]){
				this.aCalculateSchoolAmountFactorCache[iFromCurrency][iToCurrency] = fAmountSchool / fAmount;
			}

			this.calculateSchoolAmounCallback(sInput, fAmountSchool);

		}else if(sTask == 'checkPaymentCurrencyCallback'){
			this.checkPaymentCurrencyCallback(aData.data.currency_id);
		} else if(
			aData.action=='openDialog' ||
			aData.action=='saveDialogCallback' //keine Action abfragen, das unterscheidet sich immer
		){
			this.aCalculatedAmountCache = new Array();

			this.aLastData = aData;
			if(aData.data){
				this.loadInputCurrencyConvert(aData.data.id, false);
			}

			$$('.currency_amount_row_checkbox').each(function(oCheckbox){
				oCheckbox.stopObserving();
				Event.observe(oCheckbox, 'change', function() {
					this.toggleAmountInputCurrencyConvertAmount(oCheckbox);
				}.bind(this));
			}.bind(this));

		}
		
	},

	loadInputCurrencyConvert: function (iId, bResetNewFields){

		this.aAmountInputCurrencyConvert = new Array();
		var i = 0;

		// Alle Währungsumrechnungsfelder aktivieren
		$$('.currency_amount_row_input_from').each(function(oInput, iKey){

			if(!oInput.hasClassName('currency_sum_row_input_from')){

				var oSelect		= oInput.next('select');
				
				var oLeftDiv	= oInput.up('div');
				var oRightDiv	= false;
				var oToInput	= false;

				var oHidden		= false;

				if(oLeftDiv){
					oRightDiv	= oLeftDiv.next('.currency_amount_row_rightdiv');
				}

				if(oRightDiv){
					oToInput	= oRightDiv.down('.currency_amount_row_input_to');
					oHidden		= oRightDiv.down('.currency_amount_row_input_hidden');
				}

				// Value für neue Inputs löschen
				if(
					bResetNewFields == true &&
					iKey == ($$('.currency_amount_row_input_from').length - 2)
				){
					oInput.value = this.thebingNumberFormat(0);
					oToInput.value = this.thebingNumberFormat(0);
				}

				oSelect.stopObserving();
				oInput.stopObserving();
				if(oToInput){
					oToInput.stopObserving();
				}

				oSelect.id = 'currency_amount_row_select_'+i;
				oInput.id = 'currency_amount_row_input_'+i;

				if(oHidden){
					oHidden.id = 'currency_amount_row_hidden_'+i;
				}

				if(oRightDiv){
					oRightDiv.id = 'currency_amount_row_rightdiv_'+i;
				}

				if(oToInput){
					oToInput.id = 'currency_amount_row_input2_'+i;
				}

				Event.observe(oInput, 'keyup', function() {
					this.prepareAmountInputCurrencyConvert(oInput, false, iId);
				}.bind(this));

				Event.observe(oSelect, 'change', function() {
					this.prepareAmountInputCurrencyConvert(oInput, true, iId);
				}.bind(this));
				
				//To Feld observer zum erneuten summieren der summenzeile
				if(oToInput){
					Event.observe(oToInput, 'keyup', function() {
						this.updateProviderPaymentSumRows();
					}.bind(this));
				}

				i++;
			}

		}.bind(this));
	},

	// Liefert alle Dialogfelder die einer CSS Klasse haben
	getDialogFieldsByClass: function(sClass){
		
		var sDialogId	= this.sCurrentDialogId;
				
		var aFields = new Array();
		$$('#dialog_'+sDialogId+'_'+this.hash+' .'+sClass).each(function(oInput){
			aFields[aFields.length] = oInput;
		}.bind(this));
		
		return aFields;	
	},
	
	copyGuiRow: function(oElement){
		if(oElement){
			var oRow = oElement.up('.GUIDialogRow');
			var oNewRow = oRow.clone(true);
			oRow.insert({after:oNewRow});
			this.loadInputCurrencyConvert(this.aLastData.data.id, true);
		}
	},

	/**
	 * @deprecated
	 */
	calculateSchoolAmount: function(fAmount, iFromCurrency, iToCurrency, sAction, iInquiry, sToInput, iSchool){

		fAmount = fAmount.parseNumber();

		if(!iSchool || iSchool == undefined){
			iSchool = 0;
		}

		if(
			this.aCalculateSchoolAmountFactorCache &&
			this.aCalculateSchoolAmountFactorCache[iFromCurrency] &&
			this.aCalculateSchoolAmountFactorCache[iFromCurrency][iToCurrency]
		){
			var sId = sToInput.replace('currency_amount_row_input2_', '');
			var sKey = 'amount_'+sId+'_'+iFromCurrency;
			this.aCalculatedAmountCache[sKey] = fAmount;
			var fAmountSchool = fAmount * this.aCalculateSchoolAmountFactorCache[iFromCurrency][iToCurrency];
			this.calculateSchoolAmounCallback(sToInput, fAmountSchool);
			return fAmountSchool;
		} else {
			var sRequest = '&task=calculateSchoolAmount';
			sRequest += '&action='+sAction;//allPayment
			sRequest += '&amount='+fAmount;
			sRequest += '&currency='+iFromCurrency;
			sRequest += '&currency_school='+iToCurrency;
			sRequest += '&inquiry='+iInquiry;
			sRequest += '&input='+sToInput;
			sRequest += '&school='+iSchool;//optional

			this.request(sRequest, '', '', false, 0, false);
		}

		return true;

	},

	/**
	 * Schreibt den errechneten Wert
	 * @deprecated
	 */
	calculateSchoolAmounCallback : function(sClassOfInput, fAmountSchool){
		var bFound = false;

		fAmountSchool = fAmountSchool.toFixed(2);

		if(!sClassOfInput.match(/\[/)){
			$$('.'+sClassOfInput).each(function(oInput){
				bFound = true;
				oInput.value = this.thebingNumberFormat(fAmountSchool);
			}.bind(this));
		}
		// Wenn die Klasse nicht gefunden wurd, prüfe ob es eine ID ist!
		// nötig für die Single Amount felder

		if(!bFound && $(sClassOfInput)){
			oInput = $(sClassOfInput);
			oInput.value = this.thebingNumberFormat(fAmountSchool);
			var iElementPos = sClassOfInput.replace('currency_amount_row_input2_', '');
			var oCurrencySchool = $('currency_amount_row_hidden_'+iElementPos);
			if(oCurrencySchool){
				var iCurrencySchool = oCurrencySchool.value;
				var Key = 'amount_'+iElementPos+'_'+iCurrencySchool;
				this.aCalculatedAmountCache[Key] = fAmountSchool;
			}
			this._fireEvent('keyup', oInput);
		}

		// Summenzeilen updaten
		this.updateProviderPaymentSumRows();

	},

	/**
	 * Währungsfaktor holen (Request oder aus Cache)
	 *
	 * @param {Number} iCurrencyFrom
	 * @param {Number} iCurrencyTo
	 * @param {String} sDateFormatted
	 * @param {String} [sAdditional]
	 */
	getCurrencyConversionFactor: function(iCurrencyFrom, iCurrencyTo, sDateFormatted, sAdditional) {

		// Cache prüfen, damit nicht jedes Eingabefeld einen Request abfeuert
		var sCacheKey = this.getCurrencyConversionFactorCacheKey(iCurrencyFrom, iCurrencyTo, sDateFormatted);
		if(sCacheKey in this.oCurrencyFactorCache) {
			this.getCurrencyConversionFactorCallback(this.oCurrencyFactorCache[sCacheKey]);
		} else {
			var sRequest = '&task=getCurrencyConversionFactor';
			sRequest += '&currency_from=' + iCurrencyFrom;
			sRequest += '&currency_to=' + iCurrencyTo;
			sRequest += '&date=' + sDateFormatted;

			if(sAdditional !== undefined) {
				sRequest += '&additional=' + sAdditional;
			}

			this.request(sRequest, '', '', false, 0, true);
		}

	},

	/**
	 * Request-Callback: Währung holen
	 *
	 * @param {Object} oData
	 */
	getCurrencyConversionFactorCallback: function(oData) {

		// Request cachen
		var sCacheKey = this.getCurrencyConversionFactorCacheKey(oData.currency_from, oData.currency_to, oData.date_input);
		this.oCurrencyFactorCache[sCacheKey] = oData;

	},

	/**
	 * @param {Number} iCurrencyFrom
	 * @param {Number} iCurrencyTo
	 * @param {String} sDateFormatted
	 * @returns {String}
	 */
	getCurrencyConversionFactorCacheKey: function(iCurrencyFrom, iCurrencyTo, sDateFormatted) {
		return iCurrencyFrom + '_' + iCurrencyTo + '_' + sDateFormatted;
	},
	
	// Updatet die Summenzeilen der Bezahldialoge unter Buchhaltung Lehrer/Unterk. bezahlen
	updateProviderPaymentSumRows : function(){
		
		var iAmountTotalFrom = 0;
		$$('.currency_amount_row_input_from').each(function(oInput){
			if(!oInput.hasClassName('currency_sum_row_input_from')){
				iAmountTotalFrom += oInput.value.parseNumber();
			}
		});

		var iAmountTotalTo = 0;
		$$('.currency_amount_row_input_to').each(function(oInput){
			if(!oInput.hasClassName('currency_sum_row_input_to')){
				iAmountTotalTo += oInput.value.parseNumber();
			}
		});

		$$('.currency_sum_row_input_from').each(function(oSumInput){
			oSumInput.value = this.thebingNumberFormat(iAmountTotalFrom);
		}.bind(this));

		$$('.currency_sum_row_input_to').each(function(oSumInput){
			oSumInput.value = this.thebingNumberFormat(iAmountTotalTo);
		}.bind(this));
	},

	prepareAmountInputCurrencyConvert : function(oInput, bNoTimeOberserve, sDialogId){
		

		if(!bNoTimeOberserve){
			if(
				this.aAmountInputCurrencyConvert &&
				this.aAmountInputCurrencyConvert[oInput]
			){
				clearTimeout(this.aAmountInputCurrencyConvert[oInput]);
			}

			if(!this.aAmountInputCurrencyConvert){
				this.aAmountInputCurrencyConvert = new Array();
			}

		}
    
        if(!bNoTimeOberserve){
            this.aAmountInputCurrencyConvert[oInput] = setTimeout(this.startAmountInputCurrencyConvert.bind(this), 500, oInput, sDialogId);
        } else {
            this.startAmountInputCurrencyConvert(oInput, sDialogId);
        }

	},

	// Eingabe fehler anzeigen
	displayAmountInputError: function(oInput, sDialogId, sErrorCode){

		if(sErrorCode === undefined) {
			sErrorCode = 'amount_exceeded';
		}

		var aAmountErrors = new Array();
		aAmountErrors[0] = this.getTranslation('general_error');
		aAmountErrors[1] = this.getTranslation(sErrorCode);
		this.displayErrors(aAmountErrors, sDialogId);
		this.displayAmountExceededError(oInput);

		// Entfernt, da Beträge immer zurückgesetzt werden (sollten)
		//$$('.dialog-button-dummy').each(function(oButton){
		//	oButton.remove();
		//});
		//
		//$$('.dialog-button').each(function(oButton){
		//	var oClone = oButton.clone(true);
		//	oClone.addClassName('dialog-button-dummy');
		//	oClone.style.backgroundColor = 'grey';
		//	oButton.insert({after:oClone});
		//	oButton.hide();
		//
		//});

	},

	removeAmountInputError : function(oInput, sDialogId, bIgnoreValue) {
			// Eingabe fehler ausblenden
		if(
			oInput.value.parseNumber() != 0 ||
			bIgnoreValue == true				// Fehler unabhängig vom Value ausblenden bzw. immer ausblenden
		){
			this.removeErrors(sDialogId);
			oInput.removeClassName('GuiDialogErrorInput');
			//$$('.dialog-button-dummy').each(function(oButton){
			//	oButton.remove();
			//});
			//$$('.dialog-button').each(function(oButton){
			//	oButton.show();
			//});
		}

	},

	// fehler Klasse ergänzen
	displayAmountExceededError : function(oInput){
		oInput.removeClassName('GuiDialogErrorInput');
		oInput.addClassName('GuiDialogErrorInput');
	},

	startAmountInputCurrencyConvert : function(oInput, sDialogId){

        var oMaxInput = oInput.previous('.currency_amount_row_input_max')

        if(
			oMaxInput &&
			oMaxInput.value.parseNumber() < oInput.value.parseNumber()
		){
            
			this.removeAmountInputError(oInput, sDialogId);
			this.displayAmountInputError(oInput, sDialogId);
            
		} else {
        
			this.removeAmountInputError(oInput, sDialogId);
            
            var iFromCurrency = $F(oInput.up('.GUIDialogRowInputDiv').down('.currency_amount_row_select'));
            var iToCurrency = $F(oInput.up('.GUIDialogRowInputDiv').down('.currency_amount_row_input_hidden'));
            var iSchool		= $F(oInput.up('.GUIDialogRowInputDiv').down('.currency_amount_row_input_hidden2'));
            var oSchoolInput = oInput.up('.GUIDialogRowInputDiv').down('.currency_amount_row_input_to');

            this.calculateSchoolAmount($F(oInput), iFromCurrency, iToCurrency, '', 0, oSchoolInput.id, iSchool)
        } 
		
	},

	toggleAmountInputCurrencyConvertAmount : function(oCheckbox){

		var oTd1 = oCheckbox.up('td');
		if(oTd1){
			var oTd = oTd1.next('.currency_amount_row_td');
			if(oTd){
				var oInput = oTd.down('.currency_amount_row_input_from');
				if(oCheckbox.checked){
					var oMaxAmount = oInput.previous('.currency_amount_row_input_max');
					if(oMaxAmount){
						oInput.value = this.thebingNumberFormat(oMaxAmount.value.parseNumber());
					}
				} else {
					oInput.value = this.thebingNumberFormat(0);
				}
			}
		}

		

		this._fireEvent('keyup', oInput);
	},

	thebingNumberFormat : function(fValue) {

		if(typeof fValue != 'number'){
			fValue = parseFloat(fValue);
		}

		var t = '';
		var e = '.';
		var iDecimalPlaces = 2;

		if(
			this.numberFormat
		) {

			if(
				typeof this.numberFormat == 'object'
			) {
			if(this.numberFormat.t){
				t = this.numberFormat.t;
			}
			if(this.numberFormat.e){
				e = this.numberFormat.e;
			}
			if(this.numberFormat.dec){
				iDecimalPlaces = this.numberFormat.dec;
			}
			} else {

			switch(this.numberFormat){
				case 1:
					t = ",";
					e = ".";
					break;
				case 2:
					t = " ";
					e = ".";
					break;
				case 3:
					t = " ";
					e = ",";
					break;
				case 4:
					t = "'";
					e = ".";
					break;
				case 5:
					t = ".";
					e = ",";
					break;
			}
			}

		}

		// Da JS ungenau rechnet, muss vorher gerundet werden
		fValue = fValue.toFixed(6);

		var sValue = String(fValue);

		// Nachkommastellen
		var iLastIndex = sValue.lastIndexOf('.');
		var sDecimal = sValue.substring(iLastIndex+1);

		// Nullen abschneiden
		sDecimal = sDecimal.replace(/0+$/, '');

		// Aktuelle Nachkommestellen
		var iCurrentDecimalPlaces = sDecimal.length;

		if(iCurrentDecimalPlaces > iDecimalPlaces) {
			if(iCurrentDecimalPlaces > 5) {
				iDecimalPlaces = 5;
			} else {
				iDecimalPlaces = iCurrentDecimalPlaces;
			}
		}

		fValue = parseFloat(fValue);

		//Thebing Number Format zurückgeben
			var sReturn = fValue.number_format(iDecimalPlaces, e, t);
			return sReturn;

	},

	// öffnet den Kommunikationsdialog eines Kunden
	openCommunicationDialog : function(action, additional, task, iSelectedId){ 
		this.request('&task='+task+'&action='+action+'&additional='+additional, '', '', false, iSelectedId);
	},
	
	// Sicherheitsabfrage vor dem canceln
	confirmCancelCommunicationDialog : function(action, additional, task, iSelectedId){ 
		
		var bCheck = confirm(this.getTranslation('accommodation_cancelation'));
		
		if(bCheck){
			this.openCommunicationDialog(action, additional, task, iSelectedId);
		}
	},

	toggleSupplierSelect : function(aData) {

	},

	/*convertDate : function(aDates, sAction){

		var sParam = '&task=convertDate';

		sParam += '&action='+sAction;

		aDates.each(function(sDate){
			sParam += '&date[]='+sDate;
		});

		this.request(sParam);
	},*/
	
});

// START Globale Thebing JS Funktionen

Number.prototype.parseNumber = function() {
	var number = this.toString();
	return number.parseNumber();
}

Number.prototype.subtract = function(fNumber, iPrecision) {

	if(!iPrecision) {
		iPrecision = 5;
	}

	var fResult = this - fNumber;

	var iFactor = Math.pow(10, 5);

	fResult = fResult * iFactor;

	fResult = Math.round(fResult);

	fResult = fResult / iFactor;

	return fResult;
}

/**
 * Achtung: Diese Methode berücksichtigt NICHT das eingestellte Nummernformat!
 * 	Gibt man als Beispiel 5.000 an, ist dies nicht 5000, sondern 5!
 *
 * @returns {Number}
 */
String.prototype.parseNumber = function() {
	var number = this.toString();

	// Wenn leer, aus Performancegründen direkt 0 zurückgeben
	if(number == '') {
		return 0;
	}

	number = number.replace(',','.');
	number = number.replace(' ','');
	number = number.replace("'",'');
	var aParts = number.split(".");
	var iCountParts = aParts.length;

	// Sonderfall: Tausendertrenner ist '.' es wird eine Zahl eingegeben ohne Nachkommastellen z.B. 1.234 -> gemeint ist 1234.00
	if(
		iCountParts == 2 &&
		aParts[1].length == 3
	){
		number = number.replace('.','');
		
		aParts = number.split('.'); // darf nurnoch einen eintrag jetzt haben
		iCountParts = aParts.length; // == 1
	}


	var sClearNumber = '';
	var sSeperator = '';
	var i = 1;

	aParts.each(function(sPart){
		if(i == iCountParts && iCountParts > 1){
			sSeperator = '.';
		}
		sClearNumber = sClearNumber+sSeperator+sPart;

		i++;
	});

	var iClearNumber = parseFloat(sClearNumber);

	if(isNaN(iClearNumber)){
		iClearNumber = 0;
	}

	return iClearNumber;

}

Number.prototype.number_format = function(decimals, dec_point, thousands_sep) {

	var number = this;
	if(isNaN(number)){
		number = 0;
	}
	var exponent = "";
	var numberstr = number.toString ();
	var eindex = numberstr.indexOf ("e");
	if (eindex > -1) {
		exponent = numberstr.substring (eindex);
		number = parseFloat (numberstr.substring (0, eindex));
	}

	if (decimals != null) {
		var temp = Math.pow (10, decimals);
		number = Math.round (number * temp) / temp;
	}
	var sign = number < 0 ? "-" : "";
	var integer = (number > 0 ? Math.floor (number) : Math.abs (Math.ceil (number))).toString ();
	var fractional = number.toString ().substring (integer.length + sign.length);
	dec_point = dec_point != null ? dec_point : ".";
	fractional = decimals != null && decimals > 0 || fractional.length > 1 ? (dec_point + fractional.substring (1)) : "";
	if (decimals != null && decimals > 0) {
		for (i = fractional.length - 1, z = decimals; i < z; ++i) {
			fractional += "0";
		}
	}

	thousands_sep = (thousands_sep != dec_point || fractional.length == 0) ? thousands_sep : null;
	if (thousands_sep != null && thousands_sep != "") {
		for (i = integer.length - 3; i > 0; i -= 3) {
			integer = integer.substring (0 , i) + thousands_sep + integer.substring (i);
		}
	}

	return sign + integer + fractional + exponent;

}



// ENDE
