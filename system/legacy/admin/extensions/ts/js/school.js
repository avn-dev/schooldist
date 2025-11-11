
var SchoolGui = Class.create(CoreGUI, {
	
	requestCallbackHook: function ($super, aData){

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
			this.initializeTeacherLoginFields(aData.data);
		}
		
	},

	initializeTeacherLoginFields: function (data) {

		var periods = $j('#dialog_wrapper_' + data.id + '_' + this.hash).find('.teacherlogin_view_period');

		periods.each(function () {

			var periodField = $j(this);
			var beforeValue = $j(this).closest('.GUIDialogRowInputDiv').find('input[id*="_before_value]"]')
			var beforeMode = $j(this).closest('.GUIDialogRowInputDiv').find('select[id*="_before_mode]"]')
			var afterValue = $j(this).closest('.GUIDialogRowInputDiv').find('input[id*="_after_value]"]')
			var afterMode = $j(this).closest('.GUIDialogRowInputDiv').find('select[id*="_after_mode]"]')

			var intervals = $j(this).val().split(',').map((interval) => interval.match(/([P])([0-9]+)([D|W])+/i));
			var before = intervals[0] ?? null;
			var after = intervals[1] ?? null;
			var refreshPeriodField = function () {
				var value = [];
				if (before) {
					value.push([before[1], before[2], before[3]].join(''));
				}
				if (after) {
					if (value.length === 0) value.push('')
					value.push([after[1], after[2], after[3]].join(''));
				}
				periodField.val(value.join(','))
			}

			var init = function (interval, fieldValue, fieldMode, onUpdate) {

				var isNumber = new RegExp(/^\d+$/);

				if (interval) {
					$j(fieldValue).val(interval[2])
					$j(fieldMode).val(interval[3])
				}

				$j(fieldValue).keyup(function () {
					if ($j(this).val() !== '' && isNumber.test($j(this).val())) {
						interval = ['', 'P', $j(this).val(), $j(fieldMode).val()];
					} else {
						interval = null;
					}
					onUpdate(interval);
				})

				$j(fieldMode).change(function () {
					if ($j(fieldValue).val() !== '' && isNumber.test($j(fieldValue).val())) {
						interval = ['', 'P', $j(fieldValue).val(), $j(this).val()];
					} else {
						interval = null;
					}
					onUpdate(interval);
				})
			}

			if (beforeValue[0] && beforeMode[0]) {
				init(before, beforeValue, beforeMode, (newInterval) => {
					before = newInterval;
					refreshPeriodField();
				})
			}

			if (afterValue[0] && afterMode[0]) {
				init(after, afterValue, afterMode, (newInterval) => {
					after = newInterval
					refreshPeriodField();
				})
			}
		});

	}

});

