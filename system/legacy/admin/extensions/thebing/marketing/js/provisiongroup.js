
var ProvisiongroupGui = Class.create(UtilGui, {

	requestCallbackHook: function($super, aData) {
		// RequestCallback der Parent Klasse
		$super(aData);

		var sTask = aData.action;
		var sAction = aData.data.action;
		var aData = aData.data;

		if(	
			sTask == 'openDialog' ||
			sTask == 'saveDialogCallback' ||
			sTask == 'reloadDialogTab'
		){

			if(
				sAction == 'edit' ||
				sAction == 'new'
			){
				// Observer setzen
				this.setTabObserver(aData);
			}

		}else if(sTask == 'updateProvisionTabCallback'){
			if(
				aData.html &&
				aData.type &&
				$(aData.type+'_provision_table')
			){
				var oTableDiv = $(aData.type+'_provision_table');
				oTableDiv.update(aData.html);
				oTableDiv.show();
				// Observer für Tabelle
				this.setTableObserver(aData);
				
				//scrollen bis zur aktuellen Saison
				var aActiveTabs = $$('.GUITabBodyActive');
				
				if(
					aActiveTabs
				){
					var oActiveTab = aActiveTabs[0];
					var oCurrentSeason = oActiveTab.down('.current-season');
					if(
						oCurrentSeason
					){
						oActiveTab.scrollLeft = oCurrentSeason.offsetLeft;
					}
				}
			}
		}
	},

	/*
	 * Observer für Tabelle
	 */
	setTableObserver : function(aData){
		// Seasons kopieren
		$$('#table_'+aData.type+' .copy_season').each(function(oImg){
			Event.observe(oImg, 'click', function(e){
				this.copySeason(aData, oImg);
			}.bind(this));
		}.bind(this));

		// Innerhalb einer Kategorie kopieren
		$$('#table_'+aData.type+' .copy_category').each(function(oImg){
			Event.observe(oImg, 'click', function(e){
				try {
					this.copyCategory(aData, oImg);
				}catch(e) {
					console.debug(e);
				}
			}.bind(this));
		}.bind(this));
	},

	/*
	 * Category kopieren
	 */
	copyCategory : function (aData, oImg){
		// IDs bestimmen
		var aMatch = oImg.id.match(/_([a-z].*)_([a-z].*)_([0-9].*)_([0-9].*)$/);
		var sSavePrefix = aMatch[2];
		var iSeasonId = aMatch[3];
		var iCategoryId = aMatch[4];

		// Value Input der kopiert werden soll
		if(
			oImg.up('td') &&
			oImg.up('td').down('input')
		){
			var sValue = oImg.up('td').down('input').value;

			// Felder updaten
			$$('#table_'+aData.type+' .input_category_'+sSavePrefix+'_'+iSeasonId+'_'+iCategoryId).each(function(oInput){
				var sName = oInput.name;
				
                if(sSavePrefix == 'additionalcourse'){
                    sSavePrefix = 'additional_course';
                } else if(sSavePrefix == 'additionalaccommodation'){
                    sSavePrefix = 'additional_accommodation';
                } else if(sSavePrefix == 'extranight') {
					sSavePrefix = 'extra_night';
				}
				// Prüfen da Unterkünfte u. Extranächte bis auf den Prefix identisch heißen
				if(sName.indexOf(sSavePrefix) != -1){
					oInput.value = sValue;
				}
			}.bind(this));
		}
	},

	/*
	 * Season kopieren
	 */
	copySeason : function (aData, oImg){
		// Season ID
		var aMatch = oImg.id.match(/_([0-9].*)$/);
		var iSeasonId = aMatch[1];

		// Alle Seasons
		var aAllSeasons = new Array();
		$$('#table_'+aData.type+' .table_head').each(function(oTh){
			var aMatch = oTh.id.match(/_([0-9].*)$/);
			aAllSeasons[aAllSeasons.length] = aMatch[1];
		}.bind(this));

		// Daten alte Season
		var aCurrentSeasonData = new Array();
		$$('#table_'+aData.type+' .input_season_'+iSeasonId).each(function(oInput){
			
			aCurrentSeasonData[aCurrentSeasonData.length] = oInput.value;
		}.bind(this));

		// Zu kopierende SeasonId
		var iCurrentIndex = null;
		var iNewSeason = 0;
		aAllSeasons.each(function(iSeasonTemp, i) {
			if(iSeasonTemp == iSeasonId){
				iCurrentIndex = i;
			}else if(
				iCurrentIndex != null &&
				iNewSeason == 0
			){
				iNewSeason = iSeasonTemp;
				return;
			}
		}.bind(this));

		// Felder neue Season updaten
		var aNewSeasonData = new Array();
		var iCount = 0;
		$$('#table_'+aData.type+' .input_season_'+iNewSeason).each(function(oInput){
			if(aCurrentSeasonData[iCount]){
				oInput.value = aCurrentSeasonData[iCount];
			}
			iCount++;
		}.bind(this));

	},

	checkSchoolSelectVisibility: function() {
		
		var schoolDependent = false;
		$j('.settings').each(function() {
			if($j(this).val() != 3) {
				schoolDependent = true;
			}
		});

		if(schoolDependent) {
			$j('select.school').parents('.GUIDialogRow').show();
		} else {
			$j('select.school').val(0).parents('.GUIDialogRow').hide();
		}
		
	},

	/*
	 * Observer für Tab
	 */
	setTabObserver : function (aData) {

		$j('.copy_value').click(function(e) {
			this.copyValue(e.currentTarget);
		}.bind(this));

		$j('.settings').change(function() {
			this.checkSchoolSelectVisibility();
		}.bind(this));
		
		this.checkSchoolSelectVisibility();
		
		// Schulselect
		$$('#dialog_'+aData.id+'_'+this.hash+' .school_select').each(function(oSelect){
			
			Event.observe(oSelect, 'change', function(e){
				var oYearSelect = oSelect.up('.GUIDialogRow').next('.GUIDialogRow').down('.year_select');
				this.loadProvisions(oSelect, oYearSelect);
			}.bind(this));
		}.bind(this));

		$$('#dialog_'+aData.id+'_'+this.hash+' .year_select').each(function(oSelect){			
			Event.observe(oSelect, 'change', function(e){
				var oSchoolSelect = oSelect.up('.GUIDialogRow').previous('.GUIDialogRow').down('.school_select');
				this.loadProvisions(oSchoolSelect, oSelect);
			}.bind(this));
		}.bind(this));
	},

	loadProvisions : function(oSchoolSelect, oYearSelect){

		if(
			oSchoolSelect &&
			oYearSelect
		){
			var iSchool = oSchoolSelect.value;

			// prüfen welches Tab
			var aMatch = oSchoolSelect.name.match(/^([a-z].*)\[([a-z].*)\]$/);
			var sTabType = aMatch[1];
			
			var sYears = oYearSelect.serialize();

			// Provisionen laden
			if(
				iSchool > 0
			){
				var strParameters = '&task=updateProvisionTab';

				strParameters += '&school_id='+iSchool;
				strParameters += '&type='+sTabType;
					strParameters += '&' + sYears;

				this.request(strParameters);
			}
		}

	},

	copyValue : function(btn) {

		var value = $j(btn).parents('.input-group').find('.'+$j(btn).data('type')+'-input').val();
		var type = $j(btn).parents('.input-group').find('.'+$j(btn).data('type')+'-select').val();

		$j('.'+$j(btn).data('type')+'-input').val(value);
		$j('.'+$j(btn).data('type')+'-select').val(type);

	},

});