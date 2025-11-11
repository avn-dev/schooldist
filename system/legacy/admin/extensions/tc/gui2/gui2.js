/*
 * Util Klasse
 */
var CoreGUI = Class.create(ATG2, {
	
	requestCallbackHook: function($super, aData)
	{
		try {
			var sTask = aData.action;
			$super(aData);

				// Nummerformat setzen
				if(
					aData &&
					aData.number_format
				){

					// Nur neu setzen, wenn noch nicht da oder force = 1
					if(
						!this.numberFormat ||
						(
							aData.number_format.force &&
							aData.number_format.force == 1
						)
					) {
						this.numberFormat = aData.number_format;
					}

				}
				// Fehler der flexieblen Speicherfelder

				if(sTask == 'showFlexError'){ 
					this.showFlexError(aData);
				}

				if(
					aData.action && 
                    aData.data &&
                    aData.data.action && 
                    (
                        aData.data.action == 'edit' ||
                        aData.data.action == 'new'
                    ) &&
					(
						aData.action == 'openDialog' ||
						aData.action == 'saveDialogCallback'						
					)
				) {	
					this.prepareCopyDesignElement();
					this.prepareDeleteDesignElement();
				} else if(
                    aData.data &&
                    aData.data.action && 
                    aData.data.action == 'executeIndexStack'
                ){
                    this.executeIndexStack(aData);
                } else if(
                    aData.data &&
                    aData.data.action && 
                    aData.data.action == 'executeIndexStackCallback'
                ){
                   this.executeIndexStackCallback(aData);
                }

				// Platzhaltersuche
				// Platzhalterbeispiele
				if(
					aData.action == 'openDialog' ||
					aData.action == 'saveDialogCallback' ||
					aData.action == 'reloadDialogTab'
				) {
					this.preparePlaceholderElement(aData.data);
				}

		} catch (exception) {
			console.debug(exception);
		}

	},
    
	prepareCopyDesignElement: function(){

		try {

			//copy element ist -1
			if(!this.aDesignIdCount){
				this.aDesignIdCount = new Array();
			}

			$$('.addDesignButton').each(function(oButtonDiv){

				if(!oButtonDiv.hasClassName('guiBarInactive')) {
					Event.stopObserving(oButtonDiv, 'click');
					Event.observe(oButtonDiv, 'click', function(){
						// Designelement kopieren
						this.copyDesignElement(oButtonDiv);
						// DELETE Icons vorbereiten
						this.prepareDeleteDesignElement();
						this.prepareCopyDesignElement();
					}.bind(this));					
				}
				
			}.bind(this));

		} catch (exception) {
			console.debug(exception);
		}
		
	},
	
	// Kopiert ein Designelement
	copyDesignElement : function(oButtonDiv){
		
		var sLoopDivTag = oButtonDiv.id.replace('add_', '');
		sLoopDivTag = sLoopDivTag.replace('_btn', '');
				
		// Aktueller Counter suchen
		// wenn es ihn nicht gibt auf -2 stellen ( da copy el. -1 ist )
		if(!this.aDesignIdCount[oButtonDiv.id]){
			this.aDesignIdCount[oButtonDiv.id] = -2;
		}

		// Copy Anzahl
		var oCopyCount = $(oButtonDiv.id.replace('btn', 'input'));

		// Copy Anzahl suchen
		var iCopyCount = 1;
		if(oCopyCount){
			iCopyCount = $F(oCopyCount);
			iCopyCount = parseInt(iCopyCount);
		}

		// Copy Element suchen
		var oCopyDiv = oButtonDiv.up('.add-btn-container').previous('.copyDesignDiv');

		if(oCopyDiv){

			// temp id vergeben
			oCopyDiv.id = 'temp_design_clone';
			// Multiselect löschen, damit das neu init klappt
			$$('#temp_design_clone .ui-multiselect').each(function(oMultiSelect){
				oMultiSelect.remove();
			});
			// temp id löschen
			oCopyDiv.id = '';

			// Koppier anzahl
			for(var i = 0; i < iCopyCount; i++){

				// Clonen
				var oClone = oCopyDiv.clone(true);

				// "-1" von LINKS nach RECHTS nur 1 MAL ! ersetzten ( falls verschachtelt )
				//var regexp = new RegExp( '\\]\\[-1\\][^-]*?' ,'g');
				//var regexp = new RegExp( '(".*?)(-1)' ,'g');
				
				// Replace darf nur auf die id- und name-Attribute passieren!
				// Ansonsten werden auch Namen o.Ä. ersetzt, wenn dort eine -1 etc vorkommt bsp. 9.-13.06.2014
				var regexp = new RegExp( '((id|name)=".*?)(-1)' ,'g');
				
				oClone.innerHTML = oClone.innerHTML.replace(regexp, '$1'+this.aDesignIdCount[oButtonDiv.id]);
				oClone.removeClassName('copyDesignDiv');
				oClone.id = 'design_div_'+sLoopDivTag+'_'+this.aDesignIdCount[oButtonDiv.id];
				oClone.show();

				// Element suchen wo das neue eingefügt werden soll
				var oInsertAfterDiv = oButtonDiv.up().previous('.designDiv');
				
				if(!oInsertAfterDiv){
					oInsertAfterDiv = oCopyDiv;
				}

				// Einfügen
				oInsertAfterDiv.insert({after: oClone});

				this.cloneDesignElementHook(oClone);
 
				// Counter zählen
				this.aDesignIdCount[oButtonDiv.id]--;

			}

		}
    
        this.prepareTCUploaders();
		
		var aData = {};
		aData.id = this.sCurrentDialogId;
		aData.values = [];
		this.prepareDialogContent(aData);
    
	},
	

	cloneDesignElementHook : function(oCloneElement){

	},

	prepareDeleteDesignElement: function(){

		try {
			$$('.removeDesignButton').each(function(oButtonDiv){
				if(!oButtonDiv.hasClassName('guiBarInactive')) {
					Event.stopObserving(oButtonDiv, 'click');
					Event.observe(oButtonDiv, 'click', function()
					{
						var oCopyDiv = oButtonDiv.up('.designDiv');
						if(oCopyDiv){
							if(confirm(this.getTranslation('delete_question'))){ 
                                this.removeDesignButtonClickHook(oButtonDiv, oCopyDiv);
								oCopyDiv.remove();                                
							}
						}
					}.bind(this));
				}
			}.bind(this));
		} catch (exception) {
			console.debug(exception);
		}
		
	},
	
    removeDesignButtonClickHook: function(oButton, oContainer) {
        
    },
    
	preparePlaceholderElement: function(oData){

		this.resizePlaceholderBody();

		var aPlaceholderContents = $j('#dialog_' + oData.id + '_' + this.hash).find('.placeholderContent');

		aPlaceholderContents.each(function (iIndex, oContent) {

			var oSearch = $j(oContent).find('.placeholderTools .placeholderSearchInput').get(0);
			var oLoading = $j(oContent).find('.placeholderTools .placeholderLoadingIndicator').get(0);

			if (oSearch) {
				$j(oSearch).keyup(function(oEvent) {
					$j(oLoading).show();
					this.waitForInputEvent('searchPlaceholder', oEvent.originalEvent, oContent, oSearch);
				}.bind(this));
			}

		}.bind(this));

	},
	
	resizeDialogSize: function($super, aData) {

		$super(aData);

		this.resizePlaceholderBody();
		
	},
	
	resizePlaceholderBody: function(){
		
		var aScroll = $$('.placeholderContentScroll');
		
		if(aScroll.length > 0){
		
			aScroll.each(function(oScroll) {			
		
				var oParent = $$('.GUIDialogNoScrolling')[0];
				var iHeight = oParent.getHeight();
				var iNewHeight = iHeight;
				var aOption = $j(oParent).find('.placeholderTools');

				aOption.each(function(i,oOption) {
					
					// neue Höhe berechnen
					var iOptionHeight = $j(oOption).height();
					
					// Beim ersten Öffnen des Dialoges ist iOptionHeight = 0
					if(iOptionHeight != 0){
						iNewHeight -= iOptionHeight - 30;
					} else {
						iNewHeight -= 30; // normale Höhe der Option-Bar
					}

				}.bind(this));

				// Höhe setzen
				oScroll.setStyle({
					height:  iNewHeight + 'px'
				});

			}.bind(this));
		
		}
		
	}, 
	
	searchPlaceholder: function(oContent, oInput) {

		// Inhalt
		var sSearch = $j(oInput).val();
		var sSearchRegExp = new RegExp(sSearch, 'i');

		var aPlaceholderLists = $j(oContent).find('.placeholdertable');
		var iLevel = 0;

		// Platzhalterlisten durchlaufen
		aPlaceholderLists.each(function(iTableIndex, oTable){

			var aRows = $j(oTable).find('.placeholderTableRow');

			// TRs durchlaufen
			aRows.each(function(iTrIndex, oTr){

				var aTds = oTr.children;				

				// gibt an, ob das Element eingeblendet werden soll
				var bMatches = false;

				// Level holen
				var sLevel = oTr.readAttribute('data-level');
				iLevel = parseInt(sLevel);
				var oTd1 = aTds[iLevel];

				// Erstes TD durchsuchen (Platzhalter)
				if(oTd1.innerHTML.match(sSearchRegExp)){
					bMatches = true;
				}	

				// Nur Elemente, die nicht vom Typ 'parent' sind, haben ein zweites TD
				// Zweites TD durchsuchen (Beschreibung)
				if(
					!oTr.hasClassName('parent') &&
					!bMatches
				){
					var oTd2 = aTds[(iLevel + 1)];
					if(oTd2.innerHTML.match(sSearchRegExp)){
						bMatches = true;
					}	
				}

				// Bei Treffer wird das Element hinzugefügen
				if(bMatches){
					this.showLines(oTr.id);
				} else {
					// Zeile ausblenden
					oTr.hide();
				}

			}.bind(this));

		}.bind(this));

		// Loading ausblenden
		var oLoading = $j(oContent).find('.placeholderTools .placeholderLoadingIndicator').get(0);
		oLoading.hide();	

	},

	showLines: function(sId){
		
		if(sId.include('_')){
			
			// ID in Array aufsplitten
			var aIds = sId.split('_');
			// Letztes Element löschen
			aIds.pop();
			// Neue ID generieren
			var sNewId = aIds.join('_');
			// Rekursiver Aufruf der Funktion
			this.showLines(sNewId);
			
		}
				
		// Zeile einblenden
		var oElement = $(sId);
		
		if(oElement){
			oElement.show();
		}
		
	},
	
	
	resizeHook: function(){
		
		// scrollbares Platzhalter Div anpassen
		this.resizePlaceholderBody();
		
	},

	showFlexError: function(aData){
		
		var aErrors = new Array();
		// DialogId
		var sDialogId = aData.data.id;

		var aId = sDialogId.split('_');

		iId = aId[1];

		if(
			aData.error &&
			aData.error.length > 0
		) {
			aErrors[0] = this.getTranslation('error_dialog_title');
			// Write all messages into the error DIV
			aData.error.each(function(aError){
				var sSelector = 'flex['+iId+']['+aError.field_id+']';
				var oInput = $(sSelector);
				var i = aErrors.length;
				var sMessage = aError.message;
				aErrors[i] = new Array();
				aErrors[i]['message'] = sMessage;
				if(oInput){
					aErrors[i]['input'] = new Array();
					aErrors[i]['input']['object'] = oInput;
				}
			}.bind(this));
			this.displayErrors(aErrors, aData.id);
		}
	},

	/**
	 * Diese Methode konvertiert eine Zahl unter Berücksichtung des Nummernformates
	 *
	 * @param {String} sNumber
	 * @return {Number}
	 */
	parseNumber: function(sNumber) {

		sNumber = sNumber.toString()
			.replace(/\s/g,'')
			.replace(/'/g,'');

		var e = '.';
		if(this.numberFormat) {
			e = this.numberFormat.e;
		}

		if(e === ',') {
			sNumber = sNumber
				.replace(/\./g, '')
				.replace(',', '.');
		} else if (e === '.') {
			sNumber = sNumber
				.replace(/,/g, '');
		}

		var fNumber = parseFloat(sNumber);
		if(isNaN(fNumber)){
			fNumber = 0;
		}

		return fNumber;
	}
	
});

// START Globale Thebing JS Funktionen

/**
 * Scrollt zu einem Platzhalter in der Liste und hebt ihn farblich hervor.
 * @param {string} placeholderScrollId
 */
function scrollToPlaceholder(placeholderScrollId) {
	const target = $j('#placeholderScrollId'+placeholderScrollId).closest('tr');
	const container = target.closest('.placeholderContentScroll');
	container.animate({
		scrollTop: target[0].offsetTop + 12
	}, 1000);
	target.children().addClass('placeHolderRowHighlight', 1000);
	// Ausblenden nach 2 Sekunden
	setTimeout(() => {
		target.children().removeClass('placeHolderRowHighlight');
	}, 2000);
}

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

String.prototype.padLeft = function(iDigits, sPad) {
	
	if(!sPad) {
		sPad = "0";
	}

	var sCounter = this;
	while(sCounter.length < iDigits) {
		sCounter = "0"+sCounter;
	}
	
	return sCounter;
	
}

String.prototype.padRight = function(iDigits, sPad) {
	
	if(!sPad) {
		sPad = "0";
	}

	var sCounter = this;
	while(sCounter.length < iDigits) {
		sCounter = sCounter+"0";
	}
	
	return sCounter;
	
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

Number.prototype.number_format = function(decimals, dec_point, thousands_sep, increment) {

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
		
		if(!increment) {
			increment = 1;
		}
		
		var temp = Math.pow (10, decimals) / increment;
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
