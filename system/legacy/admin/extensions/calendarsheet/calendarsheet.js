if(!aCalendar) {
	var aCalendar = new Array();
}

var CalendarSheet = Class.create({

	initialize: function(sHash, sInstanceHash, sDateStart, sDateEnd, oOptions) {

		this.hash = sHash;
		this.instance_hash = sInstanceHash;
		this.sDateStart = sDateStart;
		this.sDateEnd = sDateEnd;
		this.iStartWeekDay = parseInt(oOptions.iStartWeekDay);
		this.iDefaultBreak = oOptions.iDefaultBreak;
		this.iShowConfirm = oOptions.iShowConfirm;
		this.sActiveClass = oOptions.sActiveClass;
		this.sContainerID = oOptions.sContainerID;
		this.sGuiHash = oOptions.sGuiHash;
		this.iPreventInit = oOptions.iPreventInit;
		this.iPingInterval = 0;
		this.aTooltips = [];
		this.aRequestStack = [];
		
		var oDate = new Date();
		var mMonth = oDate.getMonth() + 1;
		var mDay = oDate.getDate();
		if(mMonth < 10) {
			mMonth = '0' + mMonth;
		}
		
		if(mDay < 10) {
			mDay = '0' + mDay;
		}
		this.today = oDate.getFullYear() + '-' + mMonth + '-' + mDay;
		
		this.oPing = new PeriodicalExecuter(this.executePing.bind(this), 30);
		
		
		if(this.iPreventInit === 0) {
			this.getStructure();
		}
		
	},
	
	/**
	 * Holt die Struktur des Kalenders.
	 * Sollte iPreventInit auf 1 stehen, muss diese Methode manuell aufgerufen werden.
	 * Ein möglicher Gui-Hash wird in der request-Methode automatisch mit angehangen.
	 */
	getStructure: function(sParam)
	{
		var sRequest = '&task=getInitData&start=' + this.sDateStart + '&end=' + this.sDateEnd;
		
		if(sParam) {
			sRequest += sParam;
		}
		
		this.request(sRequest);
	},
	
	/**
	 * Methode zum Abstrahieren (WRAPPER), was mit dem Kalenderobjekt geschehen soll
	 * 
	 * Als Parameter werden die Jahreskalender in einem Array übergeben.
	 */
	display: function(aCalendars)
	{
		var oContainer = $(this.sContainerID);
		
		oContainer.childElements().each(function(oChild) {
			oChild.remove();
		});
		
		aCalendars.each(function(oCalendar) {
			oContainer.appendChild(oCalendar);
		}.bind(this));
		
	},

	/**
	 * Bereitet die Basis für einen Minikalender (Monat) vor.
	 */
	_getBase: function()
	{
		if(!this.oBase) {
			this.oBase = {
				table: new Element('table'),
				caption: new Element('caption'),
				tr: new Element('tr'),
				th: new Element('th'),
				td: new Element('td')
			};
		}
		
		return this.oBase;
	},
	
	/**
	 * Läuft die Jahre durch und erstellt die Monate im Durchlauf.
	 */
	generate: function(oData)
	{
		var aCalendars = new Array();
		var oDiv = new Element('div');
		var oCalendarToCopy = oDiv.clone();
		oCalendarToCopy.addClassName('calendarSheet');
		
		Object.keys(oData).each(function(iYear) {
			
			var oCalendar = oCalendarToCopy.clone();
			oCalendar.id = 'calendar_' + this.hash + '_' + this.instance_hash + '_' +iYear;
			
			var aMonths = Object.keys(oData[iYear]['months']);
			var iCounter = 0;
			
			var bCheckBreak = this.iDefaultBreak;
			if(this.iDefaultBreak) {
				var iHalfMonthCount = Math.round(aMonths.length / 2);
			}
			
			aMonths.each(function(iMonth) {
				
				if(
					bCheckBreak &&
					iCounter >= iHalfMonthCount
				) {
					oCalendar.appendChild(oDiv.clone());
					bCheckBreak = false;
				}
				try {
					oCalendar.appendChild(this.generateMonth(iMonth, iYear, oData[iYear]));
				} catch (e) {
					console.debug(e);
				}
				
				
				++iCounter;
				
			}.bind(this));
			
			oCalendar.appendChild(oDiv.clone());
			aCalendars.push(oCalendar);
			
		}.bind(this));
		
		this.display(aCalendars);
	},
	
	/**
	 * Generiert einen Monat.
	 */
	generateMonth: function(iMonth, iYear, oYearData)
	{
		var oBase = this._getBase();
		
		var iAddCWRow = 1
		if(this.iStartWeekDay != 1) {
			iAddCWRow = 0;
		}
		
		var aPreparedData = this.prepareMonthData(iMonth, oYearData);
		
		var oTable = oBase.table.clone();
		var oCaption = oBase.caption.clone();
		oCaption.update(this.translations.months[iMonth] + ' ' + iYear);
		oTable.appendChild(oCaption);

		var bHeadRow = true;
		
		for(var iTr = 0; iTr < 7; ++iTr) {
			var oTr = oBase.tr.clone();
			for(var iTd = 0; iTd < 7 + iAddCWRow; ++iTd) {
				
				if(bHeadRow) {
					oTd = oBase.th.clone();
					oTd.update(this.aHeadTranslations[iTd]);
				} else {
					oTd = oBase.td.clone();
					
					var mDay = aPreparedData[iTr - 1][iTd];
					if(typeof(mDay) == 'object') {
						
						oTd.id = 'calendar_' + this.hash + '_' + this.instance_hash + '_day_' + mDay.date;
						oTd.addClassName('day');
						
						if(mDay['class'] !== '') {
							oTd.addClassName(mDay['class']);
						}
						
						var mDay_ = mDay.date.split('-')[2];
						
						if(mDay.date == this.today) {
							oTd.addClassName('dayToday');
						}
						
						// Event setzen
						if(
							typeof mDay.event['function'] != 'undefined' && 
							mDay.event['function'] != ''
						) {
							
							var sFunction = mDay.event['function'];
							
							if(
								typeof mDay.event['type'] != 'undefined' &&
								mDay.event['type'] == 'gui'
							) {
								Event.observe(oTd, 'click', function() {
									var aArguments = $A(arguments);
									eval("aGUI['" + this.sGuiHash + "']." + sFunction + "('" + aArguments[1] + "', '" + aArguments[2] + "', '" + this.hash + "', '" + this.instance_hash + "')");
								}.bindAsEventListener(this, mDay.date, oTd.id));
								
							} else {
								Event.observe(oTd, 'click', function() {
									var aArguments = $A(arguments);
									eval(sFunction + "('" + aArguments[1] + "', '" + aArguments[2] + "')");
								}.bindAsEventListener(this, mDay.date, oTd.id));
								
							}
							
							// Möglichen Tooltip setzen
							if(mDay.tooltip) {								
								this.setToolTip(oTd, mDay.tooltip.toString());
							}
							
						}
						
						mDay = mDay_.replace(/^(0+)/g, '');
						
					} else {
						oTd.addClassName('dayGray');
					}
					
					// Hintergrund der KW setzen
					if(
						iTd === 0 && 
						this.iStartWeekDay === 1
					) {
						oTd.addClassName('head');
					}
					
					oTd.update(mDay);
					
				}
				
				oTr.appendChild(oTd);
			}
			bHeadRow = false;
			oTable.appendChild(oTr);
		}
				
		return oTable;
	},
	
	/**
	 * Methode, die aufgerufen wird, um einen Tag zu updaten
	 */
	updateDay: function(sDate, sId, sParam)
	{
		var sParams = '&task=updateDay&date=' + sDate + '&objectid=' + sId;
		
		var oDay = $(sId);
		if(oDay.hasClassName(this.sActiveClass)) {
			if(this.iShowConfirm) {
				if(!confirm(this.translations.delete_appointment)) return;
			}
		}
		
		if(sParam) {
			sParams += sParam;
		}
		
		this.uniqueRequest(sParams);
	},
	
	/**
	 * Standardevent für das Anklicken von Kalendertagen
	 */
	switchAppointment: function(sDate, sId)
	{
		var oDay = $(sId);
		var sParam = '&action=';
		
		if(oDay.hasClassName(this.sActiveClass)) {			
			sParam += 'remove';
			
		} else {
			sParam += 'set';
		}
		
		this.updateDay(sDate, sId, sParam);
		
	},
	
	/** 
	 * Bereitet die Daten für das Füllen eines Monats vor.
	 * 
	 * @return array
	 */
	prepareMonthData: function(iMonth, oYearData)
	{
		var oData = oYearData['months'][iMonth];
		
		// Zeilen intialisieren
		var aRows = [
			[], [], [], [], [], []
		];
		
		var iCellsTotal = 48, iCells = 0, iDayIterator = 1;
		if(this.iStartWeekDay !== 1) {
			iCellsTotal = 42;
		}
					
		if(oData.start_weekday < this.iStartWeekDay) {
			oData.start_weekday = oData.start_weekday - this.iStartWeekDay + 8;
		} else {
			oData.start_weekday = oData.start_weekday - this.iStartWeekDay + 1;
		}

		for(var iRow = 0; iRow < 6; ++iRow) {
			
			var iRowIterator = 0;
			
			// Kalenderwoche
			if(this.iStartWeekDay === 1) {
				if(iRow === 0) {
					var iCW = oData.start_cw;
				}
				
				if(
					(
						iMonth == 1 &&
						iCW > oYearData.year_weeks_ly
					) ||
					(
						iMonth == 12 &&
						iCells >= oData.month_days
					)
				) {
					iCW = 1;
				}
				
				aRows[iRow].push(iCW++);
			}
			
			// Behandlung der ersten Zeile/Woche
			if(iRow === 0) {
				var aTempRow = [];

				for(var iSteps = 1, iDaysLastMonth = oData.month_days_lm; iSteps < oData.start_weekday; ++iSteps, --iDaysLastMonth) {
					aTempRow.push(iDaysLastMonth);
				}
				aTempRow.reverse();
				iCells += aTempRow.length;
				iRowIterator += aTempRow.length;
				aRows[iRow] = aRows[iRow].concat(aTempRow);
			}
			
			// Reale Tage einfügen
			for(; iRowIterator < 7 && iDayIterator <= oData.month_days; ++iRowIterator, ++iDayIterator, ++iCells) {
				aRows[iRow].push(oData.days[iDayIterator]);
			}
			
			// Überlauf der aktuellen Monatstage, also Daten des nächsten Monats anzeigen
			if(iCells >= oData.month_days) {
				// Starte neuen Monat
				if(typeof iNewDay == 'undefined') {
					var iNewDay = 1;
				}
				
				for(; iCells < iCellsTotal + 1 && iRowIterator < 7; ++iCells, ++iNewDay, ++iRowIterator) {
					aRows[iRow].push(iNewDay);
				}
				
			}
			
		}
		
		return aRows;
	},
	
	/**
	 * Methode, die die Übersetzungen für die THs liefert.
	 * Sinn hiervon ist, dass die Kalenderwoche vorkommen kann und auch nicht.
	 * 
	 * @return array
	 */
	prepeareHeadTranslations: function()
	{
		var aTranslationDays = [];
		if(this.iStartWeekDay == 1) {
			aTranslationDays.push(this.translations.cw);
		}

		for (var [key, value] of Object.entries(this.translations.days)) {
			aTranslationDays.push(value);
		}

		this.aHeadTranslations = this._shift(this.iStartWeekDay, aTranslationDays, false);
	},
	
	/**
	 * Verschiebt ein Array um die angebene Zahl des Starttags der Woche
	 */
	_shift: function(iShift, aArray, bRemoveIndizes)
	{	
		for(var i = 0; i < iShift - 1; ++i) {
			aArray.push(aArray.shift());
		}
		
		var aNewarray = aArray;

		// »Indizes« entfernen, die Prototype bei $H => $A setzt.
		if(bRemoveIndizes) {
			aNewarray = [];
			aArray.each(function(aElement) {
				aNewarray.push(aElement[1]);
			})
		}

		return aNewarray;

	},

	/**
	 * WRAPPER für Request-Aktionen
	 */
	requestCallback : function(oResponse, strParameters) {

		// Löschen von Requests aus dem Stack
		if(
			oResponse &&
			strParameters.indexOf('&task=') > 0
		) {
			var oTaskRegEx = new RegExp("[?&]task=([^&]*)?", 'i');
			var aTask = oTaskRegEx.exec(strParameters);

			var sTask = aTask[1];

			if(sTask === 'updateDay') {
				// Ermöglichen das mehrere Tage aktualisiert werden können
				var aDate = strParameters.match(/&date=([0-9]{4}\-[0-9]{2}\-[0-9]{2})/);
				if(aDate && aDate[1]) {
					sTask += '_' + aDate[1];
				}
			}
			
			if(
				aTask &&
				this.aRequestStack[sTask]
			) {
				// Task ist erfolgreich gewesen -> kann gelöscht werden.
				delete this.aRequestStack[sTask];
			}
		}

		var oData = this._evalJson(oResponse);

		if(oData && oData.action) {
			switch(oData.action) {
				case 'updateDay':
					
					var oDay = $(oData.objectid);
					var bHighlight = false;
					var oRegEx = new RegExp(this.sActiveClass, 'g');
					
					if(
						!oDay.hasClassName(this.sActiveClass) &&
						oRegEx.test(oData['class'])
					) {
						bHighlight = true;
					}
					
					if(oDay.hasClassName('today')) {
						oData['class'] += ' today';
					}
					
					oDay.style.removeProperty("background-color");
					oDay.className = 'day ' + oData['class'];
					
					if(oData.tooltip) {
						this.setToolTip(oDay, oData.tooltip);
					} else if(
						typeof oData.tooltip != 'undefined' &&
						oData.tooltip === false
					) {
						this.removeToolTip(oDay);
					}
					
					if(bHighlight) {
						new Effect.Highlight(oDay);
					}
					
					break;
				default:
					this._requestCallback(oResponse);
			}
			
			this.doGuiAction('request', oData);

		}

	},
	
	_requestCallback: function(oResponse) {
		var oData = this._evalJson(oResponse);

		if(oData && oData.action) {
			switch(oData.action) {
				case 'init':
					this.translations = oData.i18n;
					this.prepeareHeadTranslations();
					this.generate(oData.data);
					
					this.doGuiAction('hideLoading');
					this._requestCallbackHook(oData);					
					
					break;
				default:
					break;
			}

		}
	},
	
	_requestCallbackHook: function(oResponse) {
		
	},
	
	/**
	 * Führt eine Aktion auf die GUI2-JS-Instanz aus
	 */
	doGuiAction: function(sAction, mData)
	{
		if(
			this.sGuiHash &&
			this.sGuiHash != '' &&
			aGUI[this.sGuiHash]
		) {
			var oGui = aGUI[this.sGuiHash];
				
			switch(sAction) {
				case 'hideLoading':
					oGui.hideLoading();
					break;
				case 'request':
					mData.action = 'calendarsheet_' + mData.action;
					oGui.requestCallbackHook(mData);
					break;
			}
		}
	},
	
	executePing : function(oInterval, bUnload) {

		// Wenn der letzte Ping beendet wurde
		if(this.bPingInterval == false) {

			this.bPingInterval = true;
			this.iPingInterval = 0;

			var sParam = '&task=ping';

			if(bUnload) {
				sParam += '&unload='+bUnload;
			}

			this.request(sParam);

		} else {

			this.iPingInterval++;

			if(this.iPingInterval > 6) {
				this.bPingInterval = false;
			}

		}

	},

	request: function(sParam) {

		var strRequestUrl = '/calendarsheet/request';
		
		var sRequestHash = this.hash;

		var strParameters = '?hash='+sRequestHash;
		strParameters += '&instance_hash='+this.instance_hash;

		if(sParam){
			strParameters += sParam;
		}

		if(this.sGuiHash) {
			var oGui = aGUI[this.sGuiHash];
			if(oGui) {
				strParameters += oGui.getRequestIdParameters();
			}
		}
	
		new Ajax.Request(
			strRequestUrl,
			{
				method : 'post',
				parameters 	: strParameters,
				onSuccess 	: function(r){
					this.requestCallback(r, strParameters);
				}.bind(this),
				onFailure 	: function(r){
					this.requestError(r);
				}.bind(this)
			}
		 );

		return false;

	},

	uniqueRequest: function(sParam) {

		var aMatch = sParam.match(/&task=([a-zA-Z]*)/);
		var bExecuteRequest = false;

		if(aMatch[1]) {			
			var sTask = aMatch[1];

			if(sTask === 'updateDay') {
				// Ermöglichen das mehrere Tage aktualisiert werden können
				var aDate = sParam.match(/&date=([0-9]{4}\-[0-9]{2}\-[0-9]{2})/);
				if(aDate && aDate[1]) {
					sTask += '_' + aDate[1];
				}
			}

			if(!this.aRequestStack[sTask]) {
				this.aRequestStack[sTask] = sTask;
				bExecuteRequest = true;
			}

		} else {
			bExecuteRequest = true;
		}

		if(bExecuteRequest) {
			this.request(sParam);
		}

	},

	requestError : function(oJson) {
		var iStatus = '';
		if(oJson && oJson.status) {
			iStatus = oJson.status;
		}

		var sRequest = '&task=reportError&request_status='+iStatus;
		
		if(
			oJson && 
			oJson.request && 
			oJson.request.parameters
		) {
			sRequest += '&error='+Object.toJSON(oJson.request.parameters);
		}

		this.request(sRequest);
	},

	/**
	 * Methode, um einen Tooltip zu setzen
	 * 
	 * @param {DOMObject} oObject Element, dass die Events für den Tooltip bekommt
	 * @param {String} sText Der Text des Tooltips
	 * @param {String?} sId Eine mögliche ID, ansonsten bekommt die ID einfach die ID des Objektes plus den Suffix 'tooltip'.
	 */
	setToolTip: function(oObject, sText, sId)
	{
		var sTooltipId;
		if(!sId) {
			sTooltipId = oObject.id + '_tooltip';
		} else {
			sTooltipId = sId;
		}
		
		this.aTooltips[sTooltipId] = sText;
		
		oObject.observe('mousemove', function(sTooltipId, e) {
			this.showTooltip(sTooltipId, e);
		}.bind(this, sTooltipId));
		oObject.observe('mouseout', function(sTooltipId, e) {
			this.hideTooltip(sTooltipId, e);
		}.bind(this, sTooltipId));
		
	},
	
	/**
	 * Methode, um einen Tooltip zu entfernen
	 * 
	 * @param {DOMObject} oObject
	 * @param {String?} sId ID des Tooltips, ansonsten wird Objekt plus Suffix '_tooltip' genommen
	 */
	removeToolTip: function(oObject, sId)
	{
		var sTooltipId;
		if(!sId) {
			sTooltipId = oObject.id + '_tooltip';
		} else {
			sTooltipId = sId;
		}
		
		Event.stopObserving(oObject, 'mousemove');
		Event.stopObserving(oObject, 'mouseout');
		
		delete this.aTooltips[sTooltipId];
		
	},

	showTooltip : function(sTooltipId, e) {

		if(
			!this.aTooltips[sTooltipId] ||
			typeof this.aTooltips[sTooltipId] != 'string' ||
			this.aTooltips[sTooltipId].empty()
		) 
		{
			return;
		}
		
		var sId = 'div_'+sTooltipId;

		var oDiv = $(sId);

		if(!oDiv) {
			oDiv = new Element('div');
			oDiv.id = sId;
			oDiv.className = 'divTooltip';
		}

		oDiv.update(this.aTooltips[sTooltipId]);

		document.body.insert({bottom : oDiv});

		var iX = Event.pointerX(e) + 15;
		var iY = Event.pointerY(e) + 10;

		var iDivHeight = oDiv.getHeight();
		var iDivWidth = oDiv.getWidth();
		var oDocumentElement = document.documentElement;
		var iHeight = self.innerHeight || (oDocumentElement && oDocumentElement.clientHeight) || document.body.clientHeight || document.body.offsetHeight;
		var iWidth = self.innerWidth || (oDocumentElement && oDocumentElement.clientWidth) || document.body.clientWidth || document.body.offsetWidth;
		
		if(iX + iDivWidth > iWidth) {
			iX = iX - iDivWidth - 20;
		}
		if (iY + iDivHeight > iHeight) {
			iY = iY - iDivHeight -10;
		}
		
		oDiv.setStyle({
			left: iX+'px',
			top: iY+'px'
		});
		
	},

	hideTooltip : function(sTooltipId) {
		if($('div_'+sTooltipId)) {
			$('div_'+sTooltipId).remove();
		}
	},

	_evalJson : function(oJson) {

		var mReturn = null;

		var sJson = '';
		if(oJson && oJson.responseText) {
			sJson = oJson.responseText;
		}

		try {

			mReturn = sJson.evalJSON();

		} catch (e) {

			if(this.bReadyInitialized) {

				var iStatus = '';
				if(oJson && oJson.status) {
					iStatus = oJson.status;
				}

				var sParam = '&task=reportError&request_status='+iStatus+'&error='+encodeURIComponent(e.message);

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

					 sParam += '&parameters='+encodeURIComponent(Object.toJSON(oJson.request.parameters));
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

					sParam += '&query_string='+encodeURIComponent(oJson.request.body);
				}

				// Fehlermeldung an Server senden
				this.request(sParam);

				var bNewDialog = false;

				if(this.bDebugMode){
					sMessage = sJson;
					bNewDialog = true;
					
					var aErrors = new Array(
									this.getTranslation('json_error_occured'),
									sMessage
								);
					this.displayErrors(aErrors, this.sCurrentDialogId, bNewDialog);
				}

			}

			mReturn = false;

		}

		return mReturn;

	}

});
