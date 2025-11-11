
var NumberRanges = Class.create(AccessMatrixGui, {
	
	requestCallbackHook : function ($super, aData){

		$super(aData);
		
		if(
			(
				aData.action == 'openDialog' ||
				aData.action == 'saveDialogCallback'
			) &&
			aData.data.additional == null &&
			(
				aData.data.action == 'new' ||
				aData.data.action == 'edit'
			)
		) {

			var oTempElement;
			$$('#content_'+aData.data.id+'_'+this.hash+' .reload_preview').each(function(oElement) {
				oElement.observe('keyup', function(oEvent, oElement) {
					this.waitForInputEvent('executeInputEvent', oEvent, oElement);
				}.bindAsEventListener(this, oElement));
				oTempElement = oElement;
			}.bind(this));
	
			this.executeInputEvent(oTempElement);

		}
		
	},

	executeInputEvent : function(oElement) {

		var sIdPrefix = oElement.id.replace(/\[[a-z_]+\]\[tc_nr\]$/, '');

		var iOffsetAbs = $F(sIdPrefix+'[offset_abs][tc_nr]');
		var iOffsetRel = $F(sIdPrefix+'[offset_rel][tc_nr]');
		var iDigits = $F(sIdPrefix+'[digits][tc_nr]');
		var sFormat = $F(sIdPrefix+'[format][tc_nr]');

		var oDate = new Date();

		var sCounter = iOffsetAbs.toString();
		sCounter = sCounter.padLeft(iDigits);
		var sMonth = (oDate.getMonth()+1).toString();
		sMonth = sMonth.padLeft(2);
		var sDay = oDate.getDate().toString();
		sDay = sDay.padLeft(2);

		var sPreview = sFormat;

		var iShortYear = oDate.getYear()+1900;

		sPreview = sPreview.replace(/%Y/, oDate.getYear()+1900);
		sPreview = sPreview.replace(/%y/, iShortYear.toString().substr(2));
		sPreview = sPreview.replace(/%m/, sMonth);
		sPreview = sPreview.replace(/%d/, sDay);
		sPreview = sPreview.replace(/%count/, sCounter);

		var oPreview = $$('#preview_container .GUIDialogRowInputDiv');

		oPreview.first().update('<div style="padding-top: 10px;">'+sPreview+'</div>');

	}
	
});