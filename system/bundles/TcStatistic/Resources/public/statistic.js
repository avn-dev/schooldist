
var Thebing = Thebing || {};

(function(ns, $) {
	"use strict";

	ns.Statistic = function(sBundle, sDateFormat) {
		this.bundle = sBundle;
		this.date_format = sDateFormat;
	};

	ns.Statistic.prototype.initialize = function() {
		$('[data-filter="multiselect"]').multiselect({searchable: true, sortable: false, selected_values: []});

		$('[data-filter="date"]').each(function(iKey, oCalendar) {
			oCalendar = $(oCalendar);
			$j(oCalendar).bootstrapDatePicker({
				weekStart: 1,
				todayHighlight: true,
				todayBtn: 'linked',
				//language: this.sLanguage,
				calendarWeeks: true,
				format: this.date_format,
				autoclose: true,
				assumeNearbyYear: true
			});
		}.bind(this));

		// Filter anzeigen
		var oFilterToggle = $('#filter_toggle');
		var oFilterToggleArrow = oFilterToggle.children('i').first();
		oFilterToggle.click(function() {
			var oAdditionalFiltersDiv = $('#additional_filters');
			if(oAdditionalFiltersDiv.is(':visible')) {
				oFilterToggleArrow.removeClass('fa-angle-up');
				oFilterToggleArrow.addClass('fa-angle-down');
				oAdditionalFiltersDiv.slideUp('fast');
			} else {
				oFilterToggleArrow.removeClass('fa-angle-down');
				oFilterToggleArrow.addClass('fa-angle-up');
				oAdditionalFiltersDiv.slideDown('fast');
			}

			var oSpanLabel = oFilterToggle.children('span.divToolbarToggleLabel');
			var sTmpTranslation = oSpanLabel.text();
			oSpanLabel.text(oSpanLabel.data('toggle-translation'));
			oSpanLabel.data('toggle-translation', sTmpTranslation);
		});

		// Refresh-Button
		$('#button_refresh').click(function() {
			this.executeRequest('statisticAjax', function(oData) {
				if(oData.error) {
					alert(oData.error);
				} else {
					$('#statistic_content').html(oData.table);
				}
			});
		}.bind(this));

		// Export-Button (Excel)
		$('#button_export_excel').click(function() {
			this.executeRequest('exportExcel', function(oData) {
				if(oData.error) {
					alert(oData.error);
				} else {
					var oRequestParams = this.getRequestParameters('exportExcel');
					var sUrl = oRequestParams.url + '&filters_checked=' + oData.filters_checked + '&' + oRequestParams.data;
					window.open(sUrl, '_blank', '', false);
				}
			}.bind(this));
		}.bind(this));
	};

	ns.Statistic.prototype.executeRequest = function(sAction, oCallback) {
		var oLoadingIndicator = $('#loading_indicator');
		var oRequestParams = this.getRequestParameters(sAction);

		$.ajax({
			url: oRequestParams.url,
			data: oRequestParams.data,
			dataType: 'json',
			beforeSend: function() {
				oLoadingIndicator.show();
			},
			error: function(oXhr, sStatus) {
				if(sStatus === 'parsererror') {
					$('#statistic_content').html(oXhr.responseText);
				} else {
					alert('Error: ' + sStatus);
				}
			},
			success: oCallback,
			complete: function() {
				oLoadingIndicator.hide();
			}
		});
	};

	ns.Statistic.prototype.getRequestParameters = function(sAction) {
		return {
			url: '/wdmvc/' + this.bundle + '/statistic/' + sAction + window.location.search,
			data: $j('[data-filter]').serialize()
		};
	};

})(Thebing, $j);
