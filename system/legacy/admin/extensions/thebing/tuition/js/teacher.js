
function sendPassword() {

	var guiHash = Object.keys(aGUI)[0];
	var oGui = aGUI[guiHash];

	var sParam = '&task=sendPassword';

	oGui.request(sParam);

}

function executeHook_teacher_gui2_request_callback_hook(sHook, mInput, mData) {

	if(
		mInput.action == 'sendPassword'
	) {

		var oGui = aGUI[mData];

		if(mInput.success == 1) {
			oGui.displaySuccess(mInput.data.id, mInput.message);
		} else {
			oGui.displayErrors(mInput.errors, mInput.data.id);
		}

	} else if(
        mInput.data &&
		(
            mInput.data.action == 'new' ||
            mInput.data.action == 'edit'
		) &&
		mInput.data.id.indexOf('TEACHER_SALARY_') != -1
	) {

		var sCategorySelectId = 'save['+mData+']['+mInput.data.id+'][costcategory_id][kts]';
		var oCategorySelect = $(sCategorySelectId);

		if(oCategorySelect){
			Event.observe(oCategorySelect, 'change', function(oEvent, sHash, sId) {
				switchSalaryFields(oCategorySelect, sHash, sId);
			}.bindAsEventListener(oCategorySelect, mData, mInput.data.id));

			switchSalaryFields(oCategorySelect, mData, mInput.data.id);

		}
		
	}

	return mInput;
	
}

function switchSalaryFields(oCategorySelect, sHash, sDialogId) {

	var oSalaryContainer = $('salary_container_'+sHash);



	if(oSalaryContainer){
	
		var oLabel = oSalaryContainer.down('.GUIDialogRowLabelDiv div');
		var oInput = oSalaryContainer.down('.GUIDialogRowInputDiv input');
	
		if($F(oCategorySelect) == -1) { 
			oSalaryContainer.show();
			if(oInput){
				oInput.addClassName('required');
			}

			if(oLabel){
				oLabel.addClassName('required');
			}		

		} else {
			oSalaryContainer.hide();
			if(oInput){
				oInput.removeClassName('required');
			}

			if(oLabel){
				oLabel.removeClassName('required');
			}


		}
	}
	

}

oWdHooks.addHook('gui2_request_callback_hook', 'teacher');