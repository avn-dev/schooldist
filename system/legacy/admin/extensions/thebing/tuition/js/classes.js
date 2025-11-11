var Classes = Class.create(ATG2, {

	requestCallbackHook: function($super, aData) {
		
		$super(aData);

		if(
			(
				aData.action=='openDialog' ||
				aData.action=='saveDialogCallback'
			) &&
			(
				aData.data.action == 'new' ||
				aData.data.action == 'edit' ||
				aData.data.action == 'replaceDataCallback'
			) &&
			!aData.data.additional
		){

			this.selectedRowId = [aData.data.class_id];

			if(aData.error.length==0){
				this.displayTemplateFields(aData.data,false);
			}
			
			// Observer setzen
			this.setClassDialogObserver(aData.data);
			
			this.bindOtherReloadEvents();
			this.removeIcons();
			this.initBlockEvents(aData.data);
			if(aData.return_confirm){
				this.confirmChangesToOtherWeeks(aData);
			}

			if(aData.action=='saveDialogCallback' && aData.error.length==0){
				if(typeof preparePlanification == 'function') {
					loadStudents('unallocated');
					loadStudents('allocated');
					preparePlanification();
				}
			}
	
			// Lehrer Level bzw. Kursniveau müssen auch gecheckt werden
			this.checkLevel();
			
		}
		else if(aData.action=='reloadAvailable'){
			this.reloadRoomOptions(aData);
			this.reloadTeacherOptions(aData);
		}
		else if(aData.data.action=='copy'){
			var aReloadFields = $$('.reload_field');
			this.isReplace = false;
			if('replace_data'==aData.data.additional){
				this.isReplace = true;
			}
			for(iCounter=0;iCounter<aReloadFields.length;iCounter++){
				var oReloadField = aReloadFields[iCounter];
				oReloadField.observe('change', this.reloadCopyHtml.bind(this, aData.data.id, oReloadField));
			}
			
			// Oberser setzen für das toggeln der copy-table-Tr´s
			this.setCopyDialogObserver(aData.data);

		}else if(aData.data.action=='copyCallback'){
			changeWeek('next', false);
		}else if(aData.action=='createTable'){

			//this.emitter.on('gui.filter-bar.mounted', () => {
				$j('.last_week_icon').off('click').click(() => this.changeWeekFilter2(-1));
				$j('.current_week_icon').off('click').click(() => this.changeWeekFilter2(0));
				$j('.next_week_icon').off('click').click(() => this.changeWeekFilter2(1));
			//});

			// this.default_week = aData.data.default_week;
			//
			// var oLastWeekIcon = $('lastWeek__'+this.hash);
			// if(oLastWeekIcon){
			// 	oLastWeekIcon.stopObserving('click');
			// 	oLastWeekIcon.observe('click', this.changeWeekFilter.bind(this,'last'));
			// }
			// var oNextWeekIcon = $('nextWeek__'+this.hash);
			// if(oNextWeekIcon){
			// 	oNextWeekIcon.stopObserving('click');
			// 	oNextWeekIcon.observe('click', this.changeWeekFilter.bind(this,'next'));
			// }
			// var oCurrentWeekIcon = $('currentWeek__'+this.hash);
			// if(oCurrentWeekIcon){
			// 	oCurrentWeekIcon.stopObserving('click');
			// 	oCurrentWeekIcon.observe('click', this.changeWeekFilter.bind(this,'current'));
			// }
			
		} else if (
			(
				aData.action=='openDialog' || 
				aData.action=='saveDialogCallback'
			) &&
			aData.data.action=='daily_comments'
		) {

			if(aData.action=='saveDialogCallback') {
				loadStudents('unallocated');
				loadStudents('allocated');
				preparePlanification();
			}

			var toggleComment = function(oCheckbox) {
				var oCommentField = $j('#'+$j.escapeSelector(oCheckbox.id.replace('_state', '_state_comment')));
				if (oCommentField) {
					if ($j(oCheckbox).is(':checked')) {
						$j(oCommentField).closest('.GUIDialogRow').show();
					} else {
						$j(oCommentField).closest('.GUIDialogRow').hide();
					}
				}
			};

			$j('.cancel-block-day').each(function() { toggleComment(this) })
			$j('.cancel-block-day').click(function() { toggleComment(this) })
		}
        
        if(aData.data && aData.data.level_check){
			this.showLevelCheck(aData.data);
		}
		
		if(
			aData.action == 'openDialog' ||
			aData.action == 'saveDialogCallback'
		) {
			if(aData.data.different_level_html){
				
				var oTabBody = $('tabBody_1_' + aData.data.id + '_' + this.hash);
				
				if(oTabBody)
				{	
					oTabBody.innerHTML = aData.data.different_level_html;
					
					this.showLevelChangeTab(oTabBody);
				}
			}
			else{
				var oTabHeader = $('tabHeader_1_' + aData.data.id + '_' + this.hash);

				if(oTabHeader)
				{
					oTabHeader.hide();
				}
				
				var oTabMain = $('tabHeader_0_' + aData.data.id + '_' + this.hash);
				
				if(oTabMain)
				{
					this._fireEvent('click', oTabMain);
				}
			}
		}
        //this.setCoursesObserver(aData);
    },

    setCoursesObserver : function (aData) {
        // Kurskategorie
        $j('.course_category').off('change');
        $j('.course_category').change(function (e, keepValue) {
            var select = $j(e.target);
            var value = parseInt(select.val());
            var courses = this.courseData.filter(function (course) {
                if (!value) {
                    // Alle Kurse anzeigen
                    return true;
                }
                return course.id === 0 || course.category_id === value;
            }).map(function (course) {
                return {text: course.name, value: course.id};
            });

            var courseSelect = select.closest('.InquiryCourseContainer').find('select.courseSelect');
            self.updateSelectOptions(courseSelect.get(0), courses, false, !!keepValue);
        }.bind(this));
    },


    updateSelectOptions: function($super, oSelect, aOptions, bHighlight, bRestoreOldValue) {

		$super(oSelect, aOptions, bHighlight, bRestoreOldValue);

		if(
			oSelect.id &&
			oSelect.id.search('courselanguage_id') !== -1
		) {
			
			if(aOptions.length < 2) {
				$j(oSelect).parents('.GUIDialogRow').hide();
			} else {
				$j(oSelect).parents('.GUIDialogRow').show();
			}
			
		}
		
	},

	setCopyDialogObserver : function(aData){

		$j('input.check_all').change(function () {
			var type = $j(this).data('type');
			if ($j(this).is(':checked')) {
				$j('input.'+type).attr('checked', true);
			} else {
				$j('input.'+type).attr('checked', false);
			}
		})

		var oTrNext		= $('tr_next');
		var oTrCurrent	= $('tr_current');
		var oTrBoth		= $('tr_both');

		var oTempArray = [];
		if(oTrNext){
			oTempArray[oTempArray.length] = oTrNext;
		}
		
		if(oTrCurrent){
			oTempArray[oTempArray.length] = oTrCurrent;
		}
		
		if(oTrBoth){
			oTempArray[oTempArray.length] = oTrBoth;
		}

		// Prüfen welches angezeigt werden soll
		var aShowIds = [];

		if (aData.containers) {
			aShowIds = aData.containers;
		} else {
			if(oTempArray.length == 3){
				// Alle vorhanden
				aShowIds = [oTrCurrent.id];
			}else if(oTempArray.length > 0){
				// Länge 1 oder 2
				if(oTrCurrent){
					aShowIds = [oTrCurrent.id];
				} else if(oTrNext){
					aShowIds = [oTrNext.id];
				}

			}
		}

		oTempArray.each(function(oTr){

			var sToggleClass = oTr.id + '_toggle';

			oTr.stopObserving('click');

			Event.observe(oTr, 'click', function(e){
				this.toggleCopyTr(aData, sToggleClass, oTr);
			}.bind(this));		

			// Ausblenden außer die eine, die gezeigt werden soll
			if(aShowIds.indexOf(oTr.id) === -1){
				this.toggleCopyTr(aData, sToggleClass, oTr);
			}

		}.bind(this));

	},
	
	toggleCopyTr : function(aData, sToggleClass, oTr){

		var oI = $j(oTr).find('.toggle-icon');
		var bChangeStyle = false;

		$$('#dialog_'+aData.id+'_'+this.hash+' .'+sToggleClass).each(function(oToggleTr){						
			oToggleTr.toggle();

			if(!bChangeStyle) {
				
				var sStyle = oToggleTr.getStyle('display');

				if(
					sStyle == 'table-row' ||
					sStyle == 'block'
				) {
					oI.removeClass('fa-plus').addClass('fa-minus');
				} else {
					oI.removeClass('fa-minus').addClass('fa-plus');
				}	

				bChangeStyle = true;
			}
			
		}.bind(this));
	},
	
	// changeWeekFilter : function(sAction){
	//
	// 	var oWeekFilter		= $('week_filter_'+this.hash);
	// 	var iSelectedIndex	= oWeekFilter.selectedIndex;
	//
	// 	if(oWeekFilter){
	// 		if(sAction=='next'){
	// 			iSelectedIndex += 1;
	// 		}else if(sAction=='last'){
	// 			iSelectedIndex -= 1;
	// 		}else{
	//
	// 			for (i = 0; i < oWeekFilter.length; ++i) {
	// 				if (oWeekFilter.options[i].value == this.default_week) {
	// 					iSelectedIndex = i;
	// 					break;
	// 				}
	// 			}
	// 		}
	//
	// 		oWeekFilter.selectedIndex = iSelectedIndex;
	// 		this.loadTable(false,this.hash);
	// 	}
	// },

	changeWeekFilter2: function(delta) {

		/** @type FilterModel */
		const filter = this.filters.value.find(e => e.key === 'week_filter');

		if (delta) {
			const newIndex = filter.options.findIndex(o => o.key === filter.value) + delta;
			const optionKey = filter.options[newIndex].key;
			filter.setOption(optionKey);
		} else {
			filter.reset();
		}

		this.loadTable();

	},

	displayTemplateFields : function(aData, bFireEventOnlyLast){
		var aTemplateSelects = $$('.template');

		this.aTemplateData	= aData.templates;
		this.sDialogId		= aData.id;
		var iLength			= aTemplateSelects.length;

		for(i=0;i<iLength;i++){
			var oTemplateSelect = aTemplateSelects[i];
			oTemplateSelect.stopObserving('change');
			oTemplateSelect.observe('change', this.displayTemplateField.bind(this, oTemplateSelect));
			if((bFireEventOnlyLast && i==iLength-1)||!bFireEventOnlyLast){
				this._fireEvent('change', oTemplateSelect);
			}
			oTemplateSelect.observe('change', this.reload.bind(this));
		}

	},

	displayTemplateField : function(){

		var oBlockContentDiv;
		var oTemplateFieldFrom;
		var oTemplateFieldUntil;
		var oTemplateFieldLessons;
		var aTemplateData = this.aTemplateData;

		var aParams = $A(arguments);
		var oTemplateSelect = aParams[0];

		if(oTemplateSelect) {

			oBlockContentDiv	= oTemplateSelect.up('.block-content');

			if(oBlockContentDiv) {

				oTemplateFieldFrom	= oBlockContentDiv.down('select.template_field_from');
				oTemplateFieldUntil	= oBlockContentDiv.down('.template_field_until');
				oTemplateFieldLessons = oBlockContentDiv.down('.template_field_lessons');

				if(oTemplateFieldFrom && oTemplateFieldUntil && oTemplateFieldLessons) {

					//custom immer am Anfang
					if(oTemplateSelect.selectedIndex==0){
						oTemplateFieldFrom.disabled = false;
						oTemplateFieldUntil.disabled = false;
						oTemplateFieldLessons.disabled = false;
						oTemplateFieldFrom.removeClassName('readonly');
						oTemplateFieldUntil.removeClassName('readonly');
						oTemplateFieldLessons.removeClassName('readonly');
					}else{
						oTemplateFieldFrom.disabled = "disabled";
						oTemplateFieldUntil.disabled = "disabled";
						oTemplateFieldLessons.disabled = "disabled";
						oTemplateFieldFrom.addClassName('readonly');
						oTemplateFieldUntil.addClassName('readonly');
						oTemplateFieldLessons.addClassName('readonly');
					}

					if(aTemplateData[oTemplateSelect.value]){
						// oTemplateFieldFrom.value	= aTemplateData[oTemplateSelect.value].from;
						// oTemplateFieldUntil.value	= aTemplateData[oTemplateSelect.value].until;
						this.setTemplateFieldSelect(oTemplateFieldFrom, aTemplateData[oTemplateSelect.value].from);
						this.setTemplateFieldSelect(oTemplateFieldUntil, aTemplateData[oTemplateSelect.value].until);
						oTemplateFieldLessons.value	= aTemplateData[oTemplateSelect.value].lessons;
					} else {
						oTemplateFieldFrom.selectedIndex = 0;
						oTemplateFieldUntil.selectedIndex = 0;
						oTemplateFieldLessons.value	= "";
					}

				}

			}
		}

	},

	setTemplateFieldSelect: function(oElement, sValue) {
		oElement = $j(oElement);

		// Bei geänderten Schulzeiten fehlt eine jetzt nicht mehr vorhandene Zeit einfach, daher Unknown option nachbauen
		if(!oElement.find('option[value="' + sValue + '"]').length) {
			oElement.append($j('<option>', {
				value: sValue,
				text:  sValue + ' (' + this.getTranslation('tuition_template_time_missing') + ')',
				style: 'color: red' // Funktioniert in FF seit graumer Zeit nicht mehr
			}));
		}

		oElement.val(sValue);
	},

	bindOtherReloadEvents : function(){
		var aBlockContents = $$('.block-content');
		for(iKey=0;iKey<aBlockContents.length;iKey++){
			oSelectDay = aBlockContents[iKey].down('.jQm');
			oSelectFrom = aBlockContents[iKey].down('.template_field_from');
			oSelectUntil = aBlockContents[iKey].down('.template_field_until');

			oSelectDay.observe('change', this.reload.bind(this));
			oSelectFrom.observe('change', this.reload.bind(this));
			oSelectUntil.observe('change', this.reload.bind(this));
		}
	},

	stopOtherReloadEvents : function(){
		var aBlockContents = $$('.block-content');
		for(iKey=0;iKey<aBlockContents.length;iKey++){
			oSelectDay = aBlockContents[iKey].down('.jQm');
			oSelectFrom = aBlockContents[iKey].down('.template_field_from');
			oSelectUntil = aBlockContents[iKey].down('.template_field_until');

			oSelectDay.stopObserving('change');
			oSelectFrom.stopObserving('change');
			oSelectUntil.stopObserving('change');
		}
	},

	reload : function(){
		var sParam = '&task=reloadAvailable';
		sParam += '&'+$('dialog_form_'+this.sDialogId+'_'+this.hash).serialize();
		sParam += this.getFilterparam();
		if($('week')){
			sParam += '&filter[week_filter]='+$('week').value;
		}

		this.requestBackground(sParam);
	},

	reloadRoomOptions : function(aData) {

		var aBlocks = aData.blocks;
		var aRooms	= aData.rooms;
		var aAvailableRooms;
		var oSelect;
		var oOption;
		var aSelectedIds;

		for(var i=0;i<aBlocks.length;i++) {
			var iBlockId = aBlocks[i];

			oSelect = $('save['+this.hash+']['+aData.data.id+'][blocks][ktcl]['+iBlockId+'][rooms]');

			if(oSelect) {

				oSelect.options.length = 0;

				aAvailableRooms = aRooms[i];
				var aKeys = aAvailableRooms["keys"];
				var aValues = aAvailableRooms["values"];
				aSelectedIds = aAvailableRooms["selected"];

				for(var iKey=0;iKey<aKeys.length;iKey++) {
					oOption			= document.createElement("OPTION");
					oOption.value	= aKeys[iKey];
					oOption.text	= aValues[iKey];
					oSelect.appendChild(oOption);
				}

				$j(oSelect).val(aSelectedIds);

				$j(oSelect).multiselect('reloadOptions');
			}
		}
	},

	reloadTeacherOptions : function(aData){
		var aBlocks		= aData.blocks;
		var aTeachers	= aData.teachers;
		var aAvailableTeachers;
		var oSelect;
		var oEmptyOption;
		var oOption;
		var iSelectedId;

		for(var i=0;i<aBlocks.length;i++) {
			var iBlockId = aBlocks[i];
			oSelect = $('save['+this.hash+']['+aData.data.id+'][blocks][ktcl]['+iBlockId+'][teacher_id]');
			if(oSelect){
				oEmptyOption = oSelect.options[0];
				oSelect.options.length = 0;

				oSelect.appendChild(oEmptyOption);
				aAvailableTeachers = aTeachers[i];
				var aKeys = aAvailableTeachers["keys"];
				var aValues = aAvailableTeachers["values"];
				iSelectedId = aAvailableTeachers["selected"];

				for(var iKey=0;iKey<aKeys.length;iKey++) {
					oOption			= document.createElement("OPTION");
					oOption.value	= aKeys[iKey];
					oOption.text	= aValues[iKey];
					oSelect.appendChild(oOption);
				}

				oSelect.value = iSelectedId;

				jQuery(oSelect).effect('highlight');
			}
		}
	},

	initBlockEvents : function(aData){
		
		var aDivActions = $$('.block_action');
		var iCount	= aDivActions.length;

		for(var iKey=0;iKey<iCount;iKey++) {

			if(iKey==iCount-1){
				oImgAdd = this.createActionIcon('add', aData);
				aDivActions[iKey].appendChild(oImgAdd);
			}

			if(iKey!=0){
				oImgRemove = this.createActionIcon('remove', aData);
				aDivActions[iKey].appendChild(oImgRemove);
			}

		}
		
		var daySelects = $j('.day-select');
		
		daySelects.each(function(i, daySelect) {

			$j(daySelect).find('button').each(function(i, button) {
				
				$j(button).unbind('click');
				$j(button).click(function() {

					daySelect = $j(this).parents('.day-select');

					var active = 1;
					if($j(this).hasClass('btn-primary')) {
						active = 0;
					}
					
					var btnDays = $j(this).data('days');

					$j(btnDays).each(function(i, day) {

						if(active) {
							$j(daySelect).find('.day-'+day).addClass('btn-primary').removeClass('btn-default');
							$j(daySelect).find('.day-input option[value='+day+']').prop('selected', true);
						} else {
							$j(daySelect).find('.day-'+day).removeClass('btn-primary').addClass('btn-default');
							$j(daySelect).find('.day-input option[value='+day+']').prop('selected', false);
						}
						
					});

					var selectedDays = $j.map($j(daySelect).find('.day-input').val(), function(selectedDay) { return parseInt(selectedDay);});

					if($j($j(daySelect).find('.weekdays').data('days')).filter(selectedDays).length == 5) {
						$j(daySelect).find('.weekdays').addClass('btn-primary').removeClass('btn-default');
					} else {
						$j(daySelect).find('.weekdays').removeClass('btn-primary').addClass('btn-default');
					}
					if($j($j(daySelect).find('.weekend').data('days')).filter(selectedDays).length == 2) {
						$j(daySelect).find('.weekend').addClass('btn-primary').removeClass('btn-default');
					} else {
						$j(daySelect).find('.weekend').removeClass('btn-primary').addClass('btn-default');
					}

				});
				
			});
			
			// Initial values
			$j($j(daySelect).find('.day-input').val()).each(function(i, day) {
				$j(daySelect).find('.day-'+day).removeClass('btn-primary').addClass('btn-default');
				$j(daySelect).find('.day-'+day).trigger('click');
			});

		});
		
	},

	createActionIcon : function(sType, aData) {
		var oImg = document.createElement('i');
		oImg.style.cursor = 'pointer';
		if(sType=='add') {
			oImg.alt		= this.getTranslation('tuition_class_add_block');
			oImg.className = 'add_block fa '+aData.add_icon_src;
			oImg.observe('click', this.addBlock.bind(this, oImg, aData));
		}else{
			oImg.alt		= this.getTranslation('tuition_class_remove_block');
			oImg.className = 'remove_block fa '+aData.remove_icon_src;
			oImg.observe('click', this.removeBlock.bind(this, oImg, aData));
		}

		return oImg;
	},

	addBlock : function(){
		
		var aParams = $A(arguments);
		var oImg = aParams[0];
		var aData = aParams[1];

		var aMultiSelects = $$('.ui-multiselect');
		aMultiSelects.each(function(oMultiSelect){
			oMultiSelect.remove();
		});

		//this.closeAllEditors(this.sDialogId);

		var oContentDialog	= oImg.up('.GUIDialogContentPadding');
		var oBlockContent	= oImg.up('.block-content');

		var oBlockContentClone = oBlockContent.clone(true);
		oInputHidden = oBlockContentClone.down('.hidden_block');
		oInputHidden.value = "";
		oTemplateInput = oBlockContentClone.down('.template');
		oTemplateInput.options[0].value = "0";
		oDescriptionInput = oBlockContentClone.down('.description');
		oDescriptionInput.value = "";
		
		var titleDiv = oBlockContentClone.down('.block-title');
		$j(titleDiv).html(this.getTranslation('tuition_class_new_block'));

		// Reset day select
		$j(oBlockContentClone).find('.day-select button').removeClass('btn-primary').addClass('btn-default');
		$j(oBlockContentClone).find('.day-select input.day-input').prop('disabled', true);

		oContentDialog.appendChild(oBlockContentClone);
		this.displayTemplateFields(aData, true);
		this.stopOtherReloadEvents();
		this.initializeMultiselects(aData);
		//this.pepareHtmlEditors(this.sDialogId);
		this.bindOtherReloadEvents();
		this.initBlockIds(aData);
		this.removeIcons();
		this.initBlockEvents(aData);
		this.setClassDialogObserver(aData);
		
	},

	initBlockIds : function(aData){

		var aBlocks		= $$('.block-content');
		var iCounter	= 0;
		var oInputField;

		var sIdAddon	= 'save['+this.hash+']['+aData.id+'][blocks][ktcl]';
		var sNameAddon	= 'save[blocks]';

		aBlocks.each(function(oBlockContent){

			oInputField			= oBlockContent.down('.hidden_block');
			oInputField.name	= sNameAddon+'['+iCounter+'][block_id]';
			oInputField.id		= sIdAddon+'['+iCounter+'][block_id]';

			oInputField			= oBlockContent.down('.day-input');
			oInputField.name	= sNameAddon+'['+iCounter+'][days][]';
			oInputField.id		= sIdAddon+'['+iCounter+'][days]';

			oInputField			= oBlockContent.down('.template');
			oInputField.name	= sNameAddon+'['+iCounter+'][template]';
			oInputField.id		= sIdAddon+'['+iCounter+'][template]';

			oInputField			= oBlockContent.down('.template_field_from');
			oInputField.name	= sNameAddon+'['+iCounter+'][from]';
			oInputField.id		= sIdAddon+'['+iCounter+'][from]';

			oInputField			= oBlockContent.down('.template_field_until');
			oInputField.name	= sNameAddon+'['+iCounter+'][until]';
			oInputField.id		= sIdAddon+'['+iCounter+'][until]';

			oInputField			= oBlockContent.down('.template_field_lessons');
			oInputField.name	= sNameAddon+'['+iCounter+'][lessons]';
			oInputField.id		= sIdAddon+'['+iCounter+'][lessons]';

			oInputField			= oBlockContent.down('.rooms');
			oInputField.name	= sNameAddon+'['+iCounter+'][rooms][]';
			oInputField.id		= sIdAddon+'['+iCounter+'][rooms]';

			oInputField			= oBlockContent.down('.teacher');
			oInputField.name	= sNameAddon+'['+iCounter+'][teacher_id]';
			oInputField.id		= sIdAddon+'['+iCounter+'][teacher_id]';

			oInputField = oBlockContent.down('.description');
			oInputField.name = sNameAddon+'['+iCounter+'][description]';
			oInputField.id = sIdAddon+'['+iCounter+'][description]';

			oInputField = oBlockContent.down('.description_student');
			if(oInputField) {
				oInputField.name = sNameAddon+'['+iCounter+'][description_student]';
				oInputField.id = sIdAddon+'['+iCounter+'][description_student]';
			}

			iCounter++;
		});

	},

	removeBlock : function(){
		var aParams = $A(arguments);
		var oImg = aParams[0];
		var aData = aParams[1];

		var oDivContent = oImg.up('.block-content');
		oDivContent.remove();

		this.initBlockIds(aData);
		this.removeIcons();
		this.initBlockEvents(aData);
	},

	removeIcons : function(){
		var aAddIcons = $$('.add_block');
		aAddIcons.each(function(oIcon){
			oIcon.remove();
		});
		var aRemoveIcons = $$('.remove_block');
		aRemoveIcons.each(function(oIcon){
			oIcon.remove();
		});
	},

	confirmChangesToOtherWeeks : function(aData){

		if(confirm(this.getTranslation('tuition_class_confirm_text'))){
			var sParam = '&task=copyBlockChanges';
			sParam += '&'+$('dialog_form_'+this.sDialogId+'_'+this.hash).serialize();
			sParam += this.getFilterparam();
			if($('week')){
				sParam += '&filter[week_filter]='+$('week').value;
			}

			if(aData.changed_fields){
				aData.changed_fields.each(function(sField){
					sParam += '&changed_fields[]='+sField;
				});
			}

			//bei der planification klappt die getRequestIdParameters(); nicht, deshalb die ID immer aus sDialogId nehmen
			var sDialogId	= this.sDialogId;
			var aSplit		= sDialogId.split('_');

			var iSelectedId	= aSplit[1];

			this.request(sParam, '', this.hash, false, iSelectedId);
		}
	},

	reloadCopyHtml : function (sDialogId, oReloadField){

		// Alle zurzeit geöffneten Container
		var aOpenedTrs = $j('#dialog_form_'+sDialogId+'_'+this.hash).find('.toggle-icon.fa-minus').closest('tr');

		var sParam = '&task=openDialog&action=copy';
		if(this.isReplace){
			sParam += '&additional=replace_data';
		}
		sParam += '&'+$('dialog_form_'+sDialogId+'_'+this.hash).serialize();
		sParam += this.getFilterparam();
		if($('week')){
			sParam += '&filter[week_filter]='+$('week').value;
		}

		aOpenedTrs.each((i, oTr) => sParam += '&containers[]='+oTr.id);

		this.request(sParam);
	},

	additionalFilterHook : function(sParam){
				if($('week')){
					sParam += '&filter[week_filter]='+$('week').value;
				}

		return sParam;
	},
	
	/**
	 * Prüfen ob Lehrer diese Klasse unterrichten darf
	 */
	checkLevel: function() {

		var sDialogId	= this.sDialogId;
		var aSplit		= sDialogId.split('_');
		var iSelectedId	= aSplit[1];
			
		var sParam = '&task=checkLevel&id='+iSelectedId;
		sParam += '&'+$('dialog_form_'+this.sDialogId+'_'+this.hash).serialize();
		sParam += this.getFilterparam();
		
		this.request(sParam, '', '', false, 0, false);
		
	},
	
	/**
	 * 
	 */
	showLevelCheck : function(aData){

		$j.each(aData.level_check, function(iBlockKey, oTeacherInfos) {

			// Fehler anzeigen, dass Lehrer das Level oder den Kurs nicht unterrichten kann
			var sTeacherSelectId = 'save[' + this.hash + '][' + aData.id + '][blocks][ktcl][' + iBlockKey + '][teacher_id]';

			var oTeacherSelect = $(sTeacherSelectId);

			if(oTeacherSelect){
				oTeacherSelect.style.color = '#000000';

				var aNewOptionsGood = [];
				var aNewOptionsBad = [];

				var iTeacherSelected = 0;
				
				var aOptions = oTeacherSelect.childElements();

				aOptions.each(function(oOption){

					oOption.style.color = '#000000';

					var bError = false;
					oTeacherInfos.each(function(oTeacherInfo){

						if(
							oTeacherInfo.teacher_id == oOption.value &&
							oTeacherInfo.check == 1 &&
							oOption.value != 0 // Die Option mit "0" soll immer an 1. Stelle stehen #1650
						){
							// Lehrer highliten, da fehlerhaft
							oOption.style.color = '#FF0000';
							
							bError = true;
						}
						
						// Aktuelle Auswahl merken
						if(oOption.selected == true){
							oTeacherSelect.style.color = oOption.style.color;
							iTeacherSelected = oOption.value;
						}
						
					}.bind(this));
					
					if(bError){
						aNewOptionsBad[aNewOptionsBad.length] = oOption;
					}else{
						aNewOptionsGood[aNewOptionsGood.length] = oOption;
					}
					
				}.bind(this));
				
				// Optionen sortiert wieder einfügen
				oTeacherSelect.update();
	
				aNewOptionsGood.each(function(oOption){
					if(oOption.value == iTeacherSelected){
						oOption.selected = true;
					}else{
						oOption.selected = false;
					}
					
					oTeacherSelect.insert({
						bottom: oOption
					});
				}.bind(this));
				
				aNewOptionsBad.each(function(oOption){
					if(oOption.value == iTeacherSelected){
						oOption.selected = true;
					}else{
						oOption.selected = false;
					}
					
					oTeacherSelect.insert({
						bottom: oOption
					});
				}.bind(this));
				
				
			}
			
		}.bind(this));
	},
	
	setClassDialogObserver : function(aData){
		
		$$('#dialog_'+aData.id+'_'+this.hash+' .teacher').each(function(oSelect){
				
			Event.stopObserving(oSelect, 'change');
			
			Event.observe(oSelect, 'change', function(){
				var aOptions = oSelect.childElements();
				
				aOptions.each(function(oOption){
					if(
						oOption.selected == true &&
						oOption.style.color != oSelect.style.color
					){
						oSelect.style.color = oOption.style.color;
					}
				}.bind(this));
			}.bind(this));
		}.bind(this));
			
	},
	
	showLevelChangeTab : function(oTabBody){
		
		var sTabBodyId			= oTabBody.id;
		
		var sTabHeaderId		= sTabBodyId.replace('tabBody', 'tabHeader');
		
		var sTabBodyDataId		= sTabBodyId.replace('tabBody_1', 'tabBody_0');
		
		var sTabHeaderDataId	= sTabHeaderId.replace('tabHeader_1', 'tabHeader_0');
		
		var oTabHeader			= $(sTabHeaderId);
		
		if(oTabHeader)
		{
			oTabBody.show();
			
			oTabHeader.show();
			
//			oTabHeader.addClassName('GUIDialogTabHeaderActive');
//			
//			var oTabBodyData	= $(sTabBodyDataId);
//			
//			if(oTabBodyData)
//			{
//				oTabBodyData.hide();
//			}
//			
//			var oTabHeaderData	= $(sTabHeaderDataId);
//			
//			if(oTabHeaderData)
//			{
//				oTabHeaderData.removeClassName('GUIDialogTabHeaderActive');
//			}
		}
		
		
		this.toggleDialogTabByClass('level_change_tab', this.sCurrentDialogId);
		
		
	}
	
});
