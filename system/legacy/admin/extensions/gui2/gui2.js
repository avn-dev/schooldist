/**
 * Version 2.4
 */

var bolReadyState = 0;
var aPingStack = [];

var ATG2 = Class.create({

	initialize: function(sHash, iShowLeftFrame, iDebugMode, sInstanceHash, bFrontendView) {

		this.hash = sHash;
		this.instance_hash = sInstanceHash;

		if(bFrontendView) {
			this.frontend_view = bFrontendView;
		} else {
			this.frontend_view = 0;
		}

		if(iShowLeftFrame != undefined) {
			this.iShowLeftFrame = iShowLeftFrame;
		} else {
			this.iShowLeftFrame = 1;
		}

		if(iDebugMode == 1) {
			this.bDebugMode = true;
		} else {
			this.bDebugMode = false;
		}

		this.sLanguage = 'en';
		this.enableMinimizeDialog = false;
		this.bReadyInitialized = false;
		this.bOnlyDialogMode = false;
		this.aPageData = null;
		this.sParentGuiHash = '';
		this.bUseParentFilter = 0;

		this.bBoxModel = null;
		this.iScrollBarWidth = null;
		this.aTableCellOffset = [0, 0];
		this.aChildGuiHash = [];
		this.sCurrentActiveChildHash = '';
		this.bodyElements = null;
		this.selectedRowId = null;
		this.selectedRow = null;
		this.lastSelectedRow = null;
		this.loadTableListEventElement = null;
		this.loadTableListObserver = null;
		this.hideLoadingObserver = null;
		this.searchElements = null; // Alte Filter
		this.orderElementData = null;
		this.loadingIndicator = null;
		this.columnElements = [];
		this.aCalendarData = [];
		this.aTableHeadData = null;
		this.iToolBarHeight = 36;
		this.translations = {};
		this.options = {};
		this.multipleSelection = 0;
		this.multipleSelectionActive = 0;
		this.multipleSelectionData = null;
		this.customerInplaceEditorCount = 0;
		this.sRequestUrl = '/gui2/request';
		this.selectedRowClassName = 'guiSelectedRow sc_bg_light';
		this.aDialogs = [];
		this.aLastDialogTab = [];
		this.iPaginationOffset = 0;
		this.iPaginationEnd = 0;
		this.iPaginationTotal = 0;
		this.iPaginationShow = 0;
		this.sPaginationId = '';
		this.sCalendarFormat = "dd.mm.yyyy";
		this.oDblClickElement = null;
		this.iHtmlEditorCount = 0;
		this.aOldSelectedRowId = null;
		this.sCurrentDialogId = null;
		this.bPingInterval = false;
		this.iPingInterval = 0;
		this.oPing = null;
		this.sView = null;
		this.bEnablePing = 1;
		this.bSaveAsNewEntry = false;
		this.bAfterSaveOption = false;
		this.aTooltips = [];
		this.aInnerGuis = [];
		this.oTabAreas = {};
		this.aEvalJS = [];
		this.aRequestStack = []; // Array mit Tasks, bei denen die Requests NICHT Parallel ausgefürt werden dürfen
		this.aEventStack = [];
		this.aLoadingIndicatorIcons = [];
		this.bLockLoadingIndicatorIconRequest = false;
		this.useRowIdWithoutRows = false;
		this.iWaitForInputEventDelay = 800;
		this.iHideSuccessMessageTimeout = null;
		this.oDialogInfoIconValues = {};
		this.aDependencyVisibilityRequiredFields = [];
		this.oVueInstances = {}

		if (window.__FIDELO__) {
			// Alles als ref(), damit Vue Änderungen erkennt! Man muss hier immer mit .value arbeiten, solange Vue das nicht in data() etc. unwrappt
			this.filters = window.Vue.ref([]);
			this.filterQuery = window.Vue.ref({});
			this.filterQueries = window.Vue.ref([]);

			this.emitter = window.__FIDELO__.EMITTER;
			this.emitter.off(`notification:${this.hash}`);
			this.emitter.on(`notification:${this.hash}`, (e) => {
				window.toastr[e.type](e.message, e.title, {
					progressBar: true,
					closeButton: true,
					...e.options || {}
				});
			});
		}

		// Leisten
		this.aBarToggleStatus = [];
		this.iBarCount = 0;
		this.aBars = [];

		this.fDisplayErrorDuration = 0.5;

		this.iPageResizeHeight = 0;
		this.bPageTopGui = false;
		this.bResize = false;
		this.bBoxModelTested = false;

		this.oAutoRefresh = null;

		this.bSkipUpdateSelectOptions = false;

		/**
		 * Beim verändern des Browsers die Tabelle anpassen
		 */
		Event.observe(window, 'resize', function() {
			this.resize(true);
		}.bind(this));

		Event.observe(document, 'keydown', function(e) {
			return this.checkKeyCode(e);
		}.bind(this));

		// Entsperrt die Dialoge beim Verlassen der GUI
		Event.observe(window, 'unload', function() {
			this.executePing(null, true);
			this.executePingStack();
		}.bind(this));

		// Ping nur aufrufen, wenn GUI nicht im Framework
		if(top.location == self.location) {
			// Ping starten (muss Interval > 60 haben, damit Refresh noch klappt)
			this.oPing = new PeriodicalExecuter(this.executePing.bind(this), 60);
			this.oPing = new PeriodicalExecuter(this.executePingStack.bind(this), 65);
		}

		// Linkes Menu ausblenden
		if(this.iShowLeftFrame == 0) {
			this.hideLeftFrame();
		}

		// Hook
		this.initializeHook();

		// Request für das Aktualisieren von Lade-Icons
		setInterval(this.requestLoadingIndicatorIconStatus.bind(this), 5000);

		/*
		 * Ersteinmal kein Contectmenü Event.observe(window, 'mousedown',
		 * function(e) { this.contextmenu(e); return false; }.bind(this));
		 *
		 * Event.observe(window, 'contextmenu', function(e) { this.contextmenu(e);
		 * return false; }.bind(this));
		 */

		//Event.observe(window, 'click', function(e) {this.closecontextmenu(e);return false;}.bind(this));

	},

	setPageEvents: function() {

		$j('#Gui2ChildTableDraggable_' + this.hash).draggable({
			zindex: 2999,
			axis: 'y',
			stop: function( event, ui ) {
				this.bResize = true;
				return this.resizeGuiPage(event, ui.helper.get(0));
			}.bind(this),
			drag: function( event, ui ) {

				// y = ui.position.top;

				var iMaximalHeight = this.getDocumentHeight(true) - 80;
				var iHeaderHeight = $j('#divHeader_' + this.hash).height() + 50;
				var originalTop = ui.offset.top - ui.position.top;

				if(ui.offset.top < iHeaderHeight) {
					ui.position.top = iHeaderHeight - originalTop;
					ui.helper.css('background-color', 'red');
				} else if(ui.offset.top > iMaximalHeight) {
					ui.position.top = iMaximalHeight - originalTop;
					ui.helper.css('background-color', 'red');
				} else {
					ui.helper.css('background-color', '');
				}

			}.bind(this)
		});

	},

	toggleReadonly: function(oElement, bReadonly) {

		if(!oElement) {
			return;
		}

		var sTag = oElement.tagName.toUpperCase();
		var bIsFormElement = false;

		if(
			sTag == 'INPUT' ||
				sTag == 'SELECT' ||
				sTag == 'TEXTAREA'
			) {
			bIsFormElement = true;
		}

		if(bReadonly) {

			if(bIsFormElement) {

				var oHidden = document.createElement('input');
				oHidden.type = 'hidden';
				oHidden.id = 'hidden_' + oElement.id;
				oHidden.name = oElement.name;

				var bSet = false;

				if(
					oElement.type &&
						oElement.type.toLowerCase() == 'checkbox'
					) {
					if(oElement.checked) {
						bSet = true;
					}
				} else {
					bSet = true;
				}

				if(bSet) {
					oHidden.value = oElement.value;
					oElement.insert({before: oHidden});
				}

				//oElement.disable();
				oElement.removeAttribute("disabled");
				oElement.setAttribute("disabled", "disabled");

			}

			if(!oElement.hasClassName('readonly')) {
				oElement.addClassName('readonly');
			}

		} else {

			if(bIsFormElement) {

				if($('hidden_' + oElement.id)) {
					$('hidden_' + oElement.id).remove();
				}

				//oElement.enable();
				oElement.removeAttribute("disabled");

			}

			if(oElement.hasClassName('readonly')) {
				oElement.removeClassName('readonly');
			}

		}

	},

	waitForInputEvent: function() {

		var sFunction = arguments[0];
		var oEvent = arguments[1];
		var oElement = oEvent.element();

		var bDirect = false;
		// Checkboxen und Selects direkt ausführen, alles andere mit timeout
		if(
			(
				oElement.tagName == 'INPUT' &&
				oElement.type == 'checkbox'
			) ||
			oElement.tagName == 'SELECT' ||
			oElement.tagName == 'BUTTON' ||
			oElement.tagName == 'IMG' ||
			oElement.tagName == 'TD' ||
			oElement.hasClassName('calendar_input')
		) {

			bDirect = true;

		} else {

			if(!this.aWaitForInputEventObserver) {
				this.aWaitForInputEventObserver = [];
			}

			if(this.aWaitForInputEventObserver[oElement.id]) {
				clearTimeout(this.aWaitForInputEventObserver[oElement.id]);
			}

		}

		// Funktion muss per eval aufgerufen werden
		var sFunctionCall;
		if(bDirect) {
			sFunctionCall = "this." + sFunction + "(";
		} else {
			sFunctionCall = "this.aWaitForInputEventObserver[oElement.id] = setTimeout(this." + sFunction + ".bind(this";
		}

		if(arguments.length > 2) {
			if(!bDirect) {
				sFunctionCall += ', ';
			}

			var bFirst = true;
			for(var iArgument = 2; iArgument < arguments.length; iArgument++) {
				if(!bFirst) {
					sFunctionCall += ', ';
				}
				sFunctionCall += 'arguments[' + iArgument + ']';
				bFirst = false;
			}
		}

		if(bDirect) {
			sFunctionCall += ");";
		} else {
			sFunctionCall += "), " + this.iWaitForInputEventDelay + ");";
		}

		eval(sFunctionCall);

	},

	resizeGuiPage: function(event, oElement) {

		if(this.bPageTopGui) {

			var oOffset = $j(oElement).position();

			var iTopPosition = oOffset.top;

			if(
				$j('#guiTableHead' + '_' + this.hash).length === 1 &&
				$j('#guiTableHead' + '_' + this.hash).closest('.dialog-wrapper').length === 0
			) {
				// Wenn nicht im Dialog, dann Padding der Seite abziehen
				iTopPosition += 33;
			}

			oElement.style.top = '0px';
			oElement.style.backgroundColor = '';

			this.iPageResizeHeight = iTopPosition;
			this.resizeTableBody();

			this.aChildGuiHash.each(function(sChildGuiHash) {

				oGui = aGUI[sChildGuiHash];
				oGui.iPageResizeHeight = iTopPosition;
				oGui.resizeTableBody();

			});

		}

	},

	executePingStack: function(){

		if(aPingStack.length > 0){

			var sParam = '&task=pingStack';

			aPingStack.each(function(oParam){
				sParam += '&stack['+oParam.hash+'][hash]='+ oParam.hash;
				sParam += '&stack['+oParam.hash+'][instance_hash]='+ oParam.instance_hash;
				sParam += '&stack['+oParam.hash+'][unload]='+ oParam.unload;
				oParam.dialog.each(function(sDialogID){
					sParam += '&stack['+oParam.hash+'][dialog][]='+sDialogID;
				});
			});

			aPingStack = [];

			this.requestBackground(sParam);
		}

	},

	executePing: function(oInterval, bUnload) {

		var bCheck = true;

		aPingStack.each(function(oParam){
			if(oParam.hash == this.hash){
				bCheck = false;
			}
		}.bind(this));

		if(bCheck){

			var oParam = {
				hash: this.hash,
				instance_hash: this.instance_hash,
				unload: 0,
				dialog: []
			};

			if(bUnload) {
				oParam.unload = 1;
			}

			if(
				!bUnload &&
				this.aDialogs
			) {
				this.aDialogs.each(function(oDialog) {
					if(!oDialog.read_only) {
						oParam.dialog[oParam.dialog.length] = oDialog.options.gui_dialog_id;
					}
				}.bind(this));
			}

			aPingStack[aPingStack.length] = oParam;

		}

	},

	// HOOK LISTE

	// Hook der gleich nach der initialisierung der Tabelle ausgeführt wird
	initializeHook: function() {

	},

	// Hook für alle Callbacks der GUI
	requestCallbackHook: function(aData) {

	},

	// Hook der nach dem Wechseln eines Tabs ausgeführt wird
	toggleDialogTabHook: function(iTab, iDialogId) {

	},

	// Hook für CloseHandlers des Kalenders
	// TODO: Entfernen (wird noch in TA verwendet)
	calendarCloseHandlerHook: function(sIdInput) {

	},

	// Hook um bei openDialog zusätzliche Infos mit zu schicken
	openDialogHook: function(aElement, aData, sParam) {

		return sParam;
	},

	// Hook, der vor dem öffnen der Tooltips der BarIcons ausgeführt wird
	// der aufklappbare Dialog
	prepareIconInfoTextHook: function(e, aIcon) {

	},

	// Hook der NACH dem öffnen eines Dialogs ausgeführt wird
	openEndDialogHook: function(oData) {

		$$('.dialog_wdsearch').each(function(oElement) {

			var oDiv = oElement.up('div');
			oElement.removeClassName('dialog_wdsearch');

			var oWDSearchDiv = document.createElement('div');
			oWDSearchDiv.className = 'dialog_wdsearch';
			oWDSearchDiv.appendChild(oElement);
			oDiv.appendChild(oWDSearchDiv);

			this.prepareWDSearch(oElement);

		}.bind(this));

		var oDialogWrapper = $j('#dialog_wrapper_' + oData.id + '_' + this.hash);
		this.registerLoadingIndicatorIcons(oDialogWrapper);

		this.executeOpenDialogEvents();

	},

	executeOpenDialogEvents: function() {
		if(this.aEventsOnOpenDialog) {

			try {
				this.aEventsOnOpenDialog.each(function(oData) {
					var sId = oData.element;
					var aEvent = oData.event;
					var e = {element: function() {
						return $(sId)
					}};
					var sFunctionCall = this.createEventFunctionCall(aEvent);
					eval(sFunctionCall);
				}.bind(this));
			} catch(err) {
				console.debug(err);
			}

		}
	},

	// Hook der nach dem Klonen der Joined Object Container
	addJoinedObjectContainerHook: function(oRepeat, sBlockId) {

	},

	// Hook um feste Filtereigenschaften zu ergänzen
	additionalFilterHook: function(sParam) {

		return sParam;
	},

	// Hook der im Icon Callback ausgeführt wird
	updateIconCallbackHook: function(aData) {

		return aData;
	},

	// Hock beim resizen der GUI
	resizeHook: function() {

	},

	/**
	 * Fängt Tastendrücke ab
	 */
	checkKeyCode: function(e) {
		if(e.keyCode == 116 && this.bReadyInitialized) {
			this.loadTable(false);
			e.stop();
			return false;
		}
	},

	/**
	 * Markiert die ausgewählten Zeilen
	 */
	setSelectedRows: function(aSelectedRows) {

		if(aSelectedRows) {
			var bLoadBars = false;
			var i = 1;
			var iCount = aSelectedRows.length;
			aSelectedRows.each(function(iSelectedRow) {
				if(iCount == i) {
					// Rausgenommen da bei Gui listen innerhalb von Dialogen
					// Die Icons nicht korekt geladen wurden da beim Update Icon die Parent Liste am Laden ist
					// und dadurch keine Parent id verfügbar war
					// auserdem passiert das ja beim loadTable eh auto.
					bLoadBars = true;
				}
				var oTr = $('row_' + this.hash + '_' + iSelectedRow);
				// wenn es das TR nicht mehr gibt, wähle leere die selectierungs daten!
				// ansonsten selectiere es wieder
				if(oTr) {
					this.selectRow(null, oTr, bLoadBars, true);
				} else {
					this.unselectAllRows(bLoadBars);
				}

				i++;
			}.bind(this));

		}

	},

	/**
	 * Tabelle aus einen Daten Array erzeugen
	 */
	createTable: function(aTableData) {

		this.options = aTableData.options;

		if(aTableData.loadBars == 1) {
			if (/*!this.filters.length &&*/ aTableData.filters && aTableData.filters.length) {
				this.filterQuery.value = aTableData.filters_query;
				this.filterQueries.value = aTableData.filters_queries;
				this.filters.value = window.Vue.reactive(aTableData.filters.map(f => new __FIDELO__.Gui2.FilterModel(f)));
			}
			this.createBars(aTableData, true);
		}

		// Wenn die Liste Mehrfachauswahl erlaubt dann aktiviere es
		// standartmässig
		if(this.options.multiple_selection == 1) {
			this.multipleSelectionActive = 1;
			this.multipleSelection = 1;
		}

		this.calculateColumnWidths(aTableData.head);

		this.createTableHead(aTableData.head, aTableData.has_column_group, aTableData.column_flexibility);
		this.createTableBody(aTableData);
		this.createTableSum(aTableData.sum);

		if(aTableData.selectedRows) {
			//var aCurrentSelection = Array.isArray(this.selectedRowId) ? this.selectedRowId : [];
			// Änderungen zur bisherigen Auswahl ermitteln
			//var aDifference = [
			//	...aTableData.selectedRows.filter((row) => !aCurrentSelection.includes(row)),
			//	...aCurrentSelection.filter((row) => !aTableData.selectedRows.includes(row))
			//]
			//
			//if (aDifference.length > 0) {
			//	// Nur ausführen wenn sich auch wirklich was geändert hat
				this.unselectAllRows(false);
				this.setSelectedRows(aTableData.selectedRows);
			//} else {
			//	this.updateIcons();
			//}
		} else {
			this.unselectAllRows(true);
		}

		this.updatePagination(aTableData.pagination);

		this.resize();

		var oPaginationSelect = $('pagination_select_' + this.hash);
		if(oPaginationSelect != null) {

			var aPaginationSelectionChildren = oPaginationSelect.children;
			$A(aPaginationSelectionChildren).each(function(opt) {
				if(aTableData.limit == opt.value) {
					opt.selected = true;
				}
			});

		}
	},

	/**
	 * Leisten Erzeugen
	 */
	createBars: function(aData, bResize) {

		if(
			aData.bar_top.length > 0 ||
				aData.bar_bottom.length > 0
			) {
			delete this.searchElements;
		}

		this.sCalendarFormat = aData.sCalendarFormat;

		this.aBars = [];

		this.createTableBars(aData, 'top');
		this.createTableBars(aData, 'bottom');

		this.resizeBars();

	},

	resizeBars: function() {

		// Toggle Icon
		this.aBars.each(function(oBar) {

			// Höhe merken
			oBar.style.height = 'auto';

			var iFullheight = oBar.getHeight();

			var oToggleImg = oBar.down('.divToolbarToggleIcon');
			oToggleImg?.show();

			var iFullheightWithToggle = oBar.getHeight();

			oToggleImg?.hide();

			var sHeight = oBar.getAttribute('data-height', oBar.height);

			if(
				!this.aBarToggleStatus[oBar.id] &&
				(
					!sHeight ||
					sHeight == ''
				)
			) {
				// Leiste verkleinern
				oBar.style.height = this.iToolBarHeight + 'px';
			}

			// Wenn die gesammthöhe aussagt das es nur eine zeile ist..
			if(
				sHeight ||
				iFullheight <= this.iToolBarHeight
			) {
				if(oToggleImg) {
					oToggleImg.hide();
				}
			} else {

				if(oToggleImg) {
					oToggleImg.show();
					// Mehrfache click events verhindern, damit resizeBars auch nach Initialisierung
					// aufgerufen werden kann (resize, updateIconsCallback).
					Event.stopObserving(oToggleImg, 'click');
					Event.observe(oToggleImg, 'click', function(e) {
						this.toggleFullBar(oBar, iFullheightWithToggle);
					}.bind(this, iFullheightWithToggle));
				}

			}

		}.bind(this));

	},

	/**
	 * zeigt den Ladebalken
	 */
	prepareFilterSearch: function(objEvent, bLoadBars) {

		if(objEvent) {
			this.loadTableListEventElement = objEvent;
		}

		if(this.loadTableListObserver) {
			clearTimeout(this.loadTableListObserver);
		}

		this.loadTableListObserver = setTimeout(this.executeFilterSearch.bind(this, bLoadBars, this.hash), 900);

	},

	executeFilterSearch: function(bLoadBars, sHash, sAdditionalParam) {

		this.iPaginationOffset = 0;
		this.loadTable(bLoadBars, sHash, null, sAdditionalParam);
		this.loadChildGuiLists();

	},

	prepareColumnSort: function(objEvent, oTh) {

		if(this.columnElements[oTh.id]) {
			if(this.orderElementData == null) {
				this.orderElementData = [];
			}
			this.orderElementData = this.columnElements[oTh.id];
			this.loadTable(false);
		}
	},

	/*
	 * Methode für das Blättern
	 */
	setPagination: function(sType) {

		// Pagination werte setzten
		this.iPaginationOffset = parseInt(this.iPaginationOffset);
		this.iPaginationEnd = parseInt(this.iPaginationEnd);
		this.iPaginationTotal = parseInt(this.iPaginationTotal);
		this.iPaginationShow = parseInt(this.iPaginationShow);

		// Wenn keine Einträge vorhanden sind, kann auch nicht geblättert werden
		if(this.iPaginationTotal == 0) {
			return;
		}

		// aktueller offset merken
		var iLastOffset = this.iPaginationOffset;

		// zum start springen
		if(sType == 'start') {
			this.iPaginationOffset = 0;
			// zurück
		} else if(sType == 'back') {
			this.iPaginationOffset = this.iPaginationOffset - this.iPaginationShow;
			// Weiter
		} else if(sType == 'next') {
			this.iPaginationOffset = this.iPaginationEnd;
		}

		// Wenn der Offset gleichgroß ist wie die gesammt anzahl sind wir schon zu weit
		// also zurückrechnen
		// Oder ans Ende
		if(
			this.iPaginationOffset >= this.iPaginationTotal ||
				sType == 'end'
			) {
			var iPage = Math.floor(this.iPaginationTotal / this.iPaginationShow);
			// Wenn die letzte Seite keine Elemente hat
			if(iPage == (this.iPaginationTotal / this.iPaginationShow)) {
				iPage--;
			}
			this.iPaginationOffset = iPage * this.iPaginationShow;
		}

		// Wenn das offset negativ ist -> auf start setzten
		if(this.iPaginationOffset <= 0) {
			this.iPaginationOffset = 0;
		}

		// Nur Blättern wenn sich das Offset auch verändert hat!
		if(iLastOffset != this.iPaginationOffset) {
			this.loadTable(false);
		}

	},

	/**
	 * Tabellen daten laden
	 */
	loadTable: function(bLoadBars, sHash, iLimit, sAdditionalParam, bShowLoading) {

		// Gui ohne Tabelle
		if(!$('guiScrollBody_'+this.hash)) {
			return;
		}

		var sParam = '&task=loadTable';

		if(sAdditionalParam) {
			if(
				typeof sAdditionalParam == 'string' &&
					sAdditionalParam.substring(0, 1) == '&'
				) {
				sParam += sAdditionalParam;
			}
		}

		if(!bLoadBars) {
			sParam += '&loadBars=0';
		}

		// Filter/Order Param
		sParam += this.getFilterparam(sHash);

		// wenn kein hash angegeben, nutze den aktellen
		if(!sHash) {
			sHash = this.hash;
		}

		if(iLimit) {
			sParam += '&limit=' + iLimit;
		}

		sParam += '&offset=' + this.iPaginationOffset;

		if(this.bUseParentFilter == 1 && this.sParentGuiHash != "") {
			sParam += aGUI[this.sParentGuiHash].getFilterparam(this.sParentGuiHash);
			//sParam += '&offset='+aGUI[this.sParentGuiHash].iPaginationOffset;
		}

		this.request(sParam, '', sHash, false, null, bShowLoading);

	},

	/*
	 * Funktion liefert die Filter/Order Parameter einer Tabelle wird beim laden
	 * der Tabelle / CSV / EXEL exporten benötigt
	 */
	getFilterparam: function(sHash, bIncludeParentGui) {
		var sParam = '';

		// Filterparams der Parent-GUI einbinden, wenn gewünscht
		if(
			bIncludeParentGui &&
			this.sParentGuiHash != ''
		) {
			var oParentGui = this.getOtherGuiObject(this.sParentGuiHash);
			if(oParentGui) {
				sParam += oParentGui.getFilterparam();
			}
		}

		if(
			!sHash ||
			sHash == this.hash
		) {
			// Filter elemente
			if(this.searchElements != null) {

				this.searchElements.each(function(aData) {


					if(aData != null && aData['id'] != '') {
						var sDataId = aData['id'] + '_' + this.hash;
						if(aData['filter_type'] == 'checkbox') {
							if($(sDataId).checked) {
								sParam += '&filter[' + aData['id'] + ']=' + encodeURIComponent($F(sDataId));
							}

						} else {
							if(aData.multiple) {
								$F(sDataId).forEach(function(sValue) {
									sParam += '&filter[' + aData['id'] + '][]=' + encodeURIComponent(sValue);
								});
							} else {
								sParam += '&filter[' + aData['id'] + ']=' + encodeURIComponent($F(sDataId));
							}
						}
					}
				}.bind(this));
			}

			this.filters?.value?.forEach(element => {
				if (element.isMultiple()) {
					let value = element.value.map(v => `&filter[${element.key}][]=${v}`);
					if (!value.length) {
						// Der Leerwert muss immer mitgeschickt werden, denn sonst steht der alte Filterwert in der GUI-Session
						value = [`&filter[${element.key}][]=`];
					}
					sParam += value.join('');
				} else {
					const value = element.value === null ? '' : encodeURIComponent(element.value);
					sParam += `&filter[${element.key}]=${value}`;
				}
				if (element.negated) {
					sParam += `&filter[${element.key}_negate]=1`;
				}
			});

			if (this.filterQuery?.value?.id) {
				sParam += `&filter_query_id=${this.filterQuery.value.id}`;
			}

			// Spalten Sortierung
			if(this.orderElementData != null) {
				if(this.orderElementData.sortable_column !== null) {
					sParam += '&orderby[db_column]=' + this.orderElementData.sortable_column;
				} else if(this.orderElementData.db_column != '') {
					sParam += '&orderby[db_column]=' + this.orderElementData.db_column;
				}

				if(this.orderElementData.db_alias != '') {
					sParam += '&orderby[db_alias]=' + this.orderElementData.db_alias;
				}

				if(this.orderElementData.order != '') {
					sParam += '&orderby[order]=' + this.orderElementData.order;
				}
			}
		}

		sParam = this.additionalFilterHook(sParam);

		return sParam;
	},

	/**
	 * Pagination aktualisieren
	 */
	updatePagination: function(aPagination) {

		this.iPaginationOffset = parseInt(aPagination.offset);
		this.iPaginationEnd = parseInt(aPagination.end);
		this.iPaginationTotal = parseInt(aPagination.total.value ?? aPagination.total);
		this.iPaginationShow = parseInt(aPagination.show);

		if(this.sPaginationId) {
			if(this.iPaginationTotal > 0) {
				$(this.sPaginationId + '_pagination_offset').update((this.iPaginationOffset + 1));
			} else {
				$(this.sPaginationId + '_pagination_offset').update(0);
			}
			$(this.sPaginationId + '_pagination_end').update(this.iPaginationEnd);
			$(this.sPaginationId + '_pagination_total').update(this.iPaginationTotal);
		}

	},

	updateIcons: function() {

		if(!this.bReadyInitialized) {
			return;
		}

		var sParam = '&task=updateIcons';
		// Filter/Order Param
		sParam += this.getFilterparam(this.sHash);
		this.request(sParam);

	},

	updateIconsCallback: function(aConfigData) {

		aConfigData = this.updateIconCallbackHook(aConfigData);

		var aData = aConfigData['bar_top'];

		var oDiv = $('guiTableBars' + '_' + this.hash);

		if(aData.length <= 0 || !oDiv) {
			return false;
		}

		// Filter Daten löschen da sie unten neu geschrieben werden
		aData.each(function(aBar) {

			if(aBar.bar_elements) {

				aBar.bar_elements.each(function(aElement) {

					// Wenn es ein Icon ist
					if(aElement.element_type == 'icon') {

						var sIconId = this.getBarIconId(aElement);

						if($(sIconId)) {

							var oIconDiv = $(sIconId);
							var oIconImg = oIconDiv.down('.icon');
							var oIconLabel = oIconDiv.up().down('.divToolbarLabel');
							var oDiv = oIconDiv.up();
							var sInfoIconId = 'infotext_img_' + sIconId;
							var sInfoTextId = 'infotext_' + sIconId;
							var oInfoIconId = $(sInfoIconId);

							if(aElement.info_text != undefined) {
								Event.stopObserving(oIconImg);
								if(oInfoIconId) {
									Event.stopObserving(oInfoIconId);
								}
								if(oIconLabel) {
									Event.stopObserving(oIconLabel);
								}
							} else {
								Event.stopObserving(oDiv);
							}

							if(aElement.active == 1) {

								if(aElement.info_text != undefined) {
									Event.observe(oIconImg, 'click', function(e) {
										this.prepareAction(aElement);
									}.bind(this));
									// Wenn Label vorhanden, click auch auf label legen
									if(oIconLabel) {
										Event.observe(oIconLabel, 'click', function(e) {
											this.prepareAction(aElement);
										}.bind(this));
									}
								} else {
									Event.observe(oDiv, 'click', function(e) {
										this.prepareAction(aElement);
									}.bind(this));
								}

								oIconImg.removeClassName('guiIconInactive');
								oDiv.addClassName('guiBarLink');
								oDiv.removeClassName('guiBarInactive');
							} else {
								oIconImg.addClassName('guiIconInactive');
								oDiv.removeClassName('guiBarLink');
								oDiv.addClassName('guiBarInactive');
							}

							if(aElement.visible == 1){
								oDiv.show();
							} else{
								oDiv.hide();
							}

							if($(sInfoIconId)) {
								// info text container entfernen
								$(sInfoIconId).up().remove();
							}

							if(aElement.info_text != undefined) {
								// Pfeil neben Icon mit aufklabbaren infos
								var oIconDiv = new Element('div');
								oIconDiv.className = 'divToolbarIcon';

								var oArrowImg = new Element('i');
								oArrowImg.className = 'fas fa-angle-down';
								oArrowImg.id = sInfoIconId;
								if(aElement.info_text == undefined || aElement.info_text == '') {
									//oArrowImg.className = 'guiIconInactive';
								} else {
									Event.observe(oArrowImg, 'click', function(e) {
										this.prepareIconInfoText(e, aElement);
									}.bind(this));
								}

								var oDivHidden = new Element('div');
								oDivHidden.style.display = 'none';
								oDivHidden.className = 'divToolbarIconArrowBox';
								oDivHidden.id = sInfoTextId;

								oIconDiv.appendChild(oDivHidden);
								oIconDiv.appendChild(oArrowImg);
								oDiv.appendChild(oIconDiv);

							}
						}

					} else if(aElement.element_type == 'label_group') {
						// Wenn id existiert
						if ($(aElement.id)) {
							// Prüfe, ob das Element sichtbar sein soll und blende es ggf. aus.
                            var oLabelGroup = document.getElementById(aElement.id);
                            if (aElement.visible == 1) {
                                oLabelGroup.show();
							} else{
								// Blende das Label der Gruppe aus
								oLabelGroup.hide();
							}
							// Das Element vor dem Label der Gruppe
							var oPrevElement = oLabelGroup.previousSibling;
							/**
							 * Wenn das Label der Gruppe ausgeblendet werden soll, dann prüfe
							 * ob direkt vor dem Label ein Seperator existiert.
							 * Wenn ja, dann blende diesen auch aus.
							 * Ein Seperator ist ein div Element, trägt die Klasse
							 * "divToolbarSeparator" und hat den Inhalt " :: "
							 */
							if (
                                oPrevElement &&
								oPrevElement.nodeName === "DIV" &&
								oPrevElement.hasClassName('divToolbarSeparator')
							) {
								/**
								 * Da es sich um einen Seperator handelt der vor
								 * dem ausgeblendetem Label einer Gruppe ist,
								 * soll dieser zugehörige Seperator auch
								 * ausgeblendet werden
								 */
								if (aElement.visible == 1) {
									oPrevElement.show();
								} else {
									oPrevElement.hide();
								}
							}
						}
					}
				}.bind(this));
			}

		}.bind(this));
		this.resizeBars();
	},

	loadBars: function() {

		var sParam = '&task=loadBars';

		this.request(sParam);

	},

	selectAllRows: function(oCheckbox) {

		var sTableBodyId = 'guiTableBody_' + this.hash;

		// Wenn es über die Checkbox geht und diese bereits makiert ist,
		// entmakiere alle
		if(oCheckbox) {

			if(oCheckbox.checked == false) {
				return this.unselectAllRows();
			}
		}

		if(this.multipleSelection != 1) {
			this.changeMultipleSelection();
		}

		// zählen
		var iCountTotal = $$('#' + sTableBodyId + ' .guiBodyRow').length;

		var bRequest = false;
		var iCount = 1;
		$$('#' + sTableBodyId + ' .guiBodyRow').each(function(oTr) {
			// Beim letzten durchlauf soll der Request gestartet werden
			if(iCount == iCountTotal) {
				bRequest = true;
			}
			this.selectRow(false, oTr, bRequest, true);
			iCount++;
		}.bind(this));

		//this.closecontextmenu();

	},

	unselectAllRows: function(bUpdateIcons) {

		if(bUpdateIcons == undefined) {
			bUpdateIcons = true;
		}

		this.aOldSelectedRowId = null;

		this.selectRow(false, null, false, true);

		if(bUpdateIcons) {
			this.updateIcons();
		}
		//this.closecontextmenu();

	},

	/**
	 * Zeile Selektieren
	 */
	selectRow: function(e, oTr, bRequest, bCheckbox) {

		if(bRequest == undefined) {
			var bRequest = true;
		}

		if(e) {
			var oTarget = e.target;

			// Bei allen Inputs außer der Mehrfachcheckbox nix machen
			if(
				oTarget &&
				oTarget.tagName &&
				(
					oTarget.tagName == 'INPUT' ||
					oTarget.tagName == 'SELECT'
				) &&
				!oTarget.hasClassName('multiple_checkbox') &&
				!oTarget.hasClassName('multiple_handle')
			) {
				return false;
			}

		}

		var bTargetByCheckbox;
		if(bCheckbox == undefined) {
			if(
				oTarget &&
				oTarget.type &&
				(
					(
						oTarget.type == 'checkbox' &&
						oTarget.hasClassName('multiple_checkbox')
					) ||
					oTarget.hasClassName('multiple_handle')
				)
			) {
				bTargetByCheckbox = true;
			} else {
				bTargetByCheckbox = false;
			}
		} else {
			bTargetByCheckbox = bCheckbox;
		}

		// Wenn mehrfachauswahl und nicht checkbox angeklickt wurde darf nichts
		// passieren
		if(
			this.multipleSelection == 1 &&
			!bTargetByCheckbox
		) {
			this.unselectAllRows(false);
			this.selectRow(e, oTr, bRequest, true);
			// return false;
		} else {

			if(!this.multipleSelectionData) {
				this.multipleSelectionData = [];
			}

			// Wenn kein TR angegeben wurde resete alles!
			if(!oTr) {

				if(
					this.selectedRow ||
					this.selectedRowId
				) {

					// Alle Arrays löschen
					if(this.multipleSelectionData) {
						delete this.multipleSelectionData;
					}
					if(this.selectedRowId) {
						delete this.selectedRowId;
					}
					if(this.selectedRow) {
						// Css Klasse zurücksetzten
						this.selectedRow.each(function(oRow) {

							// IE Fix
							if(oRow != undefined) {
								oRow.className = 'guiBodyRow sc_linear_hover';
								// Checkbox entmakieren falls vorhanden
								if(oRow.down('.multiple_checkbox')) {
									oRow.down('.multiple_checkbox').checked = false;
								}
							}

						}.bind(this));
						delete this.selectedRow;
					}
				}

			} else {

				if(
					!this.aOldSelectedRowId &&
					this.selectedRowId
				) {
					this.aOldSelectedRowId = this.selectedRowId.clone();
				}

				var sTrId = oTr.id.replace('row_' + this.hash + '_', '');

				if(this.selectedRow == null) {
					this.selectedRow = [];
					this.selectedRowId = [];
				}

				// Wenn mehrfach auswahl an ist
				if(this.multipleSelection == 1) {

					if(
						this.multipleSelectionData[sTrId] &&
						this.multipleSelectionData[sTrId] != undefined // IE
					) {
						if(
							oTarget &&
							bTargetByCheckbox &&
							!oTarget.hasClassName('multiple_handle')
						) {

							// Checkbox entmakieren falls vorhanden
							if(oTr.down('.multiple_checkbox')) {
								oTr.down('.multiple_checkbox').checked = false;
							}

							oTr.removeClassName(this.selectedRowClassName);

							delete this.multipleSelectionData[sTrId];
							delete this.selectedRow[sTrId];
							this.selectedRowId = this.selectedRowId.without(sTrId);

						}
					} else {

						// Checkboxmakieren falls vorhanden
						if(oTr.down('.multiple_checkbox')) {
							oTr.down('.multiple_checkbox').checked = true;
						}

						oTr.addClassName(this.selectedRowClassName);

						this.multipleSelectionData[sTrId] = oTr;
						this.selectedRow[sTrId] = oTr;
						this.selectedRowId.push(sTrId);

					}

				} else {

					// Einzel auswahl
					if(this.selectedRow[0] != null) {
						this.selectedRow[0].removeClassName(this.selectedRowClassName);
					}

					// lösche die Arrays damit alle auswahlen zu 100% weg sind
					delete this.selectedRow;
					delete this.selectedRowId;

					// lege die arrays dann neu an
					this.selectedRow = [];
					this.selectedRowId = [];

					// Definiere die Zeile
					this.lastSelectedRow = this.selectedRow;
					this.selectedRow[0] = oTr;
					this.selectedRowId[0] = sTrId;
					// ändere die css klasse
					if(this.selectedRow[0] != null) {
						this.selectedRow[0].addClassName(this.selectedRowClassName);
					}

				}

			}

			// Verhindert einen Request falls die Auswahl sich nicht verändert hat
			if(
				bRequest &&
				this.aOldSelectedRowId
			) {
				if(this.aOldSelectedRowId) {
					// Beispiel für: »Ich habe keine Ahnung von JavaScript«
					// if(this.aOldSelectedRowId.compact().toJSON() == this.selectedRowId.compact().toJSON()) {
					if (this.aOldSelectedRowId.length === this.selectedRowId.length && this.aOldSelectedRowId.every((v, i) => v === this.selectedRowId[i])) {
						return true;
					}
				}
				this.aOldSelectedRowId = null;
			}

			if(
				bRequest
			) {
				// Icons aktualisieren
				this.updateIcons();
			}

		}

		return true;

	},

	dblclickWrapper: function() {
		this.prepareAction(this.oDblClickElement);
	},

	prepareOpenDialog: function(sAction, sAdditional, sAdditionalParam) {

		var sParam = '&task=openDialog&action=' + sAction;

		if(sAdditional) {
			sParam += '&additional=' + sAdditional;
		}

		if(sAdditionalParam) {
			sParam += sAdditionalParam;
		}

		this.request(sParam);

	},

	/**
	 * Aktion vorbereiten
	 */
	prepareAction: function(aElement, aData) {

		if(typeof oWdHooks != 'undefined') {
			aElement = oWdHooks.executeHook('gui2_prepare_action', aElement, this.hash);
		}

		var sTask = aElement.task;
		var sAction = aElement.action;
		var request_data = aElement.request_data;

		// Vorgeschaltete Abfrage
		if(
			sTask == 'confirm' ||
			aElement.confirm == true
		) {

			if(aElement.confirm_message == '') {
				var sConfirmMessage = this.getTranslation('really')
			} else {
				sConfirmMessage = aElement.confirm_message;
			}

			if(!confirm(sConfirmMessage)) {
				return;
			}

		}

		var sParam = request_data;
		if(sTask != "") {
			sParam += '&task=' + sTask;
		}
		if(sAction != "") {
			sParam += '&action=' + sAction;
		}
		if(aElement.additional && aElement.additional != "") {
			sParam += '&additional=' + aElement.additional;
			if(aData && !aData.additional) {
				aData.additional = aElement.additional;
			}
		}

		if(
			sTask == 'deleteRow' &&
			this.selectedRowId != null &&
			this.selectedRowId.length > 0
		) {
			this.executeDelete();
		} else if(sTask == 'loadOtherTable') {
			this.prepareLoadOtherTable(sParam)
		} else if(
			sTask == 'openDialog' ||
			sTask == 'request'
		) {
			// Wenn neu MUSS alles deselectiert werden!
			if(sAction == 'new') {
				this.unselectAllRows();
			}
			sParam += this.getFilterparam(null, true);
			this.request(sParam);
		} else if(sTask == 'createCopy') {
			this.request(sParam + '&options_serialized=' + aElement.options_serialized);
		} else if(
			sTask == 'export_csv' ||
			sTask == 'export_excel'
		) {
			// Filter anhängen
			sParam += this.getFilterparam(this.hash);
			//this.request(sParam, '', '', true);
			this.request(sParam, '', '', false, null, false, true, true);
		} else if(sTask == 'toggleMenu') {
			this.request(sParam);
		} else if(
			sTask == 'requestAsUrl' ||
			sTask == 'openMultiplePdf'
		) {
			this.request(sParam, '', '', true);
		} else if(sTask == 'saveDialog') {
			this.prepareSaveDialog(aData, null, aElement);
		} else if(sTask == 'saveDialogAsUrl') {
			this.prepareSaveDialog(aData, null, aElement, null, true);
		} else if(sTask == 'closeDialog') {
			this.closeDialog(aData.id);
		} else if(sTask == 'confirm') {
			sParam += '&status=1';
			sParam += this.getFilterparam();
			this.request(sParam);
		} else if(sTask == 'wdsearch_btn') {
			this.startWDSearch();
		}

	},

	prepareSwitchTable: function(sCurrentHash) {

		$$('.divGui2ChildContainer').each(function(oChildElement) {
			oChildElement.hide();
		});

		$$('.Gui2ChildTableButton').each(function(oChildElement) {
			oChildElement.removeClassName('Gui2ChildTableButtonActive');
		});

		$('divGui2ChildContainer_' + sCurrentHash).show();
		$('Gui2ChildTableButton_' + sCurrentHash).addClassName('Gui2ChildTableButtonActive');

		// Falls das nicht zutrifft ist es ein Page Tab OHNE eigene GUI
		// Daher darf dann nicht die liste neuladen
		if(this.hash == sCurrentHash) {

			this.loadTable(true);

			this.resize();

		}
	},

	/**
	 * Tabellen wechsel vorbereiten
	 */
	prepareLoadOtherTable: function(sOtherHash) {

		this.loadTable(true, sOtherHash);
		// Hash erst danach setzten damit bei LoadTable die Filter und ORder
		// Teile nicht mitgeschickt werden
		this.hash = sOtherHash;

	},

	countSelection: function() {

		var iCount = 0;

		if(this.selectedRowId) {
			this.selectedRowId.each(function(iId) {
				iCount++;
			}.bind(this));
		}

		return iCount;

	},

	/**
	 * Lösch befehl ausführen
	 */
	executeDelete: function() {

		var bSuccess = false;

		// Wenn mehrfachauswahl an ist
		if(
			this.multipleSelection == 1 &&
			this.countSelection() > 1
		) {
			bSuccess = confirm(this.getTranslation('delete_all_question'));
		} else {
			bSuccess = confirm(this.getTranslation('delete_question'));
		}

		if(bSuccess) {
			this.request('&task=deleteRow');
		}

	},

	/**
	 * Übersetzung holen
	 */
	getTranslation: function(sTrans) {
		if(this.translations) {
			if(this.translations[sTrans]) {
				return this.translations[sTrans];
			} else if(this.translations['translation_not_found']) {
				return this.translations['translation_not_found'] + ' [' + sTrans + ']';
			} else {
				return 'Translation not found! (' + sTrans + ')';
			}
		} else {
			return 'No Translation Data';
		}

	},

	/**
	 * Einen Dialog Request starten
	 */
	requestDialog: function(sParam, sDialogId) {

		var oDialog = this.getDialog(sDialogId);

		var iCurrentId = 0;

		if(
			oDialog &&
			oDialog.data &&
			oDialog.data.save_id
		) {
			iCurrentId = oDialog.data.save_id;
		}

		this.request(sParam, false, false, false, iCurrentId);

	},

	getRequestIdParameters: function(iCurrentId, bParentId) {

		var strParameters = '';

		// Ids anhängen
		if(
			this.selectedRowId &&
			(
				iCurrentId == undefined ||
				iCurrentId == 0
			)
		) {
			$H(this.selectedRowId).each(function (aId) {

				var iId = aId[1];

				if (
					typeof iId != 'function' &&
					isNaN(iId) ||
					parseInt(iId) > 0
				) {

					if (bParentId) {

						strParameters += '&parent_gui_id[]=' + iId;

					} else {

						if (this.bSaveAsNewEntry !== false) {
							strParameters += '&id[]=' + iId + '&clonedata=true&save_as_new_from[]=' + this.bSaveAsNewEntry;
						} else {
							strParameters += '&id[]=' + iId;
						}
					}

				}

			}.bind(this));

			if (this.useRowIdWithoutRows) {
				if (this.bSaveAsNewEntry !== false) {
					strParameters += '&id[]=' + this.selectedRowId + '&clonedata=true&save_as_new_from[]=' + this.bSaveAsNewEntry;
				} else {
					strParameters += '&id[]=' + this.selectedRowId;
				}
			}

		} else if(
			iCurrentId != undefined &&
			typeof iCurrentId == 'string'
		) {

			var aIds = iCurrentId.split('_');

			aIds.each(function(iId) {
				if(
					parseInt(iId) > 0 &&
						this.bSaveAsNewEntry === false &&
						!bParentId
					) {
					strParameters += '&id[]=' + iId;
				}
				else if(!bParentId) {
					strParameters += '&id[]=' + iId + '&clonedata=true&save_as_new_from[]=' + this.bSaveAsNewEntry;
				}
			}.bind(this));

		} else if(
			iCurrentId != undefined &&
				parseInt(iCurrentId) > 0 &&
				!bParentId
			) {
			if(this.bSaveAsNewEntry !== false) {
				strParameters += '&id[]=' + iId + '&clonedata=true&save_as_new_from[]=' + this.bSaveAsNewEntry;
			}
			else {
				strParameters += '&id[]=' + iCurrentId;
			}
		}

		if(
			this.bAfterSaveOption &&
				this.bAfterSaveOption == 'new' &&
				!bParentId
			) {
			strParameters += '&open_new_dialog=1';
		}

		return strParameters;

	},

	getOtherGuiObject: function(sOtherGuiHash) {
		return aGUI[sOtherGuiHash];
	},

	uniqueRequest: function(sParam, sUrl, sHash, bAsNewWindow, iCurrentId, bShowLoading, bCallback) {

		// Task ermitteln der nur einmalig verschickt werden darf
		var aMatch = sParam.match(/&task=([a-zA-Z]*)/);

		var bExecuteRequest = false;

		if(aMatch[1]) {

			// Prüfen ob Task bereits ausgeführt wird
			if(!this.aRequestStack[aMatch[1]]) {
				// Task merken
				this.aRequestStack[aMatch[1]] = aMatch[1];
				//... Hier würde jetzt der Request normal abgeschickt werden....
				bExecuteRequest = true;
			} else {
				// Task läuft bereits nicht erneut ausführen
			}

		} else {
			// Kein Task gefunden -> Request normal ausführen
			bExecuteRequest = true;
		}

		if(bExecuteRequest) {
			this.request(sParam, sUrl, sHash, bAsNewWindow, iCurrentId, bShowLoading, bCallback);
		}

	},

	/**
	 * Einen Request starten
	 * Default:
	 * sParam = '';
	 * sUrl = ''; <= Standard URL
	 * bAsNewWindow = false <= neues Fenster
	 * iCurrentId = 0 <= IDs werden auto angehangen
	 * bShowLoading = true <= Ladebalken einblenden
	 */
	request: function(mParam, sUrl, sHash, bAsNewWindow, iCurrentId, bShowLoading, bCallback, bDownload) {

		// Auto-Refresh nur wenn sonst nix läuft
		if(this.oAutoRefresh) {
			this.oAutoRefresh.stop();
			this.oAutoRefresh = null;
		}

		if(bCallback == undefined) {
			bCallback = true;
		}

		// Ladebalken anzeigen
		if(
			bShowLoading != undefined &&
			bShowLoading == false
		) {
			// Do nothing
		} else {
			this.showLoading();
		}

		var bAsNewRequestWindow = false;
		if(bAsNewWindow && bAsNewWindow == true) {
			bAsNewRequestWindow = true;
		}

		var strRequestUrl = this.sRequestUrl;
		if(sUrl && sUrl != '') {
			strRequestUrl = sUrl;
		}

		var sRequestHash = this.hash;
		if(sHash && sHash != '') {
			sRequestHash = sHash;
		}

		var strParameters = '?hash=' + sRequestHash;
		strParameters += '&instance_hash=' + this.instance_hash;
		strParameters += '&frontend_view=' + this.frontend_view;

		if(this.sParentGuiHash != '') {
			var oParentGui = this.getOtherGuiObject(this.sParentGuiHash);
			strParameters += oParentGui.getRequestIdParameters(0, true);
		}

		strParameters += this.getRequestIdParameters(iCurrentId);

		if(typeof mParam != 'object') {
			strParameters += mParam;
			mParam = new FormData();
		} else if(bAsNewRequestWindow) {
			strParameters += '&'+new URLSearchParams(mParam).toString()
		}

		this.appendFormData(mParam, strParameters);

		if(bAsNewRequestWindow) {
			window.open(strRequestUrl + strParameters);
			this.hideLoading();
		} else {
			var oAjaxRequest = new XMLHttpRequest();
			
			if(bDownload === true) {
				oAjaxRequest.responseType = 'blob';
			}
			
			if(bCallback) {
				var oGui = this;
				oAjaxRequest.onerror = function() {
					oGui.requestError(this)
				};
				if(bDownload === true) {
				
					oAjaxRequest.onload = function() {
					
						var filename = 'file_name_not_available';
						var disposition = oAjaxRequest.getResponseHeader('content-disposition');
						
						var filenameRegex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
						var matches = filenameRegex.exec(disposition);
						if (matches != null && matches[1]) { 
							filename = matches[1].replace(/['"]/g, '');
						}
						
						var a = document.createElement('a');
						var url = window.URL.createObjectURL(this.response);
						a.href = url;
						a.download = filename;
						document.body.append(a);
						a.click();
						a.remove();
						window.URL.revokeObjectURL(url);
					
					};
					
				} else {

					oAjaxRequest.onload = function() {
						oGui.requestCallbackWithParse(this, strParameters);
					};
						
				}
			}
			oAjaxRequest.open('POST', strRequestUrl, true);
			oAjaxRequest.send(mParam);

			// Promise kann man hier eh vergessen, da sämtliche Ableitungen kein return machen
			return oAjaxRequest;
		}

		return false;
	},

	

	requestBackground: function(sParam) {
		this.request(sParam, '', null, false, null, false, true);
	},

	/**
	 * @param {URLSearchParams|Record<string, any>} body
	 * @returns {Promise<any>}
	 */
	request2(body) {
		if (body instanceof URLSearchParams) {
			body.append('hash', this.hash);
			body.append('instance_hash', this.instance_hash);
		} else {
			body.hash = this.hash;
			body.instance_hash = this.instance_hash;
			body = JSON.stringify(body);
		}

		return fetch(this.sRequestUrl, {
			method: 'POST',
			body: body,
			headers: {
				'Content-Type': body instanceof URLSearchParams ? 'application/x-www-form-urlencoded' : 'application/json'
			}
		}).then(response => {
			return response.json();
		});
	},

	loadChildGuiLists: function() {

		if(this.aChildGuiHash.length > 0) {
			this.aChildGuiHash.each(function(sChildGuiHash) {

				// Aktueller Tab der Child GUI suchen
				var oChildTab = $('Gui2ChildTableButton_' + sChildGuiHash);
				var oChildGui = this.getOtherGuiObject(sChildGuiHash);
				// Nur AKTIVER GUI TABS Laden!

				if(oChildGui) {
					if(
						!oChildTab ||
							oChildTab.hasClassName('Gui2ChildTableButtonActive') ||
							oChildGui.force_reload == 1
						) {

						//wenn die ChildGui durch den Parent geladen wird, offset leeren
						oChildGui.iPaginationOffset = 0;
						oChildGui.loadTable(true);
					}
				}

			}.bind(this));
		}
	},

	go: function(sUrl) {
		document.location.href = sUrl;
	},

	requestCallbackWithParse: function(objResponse, strParameters) {

		// Löschen von Requests aus dem Stack
		if(
			objResponse &&
			strParameters.indexOf('&task=') > 0
		) {
			var oTaskRegEx = new RegExp("[?&]task=([^&]*)?", 'i');
			var aTask = oTaskRegEx.exec(strParameters);
			if(
				aTask &&
				this.aRequestStack[aTask[1]]
			) {
				// Task ist erfolgreich gewesen -> kann gelöscht werden.
				delete this.aRequestStack[aTask[1]];
			}
		}

		var objData = this._evalJson(objResponse);

		this.requestCallback(objData);

	},

	/**
	 * Request Callback
	 */
	requestCallback: function(objData) {

		if(typeof oWdHooks != 'undefined') {
			objData = oWdHooks.executeHook('gui2_request_callback', objData, this.hash);
		}

		if(objData && objData.action) {

			/*
			 * Wenn der Request mit diesen Aktionen gerade ankommt und man in dem Moment
			 * bei einem Dialog auf Speichern klickt, hat man manchmal den unschönen Nebeneffekt,
			 * dass der Dialog direkt wieder entsperrt wird, bevor saveDialog den Session-Lock
			 * auslöst. Das soll durch die Abfrage hier verhindert werden…
			 */
			if(objData.action !== 'pingCallback') {
				this.hideLoading();
			}

			switch(objData.action) {
				case 'createTable':

					this.translations = objData.data.translations;

					if(
						objData.error &&
						objData.error.length > 0
					) {
						this.displayErrors(objData.error);
					}

					if(objData.data) {
						this.bReadyInitialized = true;
						this.createTable(objData.data);
					}

					if($('myMessage')) {
						$('myMessage').hide();
					}

					break;
				case 'showError':
					this.displayErrors(objData.error, objData.dialog_id);
					break;
				case 'showSuccess':
					this.displaySuccess(objData.dialog_id, objData.message, objData.success_title);
					break;
				case 'showSuccessAndReloadTable':
					this.displaySuccess(objData.dialog_id, objData.message, objData.success_title);
					this.loadTable(false);

					if (
						objData.parent_gui &&
						objData.parent_gui.size() > 0
					) {
						this.loadParentGuis(objData.parent_gui);
					}

					break;
				case 'loadTable':
					this.loadTable(false);
					break;
				case 'loadOtherTable':
					this.loadTable(true, objData.hash);
					break;
				case 'loadBars':
					this.createBars(objData.data, true);
					break;
				case 'updateIcons':
					this.updateIconsCallback(objData.data);
					this.loadChildGuiLists();
					break;
				case 'openUrl':
					if(objData.url) {
						this.go(objData.url);
						setTimeout(function() {
							// setTimeout benötigt für Chrome
							$j('.page-loader').hide(); // openUrl wird ständig für Dateien benutzt
						}, 10);
					} else if(objData.error) {
						this.displayErrors(objData.error);
					}
					break;
				case 'openUrlAndCloseDialog':
					if(objData.url) {
						this.go(objData.url);
						setTimeout(function() {
							// setTimeout benötigt für Chrome
							$j('.page-loader').hide(); // openUrl wird ständig für Dateien benutzt
						}, 10);
						var sTempHash = '';
						if(objData.data.hash) {
							sTempHash = objData.data.hash;
						}
						this.closeDialog(objData.data.id, sTempHash);
					} else if(objData.error) {
						this.displayErrors(objData.error);
					}
					break;
				case 'customInplaceEditorCallback':
					this.customerInplaceEditorCallback(objData.data);
					break;
				case 'openDialog':

					if (objData.data.translations) {
						this.translations = { ...this.translations, ...objData.data.translations }
					}

					this.openDialog(objData.data);

					if(
						objData.error &&
						objData.error.length > 0
					) {
						this.displayErrors(objData.error, objData.data.id);
					}

					break;
				case 'requestDialogInfoIconValuesCallback':
					this.oDialogInfoIconValues[objData.dialog_suffix] = objData.data.info_texts;
					this.setDialogInfoIconValues(objData.dialog_id, this.oDialogInfoIconValues[objData.dialog_suffix]);
					break;
				case 'saveDialogInfoIconCallback':

					if(
						objData.error &&
						objData.error.length > 0
					) {
						this.displayErrors(objData.error, objData.dialog_id);
					} else {
						// close dialog after save
						this.closeDialog(objData.dialog_id);
						// Reload info data for parent dialog
						delete(this.oDialogInfoIconValues[objData.parent_dialog_suffix]);
						this.loadDialogInfoIconValues(objData.parent_dialog_suffix, objData.parent_dialog_id);
					}

					break;
				// Tabelle neu laden, wenn kopierter Eintrag, der im Dialog öffnet
				case 'openDialogForCopy':
					this.selectedRowId = objData.data.selectedRows;
					this.loadTable(false);
					this.openDialog(objData.data);
					break;
				case 'openDialog':
				case 'reloadDialogTab':

					if(
						objData.error &&
						objData.error.length > 0
					) {
						this.displayErrors(objData.error, objData.data.id);
					} else if(this.sCurrentDialogId) {
						this.removeErrors(this.sCurrentDialogId);
					}

					if(
						objData &&
						objData.data &&
						objData.data.tabs
					) {
						this.reloadDialogTabCallback(objData);
					}

					break;
				case 'closeDialogAndReloadTable':
					if(
						objData.data &&
						objData.data.id &&
						(
							(
								objData.error &&
								objData.error.length <= 0
							) ||
							!objData.error
						)
					) {
						var sTempHash = '';
						if(objData.data.hash) {
							sTempHash = objData.data.hash;
						}

						this.closeDialog(objData.data.id, sTempHash);

						if(objData.success_message && objData.success_message != "") {
							this.displaySuccess(objData.data.id, objData.success_message, objData.success_title)
						}
					}

					// Ausgewählte IDs setzen, wichtig für neue Einträge, die vorher nicht in der Liste waren
					if(objData.data.selectedRows && objData.data.selectedRows.length > 0) {
						this.selectedRowId = objData.data.selectedRows;
					}

					if(objData.data && objData.data.hash) {
						var oObject = this.getOtherGuiObject(objData.data.hash);
						oObject.loadTable(false);
					} else {
						this.loadTable(false);

						if(objData.data && objData.data.load_additional_hash) {
							var oObject = this.getOtherGuiObject(objData.data.load_additional_hash);
							oObject.loadTable(false);
						}
					}

				case 'closeDialog':
					if(objData.error && objData.error.length <= 0) {
						this.closeDialog(objData.data.id);
						//this.loadTable(true);
					} else {
						this.displayErrors(objData.error, objData.data.id);
					}
					break;
				case 'closeAllDialog':
					if(this.aDialogs) {
						this.loadTable(true);
						this.aDialogs.each(function(oDialog) {
							var id = oDialog.id;
							if(oDialog.data && oDialog.data.id && !id){
								id = oDialog.data.id;
							}
							this.closeDialog(id);
						}.bind(this));

					}
					break;
				case 'openFlexDialog':

					this.closeDialog(objData.data.id);
					this.openDialog(objData.data);

					$j('.GUIDialogFlexRowList').sortable();

					// var iTemp = 1;
					// Position.includeScrollOffsets = true;
					// $$('.GUIDialogFlexRowList').each(function(oList) {
					// 	oList.id = 'flex_' + iTemp + '_' + this.hash;
					// 	var sContentId = 'tabs_content_' + objData.data.id + '_' + this.hash;
					// 	Sortable.create(
					// 		oList,
					// 		{
					// 			tag: 'div',
					// 			treeTag: 'div',
					// 			scroll: sContentId
					// 		}
					// 	);
					// 	iTemp++;
					// }.bind(this));

					$$('.group_checkbox').each(function(oGroupCheckbox) {
						Event.observe(oGroupCheckbox, 'click', function(e) {
							this.toggleFlexGroups(oGroupCheckbox);
						}.bind(this));
					}.bind(this));

					break;
				case 'saveDialogCallback':

					if(objData.error && objData.error.length <= 0) {

						// Cache last selected option
						objData.data.bAfterSaveOption = this.bAfterSaveOption;

						if(!objData.dialog_id_tag) {
							objData.dialog_id_tag = 'ID_';
						}

						if(this.bAfterSaveOption !== false) {
							switch(this.bAfterSaveOption) {
								case 'close':
								{
									if(this.bSaveAsNewEntry === false) {
										this.loadTable(false);

										// Close dialog with ID 0
										this.closeDialog(objData.dialog_id_tag + '0');

										// Close another dialog
										this.closeDialog(objData.data.id);

										return;
									}
								}
								case 'open':
								{
									// Do nothing
									break;
								}
								case 'new':
								{
									if(this.bSaveAsNewEntry === false) {
										this.loadTable(false);

										// Close dialog with ID 0
										this.closeDialog(objData.dialog_id_tag + '0');

										// Close another dialog
										this.closeDialog(objData.data.id);
									}
								}
							}
						}

						// Ausgewählte IDs setzen, wichtig für neue Einträge, die vorher nicht in der Liste waren
						if(objData.data.selectedRows && objData.data.selectedRows.length > 0) {
							this.selectedRowId = objData.data.selectedRows;
						}

						if(objData.tab) {
							this.aLastDialogTab[objData.data.id] = objData.tab;
						}

						// Dialog schliessen, falls mit ID 0 vorhanden
						this.closeDialog(objData.dialog_id_tag + '0');

						if(
							objData.data &&
							objData.data.options &&
							objData.data.options.close_after_save
						) {
							this.closeDialog(objData.data.id);
						} else if(
							objData.data &&
							(
								objData.data.tabs ||
								objData.data.html
							)
						) {
							this.openDialog(objData.data);
						}

						this.displaySuccess(objData.data.id, objData.success_message, objData.success_title);

						if(this.bReadyInitialized) {
							this.loadTable(false);
						}

						// Parent GUIs ebenfalls neu laden falls angegeben
						if(
							objData.parent_gui &&
							objData.parent_gui.size() > 0
						) {
							this.loadParentGuis(objData.parent_gui);
						}

					} else {
						this.displayErrors(objData.error, objData.data.id, false, objData.data.show_skip_errors_checkbox);
						//this.pepareHtmlEditors(objData.data.id);

						// Die Tabelle trotz fehler neu laden bei diesem Flag
						if(objData.data.reloadTableByError) {
							this.loadTable(false);
						}
					}

					break;
				case 'reloadDialogTab':
					break;
				case 'toggleMenu':
					// Linkes Menü ausblenden
					if(objData.data.type == 1) {
						this.hideLeftFrame();
					} else {
						this.showLeftFrame();
					}
					break;
				case 'saveFlexCallback':
					this.displaySuccess('FLEX');
					this.loadTable(true);
					break;
				case 'translations':
					if(objData.data) {
						this.translations = objData.data.translations;
						this.sCalendarFormat = objData.data.calendar_format;
					}
					break;
				case 'update_select_options':
					// Übergebene Werte in die Felder eintragen
					this.setDialogSaveFieldValues(objData.data);

					if(
						objData.error &&
						objData.error.length > 0
					) {
						this.displayErrors(objData.error, objData.data.id);
					}

					break;
				case 'pingCallback':
					// Ping ist zu ende
					this.bPingInterval = false;
					break;

				case 'createCopySuccess':
					// success_duplicated
					this.displaySuccess(objData.data.id, this.getTranslation('success_duplicated'), this.getTranslation('duplicate_item'));
					this.loadTable(false);
					break;
				case 'deleteCallback':
					//Liste neu laden wenn löschen erfolgreich
					if(objData.error.length <= 0) {
						this.displaySuccess(0, this.getTranslation('success_deleted'), this.getTranslation('delete_item'));

						this.loadTable(false);
						// Parent GUIs ebenfalls neu laden falls angegeben
						if(
							objData.parent_gui &&
								objData.parent_gui.size() > 0
							) {
							this.loadParentGuis(objData.parent_gui);
						}
					} else {
						this.displayErrors(objData.error);
					}
					break;
				case 'contextMenuCallback':
					this.loadTable(false);
					break;
				case 'similarityWDSearchCallback':
					this.executeSimilarityWDSearchCallback(objData);
					break;
				case 'displayWDSearchStartIndicate':

					this.openDialog(objData.data);

					/**
					 * @todo Sprachen gibt es nur bei den Angeboten, das muss irgendwie verallgemeinert werden!
					 */
					if(objData.data.languages) {
						this.aWDSearchStartIndicateLanguages = objData.data.languages;
						this.aWDSearchStartIndicateLanguageLabels = objData.data.language_labels;
					}

					if(
						this.aWDSearchStartIndicateLanguages &&
							this.aWDSearchStartIndicateLanguages.length > 0
						) {
						var sLanguage = this.aWDSearchStartIndicateLanguages.shift();
						this.loadTable(true, this.hash, 0, '&createNewWDSearchIndex=1&language=' + sLanguage + '&recreate=1');
					} else {
						this.loadTable(true, this.hash, 0, '&createNewWDSearchIndex=1');
					}

					break;
				case 'displayWDSearchStartIndicateCallback':

					var bFinished = this.setIndexStackLoadingBar(1);

					// Sprachen werden einzeln durchlaufen, bis keine mehr übrig sind
					if(
						!bFinished
						) {

						var sLanguage = this.aWDSearchStartIndicateLanguages.shift();
						this.loadTable(true, this.hash, 0, '&createNewWDSearchIndex=1&language=' + sLanguage);

					} else {

						var oIcon = $('wd_search_index_changes__' + this.hash);

						if(oIcon) {
							oIcon.hide();
						}

						window.setTimeout(
							function() {
								this.closeDialog(objData.data.id);
							}.bind(this),
							2000
						);

						this.loadTable(false, this.hash, 0, '&createNewWDSearchIndex=0');

					}

					break;
				case 'loadTooltipContent':
					if(objData.tooltip_id) {

						var oTooltip = $(objData.tooltip_id);

						if(
							oTooltip
						) {

							// Hier wird der nachgeladene Tooltipinhalt angezeigt
							this.aTooltips[oTooltip.id] = objData.tooltip;

							// Inhalt aktualisieren, falls noch eingeblendet
							var oTooltipDiv = $('div_' + oTooltip.id);

							// Falls Tooltip noch eingeblendet ist
							if(oTooltipDiv.visible()) {

								// Tooltip ausblenden
								this.hideTooltip(oTooltip.id);

								// Wenn ein Text vorhanden ist, dann einblenden
								if(
									objData.tooltip !== undefined &&
									objData.tooltip != ''
								) {
									this._fireEvent('mousemove', oTooltip);
								}
							}

						}
					}
					break;
				case 'executeRouterAction':

					if (window.__ADMIN__) {
                        objData.router_action.payload_additional = { gui2: this }
                        window.__ADMIN__.instance.action(objData.router_action, objData.local ?? false);
					} else {
						console.error('Missing __ADMIN__')
					}
				// case 'loadAccordionDataCallback':
				// 	this.loadAccordionDataCallback(objData.data);
				// 	break;
				default:
					break;
			}

			if(typeof oWdHooks != 'undefined') {
				objData = oWdHooks.executeHook('gui2_request_callback_hook', objData, this.hash);
			}

			if(
				objData &&
				objData.load_table
			) {
				this.loadTable(false);
				if(
					this.sParentGuiHash &&
					aGUI[this.sParentGuiHash]
				) {
					aGUI[this.sParentGuiHash].loadTable(false);
				}
			}

			try {
				this.requestCallbackHook(objData);
			} catch(mError) {

				if(console) {
					console.log('GUI2 reportError');
					console.log(mError);

					if(mError.stack !== null) {
						console.log(mError.stack);
					}
				}

				// Rausgenommen, da das nichts Sinnvolles bringt
				/*var sParam = '&task=reportError';
				var bExceptionRequest = true;

				sParam += '&error[error_type]=CALLBACK_HOOK_ERROR';

				if(typeof mError == 'string') {
					sParam += '&error[message]=' + encodeURIComponent(mError);
				} else if(typeof mError == 'object' && mError.message) {

					sParam += '&error[message]=' + encodeURIComponent(mError.message);

					if(mError.fileName) {
						sParam += '&error[filename]=' + encodeURIComponent(mError.fileName);
					} else {
						// Fehler ohne Dateiname bringen nichts
						bExceptionRequest = false;
					}

					if(mError.lineNumber) {
						sParam += '&error[linenumber]=' + encodeURIComponent(mError.lineNumber);
					}
				}

				if(bExceptionRequest) {
					this.requestBackground(sParam);
				}*/
			}

		} else {
			this.hideLoading();
		}

		if(objData && objData.dialog_id && objData.token) {
			var oTransactionHiddenField = $('token_' + objData.dialog_id + '_' + this.hash);
			if(oTransactionHiddenField) {
				oTransactionHiddenField.value = objData.token;
			}
			var oDialogIdHiddenField = $('dialog_id_' + objData.dialog_id + '_' + this.hash);
			if(oDialogIdHiddenField) {
				oDialogIdHiddenField.value = objData.dialog_id;
			}
		}

		if(
			objData &&
			(
				objData.alert_messages ||
				(
					objData.data &&
					objData.data.alert_messages
				)
			)
		) {
			if(objData.alert_messages) {
				var aAlertMessages = objData.alert_messages;
			} else {
				var aAlertMessages = objData.data.alert_messages;
			}

			var autoRemove = false;
			if(objData.alert_messages_autoremove) {
				autoRemove = objData.alert_messages_autoremove;
			}
			
			if(objData.data && objData.data.id) {
				this.displayErrors(aAlertMessages, objData.data.id, false, false, autoRemove);
			} else {
				this.displayErrors(aAlertMessages, null, false, false, autoRemove);
			}
		}

		if (
			objData &&
			objData.show_success
		) {
			let objectDataId = (objData.data.success_message_dialog_id) ? objData.data.success_message_dialog_id : objData.data.id;
			this.displaySuccess(objectDataId, objData.message, objData.success_title);
		}

		this.executeContainerUpdate(objData);

		if(this.oAutoRefresh) {
			this.oAutoRefresh.stop();
		}

		// TODO #12817: Funktioniert mit offenen Dialogen in Chrome nicht
		//this.oAutoRefresh = new PeriodicalExecuter(this.loadTable.bind(this, false, null, null, null, false), 60);

	},

	executeContainerUpdate: function(oData) {

		if(
			oData.data &&
			oData.data.container_update &&
			oData.data.container_update.length &&
			oData.data.container_update.length > 0
		) {

			oData.data.container_update.each(function(oContainerUpdate) {
				if($(oContainerUpdate.id)) {
					if(oContainerUpdate.content) {
						$(oContainerUpdate.id).update(oContainerUpdate.content);
						$(oContainerUpdate.id).show();
					} else {
						$(oContainerUpdate.id).hide();
					}
				}
			});

		}

	},

	loadParentGuis: function(oParentGuis) {
		oParentGuis.each(function(oParentGuiData) {
			if(
				oParentGuiData.class_js &&
				oParentGuiData.hash
			) {
				var oTestGui = this.getOtherGuiObject(oParentGuiData.hash);
				oTestGui.loadTable(false);
			}
		}.bind(this));
	},

	toggleFlexGroups: function(oGroupCheckbox) {

		$$('.' + oGroupCheckbox.name).each(function(oCheckbox) {
			if(!oCheckbox.disabled) {
				oCheckbox.checked = oGroupCheckbox.checked;
			}
		});

	},

	displayWeekDay: function(oInputCalendar, iWeekDay, bFireEvent) {

		oInputCalendar = $j(oInputCalendar);
		var oWeekdayDiv = oInputCalendar.siblings('.GUIDialogRowWeekdayDiv');
		oWeekdayDiv.text('');

		if(
			!oWeekdayDiv.length ||
			iWeekDay === ''
		) {
			return;
		}

		// TODO Noch notwendig?
		if(oInputCalendar.hasClass('readonly')) {
			oWeekdayDiv.addClass('readonly');
		}

		// 7 kommt nicht vom Datepicker
		if(parseInt(iWeekDay) === 7) {
			console.error('displayWeekDay: iWeekDay == 7');
			console.trace();
			iWeekDay = 0;
		}

		var oDatepicker = $j(oInputCalendar).data('datepicker');
		var sLanguage = oDatepicker.o.language;

		if(
			!$j().bootstrapDatePicker.dates ||
			!$j().bootstrapDatePicker.dates[sLanguage] ||
			!$j().bootstrapDatePicker.dates[sLanguage].daysMin
		) {
			console.error('displayWeekDay: No locale data found');
			return;
		}

		var sDayOfWeekTranslation = $j().bootstrapDatePicker.dates[sLanguage].daysMin[iWeekDay];

		oWeekdayDiv.text(sDayOfWeekTranslation);

		// if(bFireEvent) {
		// 	oInputCalendar.change();
		// }

	},

	displayCalendarAge: function(oInputCalendar) {

		oInputCalendar = $j(oInputCalendar);
		var oAgeDiv = oInputCalendar.siblings('.GUIDialogRowAgeDiv');

		if(!oAgeDiv.length) {
			return;
		}

		var oDatepicker = oInputCalendar.data('datepicker');
		var iDiff = Date.now() - oDatepicker.getDate().getTime();
		var oAgeDate = new Date(iDiff);
		var iAge = Math.abs(oAgeDate.getUTCFullYear() - 1970);
		var sAge = iAge;

		if(
			iDiff <= 0 ||
			iAge === 0
		) {
			sAge = '<span style="color: red">' + (iAge !== 0 ? '-' : '') + iAge + '<span>';
		}

		oAgeDiv.html(this.getTranslation('age') + ': ' + sAge);

	},

	hideLeftFrame: function() {
		if(
			self.name == 'content' &&
			parent &&
			parent.scroller_1 &&
			parent.scroller_1.status == 0
		) {
			var oFrameset = parent.document.getElementById("innerframe");
			oFrameset.cols = "0,10,*,10,0";
			parent.scroller_1.status = 1;

			// Icon ändern
			if($('toggleMenu_' + this.hash)) {
				var oImage = $('toggleMenu_' + this.hash).down('img');
				oImage.src = '/admin/extensions/gui2/application_side_expand.png';
			}

		}
	},

	showLeftFrame: function() {
		if(
			self.name == 'content' &&
				parent &&
				parent.scroller_1 &&
				parent.scroller_1.status == 1
			) {
			var oFrameset = parent.document.getElementById("innerframe");
			oFrameset.cols = iLeftFrameWidth + ",10,*,10,0";
			parent.scroller_1.status = 0;

			// Icon ändern
			if($('toggleMenu_' + this.hash)) {
				var oImage = $('toggleMenu_' + this.hash).down('img');
				oImage.src = '/admin/extensions/gui2/application_side_contract.png';
			}
		}
	},

	getDialogSaveLabelText: function(oInput) {
		var sLabelHtml = '';

		if(oInput.up('.GUIDialogRowInputDiv')) {
			if(oInput.up('.GUIDialogRowInputDiv').previous('.GUIDialogRowLabelDiv')) {
				var oInputLabel = oInput.up('.GUIDialogRowInputDiv').previous('.GUIDialogRowLabelDiv');
				var oTest = oInputLabel.down('div');
				if(oTest) {
					oInputLabel = oTest;
				}
				sLabelHtml += oInputLabel.innerHTML;
				return sLabelHtml;
			}
		}

		if(oInput.hasAttribute('data-placeholder')) {
			sLabelHtml = oInput.getAttribute('data-placeholder');
		} else if(oInput.hasAttribute('placeholder')) {
			sLabelHtml = oInput.getAttribute('placeholder');
		}

		return sLabelHtml;
	},

	reloadDialogTab: function(sDialogId, iTabIndex) {

		if(
			typeof sDialogId === 'object' &&
			sDialogId !== null
		) {
			sDialogId = sDialogId.id;
		} else if(
			typeof sDialogId === 'undefined' ||
			sDialogId === null ||
			sDialogId === ''
		) {
			sDialogId = this.sCurrentDialogId;
		}

		var oDialog = this.getDialog(sDialogId);

		var iCurrentId = 0;

		if(oDialog.data.save_id) {
			iCurrentId = oDialog.data.save_id;
		}

		// HTML Editoren inhalt schreiben
		// tinyMCE.triggerSave();
		if(typeof(tinyMCE) != 'undefined') {
			tinyMCE.triggerSave(false, true);
		}

		// HTML Editoren entfernen
		this.closeAllEditors(sDialogId);

		var sParam = '&task=reloadDialogTab';//+oDialog.data.task;
		sParam += '&action=' + oDialog.data.action;
		if(oDialog.data.additional) {
			sParam += '&additional=' + oDialog.data.additional;
		}

		var aTabIndex = [];

		if(!this.is_array(iTabIndex)) {
			aTabIndex[0] = iTabIndex;
		} else {
			aTabIndex = iTabIndex;
		}

		aTabIndex.each(function(iCurrentTabIndex) {
			sParam += '&reload_tab[]=' + iCurrentTabIndex;
		});

		sParam += '&' + $('dialog_form_' + oDialog.data.id + '_' + this.hash).serialize();
		//this.request(sParam, '', '', false, iCurrentId);
		this.request(sParam);

	},

	reloadDialogTabCallback: function(oData) {

		var aData = oData.data;

		var aReloadTabs = [];

		if(
			oData.reload_tab &&
			!this.is_array(oData.reload_tab)
		) {
			aReloadTabs[0] = oData.reload_tab;
		} else if(
			oData.reload_tab
		) {
			aReloadTabs = oData.reload_tab;
		}

		var iTab = 0;

		if(
			aData.tabs &&
			aData.tabs.length > 0
		) {

			var aInnerGuis = [];

			aData.tabs.each(function(aTab) {

				aReloadTabs.each(function(iReloadTab) {

					if(
						iReloadTab == iTab
					) {
						var sId = 'tabBody_' + iTab + '_' + aData.id + '_' + this.hash;
						var oTab = $(sId);
						if(oTab) {
							oTab = oTab.down();
							if(oTab) {
								oTab.update(aTab.html);
							}
						}
						var sTabHeaderId = 'tabHeader_' + iTab + '_' + aData.id + '_' + this.hash;
						var oTabHeader = $(sTabHeaderId);
						if(oTabHeader) {
							if(
								aTab.options &&
								aTab.options.hidden
							) {
								oTabHeader.hide();
							} else {
								oTabHeader.show();
							}
						}

						if (aTab.gui2) {
							aTab.gui2.each(function(aGui2) {
								aInnerGuis[aInnerGuis.length] = aGui2;
							}.bind(this));
						}
					}

				}.bind(this));

				iTab++;

			}.bind(this));

			if (aInnerGuis.length > 0) {
				this.executeInnerGuis(aInnerGuis);
			}

		} else {
			var sId = 'content_' + aData.id + '_' + this.hash;
			var oContent = $(sId);
			oContent = oContent.down();
			if(oContent) {
				oContent.update(aData.html);
				
				if(aData.js) {
					eval(aData.js);
				}
				
			}
		}

		this.prepareDialogContent(aData);

		// Führt alle Events von dem Dialog aus
		this.executeOpenDialogEvents(aData);

	},

	prepareSaveDialog: function(aData, bAsNewEntry, aElement, sAdditionalRequestParams, bAsUrl) {

		var iCurrentId = 0;

		if(aData.save_id) {
			iCurrentId = aData.save_id;
		}

		if(bAsUrl) {
			bAsUrl = true;
		} else {
			bAsUrl = false;
		}

		if(bAsNewEntry) {
			this.bSaveAsNewEntry = iCurrentId;
		} else {
			this.bSaveAsNewEntry = false;
		}

		var bCheckSuccess = true;
		var aErrors = [];

		aErrors[0] = this.getTranslation('error_dialog_title');

		// Gespeicherte Dialoge durchlaufen um passender zur ID zu finden
		if(this.validate) {
			this.validate.each(function(oValidateDialog) {
				if(oValidateDialog.id == iCurrentId) {
					// Alle Regexfelder durchlaufen und prüfen
					oValidateDialog.check.each(function(oCheck) {
						var aValidateFields = document.getElementsByName(oCheck.name);
						// alle gefundenen Felder prüfen
						for(var i = 0; i < aValidateFields.length; i++) {
							oValidateField = aValidateFields[i];
							var sValue = oValidateField.value;
							// Nur prüfen wenn Value gesetzt ansonsten wird über "required" abgefangen
							if(sValue != '') {
								var regexp = new RegExp(oCheck.regex);
								var aResult = sValue.match(regexp);
								if(!aResult) {
									// Falsches Format Fehler werfen
									bCheckSuccess = false;
									var i = aErrors.length;
									aErrors[i] = [];
									aErrors[i]['message'] = this.getTranslation('field_format');
									aErrors[i]['input'] = [];
									aErrors[i]['input']['object'] = oValidateField;

								}
								// gibt immer nur ein Feld mit dem Namen
								return;
							}
						}
					}.bind(this));
				}
			}.bind(this));
		}

		// HTML Editoren inhalt schreiben
		// tinyMCE.triggerSave();
		if(typeof(tinyMCE) != 'undefined') {
			tinyMCE.triggerSave(false, true);
		}

		var aRequiredFieldErrors = this.validateRequiredElements($j('#dialog_wrapper_' + aData.id + '_' + this.hash));

		if (aRequiredFieldErrors.length > 0) {
			bCheckSuccess = false;
			aErrors = aErrors.concat(aRequiredFieldErrors);
		}

		this.closeAllEditors(aData.id);

		if(bCheckSuccess) {

			var oDialog = this.getDialog(aData.id);
			if (oDialog.vueApp) {
				oDialog.vueApp[1].submit(aData);
				return;
			}

			var sParam = '&task=' + aData.task;
			sParam += '&action=' + aData.action;
			if(aData.additional) {
				sParam += '&additional=' + aData.additional;
			}

			var aIgnoredErrorCodes = [];

			var oIgnoreErrorsCodes = $('ignore_errors_codes_' + aData.id + '_' + this.hash);
			if (oIgnoreErrorsCodes) {
				aIgnoredErrorCodes = aIgnoredErrorCodes.concat(oIgnoreErrorsCodes.value.split('{|}'));
			}

			var oIgnoreErrors = $('ignore_errors_' + aData.id + '_' + this.hash);
			if(oIgnoreErrors && oIgnoreErrors.checked) {
				sParam += '&ignore_errors=1';

				if (oIgnoreErrors.hasAttribute('data-error-codes')) {
					// Fehlercodes der aktuellen Meldung mitsenden
					aIgnoredErrorCodes = aIgnoredErrorCodes.concat(oIgnoreErrors.getAttribute('data-error-codes').split('{|}'));
				}
			} else if(oIgnoreErrors && !oIgnoreErrors.checked) {
				sParam += '&ignore_errors=0';
			}

			aIgnoredErrorCodes.each(function (sCode) {
				sParam += '&ignore_errors_codes[]=' + sCode;
			});

			if(sAdditionalRequestParams) {
				sParam += sAdditionalRequestParams;
			}
			// Bei saveDialog Daten aElement-requestData (prepare Hook) mitschicken
			if(aElement && aElement.request_data) {
				sParam += aElement.request_data;
			}
			sParam += this.getFilterparam();

			var oFormData = new FormData($('dialog_form_' + aData.id + '_' + this.hash));

			this.appendFormData(oFormData, sParam);

			var url = '';
			if(aElement && aElement.url) {
				url = aElement.url;
			}

			this.request(oFormData, url, '', bAsUrl, iCurrentId);

		} else {
			this.displayErrors(aErrors, aData.id);
		}

	},

	validateRequiredElements: function(oContainer) {

		var aRequiredFields = $j(oContainer).find('.required');

		var aErrors = [];
		aRequiredFields.each(function(iFieldIndex, oInput) {

			var isHidden = this.checkElementIsHidden(oInput);

			if(!isHidden) {

				if(
					oInput.nodeName == 'INPUT' &&
					oInput.value == '' ||
					(
						oInput.nodeName == 'SELECT' &&
						oInput.selectedIndex < 1 && (
							oInput.value === '0' ||
							oInput.value === ''
						)
					) ||
					oInput.nodeName == 'TEXTAREA' &&
					oInput.value == '' // .value da innerHTML den uhrsprünglichen wert ausliest
				) {

					var sErrorMessage;
					if(oInput.nodeName == 'SELECT') {
						if(oInput.hasClassName('jQm')) {
							sErrorMessage = this.getTranslation('multiselect_required');
						} else {
							sErrorMessage = this.getTranslation('select_required');
						}
					} else {
						sErrorMessage = this.getTranslation('field_required');
					}

					var i = aErrors.length;
					aErrors[i] = [];
					aErrors[i]['message'] = sErrorMessage;
					aErrors[i]['input'] = [];
					aErrors[i]['input']['object'] = oInput;

				}

			}

		}.bind(this));

		return aErrors;
	},

	checkElementIsHidden: function(oInput) {

		// Die JS-seitige Validierung von Multiselects klappt so nicht
		//return $j(oInput).is(':hidden');

		var oInputContainer;
		oInputContainer = oInput.up('.GUIDialogRow');
		if(!oInputContainer) {
			oInputContainer = oInput.up('.displayContainer');
		}


		var isHidden = false;

		if(oInputContainer) {
			if($j(oInputContainer).is(':hidden')) {
				isHidden = true;
			} else {
				// Prüfen, ob ausgeblendeter JoinedObject-Container
				var oJoinedContainer = oInputContainer.up('.GUIDialogJoinedObjectContainerRow');
				if (!oJoinedContainer) {
					oJoinedContainer = oInputContainer.up('.copyDesignDiv'); // TODO einheitliche Klasse einbauen (Dialog-Designer)
				}
				if(
					oJoinedContainer && 
					$j(oJoinedContainer).is(':hidden')
				) {
					isHidden = true;
				}
			}
		}

		return isHidden;
	},

	appendFormData: function(oFormData, sParam) {

		// Literal % bringt das hier zum Absturz, ansonsten weitere Lösungswege hier:
		// https://stackoverflow.com/a/5713807
		// sParam = decodeURI(sParam.replace('?', ''));
		//
		// var aParamList = sParam.split('&');
		// aParamList.each(function(aParams) {
		// 	var aParamKeyValue = aParams.split('=');
		// 	if(aParamKeyValue[0]) {
		// 		try {
		// 			if(aParamKeyValue[1]) {
		// 				oFormData.append(decodeURIComponent(aParamKeyValue[0]), decodeURIComponent(aParamKeyValue[1]));
		// 			} else {
		// 				oFormData.append(decodeURIComponent(aParamKeyValue[0]), '');
		// 			}
		// 		} catch(e) {
		// 		}
		// 	}
		// });

		var oSearchParams = new URLSearchParams(sParam);
		for(let aParam of oSearchParams) {
			oFormData.append(aParam[0], aParam[1]);
		}

	},

	disableDialogActionRow: function(sDialogId) {

		$$('#dialog_wrapper_' + sDialogId + '_' + this.hash + ' .dialog-actions').each(function(oDiv) {
			oDiv.hide();
		});

	},

	activateDialogActionRow: function(sDialogId) {

		$$('#dialog_wrapper_' + sDialogId + '_' + this.hash + ' .dialog-actions').each(function(oDiv) {
			oDiv.show();
		});

	},

	openDialog: function(aData) {

		this.aEvalJS = [];

		// Index von Tabs von Textareas zurücksetzen
		this.oTabAreas[aData.id] = {};

		var bAutoWidth = false;
		if(!aData.width) {
			aData.width = this.getDocumentWidth() - 40;
			bAutoWidth = true;
		}

		var bAutoHeight = false;
		if(!aData.height) {
			aData.height = this.getDocumentHeight(true) - 40;
			bAutoHeight = true;
		}

		// eindeutige ID !
		if(!aData.id) {
			var aErrorData = [];
			aErrorData[0] = 'Bitte definiere eine eindeutige ID für den Dialog!';
			this.displayErrors(aErrorData);
		} else {

			if(
				aData.force_new_dialog &&
				aData.force_new_dialog == 1 &&
				aData.old_id
			) {
				this.closeDialog(aData.old_id);
			}

			var sIconDataId = this.getIconDataId(aData);

			var sCloseName = this.getTranslation('close');
			var sMinimizeName = this.getTranslation('minimize');
			var sSaveName = this.getTranslation('save');
			var sSaveAsNewName = this.getTranslation('save_as_new');

			var oButtons = {};

			if(!aData.read_only) {

				if(aData.buttons) {
					aData.buttons.each(function(aButton) {
						oButtons[aButton['label']] = aButton;
						oButtons[aButton['label']]['function'] = function() {
							this.prepareAction(aButton, aData);
						}.bind(this);
					}.bind(this));
				}

				if(aData.bSaveAsNewButton == 1 && aData.action != 'new') {
					oButtons[sSaveAsNewName] = {default:true};
					oButtons[sSaveAsNewName].function = function() {
						this.prepareSaveDialog(aData, true);
					}.bind(this);
				}
				if(aData.bSaveButton == 1) {
					oButtons[sSaveName] = {};
					oButtons[sSaveName].function = function() {
						this.prepareSaveDialog(aData);
					}.bind(this);
				}
			}

			var sTitle = aData.title;
			var sTitleOriginal = aData.title;

			if(this.enableMinimizeDialog) {
				var sTitleId = 'title_' + aData.id + '_' + this.hash;
				sTitle = '<div id="' + sTitleId + '" class="GUIDialogTitle">' + aData.title + '</div>';
				sTitle += '<div class="GUIDialogTitleAction">';
				sTitle += '<img id="mini_' + aData.id + '_' + this.hash + '" src="/admin/media/application_put.png" alt="' + sMinimizeName + '" title="' + sMinimizeName + '">';
				sTitle += '<img id="close_' + aData.id + '_' + this.hash + '" src="/admin/extensions/gui2/cross.png" alt="' + sCloseName + '" title="' + sCloseName + '">';
				sTitle += '</div>';
				sTitle += '<div style="clear:both;"></div>';
			}

			var sDialogFormId = 'dialog_form_' + aData.id + '_' + this.hash;
			var sDialogId = 'dialog_' + aData.id + '_' + this.hash;
			var sDialogErrorId = 'dialog_error_' + aData.id + '_' + this.hash;
			var sDialogSuccessId = 'dialog_success_' + aData.id + '_' + this.hash;
			var sDialogRequiredId = 'dialog_required_' + aData.id + '_' + this.hash;
			var sSaveBarTitleId = 'dialog_save_bar_options_title_' + aData.id + '_' + this.hash;
			var sSaveBarOptionsId = 'dialog_save_bar_options_' + aData.id + '_' + this.hash;

			var sContent = '';
			sContent += '<form id="' + sDialogFormId + '" class="form-horizontal" onsubmit="return false;">';
			sContent += '<input type="hidden" id="token_' + aData.id + '_' + this.hash + '" name="token" value="" />';
			sContent += '<input type="hidden" id="dialog_id_' + aData.id + '_' + this.hash + '" name="dialog_id" value="" />';
			sContent += '<div id="' + sDialogId + '">';

			sContent += '</div>';
			sContent += '</form>';

			var oDialog = this.checkForMinimizedDialog(aData.id);

			if(!oDialog) {

				dialogOptions = {
					content: sContent,
					/*title : sTitle,*/
					openOnCreate: true,
					buttons: oButtons,
					destroyOnClose: true,
					escHandler: function() {
						this.closeDialog(aData.id);
					}.bind(this),
					gui_dialog_id: aData.id,
					gui_dialog_hash: this.hash,
					height: aData.height,
					width: aData.width,
					level: (this.getDialogLevel() + 1),
					bAutoWidth: bAutoWidth,
					bAutoHeight: bAutoHeight,
					sTitle: sTitle,
					sTitleOriginal: sTitleOriginal,
					sCloseName: sCloseName
				};

				if(aData.options) {
					if(aData.options.settings && aData.options.settings == true) {
						dialogOptions.settingsBtn = true;
					}
					if(aData.options.focus) {
						dialogOptions.focus = aData.options.focus;
					}
				}

				oDialog = this.aDialogs[this.aDialogs.length] = new Dialog(dialogOptions);

			} else {

				this.maximizeDialog(aData.id);

				// ID neu setzten!
				oDialog.gui_dialog_id = aData.id;

				oDialog.header.down('.GUIDialogTitle').update(aData.title);

				// Buttons
				if(!$j.isEmptyObject(oButtons)) {
					oDialog.replaceButtons(oButtons);
				}

				// HTML Editoren entfernen
				var aEditorFields = $$('#dialog_' + aData.id + '_' + this.hash + ' .GuiDialogHtmlEditor');

				aEditorFields.each(function(oEditor) {
					this.removeEditor(oEditor.id);
				}.bind(this));

			}

			if(aData.bAfterSaveOption && aData.bAfterSaveOption == 'close') {
				this.loadTable(false);

				this.closeDialog(aData.id);
			}

			if(aData.id.indexOf('ERROR_DIALOG') !== 0) {
				this.sCurrentDialogId = aData.id;
			}

			oDialog.data = aData;

			// Hier starten damit der dialog nicht "springt"
			this.resizeDialogSize(aData);

			var oDivAction = oDialog.actions;

			if($(sDialogErrorId)) {
				$(sDialogErrorId).remove();
			}
			if($(sDialogSuccessId)) {
				$(sDialogSuccessId).remove();
			}
			if($(sDialogRequiredId)) {
				$(sDialogRequiredId).remove();
			}
			if($(sSaveBarTitleId)) {
				$(sSaveBarTitleId).remove();
			}

			// Save bar options
			if(
				aData.bSaveBarOptions &&
				aData.bSaveBarOptions !== false &&
				aData.bSaveBarOptions.length > 0
			) {
				var sCode = '';

				if(aData.bAfterSaveOption) {
					aData.bSaveBarDefaultOption = aData.bAfterSaveOption;
				}

				sCode += '<span class="GUIDialogSaveBarOptionsTitle" id="' + sSaveBarTitleId + '">';
				sCode += this.getTranslation('save_bar_options_title');

				sCode += '<select class="form-control GUIDialogSaveBarOptions" id="' + sSaveBarOptionsId + '">';
				for(var i = 0; i < aData.bSaveBarOptions.length; i++) {
					var sValue = aData.bSaveBarOptions[i].key.replace(/save_bar_option_/, '');

					var sSelected = '';

					if(sValue == aData.bSaveBarDefaultOption) {
						sSelected = 'selected="selected"';

						if(this.bAfterSaveOption === false) {
							this.bAfterSaveOption = sValue;
						}
					}

					sCode += '<option value="' + sValue + '" ' + sSelected + '>' + this.getTranslation(aData.bSaveBarOptions[i].key) + '</option>'
				}
				sCode += '</select>';
				sCode += '</span>';

				oDivAction.insert({top: sCode});

				Event.stopObserving($(sSaveBarOptionsId), 'change');

				Event.observe($(sSaveBarOptionsId), 'change', function() {
					this.bAfterSaveOption = $(sSaveBarOptionsId).value;
				}.bind(this));
			}

			if(aData.bSaveButton == 1) {
				var oRequiredFields = document.createElement('div');
				oRequiredFields.className = 'GUIDialogRequiredText';
				oRequiredFields.id = sDialogRequiredId;
				oRequiredFields.innerHTML = this.getTranslation('required_fields');
				oDivAction.insertBefore(oRequiredFields, oDivAction.firstChild);
			} else {
				// Alles nach rechts ausrichten (erstes Element ist flex-grow)
				var oBlank = document.createElement('div');
				oDivAction.insertBefore(oBlank, oDivAction.firstChild);
			}

			var sMessagesId = 'dialog_messages_' + aData.id + '_' + this.hash;
			var oDivMessages = $(sMessagesId);
			if(!oDivMessages) {
				oDivMessages = document.createElement('div');
				oDivMessages.className = 'GUIDialogMessages';
				oDivMessages.id = 'dialog_messages_' + aData.id + '_' + this.hash;
			}
			oDivMessages.innerHTML = '';
			oDivMessages.style.display = 'none';

			oDivMessages.appendChild(this.getNotificationDiv(aData.id, 'error'));
			oDivMessages.appendChild(this.getNotificationDiv(aData.id, 'success'));
			oDivMessages.appendChild(this.getNotificationDiv(aData.id, 'hint'));
			oDivMessages.appendChild(this.getNotificationDiv(aData.id, 'info'));

			// DIV.dialog-actions
			var oDivActionMain = oDivAction.parentNode;
			oDivActionMain.insert({top: oDivMessages});

			if(aData.read_only) {
				var oDivButtons = oDivMessages.next('.buttons');
				if(oDivButtons) {
					oDivButtons.hide();
				}
			}

			// Dialog Inhalt erzeugen
			var oContent = this.createDialogContent(aData, oDialog);

			// Scrollposition merken
			var aTabs = $j('#tabs_content_' + aData.id + '_' + this.hash+' > div');
			var aTabScrollTop = [];
			if(aTabs.length > 0) {
				aTabs.each(function(i) {
					aTabScrollTop[i] = $j(this).scrollTop();
				});
			}

			// Dialog leere
			$(sDialogId).innerHTML = '';

			// Dialog füllen
			$(sDialogId).appendChild(oContent);
			// Wenn es Tabs gibt, prüfen ob die Breite passt
			var oTabsDiv = $('tabs_' + aData.id + '_' + this.hash);

			// TODO müsste doch eigentlich auch bei window:resize passieren
			if(oTabsDiv) {
				// Dadurch das die inaktiven Tabs mit "position: absolute" ausgeblendet werden, muss deren Breite gesetzt
				// werden damit das Resizing der Gui korrekt ist.
				$j('#tabs_content_' + aData.id + '_' + this.hash).find('.GUITab').width(oTabsDiv.getWidth());

				var oTabsList = $('ul_' + aData.id + '_' + this.hash);

				var iTabWidth = 0;
				$j(oTabsList).find('li').each(function() {
					iTabWidth += $j(this)[0].getBoundingClientRect().width + 4;
				});

				if(oTabsDiv.getWidth() < iTabWidth) {

					var oLeftScrollDiv = new Element('div');
					oLeftScrollDiv.id = 'tabs_scroll_left_' + aData.id + '_' + this.hash;
					oLeftScrollDiv.className = 'tabs_scroll_left';
					var oLeftScrollIcon = new Element('span');
					oLeftScrollIcon.className = 'fa fa-caret-left';

					var oRightScrollDiv = new Element('div');
					oRightScrollDiv.id = 'tabs_scroll_right_' + aData.id + '_' + this.hash;
					oRightScrollDiv.className = 'tabs_scroll_right';
					var oRightScrollIcon = new Element('span');
					oRightScrollIcon.className = 'fa fa-caret-right';

					oLeftScrollDiv.appendChild(oLeftScrollIcon);
					oRightScrollDiv.appendChild(oRightScrollIcon);

					oTabsDiv.appendChild(oLeftScrollDiv);
					oTabsDiv.appendChild(oRightScrollDiv);

					iTabWidth += $j(oLeftScrollDiv).outerWidth(true);
					iTabWidth += $j(oRightScrollDiv).outerWidth(true);

					//$j(oTabsList).width(iTabWidth);
					$j(oTabsList).addClass('tab-scroll');

					var iTabsScrollDiff = iTabWidth - oTabsDiv.getWidth();
					var iTabsScrollDuration = iTabsScrollDiff / 100 * 1000;

					$j(oLeftScrollDiv).hover(
						function() {
							$j(oTabsList).animate({ left: 0 }, iTabsScrollDuration);
						},
						function() {
							$j(oTabsList).stop();
						}
					);
					$j(oRightScrollDiv).hover(
						function() {
							$j(oTabsList).animate({ left: iTabsScrollDiff*-1 }, iTabsScrollDuration);
						},
						function() {
							$j(oTabsList).stop();
						}
					);

				} else {
					$j(oTabsDiv).removeClass('tab-scroll');
				}
			}

			if(this.aLastDialogTab && this.aLastDialogTab[aData.id]) {
				var iTempTab = this.aLastDialogTab[aData.id];
			} else {
				var iTempTab = 0;
			}

			this.aLastDialogTab[aData.id] = iTempTab;

			// Hook der beim Tabwechsel ausgeführt wird
			this.toggleDialogTabHook(iTempTab, aData.id);

			this.prepareDialogContent(aData);

			// Init highlightRows
			if (typeof processLoading === "function") {
				processLoading();
			}

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

			// Definiere minimieren methode
			if(this.enableMinimizeDialog) {
				Event.observe($('mini_' + aData.id + '_' + this.hash), 'click', function(e) {
					this.minimizeDialog(aData.id);
				}.bind(this));
			}

			// Definiere Schliesen methode
			Event.observe($('close_' + aData.id + '_' + this.hash), 'click', function(e) {
				this.closeDialog(aData.id);
			}.bind(this));

			$j('#settings_' + aData.id + '_' + this.hash).click(function(e) {

				// settings_ID_20348_b56eab683e450abb7100bfa45fc238fd
				parts = e.currentTarget.id.match(/^settings_([A-Z_]+)_([0-9]+)_(.+)$/);

				this.openDialogSettings(parts[1]);

			}.bind(this));

			// Hier nochmal starten damit der dialog die tabs anpasst
			this.resizeDialogSize(aData);

			if(oTabsDiv) {

				// Alte Scrollposition falls möglich setzen
				var aTabs = $j('#tabs_content_' + aData.id + '_' + this.hash+' > div');
				if(aTabs.length > 0) {
					aTabs.each(function(i) {
						if(aTabScrollTop[i]) {
							$j(this).scrollTop(aTabScrollTop[i]);
						}
					});
				}

			}

		}

		// START Validierung speichern

		if(aData.validate) {
			var bSkip = false;

			if(this.is_array(this.validate)) {
				this.validate.each(function(oValidateDialog) {
					// gespeicherte Dialoge durchlaufen und prüfen ob schon existiert
					if(oValidateDialog.id == aData.id.replace('ID_', '')) {
						bSkip = true;

					}
				});

			} else {
				// komplett neu anlegen
				this.validate = [];
			}

			if(!bSkip) {
				// Einfügen wenn nicht schon existiert
				var iArrayLangth = this.validate.length;

				var oValidateDialog = [];
				oValidateDialog['id'] = aData.id.replace('ID_', '');
				oValidateDialog['check'] = aData.validate;

				this.validate[iArrayLangth] = oValidateDialog;
			}

		}
		// ENDE

		// Hook der am ende von openDialog ausgeführt werden soll
		this.openEndDialogHook(aData);

		// Innere GUIs aufbauen
		if(
			this.aInnerGuis &&
			this.aInnerGuis.length > 0
		) {
			this.executeInnerGuis(this.aInnerGuis);
		}

		if(this.aEvalJS.length > 0) {
			this.aEvalJS.each(function(sJS) {
				eval(sJS);
			});
			this.aEvalJS.clear();
		}

		this.prepareTCUploaders();
		this.pepareHtmlEditors(aData.id);

		if(aData.info_icons) {
			this.prepareDialogInfoIcons(aData.suffix, aData.id);
		}

		// Wird readonly in options übergeben, dann werden alle inputs und buttons deaktiviert
		// Wird bei IMMUTABLE_INVOICES in dem Rechnungsdialog verwendet.
		if (aData.options && aData.options.readonly) {
			$j('#'+sDialogId).find('button, select').prop('disabled', true);
			$j('#'+sDialogId).find('textarea, input').prop('readonly', true);
			$j('#'+oDialog._wrapper.id).find('button').not('.close').prop('disabled', true);
			tinymce.editors.forEach(editor => {
				const targetElement = editor.getElement();
				if ($j('#'+oDialog._wrapper.id).find(targetElement)) {
					editor.setMode('readonly');
				}
			});
		}

	},

	getDialogLevel: function () {
		var level = this.aDialogs.length;

		if (this.sParentGuiHash) {
			var oParentGui = this.getOtherGuiObject(this.sParentGuiHash);
			level += oParentGui.getDialogLevel();
		}

		return level;
	},

	executeInnerGuis: function(aInnerGuis) {

		aInnerGuis.each(function(aInnerGui) {

			aGUI[aInnerGui.hash] = eval("new " + aInnerGui.class_js + "('" + aInnerGui.hash + "', '" + this.iShowLeftFrame + "', '" + this.bDebugMode + "')");

			aGUI[aInnerGui.hash].instance_hash = aInnerGui.instance_hash;

			// Wichtig für Datepicker
			aGUI[aInnerGui.hash].sLanguage = this.sLanguage;

			if(aInnerGui.parent_hash) {

				// Kind-GUI beim Papa eintragen
				var oParentGui = aGUI[aInnerGui.parent_hash];
				oParentGui.aChildGuiHash[oParentGui.aChildGuiHash.length] = aInnerGui.hash;
				oParentGui.aChildGuiHash = oParentGui.aChildGuiHash.uniq();

				aGUI[aInnerGui.hash].sParentGuiHash = aInnerGui.parent_hash;

			} else {
				aGUI[aInnerGui.hash].sParentGuiHash = this.hash;
			}

			if(aInnerGui.page_data) {
				aGUI[aInnerGui.hash].aPageData = aInnerGui.page_data;
				// Wichtig! wenn eine Page in einem Dialog ist
				// müssen wir das hier beim erstenmal füllen
				if(!sTopGuiHash) {
					sTopGuiHash = aInnerGui.hash
				}
			}

			if(aInnerGui.view) {
				aGUI[aInnerGui.hash].sView = aInnerGui.view;
			}

			aGUI[aInnerGui.hash].bPageTopGui = false;

			// Bei der ersten GUI einer Page weiteres ausführen
			if(
				aInnerGui.page == 1 &&
				aInnerGui.parent == 1
			) {
				aGUI[aInnerGui.hash].bPageTopGui = true;
				aGUI[aInnerGui.hash].setPageEvents();
			}

			aGUI[aInnerGui.hash].loadTable(true);

			var iFound = this.aInnerGuis.findIndex((aGui2) => aGui2.hash === aInnerGui.hash);
			if (iFound === -1) {
				// Falls das Gui-Objekt noch nicht in this.aInnerGuis steht hinzufügen
				this.aInnerGuis[this.aInnerGuis.length] = aInnerGui;
			}

		}.bind(this));

	},

	openDialogSettings: function(dialogKey) {

		this.request('&task=request&action=dialogSettings&dialog_key=' + dialogKey);

	},

	openDialogSettingsCallback: function(data) {

		console.debug(data);

	},

	removeTCUploader: function(sUID){
		// TC Uploader Initalisieren wenn vorhanden
		if(typeof UploaderHelper == 'function'){
			var oUploaderHelper = new UploaderHelper();
				oUploaderHelper.removeUploader(sUID);
		}
	},

	prepareTCUploaders: function() {

		// TC Uploader Initalisieren wenn vorhanden
		if(typeof UploaderHelper == 'function'){

			var oUploaderHelper = new UploaderHelper();


			var oErrorHandler = function(aErrors, oForm){
				var aFinalErrors = [];
				aFinalErrors[0] = this.getTranslation('upload_error');
				for(var i = 1; i <= aErrors.length; i++){
					 aFinalErrors[i] = [];
					 aFinalErrors[i]['message'] = this.getTranslation(aErrors[(i-1)]);
					 aFinalErrors[i]['input'] = [];
					 aFinalErrors[i]['input']['id'] = oForm.id;

				}
				this.displayErrors(aFinalErrors);
			}.bind(this);

			var oFormHandler = function(oFormData){
				oFormData.append('hash', this.hash);
				oFormData.append('instance_hash', this.instance_hash);
				oFormData.append('task', 'start_tc_upload');
				$H(this.selectedRowId).each(function(aId) {
					var iId = aId[1];
					if(
						typeof iId != 'function' &&
						isNaN(iId) ||
						parseInt(iId) > 0
					) {
						oFormData.append('id[]', iId);
					}
				}.bind(this));
			}.bind(this);

			oUploaderHelper.initializeAllUploader(oErrorHandler, oFormHandler);
		}
	},

	getNotificationDiv: function(sId, sType, sTitle, sMessage) {

		var sClassName;
		var sSrc;
		var sTypeTitle;

		if(sType == 'error') {
			sClassName = 'alert-danger';
			sSrc = 'fa-ban';
			sTypeTitle = this.getTranslation('error_dialog_title');
		} else if(sType == 'success') {
			sClassName = 'alert-success';
			sSrc = 'fa-check';
			sTypeTitle = this.getTranslation('success');
		} else if(sType == 'hint') {
			sClassName = 'alert-warning';
			sSrc = 'fa-warning';
			sTypeTitle = this.getTranslation('hint_dialog_title');
		} else if(sType == 'info') {
			sClassName = 'alert-info';
			sSrc = 'fa-info';
			sTypeTitle = this.getTranslation('info_dialog_title');
		}

		if(!sTitle) {
			sTitle = sTypeTitle;
		}

		var oDiv = new Element('div');
		oDiv.id = 'dialog_' + sType + '_' + sId + '_' + this.hash;
		oDiv.className = 'GUIDialogNotification GUIDialogNotification alert '+sClassName;
		oDiv.style.display = 'none';

		var sContent = '<h4><i class="icon fa '+sSrc+'"></i> <span>'+sTitle+'</span></h4><div class="GuiDescription" style="display:none;">';

		if(sMessage) {
			sContent += sMessage;
		}

		sContent += '</div><div class="GuiDescriptionActions checkbox" style="display:none;"></div>';

		oDiv.innerHTML = sContent;

		return oDiv;
	},

	prepareDialogContent: function(aData) {

		// Event update_select_options nicht ausführen bei initialen Aufbau des Dialogs
		this.bSkipUpdateSelectOptions = true;

		var sIconDataId = this.getIconDataId(aData);

		// Übergebene Werte in die Felder eintragen
		this.setDialogSaveFieldValues(aData);

		// Alle Kalender Felder durchlaufen
		$$('#dialog_' + aData.id + '_' + this.hash + ' .calendar_input').each(function(oCalendarInput) {

			if(!oCalendarInput.readonly) {

				var bShowCalendar = true;

				if(
					oCalendarInput.readOnly &&
					oCalendarInput.readOnly == true
				) {
					bShowCalendar = false;
				}

				if(bShowCalendar == true) {

					var oCalendarImg;

					if(
						oCalendarInput.previous('.calendar_img')
					) {
						oCalendarImg = oCalendarInput.previous('.calendar_img');
					}

					// Felder und Bilder setzten
					if(
						oCalendarInput &&
						oCalendarImg
					) {
						this.prepareCalendar(oCalendarInput, oCalendarImg);
					}

				}

			}

		}.bind(this));

		// Kalender starten
		this.executeCalendars();

		this.pepareHtmlEditors(aData.id);

		this.refreshJoinedObjectContainerEvents(aData.id);

		this.refreshMultirowEvents(aData.id);

		this.prepareUploadFields(aData);

		this.prepareTabareas(aData.id);

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // jQuery
		// multiple
		// selects
		this.initializeMultiselects(aData);
		this.initializeFastselect(aData);
		this.initializeColorpicker(aData);
		this.initializeAutocomplete(aData);
		this.initializeAutoheightTextareas(aData);
		this.prepareDialogAccordions(aData);

		this.bSkipUpdateSelectOptions = false;

	},

	refreshMultirowEvents: function(sDialogId) {

		/**
		 * Multirow add/delete Events
		 */
		$$('#dialog_' + sDialogId + '_' + this.hash + ' .GUIDialogRowInputDivContainer i.btn_add').each(function(oBtn) {

			oBtn.stopObserving();
			oBtn.observe('click', this.addMultirowHandler.bindAsEventListener(this, sDialogId));

		}.bind(this));

		$$('#dialog_' + sDialogId + '_' + this.hash + ' .GUIDialogRowInputDivContainer i.btn_delete').each(function(oBtn) {

			oBtn.stopObserving();
			oBtn.observe('click', this.removeMultirowHandler.bindAsEventListener(this, sDialogId));

		}.bind(this));


	},

	addMultirowHandler: function(oEvent, sDialogId) {

		var oBtn = $j(Event.element(oEvent));

		// Erste Row, da nur hier [0] in ID/Name steht
		var oMasterRow = oBtn.closest('.GUIDialogRowInputDiv').find('.GUIDialogRowInputDivContainer').first().get(0);
		if(!oMasterRow) {
			console.error('No master row found!');
		}

		this.addMultirow(oMasterRow, sDialogId);

	},

	addMultirow: function(oMasterRow, sDialogId) {

		var oNewRow = oMasterRow.clone(true);

		oMasterRow.up().insert({bottom:oNewRow});

		var iCount = oNewRow.up().childElements().length;

		// ID hochzählen
		$j(oNewRow).children(':input').each(function(iKey, oField) {
			// Wert zurücksetzen
			$j(oField).val('');
			oField.id = oField.id.replace(/\[0\]$/, '['+(iCount-1)+']');
			oField.name = oField.name.replace(/\[0\]$/, '['+(iCount-1)+']');
		});

		this.refreshMultirowEvents(sDialogId);
	},

	removeMultirowHandler: function(oEvent) {

		var oBtn = Event.element(oEvent);
		var oRow = oBtn.up('.GUIDialogRowInputDivContainer');
		oRow.remove();
	},

	refreshJoinedObjectContainerEvents: function(sDialogid) {

		$$('#dialog_' + sDialogid + '_' + this.hash + ' .remove_joinedobjectcontainer').each(function(oButton) {

			oButton.stopObserving('click');
			Event.observe(oButton, 'click', function(oEvent) {
				this.removeJoinedObjectContainer(oButton);
				Event.stop(oEvent);
			}.bind(this));

		}.bind(this));

		$$('#dialog_' + sDialogid + '_' + this.hash + ' .add_joinedobjectcontainer').each(function(oButton) {

			oButton.stopObserving('click');
			Event.observe(oButton, 'click', function(oEvent) {

				var sJoinedObjectKey = oButton.id.replace(/add_joinedobjectcontainer_/, '');
				var iCloneCounter = 1;

				// Prüfen ob es ein Input gibt in dem steht wie oft gecloned werden soll
				var oCountField = $('add_joinedobjectcontainer_field_' + sJoinedObjectKey);
				if(oCountField) {
					iCloneCounter = parseInt(oCountField.value);
				}

				var oDialog = this.getDialog(sDialogid);
				var oOptions = oDialog.data.oJoinedObjectContainers.get(sJoinedObjectKey);

				if(oOptions && iCloneCounter > oOptions.joined_object_max) {
					alert(this.getTranslation('joined_object_add_max_allowed').replace('%i', oOptions.joined_object_max));
				} else {
					for(var i = 0; i < iCloneCounter; i++) {
						this.addJoinedObjectContainer(oButton);
					}
				}

				Event.stop(oEvent);
			}.bind(this));

		}.bind(this));

		this.refreshJoinedObjectContainerEventsHook(sDialogid);

		this.executeEventStack();

	},

	/**
	 * Individuelle Events für die wiederholbaren Bereiche
	 */
	refreshJoinedObjectContainerEventsHook: function(sDialogid) {

	},

	addJoinedObjectContainer: function(mObjectKey, sBlockId) {

		var sDialogId = this.sCurrentDialogId;

		var sJoinedObjectKey;
		if(typeof mObjectKey == 'object') {
			sJoinedObjectKey = mObjectKey.id.replace(/add_joinedobjectcontainer_/, '');
		} else {
			sJoinedObjectKey = mObjectKey;
		}

		var oDialog = this.getDialog(sDialogId);
		var oOptions = oDialog.data.oJoinedObjectContainers.get(sJoinedObjectKey);

		// Anzahl der Elemente ermitteln
		var aBlocks = $$('#joinedobjectcontainer_' + sJoinedObjectKey + ' .GUIDialogJoinedObjectContainerRow');
		var oTemplate = aBlocks.first();
		var iBlocks = aBlocks.size();

		var bExecuteEvents = false;
		if(!sBlockId) {
			sBlockId = '-' + iBlocks;
			bExecuteEvents = true;
			// HTML-Editoren neu inintialisieren
			this.closeAllEditors(sDialogId);
		}

		// Wenn Min < 1, dann ersten Block nur einblenden
		if(
			oOptions &&
			oOptions.joined_object_min < 1 &&
			sBlockId == '-1' &&
			oOptions.first_container_hidden
		) {
			sBlockId = '0';
			oOptions.first_container_hidden = 0;
		}

		var sNewContainerId = 'row_joinedobjectcontainer_' + sJoinedObjectKey + '_' + sBlockId;

		// Wenn der Container noch nicht da ist
		if(
			!$(sNewContainerId) &&
			oTemplate
		) {

			if(!oOptions || iBlocks < oOptions.joined_object_max) {

				var oLastSetting = aBlocks.last();

				var oRepeat = oTemplate.clone(true);
				oRepeat.id = sNewContainerId;

				oLastSetting.insert({after: oRepeat});

				oLastSetting.setStyle({
					'display': 'block'
				});

				// Eingabefelder löschen nach kopieren
				$$('#' + oRepeat.id + ' input[type="text"]').each(function(oInput) {
					oInput.value = '';
				});

				// Checkboxen löschen nach kopieren
				$$('#' + oRepeat.id + ' input[type="checkbox"]').each(function(oCheckbox) {
					oCheckbox.checked = false;
				});

				// Multiselect löschen, damit das neu init klappt
				$$('#' + oRepeat.id + ' .ui-multiselect').each(function(oMultiSelect) {
					oMultiSelect.remove();
				});

				// Wochentagsanzeige löschen
				$$('#' + oRepeat.id + ' .GUIDialogRowWeekdayDiv').each(function(oDivWeekday) {
					oDivWeekday.update();
				});

				// IDs neu schreiben
				this.updateJoinedObjectContainerId(oRepeat.id, sJoinedObjectKey, sBlockId);

				$$('#' + oRepeat.id + ' .GUIDialogRowInputDiv').each(function(oInputDiv) {
					oInputDiv = jQuery(oInputDiv);
					oInputDiv.find('.gui2_upload_existing_files').remove();
					oInputDiv.find('input[type="file"]').show();
				});

				$j('#' + $j.escapeSelector(oRepeat.id) + ' input.calendar_input').each(function(iKey, oInput) {
					this.prepareCalendar(oInput);
				}.bind(this));

				// Kalender ausführen
				this.executeCalendars();

				$$('#' + oRepeat.id + ' .remove_joinedobjectcontainer').each(function(oDiv) {
					oDiv.id = 'remove_joinedobjectcontainer_' + sJoinedObjectKey + '_' + sBlockId;
					oDiv.show();
				});

				oDialog = this.getDialog(this.sCurrentDialogId);

				// Es ist nicht ganz klar, warum hier nicht die Daten aus dem Dialog genommen werden
				var aData = {};
				aData.id = this.sCurrentDialogId;
				aData.action = oDialog.data.action;
				aData.additional = oDialog.data.additional;
				aData.values = [];

				this.initializeMultiselects(aData);
				this.initializeAutoheightTextareas(aData);

				this.refreshJoinedObjectContainerEvents(aData.id);
				this.refreshMultirowEvents(aData.id);

				// Events auf Eingabefelder neu setzen
				if(oDialog.element_events) {

					this.aEventsOnOpenDialog = [];

					$j.each(oDialog.element_events, function(sKey, mValue) {

						var aField = sKey.split(/\./, 2);

						// Der Alias ist bei JoinObjectContainern nicht optional
						var sFieldId = 'save[' + this.hash + '][' + aData.id + '][' + aField[1] + '][' + aField[0] + '][' + sBlockId + '][' + sJoinedObjectKey + ']';

						// Wenn es das Feld nicht gibt, dann kommt das Feld innerhalb des Containers nicht vor
						if($(sFieldId)) {
							this.setDialogFieldEvents(sFieldId, mValue, aData, sBlockId);

							/*
							 * dependency_visibility Event ausführen, damit beim Klonen die Elemente ggf. korrekt
							 * versteckt werden
							 * Anmerkung: Wenn Change-Event nicht ausreicht, müssen die Events durchlaufen werden
							 * Events müssen nachträglich ausgeführt werden, sonst stehen für eventuelle Requests noch
							 * nicht alle Werte zur Verfügung
							 */
							this.aEventStack.push({event:'change', object:$(sFieldId)});

						}

					}.bind(this));

				}

				// Multiselect event starten ( selections )
				if(bExecuteEvents) {
					$$('#' + oRepeat.id + ' .jQm').each(function(oMultiSelect) {
						this._fireEvent('change', oMultiSelect);
					}.bind(this));
				}

				// Hook der nach dem Klonen ausgeführt wird
				this.addJoinedObjectContainerHook(oRepeat, sBlockId);

			} else {
				alert(this.getTranslation('joined_object_add_failed'));
			}

		} else {
			if(sBlockId == '0') {
				this.toggleJoinedObjectContainerVisibility($(sNewContainerId), true);
			}
		}

		if(bExecuteEvents) {
			this.pepareHtmlEditors(sDialogId);
		}

		return sNewContainerId;
	},

	executeEventStack: function() {

		for(i = 0; i < this.aEventStack.length; i++) {
			this._fireEvent(this.aEventStack[i].event, this.aEventStack[i].object);
		}

		this.aEventStack = [];

	},

	updateJoinedObjectContainerId: function(sContainerId, sJoinedObjectKey, sBlockId) {

		var oContainer = $(sContainerId);
		oContainer.id = 'row_joinedobjectcontainer_' + sJoinedObjectKey + '_' + sBlockId;
		var oRemoveButton = oContainer.down('.remove_joinedobjectcontainer');
		oRemoveButton.id = 'remove_joinedobjectcontainer_' + sJoinedObjectKey + '_' + sBlockId;

		//Alle Inputs durchgehen & ersetzen
		$$('#' + oContainer.id + ' input').each(function(oInput) {

			this.updateJoinedObjectElementId(oInput, sJoinedObjectKey, sBlockId);

		}.bind(this));

		//Alle Selects durchgehen & ersetzen
		$$('#' + oContainer.id + ' select').each(function(oSelect) {

			this.updateJoinedObjectElementId(oSelect, sJoinedObjectKey, sBlockId);

		}.bind(this));

		//Alle Textareas durchgehen & ersetzen
		$$('#' + oContainer.id + ' textarea').each(function(oTextArea) {

			this.updateJoinedObjectElementId(oTextArea, sJoinedObjectKey, sBlockId);

		}.bind(this));

		return oContainer.id;

	},

	//ID & Name vom geklonten Element austauschen
	updateJoinedObjectElementId: function(oElement, sJoinedObjectKey, sBlockId){

		var oRegex = new RegExp('\\[[\\-0-9]*\\]\\[' + sJoinedObjectKey + '\\]');

		oElement.name = oElement.name.replace(oRegex, '[' + sBlockId + '][' + sJoinedObjectKey + ']');
		oElement.id = oElement.id.replace(oRegex, '[' + sBlockId + '][' + sJoinedObjectKey + ']');

		// Calender IDs austauschen
		if(
			oElement.hasClassName('calendar_input') &&
			oElement.next('img')
		) {
			var oImg = oElement.next('img');
			oImg.id = oImg.id.replace(oRegex, '[' + sBlockId + '][' + sJoinedObjectKey + ']');
		}

		return oElement;
	},

	removeJoinedObjectContainer: function(oButton) {

		/*var aIds = oButtodependen.id.replace(/remove_joinedobjectcontainer_/, '').split(/_/, 2);
		 var sJoinedObjectKey = aIds[0];
		 var iBlock = aIds[1];*/

		// Ersetzt, damit auch ObjektKeys funktionieren, die Unterstriche beinhalten
		var aIds = oButton.id.replace(/remove_joinedobjectcontainer_/, '').match(/(.*?)_((-|)[0-9].*)/);

		var sJoinedObjectKey = aIds[1];
		var iBlock = aIds[2];

		var oDialog = this.getDialog(this.sCurrentDialogId);
		var oOptions = oDialog.data.oJoinedObjectContainers.get(sJoinedObjectKey);

		// Es muss mindestens ein Element bleiben
		var aBlocks = $$('#joinedobjectcontainer_' + sJoinedObjectKey + ' .GUIDialogJoinedObjectContainerRow');
		var iBlocks = aBlocks.size();

		if(oOptions && iBlocks <= oOptions.joined_object_min) {
			alert(this.getTranslation('joined_object_delete_failed'));
			return false;
		}

		if(oOptions && !oOptions.joined_object_no_confirm) {
			var sConfirm = this.getTranslation('joined_object_delete_confirm');
			var bConfirm = confirm(sConfirm);
			if(!bConfirm) {
				return;
			}
		}

		var oContainer = $('row_joinedobjectcontainer_' + sJoinedObjectKey + '_' + iBlock);

		// Alle Container dürden NIE gelöscht werden
		if(iBlocks <= 1) {
			this.toggleJoinedObjectContainerVisibility(oContainer, false);
		} else {
			oContainer.remove();
		}

	},

	/**
	 * Zusätzlich zum Ein- und Ausblenden müssen die Felder deaktiviert werden
	 */
	toggleJoinedObjectContainerVisibility: function(oContainer, bEnable) {
		var aFields = [oContainer.select('input'), oContainer.select('select')];

		if(bEnable) {
			oContainer.show();
		} else {
			oContainer.hide();
		}

		aFields.each(function(aInputs) {
			aInputs.each(function(oInput) {

				if(bEnable) {
					if(oInput.hasClassName('joined_object_container_hidden')) {
						oInput.value = 1;
					} else {
						oInput.disabled = false;
					}
				} else {
					if(oInput.hasClassName('joined_object_container_hidden')) {
						oInput.value = 0;
					} else {
						oInput.disabled = true;
					}
				}

			});
		});

	},

	getAutocompleteSelectionId: function(oSearch, oOption) {
		var oHidden = oSearch.up('.input-group').next('input');

		if(
			oOption &&
				oOption.id
			) {
			oHidden.value = oOption.id;
		} else {
			oHidden.value = '';
			//oSearch.value = '';
		}

		this._fireEvent('change', oHidden);

	},

	getAutocompleteQueryString: function(oInput, strParameters) {

		var oForm = oInput.up('form');

		if(oForm) {
			if(typeof(tinyMCE) != 'undefined') {
				tinyMCE.triggerSave(false, true);
			}
			strParameters += '&' + oForm.serialize();
		}

		return strParameters;

	},

	executeAutocompleteOnComplete: function(oRequest, test) {

		this.updateChoices(oRequest.responseText);
		if(this.entryCount == 0) {
			this.options.afterUpdateElement(this.element, false);
		}

	},

	/**
	 * Liest aus dem Name-Attribut Column und Alias aus
	 */
	parseInputId: function(sInputName, sDialogId) {

		var sRegex = new RegExp('save\\[' + this.hash + '\\]\\[' + sDialogId + '\\]\\[(.*?)\\](\\[(.*?)\\])?', 'g');
		var aRegex = sRegex.exec(sInputName);

		var oReturn = {};
		if(aRegex && aRegex[1]){
		  	oReturn.column = aRegex[1];
		}
		if(aRegex && aRegex[3]){
			oReturn.alias = aRegex[3];
		}

		return oReturn;

	},

	initializeAutocomplete: function(aData) {

		var aOptionDivs = $j('#dialog_wrapper_' + aData.id + '_' + this.hash + ' .autocomplete');

		// if(
		// 	aOptionDivs &&
		// 		aOptionDivs.length > 0
		// 	) {

			aOptionDivs.each(function(iIndex, oOptionDiv) {
				oOptionDiv = $j(oOptionDiv);

				$j(oOptionDiv).addClass('ui-front');

				var oInput = oOptionDiv.prev('.input-group').children('input');
				var oHidden = oOptionDiv.next('input');
				var oLoader = oOptionDiv.prev('.input-group').children('.input-group-addon');

				var oField = this.parseInputId(oHidden.attr('id'), aData.id);
				var sColumn = oField.column;
				var sAlias = oField.alias;

				if(!sAlias) {
					sAlias = '';
				}

				if(
					aData.additional == false ||
						aData.additional == null ||
						(
							aData.additional &&
								(
									aData.additional == 'null' ||
										aData.additional == null
									)
							)
					) {
					aData.additional = '';
				}

				var strParameters = 'hash=' + this.hash + '&instance_hash=' + this.instance_hash + '&action=' + aData.action + '&additional=' + aData.additional + '&task=autocomplete&db_column=' + sColumn + '&db_alias=' + sAlias + '&';
				strParameters += this.getRequestIdParameters();

				requestUrl = this.sRequestUrl;

				$j(oInput).autocomplete({
					source: function(request, response) {

						// Sichergehen das am Ende der Parameter ein "&" kommt um search zu ergänzen
						if (strParameters.charAt(strParameters.length-1) !== '&') {
							strParameters += '&';
						}

						$j.post(
							requestUrl,
							strParameters+'search='+request.term,
							function (data) {
								response(data);
							},
							'json'
						);
					},
					select: function( event, ui ) {
						event.preventDefault();
						var input = $j(event.target);
						var hidden = input.closest('.GUIDialogRowInputDiv').find('input.autocomplete-hidden');
						hidden.val(ui.item.value).change();
						input.val(ui.item.label);
					}
				});

			}.bind(this));

		// }

	},

	bindUploadFieldEvent: function(aData, oFileIcon) {

		oFileIcon.stopObserving('click');
		Event.observe(oFileIcon, 'click', function(e) {
			var sAction = oFileIcon.readAttribute('data-action');
			if(sAction == 'add') {
				this.copyFileUpload(aData, oFileIcon);
			} else if(sAction == 'delete') {
				this.removeFileUpload(aData, oFileIcon);
			} else if(sAction == 'delete_existing_file') {
				if(confirm(this.getTranslation('joined_object_delete_confirm'))) {
					this.removeExistingFile(aData, oFileIcon);
				}
			}
		}.bind(this));

	},

	getUploadSaveMessage: function() {

		return '<div class="gui2_upload_save_message">' + this.getTranslation('save_dialog_message') + '</div>';

	},

	prepareUploadFields: function(aData) {

		var aAddFiles = $$('#dialog_wrapper_' + aData.id + '_' + this.hash + ' .add_file');
		var aDeleteFiles = $$('#dialog_wrapper_' + aData.id + '_' + this.hash + ' .delete_file');
		var aInputs = $$('#dialog_wrapper_' + aData.id + '_' + this.hash + ' div.gui2_uploads input');

		aAddFiles.each(function(oFileAdd) {
			this.bindUploadFieldEvent(aData, oFileAdd);
		}.bind(this));

		aDeleteFiles.each(function(oFileDelete) {
			this.bindUploadFieldEvent(aData, oFileDelete);
		}.bind(this));

		aInputs.each(function(oInput) {
			Event.observe(oInput,'change', function() {
				var oGui2UploadsDiv = oInput.up('div.gui2_uploads');
				if(oGui2UploadsDiv.hasAttribute('data-show-save-message')) {
					var oGuiDialogRowInputDiv = oGui2UploadsDiv.up('div.GUIDialogRowInputDiv');
					if(!oGuiDialogRowInputDiv.down('div.gui2_upload_save_message')) {
						oGuiDialogRowInputDiv.insert({
							'top': this.getUploadSaveMessage()
						});
					}
				}
			}.bind(this))
		}.bind(this));

	},

	copyFileUpload: function(aData, oFileAdd) {

		var oGuiUploadDiv = oFileAdd.up('div.gui2_upload');
		var oGuiUploadDivClone = oGuiUploadDiv.clone(true);
		var oGuiUploadSaveMessage = oGuiUploadDivClone.down('div.gui2_upload_save_message');

		oGuiUploadDiv.up('div.gui2_uploads').insert({
			bottom: oGuiUploadDivClone
		});

		oGuiUploadDivClone.down('input').value = '';
		oGuiUploadDivClone.down('.add_file').hide();

		if(oGuiUploadSaveMessage) {
			oGuiUploadSaveMessage.remove();
		}

		this.bindUploadFieldEvent(aData, oGuiUploadDivClone.down('.add_file'));
		this.bindUploadFieldEvent(aData, oGuiUploadDivClone.down('.delete_file'));

	},

	removeFileUpload: function(aData, oFileDelete) {

		var oGuiUploadDiv = oFileDelete.up('div.gui2_upload');
		var oGuiUploadsDiv = oGuiUploadDiv.up('div.gui2_uploads');

		if(oGuiUploadsDiv.childElements().length == 1) {
			oGuiUploadDiv.down('input').value = '';
		} else {
			oGuiUploadDiv.remove();
			oGuiUploadsDiv.down('div.gui2_upload').down('.add_file').show();
		}

	},

	createExistingFiles: function(aData, aValue, bMultiple) {

		if(
			!aData.value ||
			!aData.value.length ||
			aData.value.length === 0
		) {
			return;
		}

		var oInput = $(aValue.id);

		var oInputDiv = jQuery(oInput.up('div.GUIDialogRowInputDiv'));
		oInputDiv.find('.gui2_upload_existing_files').remove();

		var sName = 'save[' + aData.db_column + ']';
		if
		(
			aData.db_alias &&
			aData.db_alias !== ''
		) {
			sName += '[' + aData.db_alias + ']';
		}

		if
		(
			aData.joined_object_key != null &&
			aData.joined_object_key !== ''
		) {
			sName += '[' + aValue['joined_object_key'] + '][' + aData.joined_object_key + ']';
		}

		if(bMultiple) {
			sName += '[]';
		} else {
			oInput.up('.gui2_upload').hide();
		}

		var sClassForMultipleFiles = '';
		if(bMultiple) {
			sClassForMultipleFiles = 'multiple_files'
		}

		var sGuiExistingFileDiv = '<div class="gui2_upload_existing_files ' + sClassForMultipleFiles + '">';

		aData.value.each(function(sFileName) {
			if(
				sFileName &&
				sFileName !== 'null'
			) {
				sGuiExistingFileDiv += '<div class="gui2_upload_existing_file input-group">' +
					'<a href="' + aData['upload_path'] + sFileName + (aData['no_cache'] ? '?no_cache' : '') + '" target="_blank">' + sFileName + '</a>' +
					'<div class="input-group-btn">' +
					'<i class="delete_file btn fa fa-minus-circle input-group-addon" data-action="delete_existing_file"></i>' +
					'</div>' +
					'<input type="hidden" name="' + sName + '" value="' + sFileName + '" />' +
				'</div>';
			}
		});

		sGuiExistingFileDiv += '</div>';

		oInput.up('div.gui2_uploads').up().insert({
			bottom: sGuiExistingFileDiv
		});

	},

	removeExistingFile: function(aData, oFileDelete) {

		var oGuiUploadExistingFileDiv = oFileDelete.up('div.gui2_upload_existing_file');
		var oGuiUpload = oGuiUploadExistingFileDiv.up('div.GUIDialogRowInputDiv').down('div.gui2_upload');

		oGuiUploadExistingFileDiv.removeClassName('gui2_upload_existing_file');
		oGuiUploadExistingFileDiv.addClassName('gui2_upload_delete_file');
		oGuiUploadExistingFileDiv.down('input').setAttribute('name', oGuiUploadExistingFileDiv.down('input').name.replace('save', 'delete'));
		oGuiUploadExistingFileDiv.hide();

		if(!oGuiUpload.hasAttribute('data-multiple')) {
			oGuiUpload.show();
		}

	},

	initializeColorpicker: function(aData) {

		$j('#dialog_wrapper_' + aData.id + '_' + this.hash + ' .color').colorpicker();

	},

	initializeFastselect: function(aData) {

		var tagsinputs = jQuery(`#dialog_wrapper_${aData.id}_${this.hash} .tagsinput`);
		if (
			tagsinputs.length &&
			typeof tagsinputs.tagsinput === 'function'
		) {
			tagsinputs.tagsinput();
		}

		if(jQuery('#dialog_wrapper_' + aData.id + '_' + this.hash + ' input.fastselect').length) {
			jQuery.Fastselect.defaults.valueDelimiter = '{#}';
			jQuery.Fastselect.defaults.placeholder = this.translations['fastselect_placeholder'];
			jQuery.Fastselect.defaults.searchPlaceholder = this.translations['fastselect_searchPlaceholder'];
			jQuery.Fastselect.defaults.noResultsText = this.translations['fastselect_noResultsText'];
			jQuery.Fastselect.defaults.userOptionPrefix = this.translations['fastselect_userOptionPrefix'];
			jQuery.Fastselect.defaults.loadOnce = true;

			jQuery('#dialog_wrapper_' + aData.id + '_' + this.hash + ' input.fastselect').fastselect();
		}

	},

	initializeMultiselects: function(aData) {

		var ajTaInputs = $A($$('#dialog_wrapper_' + aData.id + '_' + this.hash + ' .jTa'));

		oThis = this;

		ajTaInputs.each(function(ojTaInput) {
			if(ojTaInput.multiple) {
				jQuery(ojTaInput).tagsinput({
					typeahead: {
						source: function(sQuery, oTagsInput) {
							oThis.loadTypeAheadOptions(oTagsInput.$element.get(0), sQuery);
							// TODO: Callbackfunktion verwenden
						}
					}
				});
			}
		}.bind(this));

		var ajQInputs = $A($$('#dialog_wrapper_' + aData.id + '_' + this.hash + ' .jQm'));

		ajQInputs.each(function(ojQInput) {
			if(ojQInput.multiple) {
				var oNext = ojQInput.next();

				if(oNext && oNext.hasClassName('ui-multiselect')) {
					return;
				}

				var bjQSortable = false;
				var bjQSearchable = false;

				var oField = this.parseInputId(ojQInput.id, aData.id);
				var sColumn = oField.column;
				var sAlias = oField.alias;

				var ajQSelected = [];

				aData['values'].each(function(ojQSelected) {

					if(
						sColumn == ojQSelected.db_column && (
							sAlias != '' ||
							sAlias == ojQSelected.db_alias
						)
					) {
						ajQSelected = ojQSelected.value;
					}

				});

				if(ojQInput.hasClassName('jQmsort')) {
					bjQSortable = true;
				}
				if(ojQInput.hasClassName('jQmsearch')) {
					bjQSearchable = true;
				}

				var ojQLocale = {
					addAll: this.translations['jquery_addAll'],
					removeAll: this.translations['jquery_removeAll'],
					itemsCount: this.translations['jquery_itemsCount']
				};

				$j(ojQInput).multiselect({sortable: bjQSortable, searchable: bjQSearchable, locale: ojQLocale, selected_values: ajQSelected});

				// readonly setzen, da ein Select nicht readonly haben kann (disabled funktioniert ohne weiteres Zutun)
				if(ojQInput.hasClassName('readonly')) {
					ojQInput.next().addClassName('readonly');
				}

			}
		}.bind(this));

	},

	/**
	 * Passt die höhe und Breite des Dialoges an ( da man keine höhe definieren
	 * kann.. )
	 */
	resizeDialogSize: function(aData) {

		var oDialogDiv = $('dialog_wrapper_' + aData.id + '_' + this.hash);

		if(!oDialogDiv) {
			return false;
		}

		// var oDialogDivSelect1 = oDialogDiv.select('.dialog-header');
		// var oDialogDivSelect2 = oDialogDiv.select('.dialog-actions');
		//
		// if(!oDialogDivSelect1 || !oDialogDivSelect2) {
		// 	return false;
		// }
		//
		// var oHeaderDiv = oDialogDivSelect1.first();
		// var oActionDiv = oDialogDivSelect2.first();

		var oHeaderLine = oDialogDiv.querySelector('.header-line');
		var oHeaderDiv = oDialogDiv.querySelector('.dialog-header');
		var oBodyDiv = oDialogDiv.querySelector('.dialog-body');
		var oActionDiv = oDialogDiv.querySelector('.dialog-actions');

		if (!oHeaderDiv || !oActionDiv || !oBodyDiv) {
			return false;
		}

		// height - (2 * p-2)
		//var iDialogHeight = oDialogDiv.getHeight() - 16;
		var iDialogHeight = oDialogDiv.getHeight();

		// Dialog mit Tabs
		var oTabDiv = $('tabs_' + aData.id + '_' + this.hash);
		// Dialog ohne Tabs
		var oContentDiv = $('content_' + aData.id + '_' + this.hash);

		if(oTabDiv || oContentDiv) {

			var bodyStyle = getComputedStyle(oBodyDiv)

			// height - (2 * p-1) - (2 * border)
			//var iNewTabContentHeight = iDialogHeight - oHeaderDiv.getHeight() - oHeaderLine.getHeight() - 10
			var iNewTabContentHeight = iDialogHeight - oHeaderDiv.getHeight() - oHeaderLine.getHeight()
				- parseFloat(bodyStyle.paddingTop)
				- parseFloat(bodyStyle.paddingBottom)
				- parseFloat(bodyStyle.marginTop)
				- parseFloat(bodyStyle.marginBottom)
			;

			if ($j(oActionDiv).is(':visible')) {
				// margin-top: 4px
				iNewTabContentHeight = iNewTabContentHeight - oActionDiv.getHeight() - 4
			}

			if(oTabDiv) {
				// Ist man in einem anderen Maintab und resizeDialogSize wird durch Ausblenden einer temporären message
				// ausgeführt, dann liefer getWidth() 0. Da resizen dann nicht nötig ist, abbrechen.
				if (oTabDiv.getWidth() == 0) {
					return;
				}
				var oTabContentDiv = $('tabs_content_' + aData.id + '_' + this.hash);

				iNewTabContentHeight = iNewTabContentHeight - oTabDiv.getHeight();

				var aTabs = $$('.tabBody_' + aData.id + '_' + this.hash + '');

				aTabs.each(function(oTab) {

					oTab.style.height = iNewTabContentHeight + 'px';
					oTab.style.width = oTabDiv.getWidth() + 'px';

					if(oTab.hasClassName('v-scrolling')) {
						oTab.style.overflowX = 'auto';
					} else {
						oTab.style.overflowX = 'hidden';
					}
					oTab.style.overflowY = 'auto';

					this.resizeDialogInnerTabs(oTab);

				}.bind(this));

				oTabContentDiv.style.height = iNewTabContentHeight + 'px';

			} else {

				var oTabContentDiv = oContentDiv;
				oTabContentDiv.style.height = iNewTabContentHeight + 'px';
				oTabContentDiv.style.overflowX = 'hidden';
				oTabContentDiv.style.overflowY = 'auto';

				this.resizeDialogInnerTabs(oTabContentDiv);

			}
		}

	},

	resizeDialogInnerTabs: function(oTab) {

		if($j(oTab).find('.nav-tabs').length > 0) {
			var oPanes = $j(oTab).find('.tab-pane');
			var iHeight = $j(oTab).height() - 40;
			oPanes.height(iHeight);
		}

	},

	/*
	 * Setzt die Übergebenen Werte in die entsprechenden Inputs schreibt ebenfalls
	 * Select options neu wenn welche mit übergeben werden
	 */
	setDialogSaveFieldValues: function(aDialogData) {

		var aJoinedObjectContainerReady = [];
		this.aEventsOnOpenDialog = [];

		if(!aDialogData.values) {
			return false;
		}

		var oDialog = this.getDialog(this.sCurrentDialogId);
		var aValueData = aDialogData.values;

		if(oDialog) {
			// Dialog oJoinedObjectContainers-Cache initiieren/zurücksetzen
			oDialog.data.oJoinedObjectContainers = $H();
		}

		if(aValueData.length > 0) {

			aValueData.each(function(aData) {

				var sId;

				if(aData.id) {
					sId = aData.id;
				} else {
					sId = 'save[' + this.hash + '][' + aDialogData.id + '][' + aData.db_column + ']';
					if(aData.db_alias) {
						sId += '[' + aData.db_alias + ']';
					}
				}

				var aIds = [];
				var aSelectOptions = [];

				// Multirows (+/-)
				var cSetJoinTableData = function(aData, sId, iRowKey) {

					if(!aData['value'].length || !$(sId+'[0]')) {
						return;
					}

					aData['value'].forEach(function(mValue, iValueKey) {

						var oId = {
							id: sId + '[' + iValueKey + ']',
							value: mValue,
							joined_object_key: iRowKey
						};

						if(
							iValueKey > 0 &&
							!$(oId['id'])
						) {
							var oFirstContainer = $(sId+'[0]').up();
							this.addMultirow(oFirstContainer, aDialogData.id);
						}

						if(aData.select_options) {
							aSelectOptions[oId.id] = aData.select_options;
						}

						aIds[aIds.length] = oId;

					}.bind(this));

				}.bind(this);

				if(aData.joined_object_key) {

					var oOptions = oDialog.data.oJoinedObjectContainers.get(aData.joined_object_key);

					// Da erster Block, setze die Options vom Container
					if(
						!oOptions ||
						typeof oOptions.lockOptions == 'undefined'
					) {

						// Erstes Element darf nur versteckt werden, wenn noch kein Container gespeichert wurde
						if(
							aData.joined_object_min < 1 &&
							aData.value.length == 0
						) {
							aData.first_container_hidden = 1;
						} else {
							aData.first_container_hidden = 0;
						}

						aData.lockOptions = true;
						oDialog.data.oJoinedObjectContainers.set(aData.joined_object_key, aData);

					}

					var bFirst = true;

					// Nur einmal pro Container ausführen
					if(!aJoinedObjectContainerReady[aData.joined_object_key]) {

						// Aktuelle Container speichern
						var aCurrentBlocks = $$('#joinedobjectcontainer_' + aData.joined_object_key + ' .GUIDialogJoinedObjectContainerRow');

						var aCurrentBlockCache = new Hash();
						aCurrentBlocks.each(function(oCurrentBlock) {
							var oCheckDiv = oCurrentBlock.up('.GUIDialogJoinedObjectContainer');
							if(
								oCheckDiv &&
								oCheckDiv.id == 'joinedobjectcontainer_' + aData.joined_object_key
							) {
								aCurrentBlockCache.set(oCurrentBlock.id, oCurrentBlock);
							}
						});

						// Löschen-Button vom ersten Container einblenden, da man ihn bei min=0 löschen darf
						if(
							aData.joined_object_min == 0 &&
							aCurrentBlocks.length > 0
						) {

							var iFirstContainerId = aCurrentBlocks[0].id.split('_').pop();
							var oRemoveButton = $('remove_joinedobjectcontainer_' + aData.joined_object_key + '_' + iFirstContainerId);

							if(oRemoveButton) {
								oRemoveButton.show();
							}

						}

						aData.value.each(function(aJoinData) {

							var iRowKey = aJoinData['id'];
							if(iRowKey < 1) {
								iRowKey = aJoinData['key'];
							}

							if(!bFirst) {

								// Neuen Container erstellen
								var sNewJoinedObjectContainer = this.addJoinedObjectContainer(aData.joined_object_key, iRowKey);
								aCurrentBlockCache.unset(sNewJoinedObjectContainer);

							} else {

								var sContainerId = 'row_joinedobjectcontainer_' + aData.joined_object_key + '_0';

								// Wenn der Standardcontainer noch vorhanden ist
								if(
									$(sContainerId) &&
									aJoinData['id'] != 0
								) {
									this.updateJoinedObjectContainerId(sContainerId, aData.joined_object_key, iRowKey);
								}

								aCurrentBlockCache.unset('row_joinedobjectcontainer_' + aData.joined_object_key + '_0');
								aCurrentBlockCache.unset('row_joinedobjectcontainer_' + aData.joined_object_key + '_' + iRowKey);

							}

							bFirst = false;
						}.bind(this));

						// Cache Eintrag wird entfernt, damit Container 0 nicht gelöscht wird
						if(aData.value.size() == 0) {
							aCurrentBlockCache.unset('row_joinedobjectcontainer_' + aData.joined_object_key + '_0');

							// Ersten Container verstecken, da er kein Pflichtfeld ist
							if(aData.first_container_hidden) {
								var oContainer = $('row_joinedobjectcontainer_' + aData.joined_object_key + '_0');

								// Sollte der Dialog schon einen gespeicherten Container haben, gibt es _0 nicht
								if(oContainer) {
									this.toggleJoinedObjectContainerVisibility(oContainer, false);
								}
							}

						}

						// Überflüssige Container entfernen
						aCurrentBlockCache.each(function(oCurrentBlockCache) {

							if(oCurrentBlockCache.value) {
								oCurrentBlockCache.value.remove();
							}
						});

						aJoinedObjectContainerReady[aData.joined_object_key] = 1;
					}

					aData.value.each(function(aJoinData) {

						var iRowKey = aJoinData['id'];
						if(iRowKey < 1) {
							iRowKey = aJoinData['key'];
						}

						var sTempId = sId + '[' + iRowKey + '][' + aData.joined_object_key + ']';

						if(aData.additional_id) {
							// Bei den Flex-Feldern kommt z.b. hinten noch etwas an die ID dran
							sTempId += aData.additional_id;
						}

						if(aData.multi_rows) {
							cSetJoinTableData(aJoinData, sTempId, iRowKey);
						} else {
							var aId = [];
							aId['id'] = sTempId;
							aId['value'] = aJoinData['value'];
							aId['joined_object_key'] = iRowKey;
							aIds[aIds.length] = aId;

							if(aData.joined_object_options) {
								aSelectOptions[aId['id']] = aData.select_options[iRowKey];
							} else {
								aSelectOptions[aId['id']] = aData.select_options;
							}

						}

					}.bind(this));

					if(aData.value.size() == 0) {
						var aId = [];
						aId['id'] = sId + '[0][' + aData.joined_object_key + ']';
						aId['value'] = '';
						aId['joined_object_key'] = '0';
						aIds[aIds.length] = aId;
						aSelectOptions[aId['id']] = aData.select_options;
					}

				} else {

					if(aData.multi_rows) {
						cSetJoinTableData(aData, sId, null);
					} else {

						var aId = [];
						aId['id'] = sId;
						aId['value'] = aData.value;
						aIds[aIds.length] = aId;

						aSelectOptions[aId['id']] = aData.select_options;

					}

				}

				aIds.each(function(aValue) {

					sId = aValue['id'];
					aData.value = aValue['value'];

					if($(sId)) {

						var oInput = $(sId);

						if(oInput.tagName == 'DIV') {

							if(
								aData.value != 0 &&
								aData.value != ''
							) {
								oInput.innerHTML = aData.value;
							} else if(
								aData.default_value &&
								aData.default_value != '' &&
								aData.default_value != 0
							) {
								oInput.innerHTML = aData.default_value;
							}

						} else {

							var oLabelDiv;
							var oRowDiv = oInput.up('.GUIDialogRow');

							// Wenn die ROW ne ID hat dann nicht
							// von diesem Element ausgehen! da evt eine [] in der ID steht und .down/.up etc damit nicht klar kommt!
							if (
								oRowDiv &&
								oRowDiv.id &&
								oRowDiv.id != ""
							) {

								oRowDiv = oInput.up('.GUIDialogRowInputDiv');

								if (oRowDiv) {
									oRowDiv = oRowDiv.previous('.GUIDialogRowLabelDiv');
									if (oRowDiv) {
										oLabelDiv = oRowDiv.down();
									}
								}

							} else {

								if (oRowDiv) {
									oRowDiv = oRowDiv.down('.GUIDialogRowLabelDiv');
									if (oRowDiv) {
										oLabelDiv = oRowDiv.down();
									}
								}
							}

							if (oLabelDiv) {

								if (aData.required != undefined) {
									oLabelDiv.removeClassName('required');
									oInput.removeClassName('required');
									// Wenn das Feld required ist und nicht blockiert wird
									if (
										aData.required == 1 && !oInput.hasClassName('block_auto_required')
									) {
										oLabelDiv.addClassName('required');
										oInput.addClassName('required');
									}
								}
							}

							// Event setzen
							var sKey = this.getFieldKey(aData);
							if (
								aDialogData.events &&
								aDialogData.events[sKey] &&
								aDialogData.events[sKey].length > 0
							) {

								this.setDialogFieldEvents(sId, aDialogData.events[sKey], aDialogData, aValue['joined_object_key']);

							}

							// Wenn Select optionen gesetzt sind schreibe die Options
							// neu
							if (
								aSelectOptions[sId] &&
								(
									(
										aSelectOptions[sId].length &&
										aSelectOptions[sId].length > 0
									) ||
									aData.force_options_reload == 1
								)
							) {
								this.updateSelectOptions($(sId), aSelectOptions[sId], false);
							}

							// Wert auswählen
							if ($(sId).multiple) {

								if(
									$(sId).tagName == 'INPUT' &&
									$(sId).type == 'file'
								) {

									if(
										aData.value &&
										Object.isArray(aData.value) &&
										aData.value.size() > 0 &&
										aData.value[0] != ""
									) {
										this.createExistingFiles(aData, aValue, true);
									}

								} else if (aData.value && Object.isArray(aData.value) && aData.value.size() > 0) {

									var oSelect = $(sId);
									var aAllOptions = oSelect.childElements();
									var aSelected = aData.value;
									var aNodes = [];
									var sNodeKey = '';

									aAllOptions.each(function (oOption) {
										sNodeKey = 'node_' + oOption.value;
										aNodes[sNodeKey] = oOption;
										oOption.style.backgroundColor = '#FFF';
									});

									aSelected.each(function (iValue) {
										sNodeKey = 'node_' + iValue;
										if (aNodes[sNodeKey]) {
											aNodes[sNodeKey].selected = true;
										} else if (
											// Verhindern, dass »unbekannte Einträge« in das Multiselect kommen bei Abhängigkeiten. ~dg 17.10.2011
											typeof aData.has_dependency == 'undefined' ||
											aData.has_dependency != 1 ||
											aData.always_add_unknown_entries == 1
										) {
											this.addUnknownOption(oSelect, iValue);
										}
									}.bind(this));

								} else if($(sId).tagName == 'INPUT' && aData.value && !Object.isArray(aData.value)) {
									$(sId).value = aData.value;
								}

								if ($(sId).hasClassName('jQm')) {
									try {
										$j($(sId)).multiselect('reloadOptions');
									} catch(e){}
								}

							}
							else if (
								$(sId).type &&
								$(sId).type == 'checkbox'
							) {
								if (
									(
									aData.default_value &&
									aData.value == aData.default_value
									) ||
									(
									!aData.default_value &&
									aData.value > 0
									)
								) {
									$(sId).checked = true;
								} else {
									$(sId).checked = false;
								}
							} else if(
								$(sId).tagName == 'INPUT' &&
								$(sId).type == 'file'
							) {

								if(
									aData.value !== '' &&
									aData.value !== null
								) {

									aData.value = [aData.value];
									this.createExistingFiles(aData, aValue, false);

								}

							} else if(
								(
									(
										aData.value != 0 &&
										aData.value !== '0.00' && // Formatierte 0 :)
										aData.value !== '0,00' // Formatierte 0 :)
									) ||
									(
										$(sId).tagName == 'INPUT' &&
										$(sId).type == 'text'
									) ||
									(
										// Da ich nicht verstanden habe, was im ersten Abschnitt passieren soll,
										// hab ich diesen Abschnitt ergänzt. Bei select soll auch '0' funktionieren /
										// als Wert verwendet werden können, weil die als Option auftauchen können.
										$(sId).tagName == 'SELECT'
									)
								) &&
								aData.value !== '' &&
								aData.value != null
							) {

								$(sId).value = aData.value;

								// Wenn Select mit Abhängigkeit, keine »unbekannten« Einträge einfügen
								if(
									$(sId).type &&
									$(sId).type == 'select-one'
								) {
									if(
										//aData.force_options_reload != 1 &&
										(
											aData.has_dependency == undefined ||
											aData.has_dependency != 1 ||
											aData.always_add_unknown_entries == 1
										) &&
										aData.value != null
									) {
										this.checkSelectOptions(sId, aData.value);
									}

									// Wenn kein Wert vorhanden, ersten Eintrag wählen (Damit auch Chrome diesen anzeigt) #2864
									if(aData.value == null) {
										$(sId).selectedIndex = 0;
									}
								}

								// Wenn autocomplete
								if(
									aData.autocomplete_label &&
									aData.autocomplete_label != ''
								) {
									this.displayWeekDay($(sId), aData.autocomplete_label);

									var sAutocompleteLabel = 'autocomplete_input_' + aData.db_column;
									if(aData.db_alias) {
										sAutocompleteLabel += '_' + aData.db_alias;
									}
									var oAutocompleteLabel = $(sAutocompleteLabel);

									oAutocompleteLabel.value = aData.autocomplete_label;

								}

							} else if(
								aData.default_value &&
								aData.default_value != '' &&
								aData.default_value != 0
							) {
								$(sId).value = aData.default_value;
							}

						}

						// I18N: Wert in alle Felder übernehmen
						if (oInput.hasClassName('i18nInput')) {
							$j(oInput).parent().find('.i18n-copy-value').click(function() {
								$j(oInput).closest('.GUIDialogRowInputDiv').find('input').val($j(oInput).val());
							});
						}

					} else {

						console.error('setDialogSaveFieldValues: Field "' + sId + '" not found', aValue);

					}

					// Event auf Plus Button setzen
					if(
						aData.selection_gui &&
						aData.selection_gui.hash &&
						aData.selection_gui.additional
					) {

						var oAddButton = $('selection_gui_button_' + sId);
						if(oAddButton) {

							Event.stopObserving(oAddButton, 'click');

							Event.observe(oAddButton, 'click', function(e) {

								var aElement = {};
								aElement.task = 'openDialog';
								aElement.action = 'selection_gui';
								aElement.additional = aData.selection_gui.additional;
								aElement.request_data = '';

								this.prepareAction(aElement);

							}.bindAsEventListener(this));

						}

					}

				}.bind(this));

			}.bind(this));

		}

		// Events im Dialogobjekt speichern
		if(aDialogData.events) {
			var oDialog = this.getDialog(aDialogData.id);
			oDialog.element_events = aDialogData.events;
		}

		this.executeEventStack();

	},

	setDialogFieldEvents: function(sId, aEvents, aDialogData, iJoinedObjectKey) {

		if($(sId)) {

			var i, oEvent;
			var bIsLastEvent = false;
			var iX = aEvents.length;

			// Events löschen bevor neu gesetzt werden damit doppelte Events möglich sind
			for(i = 0; i < aEvents.length; i++) {
				oEvent = aEvents[i];
				Event.stopObserving($(sId), oEvent.event);
			}

			// Events setzen
			for(i = 0; i < aEvents.length; i++) {

				// Referenz umgehen, da oEvent in Originalform nochmals benötigt wird
				oEvent = $j.extend(true, {}, aEvents[i]);

				// Parameter ist nicht immer vorhanden
				if(
					oEvent.parameter &&
					!this.is_array(oEvent.parameter) &&
					typeof iJoinedObjectKey != 'undefined'
				) {
					oEvent.parameter = oEvent.parameter.replace('{joined_object_container_key}', iJoinedObjectKey);
				}

				iX--;

				if(iX == 0) {
					bIsLastEvent = true;
				}

				if(
					oEvent &&
					oEvent.event &&
					oEvent.event == 'openDialog'
				) {

					var oData = {};
					oData.event = oEvent;
					oData.element = sId;

					this.aEventsOnOpenDialog[this.aEventsOnOpenDialog.length] = oData;

				} else {

					Event.observe($(sId), oEvent['event'], function(e, oEvent) {

						var sFunctionCall = this.createEventFunctionCall(oEvent);

						eval(sFunctionCall);

						if(
							!oEvent.continue &&
							bIsLastEvent // Ist das hier so richtig?
						) {
							Event.stop(e);
						}

					}.bindAsEventListener(this, oEvent));

				}

			}

		}

	},

	createEventFunctionCall: function(aEvent) {
		// Funktion muss per eval aufgerufen werden
		var sFunctionCall = "this.waitForInputEvent('" + aEvent['function'] + "', e, ";

		if(aEvent['parameter']) {
			if(this.is_array(aEvent['parameter'])) {
				sFunctionCall += 'new Array(';
				var bFirst = true;
				aEvent['parameter'].each(function(mCurrentValue) {
					if(!bFirst) {
						sFunctionCall += ',';
					}
					sFunctionCall += mCurrentValue;
					bFirst = false;
				}.bind(this));
				sFunctionCall += ')';
			} else {
				sFunctionCall += aEvent['parameter'];
			}
		} else {
			sFunctionCall += "aDialogData, e";
		}

		sFunctionCall += ")";

		return sFunctionCall;
	},

	/**
	 * Prüft, ob der Eintrag im Select existiert,
	 * ansonsten wird er hinzugefügt und markiert.
	 */
	checkSelectOptions: function(sSelectId, sValue) {

		var oSelect = $(sSelectId);
		var bIsDefinedOption = false;

		oSelect.childElements().each(function(oOption) {
			if(oOption.value == sValue) {
				bIsDefinedOption = true;

			}
		});

		if(!bIsDefinedOption) {

			oSelect.childElements().each(function(oOption) {
				oOption.style.backgroundColor = '#FFF';
			});

			this.addUnknownOption(oSelect, sValue);

		}

		return bIsDefinedOption;

	},

	addUnknownOption: function(oSelect, sValue) {

		var oOptionUndefined = new Element('option', {
			'value': sValue,
			'style': 'background-color: #FCC',
			'selected': 'selected'
		});

		oOptionUndefined.update(this.getTranslation('unknown'));
		oSelect.insert(oOptionUndefined);

		oSelect.observe('change', function() {
			if(oSelect.value != sValue) {
				oSelect.style.backgroundColor = '#FFF';
			} else {
				oSelect.style.backgroundColor = '#FCC';
			}
		});

	},

	prepareUpdateSelectOptions: function(aData, oEvent) {

		if(this.bSkipUpdateSelectOptions === true) {
			return;
		}

		var sParam = '&task=update_select_options';
		sParam += '&action=' + aData.action;
		if(aData.additional) {
			sParam += '&additional=' + aData.additional;
		}
		if(oEvent) {
			sParam += '&event_element=' + Event.element(oEvent).id;
		}
		sParam += '&' + $('dialog_form_' + aData.id + '_' + this.hash).serialize();

		this.request(sParam);

	},

	loadTypeAheadOptions: function(oField) {

		var oDialog = this.getDialog(this.sCurrentDialogId);

		var sParam = '&task=load_typeahead_option';
		sParam += '&action=' + oDialog.data.action;
		sParam += '&field_id=' + oField.id;
		if(oDialog.data.additional) {
			sParam += '&additional=' + oDialog.data.additional;
		}
		sParam += '&' + $('dialog_form_' + this.sCurrentDialogId + '_' + this.hash).serialize();

		return this.request(sParam);
	},

	updateSelectOptions: function(oSelect, aOptions, bHighlight, bRestoreOldValue) {

		if(bHighlight !== false) {
			var bHighlight = true;
		}

		var mOldValue = $F(oSelect);

		var dataAttributesToCarryOver = {};
		oSelect.childElements().each(function(oOption) {
			dataAttributesToCarryOver[oOption.value] = $j(oOption).data();
			oOption.remove();
		});

		if(
			aOptions &&
			aOptions.length &&
			aOptions.length > 0
		) {

			var bSelected = false;

			aOptions.each(function(aOptionData) {

				var oOption = new Element('option');
				oOption.innerHTML = aOptionData.text;
				oOption.value = aOptionData.value;

				if(
					mOldValue &&
					(typeof mOldValue).toLowerCase() == 'object' &&
					bRestoreOldValue
				) {

					mOldValue.each(function(sValue) {

						if(
							(
								bRestoreOldValue &&
								aOptionData.value == sValue
							)
						) {
							oOption.selected = true;
						}

					});

					if(
						aOptionData.selected == 1
					) {
						oOption.selected = true;
					}

				} else if(bRestoreOldValue) {

					if(
						(
							mOldValue &&
							aOptionData.value == mOldValue
						) ||
						aOptionData.selected == 1
					) {
						oOption.selected = true;
					}

				} else if(
					aOptionData.selected == 1
				) {
					oOption.selected = true;
				}

				if(oOption.selected === true) {
					bSelected = true;
				}

				oSelect.appendChild(oOption);
				if (oSelect.dataset['keepData'] && dataAttributesToCarryOver[oOption.value]) {
					$j.each(dataAttributesToCarryOver[oOption.value], function (indexname, value) {
						oOption.dataset[indexname] = value;
					})
				}
			});

			// Wenn kein Wert selektiert wurde, dann ersten Wert wählen
			if(
				bSelected === false &&
				!oSelect.multiple
			) {
				oSelect.selectedIndex = 0;
			}

		} else if(
			oSelect.hasClassName('required') &&
			!oSelect.hasClassName('dependent')
		) {
			/**
			 * @todo Korrekt umsetzen. Musste auskommentiert werden, weil bei abhängigen Selects diese Meldung auch kam.
			 */
			//this.displayEmptySelectMessage(oSelect);
		}

		if(oSelect.hasClassName('jQm')) {
			try {
				$j(oSelect).multiselect('reloadOptions');
			} catch(e){}
		} else if(bHighlight) {
			oSelect.highlight();
		}

		// Change abfeuern
		//this._fireEvent('change', oSelect);

	},

	/**
	 * Fährt eine Fehlernachricht hoch da das Select ein pflichtfeld ist aber keine Auswahlmöglichkeiten vorhanden sind
	 **/
	displayEmptySelectMessage: function(oSelect) {

		var aErrorData = [];

		var sDialogId = this.sCurrentDialogId;

		var sErrorMessage = this.getTranslation('empty_select_error');

		var sLabelHtml = this.getDialogSaveLabelText(oSelect);

		if(sLabelHtml != '') {
			sErrorMessage = sErrorMessage.replace('%s', sLabelHtml);
		} else {
			sErrorMessage = this.getIndividualErrorMessage(sErrorMessage, aErrorData);
		}

		aErrorData[0] = this.getTranslation('hint_dialog_title');
		aErrorData[1] = sErrorMessage;

		this.displayErrors(aErrorData, sDialogId, 'error');
	},

	checkForMinimizedDialog: function(sDialogID) {
		var mBack = false;

		if(
			sDialogID &&
			this.aDialogs
		) {

			// Array bereinigen
			aTmp = this.aDialogs;
			this.aDialogs = [];
			aTmp.each(function(oDialog) {
				// Ist noch da?
				if(oDialog.body) {
					this.aDialogs[this.aDialogs.length] = oDialog;
				}
			}.bind(this));

			this.aDialogs.each(function(oDialog) {
				if(
					oDialog.options.gui_dialog_id == sDialogID
				) {
					mBack = oDialog;
				}
			}.bind(this));
		}

		return mBack;
	},

	createDialogContent: function(aData, oDialog) {

		var oDivContainer = new Element('div');
		oDivContainer.style.position = 'relative';

		this.aInnerGuis = [];

		if(aData.tabs && aData.tabs.length > 0) {

			var oDiv = new Element('div');
			oDiv.id = 'tabs_' + aData.id + '_' + this.hash;
			oDiv.className = 'GUIDialogTabDiv';

			var oDiv2 = new Element('div');
			oDiv2.id = 'tabs_content_' + aData.id + '_' + this.hash;
			oDiv2.className = 'GUIDialogTabContentDiv';

			if(aData.no_scrolling == 1) {
				oDiv2.addClassName('GUIDialogNoScrolling');
			}

			var oUl = new Element('ul');
			//oUl.className = 'clearfix';
			oUl.id = 'ul_' + aData.id + '_' + this.hash;

			var iTab = 0;

			if(!this.aLastDialogTab) {
				this.aLastDialogTab = [];
			}

			if(!this.aLastDialogTab[aData.id]) {
				this.aLastDialogTab[aData.id] = 0;
			}

			if(this.aLastDialogTab[aData.id] > aData.tabs.length) {
				this.aLastDialogTab[aData.id] = 0;
			}

			aData.tabs.each(function(aTab) {

				var iTemp = iTab;
				var sId = '';
				// Tab Header
				// ID des aktuellen Tab
				sId = 'tabHeader_' + iTab + '_' + aData.id + '_' + this.hash;
				var oLi = new Element('li');

				oLi.id = sId;
				oLi.className = 'GUIDialogTabHeader';
				if(this.aLastDialogTab[aData.id] == iTab) {
					oLi.className = 'GUIDialogTabHeader GUIDialogTabHeaderActive';
				}
				// Klasse aller Tabs des Aktuellen Dialoges der aktuellen
				// GUI
				oLi.className += ' tab_' + aData.id + '_' + this.hash;
				// Titel des Tabs schreiben
				oLi.update(aTab.title);

				if(
					aTab.options &&
					aTab.options.hidden
				) {
					oLi.hide();
				}

				// beim anklicken muss der Tab geöffnet werden
				Event.observe(oLi, 'click', function(e) {
					this.toggleDialogTab(iTemp, aData.id);
				}.bind(this));

				oUl.appendChild(oLi);

				// Tab Content

				sId = 'tabBody_' + iTab + '_' + aData.id + '_' + this.hash;

				var oTab = new Element('div');
				oTab.id = sId;
				oTab.className = 'GUITabBody GUITab';
				//oTab.style.display = 'none';

				if(aTab.no_scrolling == 1) {
					oTab.addClassName('GUIDialogNoScrolling');
				}

				var oTabPadding = new Element('div');
				if(!aTab.no_padding) {
					oTabPadding.addClassName('GUIDialogContentPadding');
				}

				if(this.aLastDialogTab[aData.id] == iTab) {
					oTab.removeClassName('GUITabBody');
					oTab.addClassName('GUITabBodyActive');
					//oTab.style.display = '';
				}
				// Klasse aller Tabs des Aktuellen Dialoges der aktuellen
				// GUI
				oTab.addClassName('tabBody_' + aData.id + '_' + this.hash);

				if(aTab.options) {
					if(aTab.options.class) {
						oLi.addClassName(aTab.options.class);
						oTab.addClassName(aTab.options.class);
					}
					if(aTab.options.class_btn) {
						oLi.addClassName(aTab.options.class_btn);
					}
				}

				if(aTab.readonly == true) {
					// Tab ist nicht editierbar alle Bilder entfernen
					aTab.html = aTab.html.replace(/(<img[^<>+]*>)/g, '');
				}

				if(aTab.gui2) {
					oTab.addClassName('gui2');
					aTab.gui2.each(function(aGui2) {
						this.aInnerGuis[this.aInnerGuis.length] = aGui2;
					}.bind(this));
				}

				oTabPadding.innerHTML = aTab.html;
				oTab.appendChild(oTabPadding);

				// generateJS-Methode
				if(
					aTab.js &&
					aTab.js != ''
				) {
					this.aEvalJS[this.aEvalJS.length] = aTab.js;
				}

				oDiv2.appendChild(oTab);

				iTab++;
			}.bind(this));

			oDiv.appendChild(oUl);
			var oCleaner = new Element('div');
			oCleaner.className = 'divCleaner';
			oDiv.appendChild(oCleaner);

			oDivContainer.appendChild(oDiv);
			oDivContainer.appendChild(oDiv2);

		} else if(aData.html) {

			var oDiv = new Element('div');
			oDiv.id = 'content_' + aData.id + '_' + this.hash;
			oDiv.className = 'GUIDialogContentDiv';

			if(aData.no_scrolling == 1) {
				oDiv.addClassName('GUIDialogNoScrolling');
			}

			var oPadding = new Element('div');
			if(!aData.no_padding) {
				oPadding.addClassName('GUIDialogContentPadding');
			}
			if(aData.full_height) {
				oPadding.style.height = '100%';
			}

			if(aData.gui2) {
				oDiv.addClassName('gui2');
				aData.gui2.each(function(aGui2) {
					this.aInnerGuis[this.aInnerGuis.length] = aGui2;
				}.bind(this));
			}

			oPadding.innerHTML = aData.html;

			// generateJS-Methode
			if(
				aData.js &&
				aData.js != ''
			) {
				this.aEvalJS[this.aEvalJS.length] = aData.js;
			}

			if (aData.vue) {
				oDialog.vueApp = window.__FIDELO__.Gui2.createVueApp('GuiDialog', oPadding, this, {data: aData});
			}

			oDiv.appendChild(oPadding);
			oDivContainer.appendChild(oDiv);

		} else {
			oDivContainer.innerHTML = ' Error ';
		}

		return oDivContainer;

	},

	toggleDialogTabByClass: function(sClass, iDialogId) {

		var iTab = $j('#ul_'+this.sCurrentDialogId+'_'+this.hash+' li').index($j('.'+sClass));

		if(typeof(iDialogId) == 'undefined') {
			iDialogId = this.sCurrentDialogId;
		}

		this.toggleDialogTab(iTab, iDialogId)

	},

	/**
	 * Wechselt den Tab
	 */
	toggleDialogTab: function(iTab, iDialogId) {

		// ID des aktuellen Tab
		var sIdHead = '#tabHeader_' + iTab + '_' + iDialogId + '_' + this.hash;
		var sIdBody = '#tabBody_' + iTab + '_' + iDialogId + '_' + this.hash;

		// Klasse aller Tabs des Aktuellen Dialoges der aktuellen GUI
		var sClassHead = '.tab_' + iDialogId + '_' + this.hash;
		var sClassBody = '.tabBody_' + iDialogId + '_' + this.hash;

		// Alle Tabs des aktuellen Dialogs entmakieren
		$$(sClassHead).each(function(oLi) {

			oLi.removeClassName('GUIDialogTabHeaderActive');
			oLi.addClassName('GUIDialogTabHeader');

		}.bind(this));

		$$(sClassBody).each(function(oDiv) {

			oDiv.removeClassName('GUITabBodyActive');
			oDiv.removeClassName('GUITabBody');
			oDiv.addClassName('GUITabBody');

			// Tab ausblenden
			//oDiv.hide();

		}.bind(this));

		// Aktuellen Tab markieren
		$j(sIdHead).addClass('GUIDialogTabHeaderActive');
		$j(sIdBody).removeClass('GUITabBody');
		$j(sIdBody).removeClass('GUITabBodyActive');
		$j(sIdBody).addClass('GUITabBodyActive');

		// Aktuellen Tab einblenden
		//$(sIdBody).show();

		// Wenn Tab eine GUI Tabelle enthält, Table resize der GUI Tabelle ausführen
		var oGuiBody = $$(sIdBody + ' .guiScrollBody');
		if(oGuiBody.length > 0) {
			var sHash = oGuiBody[0].id.replace(/guiScrollBody_/, '');
			if(aGUI[sHash]) {
				aGUI[sHash].resize(true);
			}
		}

		if(!this.aLastDialogTab) {
			this.aLastDialogTab = [];
		}

		if(!this.aLastDialogTab[iDialogId]) {
			this.aLastDialogTab[iDialogId] = 0;
		}

		this.toggleDialogTabHook(iTab, iDialogId);

		this.aLastDialogTab[iDialogId] = iTab;
	},

	getDialog: function(sDialogID) {
		var oDialog = this.checkForMinimizedDialog(sDialogID);
		return oDialog;
	},

	removeEditor: function(sEditorId) {

		if(typeof(tinyMCE) == 'undefined') {
			return;
		}

		var oTiny = tinyMCE.get(sEditorId);

		if(oTiny) {
			try {
				oTiny.save();
				oTiny.remove();
				oTiny.destroy();
				if($(sEditorId)) {
					$(sEditorId).removeClassName('active_editor');
				}
			} catch(e) {
			}
		}

		for(i = 0; i < tinyMCE.editors.length; i++) {
			if(tinyMCE.editors[i].id == sEditorId) {
				tinyMCE.editors.splice(i, 1);
				break;
			}
		}

	},

	closeAllEditors: function(sDialogID) {

		if(typeof(tinyMCE) == 'undefined') {
			return;
		}

		// HTML Editoren entfernen
		var aEditorFields = $$('#dialog_' + sDialogID + '_' + this.hash + ' .GuiDialogHtmlEditor');

		aEditorFields.each(function(oEditor) {
			this.removeEditor(oEditor.id);
		}.bind(this));

		if(
			tinyMCE.editors &&
			tinyMCE.editors.size() > 0
		) {

			tinyMCE.editors.each(function(oEditor) {
				if(!$(oEditor.id)) {
					this.removeEditor(oEditor.id);
				}
			}.bind(this));

		}

		if(typeof oWdHooks != 'undefined') {
			oWdHooks.executeHook('gui2_close_all_editors', sDialogID, this.hash);
		}

	},

	closeDialog: function(sDialogID, sHash) {

		var oObject = this;

		if(sHash && sHash != "") {
			oObject = this.getOtherGuiObject(sHash);
		} else {
			sHash = this.hash;
		}

		// Innere GUIs finden, Ping beenden und zerstören
		var aInnerGuis = $$('#dialog_wrapper_' + sDialogID + '_' + sHash + ' .guiScrollBody');

		if(
			aInnerGuis &&
			aInnerGuis.size() > 0
		) {
			aInnerGuis.each(function(oInnerGui) {
				var sInnerHash = oInnerGui.id.replace(/guiScrollBody_/, '');

				// Inneren Ping stoppen
				if(aGUI[sInnerHash]) {

					if(aGUI[sInnerHash].oPing) {
						aGUI[sInnerHash].oPing.stop();
					}

					// Objekt zerstören
					delete aGUI[sInnerHash];

				}

			});
		}

		// Reset settings
		oObject.bSaveAsNewEntry = false;
		oObject.bAfterSaveOption = false;

		oObject.closeAllEditors(sDialogID);

		var oDialog = oObject.checkForMinimizedDialog(sDialogID);

		if(oDialog) {
			if(oObject.sCurrentDialogId == sDialogID) {
				oObject.sCurrentDialogId = null;
			}
			oDialog.close();
		}

		if(
			sDialogID &&
			oObject.aDialogs
		) {
			var aTemp = [];
			var i = 0;
			oObject.aDialogs.each(function(oDialog) {
				if(oDialog.options.gui_dialog_id != sDialogID) {
					aTemp[aTemp.length] = oDialog;
				}
				i++;
			}.bind(oObject));
			oObject.aDialogs = aTemp;
		}

		if(oDialog) {
			// update_select_options bei SELECTION_GUI Dialog ausführen
			if(oDialog.data.id.indexOf('SELECTION_GUI_') != -1) {
				var oCurrentDialog = oObject.aDialogs.last();
				if(oCurrentDialog) {
					oObject.sCurrentDialogId = oCurrentDialog.data.id;

					this.prepareUpdateSelectOptions(oCurrentDialog.data);

				}
			}

			if (oDialog.vueApp) {
				oDialog.vueApp[0].unmount();
			}
		}

		if (window.__ADMIN__ && this.bOnlyDialogMode) {
            window.__ADMIN__.instance.close({
                hash: this.hash,
                instance_hash: this.instance_hash,
                dialog: sDialogID,
                other_dialogs: oObject.aDialogs.length
            })
		}

	},

	minimizeDialog: function(sDialogID) {

		var oDialog = this.checkForMinimizedDialog(sDialogID);

		if(oDialog) {
			// zum minimieren das löschen des dialoges verhindern
			oDialog.options.destroyOnClose = false;
			oDialog.close();
			// danach wieder aktivieren damit man es am ende doch wiede
			// rkomplett schliesen kann
			oDialog.options.destroyOnClose = true;
		}

		if(!$('dialog_mini_' + sDialogID + '_' + this.hash)) {
			this.createMinimizeDialogBar(sDialogID);
		}

	},

	maximizeDialog: function(sDialogID) {

		var oDialog = this.checkForMinimizedDialog(sDialogID);

		if($('dialog_mini_' + sDialogID + '_' + this.hash)) {
			$('dialog_mini_' + sDialogID + '_' + this.hash).remove();
			this.setMinimizeDialogBars();
		}

		if(oDialog) {
			if(!$('dialog_mini_' + sDialogID + '_' + this.hash)) {
				oDialog.open(true);
				oDialog.options.destroyOnClose = true;
			}
		}

	},

	createMinimizeDialogBar: function(sDialogID, sTitleOriginal) {

		var oDialog = this.getDialog(sDialogID);
		var sTitleOriginal = oDialog.options.sTitleOriginal;

		if(!$('dialog_mini_' + sDialogID + '_' + this.hash)) {
			var oDiv = new Element('div');
			oDiv.className = 'GUIDialogMinimizeBar sc_bg sc_border';
			oDiv.id = 'dialog_mini_' + sDialogID + '_' + this.hash;
			oDiv.update(sTitleOriginal);
			$('myBody').appendChild(oDiv);

			var oImg = new Element('img');
			oImg.src = '/admin/media/application_get.png';
			oImg.className = 'GUIDialogMinimizeBarImg';
			oImg.title = this.getTranslation('maximize');
			oImg.alt = this.getTranslation('maximize');
			oDiv.appendChild(oImg);

			Event.observe(oImg, 'click', function(e) {
				this.maximizeDialog(sDialogID);
			}.bind(this));
		}

		this.setMinimizeDialogBars();

	},

	setMinimizeDialogBars: function() {
		var i = 0;
		var iRight = 0;

		this.aDialogs.each(function(oObject) {

			if(
				oObject != undefined &&
					$('dialog_mini_' + oObject.options.gui_dialog_id + '_' + this.hash)
				) {
				var oDiv = $('dialog_mini_' + oObject.options.gui_dialog_id + '_' + this.hash);
				var aTemp = oDiv.getDimensions();

				var iWidth = aTemp['width'];
				oDiv.style.right = iRight + 'px';
				iRight = iRight + iWidth;
			}
		}.bind(this))
	},

	removeErrors: function(sDialogId, sType) {

		this.resetTabs(sDialogId);

		if(!sType) {
			sType = 'error';
		}

		// Alle Fehlermakierungen entfernen
		$$('.GuiDialogErrorInput').each(function(oInput) {
			oInput.removeClassName('GuiDialogErrorInput');
		});

		if(sDialogId) {
			var sDialogErrorId = 'dialog_' + sType + '_' + sDialogId + '_' + this.hash;
			if($(sDialogErrorId)) {
				var oErrorDescription = $(sDialogErrorId).down('.GuiDescription');
				oErrorDescription.update('');
				var oErrorDescriptionActions = $(sDialogErrorId).down('.GuiDescriptionActions');
				oErrorDescriptionActions.update('');
				$(sDialogErrorId).hide();
			}

			// Container ausblenden
			var sDialogMessageId = 'dialog_messages_' + sDialogId + '_' + this.hash;
			if($(sDialogMessageId)) {
				$(sDialogMessageId).hide();
			}

		}

		var aData = {};
		aData.id = sDialogId;

		this.resizeDialogSize(aData);

	},

	/*
	 * Zeigt die Erfolgsmeldung nach dem speichern an
	 */
	displaySuccess: function(sDialogId, aMessage, sTitle) {

		if(typeof aMessage == 'string') {
			aMessage = [aMessage];
		}

		if(
			!sDialogId &&
			this.sCurrentDialogId
		) {
			sDialogId = this.sCurrentDialogId;
		}

		var oDialog = this.getDialog(sDialogId);

		var bDefaultText = false;

		if(!aMessage) {
			bDefaultText = true;
			aMessage = [this.getTranslation('success')];
		}

		// Die Parameter werden leider unterschiedlich verwendet
		if(!sTitle) {
			if(oDialog) {
				sTitle = aMessage[0];
				aMessage = null;
			} else {
				sTitle = this.getTranslation('save_item');
			}
		}

		if(aMessage !== null) {
			var sMessages = '';
			aMessage.each(function(sMessage) {
				sMessages += sMessage + '<br/>';
			});
		}

		if(oDialog) {

			this.removeErrors(sDialogId);
			this.removeErrors(sDialogId, 'hint');

			var sDialogSuccessId = 'dialog_success_' + sDialogId + '_' + this.hash;
			var sDialogMessagesId = 'dialog_messages_' + sDialogId + '_' + this.hash;
			var oNotificationTitle = this.getNotificationTitleObject('success', sDialogId);

			if(oNotificationTitle) {
				oNotificationTitle.innerHTML = sTitle;

				// sMessage ist der Titel, aber dann ist dementsprechend auch alles fett
				if(sMessages) {
					var oDescription = oNotificationTitle.up().next('.GuiDescription');
					oDescription.show();
					oDescription.innerHTML = sMessages;
				}
			}

			var aData = {};
			aData.id = sDialogId;
			aData.height = oDialog.options.height;
			$(sDialogSuccessId).show();
			$(sDialogMessagesId).show();
			$(sDialogMessagesId).up().show();

			// Erfolgreich-Meldung nach 30 Sekunden ausblenden (nur, wenn das auch der Standardtext ist)
			if(bDefaultText === true) {
				this.iHideSuccessMessageTimeout = setTimeout(function() {
					this.hideSuccess(sDialogSuccessId, aData);
				}.bind(this), 30000);
			} else {
				// Timeout verschwindet nicht auf magische Weise, wenn zwei verschiedene Succes-Meldungen innerhalb des Zeitraums kommen
				if(this.iHideSuccessMessageTimeout !== null) {
					clearTimeout(this.iHideSuccessMessageTimeout);
				}
			}

			this.resizeDialogSize(aData);

		} else {

			this.openDefaultMessageBox('SUCCESS_DIALOG', sTitle, sMessages);

		}
	},

	getNotificationTitleObject: function(sType, sDialogId) {

		var oGuiTitleSpan;
		var sDialogTypeId = 'dialog_' + sType + '_' + sDialogId + '_' + this.hash;
		var oDialogType = $(sDialogTypeId);

		if(oDialogType) {
			var oGuiTitle = oDialogType.down('h4');
			if(oGuiTitle) {
				oGuiTitleSpan = oGuiTitle.down('span');
			}
		}

		return oGuiTitleSpan;
	},

	hideSuccess: function(sDialogSuccessId, aData) {

		if($(sDialogSuccessId)) {

			$(sDialogSuccessId).hide();

			// Container ausblenden
			var sDialogMessageId = 'dialog_messages_' + aData.id + '_' + this.hash;
			if($(sDialogMessageId)) {
				$(sDialogMessageId).hide();
			}

			this.resizeDialogSize(aData);

		}
	},

	displayErrors: function(aErrorData, sDialogId, bNewDialog, bShowSkipErrors, bAutoRemove) {

		// Wenn keine Dialog ID übergeben wurde und der aktuell offene Dialog kein Fehler Dialog ist, dann aktuellen verwenden
		if(
			!sDialogId &&
			this.sCurrentDialogId
		) {
			if(this.sCurrentDialogId.indexOf('ERROR_DIALOG') == -1) {
				sDialogId = this.sCurrentDialogId;
			}
		}

		var aErrors = [];
		var aErrorsHint = [];
		var sErrorTitle = '';

		if(aErrorData) {
			aErrorData.each(function(aError) {
				if(typeof aError == 'object' && aError.type && ['hint', 'hint_codes'].indexOf(aError.type) !== -1) {
					aErrorsHint.push(aError);
				} else {
					aErrors.push(aError);
				}
			});
		}

		if(aErrors.length > 0) {

			if(aErrors.length === 1) {
				var mErrorTitle = this.getTranslation('general_error');
			} else {
				var mErrorTitle = aErrors[0];
				aErrors.splice(0, 1);
			}

			if(typeof mErrorTitle == 'string') {
				sErrorTitle = mErrorTitle;
			} else if(mErrorTitle.message) {
				sErrorTitle = mErrorTitle.message;
			}

		}

		if(sDialogId && bNewDialog != true) {

			this.removeErrors(sDialogId);
			this.removeErrors(sDialogId, 'hint');

			var oDialog = this.getDialog(sDialogId);

			var sDialogSuccessId = 'dialog_success_' + sDialogId + '_' + this.hash;
			var sDialogMessageId = 'dialog_messages_' + sDialogId + '_' + this.hash;

			if(aErrorData && aErrorData.length > 0) {
				if($(sDialogSuccessId)) {
					$(sDialogSuccessId).hide();
				}

				// Timeout von Erfolgreich-Meldung löschen, da das Timeout ansonsten auch Fehler/Warnungen einfach ausblendet
				if(this.iHideSuccessMessageTimeout !== null) {
					clearTimeout(this.iHideSuccessMessageTimeout);
				}
				this.iHideSuccessMessageTimeout = null;
			}

			if(aErrors.length > 0) {
				var oErrorTitle = this.getNotificationTitleObject('error', sDialogId);
				if(oErrorTitle) {
					oErrorTitle.innerHTML = sErrorTitle;
				}
				this.showErrors(aErrors, sDialogId);
			}

			if(aErrorsHint.length > 0) {
				this.showErrors(aErrorsHint, sDialogId, 'hint', bShowSkipErrors);
			}

			this.pepareHtmlEditors(sDialogId);

			var aData = {};
			aData.id = sDialogId;
			// +2 da er sonst nach dem hochfahren komischerweise 2 px zu weit
			// oben ist
			aData.height = oDialog.options.height;

			jQuery(`#${sDialogMessageId}`).slideDown({
				duration: 'slow',
				progress: () => this.resizeDialogSize(aData),
				complete: () => this.resizeDialogSize(aData)
			});

			if(bAutoRemove) {
				setTimeout(	function() {
					this.removeErrors(sDialogId);
				}.bind(this), 5000);
			}

		} else {

			var sHtml = '';
			aErrors.each(function(mError) {
				var sError = '';
				if(typeof mError == 'string') {
					sError = mError;
				} else if(mError.message) {
					sError = mError.message;
				}
				if(sError != '') {
					sHtml += sError + '<br/>';
				}
			});

			var bAutoSize = false;
			if(this.bDebugMode) {
				bAutoSize = true;
			}

			this.openDefaultMessageBox('ERROR_DIALOG', sErrorTitle, sHtml, bAutoSize);

		}

	},

	showErrors: function(aErrorData, sDialogId, sType, bShowSkipErrors) {

		if(!sType) {
			sType = 'error';
		}
		var sDialogErrorId = 'dialog_' + sType + '_' + sDialogId + '_' + this.hash;
		var oErrorDescription = $(sDialogErrorId).down('.GuiDescription');
		var aErrorCache = [];

		var aAlreadyIgnoredErrorCodes = [];
		var aNewErrorCodes = [];

		var sHintMessage = this.getTranslation('ignore_errors');

		aErrorData.each(function(aError) {

			if (aError.type && aError.type === 'hint_codes') {
				// TODO schönere Lösung finden
				aAlreadyIgnoredErrorCodes = aAlreadyIgnoredErrorCodes.concat(aError.codes);
				return;
			}

			var sLabelHtml = '';
			var oInput, sInputId;

			if(typeof aError == 'undefined') {
				// Do nothing
			} else if(typeof aError == 'string') {
				oErrorDescription.innerHTML += aError + '<br/>';
			} else {

				if(
					(
						aError.input && (
							aError.input.id ||
							aError.input.name ||
							(aError.input.dbcolumn && aError.input.dbcolumn !== "0") ||
							aError.input.object
						)
					) || (
						aError.error_id
					)
				) {
					if(aError.input.id) {
						oInput = $(aError.input.id);
					} else if(aError.error_id) {
						sInputId = 'save[' + this.hash + '][' + sDialogId + ']' + aError.error_id;
						oInput = $(sInputId);
					} else if(aError.input.name) {
						if(aError.input.index) {
							// Name muss nicht eindeutig sein
							oInput = $j('[name="' + $j.escapeSelector(aError.input.name) + '"]').get(aError.input.index);
						} else {
							oInput = document.querySelector('[name="' + aError.input.name + '"]');
						}
					} else if(aError.input.object) {
						oInput = aError.input.object;
					} else {
						var sDbColumn = aError.input.dbcolumn;
						var sRegex = new RegExp('\\[', 'g');
						var mRegex = sRegex.exec(sDbColumn);
						//falls in der Fehlermeldung im Key "[" vorkommt(als String), dann wurde direkt eine ID übermittelt
						//das dient zur Markierung jeder beliebigen Stelle im Dialog
						if(mRegex) {
							sInputId = 'save[' + this.hash + '][' + sDialogId + ']' + sDbColumn;
							oInput = $(sInputId);
						} else {
							if(aError.input.dbalias) {
								sInputId = 'save[' + this.hash + '][' + sDialogId + '][' + aError.input.dbcolumn + '][' + aError.input.dbalias + ']';
								oInput = $(sInputId);
							} else {
								sInputId = 'save[' + this.hash + '][' + sDialogId + '][' + aError.input.dbcolumn + ']';
								oInput = $(sInputId);
							}
						}
					}

					if(oInput) {
						sLabelHtml = this.getDialogSaveLabelText(oInput);
						// Fehlerklasse Eingabefeld
						oInput.addClassName('GuiDialogErrorInput');
						// Fehlerklasse multiple select (jQuery)
						if(oInput.hasClassName('jQm')) {
							var oContainer = oInput.next('.ui-multiselect');

							if(oContainer) {
								oContainer.addClassName('GuiDialogErrorInput');
							}
						}
						// Fehlerklasse Tab
						this.highliteTab('error', oInput, sDialogId);
					} else {
						console.error('Could not find an input field for error message', aError);
					}

				}

				if(aError.hintMessage) {
					sHintMessage = aError.hintMessage;
				}

				var sErrorMessage = aError.message;
				if(sLabelHtml != '') {
					sErrorMessage = sErrorMessage.replace('%s', sLabelHtml);
				} else {
					sErrorMessage = this.getIndividualErrorMessage(sErrorMessage, aError);
				}

				if(
					typeof sErrorMessage == 'string' &&
						!aErrorCache[sErrorMessage]
					) {
					oErrorDescription.innerHTML += sErrorMessage + '<br/>';
					aErrorCache[sErrorMessage] = 1;
				}

				if (aError.code) {
					aNewErrorCodes.push(aError.code)
				}

			}

		}.bind(this));

		if(oErrorDescription.innerHTML === '') {
			oErrorDescription.hide();
		} else {
			oErrorDescription.show();
		}

		var oDivDescriptionActionBox = $(sDialogErrorId).down('.GuiDescriptionActions');

		if(oDivDescriptionActionBox) {

			if(bShowSkipErrors) {
				oDivDescriptionActionBox.show();
				oDivDescriptionActionBox.innerHTML = '';
				var oCheckbox = new Element('input');
				oCheckbox.type = 'checkbox';
				oCheckbox.id = 'ignore_errors_' + sDialogId + '_' + this.hash;
				oCheckbox.setAttribute('data-error-codes', aNewErrorCodes.join('{|}'));
				var oLabel = new Element('label');
				oLabel.appendChild(oCheckbox);
				oLabel.innerHTML += sHintMessage;
				oLabel.setAttribute('for', 'ignore_errors_' + sDialogId + '_' + this.hash);
				oDivDescriptionActionBox.appendChild(oLabel);

				if (aAlreadyIgnoredErrorCodes.length > 0) {
					var oHidden = new Element('input');
					oHidden.type = 'hidden';
					oHidden.id = 'ignore_errors_codes_' + sDialogId + '_' + this.hash;
					oHidden.value = aAlreadyIgnoredErrorCodes.join('{|}');
					oDivDescriptionActionBox.appendChild(oHidden);
				}
			} else {
				oDivDescriptionActionBox.hide();
			}

		}

		$(sDialogErrorId).show();
		$(sDialogErrorId).up().up().show();

		this.scaleErrorMessageBox($(sDialogErrorId));

	},

	scaleErrorMessageBox: function(oDialogMessage) {

		var oDialogBody = oDialogMessage.up('.dialog');

		var oDialogContent = oDialogBody.down('.dialog-content');
		var oErrorDescription = oDialogMessage.down('.GuiDescription');
		var oErrorDescriptionContainer = oErrorDescription.up('.GUIDialogMessages');
		var iMaxHeight = 100;

		if(
			oErrorDescription &&
			oDialogContent &&
			oErrorDescriptionContainer &&
			(
				oErrorDescriptionContainer.getHeight() > oDialogContent.getHeight() ||
				oErrorDescription.innerHTML.length > 700
			)
		) {
			var fDialogHeight = oDialogContent.getHeight() / 2;

			/*
			* Da der Deaktivieren-Dialog kleiner ist, darf die Fehlermeldung kein max-height von 100px beinhalten
			* sondern 35px damit alles vom Dialog noch zu sehen ist
			*/
			if(oErrorDescriptionContainer.getHeight() > fDialogHeight) {
				iMaxHeight = 35;
			}
			oErrorDescription.setStyle({
				overflow: 'auto',
				maxHeight: iMaxHeight + 'px'
			});
			//oErrorDescription.addClassName('GuiErrorShadow');
		}

	},

	getIndividualErrorMessage: function(sErrorMessage, aError) {
		return sErrorMessage;
	},

	// highlitet ein Tab in dem ein Input ist
	highliteTab: function(sType, oInput, sDialogId) {
		if(
			oInput &&
			oInput.up('.tabBody_' + sDialogId + '_' + this.hash)
		) {
			var oTab = oInput.up('.tabBody_' + sDialogId + '_' + this.hash);

			var sTabHeaderId	= oTab.id.replace('tabBody', 'tabHeader');

			var oTabHeader		= $(sTabHeaderId);

			if(oTabHeader)
			{
				if(sType == 'error')
				{
					oTabHeader.addClassName('GuiDialogErrorTab');
				}
			}

		}
	},

	// highlitet ein Tab in dem ein Input ist
	resetTabs: function(sDialogId) {

		$$('#tabs_' + sDialogId + '_' + this.hash + ' li').each(function(oTab) {
			oTab.removeClassName('GuiDialogErrorTab');
		});

	},

	openDefaultMessageBox: function(sId, sTitle, sHtml, bAutoSize) {

		if(bAutoSize == undefined) {
			bAutoSize = false;
		}

		var aData = {};
		aData.id = sId;

		var oDialog = this.checkForMinimizedDialog(aData.id);

		if(oDialog) {
			oDialog.close();
		}

		if(!bAutoSize) {
			aData.width = 600;
			aData.height = 400;
		}

		aData.title = sTitle;
		aData.bSaveButton = 0;
		aData.html = sHtml;

		this.openDialog(aData);

	},

	requestError: function(oJson, oException) {

		if(console) {
			console.log('GUI2 reportError');
			console.log(oJson);
			console.log(oException);

			if(oException.stack !== null) {
				console.log(oException.stack);
			}
		}

		/*var iStatus = '';
		if(oJson && oJson.status) {
			iStatus = oJson.status;
		}

		var sParam = '&task=reportError&request_status=' + iStatus;

		if(oException) {
			if(oException.message.length > 262144) {
				// Maximal 2,5MB, da Rest Stack Trace sein sollte
				// Ist der String zu groß, kann der Browser hängen bleiben und/oder der Server liefert Error 413
				sParam += '&error=' + encodeURIComponent(oException.message.substring(0, 262144));
			} else {
				sParam += '&error=' + encodeURIComponent(oException.message);
			}
		}

		if(
			oJson.request &&
			oJson.request.parameters
		) {

			if(oJson.request.parameters.task) {
				var bCheckLoopError = (oJson.request.parameters.task == 'reportError');
				var bCheckLoopPing = (oJson.request.parameters.task == 'ping');

				// Wenn der String gefunden wurde, abbrechen!
				if(
					bCheckLoopError ||
						bCheckLoopPing
					) {
					return false;
				}
			}

			sParam += '&parameters=' + encodeURIComponent(Object.toJSON(oJson.request.parameters));

		}

		if(
			oJson.request &&
			oJson.request.body
		) {

			var bCheckLoop = oJson.request.body.indexOf('task=reportError');

			// Wenn der String gefunden wurde, abbrechen!
			if(bCheckLoop != -1) {
				return false;
			}

			sParam += '&query_string=' + encodeURIComponent(oJson.request.body);

		}

		// Fehlermeldung an Server senden
		this.requestBackground(sParam);*/

	},

	/**
	 * Berechnet die Position des Tabellen kopfes ( für horizontales scrollen )
	 */
	calculateHeaderPosition: function(e) {
		var oHeaderDiv = $('guiTableHead_' + this.hash);
		var aOffset = $('guiTableBody_' + this.hash).cumulativeScrollOffset();
		oHeaderDiv.style.left = aOffset[0] * -1 + 'px';
	},

	/**
	 * Berechnet die Position der summen Tabelle ( für horizontales scrollen )
	 */
	calculateSumPosition: function(e) {
		var oSumDiv = $('guiTableSum_' + this.hash);
		if(oSumDiv) {
			var aOffset = $('guiTableBody_' + this.hash).cumulativeScrollOffset();
			oSumDiv.style.left = aOffset[0] * -1 + 'px';
		}
	},

	/**
	 * Past die Tabellen an den Browser an
	 */
	resize: function(bRecalculateWidths) {

		if(bRecalculateWidths == true) {
			this.calculateColumnWidths();
		}

		this.resizeTableHead();
		this.resizeTableColumns();
		// Darf erst hinter resizeTableColumns() erfolgen, da evtl. Umbrüche entstehen können
		this.resizeTableBody();

		this.resizeDialogs();

		this.resizeHook();
		// Zweite Bar-Zeile anzeigen Button einblenden falls nötig
		this.resizeBars();
	},

	/**
	 * Passt die Dialoge an die geänderte Höhe und/oder Breite des Fensters an
	 */
	resizeDialogs: function() {

		// Passt jeden offenen Dialog an
		this.aDialogs.each(function(oDialog) {

			// Dialog nicht mehr vorhanden?
			if(!oDialog.body) {
				return;
			}

			// Das aData-Objekt für this.resizeDialogSize() faken
			var oData = {
				id: oDialog.options.gui_dialog_id
			};

			// Wenn Höhe oder Breite automatisch ausgerechnet, dann neu rechnen!
			if(oDialog.options.bAutoWidth) {
				oDialog.options.width = this.getDocumentWidth() - 40;
			}
			if(oDialog.options.bAutoHeight) {
				oDialog.options.height = this.getDocumentHeight(true) - 40
			}

			oDialog.position();
			this.resizeDialogSize(oData);

		}.bind(this));
	},

	// Tabellen spaltenbreite dynamisch mit anpassen
	resizeTableColumns: function() {

		if($('guiTableHead_' + this.hash)) {
			// Colgroups holen

			if($('guiTableHead_' + this.hash).down('.guiTableHead')) {

				var oColHead = $('guiTableHead_' + this.hash).down('.guiTableHead').down('colgroup');
				var oColBody = $('guiTableBody_' + this.hash);

				if(oColHead && oColBody) {

					oColBody = oColBody.down('colgroup');

					var oColSum = null;
					if($('guiTableSum_' + this.hash)) {
						oColSum = $('guiTableSum_' + this.hash).down('colgroup');
					}
					var i = 0;
					var aWidths = [];

					if(this.options.row_sortable == 1) {
						aWidths[i] = 30 + this.aCalculatedColumnWidths['iOpenWidthEachColumn'];
						i++;
					}

					// Breiten errechnen
					if(this.options.multiple_selection == 1) {
						aWidths[i] = 30 + this.aCalculatedColumnWidths['iOpenWidthEachColumn'];
						i++;
					}

					if(this.aTableHeadData) {
						this.aTableHeadData.each(function(aHead) {
							aWidths[i] = aHead.width;
							if(
								aHead.width_resize == true ||
								aHead.width_resize == 'true' ||
								aHead.width_resize == 1
							) {
								aWidths[i] += this.aCalculatedColumnWidths['iOpenWidthEachResizeColumn'];
							}
							aWidths[i] += this.aCalculatedColumnWidths['iOpenWidthEachColumn'];
							i++;
						}.bind(this));
					}

					// Breiten anwenden
					var iColumnCount = oColHead.childElements().size();
					i = 0;
					var iTotalTableWidth = 0;
					oColHead.childElements().each(function(oCol) {
						iTotalTableWidth += aWidths[i];
						var iColumnWidth = (aWidths[i] + this.aTableCellOffset[0]);

						if(iColumnCount == (i+1)) {
							iColumnWidth -= this.iTableWidthDiff;
						}

						oCol.width = iColumnWidth;
						//oCol.style.minWidth = iColumnWidth + 'px';
						i++;
					}.bind(this));

					i = 0;
					oColBody.childElements().each(function(oCol) {
						oCol.style.width = (aWidths[i] + this.aTableCellOffset[1]) + 'px';
						//oCol.style.minWidth = oCol.style.width;
						i++;
					}.bind(this));

					if(oColSum != null) {
						i = 0;
						oColSum.childElements().each(function(oCol) {
							oCol.style.width = (aWidths[i] + this.aTableCellOffset[0]) + 'px';
							//oCol.style.minWidth = oCol.style.width;
							i++;
						}.bind(this));
					}

					// Breite der Head Tabelle angleichen
					$('guiTableHead_' + this.hash).down().style.width = iTotalTableWidth + 'px';
					// Breite der Daten Tabelle angleichen
					$('guiTableBody_' + this.hash).style.width = iTotalTableWidth + 'px';

				}

			}
		}

	},

	/**
	 * Past die obere Tabelle der unteren an
	 */
	resizeTableHead: function() {

		if(
			$('guiScrollBody_' + this.hash) &&
			$('guiTableHead_' + this.hash)
		) {

			var iWidthTable = 0;
			var iWidthContainer = 0;

			// Große der unteren Tablle auslesen
			iWidthContainer = $('guiScrollBody_' + this.hash).getWidth();
			if($('guiTableBody_' + this.hash)) {
				iWidthTable = $('guiTableBody_' + this.hash).getWidth();
			} else {
				iWidthTable = iWidthContainer;
			}
			var sWidth = '';

			if(
				iWidthContainer <= 0 &&
				iWidthTable <= 0
			) {
				sWidth = '100%';
			} else if(iWidthContainer < iWidthTable) {
				sWidth = iWidthTable + 'px';
			} else {
				sWidth = iWidthContainer + 'px';
			}

			// Breite des oberen Containers angleichen
			$('guiTableHead_' + this.hash).style.width = sWidth;

			// Wenn es eine Summenzeile gibt, dann auch anpassen
			if($('guiTableSum_' + this.hash)) {
				$('guiTableSum_' + this.hash).style.width = sWidth;
			}

			return sWidth;

		}

		return false;

	},

	getDocumentHeight: function(bIgnoreguiPageData) {

		var iHeight;

		//Schauen ob die GUI sich in einem Dialog befindet
		if(
			$('guiTableHead' + '_' + this.hash) &&
			$('guiTableHead' + '_' + this.hash).up('.dialog-wrapper')
		) {

			// Dialog mit Tabs
			var oTabDiv = $('guiTableHead' + '_' + this.hash).up('.GUIDialogTabContentDiv');
			// Dialog ohne Tabs
			var oContentDiv = $('guiTableHead' + '_' + this.hash).up('.GUIDialogContentDiv');

			if(oTabDiv) {
				var oDialogDocument = oTabDiv;
			} else {
				var oDialogDocument = oContentDiv;
			}

			iHeight = oDialogDocument.getHeight();

			// Befindet sich die GUI in einem Container DIV?
		} else if(
			$$('.gui2_container #divHeader_' + this.hash).size() > 0
		) {

			var oHeader = $('divHeader_' + this.hash);
			var oContainer = oHeader.up('.gui2_container');

			iHeight = oContainer.getHeight();

		} else {

			iHeight = $j(window).height();

		}


		if(
			this.aPageData &&
			!bIgnoreguiPageData
		) {

			if(
				this.iPageResizeHeight > 0 &&
				this.bPageTopGui
			) {
				iHeight = this.iPageResizeHeight;
			} else if(
				this.iPageResizeHeight > 0 &&
				!this.bPageTopGui
			) {
				iHeight = iHeight - this.iPageResizeHeight - 3;
			} else {
				// Standardgröße beim öffnen der Seite
				var iPercentHeight = 50;

				if(
					this.aPageData.config.height &&
					this.aPageData.config.height > 0 &&
					this.aPageData.config.height < 100
				) {
					iPercentHeight = parseInt(this.aPageData.config.height);
				}

				iHeight = Math.round(((iHeight - 5) / 100) * iPercentHeight);

				if(
					this.aPageData.config.min_height &&
					iHeight < this.aPageData.config.min_height
				) {
					iHeight = this.aPageData.config.min_height;
				}

			}

		}

		return iHeight;
	},

	getDocumentWidth: function() {

		var iWidth = 0;

		if(window.innerWidth) {
			iWidth = window.innerWidth;
		} else if(
			document.body &&
				document.body.offsetWidth
			) {
			iWidth = document.body.offsetWidth;
		}

		//Schauen ob die GUI sich in einem Dialog befindet
		if(
			$('guiTableHead' + '_' + this.hash) &&
				$('guiTableHead' + '_' + this.hash).up('.dialog-wrapper')
			) {
			iWidth = $('guiTableHead' + '_' + this.hash).up('.dialog-wrapper').getWidth();
		}

		return iWidth;
	},

	/**
	 * Past die untere Tabellengröße dem Browser an
	 */
	resizeTableBody: function() {

		if($('guiScrollBody' + '_' + this.hash)) {

			// Größe des Fensters bestimmen;
			var iHeight = this.getDocumentHeight();

			if(this.sParentGuiHash === '') {

				// Titel
				if($j('.content-header').length > 0) {
					iHeight -= $j('.content-header').height()+15;
				}

				// Box-Padding
				iHeight -= 16;

			}

			// Höhe der oberen Leiste auslesen
			var oDivHeader = $('divHeader' + '_' + this.hash);
			if(oDivHeader) {
				var iBarHeight = oDivHeader.getHeight();
				iHeight = iHeight - iBarHeight;
			}

			// Oberer Tabellenkopf abziehen
			var iHeadHeight = $('guiTableHead' + '_' + this.hash).getHeight();
			iHeight = iHeight - iHeadHeight;

			// Summen Tabelle abziehen
			if($('guiTableSum' + '_' + this.hash) && $('guiTableSum' + '_' + this.hash).visible()) {
				var iSumHeight = $('guiTableSum' + '_' + this.hash).getHeight();
				iHeight = iHeight - iSumHeight - 5;
			}

			if($('Gui2ChildTableButton_' + this.hash)) {
				var iChildHeadHeight = $('Gui2ChildTableButton_' + this.hash).up('.Gui2ChildTableButtonContainer').getHeight();
				if ($('Gui2ChildTableDraggable_' + this.sParentGuiHash)) {
					iChildHeadHeight += $('Gui2ChildTableDraggable_' + this.sParentGuiHash).up('.divHeaderSeparator').getHeight();
					iChildHeadHeight += 3;
				}
				iHeight = iHeight - iChildHeadHeight;
			}

			// Höhe der unteren Leiste auslesen
			if($('divFooter' + '_' + this.hash)) {
				var iBottomBarHeight = $('divFooter' + '_' + this.hash).getHeight();
				iHeight = iHeight - iBottomBarHeight;
			}

			// Neue Tabellenhöhe schreiben
			if(
				$('guiScrollBody' + '_' + this.hash) &&
				iHeight > 0
			) {
				$('guiScrollBody' + '_' + this.hash).style.height = iHeight + 'px';
				$('guiScrollBody' + '_' + this.hash).className = 'guiScrollBody';
			}

		}

	},

	changeMultipleSelection: function(e, oImg) {

		if(!oImg) {
			oImg = $('guiMultipleImg_' + this.hash);
		}

		this.selectRow();

		if(this.multipleSelection == 0) {
			this.multipleSelection = 1;
			this.multipleSelectionData = [];
			oImg.src = '/admin/media/table.png';
			oImg.title = this.getTranslation('one selection');
			oImg.alt = this.getTranslation('one selection');
		} else {
			this.multipleSelection = 0;
			this.multipleSelectionData = [];
			oImg.src = '/admin/media/table_multiple.png';
			oImg.title = this.getTranslation('multiple selection');
			oImg.alt = this.getTranslation('multiple selection');
		}

	},

	// Methode um die Breiten der Spalten zu berechnen
	calculateColumnWidths: function(aData) {

		this.testBoxModel();

		// /////////////////////////////////////////////////

		if(!aData) {
			if(this.aTableHeadData) {
				aData = this.aTableHeadData;
			} else {
				return false;
			}
		}

		var iColumnWidthTotal = 0;
		var iColumns = 0;

		if(this.options.row_sortable == 1) {
			iColumnWidthTotal += 26;
			iColumns++;
		}

		if(this.multipleSelectionActive) {
			iColumnWidthTotal += 28;
			iColumns++;
		}

		// Fenster Breite auslesen
		var iTotalWidth = $j('.box-body').width();

		var oDivBody = $('divBody_' + this.hash);
		if(oDivBody) {
			iTotalWidth = oDivBody.getWidth();
		}

		// Korrektur wg. Scrollbalken
		iTotalWidth = iTotalWidth - this.iScrollBarWidth;

		// Rahmenkorrektur
		iTotalWidth -= 1;

		var iResizeColumns = 0;

		// Vorgegebene Breiten aller Spalten zusammenzählen
		aData.each(function(aHead) {

			// Wenn Titel größer als Breite ist dann Breite erweitern
			var iAdditionalWidth = this.calculateAdditionalWidth(aHead);

			iColumnWidthTotal += aHead.width + iAdditionalWidth;
			// Veränderbare Spalten zählen
			if(
				aHead.width_resize == true ||
				aHead.width_resize == 'true' ||
				aHead.width_resize == 1
			) {
				iResizeColumns++;
			}
			iColumns++;
		}.bind(this));

		var iOpenWidthEachResizeColumn = 0;
		var iOpenWidthEachColumn = 0;
		var iOpenWidth = iTotalWidth - iColumnWidthTotal;

		// Wenn noch Platz ist, dann errechne wieviel den Spalten dazugegeben
		// werden muss
		if(
			iColumnWidthTotal < iTotalWidth &&
			iResizeColumns > 0
		) {
			iOpenWidthEachResizeColumn = iOpenWidth / iResizeColumns;
		} else if(iColumnWidthTotal < iTotalWidth) {
			iOpenWidthEachColumn = iOpenWidth / iColumns;
		}

		iTotalWidth = iColumnWidthTotal;
		if(iOpenWidth > 0) {
			iTotalWidth -= iOpenWidth;
		}

		this.aCalculatedColumnWidths = [];
		this.aCalculatedColumnWidths['iOpenWidthEachResizeColumn'] = iOpenWidthEachResizeColumn;
		this.aCalculatedColumnWidths['iOpenWidthEachColumn'] = iOpenWidthEachColumn;
		this.aCalculatedColumnWidths['iTotalWidth'] = iTotalWidth;

		return this.aCalculatedColumnWidths;

	},

	calculateAdditionalWidth: function(aHead) {

		var iCharLength = 8; // Breite pro Buchstabe (Monospacing font)
		var iAdditionalWidthSpacer = 20; // Breite die noch hinzugefügt werden soll um z.B. Sortierungspfeil sehen zu können

		var iAdditionalWidth = 0;
		/* Die Berechnung ist erstmal auskommentiert da wir hier nachher eine performantere Lösung finden müssen
		 if((aHead.title.length * iCharLength) > aHead.width){
		 iAdditionalWidth = aHead.title.length * iCharLength + iAdditionalWidthSpacer - aHead.width;
		 }
		 */
		return iAdditionalWidth;
	},

	/**
	 * Erzeugt die obere Tabelle
	 */
	createTableHead: function(aData, bHasColumnGroup, bColumnFlexibility) {

		if(typeof bColumnFlexibility == 'undefined') {
			bColumnFlexibility = true;
		}

		if(aData.length <= 0) {
			return false;
		}

		if(!this.aTableHeadData) {
			this.aTableHeadData = [];
		}

		this.aTableHeadData = aData;

		var oDiv = $('guiTableHead' + '_' + this.hash);

		if(oDiv) {
			oDiv.update('');
			oDiv.style.width = '100%';
		}

		var oTable = new Element('table');
		oTable.style.width = this.aCalculatedColumnWidths['iTotalWidth']+'px';

		var oColgroup = new Element('colgroup');
		var oTableHead = new Element('thead');
		var oTr = new Element('tr');

		if(bHasColumnGroup) {

			var oTrGroup = new Element('tr');

			// Zeilen sotierbar
			if(this.options.row_sortable == 1) {
				var oTh = new Element('th');
				oTrGroup.appendChild(oTh);
			}

			if(this.multipleSelectionActive) {
				var oTh = new Element('th');
				oTrGroup.appendChild(oTh);
			}

			var iColspan = 0;
			aData.each(function(aHead, iIndex) {
				iColspan++;
				if(
					aHead.group &&
					(
						(
							aData[iIndex + 1] &&
								aHead.group != aData[iIndex + 1].group
							) ||
							(
								!aData[iIndex + 1]
								)
						)
				) {
					var oTh = new Element('th');

					if(
						aHead.group_small &&
						aHead.group_small == true
					) {
						oTh.addClassName('small');
					}

					if(iColspan > 1) {
						oTh.colSpan = iColspan;
					}
					oTh.update(aHead.group);
					oTh.title = aHead.group;
					oTrGroup.appendChild(oTh);
					iColspan = 0;
				} else if(!aHead.group) {
					var oTh = new Element('th');
					oTrGroup.appendChild(oTh);
					iColspan = 0;
				}
			});

		}

		if(bColumnFlexibility) {
			Event.observe(oTableHead, 'contextmenu', function(e) {
				this.flexmenue(e);
				e.stop();
				return false;
			}.bind(this));
		}

		// Zeilen sotierbar
		if(this.options.row_sortable == 1) {
			var oCol = new Element('col');
			var oTh = new Element('th');
			oCol.className = 'guiHeadFirstColumn';
			oTh.className = 'guiHeadFirstColumn sortasc';
			oColgroup.appendChild(oCol);
			oTr.appendChild(oTh);
		}

		if(this.multipleSelectionActive) {

			// ++++++++++++++++++++++++
			// Erste Spalte erzeugen um die Zeile gut anfassen zu können
			// (problem bei inplace umgehen + sortierung vereinfachen )
			// ++++++++++++++++++++++++
			var oCol = new Element('col');
			var oTh = new Element('th');
			oCol.className = 'guiHeadFirstColumn';
			oTh.className = 'guiHeadFirstColumn';
			// var oChangeToCheckbox = new Element('img');
			// oChangeToCheckbox.id = 'guiMultipleImg_'+this.hash;

			if(this.multipleSelection == 1) {
				var oCheckbox = new Element('input');
				oCheckbox.type = 'checkbox';
				oTh.appendChild(oCheckbox);
				Event.observe(oCheckbox, 'click', function(e) {
					this.selectAllRows(oCheckbox);
				}.bind(this));
				/*
				 * oChangeToCheckbox.src = '/admin/media/table.png';
				 * oChangeToCheckbox.title = this.getTranslation('one selection');
				 * oChangeToCheckbox.alt = this.getTranslation('one selection');
				 */
			} else {
				/*
				 * oChangeToCheckbox.src = '/admin/media/table_multiple.png';
				 * oChangeToCheckbox.title = this.getTranslation('multiple
				 * selection'); oChangeToCheckbox.alt =
				 * this.getTranslation('multiple selection');
				 */
			}
			// Mehrfachauswahl JA/NEIN switchen
			/*
			 * Event.observe(oChangeToCheckbox, 'click', function(e) {
			 * this.changeMultipleSelection(e, oChangeToCheckbox);
			 * }.bind(this)); oTh.appendChild(oChangeToCheckbox);
			 */

			oColgroup.appendChild(oCol);
			oTr.appendChild(oTh);

			// Breite der ersten (auto.) spalte der gesammtbreite hinzuaddieren

			// ++++++++++++++++++++++++
		}

		aData.each(function(aHead) {

			var sColumnId = aHead.select_column;
			if(!sColumnId || sColumnId == "") {
				sColumnId = aHead.db_column;
			}
			sColumnId = sColumnId + '_' + this.hash;
			this.columnElements[sColumnId] = aHead;

			var oCol = new Element('col');
			var oTh = new Element('th');

			oTh.id = sColumnId;

			if(aHead.small && aHead.small == true) {
				oTh.addClassName('small');
			}

			oTh.update(aHead.title);

			// Spalten sotierbar machen
			if(
				this.options.column_sortable == 1 &&
				aHead.sortable == 1
			) {
				oTh.addClassName('pointer');

				if(aHead.css_class) {
					oTh.addClassName(aHead.css_class);
				}

				Event.observe(oTh, 'click', function(e) {
					this.prepareColumnSort(e, oTh);
				}.bind(this));
			}

			if(
				aHead.mouseover_title &&
				aHead.mouseover_title != ""
			) {
				oTh.title = aHead.mouseover_title;
			} else {
				oTh.title = aHead.title;
			}

			oColgroup.appendChild(oCol);
			oTr.appendChild(oTh);

		}.bind(this));

		this.scrollTableColgroup = oColgroup.clone(true);

		oTable.className = "guiTableHead";

		oTable.appendChild(oColgroup);
		if(bHasColumnGroup) {
			oTableHead.appendChild(oTrGroup);
		}
		oTableHead.appendChild(oTr);
		oTable.appendChild(oTableHead);

		if(oDiv) {
			oDiv.appendChild(oTable);
		}

		this.testBoxModel();

		return true;

	},

	/**
	 * Erzeugt die untere Tabelle
	 */
	createTableBody: function(aTableData) {

		var aData = aTableData.body;

		if(aTableData.selectedRows) {
			var aSelectedRows = aTableData.selectedRows;
		}

		var oDiv = $('guiScrollBody' + '_' + this.hash);
		if(oDiv) {

			// Scrollposition merken
			var iScrollLeft = $j(oDiv).scrollLeft();

			oDiv.update('');
			Event.observe(oDiv, 'scroll', function(e) {
				this.calculateHeaderPosition(e);
				this.calculateSumPosition(e);
			}.bind(this));
		} else {
			// Kein DIV -> keine Tabelle
			console.debug('Container "guiScrollBody_' + this.hash+'" not available');
			return;
		}

		var oTable = new Element('table');
		oTable.style.width = this.aCalculatedColumnWidths['iTotalWidth']+'px';

		var oColgroup = this.scrollTableColgroup;
		var oTableBody = new Element('tbody');
		oTableBody.className = "guiTableTBody";
		oTable.appendChild(oColgroup);
		oTable.className = "guiTableBody";
		oTable.id = 'guiTableBody' + '_' + this.hash;

		var oTrTemplate = new Element('tr');
		var oTdTemplate = new Element('td');

		var oFirstBody = {};
		var iTemp = 0;

		oTrTemplate.className = 'guiBodyRow sc_linear_hover';

		// Zeilen sotierbar
		if(
			this.options.row_sortable == 1
		) {
			var oTd = oTdTemplate.clone();
			oTd.className = 'guiBodyFirstColumn';
			iTemp++;
			oTrTemplate.appendChild(oTd);
		}

		if(this.multipleSelectionActive) {
			var oTd = oTdTemplate.clone();
			oTd.className = 'guiBodyFirstColumn';
			iTemp++;
			oTrTemplate.appendChild(oTd);
		}

		aData.each(function(aBodyList) {
			aBodyList.items.each(function(oTemp) {
				var oTd = oTdTemplate.clone();
				oTd.className = 'guiBodyColumn';
				oTrTemplate.appendChild(oTd);
				iTemp++;
			});
			throw $break;
		});

		var r = 0;
		var previousLineColumns = new Map()

		aData.each(function(aBodyList) {

			var oTr = oTrTemplate.clone(true);
			// row_ wird für das sortable benötigt..
			oTr.id = 'row_' + this.hash + '_' + aBodyList.id;
			if(aBodyList['style'] && aBodyList['style'] != '') {
				oTr.writeAttribute('style', aBodyList['style']);
			}

			/*if(this.bDebugMode){
			 var iTempId = 0;
			 if(aBodyList.id){
			 iTempId = aBodyList.id;
			 }
			 Event.observe(oTr, 'contextmenu', function(e) {
			 this.contextmenu(e, 'ID: '+iTempId);
			 }.bind(this));

			 }*/

			var oSelectRowEvent = function(e) {
				this.selectRow(e, oTr, true);
				return true;
			}.bind(this);

			if(aBodyList.contextmenu) {

				if(typeof $j == 'undefined') {
					// throw wird neuerdings abgefangen…
					alert("GUI2 Contextmenu requires jQuery to be included!");
				}

				var aContextMenu = aBodyList.contextmenu;

				iRowId = null;
				if(typeof aBodyList.id != 'undefined') {
					iRowId = aBodyList.id;

					// ID in Kontextmenü einfügen
					if(this.bDebugMode) {

						if(
							aContextMenu[0].type &&
								aContextMenu[0].type == 'id'
							) {
							aContextMenu.shift();
						}

						aContextMenu.unshift({
							'key': 'id',
							'name': 'ID: ' + iRowId,
							'type': 'id',
							'disabled': true
						});

					}
				}

				var aContextMenuStructure = this.createContextMenu(aContextMenu, oTr);
				$j(oTr).contextMenu(aContextMenuStructure, {
					theme: 'gui',
					showTransition: 'fadeIn',
					hideTransition: 'fadeOut',
					useIframe: false,
					showSpeed: 250,
					hideSpeed: 250
				});

				if(this.options.rows_clickable == 1) {
					Event.observe(oTr, 'contextmenu', oSelectRowEvent);
				}

			}

			if(this.options.rows_clickable == 1) {

				Event.observe(oTr, 'click', oSelectRowEvent);

				// Doppelklick...
				if(this.oDblClickElement != null) {
					Event.observe(oTr, 'dblclick', function(e) {
						this.dblclickWrapper();
					}.bind(this));
				}

				// Falls die ID im System als Selectiert gemerkt wurde dann
				// selectiere diese Zeile wieder erneut
				if(
					this.selectedRowId &&
					this.selectedRowId[aBodyList.id] &&
					!aSelectedRows // Falls das gesetzt ist, werden die Rows nachher automatisch gesetzt
				) {
					oTr.className = this.selectedRowClassName;
				}
			}

			var oLastTd = null;
			var iTemp = 0;

			// Zeilen sotierbar
			if(
				this.options.row_sortable == 1
			) {

				var oTd = oTr.down('td');

				if (oTr.style.borderColor) {
					oTd.style.borderColor = oTr.style.borderColor;
					if (previousLineColumns.has('sortable')) {
						previousLineColumns.get('sortable').style.borderBottomColor = oTr.style.borderColor;
					}
				}

				var oImg = new Element('div');
				oImg.addClassName('jqueryIcon jqueryIconSortable');

				oTd.appendChild(oImg);

				previousLineColumns.set('sortable', oTd)
				oLastTd = oTd;
			}

			if(this.multipleSelectionActive) {
				// ++++++++++++++++++++++++
				// Erste Spalte erzeugen
				// ++++++++++++++++++++++++

				if(!oLastTd) {
					var oTd = oTr.down('td');
				} else {
					var oTd = oLastTd.next();
				}
				// iTemp++;

				if (oTr.style.borderColor) {
					oTd.style.borderColor = oTr.style.borderColor;
					if (previousLineColumns.has('selection')) {
						previousLineColumns.get('selection').style.borderBottomColor = oTr.style.borderColor;
					}
				}

				if(!aBodyList.multiple_checkbox_hide) {

					var oCheckbox = new Element('input');
					oCheckbox.type = 'checkbox';
					oCheckbox.className = 'multiple_checkbox';

					// ID für die linke Checkbox in der Tabelle
					if(aBodyList.multiple_checkbox_id) {
						oCheckbox.id = aBodyList.multiple_checkbox_id;
					}
					if(aBodyList.multiple_checkbox_name) {
						oCheckbox.name = aBodyList.multiple_checkbox_name;
					}

					// Falls die ID im System als Selectiert gemerkt wurde dann
					// selectiere diese Zeile wieder erneut
					if(
						this.selectedRowId &&
						this.selectedRowId.indexOf(aBodyList.id) !== -1 &&
						!aSelectedRows // Falls das gesetzt ist, werden die Rows nachher automatisch gesetzt
					) {
						oCheckbox.checked = true;
					}

					oTd.appendChild(oCheckbox);
				}

				previousLineColumns.set('selection', oTd)
				oLastTd = oTd;
				// ++++++++++++++++++++++++
			}
			var c = 0;

			aBodyList.items.each(function(aBody) {

				if(oLastTd == null) {
					var oTd = oTr.down('td');
				} else {
					var oTd = oLastTd.next();
				}
				// iTemp++;

				// oTd.id = '';
				if(aBody.text == '') {
					var sText = '&nbsp;';
				} else {
					var sText = aBody.text;
				}

				if(aBody.css_overflow) {
					var oDivOverflow = new Element('div');
					oDivOverflow.addClassName('guiBodyOverflow');
					oDivOverflow.update(sText);
					oTd.appendChild(oDivOverflow);
				} else {
					oTd.update(sText);
				}

				// Tooltip einblenden
				if(aBody.title) {

					var sTitle = '';
					var bTooltip = false;
					var iTitleType = 0; // MVC oder Normaler Tooltipp

					// MVC Tooltip Daten
					var aValues = [];
					var sPath = '';
					var iWidth = 0;

					if(typeof aBody.title == 'string') {
						sTitle = aBody.title;
					} else if(
						typeof aBody.title == 'object' &&
						aBody.title.data
					) {

						if(
							aBody.title.data.tooltip == 'wdmvc' &&
							aBody.title.data.values &&
							aBody.title.data.path
						) {
							bTooltip = true;
							aValues = aBody.title.data.values;
							iTitleType = 2;
							sPath = aBody.title.data.path;
							if(aBody.title.data.tooltip_width) {
								iWidth = aBody.title.data.tooltip_width;
							}
						} else if(
							aBody.title.data.content
						) {
							bTooltip = aBody.title.data.tooltip;
							sTitle = aBody.title.data.content;
							iTitleType = 1;
						}

					}

					if(bTooltip) {

						var sTooltipId = 'tooltip_' + r + '_' + c;
						oTd.id = sTooltipId;

						// Es gibt den "normalen" Tooltip der statischen Text anzeigt
						// UND es gibt den V5 MVC Tooltip, der einen Request abschickt zum laden des Inhalts
						if(iTitleType == 1) {
							this.aTooltips[sTooltipId] = sTitle;
							Event.observe(oTd, 'mousemove', function(e) {
								this.showTooltip(sTooltipId, e);
							}.bind(this));
						} else if(iTitleType == 2) {

							// Leeren Tooltip anzeigen (Inhalt wird erst bei mouseover geladen)
							this.aTooltips[sTooltipId] = '<img src="/admin/media/indicator.gif" />';

							Event.observe(oTd, 'mousemove', function(e) {
								this.showTooltip(sTooltipId, e, iWidth);
								// Nur beim erstenmal darf der Content neu geladen werden! (sonst Endlosschleife)
								var aResult = this.aTooltips[sTooltipId].match(/indicator.gif/);
								if(aResult) {
									this.loadTooltip(sTooltipId, aValues, sPath, e);
								}
							}.bind(this));

						}

						// Ausbeledbar müssen alle Tooltips sein
						Event.observe(oTd, 'mouseout', function(e) {
							this.hideTooltip(sTooltipId, e);
						}.bind(this));
					} else {
						oTd.title = sTitle;
					}

				}

				// Wenn es ein inplace editor ist
				if(aBody.ie && aBody.ie == 1) {
					this.prepareInplaceEditor(oTd, aBody);
				} else {
					// wenn kein inplace editor makiere die zeile beim anklicken
					// Event.observe(oTd, 'click', function(e) {
					// this.selectRow(e, oTr, true);
					// }.bind(this));
				}

				// wenn ein background gesetzt ist...
				if(aBody['style'] && aBody['style'] != '') {
					oTd.writeAttribute('style', aBody['style']);
					if (oTd.style.borderColor) {
						oTd.setAttribute('data-coloring', 1);
					}
				}

				if (oTr.style.borderColor && !oTd.style.borderColor) {
					oTd.style.borderColor = oTr.style.borderColor;
				}

				if (oTd.style.borderColor) {
					if (oLastTd && !oLastTd.hasAttribute('data-coloring')) {
						oLastTd.style.borderRightColor = oTd.style.borderColor;
					}
					if (previousLineColumns.has(c)) {
						var previousLineTd = previousLineColumns.get(c)
						previousLineTd.style.borderBottomColor = oTd.style.borderColor;
					}
				}

				// wenn ein Event gesetzt wurde
				if(
					aBody['event'] &&
						aBody['event'] != '' &&
						aBody['eventfunction'] &&
						aBody['eventfunction']['name'] &&
						aBody['eventfunction']['name'] != ''
					) {
					var sArgs = '';
					// prüfen ob es Argumente gibt
					if(aBody['eventfunction']['args']) {
						sArgs = "'" + aBody['eventfunction']['args'].join("', '") + "', ";
					}

					sArgs += aBodyList.id;

					Event.observe(oTd, aBody['event'], function(e) {
						// Funktion muss per eval aufgerufen werden
						var sFunctionCall = "this." + aBody['eventfunction']['name'] + "(" + sArgs + ")";
						eval(sFunctionCall);
					}.bind(this));
				}

				// Text Align
				// wenn es ein betrag ist => rechts ausrichten
				if(aBody['ta'] && aBody['ta'] != '') {
					oTd.style.textAlign = aBody['ta'];
				}

				previousLineColumns.set(c, oTd)
				oLastTd = oTd;

				c++;
			}.bind(this));

			oTableBody.appendChild(oTr);

			r++;
		}.bind(this));

		// Sortierbar machen
		if(
			this.options.row_sortable == 1 &&
			aTableData.row_sortable !== false
		) {
			oTableBody.style.position = "static";
			oTableBody.id = 'sortablebody' + '_' + this.hash;
			Position.includeScrollOffsets = true;
			//oTable.style.borderCollapse = 'separate';
			//oTableBody.style.borderCollapse = 'separate';
			// Die alte Definition funktioniert aus irgendeinem Grund seit FF 19 nicht mehr R-#4477
			$j(oTableBody).sortable({
				items: 'tr',
				scroll: oDiv,
				constraint: '#divBody_' + this.hash,
				containment: 'parent',
				helper: function(e, oTr) {
					// Diese Funktion ist dafür da, dass die Zeile ihre Breite beim Ziehen behält
					var oOriginalTrs = oTr.children();
					var oTrClone = oTr.clone();
					oTrClone.children().each(function(index) {
						$j(this).width(oOriginalTrs.eq(index).width())
					});
					return oTrClone;
				},
				update: function(oEvent, oUI) {
					var iSelectedId = oUI.item[0].id.replace('row_' + this.hash + '_', '');
					// Die GUI erwartet ganz merkwürdige Daten…
					var sSerialized = $j(oTableBody).sortable('serialize', {key: 'sortablebody_' + this.hash + '[]'});
					this.request('&task=saveSort&' + sSerialized + '&selected_id=' + iSelectedId);
				}.bind(this)
			});
		}

		oTable.appendChild(oTableBody);
		oDiv.appendChild(oTable);

		// resize hier, falls der request etwas dauert,
		// wird der normale resize gemacht bevor die tabelle erstellt wurde
		this.resize();
		//if(aData.length == 1 && oTr){
		//	this.selectRow(false, oTr, true, true);
		//}

		// Wieder an die vorherige Position scrollen
		$j('#guiScrollBody_' + this.hash).scrollLeft(iScrollLeft);

	},

	// Läd den Inhalt eines Tooltips "on the fly" nach mithilfe des V5 MVC-Controllers
	loadTooltip: function(sTooltipId, aValues, sPath, e) {

		var sParam = '&task=getTooltip';

		// Alle Daten mitschicken
		$H(aValues).each(function(oArray) {
			if(
				(typeof oArray.value != 'undefined') &&
					(typeof oArray.value != 'function')
				) {
				sParam += '&values[' + oArray.key + ']=' + oArray.value;
			}

		});

		sParam += '&tooltip_id=' + sTooltipId;

		this.uniqueRequest(sParam, sPath, '', false, 0, false);
	},

	showTooltip: function(sTooltipId, e, iToolTipWidth) {

		var sId = 'div_' + sTooltipId;

		var oDiv = $(sId);

		if(!oDiv) {
			var oDiv = new Element('div');
			oDiv.id = sId;
			oDiv.className = 'divTooltip';
			document.body.insert({bottom: oDiv});
		}

		oDiv.update(this.aTooltips[sTooltipId]);

		var iX = Event.pointerX(e) + 15;
		var iY = Event.pointerY(e) + 10;

		if(
			!isNaN(iX) &&
			!isNaN(iY)
		) {
			var iDivHeight = oDiv.getHeight();
			var iDivWidth = oDiv.getWidth();
			var oDocumentElement = document.documentElement;
			var iHeight = self.innerHeight || (oDocumentElement && oDocumentElement.clientHeight) || document.body.clientHeight || document.body.offsetHeight;
			var iWidth = self.innerWidth || (oDocumentElement && oDocumentElement.clientWidth) || document.body.clientWidth || document.body.offsetWidth;

			if(iX + iDivWidth > iWidth) {
				if(iX > iDivWidth) {
					iX = iX - iDivWidth - 20;
				} else {
					iX = 10;
				}

			}
			if(iY + iDivHeight > iHeight) {
				if(iY > iDivHeight) {
					iY = iY - iDivHeight - 10;
				} else {
					iY = 10;
				}
			}

			if(
				iToolTipWidth === undefined ||
				iToolTipWidth === 0
			) {
				oDiv.setStyle({
					left: iX + 'px',
					top: iY + 'px'
				});
			} else {
				oDiv.setStyle({
					left: iX + 'px',
					top: iY + 'px',
					width: iToolTipWidth + 'px'
				});
			}

		}
		// das div muss pos. werden da nach einem fireevent keine pos. ermittelt werden kann
		// daher immer generieren und pos sofern x/y vorhanden
		// und in diesen fällen nur ausblenden anstatt gar nicht generieren
		if(
			!this.aTooltips[sTooltipId] ||
			typeof this.aTooltips[sTooltipId] != 'string' ||
			this.aTooltips[sTooltipId].empty()
		) {
			oDiv.hide();
		} else {
			oDiv.show();
		}

	},

	hideTooltip: function(sTooltipId) {
		if($('div_' + sTooltipId)) {
			$('div_' + sTooltipId).hide();
		}
	},

	/**
	 * Erzeugt die summen Tabelle
	 */
	createTableSum: function(aData) {

		if(aData.length <= 0) {
			return false;
		}

		if(!this.aTableSumData) {
			this.aTableSumData = [];
		}

		this.aTableSumData = aData;

		var oTable = new Element('table');
		var oTableSum = new Element('tbody');
		oTableSum.className = "guiTableTSum";
		oTable.className = "table guiTableSum";
		oTable.id = 'guiTableSum' + '_' + this.hash;

		var oDiv = $('guiTableSum' + '_' + this.hash);
		oDiv.className = "guiTableSum";
		oDiv.show();
		oDiv.update('');

		oTable.className = "table guiTableSum";

		oTable.appendChild(this.scrollTableColgroup.clone(true));

		aData.each(function(aSumData) {

			var oTr = new Element('tr');

			// Wenn die Liste Mehrfachauswahl erlaubt dann aktiviere es
			// standartmässig
			if(
				this.options &&
					this.options.multiple_selection == 1
				) {
				this.multipleSelectionActive = 1;
				this.multipleSelection = 1;
			}

			if(this.multipleSelectionActive) {

				// ++++++++++++++++++++++++
				// Erste Spalte erzeugen um die Zeile gut anfassen zu können
				// (problem bei inplace umgehen + sortierung vereinfachen )
				// ++++++++++++++++++++++++
				var oTh = new Element('th');
				oTh.className = 'guiSumFirstColumn';
				// var oChangeToCheckbox = new Element('img');
				// oChangeToCheckbox.id = 'guiMultipleImg_'+this.hash;
				oTh.update('&nbsp;');
				oTr.appendChild(oTh);

				// ++++++++++++++++++++++++
			}

			aSumData.each(function(sSum) {

				var oTh = new Element('th');
				oTh.update(sSum);

				// wenn es ein betrag ist => rechts ausrichten
				if(sSum != "") {
					oTh.style.textAlign = 'right';
				}

				oTr.appendChild(oTh);

			}.bind(this));

			oTableSum.appendChild(oTr);
		}.bind(this));

		oTable.appendChild(oTableSum);

		oDiv.appendChild(oTable);

		return true;
	},

	/**
	 * Erzeugt die untere Tabelle
	 */
	createTableBars: function(aConfigData, sPosition) {

		var aData = aConfigData['bar_' + sPosition];

		if(sPosition == 'top') {
			var oDiv = $('guiTableBars' + '_' + this.hash);
		} else {
			var oDiv = $('guiTableBarsBottom' + '_' + this.hash);
		}
		if(!oDiv) {
			return false;
		}
		oDiv.update('');

		// Wenn keine Elemente vorhanden
		if(aData.length <= 0) {
			/*if(sPosition == 'bottom') {
				oDiv.up().hide();
			}*/
			return false;
		}

		// Filter Daten löschen da sie unten neu geschrieben werden
		aData.each(function(aBar, iBarIndex) {
			if(
				aBar.visible &&
				(
					aBar.visible == 1 ||
					aBar.visible == true
				)
			) {
				// Leisten Div
				var oBar = new Element('div');
				oBar.style.width = aBar.width;
				oBar.className = 'divToolbar form-inline';
				if(aBar.class_name != '') {
					oBar.addClassName(aBar.class_name);
				}
				oBar.id = 'divToolbar_' + this.iBarCount;
				// Leisten Elemente durchlaufen
				var iElements = 0;

				var oElementsOuterDiv = new Element('div', {
					'class': 'grow'
				});

				var oElementsDiv = new Element('div', {
					'class': 'elements-container'
				});

				var oToggleDiv = new Element('div', {
					'class': 'flex-none'
				});

				var oToggleIcon = this.createBarToogleIcon();
				oToggleIcon.hide();
				oToggleDiv.appendChild(oToggleIcon);

				//aBar.show_if_empty = true
				//aBar.bar_elements = null

				if(
					aBar.height &&
					aBar.height != ''
				) {
					oBar.setAttribute('data-height', aBar.height);
				}

				/*if(aBar.data) {
					$j.each(aBar.data, function(key, value) {
						oBar.setAttribute('data-'+key, value);
					});
				}*/

				if(aBar.show_if_empty) {
					iElements = 1;
				}

				if (
					iBarIndex === 0 && sPosition === 'top' &&
					(this.options.info_icon_edit_mode || this.options.help_url)
				) {
					var oHelpBtn = new Element('a');
					oHelpBtn.className = 'gui-help-btn';
					oHelpBtn.update('<i class="fa fa-question"></i>' + this.getTranslation('help'));
					Event.observe(oHelpBtn, 'click', this.openHelp.bind(this));
					if (!this.options.help_url) {
						oHelpBtn.className += ' inactive';
					}
					oElementsDiv.appendChild(oHelpBtn);
				}

				if(aBar.bar_elements) {
					aBar.bar_elements.each(function(aElement) {

						if(
							typeof aElement.visibility !== 'undefined' &&
							aElement.visibility === false
						) {
							throw $break;
						}

						// Wenn es ein Icon ist
						if(aElement.element_type == 'icon') {
							var oIconDiv = this.createBarIcon(aElement);
							if(oIconDiv !== false) {
								oElementsDiv.appendChild(oIconDiv);
								iElements++;
							}
						} else if(aElement.element_type == 'filter') {
							var oFilterDiv = this.createBarFilter(aElement);
							oElementsDiv.appendChild(oFilterDiv);
							iElements++;
						} else if(aElement.element_type == 'timefilter') {
							var oTimeFilterDiv = this.createBarTimeFilter(aElement);
							oElementsDiv.appendChild(oTimeFilterDiv);
							iElements++;
						} else if(aElement.element_type == 'html') {
							var oHtmlDiv = this.createBarHtml(aElement);
							oElementsDiv.appendChild(oHtmlDiv);
							iElements++;
						} else if(aElement.element_type == 'break') {

							var sHtml = '' + aElement.html;
							oElementsDiv.insert({
								bottom: sHtml
							});
							iElements++;

						} else if(aElement.element_type == 'loading_indicator') {

							this.loadingIndicator = aElement.id + '_' + this.hash;

							aElement.html = aElement.html.replace(/loading_indicator/, this.loadingIndicator);

							var sHtml = '' + aElement.html;
							oElementsDiv.insert({
								bottom: sHtml
							});

							iElements++;

						} else if(aElement.element_type == 'seperator') {
							var oDiv = new Element('div');
							oDiv.className = 'divToolbarSeparator';
							oDiv.update(aElement.html);
							oElementsDiv.appendChild(oDiv);
							iElements++;

						} else if(aElement.element_type == 'pagination') {

							var oDivPagination = this.createPagination(aElement);

							oElementsDiv.appendChild(oDivPagination);

							iElements++;
						} else if(aElement.element_type == 'label_group') {
                            oElementsDiv.insert({
								bottom: aElement.html
							});
							iElements++;
						} else {
							if("undefined" == typeof aElement['visible'] || aElement['visible'] == 1){
								oElementsDiv.insert({
									bottom: aElement.html
								});
								iElements++;
							}
						}
					}.bind(this));
				}

				oElementsOuterDiv.appendChild(oElementsDiv);
				oBar.appendChild(oElementsOuterDiv);
				oBar.appendChild(oToggleDiv);

				if(iElements > 0) {

					if(!this.aBars) {
						this.aBars = [];
					}
					if(!this.aBarToggleStatus) {
						this.aBarToggleStatus = [];
						this.aBarToggleStatus = false;
					}

					this.aBars[this.aBars.length] = oBar;

					this.iBarCount++;
					oDiv.appendChild(oBar);

					if (aBar.data?.vue) {
						window.__FIDELO__.Gui2.createVueApp('GuiFilterBar', oBar, this);
					}
				}
			}
		}.bind(this));

		if(sPosition == 'top') {
			this.updateIconsCallback(aConfigData);
		}

		// Alle benutzten Calender erzeugen und das Array danach leeren

		this.executeCalendars();

	},

	createPagination: function(aElement) {

		var oDivPagination;
		var oDivPagination2;
		var oSpanTemp;
		var oDivTemp;

		this.sPaginationId = aElement.id + '_' + this.hash;

		oDivPagination = new Element('div');
		oDivPagination.id = this.sPaginationId + '_result_count';
		oDivPagination.addClassName('divPagination');

		if(aElement.only_pagecount == 0) {
			oDivPagination2 = new Element('div');
			oDivPagination2.className = 'divToolbarIcon';

			var oPaginationBtnStart = new Element('i');
			oPaginationBtnStart.id = this.sPaginationId + '_pagination_btn_start';
			oPaginationBtnStart.className = 'fa fa-angle-double-left';
			oDivPagination2.appendChild(oPaginationBtnStart);

			oDivPagination.appendChild(oDivPagination2);

			oDivPagination2 = new Element('div');
			oDivPagination2.className = 'divToolbarIcon';

			var oPaginationBtnBack = new Element('i');
			oPaginationBtnBack.id = this.sPaginationId + '_pagination_btn_back';
			oPaginationBtnBack.className = 'fa fa-angle-left';
			oDivPagination2.appendChild(oPaginationBtnBack);

			oDivPagination.appendChild(oDivPagination2);

		}

		var oDivPaginationItems = new Element('div');
		oDivPaginationItems.addClassName('divToolbarLabel');

		var sHTML;

		sHTML = '';
		oDivPaginationItems.insert({bottom: sHTML});

		oSpanTemp = new Element('span');
		oSpanTemp.id = this.sPaginationId + '_pagination_offset';
		oSpanTemp.innerHTML = '0';
		oDivPaginationItems.appendChild(oSpanTemp);

		sHTML = '&nbsp;' + this.getTranslation('pagination_to') + '&nbsp;';
		oDivPaginationItems.insert({bottom: sHTML});

		oSpanTemp = new Element('span');
		oSpanTemp.id = this.sPaginationId + '_pagination_end';
		oSpanTemp.innerHTML = '0';
		oDivPaginationItems.appendChild(oSpanTemp);

		sHTML = '&nbsp;' + this.getTranslation('pagination_total') + '&nbsp;';
		oDivPaginationItems.insert({bottom: sHTML});

		oSpanTemp = new Element('span');
		oSpanTemp.id = this.sPaginationId + '_pagination_total';
		oSpanTemp.innerHTML = '0';
		oDivPaginationItems.appendChild(oSpanTemp);

		oDivPagination.appendChild(oDivPaginationItems);

		if(aElement.only_pagecount == 0) {
			oDivPagination2 = new Element('div');
			oDivPagination2.className = 'divToolbarIcon';

			var oPaginationBtnNext = new Element('i');
			oPaginationBtnNext.id = this.sPaginationId + '_pagination_btn_next';
			oPaginationBtnNext.className = 'fa fa-angle-right';
			oDivPagination2.appendChild(oPaginationBtnNext);

			oDivPagination.appendChild(oDivPagination2);

			oDivPagination2 = new Element('div');
			oDivPagination2.className = 'divToolbarIcon';

			var oPaginationBtnEnd = new Element('i');
			oPaginationBtnEnd.id = this.sPaginationId + '_pagination_btn_end';
			oPaginationBtnEnd.className = 'fa fa-angle-double-right';
			oDivPagination2.appendChild(oPaginationBtnEnd);

			oDivPagination.appendChild(oDivPagination2);

			// Events

			Event.observe(oPaginationBtnStart.closest('.divToolbarIcon'), 'click', function(e) {
				this.setPagination('start');
			}.bind(this));

			Event.observe(oPaginationBtnBack.closest('.divToolbarIcon'), 'click', function(e) {
				this.setPagination('back');
			}.bind(this));

			Event.observe(oPaginationBtnNext.closest('.divToolbarIcon'), 'click', function(e) {
				this.setPagination('next');
			}.bind(this));

			Event.observe(oPaginationBtnEnd.closest('.divToolbarIcon'), 'click', function(e) {
				this.setPagination('end');
			}.bind(this));

			if(aElement.limit_selection == 1) {

				/*var oDivSeperator = new Element('div');
				oDivSeperator.className = "divToolbarSeparator";
				oDivSeperator.update('::');
				oDivPagination.appendChild(oDivSeperator);*/

//				var oLimitSelectionDiv = new Element('div');
//				oLimitSelectionDiv.className = 'guiBarFilter';

				var oLimitSelectionLabel = new Element('div');
				oLimitSelectionLabel.className = 'divToolbarLabel';
				oLimitSelectionLabel.update(this.getTranslation('per_page'));

				var oLimitSelectionContainer = new Element('div');
				oLimitSelectionContainer.className = 'guiBarFilter';
				var oLimitSelection = new Element('select', {id: 'pagination_select_' + this.hash});
				oLimitSelection.className = '';
				oLimitSelectionContainer.appendChild(oLimitSelection);

				oDivPagination.appendChild(oLimitSelectionLabel);
				oDivPagination.appendChild(oLimitSelectionContainer);

//				oDivPagination.appendChild(oLimitSelectionDiv);

				this.updateSelectOptions(oLimitSelection, aElement.limited_selection_options, false);

				if(
					aElement &&
					aElement.limited_selection_options &&
					aElement.limited_selection_options.length &&
					aElement.limited_selection_options.length > 0
				) {

					var bFound = false;

					aElement.limited_selection_options.each(function(aOptionData) {
						if(aOptionData.value == aElement.default_limit) {
							bFound = true;
						}
					});

					if(!bFound) {
						var oOption = new Element('option');
						if(aElement.default_limit != 0) {
							oOption.innerHTML = aElement.default_limit;
						} else {
							oOption.innerHTML = '';
						}
						oOption.value = aElement.default_limit;
						oOption.selected = true;
						oLimitSelection.appendChild(oOption);
					} else {
						oLimitSelection.value = aElement.default_limit;
					}
				}

				// updateValue gibts nicht in allen Browsern!
				if(typeof HTMLSelectElement != 'undefined') {
					oLimitSelection.updateValue(aElement.default_limit);
				} else {
					oLimitSelection.value = aElement.default_limit;
				}

				Element.observe(oLimitSelection, 'change', function(e) {
					//wenn limit verändert wird, dann offset wieder auf 0 stellen
					this.iPaginationOffset = 0;
					this.loadTable(false, this.hash, oLimitSelection.value);
				}.bind(this));

			}

			/*var oDivSeperator = new Element('div');
			oDivSeperator.className = "divToolbarSeparator";
			oDivSeperator.update('::');
			oDivPagination.appendChild(oDivSeperator);*/

		}

		return oDivPagination;

	},

	createBarToogleIcon: function() {

		var oToggleDiv = new Element('div');
		oToggleDiv.className = 'divToolbarToggleIcon';
		oToggleDiv.id = 'toggle_div_' + this.iBarCount;

		var oToggleSpan = new Element('span');
		oToggleSpan.className = 'divToolbarToggleLabel';
		oToggleSpan.update(this.getTranslation('show_more_options'));

		var oToggleImg = new Element('i');
		oToggleImg.title = this.getTranslation('show_more_options');
		oToggleImg.alt = this.getTranslation('show_more_options');
		oToggleImg.id = 'toggle_icon_' + this.iBarCount;
		oToggleImg.className = 'fa fa-angle-down toolbarToggleIcon';

		oToggleDiv.appendChild(oToggleSpan);
		oToggleDiv.appendChild(oToggleImg);

		return oToggleDiv;

	},

	// Klappt die Leiste komplett auf
	toggleFullBar: function(oBar, iFullHeight) {

		var oImg = oBar.down('.toolbarToggleIcon');
		var oSpan = oBar.down('.divToolbarToggleLabel');

		if(!this.aBarToggleStatus) {
			this.aBarToggleStatus = [];
		}

		if(this.aBarToggleStatus[oBar.id]) {
			iFullHeight = this.iToolBarHeight;
			// Bild anpassen
			oImg.removeClassName('fa-angle-up');
			oImg.addClassName('fa-angle-down');
			oImg.title = this.getTranslation('show_more_options');
			oImg.alt = this.getTranslation('show_more_options');
			oSpan.update(this.getTranslation('show_more_options'));
		} else {
			// Bild anpassen
			oImg.removeClassName('fa-angle-down');
			oImg.addClassName('fa-angle-up');
			oImg.title = this.getTranslation('hide_more_options');
			oImg.alt = this.getTranslation('hide_more_options');
			oSpan.update(this.getTranslation('hide_more_options'));
		}

		var oBar = oImg.up('.divToolbar');

		$j(oBar).animate({ height: iFullHeight + 'px' }, 400, function () {
			this.resize()
		}.bind(this));

		// Effekt auffahren
		/*new Effect.Morph(oBar, {
			style: 'height:' + iFullHeight + 'px;', // CSS Properties
			duration: 0.4, // Core Effect properties
			afterUpdate: function callback() {
				this.resize()
			}.bind(this)
		});*/

		if(this.aBarToggleStatus[oBar.id]) {
			this.aBarToggleStatus[oBar.id] = false;
		} else {
			this.aBarToggleStatus[oBar.id] = true;
		}

	},

	/**
	 * Erzeugt ein Div mit dem Filter und allen dazugehörigen HTML Elementen
	 */
	createBarLabel: function(sLabel) {

		var oLabel = new Element('div');
		oLabel.update(sLabel);
		oLabel.className = 'divToolbarLabel';
		return oLabel;

	},

	createBarHtml: function(aElement) {

		var oDiv = new Element('div');
		oDiv.className = 'guiBarElement';
		// Wenn ein Label vorhanden ist
		if(aElement.label != '') {
			var oLabel = this.createBarLabel(aElement.label);
			oDiv.appendChild(oLabel);
		}
		var oDiv2 = new Element('div');
		oDiv2.className = 'divToolbarHtml';
		oDiv2.update(aElement.html);
		oDiv.appendChild(oDiv2);

		return oDiv;
	},

	/**
	 * Erzeugt ein Div mit dem Filter und allen dazugehörigen HTML Elementen
	 */
	createBarTimeFilter: function(aElement) {

		if(this.searchElements == null) {
			this.searchElements = [];
		}

		var oDiv = new Element('div');
		oDiv.className = 'guiBarFilter';

		if(
			aElement.db_from_column &&
			aElement.db_from_column.length > 0
		) {

			// Wenn ein Label vorhanden ist
			if(aElement.label != '') {
				var oLabel = this.createBarLabel(aElement.label);
				oDiv.appendChild(oLabel);
			}

			var oInputFrom;
			var oImgFrom;
			var oInputUntil;
			var oImgUntil;
			var aTemp;

			// FROM
			var oFromGroup = new Element('div');
			oFromGroup.className = 'input-group input-group-sm date';

			var oFromAddon = new Element('div');
			oFromAddon.className = 'input-group-addon';

			var oFromIcon = new Element('i');
			oFromIcon.className = 'fa fa-calendar';

			oFromAddon.appendChild(oFromIcon);

			oFromGroup.appendChild(oFromAddon);

			oInputFrom = new Element('input');
			oInputFrom.type = 'text';
			oInputFrom.style.width = aElement.width;
			oInputFrom.id = aElement.from_id + '_' + this.hash;
			oInputFrom.value = aElement.default_from;
			oInputFrom.className = 'form-control input-sm guiInplaceEditorInput calendar_input';
			oInputFrom.autocomplete = 'off';

			// oImgFrom = new Element('img');
			// oImgFrom.className = 'guiInplaceEditorImg';
			// oImgFrom.src = '/admin/media/calendar.png';
			// oImgFrom.id = aElement.from_id + '_calendar_img';

			oFromGroup.appendChild(oInputFrom);

			oDiv.appendChild(oFromGroup);

			/**
			 * Beim verändern des Browsers die Tabelle anpassen
			 */
			Event.observe(oInputFrom, 'keyup', function(e) {
				this.prepareFilterSearch(e);
			}.bind(this));

			//oInputFrom.writeAttribute('onchange', 'aGUI[\''+this.hash+'\'].prepareFilterSearch();');

			this.prepareCalendar(oInputFrom, oImgFrom, false, true);

			// Wichtig damit die suche auch dieses feld berücksichtigt!
			aTemp = Object.clone(aElement);
			aTemp.id = aElement.from_id;
			this.searchElements[this.searchElements.length] = aTemp;

		}

		// UNTIL
		if(
			aElement.db_until_column &&
			aElement.db_until_column.length > 0
		) {


			// Wenn ein Label vorhanden ist
			if(aElement.label_between != '') {
				var oLabel = this.createBarLabel(aElement.label_between);
				oDiv.appendChild(oLabel);
			}

			var oFromGroup = new Element('div');
			oFromGroup.className = 'input-group input-group-sm date';

			var oFromAddon = new Element('div');
			oFromAddon.className = 'input-group-addon';

			var oFromIcon = new Element('i');
			oFromIcon.className = 'fa fa-calendar';

			oFromAddon.appendChild(oFromIcon);

			oFromGroup.appendChild(oFromAddon);

			oInputUntil = new Element('input');
			oInputUntil.type = 'text';
			oInputUntil.style.width = aElement.width;
			oInputUntil.id = aElement.until_id + '_' + this.hash;
			oInputUntil.value = aElement.default_until;
			oInputUntil.className = 'form-control input-sm guiInplaceEditorInput calendar_input';
			oInputUntil.autocomplete = 'off';

			oFromGroup.appendChild(oInputUntil);
			oDiv.appendChild(oFromGroup);

			/**
			 * Beim verändern des Browsers die Tabelle anpassen
			 */
			Event.observe(oInputUntil, 'keyup', function(e) {
				this.prepareFilterSearch(e);
			}.bind(this));

			// Wichtig damit die suche auch dieses feld berücksichtigt!
			aTemp = Object.clone(aElement);
			aTemp.id = aElement.until_id;
			this.searchElements[this.searchElements.length] = aTemp;

			this.prepareCalendar(oInputUntil, oImgUntil, false, true);

		}

		// text_after
		if(aElement.text_after) {
			var oSpan = new Element('div');
			oSpan.className = 'divToolbarLabel';
			oSpan.update(aElement.text_after);
			oDiv.appendChild(oSpan);
		}

		return oDiv;

	},

	flexmenue: function(e) {

		this.request('&task=loadFlexmenu');

		return false;
	},

	/**
	 * Accordion
	 */
	prepareDialogAccordions: function(aData){

		if(typeof aData.id == 'undefined') {
			dialogId = this.sCurrentDialogId;
		} else {
			dialogId = aData.id;
		}

		var oDialog = document.getElementById('dialog_wrapper_' + dialogId + '_' + this.hash);

		if(oDialog) {
			this.prepareElementAccordions(oDialog);
		}

	},

	prepareElementAccordions: function(oObject) {

		// Expandable / Collapsable Boxen aktivieren und eventuell aufklappen
		$j('#'+oObject.id+' .box').each(function(i, box) {
			$j(box).boxWidget();
			if(
				$j(box).hasClass('box-autoexpand')
			) {
				var bHasValue = false;
				$j(box).find('input:not([type=hidden]),select,textarea').each(function() {
					if($j(this).val()) {
						bHasValue = true;
						return false;
					}
				});
				if(bHasValue) {
					$j(box).boxWidget('expand');
				}
			}

			$j(box).find('.collapse').on('show.bs.collapse', function(){
				$j(this).parent().find(".fa-plus").removeClass("fa-plus").addClass("fa-minus");
			}).on('hide.bs.collapse', function(){
				$j(this).parent().find(".fa-minus").removeClass("fa-minus").addClass("fa-plus");
			});

		}.bind(this));

	},

	// bereitet einen Kalender vor
	prepareCalendar: function(oCalendarInput, sIdImg, aActivDays, bForFilter, oPeriod) {

		var oOptions = {
			weekStart: 1,
			todayHighlight: true,
			todayBtn: 'linked',
			language: this.sLanguage,
			calendarWeeks: true,
			format: this.sCalendarFormat,
			autoclose: true,
			assumeNearbyYear: true,
			// immediateUpdates: true, // Altes Verhalten
			zIndexOffset: 3000
		};

		// TODO Wird das überhaupt noch benötigt? An anderer Stelle wird das direkt mit dem Objekt konfiguriert
		if(aActivDays) {
			var aDisabledDays = [];
			for(var i=0; i<7; i++) {
				if(aActivDays.indexOf(i) === -1) {
					aDisabledDays.push(i);
				}
			}
			oOptions.daysOfWeekDisabled = aDisabledDays.join(',');
		}

		if(!oPeriod) {
			oPeriod = {};
			if(
				oCalendarInput &&
				oCalendarInput.hasAttribute('data-period-from') &&
				oCalendarInput.hasAttribute('data-period-until')
			) {
				oPeriod.from = oCalendarInput.readAttribute('data-period-from');
				oPeriod.until = oCalendarInput.readAttribute('data-period-until');
			}
		}

		if(oPeriod.from) {
			oOptions.startDate = oPeriod.from;
		}

		if(oPeriod.until) {
			oOptions.endDate = oPeriod.until;
		}

		var oCalendar = $j(oCalendarInput);

		// Kalendar bereits vorhanden
		if(!oCalendar.data('datepicker')) {

			oCalendar.bootstrapDatePicker(oOptions);

			// Von changeDate auf change geändert, da manuelle Eingaben nicht changeDate auslösen
			// https://github.com/uxsolutions/bootstrap-datepicker/issues/2325
			//oCalendar.on('changeDate', jQuery.proxy(this.calendarCloseHandlerWrapper, this));
			oCalendar.on('change', function() {
				this.calendarCloseHandler(oCalendarInput, oCalendar.data('datepicker').getDate(), bForFilter);
			}.bind(this));

			this.aCalendarData[this.aCalendarData.length] = oOptions;

		} else {
			oCalendar.data('datepicker').update();
		}

		if(oCalendar.data('datepicker').getDate()) {
			this.displayWeekDay(oCalendarInput, oCalendar.data('datepicker').getDate().getDay());
			this.displayCalendarAge(oCalendarInput);
		} else {
			// Multiple Rows: Tag löschen
			this.displayWeekDay(oCalendarInput, '');
		}

	},

	// Führt alle Kalender aus , ( rufe also die Klasse auf )
	// TODO Kann entfernt werden?
	executeCalendars: function() {

		this.aCalendarData.each(function(aTempData) {
			//Calendar.prepareCallback(aTempData);
		}.bind(this));

		// leeren damit sie nicht erneut erzeugt werden
		this.aCalendarData = [];

	},

	/**
	 * Erzeugt ein Div mit dem Filter und allen dazugehörigen HTML Elementen
	 */
	createBarFilter: function(aElement) {

		if(this.searchElements == null) {
			this.searchElements = [];
		}

		var oDiv = new Element('div');
		oDiv.className = 'guiBarFilter';
		// Wenn ein Label vorhanden ist
		if(aElement.label != '') {
			var oLabel = this.createBarLabel(aElement.label);
			oDiv.appendChild(oLabel);
		}

		var oInput;

		if(aElement.filter_type == 'input') {

			oInput = new Element('input');
			oInput.type = 'text';
			oInput.style.width = aElement.width;
			oInput.name = aElement.name;
			oInput.id = aElement.id + '_' + this.hash;
			oInput.value = aElement.value;
			oInput.className = 'form-control input-sm';
			if(aElement['class']) {
				oInput.className = 'form-control input-sm ' + aElement['class'];
			}

			if(
				aElement.label == '' &&
				aElement.placeholder
			) {
				oInput.placeholder = aElement.placeholder;
			}

			/**
			 * Beim verändern des Browsers die Tabelle anpassen
			 */
			Event.observe(oInput, 'keyup', function(e) {
				this.prepareFilterSearch(e);
			}.bind(this));

			// Wichtig damit die suche auch dieses feld berücksichtigt!
			this.searchElements[this.searchElements.length] = aElement;
			//

			if(aElement.id == 'wdsearch') {
				var oWDSearchDiv = document.createElement('div');
				oWDSearchDiv.className = 'wdsearch';
				oWDSearchDiv.appendChild(oInput);
				oDiv.appendChild(oWDSearchDiv);
			} else {
				oDiv.appendChild(oInput);
			}

			if(aElement.id == 'wdsearch') {
				this.prepareWDSearch(oInput, aElement);
			}
		} else if(aElement.filter_type == 'checkbox') {
			oInput = new Element('input');
			oInput.type = 'checkbox';
			oInput.style.width = aElement.width;
			oInput.name = aElement.name;
			oInput.id = aElement.id + '_' + this.hash;
			oInput.value = aElement.value;
			oInput.className = '';

			/**
			 * Beim verändern des Browsers die Tabelle anpassen
			 */
			Event.observe(oInput, 'click', function(e) {
				this.prepareFilterSearch(e);
			}.bind(this));

			// Wichtig damit die suche auch dieses feld berücksichtigt!
			this.searchElements[this.searchElements.length] = aElement;
			//
			oDiv.appendChild(oInput);

		} else if(aElement.filter_type == 'select') {

			oInput = new Element('select');
			oInput.style.width = aElement.width;
			oInput.name = aElement.name;
			oInput.id = aElement.id + '_' + this.hash;
			oInput.className = 'form-control input-sm';

			if(aElement.multiple === true) {
				oInput.multiple = 'multiple';
			}
			if(aElement.size) {
				oInput.size = aElement.size;
			}

			var bNavigation = false;

			// Wenn der Flag für die Navigation des Selects gesetzt wurde, wird ein
			// data-Attribut mit dem default_value gesetzt
			if(
				aElement.select_navigation &&
				aElement.select_navigation.default_value != ''
			) {
				oInput.setAttribute('data-default', aElement.select_navigation.default_value);
				bNavigation = true;
			}

			var iOptions = 0;
			aElement.select_options.each(function(aOption) {

				var oOption = new Element('option');
				oOption.value = aOption['key'];
				oOption.innerHTML = aOption['value'];

				if(typeof aElement.value == 'object') {
					aElement.value.each(function(mValue) {
						if(aOption['key'] == mValue) {
							oOption.selected = true;
							throw $break;
						}
					});
				} else if(
					aOption['key'] == aElement.value
				) {
					oOption.selected = true;
				}

				oInput.appendChild(oOption);
				iOptions++;
			}.bind(this));


			// Wichtig damit die suche auch dieses feld berücksichtigt!
			this.searchElements[this.searchElements.length] = aElement;
			//
			oDiv.appendChild(oInput);

			// Wenn Navigation gesetzt werden soll
			if(bNavigation === true) {

				// Äußeres Div
				var oNavigation = new Element('div');
				oNavigation.className = 'divNavigation';

				// Icon: Einen Eintrag zurück
				var oBackNavigation = this.createSelectFilterNavigationIcon('fa-backward', this.getTranslation('select_navigation_back'));
				oBackNavigation.id = aElement.id + '_' + this.hash + '_back';
				// Event auf das Icon setzen
				this.setSelectFilterEvent(oBackNavigation, 'back', iOptions);
				oNavigation.appendChild(oBackNavigation);

				// Icon: Standard-Eintrag
				var oDefaultNavigation = this.createSelectFilterNavigationIcon('fa-circle-o', this.getTranslation('select_navigation_default'));
				oDefaultNavigation.id = aElement.id + '_' + this.hash + '_default';
				// Event auf das Icon setzen
				this.setSelectFilterEvent(oDefaultNavigation, 'default', iOptions);
				oNavigation.appendChild(oDefaultNavigation);

				// Icon: Einen Eintrag vor
				var oNextNavigation = this.createSelectFilterNavigationIcon('fa-forward', this.getTranslation('select_navigation_next'));
				oNextNavigation.id = aElement.id + '_' + this.hash + '_next';
				// Event auf das Icon setzen
				this.setSelectFilterEvent(oNextNavigation, 'next', iOptions);
				oNavigation.appendChild(oNextNavigation);

				oDiv.appendChild(oNavigation);

				// Status des Icons überprüfen
				this.checkSelectFilterNavigationVisibility(oInput.selectedIndex, iOptions, oInput);
			}

			/**
			 * Beim verändern des Browsers die Tabelle anpassen
			 */
			Event.observe(oInput, 'change', function(e) {
				// Falls das Select eine Navigation besutzt, muss diese auch aktualisiert werden
				if(bNavigation === true) {
					this.checkSelectFilterNavigationVisibility(oInput.selectedIndex, iOptions, oInput);
				}
				this.prepareFilterSearch(e, aElement.dependency);
			}.bind(this));

		} else {
			var aError = [];
			aError[0] = '<span style="color:red">Filter Element Type [' + aElement.filter_type + '] unknown</span>';
			this.displayErrors(aError);
		}

		return oDiv;

	},

	/**
	 * setzt auf das übergebene Icon das Event, um das Select zu navigieren
	 */
	setSelectFilterEvent: function(oElement, sAction, iOptions) {
		oElement.stopObserving('click');
		Event.observe(oElement, 'click', function(){
			this.changeSelectFilter(oElement, sAction, iOptions);
		}.bind(this));
	},

	/**
	 * erstellt ein Icon für die Select-Navigation
	 */
	createSelectFilterNavigationIcon: function(sIcon, sTitle) {

		// Icon Div
		var oIconDiv = new Element('div');
		oIconDiv.className = 'divToolbarIcon divSelectFilterNavigation';

		// Icon IMG
		var oIcon = new Element('i');
		oIcon.className = 'select_filter_navigation_icon fa ' + sIcon;
		oIcon.alt = sTitle;
		oIcon.title = sTitle;

		// Elemente setzen
		oIconDiv.appendChild(oIcon);

		return oIconDiv;
	},

	/**
	 * Funktion, die beim Navigieren des Selects aufgerufen wird. Je nach sAction
	 * wird dann dementsprechend ein Eintrag gewählt
	 */
	changeSelectFilter: function(oElement, sAction, iOptions) {

		var sFilterId = oElement.id.replace('_' + sAction, '');
		var oFilter = $(sFilterId);

		// Wert der aktuell drin steht
		var iSelectedIndex	= oFilter.selectedIndex;
		var bLoadTable = true;

		if(oFilter) {
			switch (sAction){
				case 'back':
					iSelectedIndex--;

					// Wenn der aktuelle Eintrag dem ersten Eintrag im Select entspricht, Navigation abbrechen
					if(iSelectedIndex < 0) {
						bLoadTable = false;
					}

					break;
				case 'next':
					iSelectedIndex++;

					// Wenn der aktuelle Eintrag dem letzetn Eintrag im Select entspricht, Navigation abbrechen
					if(iSelectedIndex > (iOptions - 1)) {
						bLoadTable = false;
					}

					break;
				case 'default':
				default:

					// Defaultwert setzen
					iSelectedIndex = this.setSelectFilterDefaultValue(oFilter, iOptions);
					break;
			}
		}

		if(bLoadTable === true) {
			// ensprechenden Eintrag im Filter auswählen
			oFilter.selectedIndex = iSelectedIndex;
			// Prüfen, ob ein Icon deaktiviert werden muss
			this.checkSelectFilterNavigationVisibility(iSelectedIndex, iOptions, oFilter);
			// Tabelle neu laden
			this.loadTable(false);
		}
	},

	/**
	 * Defaultwert des Selects anhand von dem data-Attribut setzen
	 */
	setSelectFilterDefaultValue: function(oFilter, iOptions) {

		var sDefault = oFilter.getAttribute('data-default');
		var iSelectedIndex = 0;

		// Eintrag raussuchen
		for (var i = 0; i < iOptions; ++i) {
			if (oFilter.options[i].value == sDefault) {
				iSelectedIndex = i;
				break;
			}
		}

		return iSelectedIndex;
	},

	/**
	 * Prüfen, ob ein Icon deaktiviert werden muss
	 */
	checkSelectFilterNavigationVisibility: function(iSelectedIndex, iMax, oElement) {

		if(oElement) {

			// Die beiden Icons, welche geprüft werden müssen
			var oLeftNavigation = $(oElement.id + '_back');
			var oNextNavigation = $(oElement.id + '_next');

			if(iSelectedIndex == (iMax - 1)) {
				// Wenn der erste Eintrag ausgewählt ist
				this.changeSelectFilterIconStatus(oNextNavigation, 0);
				this.changeSelectFilterIconStatus(oLeftNavigation, 1);
			} else if(
				iSelectedIndex > 0 &&
				iSelectedIndex < iMax
			) {
				// Wenn mitten im Select ein Eintrag ausgewählt wurde sind beide Icons aktiv
				this.changeSelectFilterIconStatus(oLeftNavigation, 1);
				this.changeSelectFilterIconStatus(oNextNavigation, 1);
			} else {
				// Wenn der letzte Eintrag ausgewählt ist
				this.changeSelectFilterIconStatus(oLeftNavigation, 0);
				this.changeSelectFilterIconStatus(oNextNavigation, 1);
			}

		}

	},

	/**
	 * Status des Icons ändern
	 */
	changeSelectFilterIconStatus: function(oElement, iVisibility) {

		if(oElement) {
			// Icon holen
			var oIcon = oElement.down('.select_filter_navigation_icon');

			if(	oIcon) {
				// CSS-Klasse setzen
				if(iVisibility == 1) {
					oIcon.removeClassName('guiIconInactive');
				} else if(iVisibility == 0) {
					oIcon.addClassName('guiIconInactive');
				}
			}
		}
	},

	getBarIconId: function(aElement) {

		var sId = this.getIconDataId(aElement);

		sId += '_' + this.hash;

		return sId;

	},

	getFieldKey: function(aElement) {

		var sKey = '';

		if(aElement.db_alias) {
			sKey += aElement.db_alias + '.';
		}
		sKey += aElement.db_column;

		return sKey;

	},

	getIconDataId: function(aElement) {

		var sId = '';
		if(aElement.action) {
			sId = aElement.action;
		} else if(aElement.task) {
			sId = aElement.task;
		}

		if(aElement.additional) {
			sId += '_' + aElement.additional;
		}

		return sId;

	},

	createIcon: function(aElement) {

		if(
			aElement.img.indexOf('fab') === 0 ||
			aElement.img.indexOf('far') === 0 ||
			aElement.img.indexOf('fas') === 0
		) {

			var oIconImg = new Element('i');
			oIconImg.className = aElement.img+' fa-colored icon';
			oIconImg.title = aElement.title;

		} else if(aElement.img.indexOf('fa-') === 0) {

			var oIconImg = new Element('i');
			oIconImg.className = 'fa '+aElement.img+' fa-colored icon';
			oIconImg.title = aElement.title;

		} else {

			var oIconImg = new Element('img');
			oIconImg.src = aElement.img;
			oIconImg.alt = aElement.title;
			oIconImg.title = aElement.title;
			oIconImg.className = 'icon';

		}

		return oIconImg;
	},

	/**
	 * Erzeugt ein Div mit dem Icon und allen dazugehörigen HTML Elementen
	 */
	createBarIcon: function(aElement) {

		if(
			aElement.dbl_click_element &&
				aElement.dbl_click_element == 1
			) {
			this.oDblClickElement = aElement;
		}

		//if(aElement.visible == 1) {
			var oDiv = new Element('div');
			oDiv.className = 'guiBarElement';

			// Icon Div
			var oIconDiv = new Element('div');
			oIconDiv.className = 'divToolbarIcon w16';
			oIconDiv.id = this.getBarIconId(aElement);

			// Icon
			oIconImg = this.createIcon(aElement);

			oIconDiv.appendChild(oIconImg);
			oDiv.appendChild(oIconDiv);

			// Wenn ein Label vorhanden ist
			if(aElement.label != '') {
				var oLabel = this.createBarLabel(aElement.label);
				oDiv.appendChild(oLabel);
			}

			if(aElement.visible == 1) {
				oDiv.show();
			} else{
				oDiv.hide();
			}

			return oDiv;
		//}
		//return false;
	},

	prepareIconInfoText: function(e, aIcon) {

		// Hook um vor dem öffnen noch events ausführen zu können
		this.prepareIconInfoTextHook(e, aIcon);

		var sIconId = this.getBarIconId(aIcon);
		var oDiv = $('infotext_' + sIconId);
		var oArrow = $('infotext_img_' + sIconId);

		if(!oDiv.visible()) {
			$$('.divToolbarIconArrowBox').each(function(oTemp) {
				oTemp.hide();
			});
		}

		oDiv.toggle();

		// Jedesmal bei Mouseover neu setzen
		oDiv.stopObserving('mousemove');
		Event.observe(oDiv, 'mousemove', function() {
			if(this.showIconInfoText) {
				clearTimeout(this.showIconInfoText);
			}
			this.showIconInfoText = setTimeout(this.hideIconInfoText.bind(this, false, oDiv), 2000);
		}.bind(this));

		oDiv.clonePosition(oArrow, {'setWidth': false, 'setHeight': false, 'offsetLeft': -17, 'offsetTop': 14});

		oDiv.update(aIcon.info_text);

		// Auch wenn man nichts anklickt wird das Fenster wieder geschlossen
		if(this.showIconInfoText) {
			clearTimeout(this.showIconInfoText);
		}

		this.showIconInfoText = setTimeout(this.hideIconInfoText.bind(this, false, oDiv), 2000);

	},

	/*
	 * Versteckt das Infotext Fenster
	 */
	hideIconInfoText: function(i, oDiv) {
		oDiv.hide();
	},

	/**
	 * zeigt den Ladebalken
	 */
	showLoading: function() {
		if($(this.loadingIndicator)) {
			$(this.loadingIndicator).show();
		}

		if(this.hideLoadingObserver) {
			clearTimeout(this.hideLoadingObserver);
		}

		// Dialoge durchgehen und sperren!
		// wichtig damit wärend eines oder mehreren request nichts mehr betätigt werden kann
		this.aDialogs.each(function(oDialog) {
			if(oDialog && oDialog.body) {

				oWrapper = oDialog.body;

				var oLoadingDiv;
				// Prüfen ob es bereits ein div gibt.
				// wenn ja nehme das ansonsten erzeuge ein neues
				if(oWrapper.down('.guiLoadingDiv')) {
					oLoadingDiv = oWrapper.down('.guiLoadingDiv');
				} else {
					oLoadingDiv = oWrapper.clone(false);
				}

				if(oLoadingDiv.style.zIndex < 1000) {
					oLoadingDiv.style.zIndex = 1000;
				} else {
					oLoadingDiv.style.zIndex = oLoadingDiv.style.zIndex + 1;
				}
				oLoadingDiv.id = '';
				oLoadingDiv.className = 'guiLoadingDiv';
				oLoadingDiv.style.height = (oWrapper.getHeight()) + 'px';
				oLoadingDiv.style.width = (oWrapper.getWidth()) + 'px';

				$j(oLoadingDiv).html('<i class="fa fa-spinner fa-spin"></i>')

				oWrapper.insert({'bottom': oLoadingDiv});
			}
		});
	},

	/**
	 * blendet den Ladebalken "verzögert" aus
	 * damit bei mehreren Dialogen übereinander der ladebalken wartet bis alles
	 * fertig geladen ist!
	 */
	hideLoading: function() {
		if($$('.guiLoadingDiv')) {

			if(this.hideLoadingObserver) {
				clearTimeout(this.hideLoadingObserver);
			}

			this.hideLoadingObserver = setTimeout(this.hideLoadingCallback.bind(this), 150);

		} else {
			this.hideLoadingCallback();
		}

	},

	/**
	 * blendet den Ladebalken aus
	 */
	hideLoadingCallback: function() {
		if($(this.loadingIndicator)) {
			$(this.loadingIndicator).hide();
		}

		$$('.guiLoadingDiv').each(function(oDiv) {
			oDiv.remove();
		})
	},

	/*
	 * Funktion prüft auf Array in dieser Klasse
	 */
	is_array: function(variable) {
		return typeof(variable) == 'object' && (variable instanceof Array);
	},

	/**
	 * Initialisiert den tinyMCE Editor
	 *
	 * @param {boolean} readonly
	 * @param {string} sAdvanced
	 * @param {int} iWidth
	 */
	tinyMCEinit: function(readonly, sAdvanced, iWidth) {

		if(typeof(readonly) == 'undefined') {
			readonly = false;
		}

		if(typeof(sAdvanced) == 'undefined') {
			sAdvanced = '';
		}

		if(typeof(iWidth) == 'undefined') {
			iWidth = 0;
		}

		if (!tinyMCE.editors) {
			tinyMCE.editors = [];
		}

		var sToolbar = "undo redo | styleselect | searchreplace pastetext visualblocks visualchars | bold italic underline | alignleft aligncenter alignright | forecolor | bullist numlist outdent indent | preview code fullscreen";

		// @TODO Bitte das alles einfach mal vereinheitlichen, das sind nur JavaScript-Objekte…
		if(sAdvanced == 'filemanager') {
			tinyMCE.init({
				//General options
				language: this.sLanguage,
				mode: "none",
				theme: "modern",
				skin: "lightgray",
				plugins: [
					"advlist autolink lists link image charmap print preview hr anchor pagebreak",
					"searchreplace wordcount visualblocks visualchars code fullscreen",
					"insertdatetime media nonbreaking save table contextmenu directionality",
					"emoticons template paste textcolor colorpicker textpattern responsivefilemanager"
				],
				menubar: false,
				toolbar1: sToolbar,
				toolbar2: "preview | backcolor | link image | charmap table | responsivefilemanager",
				toolbar_items_size: 'small',
				image_advtab: true,
				// Theme options
				//spellchecker_languages: "+English=en,German=de,French=fr,Spanish=es,Chinese=zh,Italian=it,Japanese=ja,Portuguese=pt",
				//spellchecker_rpc_url: "/admin/includes/tiny_mce/plugins/spellchecker/rpc.php?module=SpellChecker",
				theme_modern_toolbar_location: "top",
				theme_modern_toolbar_align: "left",
				theme_modern_statusbar_location: "none",
				theme_modern_resizing: true,
				theme_modern_path: false,
				readonly: readonly,
				//verify_html : false
				//apply_source_formatting : true
				forced_root_block: false,
				verify_html: false,
				convert_urls: false,
				remove_script_host: true,
				resize: "both",
				width: iWidth,
				external_filemanager_path: "/tinymce/resource/filemanager/",
				filemanager_title: "Filemanager",
				external_plugins: {
					"filemanager" : "/tinymce/resource/filemanager/plugin.min.js"
				}
			});
		} else if(sAdvanced == 'advanced') {
			tinyMCE.init({
				// General options
				language: this.sLanguage,
				mode: "none",
				theme: "modern",
				skin: "lightgray",
				plugins: [
					"advlist autolink lists link image charmap print preview hr anchor pagebreak",
					"searchreplace wordcount visualblocks visualchars code fullscreen",
					"insertdatetime media nonbreaking save table contextmenu directionality",
					"emoticons template paste textcolor colorpicker textpattern"
				],
				menubar: false,
				toolbar1: sToolbar,
				toolbar2: "backcolor | link image | charmap table",
				toolbar_items_size: 'small',
				image_advtab: true,
				// Theme options
				//spellchecker_languages: "+English=en,German=de,French=fr,Spanish=es,Chinese=zh,Italian=it,Japanese=ja,Portuguese=pt",
				//spellchecker_rpc_url: "/admin/includes/tiny_mce/plugins/spellchecker/rpc.php?module=SpellChecker",
				theme_modern_toolbar_location: "top",
				theme_modern_toolbar_align: "left",
				theme_modern_statusbar_location: "none",
				theme_modern_resizing: true,
				theme_modern_path: false,
				readonly: readonly,
				//verify_html : false
				//apply_source_formatting : true
				forced_root_block: false,
				verify_html: false,
				convert_urls: false,
				remove_script_host: true,
				resize: "both",
				width: iWidth
			});
		} else {
			tinyMCE.init({
				// General options
				language: this.sLanguage,
				mode: "none",
				theme: "modern",
				skin: "lightgray",
				plugins: [
					"textcolor searchreplace visualblocks visualchars preview code fullscreen",
					"contextmenu paste"
				],
				menubar: false,
				statusbar: false,
				toolbar1: sToolbar,
				toolbar_items_size: 'small',
				// Theme options
				//spellchecker_languages: "+English=en,German=de,French=fr,Spanish=es,Chinese=zh,Italian=it,Japanese=ja,Portuguese=pt",
				//spellchecker_rpc_url: "/admin/includes/tiny_mce/plugins/spellchecker/rpc.php?module=SpellChecker",
				theme_modern_toolbar_location: "top",
				theme_modern_toolbar_align: "left",
				theme_modern_statusbar_location: "none",
				theme_modern_resizing: true,
				theme_modern_path: false,
				readonly: readonly,
				//verify_html : false
				//apply_source_formatting : true
				forced_root_block: false,
				verify_html: false,
				convert_urls: false,
				remove_script_host: true,
				resize: "both",
				width: iWidth
			});
		}
	},

	pepareHtmlEditors: function(sDialogId) {

		if(typeof(tinyMCE) == 'undefined') {
			return;
		}

		var aEditorFields = $$('#dialog_' + sDialogId + '_' + this.hash + ' .GuiDialogHtmlEditor');

		if(!this.iHtmlEditorCount) {
			this.iHtmlEditorCount = 0;
		}

		this.tinyMCEinit(false);

		aEditorFields.each(function(oEditor) {

			if(
				!oEditor.id ||
				oEditor.id == ""
			) {
				oEditor.id = 'gui2_html_editor_' + parseInt(this.iHtmlEditorCount);
				this.iHtmlEditorCount++;
			}

			var oTiny = tinyMCE.get(oEditor.id);

			// Nur initialisieren wenn noch nicht vorhanden
			if(
				!oTiny &&
				!oEditor.hasClassName('active_editor')
			) {

				var sAdvanced;
				if(oEditor.hasClassName('advanced')) {
					sAdvanced = 'advanced';
				} else if(oEditor.hasClassName('filemanager')) {
					sAdvanced = 'filemanager';
				}

				if(!oEditor.disabled) {
					this.tinyMCEinit(false, sAdvanced, oEditor.style.width);
				} else {
					this.tinyMCEinit(true, sAdvanced, oEditor.style.width);
				}

				tinyMCE.execCommand("mceAddEditor", true, oEditor.id);
				oEditor.addClassName('active_editor');

			}

		}.bind(this));

	},

	prepareTabareas: function(sDialogId) {

		var aTabareaTabs = $j('#dialog_' + sDialogId + '_' + this.hash).find('.tab_area .tab_area_li a');

		// Bootstrap Event abfangen und ausgewählten Tab merken um diesen z.b. bei einem reloadDialogTab wieder auszuwählen
		aTabareaTabs.each((index, oTab) => {
			$j(oTab).on('shown.bs.tab', (e) => {
				var sTabareaId = $j(e.target).closest('.tab_area').attr('id');
				this.oTabAreas[sDialogId][sTabareaId] = $j(e.target).attr('id');
			})
		});

		// Bereits ausgewählte Tabs wieder auswählen (ansonsten springt er auf den ersten Tab)
		if(this.oTabAreas.hasOwnProperty(sDialogId)) {
			Object.keys(this.oTabAreas[sDialogId]).forEach((sTabareaId) => {
				var sTabId = this.oTabAreas[sDialogId][sTabareaId];
				var oTab = $(sTabId);
				if(oTab) {
					this._fireEvent('click', oTab);
				}
			});
		}

	},

	prepareDialogInfoIcons: function(sSuffix, sDialogId) {
		// Events zum editieren der Informationen
		this.prepareDialogInfoIconsAction(sSuffix, sDialogId);

		if(!this.oDialogInfoIconValues.hasOwnProperty(sSuffix)) {
			// Wenn noch keine Daten geladen wurden
			this.loadDialogInfoIconValues(sSuffix, sDialogId);
		} else {
			// Die vorhandenen Daten in die Info-Icons setzen
			this.setDialogInfoIconValues(sDialogId, this.oDialogInfoIconValues[sSuffix]);
		}

	},

	setDialogInfoIconValues: function(sDialogId, aValues) {
		// Zu Beginn alle Icons zurücksetzen (falls ein Text gelöscht wurde und vorher schon geladen wurde)
		$j('#dialog_' + sDialogId + '_' + this.hash + ' .gui-info-icon').addClass('inactive');
		$j('#dialog_' + sDialogId + '_' + this.hash + ' .gui-info-icon').removeClass('private');
		$j('#dialog_' + sDialogId + '_' + this.hash + ' .gui-info-icon').attr('data-title', '');

		for(var i = 0; i < aValues.length; ++i) {

			$j('#dialog_' + sDialogId + '_' + this.hash + ' div[data-row-key="'+aValues[i]['row_key']+'"]').each(function() {

				var oInfoIcon = $j(this).find('> label').find('.gui-info-icon');

				if(oInfoIcon.length === 0) {
					// Icon existiert nicht (außerhalb vom Modus)
					var oInfoIcon = new Element('i');
					$j(this).find('> label').prepend(oInfoIcon);
					$j(oInfoIcon).addClass('fa fa-info-circle gui-info-icon prototypejs-is-dead');
				}

				if(aValues[i]['private'] == 1) {
					// wird nur gesetzt wenn man im Modus ist
					$j(oInfoIcon).addClass('private');
				}

				$j(oInfoIcon).attr('data-title', aValues[i]['value']);
				$j(oInfoIcon).removeClass('inactive');

				$j(oInfoIcon).bootstrapTooltip({
					html: true,
					placement: 'right',
					container: oInfoIcon.closest('.GUIDialogRowLabelDiv'),
					title: function() {
						return $j(this).attr('data-title');
					}
				});

				// Fixing width (not perfect)
				if(aValues[i]['value'].split(" ").length > 1) {
					var iStrLength = aValues[i]['value'].length;
					if(iStrLength > 50) {
						$j(oInfoIcon).closest('.GUIDialogRowLabelDiv').addClass('tooltip-large');
					} else if(iStrLength > 10) {
						$j(oInfoIcon).closest('.GUIDialogRowLabelDiv').addClass('tooltip-medium');
					}
				}

			});

		}
	},

	prepareDialogInfoIconsAction: function(sSuffix, sDialogId) {
		$j('#dialog_' + sDialogId + '_' + this.hash + ' .gui-info-icon.editable').click(function(e, oIcon) {

			var sRowKey = $j(e.target).closest('.GUIDialogRow').attr('data-row-key');

			if(sRowKey) {
				// Dialog öffnen
				this.openInfoIconDialog(sRowKey);
			}
		}.bind(this));
	},

	openHelp: function () {
		if (this.options.info_icon_edit_mode) {
			this.openInfoIconDialog(`${this.hash}.${this.options.info_icon_help_key}.list`, 'input', ['en'])
		} else if (this.options.help_url) {
			window.open(this.options.help_url, '_blank')
		}
	},

	openInfoIconDialog: function(sRowKey, sFieldType, aLanguages) {
		var sParams = '&task=openDialog&action=edit_dialog_info_icon';
		sParams += '&additional='+ sRowKey;

		if (sFieldType) {
			sParams += '&field_type='+ sFieldType;
		}

		if (aLanguages && Array.isArray(aLanguages)) {
			aLanguages.forEach(function (sLanguage) {
				sParams += '&languages[]='+ sLanguage;
			})
		}

		this.request(sParams);
	},

	loadDialogInfoIconValues: function(sSuffix, sDialogId) {
		var sParam = '&task=request&action=DialogInfoIconValues';
		sParam += '&dialog_suffix=' + sSuffix;
		sParam += '&dialog_id=' + sDialogId;
		// Texte der Info-Icons laden
		this.request(sParam, '', '', false, 0, false);
	},

	// InplaceEditor
	prepareInplaceEditor: function(oElement, aBody) {

		// ein Div erzeugen damit der inplace Editor in TDs klappt
		var oDiv = new Element('div');
		oDiv.id = oElement.id + '_inplace' + '_' + this.hash;
		oDiv.update(oElement.innerHTML);
		oElement.update('');
		oElement.appendChild(oDiv);

		// Wenn die Spalte leer ist muss etwas reingeschrieben werden!
		if(oDiv.innerHTML == '' || oDiv.innerHTML == '&nbsp;') {
			oDiv.update('');
		}

		var sOldValue = oDiv.innerHTML;

		// ID des Eintrages
		var iId = aBody.id;
		if(aBody.db_type == 'timestamp') {
			this.prepareCustomInplaceEditor(oDiv, aBody, 'calendar', oElement);
		} else if(aBody.db_type == 'tinyint') {
			this.prepareCustomInplaceEditor(oDiv, aBody, 'checkbox', oElement);
		} else if(aBody.ie_type == 'select') {
			this.prepareCustomInplaceEditor(oDiv, aBody, 'select', oElement);
		} else {
			this.prepareCustomInplaceEditor(oDiv, aBody, 'text', oElement);
		}

	},

	prepareCustomInplaceEditor: function(oDiv, aBody, sType, oElement) {

		oDiv.className += ' guiInplaceEditor';

		if(aBody.ie_direct) {
			this.startCustomInplaceEditor(oDiv, aBody, sType, oElement);
		}
		else {
			Event.observe(oDiv, 'click', function() {
				this.startCustomInplaceEditor(oDiv, aBody, sType);
			}.bind(this));
		}

//		Event.observe(oDiv, 'mouseover', function() {
//			this.colorCustomInplaceEditor(oDiv, '#FFFF99', 0.1);
//		}.bind(this));
//
//		Event.observe(oDiv, 'mouseout', function() {
//			this.colorCustomInplaceEditor(oDiv, '', 0.6);
//		}.bind(this));

	},

	/**
	 * Färbt das Div eines Individuellen Inplace Editors
	 */
	colorCustomInplaceEditor: function(oDiv, sColor, iDuration) {

		var sStyle = '';

		if(sColor != '') {
			sStyle = 'background:' + sColor;
		} else {
			sStyle = 'background: #fff';
		}

		new Effect.Morph(oDiv, {
			style: sStyle, // CSS Properties
			duration: iDuration // Core Effect properties
		});

	},

	startCustomInplaceEditor: function(oDiv, aBody, sType, oElement) {

		var oOldDiv = oDiv.clone(true);

		if(oElement) {
			var oTd = oElement;
		}
		else {
			var oTd = oDiv.up('td');
		}
		oTd.innerHTML = '';

		var oDiv = new Element('div');

		oDiv.className = oOldDiv.className;
		oTd.appendChild(oDiv);

		var iOldWidth = 0;

		var sId = aBody.db_column + '_' + aBody.db_alias;
		if($(sId)) {
			iOldWidth = $(sId).style.width;
			$(sId).style.width = '120px';
			oTd.style.width = '120px';
		}

		if(sType == 'text') {

			var oInput = new Element('input');
			oInput.className = 'txt input-sm form-control';
			oInput.id = 'customer_inplace_calendar_input' + this.customerInplaceEditorCount + '_' + this.hash;
			oInput.value = oOldDiv.innerHTML;
			oDiv.appendChild(oInput);

		} else if(sType == 'calendar') {

			var oInput = new Element('input');
			oInput.className = 'guiInplaceEditorInput';
			oInput.id = 'customer_inplace_calendar_input' + this.customerInplaceEditorCount + '_' + this.hash;
			oInput.value = oOldDiv.innerHTML;
			oDiv.appendChild(oInput);

			var oImg = new Element('img');
			oImg.className = 'guiInplaceEditorImg';
			oImg.src = '/admin/media/calendar.png';
			oImg.id = 'customer_inplace_calendar_img' + this.customerInplaceEditorCount + '_' + this.hash;
			oDiv.appendChild(oImg);

		} else if(sType == 'select') {

			var oInput = new Element('select');
			oInput.id = 'customer_inplace_calendar_input' + this.customerInplaceEditorCount + '_' + this.hash;
			aBody.ie_options.each(function(aData) {
				oOption = new Element('option');
				oOption.text = aData.text;
				oOption.value = aData.value;
				if(oOption.text == oOldDiv.innerHTML) {
					oOption.selected = true;
				}
				oInput.appendChild(oOption);
			});
			// Speichern beim Ändern der Auswahl
			Event.observe(oInput, 'change', function() {
				this.saveCustomInplaceEditor(oDiv, oTd, oOldDiv, aBody, sType, iOldWidth);
			}.bind(this));
			// Speichern beim Focus entfernen
			Event.observe(oInput, 'blur', function() {
				this.saveCustomInplaceEditor(oDiv, oTd, oOldDiv, aBody, sType, iOldWidth);
			}.bind(this));
			oDiv.appendChild(oInput);

			// Focus auf Select legen
			oInput.focus();

		} else if(sType == 'checkbox') {
			var oInput = new Element('input');
			oInput.type = 'checkbox';
			oInput.value = 1;
			if(parseInt(oOldDiv.innerHTML) > 0) {
				oInput.checked = true;
			}
			//oInput.className = 'guiInplaceEditorInput';
			oInput.id = 'customer_inplace_calendar_input' + this.customerInplaceEditorCount + '_' + this.hash;
			//oOldDiv.innerHTML
			Event.observe(oInput, 'click', function() {
				this.saveCustomInplaceEditor(oDiv, oTd, oOldDiv, aBody, sType, iOldWidth);
			}.bind(this));
			oDiv.appendChild(oInput);

		}

		if(sType == 'text') {

			$j(oInput).keyup(function(e) {
				if(e.keyCode == 13) {
					this.saveCustomInplaceEditor(oDiv, oTd, oOldDiv, aBody, sType, iOldWidth);
				}
			}.bind(this));

			$j(oInput).focusout(function(e) {
				this.cancelCustomInplaceEditor(oTd, oOldDiv, aBody, sType, iOldWidth);
			}.bind(this));

		} else if(
			sType != 'checkbox' &&
			sType != 'select'
		) {
			var oButton1 = new Element('button');
			oButton1.update(this.getTranslation('ok'));
			oButton1.className = 'btn';
			oButton1.style.margin = '0px';
			Event.observe(oButton1, 'click', function() {
				this.saveCustomInplaceEditor(oDiv, oTd, oOldDiv, aBody, sType, iOldWidth);
			}.bind(this));
			oDiv.appendChild(oButton1);

			var oButton2 = new Element('button');
			oButton2.update(this.getTranslation('cancel'));
			oButton2.className = 'btn';
			oButton2.style.margin = '0px';
			Event.observe(oButton2, 'click', function() {
				this.cancelCustomInplaceEditor(oTd, oOldDiv, aBody, sType, iOldWidth);
			}.bind(this));
			oDiv.appendChild(oButton2);

			// Kalender starten
			if(oImg) {
				this.prepareCalendar(oInput, oImg);
				this.executeCalendars();
			}
		}

		this.customerInplaceEditorCount++;

	},

	cancelCustomInplaceEditor: function(oTd, oOldDiv, aBody, sType, iOldWidth) {
		oTd.innerHTML = '';
		oTd.appendChild(oOldDiv);

		// Breite wieder zurücksetzten
		oTd.style.width = iOldWidth;
		var sId = aBody.db_column + '_' + aBody.db_alias + '_' + this.hash;
		if($(sId)) {
			$(sId).style.width = iOldWidth;
		}

		this.prepareCustomInplaceEditor(oOldDiv, aBody, sType);
	},

	saveCustomInplaceEditor: function(oDiv, oTd, oOldDiv, aBody, sType, iOldWidth) {

		if(sType == 'select') {
			var oInput = oDiv.down('select');
			var mValue = $F(oInput);
		} else if(sType == 'checkbox') {
			var oInput = oDiv.down('input');
			if(oInput.checked) {
				var mValue = 1;
			}
			else {
				var mValue = 0;
			}
		} else {
			if(oDiv.tagName === 'INPUT') {
				var oInput = oDiv;
			} else {
				var oInput = oDiv.down('input');
			}
			var mValue = oInput.value;
		}

		var sParam = '&task=saveCustomInplaceEditor';
		sParam += '&value=' + mValue;
		sParam += '&db_column=' + aBody.db_column;
		sParam += '&db_alias=' + aBody.db_alias;
		sParam += '&db_type=' + aBody.db_type;
		sParam += '&type=' + sType;
		sParam += '&id=' + aBody.id;
		sParam += '&old_value=' + oOldDiv.innerHTML;

		this.request(sParam);

		if(aBody.ie_direct) {
			this.resize();

			return;
		}

		oOldDiv.innerHTML = '';
		if(sType == 'select') {
			$A(oInput.options).each(function(oOption) {
				if(oOption.value == $F(oInput)) {
					oOldDiv.innerHTML = oOption.text;
				}
			});
		} else {
			oOldDiv.innerHTML = oInput.value;
		}
		oTd.innerHTML = '';
		oTd.appendChild(oOldDiv);

		// Breite wieder zurücksetzten
		oTd.style.width = iOldWidth;
		var sId = aBody.db_column + '_' + aBody.db_alias + '_' + this.hash;
		if($(sId)) {
			$(sId).style.width = iOldWidth;
		}

		this.prepareCustomInplaceEditor(oOldDiv, aBody, sType, oTd);
		this.resize();

	},

	customerInplaceEditorCallback: function(aData) {

		this.hideLoading();

	},

	// Liefert die Fensterbreite
	getFrameWidth: function() {

		var intWidth = 0;
		intWidth = window.innerWidth;

		if(!intWidth) {
			intWidth = document.body.clientWidth;
		}

		if(!intWidth) {
			intWidth = document.documentElement.clientWidth;
		}

		return intWidth;
	},

	// Liefert die Fensterbreite
	getFrameHeight: function() {

		var intHeight = 0;
		intHeight = window.innerHeight;

		if(!intHeight) {
			intHeight = document.body.clientHeight;
		}

		if(!intHeight) {
			intHeight = document.documentElement.clientHeight;
		}

		return intHeight;
	},

	// Contextmenü

	/*contextmenu : function (e, sContent)
	 {

	 if($('contextmenu')){
	 $('contextmenu').remove();
	 }

	 this.createcontextmenu(sContent);

	 if (!e){
	 e = window.event;
	 }

	 if (
	 (e.type && e.type == "contextmenu") ||
	 (e.button && e.button == 2) ||
	 (e.which && e.which == 3)
	 ) {
	 $('contextmenu').style.display = "block";

	 var x = (document.all) ? window.event.x + document.body.scrollLeft : e.pageX;
	 var y = (document.all) ? window.event.y + document.body.scrollTop : e.pageY;

	 $('contextmenu').style.left = x+'px';
	 $('contextmenu').style.top = y+'px';

	 } else if(
	 e &&
	 e.originalTarget &&
	 e.originalTarget.up('div') &&
	 e.originalTarget.up('div').id != 'contextmenu'
	 )  {
	 this.closecontextmenu();
	 }

	 Event.stop(e);
	 return false;

	 } ,

	 createcontextmenu : function(sContent){

	 var oDiv = new Element('div');
	 oDiv.id = 'contextmenu';
	 oDiv.update(sContent);

	 document.body.appendChild(oDiv);
	 },

	 closecontextmenu : function(e)
	 {

	 if($('contextmenu') && e.target != $('contextmenu')){
	 $('contextmenu').style.display = "none";
	 }
	 },*/

	/*calendarCloseHandlerWrapper: function(oEvent) {

		var oInput = oEvent.target;

		var bForFilter = $j(oInput).data('filter');

		this.calendarCloseHandler(oInput, oEvent.date, bForFilter);

	},*/

	/**
	 * Handler, der nach dem Schließen des Kalenders ausgeführt wird
	 *
	 * Die Methode sollte möglichst nicht abgeleitet werden, sondern change-Events sollten benutzt werden.
	 *
	 * @param {Element} oInput
	 * @param {Date} oDate
	 * @param {Boolean} bForFilter
	 */
	calendarCloseHandler: function(oInput, oDate, bForFilter) {

		oInput = $j(oInput);

		// Wochentag vor den Kalender schreiben
		if(oInput.val()) {
			if(bForFilter) {
				this.prepareFilterSearch();
			} else if(oDate) {
				var iDayOfWeek = oDate.getDay();
				this.displayWeekDay(oInput, iDayOfWeek);
				this.displayCalendarAge(oInput);
			}
		}

		// Closehandler Hook
		// TODO: Entfernen (wird noch in TA verwendet)
		this.calendarCloseHandlerHook(oInput.get(0));

	},

	/**
	 * Wert von Kalender aktualisieren (.value = x)
	 *
	 * Kalendar muss informiert werden, dass Input sich verändert hat (wenn nicht change-Event),
	 * allerdings würde ein change()-Event auch weitere change-Observer auslösen. Zusätzlich werden
	 * Wochentag und Alter aktualisiert, wenn Felder vorhanden.
	 *
	 * @param {Element|jQuery} oInput
	 * @param {String} sValue Formatiertes Datum
	 */
	updateCalendarValue: function(oInput, sValue) {
		oInput = $j(oInput);

		if(sValue) {
			oInput.val(sValue);
		}

		var oDatepicker = oInput.data('datepicker');
		if(!oDatepicker) {
			console.error('updateCalendar: oInput has no datepicker!', oInput);
			return;
		}

		oDatepicker.update();

		// Manuell ausführen, da calendarCloseHandler mit Requests abgeleitet wurde
		// Eigentlich sollte das einfach mit change-Events ausgetauscht werden
		//this.calendarCloseHandler(oInput.get(0), oDatepicker.getDate());
		var oDate = oDatepicker.getDate();
		if(oDate) {
			this.displayWeekDay(oInput, oDatepicker.getDate().getDay());
			this.displayCalendarAge(oInput);
		}

	},

	buildDialogId: function(sDialogId) {
		var sId = 'dialog_wrapper_' + sDialogId + '_' + this.hash;
		return sId;
	},

	_fireEvent: function(eventType, element) {
		if(document.createEvent) {
			var evt = document.createEvent("Events");
			evt.initEvent(eventType, true, true);
			element.dispatchEvent(evt);
		} else if(document.createEventObject) {
			var evt = document.createEventObject();
			element.fireEvent("on" + eventType, evt);
		}
	},

	_evalJson: function(oJson) {

		var mReturn = null;

		var sJson = '';
		if(
			oJson && 
			oJson.responseText
		) {
			sJson = oJson.responseText;
		} else if(typeof oJson == 'object') {
			return oJson;
		}

		try {

			mReturn = sJson.evalJSON();

		} catch(e) {

			if(this.bReadyInitialized) {

				// Im Fehlerfall Ladebalken wieder ausblenden
				this.hideLoading();

				this.requestError(oJson, e);

				var bNewDialog = false;

				// Nur anzeigen, wenn es auch etwas anzuzeigen gibt (leer z.B. bei Debugger-Stop)
				if(this.bDebugMode && sJson) {
					bNewDialog = true;
					var aErrors = [this.getTranslation('json_error_occured'), sJson];
					this.displayErrors(aErrors, this.sCurrentDialogId, bNewDialog);
				}

			}

			mReturn = false;

		}

		return mReturn;

	},

	__pout: function(sOut) {
		if(this.bDebugMode) {
			__out(sOut);
		}
	},

	setDefaultSelected: function(oSelect, key) {
		for(i = 0; i < oSelect.length; i++) {
			if(oSelect.options[i].value == key) {
				oSelect.options[i].defaultSelected = true;
			} else {
				oSelect.options[i].defaultSelected = false;
			}
		}
	},

	resetSelect: function(oSelect) {
		for(i = 0; i < oSelect.length; i++) {
			if(oSelect.options[i].defaultSelected == true) {
				oSelect.options[i].selected = true;
			}
		}
	},

	/**
	 * Struktur der GUI-Definition eines Kontextmenüs für $j.contextMenu konvertieren
	 */
	createContextMenu: function(aData, oTr) {
		var aContextMenu = [];

		aData.each(function(oElement) {

			if(
				typeof oElement.type != 'undefined' &&
					oElement.type == 'separator'
				) {

				aContextMenu.push($j.contextMenu.separator);

			} else {

				var oMenu = {};

				oMenu[oElement.name] = {
					onclick: this.executeContextMenuEventClick.bind(this, null, null, oElement, oTr)
				};

				// Übergebene Klasse
				if(typeof oElement['class'] != 'undefined') {
					oMenu[oElement.name]['className'] = oElement['class'];
				}

				// Icon
				if(typeof oElement.icon != 'undefined') {
					oMenu[oElement.name].icon = oElement.icon;
				}

				// Deaktivierter Eintrag
				if(
					typeof oElement.disabled != 'undefined' &&
						oElement.disabled == true
					) {
					oMenu[oElement.name].disabled = true;
				}

				// Titel
				if(
					typeof oElement.title != 'undefined'
					) {
					oMenu[oElement.name].title = oElement.title;
				}

				// Color Icon
				if(
					typeof oElement.color_icon != 'undefined'
					) {
					oMenu[oElement.name].colorIcon = oElement.color_icon;
				}

				aContextMenu.push(oMenu);

			}

		}.bind(this));

		return aContextMenu;
	},

	/**
	 * Contextmenü-Event, beim Klicken
	 */
	executeContextMenuEventClick: function(menuItem, menu, oElement, oTr) {
		this.request('&task=contextMenu&' + Object.toQueryString(oElement));
	},

	prepareWDSearch: function(oFilter) {

		oFilter.stopObserving('keyup');
		Event.observe(oFilter, 'keyup', this.prepareSimilarityWDSearch.bindAsEventListener(this, oFilter, false));
		this.prepareSimilarityWDSearch(null, oFilter, true);

		oFilter.stopObserving('click');
		Event.observe(oFilter, 'click', function() {

			var sIDAddon = '_' + this.hash;

			var sTemp = oFilter.id;
			sTemp = sTemp.replace('wdsearch_' + this.hash, '');

			if(sTemp != "") {
				sIDAddon += sTemp;
			}

			if(
				this.oWDSearchBlink
				) {

				$H(this.oWDSearchBlink).each(function(aIntervall) {
					if(aIntervall[0] != sIDAddon) {
						clearInterval(aIntervall[1]);
						var oCurrentIntervalFilter = $('wdsearch' + aIntervall[0]);
						if(oCurrentIntervalFilter) {
							this.toggleWDSearchCursor(oCurrentIntervalFilter, true);
						}
						delete this.oWDSearchBlink[aIntervall[0]];
					}
				}.bind(this));
			}
		}.bind(this));

	},

	prepareSimilarityWDSearchDivs: function(oFilter) {

		var sIDAddon = '_' + this.hash;

		var sTemp = oFilter.id;
		sTemp = sTemp.replace('wdsearch_' + this.hash, '');

		if(sTemp != "") {
			sIDAddon += sTemp;
		}

		var aOffset = oFilter.positionedOffset();

		var sInput = 'wdsearch' + sIDAddon;
		var oInput = $(sInput);

		var sTextDiv = 'wdsearch_div_text' + sIDAddon;

		if(!$(sTextDiv)) {
			var oDiv = document.createElement('div');
			oDiv.id = sTextDiv;
			oDiv.className = 'wdsearch_div_text';
			oDiv.style.left = (aOffset[0] + 2) + 'px';
			if(oInput && oInput.style.width) {
				oDiv.style.width = oInput.style.width;
			}
			oFilter.insert({after: oDiv});
		}

		var sTextDiv2 = 'wdsearch_div_text_full' + sIDAddon;

		if(!$(sTextDiv2)) {
			var oDiv2 = document.createElement('div');
			oDiv2.id = sTextDiv2;
			oDiv2.className = 'wdsearch_div_text_full';
			if(oInput && oInput.style.width) {
				oDiv2.style.width = oInput.style.width;
			}
			oDiv.insert({after: oDiv2});
		}

		var sTextDiv3 = 'wdsearch_div_cursor' + sIDAddon;

		if(!$(sTextDiv3)) {
			var oDiv3 = document.createElement('div');
			oDiv3.id = sTextDiv3;
			oDiv3.className = 'wdsearch_div_cursor';
			oDiv2.insert({after: oDiv3});
		}

		var sTextDiv4 = 'wdsearch_div_results' + sIDAddon;

		if(!$(sTextDiv4)) {
			var oDiv4 = document.createElement('div');
			oDiv4.id = sTextDiv4;
			oDiv4.className = 'wdsearch_div_results';
			oDiv4.style = 'display:none';
			oDiv3.insert({after: oDiv4});
		}

	},

	toggleWDSearchCursor: function(oFilter, bForeHide) {

		var sIDAddon = '_' + this.hash;

		var sTemp = oFilter.id;
		sTemp = sTemp.replace('wdsearch_' + this.hash, '');

		if(sTemp != "") {
			sIDAddon += sTemp;
		}

		var sTextDiv = 'wdsearch_div_text' + sIDAddon;
		var oTextDiv = $(sTextDiv);

		if(oTextDiv) {

			var sInput = 'wdsearch' + sIDAddon;
			var oInput = $(sInput);
			//oInput.focus();

			var iSelectionEnd = oInput.selectionEnd;
			if(iSelectionEnd == 0 && oInput.value && oInput.value.length > 0){
				iSelectionEnd = oInput.value.length;
			}
			var sCurrentSelectedText = oInput.value.substring(0, iSelectionEnd);

			var aDim = this.measureWDSearchText(sCurrentSelectedText, oInput.style.fontSize, oInput.style);

			var aOffest = oTextDiv.positionedOffset();
			var aCOffest = oTextDiv.cumulativeOffset();

			var sTextCursorDiv = 'wdsearch_div_cursor' + sIDAddon;
			var oCursorDiv = $(sTextCursorDiv);

			if(oCursorDiv && oTextDiv) {
				var iLeft = (aOffest.left + aDim.width);
				var iCLeft = (aCOffest.left + aDim.width);
				oCursorDiv.style.left = iLeft + 'px';
				if(bForeHide) {
					oCursorDiv.hide();
				} else {
					oCursorDiv.toggle();
				}
			}

		}

	},

	writeWDSearchText: function(sText, sIDAddon) {

		if(!sIDAddon) {
			sIDAddon = '_' + this.hash;
		}

		var sTextDiv = 'wdsearch_div_text' + sIDAddon;
		var oTextDiv = document.getElementById(sTextDiv);
		oTextDiv.innerHTML = sText;
		oTextDiv.show();

		var sTextCursorDiv = 'wdsearch_div_cursor' + sIDAddon;
		var oCursorDiv = $(sTextCursorDiv);
		oCursorDiv.hide();

		var sTextFullDiv = 'wdsearch_div_text_full' + sIDAddon;
		var oTextFullDiv = $(sTextFullDiv);
		oTextFullDiv.hide();

	},

	writeWDSearchFullText: function(sText, sIDAddon) {

		if(!sIDAddon) {
			sIDAddon = '_' + this.hash;
		}

		var sTextDiv = 'wdsearch_div_text' + sIDAddon;
		var oTextDiv = document.getElementById(sTextDiv);

		var sValue = oTextDiv.innerHTML;

		var iPosString = sValue.lastIndexOf(',');
		var iPosString2 = sValue.lastIndexOf('OR');

		if(iPosString2 > iPosString) {
			iPosString = -1;
		}

		if(iPosString != -1) {
			sValue = sValue.substr(0, (iPosString + 1));
		} else if(iPosString2 != -1) {
			sValue = sValue.substr(0, (iPosString2 + 2));
		} else {
			sValue = '';
		}

		var sTemp = sValue.substr(-1);

		if(
			sTemp &&
				sTemp != ' '
			) {
			sValue += ' ';
		}

		oTextDiv.innerHTML = sValue;

		var aDim = oTextDiv.getDimensions();

		var sTextFullDiv = 'wdsearch_div_text_full' + sIDAddon;
		var oTextFullDiv = $(sTextFullDiv);
		oTextFullDiv.innerHTML = sText;
		oTextFullDiv.show();
		oTextFullDiv.style.left = oTextDiv.style.left;

	},

	goWDSearchSimilarityEntry: function(oFilter, sDirection) {

		var sIDAddon = '_' + this.hash;

		var sTemp = oFilter.id;
		sTemp = sTemp.replace('wdsearch_' + this.hash, '');

		if(sTemp != "") {
			sIDAddon += sTemp;
		}

		var iEntryNumber = this.iLastSelectedWDSearchSimilarityEntry;
		var iLastEntryNumber = iEntryNumber;

		if(
			iEntryNumber === null ||
				iEntryNumber === undefined ||
				iEntryNumber === false
			) {
			iEntryNumber = iLastEntryNumber = 0;
		} else {
			if(sDirection == 'down') {
				iEntryNumber += 1;
			} else {
				iEntryNumber -= 1;
			}
		}

		var oEntry = $('wdsearch_similarity_entry_' + iEntryNumber + sIDAddon);

		if(oEntry) {

			this.iLastSelectedWDSearchSimilarityEntry = iEntryNumber;

			var oLastEntry = $('wdsearch_similarity_entry_' + iLastEntryNumber + sIDAddon);
			oLastEntry.removeClassName('selected');

			oEntry.addClassName('selected');

			if(
				this.aLastWDSearchSimilarityResult &&
					this.aLastWDSearchSimilarityResult[iEntryNumber]
				) {
				this.writeWDSearchFullText(this.aLastWDSearchSimilarityResult[iEntryNumber].value, sIDAddon);
			}
		}

	},

	goWDSearchSimilarityEntryUp: function(oFilter) {
		this.goWDSearchSimilarityEntry(oFilter, 'up');
	},

	goWDSearchSimilarityEntryDown: function(oFilter) {
		this.goWDSearchSimilarityEntry(oFilter, 'down');
	},

	prepareSimilarityWDSearch: function(oEvent, oFilter, bInit) {

		if(
			!bInit ||
			(
				oFilter.value &&
				oFilter.value.length > 0
			)
		){
			var sIDAddon = '_' + this.hash;

			var sTemp = oFilter.id;
			sTemp = sTemp.replace('wdsearch_' + this.hash, '');

			if(sTemp != "") {
				sIDAddon += sTemp;
			}

			var sInput = 'wdsearch' + sIDAddon;
			var oInput = $(sInput);

			if(
				oEvent &&
				(
					(
						oEvent.keyCode == 13 && // enter
							this.aLastWDSearchSimilarityResult && // suchauswahl
							(
								this.iLastSelectedWDSearchSimilarityEntry === 0 ||
								this.iLastSelectedWDSearchSimilarityEntry > 0
							)
					) ||
					oEvent.keyCode == 39 // rechts
				) &&
				this.aLastWDSearchSimilarityResult &&
				this.aLastWDSearchSimilarityResult[this.iLastSelectedWDSearchSimilarityEntry]
			) {
				this.addWDSearchItem(this.aLastWDSearchSimilarityResult[this.iLastSelectedWDSearchSimilarityEntry], sIDAddon);
				this.aLastWDSearchSimilarityResult = false;
				this.iLastSelectedWDSearchSimilarityEntry = null;
			} else if(oEvent && oEvent.keyCode == 13) { // enter
				this.startWDSearch(sIDAddon);

			} else if(oEvent && oEvent.keyCode == 38) { // up
				this.goWDSearchSimilarityEntryUp(oFilter);
			} else if(oEvent && oEvent.keyCode == 40) { // down
				this.goWDSearchSimilarityEntryDown(oFilter);
			} else {

				this.prepareSimilarityWDSearchDivs(oFilter);

				var sTextDiv = 'wdsearch_div_text' + sIDAddon;
				var oTextDiv = $(sTextDiv);

				if(oTextDiv) {

					this.writeWDSearchText(oFilter.value, sIDAddon);

					if(!this.oWDSearchBlink) {
						this.oWDSearchBlink = [];
					}

					if(!this.oWDSearchBlink[sIDAddon] && !bInit) {
						this.oWDSearchBlink[sIDAddon] = window.setInterval(this.toggleWDSearchCursor.bind(this, oFilter), 400);
					}

					if(this.prepareSimilarityWDSearchObserver) {
						clearTimeout(this.prepareSimilarityWDSearchObserver);
					}

					this.prepareSimilarityWDSearchObserver = setTimeout(this.executeSimilarityWDSearch.bind(this, oFilter), 500);

				}
			}
		}

		if(!bInit){
			oInput.focus();
		}

	},

	executeSimilarityWDSearch: function(oFilter) {

		var sIDAddon = '_' + this.hash;

		var sTemp = oFilter.id;
		sTemp = sTemp.replace('wdsearch_' + this.hash, '');

		if(sTemp != "") {
			sIDAddon += sTemp;
		}

		if(!oFilter) {
			var sFilter = 'wdsearch' + sIDAddon;
			oFilter = $(sFilter);
		}

		var sSearchValue = oFilter.value;
		var sSearchFull = sSearchValue;
		var aTemp = sSearchValue.split(/,|OR/);

		if(
			aTemp.length &&
				aTemp[aTemp.length - 1]
			) {
			sSearchValue = aTemp[aTemp.length - 1];
			sSearchValue = sSearchValue;
			var sTemp = sSearchValue.replace(' ', '');
			if(sTemp != "") {
				var sParam = '&task=startSimilarityWDSearch&search=' + sSearchFull + '&element=' + oFilter.id;
				this.request(sParam);
			}
		}
	},

	executeSimilarityWDSearchCallback: function(objData) {

		var aResult = objData.data.similarity;

		var sIDAddon = '_' + this.hash;

		if(objData.data.id) {
			var sTemp = objData.data.id;
			sTemp = sTemp.replace('wdsearch_' + this.hash, '');

			if(sTemp != "") {
				sIDAddon += sTemp;
			}
		}

		var sInput = 'wdsearch' + sIDAddon;
		var sTextDiv4 = 'wdsearch_div_results' + sIDAddon;
		var sCursorDiv = 'wdsearch_div_cursor' + sIDAddon;

		var oInput = $(sInput);
		var oResultDiv = $(sTextDiv4);
		var oCursorDiv = $(sCursorDiv);
		var aDim = oInput.getDimensions();

		var iTop = aDim.height - 1;//aOffset.top + aDim.height - 1; rausgenommen, eltern div muss einfach pos. relativ haben :)

		oResultDiv.style.top = iTop + 'px';
		oResultDiv.style.left = oCursorDiv.style.left;
		oResultDiv.innerHTML = '';
		var i = 0;

		this.aLastWDSearchSimilarityResult = aResult;
		this.iLastSelectedWDSearchSimilarityEntry = false;

		aResult.each(function(aData) {
			var oDiv = document.createElement('div');
			var sDescription = aData.value;
			if(aData.field_name) {
				sDescription += '<span class="info">(' + aData.field_name + ')</span>';
			}
			oDiv.innerHTML = sDescription;
			oDiv.id = 'wdsearch_similarity_entry_' + i + sIDAddon;
			oResultDiv.appendChild(oDiv);
			oResultDiv.show();

			Event.observe(oDiv, 'click', function() {
				this.addWDSearchItem(aData, sIDAddon);
			}.bind(this));

			i++;
		}.bind(this));

	},

	addWDSearchItem: function(aData, sIDAddon) {

		if(!sIDAddon) {
			sIDAddon = this.hash;
		}

		var sInput = 'wdsearch' + sIDAddon;
		var sResult = 'wdsearch_div_results' + sIDAddon;

		if(!$(sResult)) {
			this.prepareSimilarityWDSearchDivs($(sInput));
		}

		var oInput = $(sInput);
		var sCurrentValue = oInput.value;
		var sString = sCurrentValue;
		var iPosString = sCurrentValue.lastIndexOf(',');
		var iPosString2 = sCurrentValue.lastIndexOf('OR');

		if(iPosString2 > iPosString) {
			iPosString = -1;
		}

		if(iPosString != -1) {
			sString = sString.substr(0, (iPosString + 1));
		} else if(iPosString2 != -1) {
			sString = sString.substr(0, (iPosString2 + 2));
		} else {
			sString = '';
		}

		if(
			sString != "" &&
				iPosString2 != -1
			) {
			sString += " ";
		}

		sString = sString + aData.value + ',';

		oInput.value = sString;

		this.writeWDSearchText(sString, sIDAddon);

		this.executeSimilarityWDSearch(oInput);

		if($(sResult)) {
			$(sResult).hide();
		}

		var sTextFullDiv = 'wdsearch_div_text_full' + sIDAddon;
		var oTextFullDiv = $(sTextFullDiv);
		oTextFullDiv.hide();

		oInput.focus();
	},

	startWDSearch: function(sIDAddon, sAdditionalParam) {

		if(!sIDAddon) {
			sIDAddon = '_' + this.hash;
		}

		this.executeFilterSearch(false, this.hash, sAdditionalParam);
		var sResult = 'wdsearch_div_results' + sIDAddon;
		if($(sResult)) {
			$(sResult).hide();
		}
		var sInput = 'wdsearch' + sIDAddon;
		var oInput = $(sInput);
		if(oInput) {
			oInput.focus();
		}
	},

	measureWDSearchText: function(pText, pFontSize, pStyle) {
		var lDiv = document.createElement('lDiv');

		document.body.appendChild(lDiv);

		if(pStyle != null) {
			lDiv.style = pStyle;
		}
		lDiv.style.fontSize = "" + pFontSize + "px";
		lDiv.style.position = "absolute";
		lDiv.style.left = -1000;
		lDiv.style.top = -1000;

		pText = pText.replace(' ', '&nbsp;');

		lDiv.innerHTML = pText;

		var lResult = {
			width: lDiv.clientWidth,
			height: lDiv.clientHeight
		};

		document.body.removeChild(lDiv);
		lDiv = null;

		return lResult;
	},

	testBoxModel: function() {

		// Wenn Parent GUI vorhanden, nehme die Werte von dieser
		if(
			this.sParentGuiHash
		) {
			var oParentGui = this.getOtherGuiObject(this.sParentGuiHash);
			this.iTableWidthDiff = oParentGui.iTableWidthDiff;
			this.aTableCellOffset = oParentGui.aTableCellOffset;
			this.iScrollBarWidth = oParentGui.iScrollBarWidth;
			this.bBoxModelTested = oParentGui.bBoxModelTested;
		}

		if(
			this.bBoxModelTested &&
			this.bBoxModelTested == true
		) {
			return;
		}

		/**
		 * Tabellenbreite testen
		 */
		var oContainer = $('guiScrollBody_' + this.hash);
		if(!oContainer) {
			oContainer = $('guiScrollBody');
		}

		if(oContainer) {
			// Construct the test element
			var oDiv = document.createElement("div");
			oDiv.id = 'test_container';
			oDiv.style.width = '140px';

			oDiv.innerHTML = '<table id="test_table_head" class="guiTableHead" style="width: 140px;"><colgroup><col style="width: 140px;" /></colgroup><tr><th id="test_table_th">Test</th></tr></table><table class="table table-bordered table-hover table-striped guiTableBody" id="test_table_body" style="width: 140px;"><colgroup><col style="width: 140px;" /></colgroup><tr><td id="test_table_td">Test</td></tr></table>';

			oContainer.appendChild(oDiv);

			var iWidthHead = $('test_table_head').getWidth();
			var iWidthBody = $('test_table_body').getWidth();

			this.iTableWidthDiff = iWidthHead - iWidthBody;

			// Testelement entfernen
			oContainer.removeChild(oDiv);

			/**
			 * Zellenbreite testen
			 */

			// Construct the test element
			var oDiv = document.createElement("div");
			oDiv.id = 'test_container';
			oDiv.style.width = '140px';

			oDiv.innerHTML = '<table class="guiTableHead"><colgroup><col style="width: 140px;" /></colgroup><tr><th id="test_table_th" style="border: 1px solid black;">Test</th></tr></table><table class="table table-bordered table-hover table-striped guiTableBody"><colgroup><col style="width: 140px;" /></colgroup><tr><td id="test_table_td" style="border: 1px solid black;">Test</td></tr></table>';

			oContainer.appendChild(oDiv);

			var iThWidth = $('test_table_th').getWidth();
			var iTdWidth = $('test_table_td').getWidth();

			// Testelement entfernen
			oContainer.removeChild(oDiv);

			// Wenn hier 0 raus kommt, dann ist die Tabelle nicht sichtbar
			if(
				iThWidth == 0 ||
				iTdWidth == 0
			) {
				return false;
			}

			// Wenn beides gleich ist, dann BoxModel
			if(
				iThWidth == 139 &&
				iTdWidth == 139
			) {
				this.aTableCellOffset = [0, 0];
			} else {
				this.aTableCellOffset = [140 - iThWidth, 140 - iTdWidth];
			}

		}

		/**
		 * Breite der Scrollbar ermitteln
		 */
		var oContainer = document.body;

		var oDiv1 = document.createElement("div");
		oDiv1.style.width = '100%';

		oDiv1.innerHTML = '<div id="test_container"></div>';

		oContainer.appendChild(oDiv1);

		var oDiv2 = document.createElement("div");
		oDiv2.style.overflow = 'scroll';
		oDiv2.style.width = '100%';

		oDiv2.innerHTML = '<div id="test_container_scroll"></div>';

		oContainer.appendChild(oDiv2);

		// Breite ermitteln
		var iWidth1 = $('test_container').getWidth();
		var iWidth2 = $('test_container_scroll').getWidth();

		// Testelemente entfernen
		oContainer.removeChild(oDiv1);
		oContainer.removeChild(oDiv2);

		this.iScrollBarWidth = iWidth1 - iWidth2;

		// Flag setzen, dass dieser Test schon durchgelaufen ist
		this.bBoxModelTested = true;

	},

	prepareDependencyVisibility: function(oElement, aValues, sElement, iIsIdElement, iIsClassElement) {

		var bHideElement = true;

		if(oElement) {
			if(
				aValues &&
				aValues.length > 0
			) {

				aValues.each(function(mValue) {

					var checkValue=true;

					// Fall 0 soll auch funktionieren (!'0' === false, aber !0 === true)
					if(oElement.type !== 'checkbox') {
						mValue = mValue.toString();
					}

					// Mit einem vorgestellten '!' kann man die Abfrage umdrehen, also bei dem angegebenen Wert das Feld ausblenden
					var negate = false;
					if(mValue[0] === '!') {
						mValue = mValue.slice(1);
						negate = true;
					}

					if(
						(
							oElement.tagName == 'INPUT' &&
							oElement.type == 'checkbox' &&
							(
								(
									oElement.checked &&
										mValue
									) ||
									(
									!oElement.checked &&
										!mValue
									)
								)
						) ||
						(
							(
								oElement.tagName != 'INPUT' ||
								oElement.type != 'checkbox'
							) &&
							(
								(
									oElement.tagName == 'SELECT' &&
									$F(oElement).length && (
										(
											// Bei String muss der String verglichen werden
											!Array.isArray($F(oElement)) &&
											$F(oElement) == mValue
										) ||
										(
											// Bei Multiselect indexOf (in_array)
											Array.isArray($F(oElement)) &&
											$F(oElement).indexOf(mValue) != -1
										)
									)
								) ||
								$F(oElement) == mValue
							)
						)
					) {
						checkValue=false;
					}
					
					if(checkValue === negate) {
						bHideElement = false;	
					}
					
				}.bind(this));
			}
		}

		if(!this.aDependencyVisibilityRequiredFields) {
			this.aDependencyVisibilityRequiredFields = [];
		}

		var sElementId = '';
		var oChildElement;
		var aChildElements;
		if(iIsIdElement) {
			sElementId = sElement;
			aChildElements = [$(sElementId)];
		} else if(iIsClassElement) {
			// Prüfen, ob in JoinContainer
			var oGUIDialogJoinedObjectContainerRow = oElement.up('.GUIDialogJoinedObjectContainerRow');
			if(oGUIDialogJoinedObjectContainerRow) {
				aChildElements = [oGUIDialogJoinedObjectContainerRow.down('.'+sElement)];
			} else {
				aChildElements = $j('.'+sElement).get();
			}
		} else {
			sElementId = 'save[' + this.hash + '][' + this.sCurrentDialogId + '][' + sElement + ']';
			// Fallback für Dialoge die nicht an eine Liste gebunden sind
			if(!$(sElementId)) {
				sElementId = 'saveid[' + sElement + ']';	
			}
			aChildElements = [$(sElementId)];
		}

		if (aChildElements.length === 0 || !aChildElements[0]) {
			console.warn('Could not find any element for event execution', sElementId, oElement);
		}

		aChildElements.each(function(oChildElement) {

			if(oChildElement) {

				var oRequiredClassObject = oChildElement;

				if(oChildElement.tagName == 'DIV') {

					oRequiredClassObject = oChildElement.down('input');

					if(!oRequiredClassObject) {
						oRequiredClassObject = oChildElement.down('select');
					}

					if(!oRequiredClassObject) {
						oRequiredClassObject = oChildElement.down('textarea');
					}

				}

				if(
					oRequiredClassObject &&
					oRequiredClassObject.hasClassName('required')
				) {
					this.aDependencyVisibilityRequiredFields[oRequiredClassObject.id] = 1;
				}

				var oToggleElement;

				if(
					oChildElement &&
					!iIsIdElement &&
					!iIsClassElement
				) {
					oToggleElement = oChildElement.up('.GUIDialogRow');
				} else if(oChildElement) {
					oToggleElement = oChildElement;
				}

				// @TODO Das sollte über das Event passieren, und nicht über redundantes show/hide
				if(bHideElement) {
					oToggleElement.hide();
				} else {
					oToggleElement.show();
				}

				if(
					oRequiredClassObject &&
					this.aDependencyVisibilityRequiredFields &&
					this.aDependencyVisibilityRequiredFields[oRequiredClassObject.id] &&
					this.aDependencyVisibilityRequiredFields[oRequiredClassObject.id] == 1
				) {
					if(bHideElement) {
						oRequiredClassObject.removeClassName('required');
						//damit auf ausgeblendete Felder kein 'required' gesetzt wird
						oRequiredClassObject.addClassName('block_auto_required');
					} else {
						oRequiredClassObject.addClassName('required');
						oRequiredClassObject.removeClassName('block_auto_required');
					}
				}

			}

		}.bind(this));

		this.prepareDependencyVisibilityHook(oElement, aValues, sElement, iIsIdElement, bHideElement);

	},

	prepareDependencyVisibilityHook: function(oElement, aValues, sElement, iIsIdElement, bHideElement) {

	},

	/**
	 * @param {jQuery} oElement
	 */
	registerLoadingIndicatorIcons: function(oElement) {
		oElement.find('[data-type="loading-indicator"]').each(function(iIndex, oIcon) {
			this.aLoadingIndicatorIcons.push(oIcon);
		}.bind(this));
	},

	getLoadingIndicatorIcon: function(sHandler, sId) {
		return $j('[data-type="loading-indicator"][data-handler="'+sHandler+'"][data-id="'+sId+'"]');
	},

	requestLoadingIndicatorIconStatus: function() {

		// Wenn es keine Elemente gibt muss auch nichts gemacht werden
		if(this.aLoadingIndicatorIcons.length == 0) {
			return;
		}

		var oPostData = {elements: {}};

		// Daten jedes einzelnen Icons sammeln
		this.aLoadingIndicatorIcons.each(function(oIcon) {
			oIcon = $j(oIcon);

			var sHandler = oIcon.attr('data-handler');
			var sId = oIcon.attr('data-id');

			if(!oPostData.elements[sHandler]) {
				oPostData.elements[sHandler] = [];
			}

			oPostData.elements[sHandler].push(sId);
		});

		// Request
		$j.ajax({
			url: '/wdmvc/core/loading-indicator/status',
			data: oPostData,
			type: 'POST',
			beforeSend: function() {
				if(this.bLockLoadingIndicatorIconRequest) {
					return false;
				}

				this.bLockLoadingIndicatorIconRequest = true;
			}.bind(this),
			success: function(oResponse) {
				$j.each(oResponse.data, function(sHandler, oIdData) {
					$j.each(oIdData, function(sId, oData) {
						this.processLoadingIconResponse(sHandler, sId, oData, oResponse);
					}.bind(this));
				}.bind(this));
			}.bind(this),
			complete: function() {
				this.bLockLoadingIndicatorIconRequest = false;
			}.bind(this)
		});
	},

	processLoadingIconResponse: function(sHandler, sId, oData, oResponse) {

		var oIcon = this.getLoadingIndicatorIcon(sHandler, sId);

		// Alle aktuellen Icons durchlaufen und prüfen, ob Icons gelöscht werden können
		$j.each(this.aLoadingIndicatorIcons, function(iIndex, oTmpIcon) {
			if(
				(
					// DOM-Element existiert nicht mehr
					oTmpIcon &&
					!$j.contains(document, oTmpIcon)
				) || (
					// Status ist ready oder fail
					$j.inArray(oData.status, ['ready', 'fail']) !== -1 &&
					oIcon.get(0) == oTmpIcon
				)
			) {
				this.aLoadingIndicatorIcons.splice(iIndex, 1);
			}
		}.bind(this));

		// Icon setzen (anhand des Mappings)
		if(oResponse.icons[sHandler][oData.status]) {
			oIcon.removeClass(Object.values(oResponse.icons[sHandler]).join(' '));
			oIcon.addClass(oResponse.icons[sHandler][oData.status]);
		}

		// Style setzen, wenn übergeben
		if(oData.style) {
			oIcon.css(oData.style);
		}

		// Wenn URL, dann auch setzen
		if(oData.url) {
			oIcon.click(function() {
				window.open(oData.url);
				return false;
			});
		}
	},


	/**
	 * definiert die große des Fortschritbalkens
	 * und füllt die hidden felder
	 */
	setIndexStackLoadingBar: function(iStep) {

		var bFinished   = false;

		if($('index_completed_hidden')) {

			var iFactor	 = 400;
			var iComplete   = $('index_completed_hidden').value;
				iComplete   = parseInt(iComplete);
			var iTotal	  = $('index_total_hidden').value;
			var oLoadingBar = $('index_stack_loader_bar');

			if(iTotal > 0) {
				iFactor = 400 / parseInt(iTotal);
			}

			iComplete = iComplete + iStep;
			if(iComplete >= iTotal){
				iComplete = iTotal;
				bFinished = true;
			}

			var iWidth	  = iComplete * iFactor;
			oLoadingBar.style.width = iWidth+'px';

			$('index_stack_completed').innerHTML	= iComplete;
			$('index_completed_hidden').value	   = iComplete;
		} else {
			bFinished = true;
		}

		return bFinished;
	},

	/**
	 * Zeigt den fortschritt an und blendet das eigen aus  und löd die liste neu falls die aktuallisierung fertig ist
	 */
	executeIndexStackCallback: function(aData){

		var bFinished = this.setIndexStackLoadingBar(10);

		if(
			!bFinished &&
			aData.error.length === 0
		){
			this.executeIndexStack(aData);
		} else {
			this.loadTable(false);
			var oIcon = $('executeIndexStack__'+this.hash);
			if(oIcon){

				var oIconDiv = oIcon.up('div');

				if(
					oIconDiv &&
					oIconDiv.previous()
				){
					oIconDiv.previous().remove();
				}

				if(oIconDiv){
					oIconDiv.previous().remove();
					oIconDiv.remove();
				}
			}
		}
	},

	/**
	 * schickt den request für das abarbeiten des Stacks ab
	 */
	executeIndexStack: function(aData){
		var sIndex	  = $('index_name_hidden').value;
		var sParam = '&index='+sIndex;
		this.request(sParam, '/wdmvc/gui2/index/executeStack', null, null, null, false);
	},

	/**
	 * Dialog-Save-Field im aktuellen Dialog suchen (sofern ID nicht manuell überschrieben wurde)
	 *
	 * @param {String} sColumn
	 * @param {String} [sAlias]
	 * @returns {jQuery}
	 */
	getDialogSaveField: function(sColumn, sAlias) {

		var sId = '#save\\[' + this.hash + '\\]\\[' + this.sCurrentDialogId + '\\]\\[' + sColumn + '\\]';
		if(sAlias != undefined) {
			sId += '\\[' + sAlias + '\\]';
		}

		return $j(sId);

	},

	setDialogSaveFieldValue: function(sColumn, sAlias, mValue) {
	
		var field = this.getDialogSaveField(sColumn, sAlias);
		
		var oDatepicker = field.data('datepicker');
		if(oDatepicker) {
			this.updateCalendarValue(field, mValue);
		} else {
			field.val(mValue);
		}

	},

	resizeTextarea : function(oElement, defaultHeight) {

		if(isNaN(defaultHeight)) {
			defaultHeight = $j(oElement).data('defaultheight');
		}

		if(isNaN(defaultHeight)) {
			defaultHeight = 30;
		}

		$j(oElement).data('defaultheight', defaultHeight);

		oElement.style.height = defaultHeight+'px';

		scrollHeight = oElement.scrollHeight;

		if(!scrollHeight) {
			scrollHeight = this.getHiddenElementHeight(oElement);
		}

		// Von 15 auf 17 geändert wg. Chrome
		if(scrollHeight > defaultHeight) {
			var height = (scrollHeight+2);
			if(height > 200) {
				height = 200;
			} else if(height < defaultHeight) {
				height = defaultHeight;
			}
			oElement.style.height = height + "px";
		}
	},

	initializeAutoheightTextareas: function(aData) {
		$j('#dialog_wrapper_' + aData.id + '_' + this.hash + ' textarea.autoheight').each(function(i, oElement) {

			this.resizeTextarea(oElement);
			$j(oElement).unbind('keyup');
			$j(oElement).keyup(function(oEvent) {
				this.resizeTextarea(oEvent.target);
			}.bind(this));

		}.bind(this));

	},

	/**
	 * Klappt nur bei normalen Eingabefeldern in Dialogen
	 * @param Object element
	 * @returns {Number|h}
	 */
	getHiddenElementHeight : function(element){

		var tempId = 'tmp-'+Math.floor(Math.random()*99999);//generating unique id just in case
		$j(element).closest('.GUIDialogRow').clone()
		.css('position','relative')
		.css('height','30px')
		//inject right into parent element so all the css applies (yes, i know, except the :first-child and other pseudo stuff..
		.appendTo($j(element).closest('.dialog-content'))
		.css('left','-10000em')
		.addClass(tempId).show();
		if($j('.'+tempId+' '+element.tagName).length > 0) {
			h = $j('.'+tempId+' '+element.tagName).get(0).scrollHeight;
			$j('.'+tempId).remove();
			return h;
		}
	},

	addAPIMenu: function() {

		// Nur für die Haupt-GUI erstmal
		if($j('#api_menu').length > 0) {
			return;
		}

		var elemDiv = document.createElement('div');
		elemDiv.id = 'api_menu';
		elemDiv.innerHTML = '<i class="fa fa-cloud-upload" aria-hidden="true"></i>';
		document.body.appendChild(elemDiv);

		$j('#api_menu').click(function() {

			var sHtml = '<p>To retrieve the current results in JSON format, please use the following information.</p>';
			sHtml += '<dl><dt>Endpoint</dt><dd>'+window.location.protocol+'//'+window.location.host+'/api/1.0/gui2/'+this.hash+'/search</dd>';
			sHtml += '<dt>Post</dt><dd>_token=API_TOKEN'+this.getFilterparam()+'</dd></dl>';

			this.openDefaultMessageBox('api_menu_dialog', 'API settings', sHtml, false);

		}.bind(this));

	}

});

// TODO Entfernen
if(typeof HTMLSelectElement != 'undefined') {
	HTMLSelectElement.prototype.updateValue = function(mValue, bHighlight) {

		if(bHighlight == undefined) {
			bHighlight = true;
		}

		var oElement = this;
		var mOrginalValue = $F(this);
		oElement.value = mValue;
		if(
			mValue != mOrginalValue &&
				bHighlight
			) {
			oElement.highlight();
		}

	}
}

// TODO Entfernen
if(typeof HTMLTextAreaElement != 'undefined') {
	HTMLTextAreaElement.prototype.updateValue = function(mValue, bHighlight) {
		var oElement = this;

		if(bHighlight == undefined) {
			bHighlight = true;
		}

		var mOrginalValue = $F(this);
		oElement.value = mValue;

		var oEditor = tinyMCE.get(oElement.id);
		if(oEditor) {

			oEditor.setContent(mValue);

		}

		if(
			mValue != mOrginalValue &&
				bHighlight
			) {
			oElement.highlight();
		}

	}
}

// TODO Entfernen
if(typeof HTMLInputElement != 'undefined') {
	HTMLInputElement.prototype.updateValue = function(mValue, bHighlight) {

		if(bHighlight == undefined) {
			bHighlight = true;
		}

		var oElement = this;
		var mOrginalValue = $F(this);

		oElement.value = mValue;

		var oDatepicker = $j(oElement).data('datepicker');
		if(oDatepicker) {
			oDatepicker.update();
		}

		if(
			mValue != mOrginalValue &&
			bHighlight
		) {
			oElement.highlight();
		}

	}
}

// TODO Entfernen
if(typeof HTMLElement != 'undefined') {
	HTMLElement.prototype.highlight = function() {
		var oElement = this;
		var sScope = '';

		if(oElement.id) {
			sScope = oElement.id;
		}

		$j(oElement).effect('highlight', {}, 2000);

		//new Effect.Highlight(oElement, {queue: {position: 'end', scope: sScope}});
		//new Effect.Highlight(oElement, {queue: {position: 'end', scope: sScope}});

	}
}

function go(url) {
	document.location.href = url;
}
