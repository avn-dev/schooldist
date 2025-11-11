
var AccommodationGui = Class.create(UtilGui, {

	prepareAction : function($super, aElement, aData){

		$super(aElement, aData);

		if(aElement.task === 'writeAccommodationInfo') {
			if(confirm(this.getTranslation('sure_override_all_data'))) {
				$$('.accommodation_info_icon').each(function(oIcon) {
						this._fireEvent('click', oIcon);
				}.bind(this));
			}
		} else if(aElement.task === 'toogleAccommodationInfo') {
			$$('.accommodation_info').each(function(oInfoDiv) {
				oInfoDiv.toggle();
			});
		}

	},

	requestCallbackHook: function($super, aData) {

		$super(aData);

		if(
			(
				aData.action === 'openDialog' ||
				aData.action === 'saveDialogCallback'
			) && (
				aData.data.action === 'new' ||
				aData.data.action === 'edit'
			)
		) {

			this.aCategoryData	= aData.data.category_data;
			this.toggleMatchingInfo();

			$$('.accommodation_info_icon').each(function(oIcon) {

				Event.observe(oIcon, 'click', function() {
                    var rightInput = $j(oIcon).parent().find('.accommodation_info input');
                    var leftInputDiv = $j(oIcon).parent().find('div:first');

					if (rightInput.length === 0) {
						// Bei den Beschreibungsfeldern von dem Unterkunftsportal sind die Felder im Fidelodialog textareas
						// und keine Inputs und dazu auch noch tinymce, also werden hier ganz andere Funktionen benutzt
                        rightInput = $j(oIcon).parent().find('.accommodation_info textarea');
                        rightInput = tinyMCE.get(rightInput[0].id);

						var leftInput = $j(leftInputDiv).find('textarea')
						leftInput = tinyMCE.get(leftInput[0].id);

                        var content = rightInput.getContent({ format: 'text' });

						leftInput.setContent(content);
                        rightInput.setContent("");
					} else {
                        $j(leftInputDiv).find('input').val($j(rightInput).val());
                        $j(rightInput).val('')
					}

				}.bind(this));
			}.bind(this));

		} else if(aData.action === 'updateIcons') {

			var sIdParam = this.getRequestIdParameters();
			sIdParam = sIdParam.replace('id', 'parent_gui_id');
			sIdParam += '&item=accommodation';
			loadAbsencesList(sIdParam);

		}

	},

	toggleMatchingInfo : function() {

		$('other_matching_div').hide();
		$('family_matching_div').hide();

		var oCategorySelect = $('save['+this.hash+']['+this.sCurrentDialogId+'][accommodation_categories]');
		if(!oCategorySelect) {
			return;
		}
		var aSelectedCategories = $F(oCategorySelect);
		if(!aSelectedCategories) {
			return;
		}

		var aCategoryData = this.aCategoryData;
		if(!aCategoryData) {
			return;
		}

		$H(aCategoryData).each(function(aData) {

			var iCatId = parseInt(aData[1]['id']);
			var iCatType = parseInt(aData[1]['type_id']);
			if(isNaN(iCatId) || isNaN(iCatType)) {
				return;
			}
			var sCatId = iCatId.toString();
			var sCatType = iCatType.toString();

			if(aSelectedCategories.indexOf(sCatId) < 0) { // -1 = nicht gefunden
				return;
			}

			if(sCatType === '1') {
				$('family_matching_div').show();
			} else {
				$('other_matching_div').show();
			}

		});

	}

});
