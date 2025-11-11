var $j = jQuery.noConflict();

var bBlockHideLoading = false;

var DesignerGui = Class.create(CoreGUI,
{
	
	initialize: function($super, sHash, iShowLeftFrame, iDebugMode, sInstanceHash) {
		$super(sHash, iShowLeftFrame, iDebugMode, sInstanceHash);
		this.aElementData			= {};
		this.iActiveAccordionPanel  = 0;
	},
	
	/**
	 * Request Callback
	 * Ableitung nötig da der HOOK beim sortieren nicht klappt da keine action zurückgegeben wird!
	 */
	requestCallback : function($super, objResponse, strParameters) {

		$super(objResponse, strParameters);

		var objData = this._evalJson(objResponse);

		if(objData) {
			// Prüfen ob keine Action
			// dann ist es der sortier request
			if(
				// Record FILTER ROW Dialog ( UNTERE GUI 3 )
				this.hash == this.sDesignFilterRowHash &&
				!objData.action
			) {
				// WICHTIG: Damit der Ladebalken nicht durch andere Request abgeschalten wird
				// wird dieser Flag gesetzt
				bBlockHideLoading = true;
				// OBERE GUI Holen
				var oParentGui = this.getOtherGuiObject(this.sDesignHash);
				oParentGui.reloadCurrentDialogTab(2);
			}
		}
		
	},
	
	toggleDialogTabHook: function($super, iTab, iDialogId) {

		$super(iTab, iDialogId);

		this.preparejQueryAccordions();
					
	},
	
	preparejQueryAccordions: function() {
		$$('.accordion').each(function(oElement) {
			if($j(oElement).hasClass("ui-accordion")) {
				// Das Accordion kann die Höhe zu setzen, wenn es beim Aufbau des Dialoges auf hidden steht. Deshalb muss
				// hier destroyed werden
				$j(oElement).accordion('destroy');
			}

			$j(oElement).accordion({
				header: 'h3', 
				autoHeight: true, 
				clearStyle: false, 
				collapsible: true, 
				heightStyle: 'content', 
				active: this.iActiveAccordionPanel,
				activate: function( event, ui ) {	
					
					var oHeader = ui.newHeader;

					if($j(oHeader).attr('data-panel-index')) {
						var sPanelIndex = $j(oHeader).attr('data-panel-index');
						this.iActiveAccordionPanel = parseInt(sPanelIndex);
					}					
					
				}.bind(this)
			});						
		}.bind(this));
	},
	
	// 
	requestCallbackHook: function($super, aData)
	{
		
		$super(aData);
		
		try {

			// Definieren der Hashes
			this.sDesignHash			= '023813039e316a4fae1427e1c1bddbc3';
			this.sDesignTabHash			= 'cc0c95554e4f4acf1f74f4cb6eedbaf3';
			this.sDesignTabElementHash	= 'dccf71353af317ae2b265227bfaa8dd8';
			this.sDesignFilterRowHash	= '41214a0dda513b5811075ea7eeb724da';
			this.sDesignFilterRowElementHash	= '7112d0e58f09d789a9ad3117ae385ca9';


			var sTask = '';
			var sAction = '';

			if(aData.action){
				
				sTask = aData.action;

				if(
					aData.data &&
					aData.data.action
				){
					sAction = aData.data.action;
				}
				
			}

			if(
				aData.task &&
				aData.task != ''
			)
			{
				sTask = aData.task;
				sAction = aData.action;
			}

			// aData in aCopy umgennenen da aData.data zu aData wird
			var aCopy = aData;

			if(aData.data){
				aData = aData.data;
			} else {
				aData = {};
			}

			if(aData.element_list_data){
				this.aElementData = aData.element_list_data;
			}

			// OBERE GUI
			if(
				(
					sTask == 'openDialog' ||
					sTask == 'saveDialogCallback' ||
					sTask == 'reloadDialogTab'
				) &&
				this.hash == this.sDesignHash
			) {

				if(
					sAction == 'new' ||
					sAction == 'edit'
				) {
					// Vermerk des aktuellen tabs löschen da der dialog neu aufgemacht wird
					// bei reload tab jedoch nicht da wir hier den zu vor gewählten wieder brauchen
					if(sTask != 'reloadDialogTab'){
						this.sActiveTab = '';
						this.iActiveAccordionPanel = 0;
					}
					// WICHTIG: Hier darf der lade balken wieder ausgeblendet werden
					bBlockHideLoading = false;
					// Ausblenden des ladebalken
					this.hideLoading();
					// Designer vorbereiten
					this.prepareDesigner(aData.id);
				}

			} else if(
				(
					sTask == 'openDialog' ||
					sTask == 'saveDialogCallback' ||
					sTask == 'reloadDialogTab' ||
					sTask == 'update_select_options'
				) && (
					// Record ELEMENT Dialog ( UNTERE GUI 2 )
					this.hash == this.sDesignTabElementHash ||
					// Record FILTER ROW Dialog ( UNTERE GUI 3 )
					this.hash == this.sDesignFilterRowHash ||
					// Record FILTER ROW ELEMENT Dialog ( UNTERE GUI 4 )
					this.hash == this.sDesignFilterRowElementHash
				)
			) {

				if(
					sAction == 'new' ||
					sAction == 'edit'
				) {
					// WICHTIG: Hier darf der lade balken wieder ausgeblendet werden
					bBlockHideLoading = false;
					// Ausblenden des ladebalken
					this.hideLoading();
					// wieder umstellen da die nächsten request den balken nicht deaktiviern dürfen
					bBlockHideLoading = true;

					// Element dialog vorbereiten
					if(this.hash == this.sDesignTabElementHash){
						this.prepareElementDialog(aData);
					} else if(this.hash == this.sDesignFilterRowElementHash){
						this.prepareFilterElementDialog(aData);
					}

				}

			} else if(
				(
					// Record TAB Dialog ( UNTERE GUI )
					this.hash == this.sDesignTabHash ||
					// Record ELEMENT Dialog ( UNTERE GUI 2 )
					this.hash == this.sDesignTabElementHash
				) && (
					sTask == 'closeDialog' ||
					sTask == 'closeDialogAndReloadTable' ||
					sTask == 'deleteCallback'
				)
			) {
				// WICHTIG: Damit der Ladebalken nicht durch andere Request abgeschalten wird
				// wird dieser Flag gesetzt
				bBlockHideLoading = true;
				// OBERE GUI Holen
				var oParentGui = this.getOtherGuiObject(this.sDesignHash);
				oParentGui.reloadCurrentDialogTab(1);
			} else if(
				(
					// Record FILTER ROW Dialog ( UNTERE GUI 3 )
					this.hash == this.sDesignFilterRowHash ||
					// Record FILTER ROW ELEMENT Dialog ( UNTERE GUI 4 )
					this.hash == this.sDesignFilterRowElementHash
				) && (
					sTask == 'closeDialog' ||
					sTask == 'closeDialogAndReloadTable' ||
					sTask == 'deleteCallback' ||
					sTask == "" ||
					!sTask
				)
			) {
				// WICHTIG: Damit der Ladebalken nicht durch andere Request abgeschalten wird
				// wird dieser Flag gesetzt
				bBlockHideLoading = true;
				// OBERE GUI Holen
				var oParentGui = this.getOtherGuiObject(this.sDesignHash);
				oParentGui.reloadCurrentDialogTab(2);
			}
		} catch (exception) {
			console.debug(exception);
		}
		
	},
	
	displayErrors : function($super, aErrorData, sDialogId, bNewDialog, bShowSkipErrors) {
		
		bBlockHideLoading = false;
		
		if(
			sDialogId == undefined && 
			this.hash != this.sDesignHash
		){
			var oDesignGui = this.getOtherGuiObject(this.sDesignHash);
			oDesignGui.displayErrors(aErrorData, sDialogId, bNewDialog, bShowSkipErrors);
		} else {
			$super(aErrorData, sDialogId, bNewDialog, bShowSkipErrors);
		}

		this.hideLoading();
		
	},
		
	/**
	 * zeigt den Ladebalken
	 */
	showLoading : function($super)
	{
		$super();
		// Eltern GUI Dialog ebenfalls auf loading stellen
		if(
			this.hash != this.sDesignHash &&
			this.sDesignHash &&
			this.sDesignHash != ""
		){
			// OBERE GUI Holen
			var oParentGui = this.getOtherGuiObject(this.sDesignHash);
			oParentGui.showLoading();
		}
	},

	/**
	 * blendet den Ladebalken "verzögert" aus
	 * damit bei mehreren Dialogen übereinander der ladebalken wartet bis alles
	 * fertig geladen ist!
	 */
	hideLoading : function($super)
	{
		if(!bBlockHideLoading){
			$super();
			// Eltener GUI Dialog ebenfalls hidden
			if(
				this.hash != this.sDesignHash &&
				this.sDesignHash &&
				this.sDesignHash != ""
			){
				// OBERE GUI Holen
				var oParentGui = this.getOtherGuiObject(this.sDesignHash);
				oParentGui.hideLoading();
			}
		}
	},
	
	// läd den Tab von AKTUELLEN Dialog neu
	reloadCurrentDialogTab : function(iTab){
		var sID = this.getCurrentIDString();
		// Tab 2 Neu Laden
		this.reloadDialogTab(sID, iTab);
	},
	
	// Gibt den ID String zrück (z.b ID_1)
	getCurrentIDString : function(){
		
		var sID = 'ID'

		if(this.selectedRowId){
			// ID String bauen
			this.selectedRowId.each(function(iId){
				sID += '_'+iId;
			});
		}
		

		if(sID == 'ID'){
			sID = 'ID_0';
		}
		
		return sID;
	},
	
	// Vorbereitung des Filter Element Dialoges
	prepareFilterElementDialog : function(aData){
		
		var oSelect = $('save['+this.hash+']['+aData.id+'][element_hash]');

		// Events setzten
		Event.stopObserving(oSelect, 'click');
		Event.observe(oSelect, 'change', function()
		{
			this.toggleFilterElementFields(oSelect, aData);
		}.bind(this));
		
		// Felder toggeln
		this.toggleFilterElementFields(oSelect, aData);
	
	},
	
	prepareElementDialog : function(aData){
		
		var oSelect = $('save['+this.hash+']['+aData.id+'][element_hash]');
		var oParentSelect = $('save['+this.hash+']['+aData.id+'][parent_element_id]');
		
		// Events setzten
		Event.stopObserving(oSelect, 'click');
		Event.observe(oSelect, 'change', function()
		{
			this.toggleElementFields(oSelect, aData);
		}.bind(this));
		
		Event.observe(oParentSelect, 'change', function()
		{
			this.toggleElementFields(oSelect, aData);
		}.bind(this));
		
		// Felder toggeln
		this.toggleElementFields(oSelect, aData);
	
	},
	
	// toggeln der Felder des Elementes
	toggleFilterElementFields : function(oSelect, aData){
		
		var aElements = aData.element_data;
		var oSelectTab = $('tabHeader_2_'+aData.id+'_'+this.hash);
		var oColumnsRow = this.getDialogRowForSaveField('columns', aData.id);
		
		oSelectTab.hide();
		oColumnsRow.hide();

		// Elementdaten durchgehen und aktuelles element suchen
		if(aElements){
			aElements.each(function(aData){
				if($F(oSelect) == aData.hash){
					
					if(
						aData.type == 'select' && 
						aData.special_type == ''
					){
						// Selectoptions Tab einblenden
						if(oSelectTab){
							oSelectTab.show();
						}
					}
					
					if(
						(
							aData.type == 'input' || 
							aData.type == 'select'
						) &&
						aData.special_type == ''
					){
						oColumnsRow.show();
					}
				}
			});
		}	
		
	},
	
	// toggeln der Felder des Elementes
	toggleElementFields : function(oSelect, aData){

		// Element daten auslesen
		aElements = aData.element_data;
		
		// Objecte holen
		var oRowParent = this.getDialogRowForSaveField('parent_element_id', aData.id);
		var oRowParentColumn = this.getDialogRowForSaveField('parent_element_column', aData.id);
		var oRowColumn = this.getDialogRowForSaveField('column_count', aData.id);
		var oRowRequired = this.getDialogRowForSaveField('required', aData.id);
		var oSelectTab = $('tabHeader_2_'+aData.id+'_'+this.hash);
		
		// Eltern Spalten auswahl ausblenden
		if(oRowParentColumn){
			oRowParentColumn.hide();
		}
		
		// Anzahl spalten ausblenden
		if(oRowColumn){
			oRowColumn.hide();
		}

		// Anzahl spalten ausblenden
		if(oSelectTab){
			oSelectTab.hide();
		}

		// Pflichtfeld-Einstellung ausblenden
		if(oRowRequired){
			oRowRequired.hide();
		}

		// Elementdaten durchgehen und aktuelles element suchen
		if (aElements) {
			var sSelectedHash;
			aElements.each(function(aData){
				sSelectedHash = $F(oSelect);
				if (sSelectedHash == aData.hash) {

					// Wenn Content bereich
					if (aData.type == 'content') {
						// Spalten eingabe aktivieren
						if(oRowColumn){
							oRowColumn.show();
						}
					} else if (aData.type == 'select') {
						// Selectoptions Tab einblenden
						if(oSelectTab){
							oSelectTab.show();
						}
					}

					if (oRowRequired && aData.required_setting) {
						// Pflichtfeld-Einstellung einblenden
						oRowRequired.show();
					}

				}
			});
		}		
		
		// Prüfen ob ein Eltern Element eingestellt ist
		if(oRowParent){
			// Wenn ja
			if ($F($(oRowParent).down('select')) > 0) {
				// Spalten Eingabe anzeigen
				if(oRowParentColumn){
					oRowParentColumn.show();
				}
			}
		}
	},
	
	// Sucht eine Dialog Row von einem Element
	getDialogRowForSaveField: function(sDbColumn, sDialog){
		
		var oElement = $('save['+this.hash+']['+sDialog+']['+sDbColumn+']');
		
		if(oElement){
			var oRow = oElement.up('.GUIDialogRow');
			if(oRow){
				return oRow;
			}
		}
		
		return false;
	},
	
	getDesignerDialogElementData: function(sHash){

		var aFinalData = {};

		if(
			this.aElementData &&
			this.aElementData.length > 0
		){
			this.aElementData.each(function(aData){
				if(
					aData &&
					aData.hash &&
					aData.hash == sHash
				){	
					aFinalData = aData;
				}
			});
		}
		
		return aFinalData;
	},

	// bereitet den Designer vor
	prepareDesigner : function(iPageID){
			
		this.preparejQueryAccordions();	
			
		// Icon events verteilen
		this.prepareDesignerIcons();	
		
		// aktiven Tab suchen
		var bActiveTab = false;
		// Tabs
		$$('.form_pages_tab').each(function(oTab){
			
			
			if(
				this.sActiveTab &&
				this.sActiveTab != "" &&
				oTab.id == this.sActiveTab
			){
				this.setActiveDesignerTab(oTab);
			} else if(
				!this.sActiveTab ||
				this.sActiveTab == ""	
			){
				if(!bActiveTab){
					this.setActiveDesignerTab(oTab);
					bActiveTab = true;
				}
			}
			
			// Event auf den Tab setzten
			Event.stopObserving(oTab, 'click');
			Event.observe(oTab, 'click', function()
			{
				this.setActiveDesignerTab(oTab);
			}.bind(this));
			
			
			
		}.bind(this));
		
		 // Linke Element liste Draggable machen
	   var aBlocks = $$('.elementBox');
		
		aBlocks.each(function(oBlock)
		{
			$j(oBlock).sortable(
			{
				appendTo: $('form_pages_content'),
				scroll: true,
				helper: 'clone',
				revert: true,
				connectWith: '.form_pages_content' 
			});
			
		}.bind(this));	
		
				
		// Tabs sortable machen
		$j('#form_pages_tabs').sortable({
			items : '> *',
			axis: 'x',
			update: function(event, oElement) { 
				var sOrder = $j('#form_pages_tabs').sortable( "serialize");
				var oTabGui = this.getOtherGuiObject(this.sDesignTabHash);
					// WICHTIG: Damit der Ladebalken nicht durch andere Request abgeschalten wird
					// wird dieser Flag gesetzt
					bBlockHideLoading = true;
					oTabGui.request('&task=moveTab&action=move&'+sOrder);
			}.bind(this)
		});

	     // Elemente Sortabel machen
	   $$('.form_pages_content').each(function(oSortable){
	
			$j(oSortable).sortable({
				items : '.areas',
				connectWith : '.form_pages_content',
				forceHelperSize: true,
				forcePlaceholderSize: true,
				//placeholder: 'sortable-placeholder',
				change: function(event, oElement) {
					$$('.designer_parent_check_success').each(function(oTemp){
						oTemp.removeClassName('designer_parent_check_success');
					})
					$$('.designer_parent_check_failed').each(function(oTemp){
						oTemp.removeClassName('designer_parent_check_failed');
					})
					
					// Aktuelles Drag Element
					var oBlock = oElement['item'][0]; 					
					var sBlockKey = oBlock.id;	
					var aMatch = oSortable.className.match(/(element_[a-zA-Z0-9]*)/gi);
					var sDropHash = '';
					
					if(aMatch && aMatch[0]){
						var sDropHash = aMatch[0];
					}

					var icheckDesignerParentTabElement = this.checkDesignerParentTabElement(sBlockKey, sDropHash);

					if(
						icheckDesignerParentTabElement == 1
					){
						oSortable.addClassName('designer_parent_check_success');
					} else if(
						icheckDesignerParentTabElement == 0
					) {
						oSortable.addClassName('designer_parent_check_failed');
					}
				}.bind(this),
				// ### MOVE ###
				update: function(event, oElement) {

					// Aktuelles Drag Element
					var oBlock = oElement['item'][0]; 

					// wenn es eine korrekte "Area" ist
					if(oBlock.hasClassName('areas')){
						// ALLE Sortabels Speichern
						// zuerst wollt ich sie einzeln speichern..
						// aber urplötzlich löste sich nur noch das event der haupt sortable aus...
						// kp warum aufjedenfall geht es so auch und ist performatner ( nur 1 request anstelle von x )
						this.prepareDesignerTabElementSorting();
					}
					
				}.bind(this),
				// ### DRAG ###
				receive: function(event, oDrag) {

					// Drag ITEM
					var oBlock = oDrag['item'][0];
					var oSender = oDrag['sender'][0];
					// Darf keine area sein
					if(!oBlock.hasClassName('areas')){

						if(this.loadDesignAddTabElementObserver){
							clearTimeout(this.loadDesignAddTabElementObserver);
						}

						this.loadDesignAddTabElementObserver = setTimeout(this.executeDesignAddTabElement.bind(this, oBlock, oSortable, oSender), 500);

					
					}
				}.bind(this),
				activate: function () {
					$j('.form_pages_content .field-content').hide();
					$j('.form_pages_content .form_tab_icons').hide();
				},
				deactivate: function () {
					$j('.form_pages_content .field-content').show();
					$j('.form_pages_content .form_tab_icons').show();
				}
		   }).disableSelection();
		   
		}.bind(this));
		
		// Filter Zeilen
		$j('#form_pages_filter_rows').sortable({
			items: 'tr',
			axis: 'y',
			update: function(event, oElement) { 
				// Row Gui holen
				var oRowGui = this.getOtherGuiObject(this.sDesignFilterRowHash);
				// WICHTIG: Damit der Ladebalken nicht durch andere Request abgeschalten wird
				// wird dieser Flag gesetzt
				bBlockHideLoading = true;
				var sOrder = $j('#form_pages_filter_rows').sortable( "serialize");
				sOrder = sOrder.replace(/form_pages_filter_row/gi,'sortablebody_'+oRowGui.hash);
				oRowGui.request('&task=saveSort&'+sOrder);
			}.bind(this)
		});
			   
		// Filter Elemente
		$j('.filter_row_content').sortable({
			items: '.filter_row_element',
			revert: true,
			connectWith: '.filter_row_content',
			forceHelperSize: true,
			forcePlaceholderSize: true,
			update: function(event, oElement) { 
				// DRAG Element
				var oDragElement = oElement.item[0];
				
				// Nur wenn es keine "Vorlage" ist
				// weil sonst ist es kein sortieren sondern ein Draggen
				if(!oDragElement.hasClassName('filter_element_list')){
					
					var iDropId = this.id;
					iDropId = iDropId.replace('filter_row_', '');	
					
					var sOrder = '';
					
					$$('.filter_row_content').each(function(oSortable){
						var iRow = oSortable.id;
							iRow = iRow.replace('filter_row_', '');
						var sSeri = $j(oSortable).sortable("serialize");
							sSeri = sSeri.replace(/filter_row_element/gi, 'filter_row_'+iRow+'_element');
						sOrder += '&';
						sOrder += sSeri;
					})					
					
					var oGui = $j('.filter_row_content').sortable( "option", "oGUI");
					var oFilterElementGui = oGui.getOtherGuiObject(oGui.sDesignFilterRowElementHash);
					oFilterElementGui.request('&task=moveElement&action=move'+sOrder);
					
				}
				
			},
			receive: function(event, oElement){
				
				// DRAG Element
				var oDragElement = oElement.item[0];

				if(oDragElement.hasClassName('filter_element_list')){

					var iDropId = this.id;
						iDropId = iDropId.replace('filter_row_', '');				

					var sOrder = $j('.filter_row_content').sortable( "serialize");
					
					var oGui = $j('.filter_row_content').sortable( "option", "oGUI");
					// Element ID holen
					var sElementHash = oDragElement.id;
					var oFilterElementGui = oGui.getOtherGuiObject(oGui.sDesignFilterRowElementHash);

					oGui.setActiveDesignerFilterRow(iDropId);
					oGui.setActiveDesignerFilterRowElement('0');	
					// WICHTIG: Damit der Ladebalken nicht durch andere Request abgeschalten wird
					// wird dieser Flag gesetzt
					bBlockHideLoading = true;
					oFilterElementGui.request('&task=openDialog&action=new&element_hash='+sElementHash+'&row_id='+iDropId+'&'+sOrder);
				}
			}
		}).disableSelection();
		
		$j('.filter_row_content').sortable( "option", "oGUI" , this );
		
		 // Linke Filter Element liste Draggable machen
		$j('.filter_element_list').draggable(
		{
			appendTo: $('filter_content'),
			helper: 'clone',
			revert: true,
			connectToSortable: '.filter_row_content'
		});		

	},
	
	checkDesignerParentTabElement: function(sBlockKey, sDropHash){

		var aCurrentElementData = this.getDesignerDialogElementData(sBlockKey);
		
		if(
			aCurrentElementData.allowed_parent === null
		) {
			return 2;
		} else if(
			aCurrentElementData.allowed_parent === sDropHash
		) {
			return 1;
		}
		
		return 0;
	},
	
	executeDesignAddTabElement : function(oBlock, oDrop, oSender){

		// Element ID holen
		var sBlockKey = oBlock.id;
		var iBlockId = sBlockKey.replace('element_');
		// Eltern (drop) id
		var iDropId = oDrop.id;
		// Eltern (drop) spalte
		var iColumn = 0;

		// Eltern ID suchen
		if(iDropId.match(/form_pages_content_block_1_/)){
			iDropId = iDropId.replace('form_pages_content_block_1_', '');
			iColumn = 1;
		} else if(iDropId.match(/form_pages_content_block_2_/)){
			iDropId = iDropId.replace('form_pages_content_block_2_', '');
			iColumn = 2;
		} else {
			iDropId = 0;
		}

		// Tab conten suchen
		var oDesignTabContent;
		if(oDrop.hasClassName('form_pages_content')){
			oDesignTabContent = oDrop;
		} else {
			oDesignTabContent = oDrop.up('.form_pages_content')
		}

		var aMatch = oDrop.className.match(/(element_[a-zA-Z0-9]*)/gi);
		var sDropHash = '';
		if(aMatch && aMatch[0]){
			sDropHash = aMatch[0];
		}

		var icheckDesignerParentTabElement = this.checkDesignerParentTabElement(sBlockKey, sDropHash);

		if(
			oDesignTabContent.style.display != 'none' &&
			icheckDesignerParentTabElement > 0
		) {

			// Tab suchen
			var sDesignTab = oDesignTabContent.id;
			sDesignTab = sDesignTab.replace('_content', '');

			var oDesignTab = $(sDesignTab);

			// Tab auswählen damit die IDs stimmen
			if(oDesignTab){
				this.setActiveDesignerTab(oDesignTab);
			}

			// Sortierung holen
			var sOrder = $j(oDrop).sortable( "serialize");


			var oElementGui = this.getOtherGuiObject(this.sDesignTabElementHash);
			this.setActiveDesignerTabElement('0');	
			// WICHTIG: Damit der Ladebalken nicht durch andere Request abgeschalten wird
			// wird dieser Flag gesetzt
			bBlockHideLoading = true;
			oElementGui.request('&task=openDialog&action=new&element_hash='+sBlockKey+'&parent_element_id='+iDropId+'&parent_element_column='+iColumn+'&'+sOrder);
			return true;
		} else if (
			oDesignTabContent.style.display != 'none' &&
			icheckDesignerParentTabElement == 0
		) {
			var aErrors = new Array();
			aErrors[0] = new Array();
			aErrors[0]['message'] = this.getTranslation('wrong_parent');
			this.displayErrors(aErrors);
			//$('fix_tab_elements').insert({top: oBlock});
			oSender.insert({top: oBlock});
			return false;
		}

	},
	
	// Saving all Sortable List at once!
	prepareDesignerTabElementSorting : function(){
		
		var sOrderFinal = '';
		
		 $$('.form_pages_content').each(function(oSortable){

			var sOrder = $j(oSortable).sortable( "serialize");
			// ElternID ermitteln
			var iDropId = oSortable.id;
			var iColumn = 0;

			// ElternID ermitteln
			if(iDropId.match(/form_pages_content_block_1_/)){
				iDropId = iDropId.replace('form_pages_content_block_1_', '');
				iColumn = 1;
			} else if(iDropId.match(/form_pages_content_block_2_/)){
				iDropId = iDropId.replace('form_pages_content_block_2_', '');
				iColumn = 2;
			} else {
				iDropId = 0;
			}

			var sTempOrderFinal = '&'+sOrder.replace(/element\[\]/gi, 'element['+iDropId+']['+iColumn+'][]');
			
			// Kurze erklärung zum volgenden abschnitt...
			// da dieses dumme sortable beim äusersten auch die inneren als sortable elemente mit aufnimmt
			// und wir die inneren aber nacher auch noch sortablen haben wir doppelte einträge!
			// daher wird im unteren abschnitt geschaut ob die ID bereits im String vorhanden ist
			// wenn ja wird sie gelöscht und mit den aktuellen sortable daten hinzugefügt
			// Wichtig! Falls ihrgendwann das äuserste Sortable nichtmehr das erste in der schleife ist
			// muss das hier verändert werden!! das klappt nur wenn es von ausen nach innen geht
			var aTemp = sTempOrderFinal.split('&');
			
			aTemp.each(function(sPart){
				
				if(
					sPart && 
					sPart != ""
				){
					
					var aPartMatch = sPart.match(/=[0-9]+/gi);
					var sPartMatch = aPartMatch[0];
					
					var aTempOriginal = sOrderFinal.split('&');
					for(var key = 0; key < aTempOriginal.length; ++key) {
						
						var aLoopMatch = aTempOriginal[key].match(/=[0-9]+/gi);
						if(aLoopMatch) {
							var sLoopPart = aLoopMatch[0];
							
							if(
								aTempOriginal[key] !== sPart &&
								sLoopPart === sPartMatch
							) {
								aTempOriginal.splice(key, 1);
							}
						}						
					}
					
					sOrderFinal = aTempOriginal.join('&');
					
					//var exp = new RegExp('\\&element\\[[0-9]+\\]\\[[0-9]+\\]\\[\\]' + sPart2);
					//sOrderFinal = sOrderFinal.replace(exp, '');
				}
				
			});
			
			sOrderFinal += sTempOrderFinal;
			
		}.bind(this));

		// Element Gui holen
		var oElementGui = this.getOtherGuiObject(this.sDesignTabElementHash);
		// WICHTIG: Damit der Ladebalken nicht durch andere Request abgeschalten wird
		// wird dieser Flag gesetzt
		bBlockHideLoading = true;
		oElementGui.request('&task=moveAllElements&action=move'+sOrderFinal);
		
	},
	
	
	
	/**
	 * Bereitet alle ICONS des Designers vor (Observer setzen)
	 */
	prepareDesignerIcons: function(){
		
		/**
		 *	DIALOG ELEMENTE
		 */

		if($('add_page_icon')){
			// New page icon
			Event.stopObserving($('add_page_icon'), 'click');
			Event.observe($('add_page_icon'), 'click', function()
			{
				var oTabGui = this.getOtherGuiObject(this.sDesignTabHash);
				oTabGui.unselectAllRows(false);
				oTabGui.request('&task=openDialog&action=new');
			}.bind(this));
		}
		
		// Edit tab Icons
		$$('.edit_tab').each(function(oIcon){
			
			Event.stopObserving(oIcon, 'click');
			Event.observe(oIcon, 'click', function()
			{
				
				var iTabId = oIcon.id.replace('edit_tab_', '');
				var oTab = $('pages_tab_'+iTabId);
				
				// Tab selectieren ( im dialog und in der Unteren GUI
				this.setActiveDesignerTab(oTab);
				
				var oTabGui = this.getOtherGuiObject(this.sDesignTabHash);			
				oTabGui.request('&task=openDialog&action=edit');
				
			}.bind(this));
		}.bind(this));
		
		// Remove Tab Icons
		$$('.remove_tab').each(function(oIcon){
			// Edit page icon
			Event.stopObserving(oIcon, 'click');
			Event.observe(oIcon, 'click', function()
			{
				
				var sConfirmMessage = this.getTranslation('really')
				if(confirm(sConfirmMessage)){
					var iTabId = oIcon.id.replace('remove_tab_', '');

					var oTabGui = this.getOtherGuiObject(this.sDesignTabHash);
					oTabGui.unselectAllRows(false);
					var aTabID = new Array();
					aTabID[0] = iTabId;
					oTabGui.setSelectedRows(aTabID);				
					oTabGui.request('&task=deleteRow');
				}	
				
			}.bind(this));
		}.bind(this));
		
		// Element Edit
		$$('.element_edit_img').each(function(oIcon){
	
			Event.stopObserving(oIcon, 'click');
			Event.observe(oIcon, 'click', function()
			{

				var iElementId = oIcon.id.replace('edit_element_', '');
	
				this.setActiveDesignerTabElement(iElementId);

				var oTabElementGui = this.getOtherGuiObject(this.sDesignTabElementHash);			
				oTabElementGui.request('&task=openDialog&action=edit');
				
			}.bind(this));
		}.bind(this));
		
		// Element Delete
		$$('.element_remove_img').each(function(oIcon){
	
			Event.stopObserving(oIcon, 'click');
			Event.observe(oIcon, 'click', function()
			{
				var iElementId = oIcon.id.replace('remove_element_', '');
				
				var sConfirmMessage = this.getTranslation('really')
				if(confirm(sConfirmMessage)){

					this.setActiveDesignerTabElement(iElementId);

					var oTabElementGui = this.getOtherGuiObject(this.sDesignTabElementHash);			
					oTabElementGui.request('&task=deleteRow');
					
				}	

			}.bind(this));
		}.bind(this));
		
		
		/**
		 *
		 * FILTER ELEMENTE
		 * 
		 */
		if($('add_filter_row_icon')){
			Event.stopObserving($('add_filter_row_icon'), 'click');
			Event.observe($('add_filter_row_icon'), 'click', function()
			{
				var oRowGui = this.getOtherGuiObject(this.sDesignFilterRowHash);
				oRowGui.unselectAllRows(false);
				oRowGui.request('&task=openDialog&action=new');
			}.bind(this));
		}
		
		// Filter Row Edit
		$$('.edit_filter_row').each(function(oIcon){
			
			Event.stopObserving(oIcon, 'click');
			Event.observe(oIcon, 'click', function()
			{
				var iRowId = oIcon.id.replace('edit_filter_row_', '');
				
				this.setActiveDesignerFilterRow(iRowId);
				
				var oTabGui = this.getOtherGuiObject(this.sDesignFilterRowHash);			
				oTabGui.request('&task=openDialog&action=edit');
				
			}.bind(this));
		}.bind(this));
		
		// Filter Row Delete
		$$('.remove_filter_row').each(function(oIcon){
	
			Event.stopObserving(oIcon, 'click');
			Event.observe(oIcon, 'click', function()
			{
				var iElementId = oIcon.id.replace('remove_filter_row_', '');
				
				var sConfirmMessage = this.getTranslation('really')
				if(confirm(sConfirmMessage)){

					this.setActiveDesignerFilterRow(iElementId);

					var oFilterRowGui = this.getOtherGuiObject(this.sDesignFilterRowHash);			
					oFilterRowGui.request('&task=deleteRow');
					
				}	

			}.bind(this));
		}.bind(this));
		
		// Filter Row Element Edit
		$$('.filter_row_element_edit_img').each(function(oIcon){
			
			Event.stopObserving(oIcon, 'click');
			Event.observe(oIcon, 'click', function()
			{
				
				var iRowId = oIcon.up('.filter_row_content').id.replace('filter_row_', '');
				
				this.setActiveDesignerFilterRow(iRowId);
				
				var iElementId = oIcon.id.replace('edit_filter_row_element_', '');
				
				this.setActiveDesignerFilterRowElement(iElementId);
				
				var oFilterElementGui = this.getOtherGuiObject(this.sDesignFilterRowElementHash);			
				oFilterElementGui.request('&task=openDialog&action=edit');
				
			}.bind(this));
		}.bind(this));
		
		// Filter Row Element Delete
		$$('.filter_row_element_remove_img').each(function(oIcon){
	
			Event.stopObserving(oIcon, 'click');
			Event.observe(oIcon, 'click', function()
			{
				var iElementId = oIcon.id.replace('remove_filter_row_element_', '');
				
				var sConfirmMessage = this.getTranslation('really')
				if(confirm(sConfirmMessage)){

					var iRowId = oIcon.up('.filter_row_content').id.replace('filter_row_', '');
				
					this.setActiveDesignerFilterRow(iRowId);

					this.setActiveDesignerFilterRowElement(iElementId);

					var oFilterElementGui = this.getOtherGuiObject(this.sDesignFilterRowElementHash);			
					oFilterElementGui.request('&task=deleteRow');
					
				}	

			}.bind(this));
		}.bind(this));
		
		
	},
	
	setActiveDesignerFilterRow : function(mBlockId){
		if(mBlockId.match(/filter_/)){
			mBlockId = 0;
		}
		
		var oRowGui = this.getOtherGuiObject(this.sDesignFilterRowHash);
			oRowGui.unselectAllRows(false);
			
		var aTabID = new Array();
			aTabID[0] = mBlockId;
		
		oRowGui.setSelectedRows(aTabID);
	},
	
	setActiveDesignerFilterRowElement: function(mBlockId){
		if(mBlockId.match(/filter_/)){
			mBlockId = 0;
		}
		
		var oElementGui = this.getOtherGuiObject(this.sDesignFilterRowElementHash);
			oElementGui.unselectAllRows(false);
			
		var aTabID = new Array();
			aTabID[0] = mBlockId;
		
		oElementGui.setSelectedRows(aTabID);
	},

	setActiveDesignerTabElement :function(mBlockId){
		
		if(mBlockId.match(/element_/)){
			mBlockId = 0;
		}
		
		var oElementGui = this.getOtherGuiObject(this.sDesignTabElementHash);
			oElementGui.unselectAllRows(false);
			
		var aTabID = new Array();
			aTabID[0] = mBlockId;
		
		oElementGui.setSelectedRows(aTabID);
		
	},

	/**
	 * Highlight tabs, show or hide contents
	 * 
	 * @param int iPageID
	 */
	setActiveDesignerTab: function(oTab)
	{
		
		this.sActiveTab = oTab.id;

		var aPageTabs = $$('.form_pages_tab');

		aPageTabs.each(function(oCurrentTab)
		{

			if(oCurrentTab.id != oTab.id)
			{
				oCurrentTab.removeClassName('form_pages_tab_active');
				
				if($(oCurrentTab.id+'_content')){
					$(oCurrentTab.id+'_content').hide();
				}
				
			}
			
		}.bind(this));

		// Highlight the tab
		oTab.addClassName('form_pages_tab_active');

		// Show content
		if($(oTab.id+'_content')){
			$(oTab.id+'_content').show();
		}
		
		var iTabId = oTab.id.replace('pages_tab_', '');
		
		var oTabGui = this.getOtherGuiObject(this.sDesignTabHash);
		oTabGui.unselectAllRows(false);
		var aTabID = new Array();
		aTabID[0] = iTabId;
		oTabGui.setSelectedRows(aTabID);	
		
	},
	
	/**
	 * Markiert die ausgewählten Zeilen
	 * UND (SPEZIEL HIER) wenn es die TR nicht gibt ein "Fake" Tr erstellen damit die ID korrekt übermittelt wird
	 */
	setSelectedRows : function(aSelectedRows) {

		if(aSelectedRows){
			bLoadBars = false;

			aSelectedRows.each(function(iSelectedRow) {
				if(iSelectedRow != "0" && iSelectedRow != ""){
					oTr = $('row_'+this.hash+'_'+iSelectedRow);
					// Wenn es die Row nicht gibt.
					// baue eine Hidden ein damit aufjedenfall der eintrag für die gui "makiert" ist
					if(!oTr) {
						var oTBody = $('guiTableBody_'+this.hash).down('.guiTableTBody');
						if(oTBody){
							var oTr = oTBody.down('tr');
							var oTrClone;
							if(oTr){
								oTrClone = oTr.clone(true); 
							} else {
								oTrClone = new Element('tr');
							}
							oTrClone.id = 'row_'+this.hash+'_'+iSelectedRow;
							oTrClone.hide();
							oTBody.appendChild(oTrClone);
							this.selectRow(null, oTrClone, bLoadBars, true);
						}
					} else {
						this.selectRow(null, oTr, bLoadBars, true);
					}
				}
			}.bind(this));

		}

	},
	
	prepareDependencyVisibilityHook: function(oElement, aValues, sElement, iIsIdElement, bHideElement) {
		
		var oPlaceholderNotification = $('gui_designer_element_placeholder');
		
		if(!bHideElement) {
			oPlaceholderNotification.show();
		} else {
			oPlaceholderNotification.hide();
		}
		
	}
	
});
