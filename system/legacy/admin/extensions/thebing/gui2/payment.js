
var PaymentGui = Class.create(UtilGui, {
	iWaitForInputEventDelay: 500, // 800ms sind zu lang bei diesem Dialog

	requestCallbackHook: function($super, oData) {
		$super(oData);

		if(
			oData.data &&
			oData.data.action === 'payment'
		) {
			this.sPaymentDialogType = null;
			this.fAmountAvailable = null; // Maximaler Betrag für diese Zahlung (bei Agenturzahlungen relevant)
			this.fAmountAvailableOriginal = null;

			// Da Payment-JS-Fehler und GUI-Fehler selbe DIVs benutzen, dürfen automatische Events Meldungen nicht verstecken
			this.bPreventDialogMessageHiding = false;

			this.setPaymentDialogEvents(oData.data);
		}
	},

	// Wird beim Wecheln eines Tabs ausgeführt
	toggleDialogTabHook : function ($super, iTab, iDialogId){
		$super(iTab, iDialogId);

		// Beim Wechseln des Payment-Dialogs auf den Überbezahl-Tab muss der Inhalt mit switchen
		if(iDialogId.match(/PAYMENT/)){

			// Aktionen bei bestimmten Tab ausführen
			var aActiveTabHeaders = $$('#tabs_'+iDialogId+'_'+this.hash+' .GUIDialogTabHeaderActive');

			if(aActiveTabHeaders[0]) {
				if(aActiveTabHeaders[0].hasClassName('tab_payments_payment')) {
					this.togglePaymentTabs(0, iDialogId);
				} else if(aActiveTabHeaders[0].hasClassName('tab_payments_overpayment')) {
					this.togglePaymentTabs(1, iDialogId);
				}
			}
		}
	},

	/**
	 * Switcht zwischen den beiden Tabs Bezahlung und Überbezahlung
	 *
	 * @param {int} iType
	 * @param {int} iDialogId
	 */
	togglePaymentTabs: function(iType, iDialogId) {

		var oAllCheckbox = this.getDialogAllCheckbox();

		// Beim Tabwecheln alle Inputs leeren
		this.clearAllDialogAmountInputs();

		// Beim Tabwechsel auch Refund Select resetten
		this.getDialogSaveField2('type_id').get(0).selectedIndex = 0;

		// Wenn Überbezahlen Tab
		this.bOverpayTab = false;

		if(iType == 1) {

			// überbezahlen
			this.bOverpayTab = true;

			$('tabBody_0_'+iDialogId+'_'+this.hash).removeClassName('GUITabBody');
			$('tabBody_0_'+iDialogId+'_'+this.hash).addClassName('GUITabBodyActive');

			if($('tabBody_1_'+iDialogId+'_'+this.hash)) {
				$('tabBody_1_'+iDialogId+'_'+this.hash).addClassName('GUITabBody');
				$('tabBody_1_'+iDialogId+'_'+this.hash).removeClassName('GUITabBodyActive');
			}
			if($('tabBody_3_'+iDialogId+'_'+this.hash)) {
				$('tabBody_3_'+iDialogId+'_'+this.hash).addClassName('GUITabBody');
				$('tabBody_3_'+iDialogId+'_'+this.hash).removeClassName('GUITabBodyActive');
			}

			this.getDialogSaveField2('method_id').removeClass('required');

			// Felder aus/einblenden
			$$('#dialog_'+iDialogId+'_'+this.hash+' .payment_data').each(function(oDiv){
				oDiv.hide();
			}.bind(this));
			$$('#dialog_'+iDialogId+'_'+this.hash+' .overpayment_data').each(function(oDiv){
				oDiv.hide();
			}.bind(this));
			$$('#dialog_'+iDialogId+'_'+this.hash+' .over_pay_tr_1').each(function(oDiv){
				oDiv.show();
			}.bind(this));
			$$('#dialog_'+iDialogId+'_'+this.hash+' .over_pay_tr_0').each(function(oDiv){
				oDiv.hide();
			}.bind(this));

			// Hidden Feld umschreiben
			$('payment_type').value = 'overpay';

			// Checkbox deaktivieren und alle Felder editierbar machen
			oAllCheckbox.prop('checked', true);
			this.toggleAmountInputFieldsEditing();
			this.toggleCreditnotes(iDialogId);
			// this.toggleAllCheckbox(true);

		} else {

			// bezahlen

			this.getDialogSaveField2('method_id').addClass('required');

			// Felder aus/einblenden
			$$('#dialog_'+iDialogId+'_'+this.hash+' .payment_data').each(function(oDiv){
				oDiv.show();
			}.bind(this));
			$$('#dialog_'+iDialogId+'_'+this.hash+' .overpayment_data').each(function(oDiv){
				oDiv.hide();
			}.bind(this));
			$$('#dialog_'+iDialogId+'_'+this.hash+' .over_pay_tr_1').each(function(oDiv){
				oDiv.hide();
			}.bind(this));
			$$('#dialog_'+iDialogId+'_'+this.hash+' .over_pay_tr_0').each(function(oDiv){
				oDiv.show();
			}.bind(this));

			// Hidden Feld umschreiben
			$('payment_type').value = 'payment';

			// Checkbox aktivieren und alle Felder sperren (Reset für Overpayment-Tab)
			oAllCheckbox.prop('checked', true);
			this.toggleAmountInputFieldsEditing();
			this.toggleCreditnotes(iDialogId);
			// this.toggleAllCheckbox(false);

		}

	},

	/**
	 * Events für den Payment-Dialog initialiseren
	 *
	 * @param {Object} aData
	 */
	setPaymentDialogEvents: function(aData) {

		this.sDialogIdOfPayment = aData.id;

		this.sPaymentDialogType = aData.optional.payment_dialog_type;

		this.bMultipleSelection = aData.optional.multiple_inquiries_selected;
		this.oTypeSelectConfig = aData.optional.type_select_config;
		//this.fAmountTotal = aData.optional.payment_amount_total;
		this.fAmountPending = aData.optional.payment_amount_total_open;

		this.fSchoolCurrencyFactor = aData.optional.school_currency_factor;
		this.iCurrenyFrom = aData.optional.currency_from;
		this.iCurrenyTo = aData.optional.currency_to;

		// Maximal verfügbarer Betrag (bei Agenturzahlungen relevant)
		if(aData.optional.payment_amount_available) {
			this.fAmountAvailable = aData.optional.payment_amount_available;
			this.fAmountAvailableOriginal = aData.optional.payment_amount_available;
		}

		this.bDisableTotalOverpayment = this.bMultipleSelection || this.sPaymentDialogType === 'agency_payment';

		this.setPaymentDialogInputEvents(aData);

	},

	/**
	 * Prüft wie viele checkboxen angeklickt sind und passt checkboxAll an
	 */
	adjustCheckboxAllState: function() {
		let checkboxCount = 0;
		let checkedCount = 0;

		document.querySelectorAll(".payment_toggle_row").forEach((clickableRow) => {
			const checkbox = clickableRow.querySelector('input[type="checkbox"]');

			if (checkbox) {
				if (checkbox.checked) {
					checkedCount++;
				}
				checkboxCount++;
			}
		});

		checkboxCount === checkedCount || checkedCount === 0 ? this.getDialogAllCheckbox().prop('indeterminate', false) : this.getDialogAllCheckbox().prop('indeterminate', true);
		checkedCount > 0 ? this.getDialogAllCheckbox().prop('checked', true) : this.getDialogAllCheckbox().prop('checked', false);
	},

	/**
	 * Setzt Events auf die Checkboxen
	 */
	setPaymentDialogInputEvents: function(aData) {

		document.querySelectorAll(".payment_clickable_row").forEach((clickableRow) => {
			const masterCheckbox = clickableRow.querySelector('input[type="checkbox"]');
			
			clickableRow.addEventListener("click", (e) => {

				if (e.target.tagName.toLowerCase() === "input") return;

				let currentRow = clickableRow.nextElementSibling;
				while (
					currentRow &&
					currentRow.classList.contains('payment_toggle_row')
				) {
					currentRow.classList.toggle("hidden");
					currentRow = currentRow.nextElementSibling;
				}

			});

			if (masterCheckbox) {
				masterCheckbox.addEventListener("change", () => {
					let currentRow = clickableRow.nextElementSibling;

					while (
						currentRow &&
						currentRow.classList.contains('payment_toggle_row')
					) {
						const childCheckbox = currentRow.querySelector('input[type="checkbox"]');
						if (childCheckbox) {
							childCheckbox.checked = masterCheckbox.checked;
						}
						currentRow = currentRow.nextElementSibling;
					}

					this.adjustCheckboxAllState();
				});
			}
		});

		document.querySelectorAll("tr").forEach((row) => {
			const checkbox = row.querySelector('input[type="checkbox"]');

			if (
				checkbox &&
				row.classList.contains('payment_toggle_row')
			) {
				checkbox.addEventListener("change", () => {

					let masterRow = row.previousElementSibling;
					while (
						masterRow &&
						masterRow.classList.contains('payment_toggle_row')
					) {
						masterRow = masterRow.previousElementSibling;
					}

					if (!masterRow) return;

					const masterCheckbox = masterRow.querySelector('input[type="checkbox"]');

					if (masterCheckbox) {
						let currentRow = masterRow.nextElementSibling;
						const children = [];

						while (
							currentRow &&
							currentRow.classList.contains('payment_toggle_row')
						) {
							const cb = currentRow.querySelector('input[type="checkbox"]');
							if (cb) children.push(cb);
							currentRow = currentRow.nextElementSibling;
						}

						const checkedCount = children.filter(cb => cb.checked).length;

						if (checkedCount === 0) {
							masterCheckbox.checked = false;
							masterCheckbox.indeterminate = false;
						} else if (checkedCount === children.length) {
							masterCheckbox.checked = true;
							masterCheckbox.indeterminate = false;
						} else {
							masterCheckbox.checked = false;
							masterCheckbox.indeterminate = true;
						}
					}

					this.adjustCheckboxAllState();
				});
			}
		});

		// Event auf einzelne Inputs (links): Summe aktualisieren
		$j('.payment_amount_input, .overpayment[name*=inquiry]').each(function(iIndex, oInput) {
			oInput = $j(oInput);
			oInput.keyup(function(oEvent) {
				this.waitForInputEvent('executeAmountInputChange', oEvent.originalEvent, oInput);
			}.bind(this));
		}.bind(this));

		// Event auf Total-Amount (links): Beträge verteilen
		var oInquirySumAmount = this.getDialogSumInput();
		oInquirySumAmount.keyup(function(oEvent) {
			this.waitForInputEvent('allocateSumToAmountInputs', oEvent.originalEvent);
		}.bind(this));

		this.getDialogAllCheckbox().change(this.toggleAmountInputFieldsEditing.bind(this));

		// Checkbox pro Item
		$j('.payment_item_checkbox').change((event) => {
			this.adjustCheckboxAllState();
			this.allocateSumToAmountInputs();
		})

		// Event beim Verändern des Datums der Zahlung
		var oDateSelect = this.getDialogSaveField2('date');
		oDateSelect.change(this.executePaymentDateChange.bind(this));

		// Event auf das Select vom Payment-Type
		var oTypeSelect = this.getDialogSaveField2('type_id');
		oTypeSelect.change(this.executePaymentTypeSelectChange.bind(this));
		this.executePaymentTypeSelectChange();

		// Events, wenn der Dialog bei Agenturzahlungen benutzt wird
		// if(this.sPaymentDialogType === 'agency_payment') {
			this.calculateAvailableAmount();
			$j('.checkbox_creditnote').each(function(iIndex, oCheckbox) {
				oCheckbox = $j(oCheckbox);
				oCheckbox.click(this.calculateAvailableAmount.bind(this));
			}.bind(this));
		// }

		$j('input.checkbox_creditnotes_total').change((event) => {
			if ($j(event.currentTarget).is(':checked')) {
				$j('.checkbox_creditnote:not(:checked)').trigger('click')
			} else {
				$j('.checkbox_creditnote:checked').trigger('click')
			}
		})

		$j('tr.creditnotes-toggle').click((event) => {
			var icon = $j(event.currentTarget).find('i.fa');
			icon.toggleClass('fa-chevron-up fa-chevron-down')
			this.toggleCreditnotes(aData.id)
		})

	},

	/**
	 * Betragsfeld verändert: Schulbetrag berechnen und Summe neu berechnen
	 *
	 * @param {jQuery} oInput
	 */
	executeAmountInputChange: function(oInput) {

		throw new Error('Manual amount allocation has been removed.');

		// Bei Agenturzahlungen, Gruppen und beim Verteilen der Überbezahlungen gibt es keine Überbezahlungen pro Item, daher resetten
		if(
			!oInput.hasClass('overpayment') && ( // Overpayment-Input springt hier auch rein, aber das geht über this.bDisableTotalOverpayment
				this.bOverpayTab ||
				this.hasDialogGroupedInquiries() ||
				this.sPaymentDialogType === 'agency_payment'
			)
		) {
			var fInputAmount = oInput.val().parseNumber();
			var fOpenAmount = $j('.open_payment_amount[data-item-id=' + oInput.data('item-id') + ']').val().parseNumber();

			if(
				(
					// Offen > 0: Eingabe darf nicht über offen liegen und nicht unter 0
					fOpenAmount > 0 && (
						fInputAmount > fOpenAmount ||
						fInputAmount < 0
					)
				) || (
					// Offen < 0: Eingabe darf nicht über (unter) offen liegen und nicht über 0
					fOpenAmount < 0 && (
						fInputAmount < fOpenAmount ||
						fInputAmount > 0
					)
				) || (
					// Offen == 0: Hier darf gar kein Betrag eingegeben werden
					fOpenAmount == 0 &&
					Math.abs(fInputAmount) > 0
				)
			) {
				oInput.val(this.thebingNumberFormat(0));
				this.throwAmountExceededError(oInput.get(0));
			} else {
				this.hideAmountExceededError(oInput.get(0));
			}
		}

		// Schulbetrag (rechtes Betragsfeld) berechnen
		this.calculateAmountForSchoolAmountInput(oInput);

		// Summe neu berechnen
		this.calculatePaymentSum(true);

		this.executeCommonAmountInputChange();

	},

	/**
	 * Funktion wird beim Ändern irgendeines Betrags-Eingabefelds (Item UND Summe) ausgeführt
	 */
	executeCommonAmountInputChange: function() {

		// Im Buchungsdialog ist eine beliebige Überbezahlung möglich
		if (this.sPaymentDialogType !== 'agency_payment') {
			return;
		}

		// Wenn verfügbarer Betrag (Agenturzahlungen) vorhanden und überschritten: Alle Beträge zurücksetzen
		if(this.fAmountAvailable !== null) {
			var oSumAmountInput = this.getDialogSumInput();
			var fSumAmount = oSumAmountInput.val().parseNumber();
			if(
				fSumAmount < 0 || // Negative Beträge sind bei Agenturzahlungen generell nicht möglich
				fSumAmount > this.fAmountAvailable
			) {
				this.clearAllDialogAmountInputs();
				this.throwAmountExceededError(oSumAmountInput.get(0));
			}
		}

	},

	/**
	 * Summe der linken Spalte berechnen
	 *
	 * @param {Boolean} [bSetValue]
	 * @returns {Number}
	 */
	calculatePaymentSum: function(bSetValue) {

		// Alle einzelnen Inputs durchlaufen
		var fAmountAll = 0;
		$j('.payment_amount_input, .overpayment[name*=inquiry]').each(function(iIndex, oInput) {
			var fValue = oInput.value.parseNumber();
			if(isNaN(fValue)) {
				fValue = 0;
			}
			fAmountAll = fAmountAll + fValue;
		});

		// Wert direkt ins Feld setzen
		if(bSetValue) {
			var oInquirySumAmount = this.getDialogSumInput();
			oInquirySumAmount.val(this.thebingNumberFormat(fAmountAll));
			this.calculateAmountForSchoolAmountInput(oInquirySumAmount);
		}

		return fAmountAll;

	},

	/**
	 * Eingegebene Summe auf die einzelnen Positionen aufteilen
	 *
	 * Die Funktion macht in etwa dasselbe wie Ext_TS_Payment_Item_AllocateAmount.
	 * Wenn hier Anpassungen gemacht werden, muss das ggf. auch in der PHP-Klasse gemacht werden!
	 */
	allocateSumToAmountInputs: function() {

		var oInquirySumAmountInput = this.getDialogSumInput();
		var fAmountToAllocate = oInquirySumAmountInput.val().parseNumber();

		// Alle Eingabefelder leeren aufgrund der unterschiedlichen Verteilungsarten
		this.clearAllDialogAmountInputs(oInquirySumAmountInput.get(0));
		this.hideAmountExceededError(oInquirySumAmountInput.get(0));

		// Beträge verteilen: Werte direkt in die Eingabefelder schreiben
		if(fAmountToAllocate >= 0) {
			// Wenn Summe positiv: Normal verteilen
			this.allocatePositiveAmountToItems(fAmountToAllocate);
		} else {
			// Wenn Summe negativ: Hier ist die Verteilung komplizierter
			this.allocateNegativeAmountToItems(fAmountToAllocate);
		}

		// Rechtes Feld (Schulbetrag) füllen; Werte neu holen wegen Array.splice()
		var aItemRows = this.getDialogInputAmountsMatrix();
		aItemRows.forEach(function(oRow) {
			var oInput = this.getDialogAmountInput(oRow.item_id);
			this.calculateAmountForSchoolAmountInput(oInput);
		}.bind(this));

		// Schulbetrag (rechts) für Overpayment auch neu berechnen
		this.calculateAmountForSchoolAmountInput(this.getDialogOverpaymentInput());

		// Schulbetrag (rechts) für Summe selbst berechnen
		this.calculateAmountForSchoolAmountInput(oInquirySumAmountInput);

		this.executeCommonAmountInputChange();

	},

	/**
	 * Positiven Betrag verteilen (Betrag im Summenfeld wurde positiv eingegeben)
	 *
	 * @param {Number} fAmountToAllocate
	 */
	allocatePositiveAmountToItems: function(fAmountToAllocate) {

		var aItemRows = this.getDialogInputAmountsMatrix();
		var aSkipItems = [];

		/*
		 * Zuerst alle Zeilen sammeln mit negativem Bezahlbetrag (z.B. Specials und überbezahlte Positionen)
		 *
		 * Normalerweise würde es reichen, die Beträge zuerst nach negativen Beträgen zu sortieren,
		 * denn dann könnte man sich diese Schleife ersparen. Da der Kunde aber die Beträge sequenziell verteilt sehen möchte,
		 * muss hier eine zweite Schleife her, da sich das nicht durch eine einfache Sortierfunktion lösen lässt.
		 */
		aItemRows.forEach(function(oRow) {
			// Negative Beträge immer auf magische Weise bezahlen und Betrag hinzuaddieren
			if(oRow.amount_open < 0) {
				fAmountToAllocate += oRow.amount_open * -1;
				this.setDialogAmountInputValue(oRow.item_id, oRow.amount_open);
				aSkipItems.push(oRow.item_id); // Row »löschen«, damit das unten nicht nochmal durchläuft (kein Array.splice() benutzen!)
			}
		}.bind(this));

		// Verfügbaren Betrag verteilen
		aItemRows.forEach(function(oRow) {

			if(aSkipItems.indexOf(oRow.item_id) !== -1) {
				return true;
			}

			var fAllocateToItem = 0;
			if(fAmountToAllocate >= oRow.amount_open) {
				// Item kann voll bezahlt werden
				fAllocateToItem = oRow.amount_open;
				fAmountToAllocate -= oRow.amount_open;
			} else if(
				fAmountToAllocate > 0 &&
				fAmountToAllocate < oRow.amount_open
			) {
				// Item kann teilweise (noch) bezahlt werden
				fAllocateToItem = fAmountToAllocate;
				fAmountToAllocate = 0;
			}

			this.setDialogAmountInputValue(oRow.item_id, fAllocateToItem);

		}.bind(this));

		// Ungenauigkeiten können bei komischen Gruppenbeträgen auftauchen
		if(Math.abs(fAmountToAllocate) < 0.0001) {
			fAmountToAllocate = 0;
		}

		// Restlichen Betrag in Overpayment schreiben
		if(!this.bDisableTotalOverpayment) {
			this.getDialogOverpaymentInput().val(this.thebingNumberFormat(fAmountToAllocate));
		} else {
			// Bei Mehrfachauswahl oder Agenturzahlungen  darf es keinen Betrag im Overpayment geben, daher Wert löschen
			// Das hat nur beim automatischen Verteilen Relevanz, da beim manuellen Verteilen das Feld readonly ist
			if(Math.abs(fAmountToAllocate) > 0) {
				this.clearAllDialogAmountInputs();
				this.throwAmountExceededError(this.getDialogOverpaymentInput().get(0), 'amount_overpayment_impossible');
			}
		}

	},

	/**
	 * Negativen Betrag verteilen (Betrag im Summenfeld wurde negativ eingegeben)
	 *
	 * @param {Number} fAmountToAllocate
	 */
	allocateNegativeAmountToItems: function(fAmountToAllocate) {

		var aItemRows = this.getDialogInputAmountsMatrix();
		var aSkipItems = [];

		// Darf nur Overpayment abziehen, wenn das nicht deakviert ist
		if(!this.bDisableTotalOverpayment) {
			// Umdrehen, da mit Pending gearbeitet wird
			var fOverpaymentAmount = this.getDialogOverpaymentInput().data('current-overpayment-amount') * -1;
			var fAllocateOverpayAmount = 0;

			// Zuerst wird das vorhandene Overpayment abgezogen
			if(fAmountToAllocate >= fOverpaymentAmount) {
				// Auszahlungsbetrag ist geringer als Overpayment, kann also nur mit Overpayment bezahlt werden
				fAllocateOverpayAmount = fAmountToAllocate;
				fAmountToAllocate = 0;
			} else {
				// Overpayment ist geringer als Auszahlungsbetrag: Overpayment aufzehren und weiter verteilen
				fAllocateOverpayAmount = fOverpaymentAmount;
				fAmountToAllocate -= fOverpaymentAmount; // Overpayment vom zum verteilenden Betrag abziehen
			}
		}

		// Negativen Wert ins Overpayment schreiben
		this.getDialogOverpaymentInput().val(this.thebingNumberFormat(fAllocateOverpayAmount));

		// Wenn Auszahlungsbetrag > Overpayment: Alle Items mit negativer Balance (Pending) auszahlen
		aItemRows.forEach(function(oRow) {

			if(oRow.amount_open < 0) {
				var fAllocateToItem = 0;

				if(fAmountToAllocate <= oRow.amount_open) {
					// Item passt voll in den Auszahlungsbetrag
					fAllocateToItem = oRow.amount_open;
					fAmountToAllocate -= oRow.amount_open;
				} else if(
					fAmountToAllocate < 0 &&
					fAmountToAllocate > oRow.amount_open
				) {
					// Item passt noch teilweise in den Auszahlungsbetrag
					fAllocateToItem = fAmountToAllocate;
					fAmountToAllocate = 0;
				}

				this.setDialogAmountInputValue(oRow.item_id, fAllocateToItem);
				aSkipItems.push(oRow.item_id); // Row »löschen«, damit das unten nicht nochmal durchläuft (kein Array.splice() benutzen!)

			}

		}.bind(this));

		// Wenn Auszahlungsbetrag immer noch > 0: Die Bezahlung von bezahlten Items wegnehmen
		aItemRows.forEach(function(oRow) {

			if(aSkipItems.indexOf(oRow.item_id) !== -1) {
				return true;
			}

			if(oRow.amount_paid > 0) {
				var fPayedNegative = oRow.amount_paid * -1;
				var fAllocateToItem = 0;

				if(fAmountToAllocate <= fPayedNegative) {
					// Bezahlter Betrag des Items passt vollständig in den übrigen Auszahlungsbetrag
					fAllocateToItem = fPayedNegative;
					fAmountToAllocate -= fPayedNegative;
				} else if(
					fAmountToAllocate < 0 &&
					fAmountToAllocate > fPayedNegative
				) {
					// Bezahlter Betrag des Items passt noch teilweise in den übrigen Auszahlungsbetrag
					fAllocateToItem = fAmountToAllocate;
					fAmountToAllocate = 0;
				}

				this.setDialogAmountInputValue(oRow.item_id, fAllocateToItem);
			}

		}.bind(this));

		/*
		 * Wenn der zuzuweisende Betrag immer noch > 0, ist die Auszahlung viel zu hoch.
		 * Das würde theoretisch ein negatives Overpayment erzeugen, aber das geht hier nicht,
		 * da das Overpayment bereits ausbezahlt wird.
		 */
		if(fAmountToAllocate.toFixed(2) != '0.00') {
			this.clearAllDialogAmountInputs();
			this.throwAmountExceededError(this.getDialogSumInput().get(0));
		}

	},

	/**
	 * Liefert die Rohdaten (Beträge) alle Zeilen der Tabelle des Dialogs
	 *
	 * @returns {Object[]}
	 */
	getDialogInputAmountsMatrix: function() {
		var aMatrix = [];

		$j('.payment_amount_input').each(function(iIndex, oInput) {
			var sItemId = $j(oInput).data('item-id');

			// Nur ausgewählte Zeilen
			if (!$j('.payment_item_checkbox[data-item-id=' + sItemId + ']').prop('checked')) {
				return true;
			}

			aMatrix.push({
				item_id: sItemId,
				amount_paid: $j('.payed_amount[data-item-id='+sItemId+']').val().parseNumber(),
				amount_open: $j('.open_payment_amount[data-item-id='+sItemId+']').val().parseNumber(),
				amount_allocation: $j(oInput).val().parseNumber()
			});
		});

		return aMatrix;
	},

	/**
	 * Betragsfeld über Version-Item-ID/Inquiry ID holen (linkes Feld)
	 * 
	 * @param {Number} iItemId
	 * @returns {jQuery}
	 */
	getDialogAmountInput: function(iItemId) {
		return $j('.payment_amount_input[data-item-id=' + iItemId + ']');
	},

	/**
	 * Wert von Betragsfeld setzen: Float runden und formatieren
	 * 
	 * @param {Number} iItemId
	 * @param {Number} fAmount
	 */
	setDialogAmountInputValue: function(iItemId, fAmount) {
		var oInput = this.getDialogAmountInput(iItemId);
		fAmount = fAmount.toFixed(2).parseNumber();
		oInput.val(this.thebingNumberFormat(fAmount));
	},

	/**
	 * Overpayment-Betragsfeld (links) holen
	 * 
	 * @returns {jQuery}
	 */
	getDialogOverpaymentInput: function() {
		return $j('.overpayment[name*=inquiry]');
	},

	/**
	 * Summen-Feld (links) holen
	 * 
	 * @returns {jQuery}
	 */
	getDialogSumInput: function() {
		return $j('.payment_amount_input_all');
	},

	/**
	 * Alle Betragsfelder (links) holen
	 *
	 * @returns {jQuery}
	 */
	getAllAmountInputs: function() {
		return $j('input.amount');
	},

	/**
	 * Checkbox »Beträge manuell verteilen« (über der Summenzeile)
	 *
	 * @returns {jQuery}
	 */
	getDialogAllCheckbox: function() {
		return $j('.payment_checkbox_all');
	},

	// save[payment_dialog][group_payment_merged]

	/**
	 * Gruppenbezahlung: Wurden die Items zusammengemergt?
	 *
	 * @returns {Boolean}
	 */
	hasDialogGroupedInquiries: function() {
		var oHidden = $j('#save\\[payment_dialog\\]\\[group_payment_merged\\]');
		return Boolean(oHidden.val().parseNumber()); // Boolean('0') === true
	},

	/**
	 * Betrag für rechtes Betragsfeld ausrechnen und setzen
	 *
	 * @param {jQuery} oInput
	 */
	calculateAmountForSchoolAmountInput: function(oInput) {

		var oSchoolInput = this.getRightAmountInputForLeftAmountInput(oInput);
		var fAmount = oInput.val().parseNumber();
		var fSchoolAmount = fAmount * this.fSchoolCurrencyFactor;

		oSchoolInput.val(this.thebingNumberFormat(fSchoolAmount.toFixed(2).parseNumber()));

	},

	// /**
	//  * Alle-Checkbox aktivieren/deaktivieren
	//  *
	//  * @param {Boolean} bDisable
	//  */
	// toggleAllCheckbox: function(bDisable) {
	// 	var oAllCheckbox = this.getDialogAllCheckbox();
	// 	oAllCheckbox.prop('readonly', bDisable);
	// 	oAllCheckbox.prop('disabled', bDisable);
	// },

	/**
	 * Alle Eingabefelder leeren
	 *
	 * @param {HTMLElement} [oExcludeInput]
	 */
	clearAllDialogAmountInputs: function(oExcludeInput) {
		this.getAllAmountInputs().each(function(iIndex, oInput) {
			if(oInput != oExcludeInput) {
				oInput.value = this.thebingNumberFormat(0);
			}
		}.bind(this));
	},

	/**
	 * Betrag überschritten: Fehler anzeigen und Betrag auf 0 setzen
	 *
	 * @param {HTMLInputElement} oInput
	 * @param {String} [sErrorCode]
	 */
	throwAmountExceededError: function(oInput, sErrorCode) {

		this.removeAmountInputError(oInput, this.sDialogIdOfPayment, true);
		this.displayAmountInputError(oInput, this.sDialogIdOfPayment, sErrorCode);

		// Rechtes Betragsfeld ebenso leeren
		var oSchoolInput = this.getRightAmountInputForLeftAmountInput($j(oInput));
		oSchoolInput.val(this.thebingNumberFormat(0));

		//if(oInput.className.match(/payment_amount_input_all/)) {
			// Verfügbaren Betrag nicht auf 0 setzen #6349
			//this.setPaymentAllAmount(0, true);
		//} else {
			this.calculatePaymentSum(true);
		//}

	},

	/**
	 * Fehler von throwAmountExceededError() ausblenden
	 *
	 * @see throwAmountExceededError
	 * @param {HTMLInputElement} oInput
	 */
	hideAmountExceededError: function(oInput) {
		if(!this.bPreventDialogMessageHiding) {
			this.removeAmountInputError(oInput, this.sDialogIdOfPayment);
		}
	},

	/**
	 * Von einem linken Eingabefeld (Buchungsbetrag) auf das rechte (Schulbetrag) springen
	 *
	 * @param {jQuery} oInput
	 * @returns {jQuery}
	 */
	getRightAmountInputForLeftAmountInput: function(oInput) {
		return oInput.closest('td').next('td').find('input');
	},

	/**
	 * Bearbeitung aller Input-Eingabefelder umschalten (Betragsverteilung oder manuell verteilen)
	 */
	toggleAmountInputFieldsEditing: function() {

		// var bAllCheckboxChecked = this.getDialogAllCheckbox().is(':checked');
		//
		// if(!bAllCheckboxChecked) {
			$j('.payment_amount_input').prop('readonly', true);
			this.getDialogOverpaymentInput().prop('readonly', true);
			this.getDialogSumInput().prop('readonly', false);
		// } else {
		// 	$j('.payment_amount_input').prop('readonly', false);
		//
		// 	// Bei Agenturzahlungen darf das Feld nicht aktiviert werden
		// 	if(!this.getDialogOverpaymentInput().data('always-ro')) {
		// 		this.getDialogOverpaymentInput().prop('readonly', false);
		// 	}
		//
		// 	this.getDialogSumInput().prop('readonly', true);
		// }
		$j('.payment_item_checkbox').prop('checked', this.getDialogAllCheckbox().is(':checked'));
		$j('.payment_checkbox_customer').prop('checked', this.getDialogAllCheckbox().is(':checked'));
		$j('.payment_checkbox_customer').prop('indeterminate', false);
		this.getDialogAllCheckbox().prop('indeterminate', false);

		this.clearAllDialogAmountInputs(this.getDialogSumInput().get(0));
		// this.calculatePaymentSum(true);
		this.allocateSumToAmountInputs();

	},

	toggleCreditnotes: function (iDialogId) {

		if (this.bOverpayTab) {
			$$('#dialog_'+iDialogId+'_'+this.hash+' tr.creditnotes-toggle').each(function(oTr){
				oTr.hide();
			}.bind(this));
			$$('#dialog_'+iDialogId+'_'+this.hash+' tr.creditnote-row').each(function(oTr){
				oTr.hide();
				$j(oTr).find('.checkbox_creditnote').prop('checked', false);
			}.bind(this));

			return;
		}

		$$('#dialog_'+iDialogId+'_'+this.hash+' tr.creditnotes-toggle').each(function(oTr){
			oTr.show();

			var table = $j(oTr).closest('table')
			var icon = $j(oTr).find('i.fa');

			if (icon.hasClass('fa-chevron-up')) {
				table.find('tr.creditnote-row').show();
				table.find('tr.creditnotes-total').show();
			} else {
				table.find('tr.creditnote-row').hide();
				table.find('tr.creditnotes-total').hide();
				table.find('tr.creditnote-row .checkbox_creditnote').prop('checked', false);
			}
		}.bind(this));
	},

	/**
	 * Event beim Ändern des Datums: Wechselkurs muss neu geholt werden für Schulbeträge
	 */
	executePaymentDateChange: function() {

		var sPaymentDate = this.getDialogSaveField2('date').val();
		this.getCurrencyConversionFactor(this.iCurrenyFrom, this.iCurrenyTo, sPaymentDate, 'school_amounts');

	},

	/**
	 * Callback von getCurrencyConversionFactor(): Alle Schulbeträge neu berechnen
	 *
	 * @param $super
	 * @param {Object} oData
	 */
	getCurrencyConversionFactorCallback: function($super, oData) {
		$super(oData);

		// Alle Schulbeträge mit neuem Währungsfaktor neu berechnen
		if(oData.additional === 'school_amounts') {
			this.fSchoolCurrencyFactor = oData.factor;
			this.getAllAmountInputs().each(function(iIndex, oInput) {
				this.calculateAmountForSchoolAmountInput($j(oInput));
			}.bind(this));
		}

	},

	/**
	 * Event beim Ändern des Type-Felds: Verfügbare Optionen von Sender und Empfänger verändern
	 */
	executePaymentTypeSelectChange: function() {

		var oTypeSelect = this.getDialogSaveField2('type_id');
		var oSenderSelect = this.getDialogSaveField2('sender');
		var oReceiverSelect = this.getDialogSaveField2('receiver');

		// Konfiguration: Optionen, die bei den Typen verfügbar sind und ansonsten deaktiviert werden (kommt vom PHP)
		var oConfig = this.oTypeSelectConfig[oTypeSelect.val()];

		// Empfänger-Feld ein- oder ausblenden
		var oReceiverSelectDiv = oReceiverSelect.closest('.GUIDialogRow');
		if(oConfig.show_receivers) {
			oReceiverSelectDiv.show();
		} else {
			oReceiverSelectDiv.hide();
		}

		// Helper-Funktion für beide Selects: Options aktivieren/deaktivieren
		var oHelper = function(oSelect, aOptions) {
			oSelect.children().each(function(iIndex, oOption) {
				oOption = $j(oOption);
				if(aOptions.indexOf(oOption.val()) >= 0) {
					// Wenn im Array: Option aktivieren
					oOption.prop('disabled', false);
				} else {
					// Wenn nicht im Array: Option deaktivieren und deselektieren
					oOption.prop('disabled', true);
					oOption.prop('selected', false);
				}
			});

			// Ersten Wert aus Array wieder selektieren (Es muss immer etwas ausgewählt sein!)
			oSelect.children('option[value=' + aOptions[0] + ']').prop('selected', true);
		};

		oHelper(oSenderSelect, oConfig.senders);
		oHelper(oReceiverSelect, oConfig.receivers);

	},

	/**
	 * Agenturzahlungen: Maximal verfügbaren Betrag ausrechnen und Werte setzen
	 */
	calculateAvailableAmount: function() {

		var fCreditnoteAmount = 0;
		$j('.checkbox_creditnote:checked').each(function(iIndex, oCheckbox) {
			fCreditnoteAmount += $j(oCheckbox).data('amount-open');
		});

		if(this.fAmountPending > this.fAmountAvailableOriginal) {
			// Wenn offener Rechnungsbetrag > verfügbarer Betrag: Verfügbarer Betrag + CN-Betrag
			this.fAmountAvailable = this.fAmountAvailableOriginal + fCreditnoteAmount;
			if(this.fAmountAvailable > this.fAmountPending) {
				// Wenn offener Rechnungsbetrag erreicht: Diesen Wert setzen
				this.fAmountAvailable = this.fAmountPending;
			}
		} else {
			// Verfügbarer Betrag > offener Rechnungsbetrag: Nur offenen Rechnungsbetrag anzeigen
			this.fAmountAvailable = this.fAmountPending;
		}

		$j('.amount_for_use_total').text(this.thebingNumberFormat(this.fAmountAvailable));

		// Wenn Beträge nicht manuell verteilt werden: Verfügbaren Betrag auch im Summenfeld setzen
		// if(!this.getDialogAllCheckbox().is(':checked')) {
			var oSumAmountInput = this.getDialogSumInput();
			oSumAmountInput.val(this.thebingNumberFormat(this.fAmountAvailable));

			// Verstecken der Meldung verhindern, damit mögliche Fehler nicht direkt ausgeblendet werden
			this.bPreventDialogMessageHiding = true;
			this.allocateSumToAmountInputs();
			this.bPreventDialogMessageHiding = false;
		// }

	},

	/**
	 * Aktion beim Klick auf den Löschen-Button in der Historie (normale Payments)
	 *
	 * @param {Number} iPaymentId
	 * @param {String} sAdditional
	 */
	deletePayment: function(iPaymentId, sAdditional) {

		var bSuccess = false;
		if(
			this.multipleSelection == 1 &&
			this.countSelection() > 1
		) {
			bSuccess = confirm(this.getTranslation('payment_delete_all'));
		} else {
			bSuccess = confirm(this.getTranslation('payment_delete'));
		}

		if(bSuccess) {
			this.request('&task=deleteInquiryPayment&iPaymentId='+iPaymentId+'&additional='+sAdditional);
		}

	},

	/**
	 * Aktion beim Klick auf den Löschen-Button in der Historie (Agenturzahlungen)
	 *
	 * @param {Number} iPaymentId
	 * @param {String} sAdditional
	 * @param {String} sMode
	 * @param {String} sTranslation
	 */
	deleteAgencyPaymentAndCreditnote: function(iPaymentId, sAdditional, sMode, sTranslation) {

		var bSuccess = false;

		switch(sMode) {
			case 'creditnote_payments':
				bSuccess = confirm(sTranslation);
				break;
			case 'payment_creditnotes':
				bSuccess = confirm(sTranslation);
				break;
		}

		if(bSuccess) {
			this.request('&task=deleteInquiryPayment&iPaymentId='+iPaymentId+'&additional='+sAdditional+'&mode='+sMode);
		}

	},

	/**
	 * Da in die DB-Column der Key payment reingepfuscht wurde, funktioniert die normale GUI-Funktion nicht…
	 * Da die Inquiry/Enquiry-Dateien aber von dieser Klasse ableiten, darf die Methode auch nicht überschrieben werden.
	 *
	 * @param {String} sColumn
	 * @param {String} [sAlias]
	 * @returns {jQuery}
	 */
	getDialogSaveField2: function(sColumn, sAlias) {
		return $j('#save\\[' + this.hash + '\\]\\[' + this.sCurrentDialogId + '\\]\\[payment\\]\\[' + sColumn + '\\]');
	}
    
});
