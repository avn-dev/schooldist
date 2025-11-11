var Company = Class.create(CoreGUI, {
	aData: [],
	
	/**
	 * siehe parent
	 */
	requestCallbackHook: function($super, aData) {
		
		$super(aData);
		
		if(
			(
				aData.action == 'openDialog' ||
				aData.action == 'saveDialogCallback' ||
				aData.action == 'update_select_options'
			)
			&&
			(
				aData.data.action == 'new' ||
				aData.data.action == 'edit'
			)
		) {
			// aData cachen um in weiteren Methoden verwenden zu können
			this.aData = aData.data;

			// Dependency Selection manuell aufbauen für Schule/Inboxselect
			this.setInboxDependencyEvents();
			
			// Alle Events im Tab Verbuchung
			this.setEventsAccountOptionsTab();
			
			// Zu diesen Elementen direkt ein "change" abfeuern,
			// damit die ein/ausblenderei auch beim ersten öffnen des Dialoges passiert
			var aElements = [
				'accounting_type',
				'interface',
				'customer_account_type',
				'agency_account_type',
				'agency_active_account_use_number',
				'agency_activepassive_account_use_number',
				'agency_account_booking_type',
				'customer_account_use_number',
				'agency_active_account_use_number',
				'agency_activepassive_account_use_number'
			];
			
			var oElement;
			
			aElements.each(function(sType){
				
				oElement = this.getDialogElement(sType);
				
				if(oElement)
				{
					this._fireEvent('change', oElement);
				}
				
			}.bind(this));
			
			var oTabAllocations = this.getAllocationTab();
			
			if(oTabAllocations)
			{
				Event.observe(oTabAllocations, 'click', function(e) {
					
					this.reloadAllocationTab();
					
				}.bind(this));
			}
			
			var oTabSettings = this.getTabByIndex('0');
			
			if(oTabSettings)
			{
				this._fireEvent('click', oTabSettings);
			}

		}
		else if(aData.action == 'set_allocation_html')
		{
			var oTabHeader = this.getAllocationTab();
			
			if(oTabHeader && aData.allocation_html)
			{
				var sTabBodyId = oTabHeader.id.replace('tabHeader', 'tabBody');
				
				var oTabBody = $(sTabBodyId);
				
				if(oTabBody)
				{
					oTabBody.innerHTML = aData.allocation_html;
					
					this.setEventsAccountNumbers(aData.vat_rates);
				}
			}
		}

	},
	
	/**
	 * Dependency Selection manuell aufbauen für Schule/Inboxselect
	 */
	setInboxDependencyEvents : function()
	{
		return;
		var sTableBodyId = 'dialog_'+this.aData.id+'_' + this.hash;
		
		if($(sTableBodyId))
		{
			var iJoinedObjectKey;
			
			var sElementId;
			
			// Alle Schul-multiselects durchgehen & event setzen
			$$('#' + sTableBodyId + ' .school_multiselects').each(function(oSchoolSelect) {
				
				Event.observe(oSchoolSelect, 'change', function(e) {
					
					sElementId = oSchoolSelect.id;

					sElementId = sElementId.replace('save['+this.hash+']['+this.aData.id+'][schools][ts_com_c][', '');

					// Container-ID des wiederholbaren Bereiches
					iJoinedObjectKey = sElementId.replace('][combinations]', '');
					
					this.executeInboxDependencyEvent(iJoinedObjectKey);
					
				}.bind(this));
				
			}.bind(this));	
		}
	},
	
	/**
	 * Beim hinzufügen eines Containers muss für das neue Element das Event gesetzt werden &
	 *  auch schon 1 mal ausgeführt werden, damit direkt nach dem hinzufügen die richtigen verfügbaren Optionen angezeígt werden
	 */
	addJoinedObjectContainerHook : function (oRepeat, sBlockId)
	{
		// Selection-Events neu setzen
		this.setInboxDependencyEvents();
		
		// Selection auf das neue hinzugefügte Element ausführen
		this.executeInboxDependencyEvent(sBlockId);
	},
	
	/**
	 * Nachgemachte Selection für den wiederholbaren Bereich ausführen
	 */
	executeInboxDependencyEvent : function(iJoinedObjectKey) {
		
		// Hier soll keine verfügbaren Kombinationen vorerst überprüft werden. Ist nicht unbedingt notwendig hier.
		return;

		if(
			typeof this.aData == 'undefined' ||
			typeof this.aData.id == 'undefined' ||
			typeof this.aData.action == 'undefined'
		) {
			return;
		}
		
		var sParam;
		
		sParam = '&task=update_inbox_options&child_id=' + iJoinedObjectKey;

		sParam += '&action=' + this.aData.action;

		sParam += '&' + $('dialog_form_' + this.aData.id + '_' + this.hash).serialize();

		this.request(sParam);
	},
	
	/**
	 * Alle Events im Tab Verbuchung
	 */
	setEventsAccountOptionsTab : function()
	{
		// Events - "Buchführung"
		var oAccountingType		= this.getDialogElement('accounting_type');
		
		if(oAccountingType)
		{
			Event.observe(oAccountingType, 'change', function(e) {
				
				this.toggleAccountingTypeElements(oAccountingType);
				
			}.bind(this));
		}

//		var oInterfaceSelect = $j(this.getDialogElement('interface'));
//		oInterfaceSelect.on('change', this.toggleInterfaceTypeElements.bind(this, oInterfaceSelect));
		
		// Events - "Kundenummer als Kontonummer verwenden"
		var oCustomerAccountUseNumber = this.getDialogElement('customer_account_use_number');
		
		if(oCustomerAccountUseNumber)
		{
			Event.observe(oCustomerAccountUseNumber, 'change', function(e) {
				
				this.toggleNummerrange('customer', oCustomerAccountUseNumber);
				
			}.bind(this));
		}
		
		// Events - "Verbuchungsart" bei den Agentureinstellungen
		var oAgencyAccountType = this.getDialogElement('agency_account_type');
		
		if(oAgencyAccountType)
		{			
			Event.observe(oAgencyAccountType, 'change', function(e) {
				
				this.toggleBookingTypeFieldset();
				
			}.bind(this));
		}
		
		// Events - "Verbuchungsart" bei den Agentureinstellungen
		var oAgencyAccountUseNumberActive = this.getDialogElement('agency_active_account_use_number');
		
		if(oAgencyAccountUseNumberActive)
		{
			Event.observe(oAgencyAccountUseNumberActive, 'change', function(e) {
				
				this.toggleNummerrange('agency_active', oAgencyAccountUseNumberActive);
				
			}.bind(this));
		}
		
		// Events - "Agenturnummer als Kontonummer verwenden" (aktiv)
		var oAgencyAccountUseNumberActivePassive = this.getDialogElement('agency_activepassive_account_use_number');
		
		if(oAgencyAccountUseNumberActivePassive)
		{
			Event.observe(oAgencyAccountUseNumberActivePassive, 'change', function(e) {
				
				this.toggleNummerrange('agency_activepassive', oAgencyAccountUseNumberActivePassive);
				
			}.bind(this));
		}
		
		// Events - "Agenturnummer als Kontonummer verwenden" (aktiv und passiv)
		var oAgencyAccountBookingType = this.getDialogElement('agency_account_booking_type');
		
		if(oAgencyAccountBookingType)
		{
			Event.observe(oAgencyAccountBookingType, 'change', function(e) {
				
				this.toggleBookingTypeFieldset();
				
				this.toggleFieldsetServiceExpenseCN();
				
			}.bind(this));
		}

		// Events – Gutschrift NICHT als Reduktion, Passiv-Aufwandskonten (CN) aktivieren bei einfacher BH
		var oReductionCheckbox = this.getDialogElement('service_account_book_credit_as_reduction');
		if(oReductionCheckbox) {
			oReductionCheckbox.observe('change', this.toggleFieldsetServiceExpenseCN.bind(this));
		}
	},
	
	/**
	 * Dialog-Element laden anhand der db_column
	 *
	 * @deprecated
	 */
	getDialogElement : function(sKey)
	{
		var sIdTag = 'save[' + this.hash + '][' + this.aData.id + '][' + sKey + ']';

		var oElement = $(sIdTag);
		
		return oElement;
	},
	
	/**
	 * Numberrange Select wird ausgeblendet, wenn "Agentur/Kundennummer als Kontonummer verwenden" aktiviert ist...
	 */
	toggleNummerrange : function(sType, oElementUseNumber)
	{
		if(!oElementUseNumber)
		{
			oElementUseNumber = this.getDialogElement(sType + '_account_use_number');
		}
		
		var oElementAccountingType	= this.getDialogElement('accounting_type');
		
		var sTypeAccountType;
		
		if(sType == 'customer')
		{
			sTypeAccountType = 'customer';
		}
		else
		{
			sTypeAccountType = 'agency';
		}
		
		var sKeyNumberrange			= sType + '_account_numberrange_id';
		
		var oElementNumberrangeId	= this.getDialogElement(sKeyNumberrange);
		
		var oElementAccountType		= this.getDialogElement(sTypeAccountType + '_account_type');
		
		var oElementAgencyAccountBookingType		= this.getDialogElement('agency_account_booking_type');
		
		if(oElementAccountingType && oElementUseNumber && oElementNumberrangeId && oElementAccountType)
		{
			if(
				oElementUseNumber.checked || 
				oElementAccountingType.value != 'double' || 
				oElementAccountType.value != '1' ||
				(
					sType == 'agency_activepassive' &&
					oElementAgencyAccountBookingType &&
					oElementAgencyAccountBookingType.value != '2'
				)
			)
			{
				this.actionRow(oElementNumberrangeId, 'hide');
				
				oElementNumberrangeId.removeClassName('required');
			}
			else
			{
				this.actionRow(oElementNumberrangeId, 'show');
				
				oElementNumberrangeId.addClassName('required');
			}	
		}
	},
	
	/**
	 * Komplette Zeile(Div) ausblenden anhand eines Elements im Dialog
	 */
	actionRow : function(oElement, sAction)
	{
		var oDivInput = oElement.parentNode;
		
		if(oDivInput)
		{			
			var oRow = oDivInput.up('.GUIDialogRow');
			
			if(oRow)
			{
				if(sAction == 'show')
				{
					oRow.show();
				}
				else
				{
					oRow.hide();
				}
			}
		}
	},

	/**
	 * Alle Events zu dem Feld "Buchführung" (einfache/doppelte)
	 * @param oAccountingType
	 */
	toggleAccountingTypeElements: function(oAccountingType) {
			
		// Kunde/Agentureinstellungen Fieldsets ausblenden
		['customer', 'agency'].each(function(sType){
			
			// Bei doppelter Buchführung die Kundeneinstellungen/Agentureinstellungen einblenden
			var oSettings	= $(sType + '_settings_' + this.hash + '_' + this.instance_hash);

			if(oSettings) {
				if(oAccountingType.value == 'double') {
					oSettings.show();
				} else {
					oSettings.hide();

					// Wenn das auf Aktiv/Passiv gestellt war, würden bei den Zuweisungen weiterhin Passiv-Aufwandskonten angezeigt werden
					this.getDialogElement('agency_account_booking_type').value = 0;
				}
			}
			
		}.bind(this));
		
		this.toggleNummerrange('customer');
		
		this.toggleNummerrange('agency_active');
		
		this.toggleNummerrange('agency_activepassive');

		var oAccountingInterface = $j(this.getDialogElement('interface'));

		// Sage/Quickbooks Basic deselektieren
//		if(
//			oAccountingType.value !== 'single' && (
//				oAccountingInterface.val() === 'sage_basic' ||
//				oAccountingInterface.val() === 'quickbooks_basic'
//			)
//		) {
//			oAccountingInterface.val(0);
//			oAccountingInterface.trigger('change');
//		}

		// Interface-Optionen Sage/Quickbooks Basic ist nur bei einfacher Buchhaltung verfügbar
//		oAccountingInterface.children('[value="sage_basic"]').prop('disabled', oAccountingType.value !== 'single');
//		oAccountingInterface.children('[value="quickbooks_basic"]').prop('disabled', oAccountingType.value !== 'single');

		// Leistungseinstellungen CN
		this.toggleFieldsetServiceExpenseCN();
	},

	/**
	 * @param {jQuery} oInterfaceSelect
	 */
//	toggleInterfaceTypeElements: function(oInterfaceSelect) {
//		var oReduceCheckbox = $j(this.getDialogElement('service_account_book_credit_as_reduction'));
//		var oReduceCheckboxRow = oReduceCheckbox.closest('.GUIDialogRow');
//
//		// Gutschrift als Reduktion ausblenden (und abwählen) bei Sage/Quickbooks Basic
//		if(
//			oInterfaceSelect.val() === 'sage_basic' ||
//			oInterfaceSelect.val() === 'quickbooks_basic'
//		) {
//			oReduceCheckbox.prop('checked', false);
//			oReduceCheckbox.trigger('change');
//			oReduceCheckboxRow.hide();
//
//			// Tab »Zuweisungen« und Einstellungen für Kontozuweisungen ausblenden
//			if(oInterfaceSelect.val() === 'sage_basic') {
//				$j('li.tab_allocation').hide(); // nur das li, sonst wird der Tab-Body auch gematched (#9549)
//				$j('.fieldset_service_allocation').hide();
//			} else {
//				$j('li.tab_allocation').show(); // nur das li, sonst wird der Tab-Body auch gematched (#9549)
//				$j('.fieldset_service_allocation').show();
//			}
//		} else {
//			oReduceCheckboxRow.show();
//			$j('li.tab_allocation').show(); // nur das li, sonst wird der Tab-Body auch gematched (#9549)
//			$j('.fieldset_service_allocation').show();
//		}
//	},
	
	/**
	 * Ein bestimmtes Fieldset Element laden (alle haben den gleichen Aufbau)
	 */
	getFieldset : function(sType)
	{
		var oFieldset = $('agency_' + sType + '_settings_' + this.hash + '_' + this.instance_hash);
		
		return oFieldset;
	},
	
	/**
	 * Wenn Kontotyp "individuell" dann "aktiv" Einstellungen einblenden
	 * Wenn Kontotyp "individuell" & "aktiv&passiv", dann "aktiv&passiv" einblenden
	 */
	toggleBookingTypeFieldset : function()
	{
		var oAgencyAccountType = this.getDialogElement('agency_account_type');
		
		var oAgencyAccountBokkingType = this.getDialogElement('agency_account_booking_type');
		
		var oFieldsetActive = this.getFieldset('active');

		var oFieldsetActivePassive = this.getFieldset('activepassive');
		
		if(
			oAgencyAccountType && 
			oAgencyAccountBokkingType && 
			oFieldsetActive && 
			oFieldsetActivePassive
		)
		{
			if(oAgencyAccountType.value == '1') // wenn individuell
			{
				oFieldsetActive.show(); // Fieldset "aktiv" Einstellungen einblenden
				
				if(oAgencyAccountBokkingType.value == '2') // wenn aktiv & passiv
				{
					oFieldsetActivePassive.show(); // Fieldset "aktiv & passiv" Einstellungen einblenden
				}
				else
				{
					oFieldsetActivePassive.hide();
				}
			}
			else
			{
				oFieldsetActive.hide();
				oFieldsetActivePassive.hide();
			}
			
			this.toggleNummerrange('agency_active');

			this.toggleNummerrange('agency_activepassive');
		}
	},
	
	/**
	 * Fieldset Aufwände CN einblenden wenn "doppelte" Buchführung & "aktiv&passiv"
	 */
	toggleFieldsetServiceExpenseCN : function()
	{
		var oAccountingType = this.getDialogElement('accounting_type');
		var oAgencyBookingType = this.getDialogElement('agency_account_booking_type');
		var oReduceCheckbox = this.getDialogElement('service_account_book_credit_as_reduction');
		var oInterfaceSelect = this.getDialogElement('interface');
		var oFieldsetExpenseCN = $('service_expense_cn_settings_' + this.hash + '_' + this.instance_hash);
		
		if(
			oAccountingType &&
			oAgencyBookingType &&
			oReduceCheckbox &&
			oFieldsetExpenseCN
		) {
			if(
				// Wiederholt auf Interface prüfen, da mal hier und mal da ein/ausgeblendet wird
				(
					// Einfache Buchführung + Gutschrift NICHT als Reduktion
					oAccountingType.value == 'single' &&
					!oReduceCheckbox.checked
				) || (
					// Doppelte Buchführung + Aktiv und Passiv
					oAccountingType.value == 'double' &&
					oAgencyBookingType.value == '2'
				)
			) {
				oFieldsetExpenseCN.show();
			} else {
				oFieldsetExpenseCN.hide();
			}
		}
	},
	
	/**
	 * Show-Errors ableiten, da die noch nicht anständige Fehler markieren kann bei wiederholbaren Bereichen
	 */
	showErrors: function($super, aErrorData, sDialogId, sType, bShowSkipErrors) {
		
		$super(aErrorData, sDialogId, sType, bShowSkipErrors);
		
		var iContainerId;
		
		var oContainer;
		
		aErrorData.each(function(aError) {
			
			if(
				aError.input &&
				aError.input.dbalias == 'combinations'
			)
			{
				iContainerId = aError.input.dbcolumn;
				
				// Den Kompletten Container-Div markieren
				oContainer = $('row_joinedobjectcontainer_combinations_' + iContainerId);
				
				if(oContainer)
				{
					oContainer.addClassName('GuiDialogErrorInput');
				}
			}
			
		});
		
	},
	
	/**
	 * Tab-Zuweisungen immer per Ajax laden wegen Performance
	 */
	reloadAllocationTab : function()
	{
		var sParam = '&task=reload_allocations';
		
		sParam += '&action=' + this.aData.action;
		
		sParam += '&' + $('dialog_form_' + this.aData.id + '_' + this.hash).serialize();
		
		this.request(sParam);
	},
	
	/**
	 * Zuweisungs-Tab einheitlich laden
	 */
	getAllocationTab : function()
	{
		var oTabAllocations = this.getTabByIndex('2');
		
		return oTabAllocations;
	},
	
	/**
	 * Zuweisungs-Tab einheitlich laden
	 */
	getTabByIndex : function(iIndex)
	{
		var oTab = $('tabHeader_'+iIndex+'_'+ this.aData.id +'_' + this.hash);
		
		return oTab;
	},
	
	setAccountNumbersToOtherVats : function(oInputDefaultVat, aVatRates) {

		var bDiscount = false;
		if(oInputDefaultVat.id.indexOf('_discount') != -1) {
			var bDiscount = true;
		}

		var sAutomaticCheckboxId	= oInputDefaultVat.id.replace('account_number_discount', 'automatic_account').replace('account_number', 'automatic_account');
		
		var oAutomaticCheckbox		= $(sAutomaticCheckboxId);

		/*
		 * Wenn es ein Automatikkonto ist, dann müssen zwangsläufig pro USt.-Art unterschiedliche Kontonummern verwendet werden.
		 * Einstellung "Alle Konten sind Automatikkonten" ist nicht berücksichtigt.
		 */
		if(
			oAutomaticCheckbox &&
			oAutomaticCheckbox.checked
		) {
			return false;
		}
		
		if(bDiscount) {
			var sInfo = oInputDefaultVat.id.replace('_account_number_discount', '');
		} else {
			var sInfo = oInputDefaultVat.id.replace('_account_number', '');	
		}
		
		var aInfo = sInfo.split('#');
		
		var oOtherInput;
		
		aVatRates.each(function(iVatRate) {	

			aInfo[6] = iVatRate;
			
			sInfo = aInfo.join('#');
			
			if(bDiscount) {
				sInfo += '_account_number_discount';
			} else {
				sInfo += '_account_number';
			}
			
			oOtherInput = $(sInfo);
			
			if(
				oOtherInput &&
				oOtherInput.value == ''
			) {
				oOtherInput.value = oInputDefaultVat.value;
			}
		});
		
		this.setEventsAccountNumbers(aVatRates);
	},
	
	setEventsAccountNumbers : function(aVatRates)
	{
		$$('.default_rate_input').each(function(oInput){
						
			oInput.stopObserving('keyup');
			oInput.observe('keyup', function(oEvent) {

				this.waitForInputEvent('setAccountNumbersToOtherVats', oEvent, oInput, aVatRates);

			}.bindAsEventListener(this));	
			
		}.bind(this));
	},
	
	prepareDependencyVisibilityHook: function(oElement, aValues, sElement, iIsIdElement, bHideElement) {
		
		var oElementCustomerAccountType = this.getDialogElement('customer_account_type');
		
		var oElementAgencyAccountType	= this.getDialogElement('agency_account_type');
		
		if(oElementCustomerAccountType === oElement)
		{
			var oUseNumber = this.getDialogElement('customer_account_use_number');
			
			if(oUseNumber)
			{
				this._fireEvent('change', oUseNumber);
			}
		}
		else if(oElementAgencyAccountType === oElement)
		{
			var oUseNumberActive = this.getDialogElement('agency_active_account_use_number');
			
			if(oUseNumberActive)
			{
				this._fireEvent('change', oUseNumberActive);
			}
			
			var oUseNumberActivePassive = this.getDialogElement('agency_activepassive_account_use_number');
			
			if(oUseNumberActivePassive)
			{
				if(oUseNumberActivePassive)
				{
					this._fireEvent('change', oUseNumberActivePassive);
				}	
			}
		}
		
	}
	
});