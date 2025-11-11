var TsAccommodationLogin = TsAccommodationLogin || {};

(function(ns, $) {
	"use strict";

	ns.bLocked = false;

	ns.translations = {};

	ns.MyData = function() {
		window.initPasswordInputs();
	};

	ns.Timetable = function(sLocale, sDateFormat, sShortDateFormat, aClassTimes) {
		this.locale = sLocale;
		this.initializeCalendar(aClassTimes, sDateFormat, sShortDateFormat);
	};

	ns.Timetable.prototype.initializeCalendar = function(aClassTimes, sDateFormat, sShortDateFormat) {

		var defaultView = (localStorage.getItem("fcDefaultViewTeacherPortal") !== null ? localStorage.getItem("fcDefaultViewTeacherPortal") : "month");

		$('#calendar').fullCalendar({
			defaultView: defaultView,
			locale: this.locale,
			listDayAltFormat: sDateFormat,
			titleFormat: sDateFormat,
			views: {
				month: {
					titleFormat: 'MMMM YYYY'
				},
				week: {
					columnHeaderFormat: 'dd. ' + sShortDateFormat
				}
			},
			header: {
				left: 'prev,next today',
				center: 'title',
				right: 'month,agendaWeek,agendaDay,listWeek'
			},
			buttonText: {
				today: ns.translations.today,
				month: ns.translations.month,
				week: ns.translations.week,
				day: ns.translations.day,
				listWeek: ns.translations.list
			},
			//Random default events
			events: '/teacher/timetable/json',
			height: 'auto',
			timeFormat: 'HH:mm',
			editable: false,
			droppable: true, // this allows things to be dropped onto the calendar !!!
			firstDay: 1,
			slotLabelFormat: 'HH:mm',
			slotDuration: {minutes: aClassTimes['interval']},
			minTime: aClassTimes['from'],
			maxTime: aClassTimes['until'],
			eventClick: this.openEventModal.bind(this),
			viewRender: function(view, element) {  
				localStorage.setItem("fcDefaultViewTeacherPortal", view.name);
			}
		});

	};

	ns.Timetable.prototype.openEventModal = function(calEvent, jsEvent, view) {

		if(ns.bLocked === false) {

			$(document).ajaxStart(function() { Pace.restart(); });

			ns.bLocked = true;

			$.post(
				'/teacher/timetable/' + calEvent.id,
				{time: calEvent.start._i},
				function (oData) {

					$('#timetableModalBody').html(oData.body);
					$('#timetableModalLabel').text(oData.title);
					$('#block-description').text(oData.description);

					$('#timetableModalBtn').off('click');
					$('#timetableModalBtn').click(this.saveEventModal.bind(this, calEvent));

					$('.students-info-icon').click(function() {
						var iId = $(this).data('id');
						$('#modal-student').find('.modal-content').html($('#modal_content_student_'+iId).html());
						$('#modal-student').modal('show');
						$('.collapsible-box-student-'+iId).boxWidget();
					});

					$('#timetableModal').modal('show');

					$('#description-box').boxWidget();

					$('#timetableModal [data-toggle="tooltip"]').tooltip({html:true, placement: 'auto'});
					ns.bLocked = false;

				}.bind(this)
			);
		}

	};

	ns.Timetable.prototype.saveEventModal = function(calEvent) {

		$('#timetableModalBtn').attr('disabled','disabled');
		$('#timetableModalBtn').addClass('loading');

		$.post(
			'/teacher/block/'+calEvent.id+'/save',
			{description: $('#block-description').val()},
			function(oData) {
				$('#timetableModalBtn').removeClass('loading');
				$('#timetableModalBtn').removeAttr('disabled');
				if(oData.valid === true) {
					$('#successAlert').css('display', 'block');
				} else {
					$('#errorAlert').css('display', 'block');
				}
			}
		);

	};

	ns.Communication = function(sFormAction) {
		this.formEvents(sFormAction);
	};

	ns.Communication.prototype.formEvents = function(sFormAction) {

		$('#prev-week').click(function() {
			var dDate = moment($('#week-date').val());
			dDate.subtract(7, 'days');
			dDate = dDate.format('YYYY-MM-DD');

			$('#week-date').val(dDate.toString());

			$('#block-select').prop('disabled', true);

			this.form.submit();
		});

		$('#next-week').click(function() {
			var dDate = moment($('#week-date').val());
			dDate.add(7, 'days');
			dDate = dDate.format('YYYY-MM-DD');

			$('#week-date').val(dDate.toString());

			$('#block-select').prop('disabled', true);

			this.form.submit();
		});

		$('#current-week').click(function() {
			var dWeekDate = moment().startOf('isoWeek');
			dWeekDate = dWeekDate.format('YYYY-MM-DD');
			$('#week-date').val(dWeekDate.toString());

			$('#block-select').prop('disabled', true);

			this.form.submit();
		});

		$('#block-select').change(function() {
			this.form.submit();
		});

		$('#message-type-select').change(function() {
			this.form.submit();
		});

		// Duallistbox
		$('#students').bootstrapDualListbox({
			bootstrap3Compatible: true,
			filterPlaceHolder: ns.translations.filter,
			moveAllLabel: ns.translations.move_all,
			removeAllLabel: ns.translations.remove_all,
			infoTextEmpty: ns.translations.empty_list,
			infoText: ns.translations.showing_all,
			filterTextClear: ns.translations.show_all
		});

		if($('.dropzone').length > 0) {

			Dropzone.options.communicationForm = {
				url: sFormAction,
				autoProcessQueue: false,
				uploadMultiple: true,
				parallelUploads: 100,
				maxFiles: 100,
				previewsContainer: '#dropzone-previews',
				clickable: '#dropzone-previews',
				init: function() {

					this.initForm(sFormAction);

					var myDropzone = $('#communication-form').get(0).dropzone;

					myDropzone.on("successmultiple", function(files, response) {
						this.checkErrors(response)
					}.bind(this));
					myDropzone.on("errormultiple", function(files, response) {
						this.checkErrors(response)
					}.bind(this));
					myDropzone.on("addedfile", function(file, response) {

						$('.dz-message').hide();

						file.previewElement.addEventListener("click", function() {

							myDropzone.removeFile(file);

							if(myDropzone.getQueuedFiles().length <= 0) {
								$('.dz-message').show();
							}
						});
					}.bind(this));

				}.bind(this)
			};

		} else {
			this.initForm(sFormAction);
		}

	};

	ns.Communication.prototype.initForm = function(sFormAction) {

		var myDropzone = $('#communication-form').get(0).dropzone;
		// First change the button to actually tell Dropzone to process the queue.
		$("#submit-btn").click(function(e) {
			// Make sure that the form isn't actually being sent.
			e.preventDefault();
			e.stopPropagation();

			var bValid = $('#communication-form').get(0).reportValidity();

			if(bValid === true) {

				$("#submit-btn").prop( "disabled", true );
				$("#submit-btn").addClass('loading');

				if(
					myDropzone &&
					myDropzone.getQueuedFiles().length > 0
				) {
					myDropzone.processQueue();
				} else {
					$.post(
						sFormAction,
						$('#communication-form').serialize(),
						function(oData) {
							this.checkErrors(oData);
						}.bind(this)
					);
				}
			}

		}.bind(this));
	};

	ns.Communication.prototype.checkErrors = function(oData) {
		var errorAlert = $('#error-alert');
		var successAlert = $('#success-alert');

		if(oData.error === true) {
			if(oData.messages.length > 0) {
				oData.messages.forEach(function(message) {
					errorAlert.find('ul').first().append('<li>'+message+'</li>');
				});
			}
			successAlert.hide();
			successAlert.find('span').first().text('');
			errorAlert.show();
			document.documentElement.scrollTop = 0; // Für Chrome, Firefox, IE und Opera
			document.body.scrollTop = 0; // Für Safari
		} else {
			errorAlert.hide();
			errorAlert.find('ul').first().html('');
			successAlert.find('span').first().append(oData.messages[0]);
			successAlert.show();
			document.documentElement.scrollTop = 0; // Für Chrome, Firefox, IE und Opera
			document.body.scrollTop = 0; // Für Safari
			$('form').find('input, textarea').val('');
			$('.dz-preview').remove();
			$('.dz-message').show();
			$('#students').find('option').prop('selected', false);
			$('#students').bootstrapDualListbox('refresh', true);
		}

		$("#submit-btn").removeClass('loading');
		$("#submit-btn").removeAttr("disabled");
	};

	ns.Attendance = function(fLessonDuration, sFormAction) {
		$('.header-tooltip [data-toggle="tooltip"]').tooltip({html:true});
		this.InitializeSlider(fLessonDuration, sFormAction);
	};

	// TODO Wenn die Methode InitializeSlider heißt, warum sind hier dann die ganzen Events zum Navigieren?
	ns.Attendance.prototype.InitializeSlider = function(fLessonDuration, sFormAction) {

		$('#prev-week').click(function() {
			var dDate = moment($('#week-date').val());
			dDate.subtract(7, 'days');
			dDate = dDate.format('YYYY-MM-DD');

			$('#week-date').val(dDate.toString());

			$('#block-select').prop('disabled', true);

			this.form.action = sFormAction;
			this.form.submit();
		});

		$('#next-week').click(function() {
			var dDate = moment($('#week-date').val());
			dDate.add(7, 'days');
			dDate = dDate.format('YYYY-MM-DD');

			$('#week-date').val(dDate.toString());

			$('#block-select').prop('disabled', true);

			this.form.action = sFormAction;
			this.form.submit();
		});

		$('#current-week').click(function() {
			var dWeekDate = moment().startOf('isoWeek');
			dWeekDate = dWeekDate.format('YYYY-MM-DD');
			$('#week-date').val(dWeekDate.toString());

			$('#block-select').prop('disabled', true);

			this.form.action = sFormAction;
			this.form.submit();
		});

		$('#extended-view, #simple-view').click(function() {
			$('#view-type').val($(this).data('view'));

			this.form.action = sFormAction;
			this.form.submit();
		});

		$('#weekly-view, #daily-view').click(function() {
			$('#period').val($(this).data('view'));
			$('#block-select').prop('disabled', true);

			this.form.action = sFormAction;
			this.form.submit();
		});

		$('#block-select').change(function() {
			this.form.action = sFormAction;
			this.form.submit();
		});

		var aSliderInputs = $('.rangeslider');

		aSliderInputs.each(function() {

			var oInput = $(this);

			oInput.ionRangeSlider({
				type: 'single',
				min: 0,
				max: fLessonDuration,
				step: 5,
				grid: true,
				grid_num: fLessonDuration / 30,
				onChange: function() {
					var oExcusedDiv = oInput.closest('li').find('div.checkbox-container-excused');
					if(fLessonDuration !== Number(oInput.val())) {
						oExcusedDiv.show();
					} else {
						oExcusedDiv.hide();
					}
				}
			});

			var oRangeSlider = $(this).data('ionRangeSlider');
			var sSliderId = $(this).data('id');

			// TODO Box als Container ansehen, IDs rauswerfen und mit data- arbeiten?
			var aIdParts = sSliderId.split('-');
			var iInquiryId = aIdParts[0];

			var oAttendantChecbox = $('#attendant-' + iInquiryId);
			oAttendantChecbox.change(function() {
				oRangeSlider.options.onChange();
				if(!this.checked) {
					// Konsistentes Verhalten mit nicht gespeicherten Einträgen
					oInput.closest('li').find('div.checkbox-container-excused').hide();
				}
				oRangeSlider.update({
					disable: !this.checked
				});

			}).change();

		});

		$('#check-all-btn').click(function() {

			$('input.attendant-checkbox').each(function() {
				$(this).prop('checked', true);
				$(this).trigger('change');
			});

		});

		$('.attendance-edit-btn').click(function() {

			var iInquiryId = this.getAttribute('data-id');

			$('#attendance-edit-form-'+iInquiryId).toggle();

		});

		$('#show_fields_btn').click(function() {

			$('div[id^="attendance-edit-form"]').show();
			$('#show_fields_btn').hide();
			$('#hide_fields_btn').show();
		});

		$('#hide_fields_btn').click(function() {

			$('div[id^="attendance-edit-form"]').hide();
			$('#hide_fields_btn').hide();
			$('#show_fields_btn').show();
		});

	};

	ns.Reportcards = function(sDaterangepickerFormat) {
		$('.header-tooltip [data-toggle="tooltip"]').tooltip({html:true});
		this.initTable(sDaterangepickerFormat);
	};

	ns.Reportcards.prototype.initTable = function(sDaterangepickerFormat) {

		var that = this;

		$('#prev-week').click(function() {
			var dWeekDate = moment($('#week-date').val());
			dWeekDate.subtract(7, 'days');
			dWeekDate = dWeekDate.format('YYYY-MM-DD');
			$('#week-date').val(dWeekDate.toString());

			var dWeekStart = moment($('#week-start').val());
			dWeekStart.subtract(7, 'days');

			var dWeekEnd = moment($('#week-end').val());
			dWeekEnd.subtract(7, 'days');

			this.form.submit();

			$('#week-start').val(dWeekStart.toString());
			$('#week-end').val(dWeekEnd.toString());
		});

		$('#next-week').click(function() {
			var dDate = moment($('#week-date').val());
			dDate.add(7, 'days');
			dDate = dDate.format('YYYY-MM-DD');

			$('#week-date').val(dDate.toString());

			this.form.submit();
		});

		$('#current-week').click(function() {
			var dWeekDate = moment().startOf('isoWeek');
			dWeekDate = dWeekDate.format('YYYY-MM-DD');
			$('#week-date').val(dWeekDate.toString());

			this.form.submit();
		});

		$('.reportcards_table_row').click(function(e) {
			that.tableRowClick(this, e, sDaterangepickerFormat);
		});

		var oDisabledLinks = $('.disabled-link');
		oDisabledLinks.css('color', 'grey');
		oDisabledLinks.tooltip();

		$('.email-reportcard').click(function(e) {
			$(this).attr('disabled', 'disabled');
			that.sendToStudent(this, e);
		});

	};

	ns.Reportcards.prototype.tableRowClick = function(oRow, e, sDaterangepickerFormat) {

		var oTr = $(oRow);

		if($(e.target).is('i')) {

		} else {

			if(ns.bLocked === false) {

				// TODO Diese Funktion setzt einen globalen Kontext
				$(document).ajaxStart(function() { Pace.restart(); });

				ns.bLocked = true;

				var oTableRowData = this.getTableRowData(oTr);
				var sDataValues = oTableRowData.template_id + '/' + oTableRowData.inquiry_course_id + '/' + oTableRowData.examination_date + '/' + oTableRowData.examination_version_id + '/' + oTableRowData.examination_id + '/' + oTableRowData.program_service_id;
				$.get(
					'/teacher/reportcards/modal/' + sDataValues,
					function(oData) {

						if(oData.authorized === true) {

							$('#error-unauthorized-access').css('display', 'none');

							$('#reportcards_modal_body').html(oData.body);

							//Date picker
							$('#datepicker').val(oData.examination_date);
							$('#datepicker').datepicker({
								autoclose: true
							});

							var start = moment(oData.date_from).format(sDaterangepickerFormat);
							var end = moment(oData.date_until).format(sDaterangepickerFormat);
							$('#daterange').daterangepicker({
								locale: {
									format: sDaterangepickerFormat
								},
								startDate: start,
								endDate: end
							});

							$('#calculate-score-btn').click(function() {

								$.get(
									'/teacher/reportcards/modal/calculate-score' + '/' + oTableRowData.inquiry_course_id + '/' + oTableRowData.program_service_id,
									function(oData) {
										$('#score').val(oData.average_score);
									}
								);

							});

							var oSubmitButton = $('#submit-btn');
							oSubmitButton.off('click');
							oSubmitButton.click(function() {
								this.submitModalForm(oSubmitButton, oTr);
							}.bind(this));

							$('#reportcards_modal').modal('show');

							ns.bLocked = false;

						} else {
							$('#error-unauthorized-access').css('display', 'block');
						}


					}.bind(this)
				);

			}

		}

	};

	ns.Reportcards.prototype.sendToStudent = function(oSendButton, e) {

		oSendButton = $(oSendButton);

		var iVersionId = parseInt(oSendButton.data('version-id'));
		var iStudentId = parseInt(oSendButton.data('student-id'));

		$.post({
			url: '/teacher/reportcards/email/' + iVersionId + '/' + iStudentId,
		}).done(function(oData) {

			if(oData.success) {
				$('#success-alert').find('span').text(oData.message);
				$('#success-alert').css('display', 'block');
			} else {
				$('#error-alert').find('span').text(oData.message);
				$('#error-alert').css('display', 'block');
			}

			oSendButton.closest('.modal').modal('hide');
			oSendButton.removeAttr('disabled');
		});

	};

	ns.Reportcards.prototype.submitModalForm = function(oSubmitButton, oTr) {

		oSubmitButton.attr('disabled','disabled');
		oSubmitButton.addClass('loading');

		var sData = $('#reportcards_modal').find('form').serialize();
		sData += '&' + $.param(this.getTableRowData(oTr));

		$.post({
			url: '/teacher/reportcards/modal',
			data: sData
		}).done(function(oData) {
			oSubmitButton.removeClass('loading');
			oSubmitButton.removeAttr('disabled');
			if(oData.valid === true) {

				$('#successAlert').css('display', 'block');

				$('#reportcards_modal').on('hide.bs.modal', function() {
					location.reload();
				});

			} else {
				$('#errorAlert').css('display', 'block');
			}
		});

	};

	ns.Reportcards.prototype.getTableRowData = function(oTr) {
		return {
			'template_id': oTr.data('template-id'),
			'examination_date': oTr.data('examination-date'),
			'examination_id': oTr.data('examination-id'),
			'examination_version_id': oTr.data('examination-version-id'),
			'examination_term_id': oTr.data('examination-term-id'),
			'inquiry_course_id': oTr.data('inquiry-course-id'),
			'program_service_id': oTr.data('program-service-id'),
		};
	};

})(TsAccommodationLogin, jQuery);
