var QuestionnairesGUI = Class.create(CoreGUI, {
	
	requestCallbackHook : function ($super, aData){

		$super(aData);

		if(
			aData.action &&
			(
				aData.action == 'openDialog' ||
				aData.action == 'saveDialogCallback' ||
				aData.action == 'reloadDialogTab'
			) && (
				aData.data.action == 'addQuestion' ||
				aData.data.action == 'edit' ||
				aData.data.action == 'new'
			)					
		) {
			this.setQuestionDialogEvents(aData.data);
		} else if(aData.action == 'loadTopicQuestionsCallback') {
			this.updateQuestionSelect(aData.data);
		} /*else if(aData.action == 'loadRatingDataCallback') {
			this.updateRatingData(aData.data);
		}*/
		
	},
	
	setQuestionDialogEvents: function(aData) {
		
		var aTopicSelects = $$('.topic_select');
		
		aTopicSelects.each(function(oTopicSelect) {
			
			if(oTopicSelect) {
				oTopicSelect.stopObserving('change');
				Event.observe(oTopicSelect, 'change', function(oEvent) {					
					this.loadTopicQuestions(oTopicSelect);
					this.resetQuestionSelect(oTopicSelect);										
				}.bind(this));
				
				// var oQuestionSelect = this.getQuestionSelect(oTopicSelect);
				//
				// if(oQuestionSelect) {
				// 	oQuestionSelect.stopObserving('change');
				// 	Event.observe(oQuestionSelect, 'change', function(oEvent) {
				// 		this.loadRatingData(oQuestionSelect);
				// 	}.bind(this));
				// }
			}	
			
			
			
		}.bind(this));
		
	},
		
	loadTopicQuestions: function(oTopicSelect) {
		
		var iValue = $F(oTopicSelect);
		
		var iTopic = parseInt(iValue);
		
		var sId = oTopicSelect.id;
		
		var sParams = '&action=loadTopicQuestions&topic_id=' + iTopic + '&select_id=' + sId;		
		this.request(sParams);
		
		
	},
	
	updateQuestionSelect: function(aData) {
		
		if(aData.select_id) {
			var sId = aData.select_id.replace('topic_id', 'questions');
			var oQuestions = $(sId);

			if(
				oQuestions &&
				aData.questions
			) {
				this.updateSelectOptions(oQuestions, aData.questions, true, true);
			}
		}
		
	},
		
	// loadRatingData: function(oQuestionSelect) {
	//
	// 	var sParams = '&action=loadRatingData&questions=' + $F(oQuestionSelect);
	//
	// 	this.request(sParams);
	// },
	
	// updateRatingData: function(aData) {
	//
	// 	var oRating = $('rating_data');
	// 	if(oRating) {
	// 		if(aData.rating_rows) {
	// 			oRating.innerHTML = aData.rating_rows;
	// 		} else {
	// 			oRating.innerHTML = "";
	// 		}
	// 	}
	//
	// },

	resetQuestionSelect: function(oTopicSelect) {
		var oQuestionSelect = this.getQuestionSelect(oTopicSelect);
		if(oQuestionSelect) {
			$j(oQuestionSelect).multiselect('removeAllOptions');
		}
	},

	getQuestionSelect: function(oTopicSelect) {
		var sId = oTopicSelect.id.replace('topic_id', 'questions');		
		var oQuestionSelect = $(sId);
		
		return oQuestionSelect;
	}	
		
});