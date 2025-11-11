
var AccessMatrixGui = Class.create(CoreGUI,
{
	requestCallbackHook: function(aData)
	{
		var sTask = aData.action;
		var sAction = aData.data.action;

		if(aData.task && aData.task != '')
		{
			sTask = aData.task;
			sAction = aData.action;
		}

		aData = aData.data;

		// Horizontales Scrollen mit position sticky ermöglichen
		$j('.accessOne').closest('.GUIDialogContentDiv').css('overflow', 'auto');

		/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

		if(
			sAction == 'openAccessDialog' || 
			sAction == 'saveAccessDialogCallback' || 
			(
				sAction == 'saveDialogCallback' &&
				aData.action == 'openAccessDialog'
			)
		) {

			if(sAction == 'saveAccessDialogCallback') {

				this.displaySuccess(this.sCurrentDialogId);
				
				this.request('&task=openDialog&action=openAccessDialog');

				return;
			}

			if (aData.save_id) {
				const head = document.querySelector('th[data-scroll-id="' + aData.save_id + '"]');
				if (head) {
					head.style.backgroundColor = '#ffeb9c';
					head.scrollIntoView(false);
				}
			}

			this.aMatrixData = aData['aMatrixData'];
			this.aMatrixCellColors = aData['aMatrixCellColors'];

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

			var aAccessDDs = $A($$('.accessOne'));

			aAccessDDs.each(function(oSelect)
			{
				Event.observe(oSelect, 'change', function() {
					this.switchAccessCellColor(oSelect);
				}.bind(this));
			}.bind(this));

			/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

			const self = this;
			$j('.accessGroup').on('change', function () {
				const dependencies = $j('select[data-group-class=' + $j(this).data('user-class') + ']');
				dependencies.each(function () {
					self.switchAccessCellColor(this);
				});
			});

		}
	},

	switchAccessCellColor: function(oSelect) {
		const td = $j(oSelect).parent();
		const userId = $j(oSelect).data('user-id');

		// Auswahl im Select pro User überschreibt Group
		if (oSelect.value === '1') {
			td.css('background-color', this.aMatrixCellColors['green']);
		} else if(oSelect.value === '0') {
			td.css('background-color', this.aMatrixCellColors['red']);
		} else {
			td.css('background-color', this.aMatrixCellColors['red']);

			const groupCheckboxes = document.querySelectorAll('input[data-user-class=' + $j(oSelect).data('group-class') + ']');
			switchColor:
			for (const checkbox of groupCheckboxes) {
				const groupId = $j(checkbox).data('group-id');
				if (checkbox.checked) {
					for (const matrix of this.aMatrixData) {
						if (
							matrix[1].user_id === userId.toString() &&
							matrix[1]['user_groups'][groupId]
						) {
							td.css('background-color', this.aMatrixCellColors['green']);
							break switchColor;
						}
					}
				}
			}
		}
	}
});