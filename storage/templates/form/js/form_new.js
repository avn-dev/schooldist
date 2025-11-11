
var Thebing = Thebing || {};

(function(ns, $) {
	"use strict";

	var oDynamicDataHandlers = {
		'TriggerAjaxRequest': TriggerAjaxRequest,
		'AjaxContainerChange': AjaxContainerChange,
		'AjaxListResult': AjaxListResult,
		'KeepValueSynced': KeepValueSynced,
		'StaticSelectOptions': StaticSelectOptions,
		'DependencyVisibility': DependencyVisibility,
		'DependencyRequirement': DependencyRequirement,
		'DuplicateableContainer': DuplicateableContainer,
		'SelectOptionsInRange': SelectOptionsInRange,
		'SelectOptionsMap': SelectOptionsMap,
		'SelectOptionsLookup': SelectOptionsLookup,
		'ValidateInput': ValidateInput,
		'UpdateToValue':UpdateToValue
	};

	ns.initializeForm = function(oForm) {

		if(!$(oForm).data('dynamic-form-initialized')) {
			$(oForm).data('dynamic-form-initialized', true);
			new FormObject(oForm);
		} else {
			console.log('duplicate!');
		}

	};

	function FormObject(oForm) {

		this.oForm = $(oForm);
		this.oPendingAjaxTimeouts = {};
		this.oPendingAjaxCalls = {};
		this.iAjaxRequestDelayMs = 250;
		this.iLoadingDelayMs = 100;
		this.aEmptyValues = [null, '', '0'];
		this.bInitializeEventFired = false;
		var bSuccess = this.initialize();

		if(this.logIsAvailable()) {
			if(bSuccess) {
				console.log('From initialization successful :)', oForm);
			} else {
				console.error('From initialization failed :(', oForm);
			}
		}

		if(typeof FormData === 'undefined') {
			alert('Your browser is not supported!')
		}

	}

	FormObject.prototype.initialize = function() {

		this.removeListenerEvents();
		this.removeDataAttributes();

		if(
			!this.initializeDynamicData() ||
			!this.initializePageNavigation() ||
			!this.initializeSubmitEventListener() ||
			!this.updateValidationState()
		) {
			this.removeListenerEvents();
			this.removeDataAttributes();
			return false;
		}

		this.triggerListenerEvents();
		return true;

	};

	FormObject.prototype.removeListenerEvents = function() {

		this.oForm.find('*').each(function(iIndex, oElement) {
			oElement = $(oElement);
			oElement.unbind('change');
			oElement.unbind('click');
			oElement.unbind('focusout');
			oElement.unbind('dynamicFormInitialize');
			//oElement.unbind('dynamicFormAjax');
			//oElement.unbind('dynamicFormSync');
			oElement.unbind('dynamicFormValidate');
		});

		this.oForm.off('dynamicFormAjax');
		this.oForm.off('dynamicFormSync');

	};

	FormObject.prototype.removeDataAttributes = function() {

		this.oForm.find('*').each(function(iIndex, oElement) {
			oElement = $(oElement);
			oElement.removeData('dynamic-validator-chain');
			oElement.removeAttr('data-dynamic-validator-chain');
		});

	};

	FormObject.prototype.triggerListenerEvents = function() {

		this.oForm.find('*').each(function(iIndex, oElement) {
			oElement = $(oElement);
			oElement.trigger('dynamicFormInitialize');
		});

		// Da die Container immer wieder das ganze Formular einfach neu initialisieren…
		if(!this.bInitializeEventFired) {
			this.bInitializeEventFired = true;
			this.oForm.trigger('dynamicFormInitializeCompleted');
		}

	};

	FormObject.prototype.initializeDynamicData = function() {

		var oFormObject = this;
		var bSuccess = true;

		this.oForm.find('*[data-dynamic-config]').each(function(iIndex, oElement) {
			$.each(
				$(oElement).data('dynamic-config'),
				function(iIndex, oValue) {
					if(oDynamicDataHandlers.hasOwnProperty(oValue['type'])) {
						new oDynamicDataHandlers[oValue['type']](oFormObject, oElement, oValue['data']);
					} else {
						if(oFormObject.logIsAvailable()) {
							console.error('Invalid listener type!', oValue['type'], oElement);
							bSuccess = false;
						}
					}
				}
			);
		});

		return bSuccess;

	};

	FormObject.prototype.initializeSubmitEventListener = function() {

		this.oForm.bind(
			'dynamicFormAjax.submitSend',
			{
				oFormObject: this
			},
			function(event) {
				var oFormObject = event.data.oFormObject;
				oFormObject.resetFormErrorMessages();
			}
		);

		this.oForm.bind(
			'dynamicFormAjax.submitCallback',
			{
				oFormObject: this
			},
			function(event, oResultData) {
				var oFormObject = event.data.oFormObject;
				if(!oResultData.hasOwnProperty('result')) {
					if(oFormObject.logIsAvailable()) {
						console.error('Missing key "result" in ajax response data!');
					}
					return;
				}
				if(!oResultData.hasOwnProperty('block_errors')) {
					if(oFormObject.logIsAvailable()) {
						console.error('Missing key "block_errors" in ajax response data!');
					}
					return;
				}
				if(!oResultData.hasOwnProperty('form_errors')) {
					if(oFormObject.logIsAvailable()) {
						console.error('Missing key "form_errors" in ajax response data!');
					}
					return;
				}
				if(oResultData['result'] === 'success') {

					oFormObject.resetFormErrorMessages();

					if(oResultData.hasOwnProperty('success_message')) {
						this.oForm.find('.success-message').html(oResultData['success_message']);
					} else {
						console.error('success_message is missing!');
					}

					this.oForm.trigger('dynamicFormSubmitSuccessful');

					oFormObject.pageNavigationSuccess();
					if(oFormObject.logIsAvailable()) {
						console.log('Form submit successful :)', oFormObject.oForm.get(0));
					}
					return;
				}
				oFormObject.setBlockErrorMessages(oResultData['block_errors']);
				oFormObject.setFormErrorMessages(oResultData['form_errors']);
				oFormObject.validateChilds(oFormObject.oForm, false);
				oFormObject.pageNavigationGotoFirstError();
			}.bind(this)
		);

		this.oForm.bind(
			'dynamicFormAjax.submitComplete',
			{
				oFormObject: this
			},
			function(event, sStatus) {
				var oFormObject = event.data.oFormObject;
				oFormObject.unlockNavigation();
			}
		);

		return true;

	};

	FormObject.prototype.logIsAvailable = function() {

		return console && console.log && console.error;

	};

	FormObject.prototype.setSelectOptions = function(oElement, aData, bNoChangeEvent) {

		oElement = $(oElement);
		var sOriginalValue = oElement.val();

		if(!aData) {
			return false;
		}

		oElement.empty();
		$.each(aData, function(iIndex, mValue) {

			if($.isArray(mValue)) {
				this.setSelectOption(oElement, mValue);
			} else {
				var oOptGroup = document.createElement('optgroup');
				oOptGroup.label = mValue.label;
				$.each(mValue.select_options, function(iIndex, aOption) {
					this.setSelectOption(oOptGroup, aOption);
				}.bind(this));
				oElement.append(oOptGroup);
			}

		}.bind(this));

		if(sOriginalValue) {
			oElement.val(sOriginalValue);
		}

		if(
			!bNoChangeEvent &&
			sOriginalValue !== oElement.val()
		) {
			oElement.trigger('change');
		}

		return true;

	};

	FormObject.prototype.setSelectOption = function(oElement, aOption) {

		// Index 0 und 1 sind Wert und Titel
		var oOption = new Option(aOption[1], aOption[0]);
		// Index 2 ist (wenn angegeben) das was in das "class"-Attribut soll (CSS-Klassen)
		if(aOption[2]) {
			oOption.setAttribute('class', aOption[2]);
		}
		oElement.append(oOption);

	};

	FormObject.prototype.preSelectOption = function(oElement, oOptions) {

		var sSelector = ':not([value=0])';
		if(oOptions.hasOwnProperty('class')) {
			sSelector += '.' + oOptions.class;
		}

		if(oOptions.order === 'last') {
			oElement.children(sSelector).last().prop('selected', true);
		} else {
			oElement.children(sSelector).first().prop('selected', true);
		}

		setTimeout(function() {
			// Validator kommt manchmal nicht hinterher, wenn das sofort passiert
			oElement.change();
		}, 100);

	};

	FormObject.prototype.findSingleRelatedElement = function(oElement, sDynamicIdentifier) {

		var oRoot = $(oElement).closest('form[data-dynamic-form],*[data-duplicateable="area"]').first();
		var oRelatedElements = $(oRoot).find('*[data-dynamic-identifier="'+sDynamicIdentifier+'"]');

		if(oRelatedElements.length !== 1) {
			if(this.logIsAvailable()) {
				var sLogMessage = 'Failed to find single related element! Result count: ' + oRelatedElements.length;
				console.error(sLogMessage, oElement, sDynamicIdentifier);
			}
			return null;
		}

		return oRelatedElements.first();

	};

	FormObject.prototype.findMultipleRelatedElements = function(oElement, sDynamicIdentifier) {

		var oRoot = $(oElement).closest('form[data-dynamic-form]').first();
		var oRelatedElements = $(oRoot).find('*[data-dynamic-identifier="'+sDynamicIdentifier+'"]');

		if(oRelatedElements.length < 1) {
			if(this.logIsAvailable()) {
				console.error('Did not find any related elements, that seems wrong!', oElement, sDynamicIdentifier);
			}
		}

		return oRelatedElements;

	};

	FormObject.prototype.resolveDependency = function(oElement, sIdentifierDefinition) {

		var aIdentifier = sIdentifierDefinition.split(":");
		var sType = aIdentifier[0];
		var sIdentifier = aIdentifier[1];
		var oDependency = $();

		if(sIdentifier === null) {
			if(this.logIsAvailable()) {
				console.error('No dependency identifier specified!', oElement, sIdentifierDefinition);
			}
			return oDependency;
		}

		if(sType === 'all') {
			var oRelatedElements = this.findMultipleRelatedElements(oElement, sIdentifier);
			if(oRelatedElements) {
				oDependency = $(oRelatedElements);
			}
		} else if(sType === 'single') {
			var oRelatedElement = this.findSingleRelatedElement(oElement, sIdentifier);
			if(oRelatedElement) {
				oDependency = $(oRelatedElement);
			}
		} else {
			if(this.logIsAvailable()) {
				console.error('Invalid dependency type: ' + sType, oElement, sIdentifierDefinition);
			}
		}

		return oDependency;

	};

	FormObject.prototype.lookupValue = function(oData, sIdentifierDefinition, oElement) {

		var aIdentifier = sIdentifierDefinition.split(':');
		var sIdentifier = aIdentifier[1];

		if(sIdentifier === null) {
			if(this.logIsAvailable()) {
				console.error('No identifier specified!', oElement, sIdentifierDefinition);
			}
			return null;
		}

		if(!oData.hasOwnProperty(sIdentifier)) {
			return null;
		}

		var sValue = 'v'+$(oElement).val();

		if(!oData[sIdentifier].hasOwnProperty(sValue)) {
			return null;
		}

		return oData[sIdentifier][sValue];

	};

	FormObject.prototype.walkDependencies = function(oElement, aDependencies, oCallbackNode, oCallbackLeaf) {

		var oFormObject = this;

		$.each(aDependencies, function(iIndex, oDependency) {
			if(!oDependency.hasOwnProperty('type')) {
				if(oFormObject.logIsAvailable()) {
					console.error('Missing key "type" in dependency specification!', oElement, oDependency);
				}
				return;
			}
			if(oDependency['type'] === 'Field') {
				if(!oDependency.hasOwnProperty('name')) {
					if(oFormObject.logIsAvailable()) {
						console.error('Missing key "name" in dependency specification!', oElement, oDependency);
					}
					return;
				}
				var oNewElement = oFormObject.findSingleRelatedElement(oElement, oDependency['name']);
				if(oNewElement) {
					var aNewDependencies = oCallbackNode(oNewElement, oDependency);
					oFormObject.walkDependencies(oNewElement, aNewDependencies, oCallbackNode, oCallbackLeaf);
				}
			} else {
				oCallbackLeaf(oElement, oDependency);
			}
		});

	};

	/**
	 * Methode basiert auf der Annahme, dass oCallbackNode mit Closures arbeitet (Callback-Hölle)
	 */
	FormObject.prototype.walkDefinitions = function(oElement, aDefinitions, oCallbackNode) {

		var oFormObject = this;

		$.each(aDefinitions, function(iIndex, oDefinition) {
			if(!oDefinition.hasOwnProperty('field')) {
				if(oFormObject.logIsAvailable()) {
					console.error('Missing key "field" in definition!', oElement, oDefinition);
				}
				return;
			}
			if(!oDefinition.hasOwnProperty('options')) {
				if(oFormObject.logIsAvailable()) {
					console.error('Missing key "options" in definition!', oElement, oDefinition);
				}
				return;
			}
			if(!oDefinition.hasOwnProperty('childs')) {
				if(oFormObject.logIsAvailable()) {
					console.error('Missing key "childs" in definition!', oElement, oDefinition);
				}
				return;
			}
			oFormObject.resolveDependency(oElement, oDefinition['field']).each(function(iIndex, oNewElement) {
				oCallbackNode(oNewElement, oDefinition['field'], oDefinition['options']);
				oFormObject.walkDefinitions(oNewElement, oDefinition['childs'], oCallbackNode);
			});
		});

	};

	FormObject.prototype.sendAjax = function(sTask, oAdditionalData, oOnErrorCallback) {

		var sTaskIdentifier = sTask;
		var oFormObject = this;

		var oFormData = new FormData();
		oFormData.append('get_request', sTask);
		oFormData.append('task', sTask);
		this.fillFormData(oFormData, sTask);

		if(oAdditionalData) {
			$.each(oAdditionalData, function(sKey, sValue) {
				oFormData.append(sKey, sValue);
				sTaskIdentifier += sKey + '=' + sValue;
			});
		}

		if(!oOnErrorCallback) {
			oOnErrorCallback = function() {};
		}

		if(oFormObject.oPendingAjaxTimeouts.hasOwnProperty(sTaskIdentifier)) {
			window.clearTimeout(oFormObject.oPendingAjaxTimeouts[sTaskIdentifier]);
			delete oFormObject.oPendingAjaxTimeouts[sTaskIdentifier];
		}

		if(oFormObject.oPendingAjaxCalls.hasOwnProperty(sTaskIdentifier)) {
			oFormObject.oPendingAjaxCalls[sTaskIdentifier].abort();
			delete oFormObject.oPendingAjaxCalls[sTaskIdentifier];
		}

		oFormObject.oPendingAjaxTimeouts[sTaskIdentifier] = window.setTimeout(
			function() {
				delete oFormObject.oPendingAjaxTimeouts[sTaskIdentifier];
				var oForm = oFormObject.oForm;
				//oForm.trigger('dynamicFormAjax.'+sTask+'Send', []);
				oFormObject.oPendingAjaxCalls[sTask] = $.ajax({
					type: 'POST',
					url: window.location.href,
					data: oFormData,
					contentType: false,
					processData: false,
					beforeSend: function() {
						oForm.trigger('dynamicFormAjax.'+sTask+'beforeSend');
					},
					success: function(mData) {
						var oData = null;
						if(mData) {
							try {
								if(mData.substr(0, 1) !== '{') {
									throw 'String did not start with "{"';
								}
								oData = JSON.parse(mData);
							} catch(oException) {
								if(oFormObject.logIsAvailable()) {
									console.error('Exception during JSON.parse()!', oForm, oException);
								}
								oOnErrorCallback();
								return;
							}
						}
						if(!oData) {
							if(oFormObject.logIsAvailable()) {
								console.error('Failed to parse ajax response!', oForm, mData);
							}
							oOnErrorCallback();
							return;
						}
						if(!oData.hasOwnProperty('method')) {
							if(oFormObject.logIsAvailable()) {
								console.error('Missing key "method" in ajax response!', oForm, mData);
							}
							oOnErrorCallback();
							return;
						}
						if(!oData.hasOwnProperty('data')) {
							if(oFormObject.logIsAvailable()) {
								console.error('Missing key "data" in ajax response!', oForm, mData);
							}
							oOnErrorCallback();
							return;
						}
						oForm.trigger(
							'dynamicFormAjax.'+oData['method'],
							[ oData['data'] ]
						);
					},
					error: function(oRequest, sStatus, oException) {
						if(
							sStatus !== 'abort' &&
							oFormObject.logIsAvailable()
						) {
							console.error('jQuery.ajax(): request failed!', oRequest, sStatus, oException, oForm);
						}

						oOnErrorCallback();
					},
					complete: function(oRequest, sStatus) {
						if(
							sStatus !== 'abort' &&
							oFormObject.oPendingAjaxCalls.hasOwnProperty(sTaskIdentifier)
						) {
							delete oFormObject.oPendingAjaxCalls[sTaskIdentifier];
						}
						oForm.trigger(
							'dynamicFormAjax.'+sTask+'Complete',
							[ sStatus ]
						);
					}
				});
			},
			oFormObject.iAjaxRequestDelayMs
		);

	};

	/**
	 * @param {FormData} oFormData
	 * @param {String} sTask
	 */
	FormObject.prototype.fillFormData = function(oFormData, sTask) {

		var aData = this.oForm.serializeArray();
		$.each(aData, function(iIndex, oParam) {
			oFormData.append(oParam.name, oParam.value);
		});

		if(sTask === 'submit') {
			$('input[type=file]').each(function(iIndex, oFileUpload) {
				if(
					oFileUpload.files &&
					oFileUpload.files[0]
				) {
					oFormData.append(oFileUpload.name, oFileUpload.files[0]);
				}
			});
		}

	};

	FormObject.prototype.registerValidator = function(oElement, oValidatorCallback) {

		if(!oElement) {
			if(this.logIsAvailable()) {
				var sMessage = 'No element specified!';
				console.error(sMessage);
			}
			return;
		}

		oElement = $(oElement);

		if(!oElement.data('dynamic-validator-chain')) {
			oElement.data('dynamic-validator-chain', new ValidatorChain(this, oElement));
		}

		if(!oElement.attr('data-validateable-result')) {
			oElement.attr('data-validateable-result', 'unknown');
		}

		var oValidatorChain = oElement.data('dynamic-validator-chain');
		oValidatorChain.aValidators.push(oValidatorCallback);

	};

	FormObject.prototype.isEmail = function(sInput) {

		var sRegExp = /^[a-z0-9!#$%&'*+/=?^_`{|}~-]+(?:\.[a-z0-9!#$%&'*+/=?^_`{|}~-]+)*@(?:[a-z0-9](?:[a-z0-9-]*[a-z0-9])?\.)+[a-z0-9](?:[a-z0-9-]*[a-z0-9])?$/i;
		return sRegExp.test(sInput);

	};

	FormObject.prototype.isTime = function(sInput) {

		var sRegExp = /^([0-1]{0,1}[0-9]{1}|2[0-3]{1}):[0-5]{0,1}[0-9]{1}(|:[0-5]{0,1}[0-9]{1})$/;
		return sRegExp.test(sInput);

	};

	FormObject.prototype.isInteger = function(sInput) {

		var sRegExp = /^[0-9]+$/;
		return sRegExp.test(sInput);

	};

	FormObject.prototype.updateValidationState = function() {

		var oFormObject = this;
		var oForm = oFormObject.oForm;

		if(oForm.data('validateable') !== 'form') {
			if(this.logIsAvailable()) {
				var sLogMessage = 'Form element is not marked as validateable.';
				console.error(sLogMessage, this.oForm);
			}
			return false;
		}

		oForm.find('*[data-validateable="page"]').each(function(iIndex, oPage) {
			oFormObject.updateValidationResult(oPage);
		});
		oFormObject.updateValidationResult(oForm);

		return true;

	};

	FormObject.prototype.updateValidationResult = function(oElement) {

		oElement = $(oElement);

		if(
			oElement.data('validateable') !== 'form' &&
			oElement.data('validateable') !== 'page'
		) {
			if(this.logIsAvailable()) {
				var sLogMessage = 'Element is not a validateable container';
				console.error(sLogMessage, oElement);
			}
			return;
		}

		var bUnknown = false;
		var bError = false;
		var sSuccessClass = 'validate-success-form';
		var sErrorClass = 'validate-error-form';
		if(oElement.data('validateable') === 'page') {
			sSuccessClass = 'validate-success-page';
			sErrorClass = 'validate-error-page';
		}

		oElement.find('*[data-validateable-result]').each(function(iIndex, oElement) {
			var sResult = $(oElement).attr('data-validateable-result');
			if(sResult === 'error') {
				bError = true;
			} else if(sResult !== 'success') {
				bUnknown = true;
			}
		});

		if(bError) {
			oElement.attr('data-validateable-result', 'error');
			oElement.removeClass(sSuccessClass);
			oElement.addClass(sErrorClass);
			return;
		}

		if(bUnknown) {
			oElement.attr('data-validateable-result', 'unknown');
			oElement.removeClass(sSuccessClass);
			oElement.removeClass(sErrorClass);
			return;
		}

		oElement.attr('data-validateable-result', 'success');
		oElement.addClass(sSuccessClass);
		oElement.removeClass(sErrorClass);

	};

	FormObject.prototype.validateChilds = function(oElement, bSkipValidationStateUpdate) {

		var oFormObject = this;

		if($(oElement).data('validateable') === 'form') {
			$(oElement).find('*[data-validateable="page"]').each(function(iIndex, oElement) {
				var bWasHidden = false;
				if($(oElement).is(':hidden')) {
					bWasHidden = true;
					$(oElement).show();
				}
				oFormObject.validateChilds(oElement, true);
				if(bWasHidden) {
					$(oElement).hide();
				}
			});
		} else if($(oElement).data('validateable') === 'page') {
			$(oElement).find('*[data-validateable-result]').each(function(iIndex, oElement) {
				var oValidatorChain = $(oElement).data('dynamic-validator-chain');
				if(!oValidatorChain) {
					return;
				}
				oValidatorChain.validate(true);
			});
		} else {
			if(this.logIsAvailable()) {
				var sLogMessage = 'Element is not a validateable container';
				console.error(sLogMessage, oElement);
			}
			return;
		}

		if(bSkipValidationStateUpdate !== true) {
			this.updateValidationState();
		}

	};

	FormObject.prototype.initializePageNavigation = function() {

		var oFormObject = this;

		this.oForm.find('*[data-form-navigation]').each(function(iIndex, oElement) {
			var sType = $(oElement).data('form-navigation');
			if(sType === 'prev') {
				$(oElement).bind(
					'click',
					{
						oFormObject: oFormObject,
						oElement: oElement
					},
					function(event) {
						var oFormObject = event.data.oFormObject;
						var oElement = event.data.oElement;
						oFormObject.pageNavigationPrev(oElement);
					}
				);
			} else if(sType === 'next') {
				$(oElement).bind(
					'click',
					{
						oFormObject: oFormObject,
						oElement: oElement
					},
					function(event) {
						var oFormObject = event.data.oFormObject;
						var oElement = event.data.oElement;
						oFormObject.pageNavigationNext(oElement);
					}
				);
			} else if(sType === 'submit') {
				$(oElement).bind(
					'click',
					{
						oFormObject: oFormObject,
						oElement: oElement
					},
					function(event) {
						var oFormObject = event.data.oFormObject;
						var oElement = event.data.oElement;
						oFormObject.pageNavigationSubmit(oElement, false);
					}
				);
			} else if(sType === 'submit-reset') {
				$(oElement).bind(
					'click',
					{
						oFormObject: oFormObject,
						oElement: oElement
					},
					function(event) {
						var oFormObject = event.data.oFormObject;
						oFormObject.pageNavigationGotoFirstError();
					}
				);
			} else if(sType === 'page') {
				$(oElement).hide();
			} else if(sType === 'success') {
				$(oElement).hide();
			}
		});

		var aPages = [];
		var bPageSelected = false;

		this.oForm.find('*[data-form-navigation="page"]').each(function(iIndex, oElement) {
			aPages.push(oElement);
			if(!$(oElement).attr('data-form-navigation-current-page')) {
				$(oElement).attr('data-form-navigation-current-page', 'no');
			}
			if($(oElement).attr('data-form-navigation-current-page') === 'yes') {
				bPageSelected = true;
				$(oElement).show();
			}
		});

		if(!bPageSelected) {
			$(aPages[0]).show();
			$(aPages[0]).attr('data-form-navigation-current-page', 'yes');
		}

		return true;

	};

	FormObject.prototype.pageNavigationPrev = function(oButton) {

		var oFormObject = this;
		var oPage = $(oButton).closest('*[data-form-navigation="page"]').get(0);
		var aPages = [];
		oFormObject.oForm.find('*[data-form-navigation="page"]').each(function(iIndex, oElement) {
			aPages.push(oElement);
		});

		var iPageIndex = aPages.indexOf(oPage);
		if(iPageIndex < 1) {
			if(oFormObject.logIsAvailable()) {
				console.error('Previous page event on first page?', oButton, oPage, aPages);
			}
			return false;
		}

		oFormObject.showLoadingOverlay(oPage);
		$(oButton).addClass('page-loading-overlay-clicked-button');
		window.setTimeout(
			function() {
				$(oPage).attr('data-form-navigation-current-page', 'no');
				$(oPage).hide();
				$(aPages[iPageIndex-1]).attr('data-form-navigation-current-page', 'yes');
				$(aPages[iPageIndex-1]).show();
				$(oPage).find('.page-loading-overlay-clicked-button').removeClass('page-loading-overlay-clicked-button');
				oFormObject.hideLoadingOverlay(oPage);
			},
			oFormObject.iLoadingDelayMs
		);

		return true;

	};

	FormObject.prototype.pageNavigationNext = function(oButton) {

		var oFormObject = this;
		var oPage = $(oButton).closest('*[data-form-navigation="page"]').get(0);
		var aPages = [];
		oFormObject.oForm.find('*[data-form-navigation="page"]').each(function(iIndex, oElement) {
			aPages.push(oElement);
		});

		var iPageIndex = aPages.indexOf(oPage, aPages);
		if(
			iPageIndex < 0 ||
			!aPages.hasOwnProperty(iPageIndex+1)
		) {
			if(oFormObject.logIsAvailable()) {
				console.error('Next page event on last page?', oButton, oPage, aPages);
			}
			return false;
		}

		var oValidationPage = $(oButton).closest('*[data-validateable="page"]').first();

		oFormObject.showLoadingOverlay(oPage);
		$(oButton).addClass('page-loading-overlay-clicked-button');
		window.setTimeout(
			function() {
				oFormObject.validateChilds(oValidationPage, false);
				if(oValidationPage.attr('data-validateable-result') === 'success') {
					$(oPage).attr('data-form-navigation-current-page', 'no');
					$(oPage).hide();
					$(aPages[iPageIndex+1]).attr('data-form-navigation-current-page', 'yes');
					$(aPages[iPageIndex+1]).show();
				}
				$(oPage).find('.page-loading-overlay-clicked-button').removeClass('page-loading-overlay-clicked-button');
				oFormObject.hideLoadingOverlay(oPage);
			},
			oFormObject.iLoadingDelayMs
		);

		return true;

	};

	FormObject.prototype.pageNavigationSubmit = function(oButton, bForce) {

		var oFormObject = this;
		var oPage = $(oButton).closest('*[data-form-navigation="page"]').get(0);
		var aPages = [];
		oFormObject.oForm.find('*[data-form-navigation="page"]').each(function(iIndex, oElement) {
			aPages.push(oElement);
		});

		var iPageIndex = aPages.indexOf(oPage);
		if(
			(
				!bForce
			) && (
				iPageIndex < 0 ||
				aPages.hasOwnProperty(iPageIndex+1)
			)
		) {
			if(oFormObject.logIsAvailable()) {
				console.error('Submit event not on last page?', oButton, oPage, aPages);
			}
			return false;
		}

		oFormObject.validateChilds(this.oForm, false);
		if(this.oForm.attr('data-validateable-result') !== 'success') {
			return false;
		}

		oFormObject.showLoadingOverlay(oPage);
		$(oButton).addClass('page-loading-overlay-clicked-button');
		var oOnErrorCallback = function() {
			$(oPage).find('.page-loading-overlay-clicked-button').removeClass('page-loading-overlay-clicked-button');
			oFormObject.hideLoadingOverlay(oPage);
			oFormObject.lockNavigation();
			if(oFormObject.logIsAvailable()) {
				var sMessage = 'Fatal error on form submit, cannot continue :(';
				console.error(sMessage, oFormObject.oForm);
			}

			oFormObject.setFormErrorMessages([oFormObject.oForm.data('message-error-internal')]);
		};
		var oSubmitEvent = function() {
			oFormObject.submitForm(oOnErrorCallback);
		};
		window.setTimeout(
			oSubmitEvent,
			oFormObject.iLoadingDelayMs
		);

		return true;

	};

	FormObject.prototype.pageNavigationGotoFirstError = function() {

		var oFormObject = this;
		var bErrorFound = false;
		var oLastPage = null;

		oFormObject.oForm.find('*[data-form-navigation]').each(function(iIndex, oElement) {
			var sType = $(oElement).data('form-navigation');
			if(sType === 'page') {
				oFormObject.hideLoadingOverlay(oElement);
				if(
					bErrorFound !== true &&
					$(oElement).attr('data-validateable-result') !== 'success'
				) {
					$(oElement).attr('data-form-navigation-current-page', 'yes');
					$(oElement).show();
					bErrorFound = true;
				} else {
					$(oElement).attr('data-form-navigation-current-page', 'no');
					$(oElement).hide();
					oLastPage = oElement;
				}
			} else if(sType === 'success') {
				oFormObject.hideLoadingOverlay(oElement);
				$(oElement).hide();
			}
		});

		if(
			oLastPage !== null &&
			bErrorFound !== true
		) {
			$(oLastPage).attr('data-form-navigation-current-page', 'yes');
			$(oLastPage).show();
		}

		$(oFormObject.oForm).find('.page-loading-overlay-clicked-button').removeClass('page-loading-overlay-clicked-button');

	};

	FormObject.prototype.pageNavigationSuccess = function() {

		var oFormObject = this;
		oFormObject.oForm.find('*[data-form-navigation]').each(function(iIndex, oElement) {
			var sType = $(oElement).data('form-navigation');
			if(sType === 'page') {
				oFormObject.hideLoadingOverlay(oElement);
				$(oElement).hide();
			} else if(sType === 'success') {
				oFormObject.hideLoadingOverlay(oElement);
				$(oElement).show();
			}
		});

		$(oFormObject.oForm).find('.page-loading-overlay-clicked-button').removeClass('page-loading-overlay-clicked-button');

	};

	FormObject.prototype.submitForm = function(oParentOnErrorCallback) {

		if(!oParentOnErrorCallback) {
			oParentOnErrorCallback = function() {};
		}

		var oFormObject = this;
		oFormObject.lockNavigation();
		var oOnErrorCallback = function() {
			oFormObject.unlockNavigation();
			oParentOnErrorCallback();
		};
		oFormObject.sendAjax('submit', null, oOnErrorCallback);

	};

	FormObject.prototype.lockNavigation = function() {

		this.oForm.find('*[data-form-navigation]').each(function(iIndex, oElement) {
			var sType = $(oElement).data('form-navigation');
			if(
				sType === 'prev' ||
				sType === 'next' ||
				sType === 'submit' ||
				sType === 'submit-reset'
			) {
				$(oElement).attr('disabled', 'disabled');
			}
		});

	};

	FormObject.prototype.unlockNavigation = function() {

		this.oForm.find('*[data-form-navigation]').each(function(iIndex, oElement) {
			var sType = $(oElement).data('form-navigation');
			if(
				sType === 'prev' ||
				sType === 'next' ||
				sType === 'submit' ||
				sType === 'submit-reset'
			) {
				$(oElement).removeAttr('disabled');
			}
		});

	};

	FormObject.prototype.setBlockErrorMessages = function(oMessages) {

		var oFormObject = this;

		$.each(oMessages, function(sInputName, oMessageData) {
			var bFound = false;
			oFormObject.oForm.find('*[name="'+sInputName+'"]').each(function(iIndex, oElement) {
				bFound = true;
				oFormObject.setBlockErrorMessage(oElement, oMessageData);
			});

			if(!bFound) {
				console.error('Could not find an element for block error message!', sInputName, oMessageData);
			}
		});

	};

	FormObject.prototype.setFormErrorMessages = function(aMessages) {

		this.resetFormErrorMessages();

		var oList = $('<ul/>');
		$.each(aMessages, function(iIndex, sMessage) {
			var oMessage = $('<li/>');
			oMessage.append(sMessage);
			oMessage.appendTo(oList);
		});

		this.oForm.find('*[data-validateable="form-message"]').each(function(iIndex, oElement) {
			oList.clone(true).appendTo($(oElement));
		});

	};

	FormObject.prototype.resetFormErrorMessages = function() {

		this.oForm.find('*[data-validateable="form-message"]').each(function(iIndex, oElement) {
			$(oElement).empty();
		});

	};

	FormObject.prototype.setBlockErrorMessage = function(oElement, oMessageData) {

		if(!oMessageData.hasOwnProperty('value')) {
			if(this.logIsAvailable()) {
				console.error('Missing key "value" in error message data', oElement);
			}
			return;
		}

		if(!oMessageData.hasOwnProperty('message')) {
			if(this.logIsAvailable()) {
				console.error('Missing key "message" in error message data', oElement);
			}
			return;
		}

		// TODO Implement algorithm
		if(!oMessageData.hasOwnProperty('algorithm')) {
			if(this.logIsAvailable()) {
				console.error('Missing key "algorithm" in error message data', oElement);
			}
			//return;
		}

		if(
			$(oElement).is('input[type="text"]') ||
			$(oElement).is('input[type="file"]') ||
			$(oElement).is('textarea') ||
			$(oElement).is('select')
		) {

			var oConfig = {
				algorithm: 'InputBlacklist',
				blacklist: [ oMessageData.value ],
				message: oMessageData.message
			};

			if($(oElement).is('select')) {
				oConfig.algorithm = 'SelectOptionsBlacklist';
			}

			// Each value is an instance of InputBlacklist and an own validator in chain
			new ValidateInput(this, oElement, oConfig);

		}

	};

	FormObject.prototype.showLoadingOverlay = function(oElement) {

		var oPage = $(oElement).closest('*[data-form-navigation="page"]').first();
		var oOverlay = $(oPage).find('.page-loading-overlay').first();
		if(oOverlay.length > 0) {
			$(oOverlay).addClass('shown');
		}

	};

	FormObject.prototype.hideLoadingOverlay = function(oElement) {

		var oPage = $(oElement).closest('*[data-form-navigation="page"]').first();
		var oOverlay = $(oPage).find('.page-loading-overlay').first();
		if(oOverlay.length > 0) {
			$(oOverlay).removeClass('shown');
		}

	};

	function ValidatorChain(oFormObject, oElement) {

		this.oFormObject = oFormObject;
		this.oElement = $(oElement);
		this.aValidators = [];

		this.oBlock = this.oElement.closest('*[data-validateable="block"]');
		if(this.oBlock.length !== 1) {
			if(oFormObject.logIsAvailable()) {
				var sLogMessage = 'Failed to find single related block! Result count: ' + this.oBlock.length;
				console.error(sLogMessage, oElement);
			}
			return;
		}

		this.oMessageContainer = this.oBlock.find('*[data-validateable="block-message"]');
		if(this.oMessageContainer.length !== 1) {
			if(oFormObject.logIsAvailable()) {
				var sLogMessage = 'Failed to find single related message container! Result count: ' + this.oMessageContainer.length;
				console.error(sLogMessage, oElement);
			}
			return;
		}

		this.initialize();

	}
	ValidatorChain.prototype.initialize = function() {

		this.oElement.bind(
			'dynamicFormValidate change focusout',
			{
				oValidatorChain: this
			},
			function(event) {
				var oValidatorChain = event.data.oValidatorChain;
				oValidatorChain.validate();
			}
		);

	};

	ValidatorChain.prototype.validate = function(bSkipValidationStateUpdate) {

		this.oMessageContainer.text('');
		var bSuccess = true;

		for(var iIndex in this.aValidators) {
			if(!this.aValidators.hasOwnProperty(iIndex)) {
				continue;
			}
			var oValidatorCallback = this.aValidators[iIndex];
			if(!oValidatorCallback(this.oMessageContainer)) {
				bSuccess = false;
				break;
			}
		}

		if(bSuccess) {

			if(this.oFormObject.aEmptyValues.indexOf(this.oElement.val()) === -1) {
				this.oElement.addClass('validate-success');
			}

			this.oElement.removeClass('validate-error');
			this.oElement.attr('data-validateable-result', 'success');
			this.oBlock.addClass('validate-success-block');
			this.oBlock.removeClass('validate-error-block');
			this.oBlock.attr('data-validateable-result', 'success');

		} else {

			this.oElement.addClass('validate-error');
			this.oElement.removeClass('validate-success');
			this.oElement.attr('data-validateable-result', 'error');
			this.oBlock.addClass('validate-error-block');
			this.oBlock.removeClass('validate-success-block');
			this.oBlock.attr('data-validateable-result', 'error');

		}

		if(!bSkipValidationStateUpdate) {
			this.oFormObject.updateValidationState();
		}

	};

	function TriggerAjaxRequest(oFormObject, oElement, oData) {

		if(!oData.hasOwnProperty('task')) {
			if(oFormObject.logIsAvailable()) {
				console.error('Missing configuration option "task"!', oElement);
			}
			return;
		}

		if(!oData.hasOwnProperty('additional')) {
			oData['additional'] = null;
		}

		$(oElement).on('dynamicFormInitialize change', function() {
			oFormObject.sendAjax(oData['task'], oData['additional'], null);
		});

	}

	function AjaxContainerChange(oFormObject, oElement, oData) {

		this.oFormObject = oFormObject;
		this.oElement = $(oElement);
		this.sType = oData.type;

		oFormObject.oForm.bind('dynamicFormAjax.'+oData['task']+'Callback', function(oEvent, oResultData) {

			if(
				!oResultData['container_change'] ||
				oResultData['container_change'].length
			) {
				return;
			}

			$.each(oResultData['container_change'], function(sType, aEntries) {
				if(this.oElement.children('.block-area-' + sType).length !== 1) {
					return true;
				}

				var oDuplicateContainer = this.oElement.data('DuplicateableContainer');
				if(!oDuplicateContainer instanceof DuplicateableContainer) {
					console.error('Container has no instance of DuplicateableContainer!', this.oElement);
					return true;
				}

				oDuplicateContainer.clearContainer();

				$.each(aEntries, function(iKey, oCourseData) {
					if(iKey > 0) {
						oDuplicateContainer.oElement.find('[data-duplicateable="control-add"]').last().click();
					}

					var oArea = this.oElement.find('[data-duplicateable="area"]:nth-child(' + (iKey + 1) +')');
					if(oArea.length !== 1) {
						console.error('Could not find an area with key ' + (iKey + 1) + '!', this.oElement);
						return true;
					}

					$.each(oCourseData, function(sFieldIdentifier, sValue) {
						var oInput = oArea.find('[data-dynamic-identifier='+sFieldIdentifier+']');
						if(oInput.length !== 1) {
							console.error('Could not find input with identifier in area! ('+sFieldIdentifier+')', oArea)
						}

						oInput.val(sValue);
						oInput.trigger('change');
					});
				}.bind(this));

				if(
					oResultData['container_change'].messages &&
					oResultData['container_change'].messages[sType]
				) {
					// Wenn das mit der ValidatorChain Konflikte gibt, muss das irgendwie anders gemacht werden
					oDuplicateContainer.oElement.find('*[data-validateable="block-message"]').eq(0).text(oResultData['container_change'].messages[sType]);
				}

			}.bind(this));

		}.bind(this));
	}

	function AjaxListResult(oFormObject, oElement, oData) {

		this.oFormObject = oFormObject;
		this.oElement = $(oElement);
		this.oOverlay = this.oElement.parent().children('.block-area-overlay');
		this.sResultGroup = null;

		if(!oData.hasOwnProperty('task')) {
			if(oFormObject.logIsAvailable()) {
				console.error('Missing configuration option "task"!', oElement);
			}
			return;
		}

		if(oData.hasOwnProperty('result_group')) {
			this.sResultGroup = 'group_' + oData['result_group'];
		}

		oFormObject.oForm.bind('dynamicFormAjax.'+oData['task']+'beforeSend', function() {
			this.oOverlay.addClass('shown');
		}.bind(this));

		oFormObject.oForm.bind('dynamicFormAjax.'+oData['task']+'Callback', function(event, oResultData) {

			if(this.sResultGroup !== null) {
				if(!oResultData.hasOwnProperty(this.sResultGroup)) {
					if(oFormObject.logIsAvailable()) {
						var sMessage = 'Expected result group not in result data: ' + this.sResultGroup;
						console.error(sMessage, this.oElement);
					}
					return;
				}
				oResultData = oResultData[this.sResultGroup];
			}
			this.update(oResultData);

			this.oOverlay.removeClass('shown');

		}.bind(this));

	}
	AjaxListResult.prototype.update = function(oResultData) {

		var oElement = this.oElement;
		var oTable = $('<table/>');

		if(oResultData.hasOwnProperty('css')) {
			oTable.prop('class', oResultData['css']);
		}

		if(oResultData.hasOwnProperty('rows')) {
			$.each(oResultData['rows'], function(iIndex, oData) {
				var oTr = $('<tr/>');
				if(oData.hasOwnProperty('css')) {
					oTr.prop('class', oData['css']);
				}
				var oTd = null;
				oTd = $('<td/>');
				if(oData.hasOwnProperty('title_css')) {
					oTd.prop('class', oData['title_css']);
				}
				if(oData.hasOwnProperty('title')) {
					var oTitle = $('<div/>');
					oTitle.text(oData['title']);
					oTd.append(oTitle);
				}
				if(oData.hasOwnProperty('notes')) {
					$.each(oData['notes'], function(iIndex, sNote) {
						var oNote = $('<div/>');
						oNote.text(sNote);
						if(oData.hasOwnProperty('notes_css')) {
							oNote.prop('class', oData['notes_css']);
						}
						oTd.append(oNote);
					});
				}
				if(!oData.hasOwnProperty('text')) {
					oTd.prop('colspan', 2);
				}
				oTr.append(oTd);
				if(oData.hasOwnProperty('text')) {
					oTd = $('<td/>');
					var oText = $('<div/>');
					oText.text(oData['text']);
					oTd.append(oText);
					if(oData.hasOwnProperty('text_css')) {
						oTd.prop('class', oData['text_css']);
					}
					oTr.append(oTd);
				}
				oTable.append(oTr);
			});
		}

		oElement.empty();
		oTable.appendTo(oElement);
		return true;

	};

	function KeepValueSynced(oFormObject, oElement, oData) {

		this.oFormObject = oFormObject;
		this.oElement = $(oElement);
		this.oData = oData;

		if(!oData.hasOwnProperty('identifier')) {
			if(oFormObject.logIsAvailable()) {
				console.error('Missing configuration option "identifier"!', oElement);
			}
			return;
		}

		this.oElement.bind(
			'change',
			{
				oListener: this
			},
			function(event) {
				var oListener = event.data.oListener;
				var oElement = oListener.oElement;
				var oFormObject = oListener.oFormObject;
				var oForm = oFormObject.oForm;
				var oData = oListener.oData;
				oForm.trigger(
					'dynamicFormSync.'+oData['identifier'],
					[ oElement ]
				);
			}
		);

		oFormObject.oForm.bind(
			'dynamicFormSync.'+oData['identifier'],
			{
				oListener: this
			},
			function(event, oTriggerElement) {
				var oListener = event.data.oListener;
				oListener.update(oTriggerElement);
			}
		);

	}
	KeepValueSynced.prototype.update = function(oTriggerElement) {

		var oElement = this.oElement;
		oTriggerElement = $(oTriggerElement);

		oElement.val(oTriggerElement.val());

	};

	function StaticSelectOptions(oFormObject, oElement, oData) {

		if(!oData.hasOwnProperty('select_options')) {
			if(oFormObject.logIsAvailable()) {
				console.error('Missing configuration option "select_options"!', oElement);
			}
			return;
		}

		oFormObject.setSelectOptions(oElement, oData['select_options'], true);

		if(
			oData.preselect &&
			// Bereit ausgewählte Option nicht überschreiben
			$(oElement).val() === '0'
		) {
			oFormObject.preSelectOption($(oElement), oData.preselect);
		}
	}
	function DependencyVisibility(oFormObject, oElement, oData) {

		this.oFormObject = oFormObject;
		this.oElement = $(oElement);
		this.oData = oData;

		if(!oData.hasOwnProperty('dependencies')) {
			if(oFormObject.logIsAvailable()) {
				console.error('Missing configuration option "dependencies"!', oElement);
			}
			return;
		}

		if(!oData.hasOwnProperty('default')) {
			if(oFormObject.logIsAvailable()) {
				console.error('Missing configuration option "default"!', oElement);
			}
			return;
		}

		this.initialize();

	}
	DependencyVisibility.prototype.initialize = function() {

		var oListener = this;
		var oFormObject = oListener.oFormObject;
		var oElement = oListener.oElement;
		var oData = oListener.oData;
		var aDependencies = [];

		var oCallbackNode = function(oElement, oDependency) {
			if($.inArray(oElement, aDependencies) <= 0) {
				$(oElement).bind(
					'dynamicFormInitialize change',
					{
						oListener: oListener
					},
					function(event) {
						var oListener = event.data.oListener;
						oListener.updateVisibility();
					}
				);
				aDependencies.push(oElement);
			}
			var aNewDependencies = [];
			if(oDependency.hasOwnProperty('data')) {
				$.each(oDependency['data'], function(sIndex, oDependency) {
					aNewDependencies = aNewDependencies.concat(oDependency);
				});
			}
			return aNewDependencies;
		};

		var oCallbackLeaf = function() {};

		oFormObject.walkDependencies(oElement, oData['dependencies'], oCallbackNode, oCallbackLeaf);

	};

	DependencyVisibility.prototype.updateVisibility = function() {

		var oListener = this;
		var oFormObject = oListener.oFormObject;
		var oElement = oListener.oElement;
		var oData = oListener.oData;
		var sAction = null;

		var oCallbackNode = function(oElement, oDependency) {
			var sValue = 'v' + $(oElement).val();
			var aNewDependencies = [];
			if(oDependency.hasOwnProperty('data')) {
				$.each(oDependency['data'], function(sIndex, oDependency) {
					if(sIndex === sValue) {
						aNewDependencies = aNewDependencies.concat(oDependency);
					}
				});
			}
			return aNewDependencies;
		};

		var oCallbackLeaf = function(oElement, oDependency) {
			if(
				oDependency['type'] === 'Visibility' &&
				(
					oDependency['action'] === 'show' ||
					oDependency['action'] === 'hide'
				) &&
				sAction === null
			) {
				sAction = oDependency['action'];
			}
		};

		oFormObject.walkDependencies(oElement, oData['dependencies'], oCallbackNode, oCallbackLeaf);

		if(sAction === null) {
			sAction = oData['default'];
		}

		if(sAction === 'hide') {
			oElement.hide();
		} else if(sAction === 'show') {
			oElement.show();
		} else {
			if(oFormObject.logIsAvailable()) {
				console.error('Invalid visibility action!', sAction, oElement);
			}
		}

	};

	function DependencyRequirement(oFormObject, oElement, oData) {

		this.oFormObject = oFormObject;
		this.oElement = $(oElement);
		this.oData = oData;

		if(!oData.hasOwnProperty('dependencies')) {
			if(oFormObject.logIsAvailable()) {
				console.error('Missing configuration option "dependencies"!', oElement);
			}
			return;
		}

		this.initialize();

	}

	DependencyRequirement.prototype.initialize = function() {

		this.oData['dependencies'].forEach(function(oDependency) {
			var oField = this.oFormObject.findSingleRelatedElement(this.oElement, oDependency['name']);

			oField.on('dynamicFormInitialize change', function() {
				var bRequire = false;
				if(Array.isArray(oDependency.values)) {
					$.each(oDependency.values, function(iKey, sValue) {
						if(sValue === oField.val()) {
							bRequire = true;
							return false;
						}
					});
				}

				if(bRequire) {
					var oConfig = {
						algorithm: 'InputBlacklist',
						blacklist: this.oFormObject.aEmptyValues,
						message: ''
					};

					if(this.oElement.is('select')) {
						oConfig.algorithm = 'SelectOptionsBlacklist';
					}

					new ValidateInput(this.oFormObject, this.oElement, oConfig);
				}

			}.bind(this));
		}.bind(this));

	};

	function DuplicateableContainer(oFormObject, oElement, oData) {

		this.oFormObject = oFormObject;
		this.oElement = $(oElement);
		this.oData = oData;
		this.renameDuplicateableFields();
		this.addClickEvents();

		this.oElement.data('DuplicateableContainer', this);

	}
	DuplicateableContainer.prototype.addClickEvents = function() {

		var oPrevAddElement = null;
		var oPrevRemoveElement = null;
		var iLastAreaIndex = null;

		this.oElement.find('*[data-duplicateable="area"]').each(function(iAreaIndex, oArea) {

			oArea = $(oArea);
			var oCurAddElement = oArea.find('*[data-duplicateable="control-add"]').first();
			var oCurRemoveElement = oArea.find('*[data-duplicateable="control-remove"]').first();

			if(
				oPrevAddElement &&
				oCurAddElement.length
			) {
				$(oPrevAddElement).hide();
				$(oPrevAddElement).prop('disabled', true);
			}

			if(oPrevRemoveElement) {
				$(oPrevRemoveElement).show();
				$(oPrevRemoveElement).prop('disabled', false);
			}

			if(oCurAddElement.length) {
				oCurAddElement.on('click', function() {
					this.executeClickEvent(oCurAddElement, oArea);
				}.bind(this));
				oCurAddElement.show();
				oCurAddElement.prop('disabled', false);
				oPrevAddElement = oCurAddElement;
			}

			if(oCurRemoveElement.length) {
				oCurRemoveElement.on('click', function() {
					this.executeClickEvent(oCurRemoveElement, oArea);
				}.bind(this));
				oCurRemoveElement.hide();
				oCurRemoveElement.prop('disabled', true);
				oPrevRemoveElement = oCurRemoveElement;
			}

			setTimeout(function() {
				if(this.oData.hasOwnProperty('disable_options')) {
					this.oData.disable_options.forEach(function(oConfig) {

						if(
							(
								oConfig.offset === 'exact' &&
								iAreaIndex !== oConfig.offset_value
							) ||
							(
								oConfig.offset === 'from' &&
								iAreaIndex < oConfig.offset_value
							)
						) {
							return;
						}

						oConfig.values.forEach(function(sValue) {
							oArea.find('select[data-dynamic-identifier=' + oConfig.input + '] option[value=' + sValue + ']').prop('disabled', true);
						}.bind(this));

					}.bind(this));
				}
			}.bind(this), 100); // Werte sind noch nicht da (auch wieder eine doofe Lösung)

			iLastAreaIndex = iAreaIndex;

		}.bind(this));

		if(
			iLastAreaIndex > 0 &&
			oPrevRemoveElement
		) {
			$(oPrevRemoveElement).show();
			$(oPrevRemoveElement).prop('disabled', false);
		}

	};

	DuplicateableContainer.prototype.executeClickEvent = function(oButton, oArea) {

		this.oFormObject.showLoadingOverlay(this.oElement);
		oButton.addClass('page-loading-overlay-clicked-button');
		//window.setTimeout(function() {
			if(oButton.data('duplicateable') === 'control-remove') {
				this.removeArea(oArea);
			} else {
				this.addArea(oArea);
			}
			// TODO Hier wird jedes Mal das komplette Formular neu initialisiert
			// Der Sinn dürfte eigentlich nur sein, dass Unterkunftsfelder ggf. vom Kurs abhängen
			this.oFormObject.initialize();
			this.oElement.find('.page-loading-overlay-clicked-button').removeClass('page-loading-overlay-clicked-button');
			this.oFormObject.hideLoadingOverlay(this.oElement);
		//}.bind(this), this.oFormObject.iLoadingDelayMs);

	};

	DuplicateableContainer.prototype.addArea = function(oArea) {

		oArea = $(oArea);
		var oNewArea = $(oArea).clone();
		oNewArea.find('input, select').removeClass('validate-success validate-error');
		$(oArea).after(oNewArea);

		return true;

	};

	DuplicateableContainer.prototype.removeArea = function(oArea) {

		if(this.oElement.find('*[data-duplicateable="area"]').length <= 1) {
			if(this.oFormObject.logIsAvailable()) {
				console.error('Only one area left, will not remove the last remaining area!', oArea);
			}
			return false;
		}

		$(oArea).remove();
		return true;

	};

	DuplicateableContainer.prototype.clearContainer = function() {
		this.oElement.find('[data-duplicateable="area"]').each(function(iAreaIndex, oArea) {
			if(iAreaIndex === 0) {
				$(oArea).find('input[type=text]').val('');
				$(oArea).find('select').val('0');
			} else {
				$(oArea).find('[data-duplicateable="control-remove"]').click();
			}
		});
	};

	DuplicateableContainer.prototype.renameDuplicateableFields = function() {

		var oListener = this;

		this.oElement.find('*[data-duplicateable="area"]').each(function(iAreaIndex, oArea) {
			$(oArea).find('*[name]').each(function(iFieldIndex, oField) {
				oListener.renameField(iAreaIndex, oField);
			});
		});

	};

	DuplicateableContainer.prototype.renameField = function(iAreaIndex, oField) {

		var oFormObject = this.oFormObject;
		var sName = $(oField).attr('name');
		var aName = null;
		var sNewName = null;

		// name[index]
		if(aName === null) {
			aName = sName.match(/^([^\[]+)(\[[0-9]+\])$/);
		}
		if(
			aName !== null &&
			sNewName === null
		) {
			sNewName = aName[1] + '[' + iAreaIndex + ']';
		}

		// block[index][name]
		if(aName === null) {
			aName = sName.match(/^([^\[]+)(\[[0-9]+\])\[([^\[]+)\]$/);
		}
		if(
			aName !== null &&
			sNewName === null
		) {
			sNewName = aName[1] + '[' + iAreaIndex + ']' + '[' + aName[3] + ']';
		}

		if(sNewName === null) {
			if(oFormObject.logIsAvailable()) {
				var sMessage = 'Unable to rename field, this will cause problems!';
				console.error(sMessage, oField);
			}
			$(oField).prop('disabled', true);
			return false;
		}

		$(oField).attr('name', sNewName);
		return true;

	};

	function SelectOptionsInRange(oFormObject, oElement, oData) {

		this.oFormObject = oFormObject;
		this.oElement = $(oElement);
		this.oPreselectOption = null;

		if(!oData.hasOwnProperty('require_results_from_all_definitions')) {
			oData.require_results_from_all_definitions = false;
		}

		if(!oData.hasOwnProperty('prepend_select_options')) {
			oData.prepend_select_options = [];
		}

		if(!oData.hasOwnProperty('append_select_options')) {
			oData.append_select_options = [];
		}

		if(!oData.hasOwnProperty('scale_min_to')) {
			oData.scale_min_to = null;
		}

		this.oData = oData;

		if(!oData.hasOwnProperty('definitions')) {
			if(oFormObject.logIsAvailable()) {
				console.error('Missing configuration option "definitions"!', oElement);
			}
			return;
		}

		if(!oData.hasOwnProperty('value_map')) {
			if(oFormObject.logIsAvailable()) {
				console.error('Missing configuration option "value_map"!', oElement);
			}
			return;
		}

		if(!oData.hasOwnProperty('result_map')) {
			if(oFormObject.logIsAvailable()) {
				console.error('Missing configuration option "result_map"!', oElement);
			}
			return;
		}

		if(!oData.hasOwnProperty('default_select_options')) {
			if(oFormObject.logIsAvailable()) {
				console.error('Missing configuration option "default_select_options"!', oElement);
			}
			return;
		}

		if(oData.preselect) {
			this.oPreselectOption = oData.preselect;
		}

		this.initialize();

	}
	SelectOptionsInRange.prototype.initialize = function() {

		var oListener = this;
		var oFormObject = oListener.oFormObject;
		var oElement = oListener.oElement;
		var oData = oListener.oData;
		var aDependencies = [];

		var oCallbackNode = function(oElement) {
			if($.inArray(oElement, aDependencies) <= 0) {
				$(oElement).bind(
					'dynamicFormInitialize change',
					{
						oListener: oListener
					},
					function(event) {
						var oListener = event.data.oListener;
						oListener.updateOptions();
					}
				);
				aDependencies.push(oElement);
			}
		};

		oFormObject.walkDefinitions(oElement, oData['definitions'], oCallbackNode);

	};

	SelectOptionsInRange.prototype.updateOptions = function() {

		var oFormObject = this.oFormObject;
		var oElement = this.oElement;
		var oData = this.oData;

		var iMin = null;
		var iMax = null;

		var bValidResult = true;
		var iResult = 0;
		var oCallbackNode = function(oElement, sIdentifierDefinition) {
			if(!bValidResult) {
				return;
			}
			var sValue = oFormObject.lookupValue(oData['value_map'], sIdentifierDefinition, oElement);
			if(sValue === null) {
				bValidResult = false;
				return;
			}
			iResult += parseInt(sValue);
		};

		var bNoValidResults = false;

		$.each(oData['definitions'], function(iIndex, oDefinition) {
			if(bNoValidResults) {
				return;
			}
			bValidResult = true;
			iResult = 0;
			oFormObject.walkDefinitions(oElement, [oDefinition], oCallbackNode);
			if(!bValidResult) {
				if(oData['require_results_from_all_definitions']) {
					bNoValidResults = true;
					iMin = null;
					iMax = null;
				}
				return;
			}
			if(iMin === null) {
				iMin = iResult;
			}
			if(iMax === null) {
				iMax = iResult;
			}
			iMin = Math.min(iMin, iResult);
			iMax = Math.max(iMax, iResult);
		});

		if(
			iMin === null ||
			iMax === null
		) {
			oFormObject.setSelectOptions(oElement, oData['default_select_options']);
			return;
		}

		// if(oData['scale_min_to']) {
		// 	var iScaleTo = parseInt(oData['scale_min_to']);
		// 	var iModifier = (iScaleTo - iMin);
		// 	iMin += iModifier;
		// 	iMax += iModifier;
		// }

		var aSelectOptions = [];
		$.each(oData['result_map'], function(iIndex, oResult) {
			if(!oResult.hasOwnProperty('value')) {
				if(oFormObject.logIsAvailable()) {
					console.error('Missing key "value" in result map element!', oElement, oResult);
				}
				return;
			}
			if(!oResult.hasOwnProperty('select_options')) {
				if(oFormObject.logIsAvailable()) {
					console.error('Missing key "select_options" in result map element!', oElement, oResult);
				}
				return;
			}
			var iValue = parseInt(oResult['value']);
			if(
				iValue < iMin ||
				iValue > iMax
			) {
				return;
			}
			aSelectOptions = aSelectOptions.concat(oResult['select_options']);
		});
		aSelectOptions = oData['prepend_select_options'].concat(aSelectOptions);
		aSelectOptions = aSelectOptions.concat(oData['append_select_options']);

		oFormObject.setSelectOptions(oElement, aSelectOptions);

		if(
			this.oPreselectOption &&
			// Bereit ausgewählte Option nicht überschreiben
			oElement.val() === '0'
		) {
			oFormObject.preSelectOption(oElement, this.oPreselectOption);
		}

	};

	function SelectOptionsMap(oFormObject, oElement, oData) {

		this.oFormObject = oFormObject;
		this.oElement = $(oElement);
		this.oData = oData;
		this.oPreselectOption = null;

		if(!oData.hasOwnProperty('definitions')) {
			if(oFormObject.logIsAvailable()) {
				console.error('Missing configuration option "definitions"!', oElement);
			}
			return;
		}

		if(!oData.hasOwnProperty('value_map')) {
			if(oFormObject.logIsAvailable()) {
				console.error('Missing configuration option "value_map"!', oElement);
			}
			return;
		}

		if(!oData.hasOwnProperty('result_map')) {
			if(oFormObject.logIsAvailable()) {
				console.error('Missing configuration option "result_map"!', oElement);
			}
			return;
		}

		if(!oData.hasOwnProperty('default_select_options')) {
			if(oFormObject.logIsAvailable()) {
				console.error('Missing configuration option "default_select_options"!', oElement);
			}
			return;
		}

		if(oData.preselect) {
			this.oPreselectOption = oData.preselect;
		}

		this.initialize();

	}
	SelectOptionsMap.prototype.initialize = function() {

		var oListener = this;
		var oFormObject = oListener.oFormObject;
		var oElement = oListener.oElement;
		var oData = oListener.oData;
		var aDependencies = [];

		var oCallbackNode = function(oElement) {
			if($.inArray(oElement, aDependencies) <= 0) {
				$(oElement).bind(
					'dynamicFormInitialize change',
					{
						oListener: oListener
					},
					function(event) {
						var oListener = event.data.oListener;
						oListener.updateOptions();
					}
				);
				aDependencies.push(oElement);
			}
		};

		oFormObject.walkDefinitions(oElement, oData['definitions'], oCallbackNode);

	};

	SelectOptionsMap.prototype.updateOptions = function() {

		var oFormObject = this.oFormObject;
		var oElement = this.oElement;
		var oData = this.oData;

		var bValidResult = true;
		var sResult = '';
		var oCallbackNode = function(oElement, sIdentifierDefinition) {
			if(!bValidResult) {
				return;
			}
			var sValue = oFormObject.lookupValue(oData['value_map'], sIdentifierDefinition, oElement);
			if(sValue === null) {
				bValidResult = false;
				return;
			}
			sResult = sResult + ':' + sValue;
		};

		var aResults = [];

		$.each(oData['definitions'], function(iIndex, oDefinition) {
			bValidResult = true;
			sResult = '';
			oFormObject.walkDefinitions(oElement, [oDefinition], oCallbackNode);
			if(!bValidResult) {
				return;
			}
			aResults.push(sResult);
		});

		if(aResults.length < 1) {
			oFormObject.setSelectOptions(oElement, oData['default_select_options']);
			return;
		}

		var aSelectOptions = [];
		$.each(oData['result_map'], function(iIndex, oResult) {
			if(!oResult.hasOwnProperty('value')) {
				if(oFormObject.logIsAvailable()) {
					console.error('Missing key "value" in result map element!', oElement, oResult);
				}
				return;
			}
			if(!oResult.hasOwnProperty('select_options')) {
				if(oFormObject.logIsAvailable()) {
					console.error('Missing key "select_options" in result map element!', oElement, oResult);
				}
				return;
			}
			$.each(aResults, function(iIndex, sResult) {
				if(oResult['value'] !== sResult) {
					return;
				}
				aSelectOptions = aSelectOptions.concat(oResult['select_options']);
			});
		});

		oFormObject.setSelectOptions(oElement, aSelectOptions);

		if(
			this.oPreselectOption &&
			// Bereit ausgewählte Option nicht überschreiben
			oElement.val() === '0'
		) {
			oFormObject.preSelectOption(oElement, this.oPreselectOption);
		}

	};

	function SelectOptionsLookup(oFormObject, oElement, oData) {

		this.oFormObject = oFormObject;
		this.oElement = $(oElement);
		this.oPreselectOption = null;

		if(!oData.hasOwnProperty('require_results_from_all_definitions')) {
			oData.require_results_from_all_definitions = false;
		}

		if(!oData.hasOwnProperty('prepend_select_options')) {
			oData.prepend_select_options = [];
		}

		if(!oData.hasOwnProperty('append_select_options')) {
			oData.append_select_options = [];
		}

		if(!oData.hasOwnProperty('scale_min_to')) {
			oData.scale_min_to = null;
		}

		this.oData = oData;

		if(!oData.hasOwnProperty('definitions')) {
			if(oFormObject.logIsAvailable()) {
				console.error('Missing configuration option "definitions"!', oElement);
			}
			return;
		}

		if(!oData.hasOwnProperty('value_map')) {
			if(oFormObject.logIsAvailable()) {
				console.error('Missing configuration option "value_map"!', oElement);
			}
			return;
		}

		if(!oData.hasOwnProperty('result_map')) {
			if(oFormObject.logIsAvailable()) {
				console.error('Missing configuration option "result_map"!', oElement);
			}
			return;
		}

		if(!oData.hasOwnProperty('default_select_options')) {
			if(oFormObject.logIsAvailable()) {
				console.error('Missing configuration option "default_select_options"!', oElement);
			}
			return;
		}

		if(oData.preselect) {
			this.oPreselectOption = oData.preselect;
		}

		this.initialize();

	}
	SelectOptionsLookup.prototype.initialize = function() {

		var oListener = this;
		var oFormObject = oListener.oFormObject;
		var oElement = oListener.oElement;
		var oData = oListener.oData;
		var aDependencies = [];

		var oCallbackNode = function(oElement) {
			if($.inArray(oElement, aDependencies) <= 0) {
				$(oElement).bind(
					'dynamicFormInitialize change',
					{
						oListener: oListener
					},
					function(event) {
						var oListener = event.data.oListener;
						oListener.updateOptions();
					}
				);
				aDependencies.push(oElement);
			}
		};

		oFormObject.walkDefinitions(oElement, oData['definitions'], oCallbackNode);

	};

	SelectOptionsLookup.prototype.updateOptions = function() {

		var oFormObject = this.oFormObject;
		var oRootElement = this.oElement;
		var oData = this.oData;

		var iMin = null;
		var iMax = null;

		var bValidResult = true;
		var bRangeResult = false;
		var sNamespace = 'ns';
		var iResult = null;

		// Closure wird in Schleife mit Elementen und Unterelementen aufgerufen
		var oCallbackNode = function(oElement, sIdentifierDefinition, oOptions) {
			if(!bValidResult) {
				return;
			}
			if(!oOptions.hasOwnProperty('type')) {
				if(oFormObject.logIsAvailable()) {
					var sMessage = 'Missing key "type" in definition options!';
					console.error(sMessage, oRootElement, sIdentifierDefinition, oOptions);
				}
				bValidResult = false;
				return;
			}
			var sValue = oFormObject.lookupValue(oData['value_map'], sIdentifierDefinition, oElement);
			if(sValue === null) {
				bValidResult = false;
				return;
			}
			if(oOptions['type'] === 'range') {
				bRangeResult = true;
				if(iResult === null) {
					iResult = parseInt(sValue);
				} else {
					iResult += parseInt(sValue);
				}
			} else if(oOptions['type'] === 'startrange') {
				bRangeResult = true;
				iResult = parseInt(sValue);
			} else if(oOptions['type'] === 'min') {
				bRangeResult = true;
				if(iResult === null) {
					iResult = parseInt(sValue);
				}
				iResult = Math.min(iResult, parseInt(sValue));
			} else if(oOptions['type'] === 'max') {
				bRangeResult = true;
				if(iResult === null) {
					iResult = parseInt(sValue);
				}
				iResult = Math.max(iResult, parseInt(sValue));
			} else if(oOptions['type'] === 'namespace') {
				sNamespace = sNamespace + ':' + sValue;
			} else if(oOptions['type'] === 'ignore') {

			} else {
				if(oFormObject.logIsAvailable()) {
					var sMessage = 'Invalid type in definition options!';
					console.error(sMessage, oRootElement, oOptions['type'], sIdentifierDefinition, oOptions);
				}
				bValidResult = false;

			}
		// console.debug($(oRootElement).attr('name'), $(oElement).attr('name'), oOptions['type'], sValue, iResult);
		};

		var bNoValidResults = false;

		$.each(oData['definitions'], function(iIndex, oDefinition) {
			if(bNoValidResults) {
				return;
			}
			bValidResult = true;
			bRangeResult = false;
			iResult = null;
			oFormObject.walkDefinitions(oRootElement, [oDefinition], oCallbackNode);
			if(!bValidResult) {
				if(oData['require_results_from_all_definitions']) {
					bNoValidResults = true;
					iMin = null;
					iMax = null;
				}
				return;
			}
			if(bRangeResult) {
				if(iMin === null) {
					iMin = iResult;
				}
				if(iMax === null) {
					iMax = iResult;
				}
				iMin = Math.min(iMin, iResult);
				iMax = Math.max(iMax, iResult);
			}
		});

		if(
			iMin === null ||
			iMax === null ||
			!oData['result_map'].hasOwnProperty(sNamespace)
		) {
			oFormObject.setSelectOptions(oRootElement, oData['default_select_options']);
			return;
		}

		//console.debug($(oRootElement).attr('name'), iMin, iMax, oData['scale_min_to']);
		if(oData['scale_min_to']) {
			var iScaleTo = parseInt(oData['scale_min_to']);
			var iModifier = (iScaleTo - iMin);
			iMin += iModifier;
			//iMax += iModifier;
		}

		var aSelectOptions = [];
		$.each(oData['result_map'][sNamespace], function(iIndex, oResult) {
			if(!oResult.hasOwnProperty('value')) {
				if(oFormObject.logIsAvailable()) {
					console.error('Missing key "value" in result map element!', oRootElement, oResult);
				}
				return;
			}
			if(!oResult.hasOwnProperty('select_options')) {
				if(oFormObject.logIsAvailable()) {
					console.error('Missing key "select_options" in result map element!', oRootElement, oResult);
				}
				return;
			}
			var iValue = parseInt(oResult['value']);
			if(
				iValue < iMin ||
				iValue > iMax
			) {
				return;
			}
			aSelectOptions = aSelectOptions.concat(oResult['select_options']);
		});
		aSelectOptions = oData['prepend_select_options'].concat(aSelectOptions);
		aSelectOptions = aSelectOptions.concat(oData['append_select_options']);

		oFormObject.setSelectOptions(oRootElement, aSelectOptions);

		if(
			this.oPreselectOption &&
			// Bereit ausgewählte Option nicht überschreiben
			oRootElement.val() === '0'
		) {
			oFormObject.preSelectOption(oRootElement, this.oPreselectOption);
		}

	};

	function ValidateInput(oFormObject, oElement, oData) {

		this.oFormObject = oFormObject;
		this.oData = oData;
		this.oElement = $(oElement);

		if(!oData.hasOwnProperty('message')) {
			if(oFormObject.logIsAvailable()) {
				console.error('Missing configuration option "message"!', oElement);
			}
			return;
		}

		if(!oData.hasOwnProperty('algorithm')) {
			if(oFormObject.logIsAvailable()) {
				console.error('Missing configuration option "algorithm"!', oElement);
			}
			return;
		}

		this.initialize();

	}

	ValidateInput.prototype.initialize = function() {

		var oListener = this;
		var oValidatorCallback = null;

		if(oListener.oData.algorithm === 'NotEmpty') {
			oValidatorCallback = function(oMessageContainer) {
				oMessageContainer = $(oMessageContainer);
				if(oMessageContainer.is(':hidden')) {
					return true;
				}
				if(oListener.oElement.val() === '') {
					oMessageContainer.text(oListener.oData.message);
					return false;
				}
				return true;
			};
		}

		if(oListener.oData.algorithm === 'EmailOrEmpty') {
			oValidatorCallback = function(oMessageContainer) {
				oMessageContainer = $(oMessageContainer);
				if(oMessageContainer.is(':hidden')) {
					return true;
				}
				var sValue = oListener.oElement.val();
				if(sValue !== '' && !oListener.oFormObject.isEmail(sValue)) {
					oMessageContainer.text(oListener.oData.message);
					return false;
				}
				return true;
			};
		}

		if(oListener.oData.algorithm === 'SelectOptionsBlacklist') {
			if(!oListener.oData.hasOwnProperty('blacklist')) {
				if(oListener.oFormObject.logIsAvailable()) {
					console.error('Missing configuration option "blacklist"!', oListener.oElement);
				}
				return;
			}
			oValidatorCallback = function(oMessageContainer) {
				oMessageContainer = $(oMessageContainer);
				if(oMessageContainer.is(':hidden')) {
					return true;
				}
				for(var iIndex in oListener.oData.blacklist) {
					if(!oListener.oData.blacklist.hasOwnProperty(iIndex)) {
						continue;
					}
					var sValue = oListener.oElement.val();
					if(sValue === oListener.oData.blacklist[iIndex]) {
						oMessageContainer.text(oListener.oData.message);
						return false;
					}
				}
				return true;
			};
		}

		if(oListener.oData.algorithm === 'InputBlacklist') {
			if(!oListener.oData.hasOwnProperty('blacklist')) {
				if(oListener.oFormObject.logIsAvailable()) {
					console.error('Missing configuration option "blacklist"!', oListener.oElement);
				}
				return;
			}
			oValidatorCallback = function(oMessageContainer) {
				oMessageContainer = $(oMessageContainer);
				if(oMessageContainer.is(':hidden')) {
					return true;
				}
				for(var iIndex in oListener.oData.blacklist) {
					if(!oListener.oData.blacklist.hasOwnProperty(iIndex)) {
						continue;
					}
					var sValue = oListener.oElement.val();
					if(sValue === oListener.oData.blacklist[iIndex]) {
						oMessageContainer.text(oListener.oData.message);
						return false;
					}
				}
				return true;
			};
		}

		if(oListener.oData.algorithm === 'CheckboxChecked') {
			oValidatorCallback = function(oMessageContainer) {
				oMessageContainer = $(oMessageContainer);
				if(oMessageContainer.is(':hidden')) {
					return true;
				}
				if(!oListener.oElement.attr('checked')) {
					oMessageContainer.text(oListener.oData.message);
					return false;
				}
				return true;
			};
		}

		if(oListener.oData.algorithm === 'RadioChecked') {
			oValidatorCallback = function(oMessageContainer) {
				oMessageContainer = $(oMessageContainer);
				if(oMessageContainer.is(':hidden')) {
					return true;
				}
				if(!$('input[name=' + oListener.oElement.attr('name') + ']:checked').val()) {
					oMessageContainer.text(oListener.oData.message);
					return false;
				}
				return true;
			};
		}

		if(oListener.oData.algorithm === 'IntegerRange') {
			if(!oListener.oData.hasOwnProperty('min')) {
				if(oListener.oFormObject.logIsAvailable()) {
					console.error('Missing configuration option "min"!', oListener.oElement);
				}
				return;
			}
			if(!oListener.oData.hasOwnProperty('max')) {
				if(oListener.oFormObject.logIsAvailable()) {
					console.error('Missing configuration option "max"!', oListener.oElement);
				}
				return;
			}
			oValidatorCallback = function(oMessageContainer) {
				oMessageContainer = $(oMessageContainer);
				if(oMessageContainer.is(':hidden')) {
					return true;
				}
				var sValue = oListener.oElement.val();
				if(!oListener.oFormObject.isInteger(sValue)) {
					oMessageContainer.text(oListener.oData.message);
					return false;
				}
				var iValue = parseInt(sValue);
				if(
					iValue < oListener.oData.min ||
					iValue > oListener.oData.max
				) {
					oMessageContainer.text(oListener.oData.message);
					return false;
				}
				return true;
			};
		}

		if(oListener.oData.algorithm === 'TimeOrEmpty') {
			oValidatorCallback = function(oMessageContainer) {
				oMessageContainer = $(oMessageContainer);
				if(oMessageContainer.is(':hidden')) {
					return true;
				}
				var sValue = oListener.oElement.val();
				if(sValue !== '' && !oListener.oFormObject.isTime(sValue)) {
					oMessageContainer.text(oListener.oData.message);
					return false;
				}
				return true;
			};
		}

		if(oListener.oData.algorithm === 'FileExtensionOrEmpty') {
			if(!Array.isArray(oListener.oData.file_extensions)) {
				if(this.oFormObject.logIsAvailable()) {
					console.error('Missing file extension list!', oListener.oElement);
				}
			}

			oValidatorCallback = function(oMessageContainer) {
				oMessageContainer = $(oMessageContainer);
				if(oMessageContainer.is(':hidden')) {
					return true;
				}
				var sValue = oListener.oElement.val();
				if(sValue === '') {
					return true;
				}
				var sFileExtension =  sValue.split('.').pop();
				if(oListener.oData.file_extensions.indexOf(sFileExtension) === -1) {
					oMessageContainer.text(oListener.oData.message);
					return false;
				}
				return true;
			};
		}

		if(oValidatorCallback === null) {
			if(this.oFormObject.logIsAvailable()) {
				console.error('Invalid validation algorithm: ' + oListener.oData.algorithm, this.oElement);
			}
			return;
		}

		oListener.oFormObject.registerValidator(oListener.oElement, oValidatorCallback);

	};

	function UpdateToValue(oFormObject, oElement, oData) {

		this.oFormObject = oFormObject;
		this.oElement = $(oElement);
		this.aDependencies = [];
		this.bDoNotUpdate = false;

		if(!oData.hasOwnProperty('only_if_unmodified')) {
			oData.only_if_unmodified = true;
		}

		if(!oData.hasOwnProperty('ignore_values')) {
			oData.ignore_values = [];
		}

		this.oData = oData;

		if(!oData.hasOwnProperty('definitions')) {
			if(oFormObject.logIsAvailable()) {
				console.error('Missing configuration option "definitions"!', oElement);
			}
			return;
		}

		if(!oData.hasOwnProperty('type')) {
			if(oFormObject.logIsAvailable()) {
				console.error('Missing configuration option "type"!', oElement);
			}
			return;
		}

		this.initialize();

	}
	UpdateToValue.prototype.initialize = function() {

		var oListener = this;
		var oFormObject = oListener.oFormObject;
		var oData = oListener.oData;
		var oElement = oListener.oElement;

		$.each(oData['definitions'], function(iIndex, sIdentifier) {
			oFormObject.resolveDependency(oElement, sIdentifier).each(function(iIndex, oDependency) {
				if($.inArray(oDependency, oListener.aDependencies) <= 0) {
					$(oDependency).bind(
						'dynamicFormInitialize change',
						{
							oListener: oListener
						},
						function(event) {
							var oListener = event.data.oListener;
							oListener.updateValue();
						}
					);
					if(oData.only_if_unmodified) {
						$(oElement).bind(
							'focusout',
							{
								oListener: oListener
							},
							function(event) {
								var oListener = event.data.oListener;
								oListener.bDoNotUpdate = true;
							}
						);
					}
					oListener.aDependencies.push(oDependency);
				}
			});
		});

	};

	UpdateToValue.prototype.updateValue = function() {

		if(this.bDoNotUpdate) {
			return;
		}

		var oListener = this;
		var oData = oListener.oData;
		var oElement = oListener.oElement;

		var mCompareValue = null;
		var mValue = null;

		$.each(oListener.aDependencies, function(iIndex, oDependency) {
			var mCurrentValue = $(oDependency).val();
			if(
				mCurrentValue === null ||
				oData.ignore_values.indexOf(mCurrentValue) >= 0
			) {
				return;
			}
			if(mCompareValue === null) {
				mCompareValue = mCurrentValue;
				if($(oDependency).is('select')) {
					mValue = $(oDependency).find('option:selected').text();
				} else {
					mValue = mCurrentValue;
				}
			} else if(
				oData.type === 'min' &&
				mCurrentValue < mCompareValue
			) {
				mCompareValue = mCurrentValue;
				if($(oDependency).is('select')) {
					mValue = $(oDependency).find('option:selected').text();
				} else {
					mValue = mCurrentValue;
				}
			} else if(
				oData.type === 'max' &&
				mCurrentValue > mCompareValue
			) {
				mCompareValue = mCurrentValue;
				if($(oDependency).is('select')) {
					mValue = $(oDependency).find('option:selected').text();
				} else {
					mValue = mCurrentValue;
				}
			}
		});

		oElement.val(mValue);

	};

}(Thebing, jQueryThebing));
