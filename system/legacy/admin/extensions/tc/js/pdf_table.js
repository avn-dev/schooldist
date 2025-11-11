var PdfTable = Class.create(CoreGUI,{

	iFactor : 0,//Faktor zwischen PDF MM Breite und HTMl Pixel
	
	sDialogId : 'ID_0',//DialogID Cachen um nicht aData wegender der DialogID immer mit zu schleppen
	
	iMinimumColWidth : 0,//Minimum Breite der Spalten
	
	aCols : new Array,//Spalten-Cache
	
	aPositionCols : new Array,//Die Hidden-Felder wo die Informationen der Position drin steht
	
	iMinColPos : 0,//Minimum Position der Spalten, wird beim verschieben berücksichtigt, wenn man auf den Pfeil links klickt
	
	iMaxColPos: 0,//Maximum Position der Spalten, wird beim verschieben berücksichtigt, wenn man auf den Pfeil recths klickt
	
	sIconAction : 'new',//Dialog Action auch cachen um Später bei manuellen requests die richtige Dialog_ID bekommen zu können
	
	/**                                  
	 * Request Callback                  
	 */                                  
	requestCallbackHook: function($super, aData) {

		$super(aData);

		if(
			aData.action=='openDialog' ||
			aData.action=='saveDialogCallback'
		){
			//Beim öffnen/ nach dem Speichern die Tabelle aufbauen
			if(aData.data && aData.data.pdf_table_html)
			{
				//Dialog Action cachen
				this.sIconAction = aData.data.action;
				
				//Dialog id cachen
				this.sDialogId = aData.data.id;
				
				var oPdfTable = this.getPdfTable();
				
				if(oPdfTable)
				{
					// PDF Tabelleninhalt wird PHP-seitig übermittelt
					oPdfTable.update(aData.data.pdf_table_html);
					
					// Minimale Breite für eine Spalte errechnen
					this.calculateMinimumWidthForColWidths();
					
					// Faktor von mm auf Pixel ausrechnen
					this.calculateWidthFactor();
					
					// Breite für das Beschreibungsspalte ausrechnen, die wird immer dynamisch errechnet
					this.calculateWidthForDescriptionField();
					
					// Anhand der Faktors die Spaltenbreiten anpassen
					this.resizeWidthsByFactor();
					
					// Spalten-Observer, damit die Spaltenbreie für die Beschreibungsspalte neu errechnet wird &
					// anhand des Faktors die Spaltenbreiten wieder HTML-seitig gesetzt werden
					this.setColWitdhEvents();
					
					// Observer für das Feld "Totale Breite"
					this.setEventForTotalWidthInput();
					
					// Observer für die Icons zum verschieben
					this.setMoveEvents();
					
					// Observer für die Buttons
					this.setActionsObserver();
					
					// Spalten Cachen, wird beim verschieben benutzt
					this.initCols();
				}
			}
		}
		
	},
	
	/**
	 * Faktor von mm auf Pixel ausrechnen
	 */
	calculateWidthFactor : function()
	{	
		var oInputTotalWidth	= this.getTotalWidthInput();
		var aFirstCols			= $$('.firstCol');
		
		if(aFirstCols)
		{
			var oFirstCol = aFirstCols[0];
			
			var oPdfTable = this.getPdfTable();
			var iWidthPdfTable = oPdfTable.getWidth();
			iWidthPdfTable -= oFirstCol.getWidth();

			if(oInputTotalWidth)
			{
				this.iFactor = iWidthPdfTable / oInputTotalWidth.value;
			}
		}
	},
	
	/**
	 * Hauptdiv für die Tabelle
	 */
	getPdfTable : function()
	{	
		var oPdfTable = $('pdf_table_'+this.hash);
			
		return oPdfTable;
	},
	
	/**
	 * Input "Totale Breite"
	 */
	getTotalWidthInput : function()
	{
		var oInputTotalWidth	= $('save[' + this.hash + ']['+this.sDialogId+'][table_width][attribute]');
		
		return oInputTotalWidth;
	},
	
	/**
	 * Breite für das Beschreibungsspalte ausrechnen, die wird immer dynamisch errechnet
	 */
	calculateWidthForDescriptionField : function()
	{
		var oDynamicWidthInput;// Beschreibungsspalte		
		
		var iUsedWidth = 0;// Wieviel schon verteilt ist
		
		var aColWidths = this.getWidthCols();
		
		var oTotalWidthInput = this.getTotalWidthInput();
		
		aColWidths.each(function(oColWidthInput){
			if(!oColWidthInput.readOnly){
				
				var iValue = parseInt(oColWidthInput.value);
				
				// Wenn keine Ganzzahl eingetippt wurde
				if(!iValue){
					iValue = 0;
				}
				
				// Breiten summieren
				iUsedWidth += iValue;
				
			}else{
				//Wenn readonly, das ist es das "Beschreibungsfeld, diesen Input merken
				oDynamicWidthInput = oColWidthInput;
			}
		});
		
		if(oDynamicWidthInput && oTotalWidthInput){
			// In das "Beschreibungsfeld" den übrigen Wert setzen
			oDynamicWidthInput.value = oTotalWidthInput.value - iUsedWidth;
		}
	},
	
	/**
	 * Anhand der Faktors die Spaltenbreiten anpassen
	 */
	resizeWidthsByFactor : function()
	{
		var aWidthCols = $$('.widthCols');
		var oPdfTable = this.getPdfTable();
		
		// Komplette Breite der Tabelle
		var iWidthPdfTable = oPdfTable.getWidth();
		
		// Input "Beschreibung"
		var oDynamicColWidth;
		
		aWidthCols.each(function(oColWidthDiv){
			
			// Der Input wo der Wert drin steht
			var oInput = $(oColWidthDiv.id + '_input');
			
			if(oInput){
				
				if(!oInput.readOnly){
					
					// Pixel Wert anhand des Faktors
					var iPixelValue = oInput.value * this.iFactor;

					if(iPixelValue < this.iMinimumColWidth)
					{
						// Wenn der Pixelwert kleiner ist als die minimale Breite einer Spalte, dann übernehme den Minimumwert
						iPixelValue = this.iMinimumColWidth;
					}
					
					// Errechnen was noch übrig bleibt für die dynamische Spalte "Beschreibung"
					iWidthPdfTable -= iPixelValue;

					// Den umgerechneten wird in Pixel setzen
					oColWidthDiv.style.width = iPixelValue + 'px';
					
				}else{
					oDynamicColWidth = oColWidthDiv;
				}
			}
		}.bind(this));
		
		if(oDynamicColWidth){
			// Breite für die dynamische Spalte "Beschreibung" setzen
			oDynamicColWidth.style.width = iWidthPdfTable + 'px';
		}
		
	},
	
	/**
	 * Observer setzen, damit beim Ändern einer Breite aktualisiert wird
	 */
	setColWitdhEvents : function()
	{
		var aColWidths = this.getWidthCols();
		
		aColWidths.each(function(oColWidthInput){
			Event.observe(oColWidthInput, 'keyup', function() {
				this.prepareUpdateWidths();
			}.bind(this));
		}.bind(this));
	},
	
	/**
	 * @todo: Dynamisch errechnen?
	 */
	calculateMinimumWidthForColWidths : function()
	{
		this.iMinimumColWidth = 86;
	},
	
	/**
	 * Alle TD's die zu einer Spalte gehören cachen
	 */
	initCols : function()
	{
		var aPositions = new Array();
		
		// TD's wo die ganzen Icons & Hiddenfelder sich befinden
		var aActionsRows = $$('.actionsRow');
		
		aActionsRows.each(function(oActionDiv){
			
			var sId = oActionDiv.id;
			
			// HiddenFeld-ID ist genau gleich wie beim TD aufgebaut, nur mit dem Zusatz _position_id
			var sIdPosition = sId + '_position';
			var oPosition = $(sIdPosition);
			
			if(oPosition)
			{
				// Positionen merken, um sie später als "Schlüssel" für aCols zu benutzen
				aPositions.push(oPosition.value);
			}
		});
		
		var aCols = new Array();
		var oTable = this.getPdfTable();
		
		if(oTable)
		{
			var aRows = oTable.getElementsByTagName('tr');
			var iCountRows = aRows.length;
			var oRow;
			var iMinPos = null;
			var iMaxPos = null;
			
			for(iCounter=0;iCounter<iCountRows;iCounter++)
			{
				//Alle TR's durchlaufen
				
				oRow = aRows[iCounter];
				
				var aCells = oRow.childNodes;
				var iCountCells = aCells.length;
				
				for(iCounterCells=0;iCounterCells<iCountCells;iCounterCells++)
				{
					//Alle TD's/TH's durchlaufen
					
					var oCell = aCells[iCounterCells];
					
					// Die erste Spalte ist nicht verschiebbar, darum lassen wir alle mit Klassennamen "firstCol" weg
					if(!oCell.hasClassName('firstCol'))
					{
						// Da wir die erste Spalte mit Klassennamen "firstCol" weglassen, müssen wir hier eins abziehen
						var iAdd = iCounterCells - 1;
						var iPosition = aPositions[iAdd];
						
						if(!aCols[iPosition])
						{
							aCols[iPosition] = new Array();
						}
						
						aCols[iPosition].push(oCell);
						
						if(oCell.hasClassName('actionsRow'))
						{
							//Wenn wir beim TD mit den ganzen Hidden Felder sind, dann Minimum&Maximum vorbereiten
							
							var sCellId = oCell.id;
							var sPositionHiddenId = sCellId + '_position';
							var oPositionHidden = $(sPositionHiddenId);
							
							if(oPositionHidden)
							{
								this.aPositionCols[iPosition] = oPositionHidden;
								
								var iPosition = parseInt(oPositionHidden.value);
								
								if(iMinPos === null || iPosition < iMinPos)
								{
									// Minimum Position einer Spalte
									iMinPos = iPosition;
								}
								
								if(iMaxPos === null || iPosition > iMaxPos)
								{
									// Maximum Position einer Spalte
									iMaxPos = iPosition;
								}
							}
						}
					}
				}
			}
			
			this.iMinColPos = iMinPos;
			this.iMaxColPos = iMaxPos;

			this.aCols = aCols;
		}
		
	},
	
	/**
	 * Events setzen für die Icons zum Verschieben
	 */
	setMoveEvents : function()
	{	
		this.setMoveBackForwardEvents('back');
		this.setMoveBackForwardEvents('next');
	},
	
	/**
	 * Events für links/rechts verschieben sind sehr identisch, darum in einer Methode mit Parametern behandeln
	 */
	setMoveBackForwardEvents : function(sType)
	{
		var aIcons; // Alle Icons die ein Event bekommen sollen
		var sButtonIdAddon; // ID-Zusatz des Icons, um später diese zu ersetzen um das HiddenFeld für die Position zu finden
		
		if(sType == 'back')
		{
			aIcons = $$('.positionBack');
			sButtonIdAddon = 'positionBack';
		}
		else
		{
			aIcons = $$('.positionNext');
			sButtonIdAddon = 'positionNext';
		}
		
		aIcons.each(function(oButton)
		{
			Event.observe(oButton, 'click', function()
			{
				var sId = oButton.id;
				var sIdHiddenPosition = sId.replace(sButtonIdAddon, 'position');
				
				// Hidden-Feld mit Positionswert
				var oHiddenPosition = $(sIdHiddenPosition);
				
				if(oHiddenPosition)
				{
					if(sType == 'back')
					{
						// nach links verschieben
						this.changeColPosition('back', oHiddenPosition.value);
					}
					else
					{
						// nach rechts verschieben
						this.changeColPosition('next', oHiddenPosition.value);
					}
				}
				
			}.bind(this));
		}.bind(this));
	},
	
	/**
	 * Spalte verschieben ausführen (rechts/links)
	 */
	changeColPosition : function(sType, iPosition)
	{
		if(this.aCols[iPosition]) // Wenn das Element im Cache vorhanden ist
		{
			var iReplace;
			
			// Positionsnummer holen wo das Element verschoben werden muss
			if(sType == 'back')
			{
				iReplace = this.getPreviousPosition(iPosition);
			}
			else
			{
				iReplace = this.getNextPosition(iPosition);
			}
			
			if(this.aCols[iReplace]) // Wenn das zu verschiebende Element im Cache vorhanden ist
			{
				var aColsReplace = this.aCols[iReplace];
				var iCount = aColsReplace.length;
				
				for(iCounter=0;iCounter<iCount;iCounter++)
				{
					// Neben dieser Zelle wir unsere Zelle verschoben (rechts oder links)
					var oCellReplace = aColsReplace[iCounter];
					
					// Unsere jetzige Zelle
					var oCellCurrent = this.aCols[iPosition][iCounter];
				
					// Hier wird entschieden ob nach rechts oder links verschoben werden muss
					if(sType == 'back')
					{
						this.insertCellPrevious(iReplace, oCellCurrent, oCellReplace);
					}
					else
					{
						this.insertCellNext(iReplace, oCellCurrent, oCellReplace);
					}
				}
				
				// Die Positionsnummern erneut aufbauen
				this.sortNew();
				
				// Den Cache erneut aufbauen
				this.initCols();
			}
		}
	},
	
	/**
	 * Je nach Situation nach links oder rechts verschieben für den linken Pfeil
	 */
	insertCellPrevious : function(iReplace, oCellCurrent, oCellReplace)
	{
		if(iReplace == this.iMaxColPos)
		{
			// Wenn man in der ersten Spalte auf den linken Pfeil klickt, dann wird unsere Zelle rechts vom letzten Element verschoben
			oCellReplace.insert({
				'after': oCellCurrent
			});	
		}
		else
		{
			// Ansonsten immer links vom vorherigem Element
			oCellReplace.insert({
				'before': oCellCurrent
			});	
		}
	},
	
	/**
	 * Je nach Situation nach links oder rechts verschieben für den rechten Pfeil
	 */
	insertCellNext : function(iReplace, oCellCurrent, oCellReplace)
	{
		if(iReplace == this.iMinColPos)
		{
			// Wenn man in der letzten Spalte auf den rechten Pfeil klickt, dann wird unsere Zelle links vom ersten Element verschoben
			oCellReplace.insert({
				'before': oCellCurrent
			});	
		}
		else
		{
			// Ansonsten immer rechts vom nächsten Element
			oCellReplace.insert({
				'after': oCellCurrent
			});	
		}
	},
	
	/**
	 * Vorherige Position finden
	 */
	getPreviousPosition : function(iPosition)
	{
		var iPrevPosition;
		
		if(iPosition == this.iMinColPos)
		{
			// Wenn beim ersten Element auf den linken Pfeil geklickt wird, dann gib die letzte Position zurück
			iPrevPosition = this.iMaxColPos;
		}
		else
		{
			var aPositionCols = this.aPositionCols;
			var oCurrentPositionCol = aPositionCols[iPosition];
			var iCurrentPosition = oCurrentPositionCol.value;
				
			var iCount = aPositionCols.length - 1;
			var oHiddenPos;
			var iPosition;
			
			for(iCounter=iCount; iCounter>=this.iMinColPos; iCounter--)
			{
				oHiddenPos = aPositionCols[iCounter];
				iPosition = oHiddenPos.value;
				
				if(iPosition < iCurrentPosition)
				{
					iPrevPosition = iPosition;
						
					break;
				}
			}
		}
		
		return iPrevPosition;
	},
	
	/**
	 * Nächste Position finden
	 */
	getNextPosition : function(iPosition)
	{
		var iNextPosition;
		
		if(iPosition == this.iMaxColPos)
		{
			// Wenn beim letzten Element auf den rechten Pfeil geklickt wird, dann gib die erste Position zurück
			iNextPosition = this.iMinColPos;
		}
		else
		{
			var aPositionCols = this.aPositionCols;
			var oCurrentPositionCol = aPositionCols[iPosition];
			var iCurrentPosition = oCurrentPositionCol.value;
				
			var iCount = aPositionCols.length - 1;
			var oHiddenPos;
			var iPosition;
			
			for(iCounter=0; iCounter<=iCount; iCounter++)
			{
				oHiddenPos = aPositionCols[iCounter];
				iPosition = oHiddenPos.value;
				
				if(iPosition > iCurrentPosition)
				{
					iNextPosition = iPosition;
						
					break;
				}
			}
		}
		
		return iNextPosition;
	},
	
	/**
	 * Positionsnummern erneut generieren
	 */
	sortNew : function()
	{
		var aPosCols = $$('.actionsRow');
		
		aPosCols.each(function(oActionDiv, iIndex){
			
			var sId = oActionDiv.id;
			var sIdHidden = sId + '_position';
			
			var oHiddenPosition = $(sIdHidden);
			
			if(oHiddenPosition)
			{
				oHiddenPosition.value = iIndex;
			}
			
		});
	},
	
	/**
	 * Observer für die Buttons
	 */
	setActionsObserver : function()
	{
		var aActionsRows = $$('.actionsRow');
		
		aActionsRows.each(function(oActionsDiv){
			
			var sId = oActionsDiv.id;
			
			// Observer für das Löschen-Icon
			var sIdDeleteButton = sId + '_delete';
			var sIdHiddenKey = sId + '_key';
			var oDeleteButton = $(sIdDeleteButton);
			var oHiddenKey = $(sIdHiddenKey);
			
			if(oHiddenKey && oDeleteButton)
			{
				Event.observe(oDeleteButton, 'click', function(e) {
					this.deleteCol(oHiddenKey.value);
				}.bind(this));
			}
		}.bind(this));
	},
	
	/**
	 * Request zum Löschen einer Spalte
	 */
	deleteCol : function(sKey)
	{
		var sTask = '&task=deleteCol';
		sTask += '&col='+sKey; // Spaltenkey
		sTask += '&action='+this.sIconAction; // Dialog-Action wird benötigt, umd die DialogID wieder zurückzugeben
		
		this.request(sTask);
	},
	
	/**
	 * Observer für das Feld "Totale Breite"
	 */
	setEventForTotalWidthInput : function()
	{
		var oFieldTableWidth = this.getTotalWidthInput();
		
		if(oFieldTableWidth)
		{
			Event.observe(oFieldTableWidth, 'keyup', function() {
				
				this.prepareUpdateWidths();
				
			}.bind(this));
		}
	},
	
	/**
	 * Verzögertes aktualisieren der Breitenberechnung
	 */
	prepareUpdateWidths : function()
	{
		if(this.prepareUpdateWidthsTimer){
			clearTimeout(this.prepareUpdateWidthsTimer);
		}

		this.prepareUpdateWidthsTimer = setTimeout(this.updateWidths.bind(this), 800);
	},
	
	/**
	 * Berechnen der dynamischen Breite für die Spalte "Beschreibung"
	 * Nach Faktor die Breiten in Pixel umwandeln
	 */
	updateWidths : function()
	{
		this.calculateWidthForDescriptionField();
		this.resizeWidthsByFactor();
	},
	
	/**
	 * /Alle Inputs mit Breitenangaben
	 */
	getWidthCols : function()
	{
		var aColWidths = $$('.colWidth');
		
		return aColWidths;
	}
});