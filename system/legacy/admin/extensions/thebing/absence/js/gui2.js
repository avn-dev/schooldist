
var Absence = Class.create(ATG2, {

	resizeTableBody: function($super) {

		if($('divDays')) {

			// Größe des Fensters bestimmen;
			var iHeight = this.getDocumentHeight();
			var iWidth = this.getDocumentWidth();

			// Höhe der oberen Leiste auslesen
			var oDivHeader = $('divHeader_' + this.hash);
			if(oDivHeader) {
				var iBarHeight = oDivHeader.getHeight();
				iHeight = iHeight - iBarHeight - 4;
			} else {
				iHeight = iHeight + 1;
			}

			var oDivHeader = $('divAbsenceHeader_' + this.hash);
			if(oDivHeader) {
				var iBarHeight = oDivHeader.getHeight();
				iHeight = iHeight - iBarHeight;
			}

			if($('Gui2ChildTableButton_' + this.hash)) {
				var iChildHeadHeight = $('Gui2ChildTableButton_' + this.hash).up('.Gui2ChildTableButtonContainer').getHeight();
				iHeight = iHeight - iChildHeadHeight;
			}

			// Höhe der unteren Leiste auslesen
			if($('divFooter' + '_' + this.hash)) {
				var iBottomBarHeight = $('divFooter' + '_' + this.hash).getHeight();
				iHeight = iHeight - iBottomBarHeight;
			}

			// Neue Tabellenhöhe schreiben
			if(
				iHeight > 0
			) {

				aDim = $('guiTableHead_' + this.hash).getDimensions();
				var iTableHead = aDim.height - 6;

				if(iTableHead <= 0)
				{
					iTableHead = 33;
				}

				var aColumns = $j('#tblTableHead').children('thead').children('tr').children('.monthDays');
				iColumns = aColumns.length;

				iInnerHeight = iHeight - iTableHead - 5;

				$('guiScrollBody').style.height	= iInnerHeight + 'px';

				$('divDays').style.height = iHeight + 'px';

				if(bGlobalLocation == 'accommodation')
				{
					$('divDays').style.height = (iHeight ) + 'px';
				}

				var iContainerWidth = iWidth;

				iWidth = iWidth - (iColumns * 17) - 16 - 12;

				if(iWidth < 180) {
					iWidth = 180;
				}

				var iCompleteWidth = iWidth + (iColumns * 17) + 12;

				var iScrollBarWidth = 16;

				if(oGui) {
					oGui.testBoxModel();
					iScrollBarWidth = oGui.iScrollBarWidth;
				}

				$('guiTableHead_' + this.hash).style.width = iContainerWidth + 'px';
				$('guiScrollBodyContainer').style.width = (iContainerWidth - iScrollBarWidth)+ 'px';

				$('tblTableHead').style.width = iCompleteWidth + 'px';
				$('tblScrollBody').style.width = iCompleteWidth + 'px';

				if($('teacher_column_head')) {
					$('teacher_column_head').style.width = iWidth + 'px';
				}
				if($('teacher_column_body')) {
					$('teacher_column_body').style.width = iWidth + 'px';	
				}
				if($('teacher_column_body_col')) {
					$('teacher_column_body_col').style.width = iWidth + 'px';	
				}

				if(bGlobalLocation == 'accommodation') {
					Event.observe($('guiScrollBody'), 'scroll', function()
					{
						$('tblTableHead').style.position = 'relative';

						var aOffsets = $('guiScrollBody').cumulativeScrollOffset();

						$('tblTableHead').style.left = (aOffsets[0] * -1) + 'px';
					});
				}
			}

		}
		
	},

	requestCallbackHook: function($super, aData) {

		if(aData.action=='loadAbsencesList')
		{
			loadAbsencesList();
		}
		else if(
			(
				aData.action=='openDialog' ||
				aData.action=='saveDialogCallback'
			) &&
			(
				aData.data.action == 'new'
			)
		){
			this.executeRequestUntil('calculateUntil',aData.data.id);
		}
		else if(aData.action=='refreshAbsenceData'){
			var oData		= aData.data;
			var sIdMain		= 'save['+this.hash+']['+oData.id+']';
			if(oData){
				var oRefresh = oData.refresh;
				var oRefreshObj;

				if(oRefresh){
					if(oRefresh.until){
						oRefreshObj = $(sIdMain+'[until]');
						if(oRefreshObj){
							this.updateCalendarValue(oRefreshObj, oRefresh.until);
						}
					}

					if(oRefresh.days){
						var oDays = $(sIdMain+'[days]');
						if(oDays){
							oDays.value = oRefresh.days;
						}
					}

				}
			}
		}
	},

	calendarCloseHandler : function($super, oInput, oDate, bForFilter) {
		$super(oInput, oDate, bForFilter);

		var sRegex	= new RegExp('^save\\[' + this.hash + '\\]\\[(.*)\\]\\[(.*)\\]$', 'g');
		var aRegex	= sRegex.exec(oInput.id);
		var sId		= aRegex[1];
		var sColumn	= aRegex[2];

		if(sColumn=='from')
		{
			this.executeRequestUntil('calculateUntil',sId);
		}
		else
		{
			this.executeRequestUntil('calculateDays',sId);
		}
	},

	calculateUntil : function(aData){
		this.executeRequestUntil('calculateUntil',aData.id);
	},

	calculateDays : function(aData){
		this.executeRequestUntil('calculateDays',aData.id);
	},

	executeRequestUntil : function(sTask, sId){
		var sParam = this.getCalculateParams(sTask, sId);
		this.request(sParam);
	},

	getCalculateParams : function(sTask, sId){
		var sParam	= '&task='+sTask;
		sParam += '&dialog_id='+sId;
		var sMainId = 'save['+this.hash+']['+sId+']';

		var oInputFrom	= $(sMainId+'[from]');
		var oInputUntil = $(sMainId+'[until]');
		var oInputDays	= $(sMainId+'[days]');

		if(oInputFrom)
		{
			sParam += '&from='+oInputFrom.value;
		}
		if(oInputFrom)
		{
			sParam += '&until='+oInputUntil.value;
		}
		if(oInputFrom)
		{
			sParam += '&days='+oInputDays.value;
		}

		return sParam;
	}

});