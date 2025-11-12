var StudentlistGui = Class.create(PaymentGui, {

    sAlias : 'ki',
    oWaitForInputEventStudentObserver: {},
    oWebcamStream:null,
    oWebcamVideo:null,
    iSelectedSchoolId:null,
    additionalservicesCourse: [],
    additionalservicesAccommodation: [],
    courseData: [],
    courseLanguages: [],
    courseLessonsUnits: [],

    executeInputEvent : function(oElement) {

        var sElementId = oElement.id;

        if(!sElementId) {
            return;
        }

        var iPos = sElementId.lastIndexOf('_');

        var sElementType = sElementId.substr(0, iPos);
        var iElementKey = sElementId.substr(iPos+1);

        var sTest1 = iElementKey.replace(/EP/, '');
        var sTest2 = iElementKey.replace(/SI/, '');
        var sTest3 = iElementKey.replace(/SC/, '');

        if(
            isNaN(sTest1) &&
            isNaN(sTest2) &&
            isNaN(sTest3)
        ) {
            sElementType = sElementId;
            iElementKey = null;
        }

        if(oElement.hasClassName('calendar_input')){
            iPos = sElementId.lastIndexOf('[');
            sElementType = sElementId.substr(0, iPos);
            iElementKey = sElementId.substr(iPos+1);
            sElementType = iElementKey.replace(']', '');
        }

        switch(sElementType) {
            // Checkboxen: Vor-Ort-Kosten
            case 'initalcost':

                // Summe Vor-Ort und Vor-Anreise ausrechnen
                this.recalculateSums();

                break;

            // Checkboxen: Aktiv
            case 'onpdf':

                this.togglePositionActive(oElement, iElementKey);

                break;

            // Eingabe: Kundenbetrag
            case 'description':

                // Daten der Discount Zeile füllen
                if(oElement.hasClassName('position')) {

                    this.updatePositionDiscountRow(iElementKey);

                }

                break;

            // Eingabe: Betrag
            case 'amount':

                this.recalculatePosition(iElementKey);

//				if($('amount_provision_'+iElementKey)) {
//					this.recalculateCommission(iElementKey);
//				}

                break;

            // Eingabe: Provision
            case 'amount_provision':

                this.recalculatePosition(iElementKey);

                break;

            // Eingabe: Rabatt
            case 'amount_discount':

                this.recalculatePosition(iElementKey);

                // Daten der Discount Zeile füllen
                if(oElement.hasClassName('position')) {

                    this.updatePositionDiscountRow(iElementKey);

                }

//				if($('amount_provision_'+iElementKey)) {
//					this.recalculateCommission(iElementKey);
//				}

                break;

            // Auswahl: Steuern
            case 'tax_category':

                this.recalculatePosition(iElementKey);

                break;
            // Rechnungsdatum
            //case 'date':
            // Anzahlung Datum
            //case 'amount_prepay_due':
            // Restzahlung Datum
            //case 'amount_finalpay_due':
            // 	var oDate = $('save['+this.hash+']['+this.document_id+'][date]');
            //
            // 	if(oDate) {
            // 		var aDates = [oDate.value];
            // 		this.convertDate(aDates, 'compare_prepay');
            // 	}
            //
            // 	break;

            // Löschen
            case 'delete':

                var bConfirm = confirm(this.getTranslation('delete_document_position'));

                if(bConfirm) {

                    $('position_row_'+iElementKey).remove();

                    this.updatePositionCache();
                    this.recalculateSums();

                }

                break;

            // Rabatt und Unterpositionen bearbeiten
            case 'count':
            case 'edit':

                this.openPositionDialog(iElementKey);

                break;

            case 'amount_provision_reload':

                this.recalculateCommissionAmounts([iElementKey]);

                break;
            case 'multiple_checkbox':
                var mChecked = false;
                if(oElement.checked){
                    mChecked = 'checked';
                }
                $$('.onPdf.position').each(function(oCheckbox){
                    oCheckbox.checked = mChecked;
                });
                break;
            case 'onPdf_toggle_all':
                this.toggleDocumentOnPdfCheckboxes();
                break;
            case 'amount_commission_refresh_all':
                this.recalculateAllCommissionAmounts();
                break;
            default:
                break;
        }

    },

    // TODO: Refaktorisieren
    initCamera: function() {

        var oGui = this;

        // Grab elements, create settings, etc.
        var canvas = document.getElementById("canvas"),
            canvas_preview = document.getElementById("canvas_preview"),
            context = canvas.getContext("2d"),
            context_preview = canvas_preview.getContext("2d"),
            video = document.getElementById("video"),
            videoObj = { "video": true },
            errBack = function(error) {
                console.log("Video capture error: ", error.code);
            };

        var oCameryErrorMessage = this.getNotificationDiv('error', 'error', this.getTranslation('camera_not_available'));

        var promisifiedOldGUM = function(constraints) {

            // First get ahold of getUserMedia, if present
            var getUserMedia = (navigator.getUserMedia ||
                navigator.webkitGetUserMedia ||
                navigator.mozGetUserMedia);

            // Some browsers just don't implement it - return a rejected promise with an error
            // to keep a consistent interface
            if(!getUserMedia) {
                return Promise.reject(new Error('getUserMedia is not implemented in this browser'));
            }

            // Otherwise, wrap the call to the old navigator.getUserMedia with a Promise
            return new Promise(function(resolve, reject) {
                getUserMedia.call(navigator, constraints, resolve, reject);
            });

        };

        // Older browsers might not implement mediaDevices at all, so we set an empty object first
        if(navigator.mediaDevices === undefined) {
            navigator.mediaDevices = {};
        }

        // Some browsers partially implement mediaDevices. We can't just assign an object
        // with getUserMedia as it would overwrite existing properties.
        // Here, we will just add the getUserMedia property if it's missing.
        if(navigator.mediaDevices.getUserMedia === undefined) {
            navigator.mediaDevices.getUserMedia = promisifiedOldGUM;
        }

        if(
            navigator.mediaDevices &&
            navigator.mediaDevices.getUserMedia
        ) {
            // Not adding `{ audio: true }` since we only want video now
            var oMedia = navigator.mediaDevices.getUserMedia({
                video: true
            });

            oMedia.then(function(stream) {
                oGui.oWebcamStream = stream;
                //video.src = window.URL.createObjectURL(stream);
                video.srcObject = stream;
                video.play();
                oGui.oWebcamVideo = video;
            });

            oMedia.catch(function(err) {
                $j('#camera_container').html(oCameryErrorMessage);
                $j(oCameryErrorMessage).show();
                console.log(err.name);
            });
        } else {
            $j('#camera_container').html(oCameryErrorMessage);
            $j(oCameryErrorMessage).show();
        }

        $j('#camera_frame').draggable({
            containment: "parent"
        });

    },

    prepareSaveDialog: function($super, aData, bAsNewEntry, aElement, sAdditionalRequestParams, bAsUrl) {

        var oCanvas = document.getElementById("canvas");
        var oSaveCameraHidden = $('save_camera');

        // Suboptimale Abfrage, ob wir im Camera Dialog sind
        if(
            oCanvas &&
            oSaveCameraHidden
        ) {
            var sData = oCanvas.toDataURL('image/png');
            $('save_camera').value = sData;
        }

        $super(aData, bAsNewEntry, aElement, sAdditionalRequestParams, bAsUrl);

    },

    /**
     * Provisionsbeträge neu laden
     *
     * @param {Array} aElementKeys
     */
    recalculateCommissionAmounts: function(aElementKeys) {

        var oDialogTab = $('position_row_'+aElementKeys[0]).up('.GUIDialogTabContentDiv');
        if(
            !oDialogTab &&
            $('position_key_hidden')
        ) {
            oDialogTab = $('position_row_'+$F('position_key_hidden')).up('.GUIDialogTabContentDiv');
        }
        var oRegex = new RegExp('_'+this.hash);
        var sDialogId = oDialogTab.id.replace(/tabs_content_/, '').replace(oRegex, '');

        var sParams = '&task=getNewCommissionAmounts';
        sParams += '&template_id='+$F('save['+this.hash+']['+sDialogId+'][template_id]');
        sParams += '&dialog_id='+sDialogId;
        sParams += '&type='+$F('save['+this.hash+']['+sDialogId+'][document_type]');

        var oPositions = {};
        $j.each(aElementKeys, function(iIndex, sElementKey) {
            var sPosition = 'positions['+iIndex+']';

            oPositions[sPosition] = {
                'amount': this.getNumericValue($('amount_after_discount_'+sElementKey)),
                'type_id': $F('type_id_'+sElementKey),
                'type': $F('type_'+sElementKey),
                'type_object_id': $F('type_object_id_'+sElementKey),
                'parent_id': $F('parent_id_'+sElementKey),
                'parent_type': $F('parent_type_'+sElementKey),
                'parent_booking_id': $F('parent_booking_id_'+sElementKey),
                'additional': $F('additional_'+sElementKey),
                'position_key': sElementKey
            };

            if($('position_key_hidden')) {
                oPositions[sPosition]['subposition_key'] = sElementKey;
                oPositions[sPosition]['position_key'] = $F('position_key_hidden');
            }

        }.bind(this));

        sParams += '&'+$j.param(oPositions);

        this.request(sParams);

    },

    /**
     * Alle Provisionsbeträge der Positionstabelle neu laden
     */
    recalculateAllCommissionAmounts: function() {
        // Alle Pfeile holen, die in aktiven Rows sind und auch nicht in der verstecken Position…
        var aPositionKeys = $j('#position_container tr:not(.readonly) td button[id^=amount_provision_reload_]:not([id$=XXX])').map(function() {
            return this.id.split('_').pop();
        });

        this.recalculateCommissionAmounts(aPositionKeys);
    },

    getTotalAmount : function(sDialogId) {

        if(!sDialogId && this.aLastData){
            sDialogId = this.aLastData.id;
        }

        var oTotalAmountCell;

        // Brutto
        if(this.iVatMode === 1) {
            if(
                $('save['+this.hash+']['+sDialogId+'][document_type]') &&
                (
                    $F('save['+this.hash+']['+sDialogId+'][document_type]') === 'creditnote' ||
                    $F('save['+this.hash+']['+sDialogId+'][document_type]') === 'creditnote_subagency'
                )
            ) {
                // Bei Steuer-inklusive muss dieses Feld benutzt werden, da im anderen Feld der totale Bruttobetrag drin steht (#4005)
                // TODO: Bei Creditnotes immer dieses Feld benutzen?
                oTotalAmountCell = $('total_amount_total_amount_provision');
            } else {
                oTotalAmountCell = $('total_amount_total_amount_total_gross');
            }
            // Netto
        } else if(this.iVatMode === 2) {
            // Hier kann bei einer CN das selbe Feld benutzt werden, da Summe von total_amount_total_amount_provision (im Gegensatz zu iVatMode == 1, #7236)
            oTotalAmountCell = $('total_amount');
            // Ohne
        } else {
            if(
                $('save['+this.hash+']['+sDialogId+'][document_type]') &&
                (
                    $F('save['+this.hash+']['+sDialogId+'][document_type]') === 'creditnote' ||
                    $F('save['+this.hash+']['+sDialogId+'][document_type]') === 'creditnote_subagency'
                )
            ) {
                // Bei keiner Steuer muss auch dieses Feld benutzt werden (wie oben), da im anderen Feld der totale Betrag drin steht (#11188)
                oTotalAmountCell = $('total_amount_total_amount_provision');
            } else {
                oTotalAmountCell = $('total_amount_total_amount_total');
            }
        }

        if(oTotalAmountCell){
            var fTotalAmount = oTotalAmountCell.innerHTML.parseNumber();
        }

        return fTotalAmount;

    },

    updatePositionDiscountRow : function(iElementKey) {

        // Wenn es keine Unterposition ist
        if(iElementKey.indexOf('SI') == -1) {
            return;
        }

        var oElement = $('amount_discount_'+iElementKey);

        var fDiscount = this.getNumericValue($(oElement));

        var sDiscountRowKey = iElementKey.replace(/SI/, 'SC');
        var oDiscountRow = $('position_row_'+sDiscountRowKey);

        if(!oDiscountRow) {
            return;
        }

        var fDiscountAmount = 0;
        var fDiscountAmountCommission = 0;
        var fDiscountAmountNet = 0;

        if(fDiscount > 0) {
            oDiscountRow.show();

            var sDiscountDescription = this.sDiscountDescription;

            sDiscountDescription = sDiscountDescription.replace(/\{percent\}/, this.thebingNumberFormat(fDiscount));
            sDiscountDescription = sDiscountDescription.replace(/\{description\}/, $F('description_'+iElementKey));

            var oDiscountDescription = $('description_'+sDiscountRowKey);

            oDiscountDescription.updateValue(sDiscountDescription);
            this.resizeTextarea(oDiscountDescription, 21);

            var fDiscount = 0;
            if($('amount_discount_'+iElementKey)) {
                fDiscount = this.getNumericValue($('amount_discount_'+iElementKey));
            }

            if($('amount_'+iElementKey)) {
                fDiscountAmount = (this.getNumericValue($('amount_'+iElementKey)) / 100) * fDiscount;
                fDiscountAmount = fDiscountAmount.toFixed(2);
            }

            if($('amount_provision_'+iElementKey)) {
                fDiscountAmountCommission = (this.getNumericValue($('amount_provision_'+iElementKey)) / 100) * fDiscount;
                fDiscountAmountCommission = fDiscountAmountCommission.toFixed(2);
            }

            if($('amount_after_discount_'+iElementKey)) {
                fDiscountAmountNet = (this.getNumericValue($('amount_after_discount_'+iElementKey)) / 100) * fDiscount;
                fDiscountAmountNet = fDiscountAmountNet.toFixed(2);
            }

        } else {
            oDiscountRow.hide();
        }

        if($('amount_'+sDiscountRowKey)) {
            $('amount_'+sDiscountRowKey).update(this.thebingNumberFormat(fDiscountAmount));
        }

        if($('amount_provision_'+sDiscountRowKey)) {
            $('amount_provision_'+sDiscountRowKey).update(this.thebingNumberFormat(fDiscountAmountCommission));
        }

        if($('amount_after_discount_'+sDiscountRowKey)) {
            $('amount_after_discount_'+sDiscountRowKey).update(this.thebingNumberFormat(fDiscountAmountNet));
        }

    },

    getNumericValue : function(oField) {

        var sTag = oField.tagName;
        var fValue;

        if(
            sTag == 'INPUT' ||
            sTag == 'SELECT' ||
            sTag == 'TEXTAREA'
        ){
            fValue = oField.value.parseNumber();
        } else {
            fValue = oField.innerHTML.parseNumber();
        }

        return fValue;

    },

    openPositionDialog : function(iElementKey) {

        // Get dialog id
        var oDialogTab = $('position_row_'+iElementKey).up('.GUIDialogTabContentDiv');
        var oRegex = new RegExp('_'+this.hash);
        var sDialogId = oDialogTab.id.replace(/tabs_content_/, '').replace(oRegex, '');

        // Request zum Fenster öffnen absenden
        var sParam = '&';

        sParam += $('dialog_form_'+sDialogId+'_'+this.hash).serialize();

        sParam += '&task=openPositionDialog';

        sParam += '&description='+encodeURIComponent($F('description_'+iElementKey));

        sParam += '&type='+$F('type_'+iElementKey);
        sParam += '&type_id='+$F('type_id_'+iElementKey);

        sParam += '&amount='+this.getNumericValue($('amount_'+iElementKey));

        if($('amount_provision_'+iElementKey)) {
            sParam += '&amount_provision='+this.getNumericValue($('amount_provision_'+iElementKey));
        }
        if($('amount_discount_'+iElementKey)) {
            sParam += '&amount_discount='+this.getNumericValue($('amount_discount_'+iElementKey));
        }

        sParam += '&position_key='+iElementKey;

        // Daten werden für reloadPositionsTable benötigt
        sParam +=  '&document_id='+$F('save['+this.hash+']['+sDialogId+'][document_id]');
        sParam +=  '&template_id='+$F('save['+this.hash+']['+sDialogId+'][template_id]');
        sParam +=  '&language='+$F('save['+this.hash+']['+sDialogId+'][language]');
        sParam +=  '&document_type='+$F('save['+this.hash+']['+sDialogId+'][document_type]');
        sParam +=  '&refresh='+$F('save['+this.hash+']['+sDialogId+'][is_refesh]');
        sParam +=  '&negate='+$F('save['+this.hash+']['+sDialogId+'][is_credit]');

        this.request(sParam);

    },

    waitForInputEventStudent : function(oEvent) {

        var oElement = oEvent.currentTarget;

        // Checkboxen und Selects direkt ausführen, alles andere mit timeout
        if(
            (
                oElement.tagName == 'INPUT' &&
                oElement.type == 'checkbox'
            ) ||
            oElement.tagName == 'SELECT' ||
            oElement.tagName == 'BUTTON' ||
            oElement.tagName == 'IMG' ||
            oElement.tagName == 'TD' ||
            oElement.hasClassName('calendar_input')
        ) {
            this.executeInputEvent(oElement);
        }else {

            if(this.oWaitForInputEventStudentObserver[oElement.id]){
                clearTimeout(this.oWaitForInputEventStudentObserver[oElement.id]);
            }

            this.oWaitForInputEventStudentObserver[oElement.id] = setTimeout(this.executeInputEvent.bind(this, oElement), 500);

        }

    },

    /**
     * @param oElement
     * @param sElementKey
     * @param [bReadOnlyParam]
     */
    togglePositionActive : function(oElement, sElementKey, bReadOnlyParam) {

        var bReadOnly = bReadOnlyParam;
        if(typeof bReadOnly == 'undefined') {
            bReadOnly = !oElement.checked;
        }

        var iDiscountKey = sElementKey.replace('SI', 'SC');

        // Eingabefelder der Zeile readonly setzen
        var oRow = $('position_row_'+sElementKey);
        var oCount = $('count_'+sElementKey);
        var oInitial = $('initalcost_'+sElementKey);
        var oDescription = $('description_'+sElementKey);
        var oAmount = $('amount_'+sElementKey);
        var oAmountProvision = $('amount_provision_'+sElementKey);
        var oAmountDiscount	= $('amount_discount_'+sElementKey);
        var oTax = $('tax_category_'+sElementKey);

        // Rabattzeile
        var oDiscountRow = $('position_row_'+iDiscountKey);
        var oDescriptionDiscount = $('description_'+iDiscountKey);

        if(oCount) {
            this.toggleReadonly(oCount, bReadOnly);
        }

        if(oInitial) {
            this.toggleReadonly(oInitial, bReadOnly);
        }

        if(oDescription) {
            this.toggleReadonly(oDescription, bReadOnly);
        }

        if(oAmount) {
            this.toggleReadonly(oAmount, bReadOnly);
        }

        if(oAmountProvision) {
            this.toggleReadonly(oAmountProvision, bReadOnly);
        }

        if(oAmountDiscount) {
            this.toggleReadonly(oAmountDiscount, bReadOnly);
        }

        if(oTax) {
            this.toggleReadonly(oTax, bReadOnly);
        }

        if(oRow) {
            this.toggleReadonly(oRow, bReadOnly);
        }

        // Rabattzeile
        if(oDiscountRow) {
            this.toggleReadonly(oDiscountRow, bReadOnly);
        }

        if(oDescriptionDiscount) {
            this.toggleReadonly(oDescriptionDiscount, bReadOnly);
        }

        this.updatePositionCache();

        if(typeof bReadOnlyParam == 'undefined') {
            this.toggleDocumentOnPdfAllCheckbox();
        }

        // Alle Gesamtsummen neu ausrechnen
        this.recalculateSums();

    },

    toggleReadonly : function(oElement, bReadonly) {

        if(!oElement) {
            return;
        }

        var sTag = oElement.tagName;
        var bIsFormElement = false;

        if(
            sTag == 'INPUT' ||
            sTag == 'SELECT' ||
            sTag == 'TEXTAREA'
        ) {
            bIsFormElement = true;
        }

        if(bReadonly) {

            if(bIsFormElement) {

                var oHidden = document.createElement('input');
                oHidden.type = 'hidden';
                oHidden.id = 'hidden_'+oElement.id;
                oHidden.name = oElement.name;

                if(oElement.checked){
                    oHidden.value = oElement.value;
                    oElement.insert({before: oHidden});
                }

                //oElement.disable();
                oElement.removeAttribute("readonly");
                oElement.setAttribute("readonly", "readonly");

            }

            if(!oElement.hasClassName('readonly')){
                oElement.addClassName('readonly');
            }

        } else {

            if(bIsFormElement) {

                if($('hidden_'+oElement.id)) {
                    $('hidden_'+oElement.id).remove();
                }

                //oElement.enable();
                oElement.removeAttribute("readonly");

            }

            if(oElement.hasClassName('readonly')){
                oElement.removeClassName('readonly');
            }

        }

    },

    recalculatePosition : function(iElementKey) {

        this.aCurrentAmount = [];
        var iAmount = 0;
        var iAmountProvision = 0;
        var iDiscount = 0;
        var iTax = 0;
        var iAmountAgency;
        var iAmountCurrent;
        var iAmountDiscount;

        iAmount = this.getNumericValue($('amount_'+iElementKey));
        iAmountCurrent = iAmount;

        if($('amount_discount_'+iElementKey)) {
            iDiscount = this.getNumericValue($('amount_discount_'+iElementKey));
            // Wofür ist das? Man muss doch mit dem gerundeten Betrag rechnen der auch angezeigt wird
            iAmountDiscountNotRounded = (iAmountCurrent / 100) * iDiscount;
            iAmountDiscount = iAmountDiscountNotRounded.toFixed(2);
            iAmountCurrent = iAmountCurrent - iAmountDiscount;
        }

        if($('tax_category_'+iElementKey)) {
            iTaxRate = this.vatRates[$F('tax_category_'+iElementKey)];
        }

        // Discount
        if($('amount_discount_amount_'+iElementKey)) {
            $('amount_discount_amount_'+iElementKey).update(this.thebingNumberFormat(iAmountDiscount));
        }

        // Agenturbetrag
        if($('amount_after_discount_'+iElementKey)) {
            $('amount_after_discount_'+iElementKey).update(this.thebingNumberFormat(iAmountCurrent));
        }

        if($('amount_provision_'+iElementKey)) {
            iAmountProvision = this.getNumericValue($('amount_provision_'+iElementKey));
            iAmountCurrent = iAmountCurrent - iAmountProvision;
        }

        this.aCurrentAmount[iElementKey] = iAmountCurrent;

        // Gesamt brutto
        if($('amount_total_gross_'+iElementKey)) {
            $('amount_total_gross_'+iElementKey).update(this.thebingNumberFormat(iAmountCurrent));
        }

        // Gesamt netto
        if($('amount_total_net_'+iElementKey)) {
            $('amount_total_net_'+iElementKey).update(this.thebingNumberFormat(iAmountCurrent));
        }

        // Gesamt netto
        if($('amount_total_'+iElementKey)) {
            $('amount_total_'+iElementKey).update(this.thebingNumberFormat(iAmountCurrent));
        }

        // Aktualisiert die Discount Zeile falls vorhanden
        this.updatePositionDiscountRow(iElementKey);

        this.recalculateSums();



    },

    recalculateSums : function() {

        var fTotalOnSite = 0;
        var fTotalPreArrival = 0;

        var aTotalTax = [];
        var iTotalSum = 0;

        var iTotalSumNet = 0;

        this.aColumnCache.each(function(sColumn) {

            var fSumOnSite = this.sumPositionColumn(sColumn, 'on_site');
            var fSumPreArrival = this.sumPositionColumn(sColumn, 'pre_arrival');
            var fSum = fSumOnSite + fSumPreArrival;

            var fSumOnSiteRounded = fSumOnSite.toFixed(2);
            var fSumPreArrivalRounded = fSumPreArrival.toFixed(2);
            var fSumRounded	= fSum.toFixed(2);

            if($('total_amount_on_site_'+sColumn)){
                $('total_amount_on_site_'+sColumn).innerHTML = this.thebingNumberFormat(fSumOnSiteRounded);
            }

            if($('total_amount_pre_arrival_'+sColumn)){
                $('total_amount_pre_arrival_'+sColumn).innerHTML = this.thebingNumberFormat(fSumPreArrivalRounded);
            }

            if($('total_amount_total_'+sColumn)){
                $('total_amount_total_'+sColumn).innerHTML = this.thebingNumberFormat(fSumRounded);
            }

            if(
                sColumn == this.sTotalAmountColumn
            ) {
                iTotalSum = fSumOnSite + fSumPreArrival;
            }

            fTotalOnSite += fSumOnSite;
            fTotalPreArrival += fSumPreArrival;

        }.bind(this));

        if(
            fTotalOnSite == 0 ||
            fTotalPreArrival == 0
        ) {
            if($('row_sum_on_site')){
                $('row_sum_on_site').hide();
            }
            if($('row_sum_pre_arrival')){
                $('row_sum_pre_arrival').hide();
            }

        } else {
            if($('row_sum_on_site')){
                $('row_sum_on_site').show();
            }
            if($('row_sum_pre_arrival')){
                $('row_sum_pre_arrival').show();
            }
        }

        // Steuern
        var iValue;
        if(this.iVatMode > 0) {

            this.aPositionCache.each(function(iPositionKey) {

                var oElement = $(this.sTotalAmountColumn+'_'+iPositionKey);

                var iTaxCategory = 0;
                if($('tax_category_'+iPositionKey)) {
                    iTaxCategory = $F('tax_category_'+iPositionKey);
                }

                // Wert immer auslesen
                iValue = this.getNumericValue(oElement);

                if(!aTotalTax[iTaxCategory]) {
                    aTotalTax[iTaxCategory] = 0;
                }

                aTotalTax[iTaxCategory] += iValue;

            }.bind(this));

            iTotalSumNet = iTotalSum;
            var bShowTotalAmount = false;

            this.vatRates.each(function(aVatRate) {

                if(aTotalTax[aVatRate[0]]) {

                    var iTaxFactor = aVatRate[1] / 100 + 1;

                    if(this.iVatMode == 1) {
                        var iVatAmount = aTotalTax[aVatRate[0]] - aTotalTax[aVatRate[0]] / iTaxFactor;
                    } else {
                        var iVatAmount = aTotalTax[aVatRate[0]] * iTaxFactor - aTotalTax[aVatRate[0]];
                    }

                    if(this.iVatMode == 1) {
                        iTotalSumNet -= parseFloat(iVatAmount);
                    } else {
                        iTotalSumNet += parseFloat(iVatAmount);
                    }

                    iVatAmount = iVatAmount.toFixed(2);

                    $('tax_amount_'+aVatRate[0]).innerHTML = this.thebingNumberFormat(iVatAmount);
                    $('tax_row_'+aVatRate[0]).show();

                    bShowTotalAmount = true;

                } else {
                    if($('tax_row_'+aVatRate[0])) {
                        $('tax_row_'+aVatRate[0]).hide();
                    }
                }

            }.bind(this));

            iTotalSumNet = iTotalSumNet.toFixed(2);
            if($('total_amount')){
                $('total_amount').innerHTML = this.thebingNumberFormat(iTotalSumNet);
            }

            if($('total_amount_row')){
                if(bShowTotalAmount) {
                    $('total_amount_row').show();
                } else {
                    $('total_amount_row').hide();
                }
            }

        }

        this.calculatePaymentTermAmounts(this.document_id);

    },

    sumPositionColumn : function(sColumn, sType) {

        var fSum = 0;
        var iValue = 0;

        this.aPositionCache.each(function(iPositionKey) {

            if(sType) {

                if(sType == 'on_site') {
                    if(!$F('initalcost_'+iPositionKey)) {
                        return;
                    }
                } else {
                    if($F('initalcost_'+iPositionKey)) {
                        return;
                    }
                }

            }

            var oElement = $(sColumn+'_'+iPositionKey);

            if(
                sColumn=='amount_total_net' &&
                this.aCurrentAmount &&
                this.aCurrentAmount[iPositionKey]
            ){
                iValue = this.aCurrentAmount[iPositionKey];
            }else{
                if(
                    oElement.tagName == 'TH' ||
                    oElement.tagName == 'TD'
                ) {
                    iValue = oElement.innerHTML.parseNumber();
                } else {
                    iValue = oElement.value.parseNumber();
                }
            }

            fSum += iValue;

        }.bind(this));

        return fSum;

    },

    updatePositionCache : function() {

        var aRows = $$('.tblMainDocumentPositions tbody tr');

        this.aPositionCache = [];
        this.aColumnCache = [];

        var iPosition = 1;
        aRows.each(function(oRow) {

            var iElementKey = oRow.id.replace(/position_row_/, '');

            // Position aktualisieren
            $('position_'+iElementKey).value = iPosition++;

            if(this.aColumnCache.length == 0) {

                if($('amount_'+iElementKey)) {
                    this.aColumnCache[this.aColumnCache.length] = 'amount';
                }

                if($('amount_provision_'+iElementKey)) {
                    this.aColumnCache[this.aColumnCache.length] = 'amount_provision';
                }

                if($('amount_after_discount_'+iElementKey)) {
                    this.aColumnCache[this.aColumnCache.length] = 'amount_after_discount';
                }

                if($('amount_discount_amount_'+iElementKey)) {
                    this.aColumnCache[this.aColumnCache.length] = 'amount_discount_amount';
                }

                if($('amount_total_gross_'+iElementKey)) {
                    this.aColumnCache[this.aColumnCache.length] = 'amount_total_gross';
                }

                if($('amount_total_net_'+iElementKey)) {
                    this.aColumnCache[this.aColumnCache.length] = 'amount_total_net';
                }

                if($('amount_total_'+iElementKey)) {
                    this.aColumnCache[this.aColumnCache.length] = 'amount_total';
                }

            }

            var oInput = $('onpdf_'+iElementKey);
            var iValue = $F('onpdf_'+iElementKey);

            if(
                iValue
            ) {

                // IF Bedingung ist hier nur TESTWEISE drin
                if(
                    oInput.next('input') &&
                    oInput.next('input').disabled == true &&
                    oInput.next('input').checked == false
                ){

                }else{
                    this.aPositionCache[this.aPositionCache.length] = iElementKey;
                }


            }

        }.bind(this));

    },

    requestCallbackHook : function ($super, aData){
        // HOOK
        // RequestCallback der Parent Klasse

        $super(aData);

        // Bei true kommen keine Confirms bei Kurs/Unterkunft
        // wird für das school select benötigt
        this.bSkipConfirm = false;

        var sTask = aData.action;
        if(aData.data) {
            var sAction = aData.data.action;
        }

        if(aData.task && aData.task != ''){
            sTask = aData.task;
            sAction = aData.action;
        }

        // Fehler beim grupenspeichern
        var aError = null;
        if(aData.error){
            aError = aData.error;
        }

        var aDataOriginal = aData;
        aData = aData.data;

        if(
            sTask == 'saveDialogCallback' &&
            (
                (
                    aData.error &&
                    aData.error.length <= 0
                ) ||
                !aData.error
            )
        ){
            // aError ist beim Gruppenspeichern gefüllt
            if(aError == null){
                // sichergehen das die "0" ID Dialoge 100% zu gehen
                this.closeDialog('ID_0');
                this.closeDialog('DOCUMENT_0');
                this.closeDialog('GROUP_0');
            }
        }

        if(sTask == 'reloadPartialInvoiceTable') {

            $j('#partial_invoices_container').html(aData.html);
            if(aData.js) {
                eval(aData.js);
            }

            $j('.partial-invoices-loading').hide();

            oInput = $('partial_invoices_deposit_date');
            this.prepareCalendar(oInput);

        } else if(sTask == 'openPositionDialog'){

            this.sDiscountDescription = aData.discount_description;

            this.setPositionObserver();

        } else if(
            sTask == 'updateIcons' ||
            sTask == 'createTable'
        ){
            // Icons checken welche angezeigt werden dürfen
            this.updateIconsCheck(aData);

            // Zurücksetzen der  Counter die neue Positionen (Kurse, Unterkünfte,... hinzufügen
            this.oLastInquiryContainerAddCount = {
                'course': 0,
                'accommodation': 0,
                'transfer': 0,
                'insurance': 0,
                'course_guide': 0,
                'accommodation_guide': 0,
                'sponsoring_gurantee': 0,
                'activity': 0
            };

        } else if(sTask == 'loadNewSchoolDataCallback'){
            this.loadNewSchoolDataCallback(aData);
        } else if(	sTask == 'openDialog' || sTask == 'saveDialogCallback' ){

            this.aLastData = aData;

            if(
                sAction == 'edit' ||
                sAction == 'new'
            ) {

                this.courseLanguages = aData.course_languages ?? [];
                this.additionalservicesCourse = aData.additionalservices_course ?? [];
                this.additionalservicesAccommodation = aData.additionalservices_accommodation ?? [];
                this.courseData = aData.course_data ?? [];
                this.courseLessonsUnits = aData.course_lessons_units ?? [];

                var iSchool = 0;
                if($('save['+this.hash+']['+aData.id+'][school_id][ts_ij]')){
                    iSchool = $F($('save['+this.hash+']['+aData.id+'][school_id][ts_ij]'));
                }

                // START Ferien KalenderFelder müssen nochmal initialisiert werden da hier andere Tage zur Auswahl stehen
                $j('.holiday_from').each(function(iIndex, oCalendarInput) {
                    $j(oCalendarInput).bootstrapDatePicker('setDaysOfWeekDisabled', [2, 3, 4, 5]);
                });

                // START VISUM Felder laden
                var oVisumSelect = this.getDialogSaveField('status', 'ts_ijv');

                oVisumSelect.change(function() {

                    var oTabVisumData = $j('#dialog_' + this.sCurrentDialogId + '_' + this.hash + ' .student_record_visum');
                    var oVisumStatusFlexFieldDiv = oTabVisumData.find('.dialog-flex-fields[data-section=student_record_visum_status]');
                    var aVisumStatusFlexDependency = aData.visa_status_flex_fields[oVisumSelect.val()];

                    oVisumStatusFlexFieldDiv.find('.GUIDialogRow').hide();

                    if(Array.isArray(aVisumStatusFlexDependency)) {
                        aVisumStatusFlexDependency.forEach(function(iFieldId) {
                            var oField = oVisumStatusFlexFieldDiv.find(':input[data-flex-id=' + iFieldId + ']');
                            if(oField.length === 0) {
                                console.error('Flex field ' + iFieldId + ' not found');
                            }
                            oField.closest('.GUIDialogRow').show();
                        });
                    }

                }.bind(this)).change();

                // ENDE Visum Felder ##

                // Uploads bei neuer Inquiry deaktivieren, da die ID für die Flex-Uploads fehlt
                if(aData.inquiry_id == 0) {
                    $j('.upload_save_info').show(); // GUIDialogNotification
                    $j('.GUIDialogTabContentDiv .student_record_upload input').prop('disabled', true);
                }

                // START Payment method

                // Studentrecord
                this.reloadAgencyDependingFields(aData, 1);

                // Gruppen
                var oAgencyGroup = $('save['+this.hash+']['+aData.id+'][agency_id][kg]');
                var oPaymentMethodeGroup = $('save['+this.hash+']['+aData.id+'][payment_methode_group][kg]');
                var oPaymentMethodeCommentGroup = $('save['+this.hash+']['+aData.id+'][payment_method_comment_group][kg]');

                if(
                    oAgencyGroup &&
                    oPaymentMethodeGroup &&
                    oPaymentMethodeCommentGroup
                ){

                    if($F(oAgencyGroup) <= 0){
                        this.disablePaymentMethod(oPaymentMethodeGroup);
                    }

                    Event.observe(oAgencyGroup, 'change', function(e)
                    {
                        this.writePaymentMethodData(oAgencyGroup, oPaymentMethodeGroup, oPaymentMethodeCommentGroup, aData['agency_payment_method']);
                        if(
                            aData['agency_currency_id'] &&
                            aData['agency_currency_id'][$F(oAgencyGroup)]
                        ) {
                            this.changeCurrency(aData['agency_currency_id'][$F(oAgencyGroup)], aData.id, 'kg');
                        }
                    }.bind(this));
                }
                // ENDE Payment Method

                // START Suchen von ähnlichen Kunden
                var oFieldLastname = this.getDialogSaveField('lastname', 'cdb1');
                var oFieldFirstname = this.getDialogSaveField('firstname', 'cdb1');
                var oFieldBirthday = this.getDialogSaveField('birthday', 'cdb1');
                var oCustomerResultList = this.getDialogSaveField('customer_results_list');

                if(
                    oFieldLastname.length &&
                    oFieldFirstname.length &&
                    oFieldBirthday.length &&
                    oCustomerResultList.length
                ) {
                    oCustomerResultList.closest('.GUIDialogRow').hide();

                    oFieldLastname.on('change keyup', function() {
                        this.prepareCheckForSameUser(aData);
                    }.bind(this));

                    oFieldFirstname.on('change keyup', function() {
                        this.prepareCheckForSameUser(aData);
                    }.bind(this));

                    oFieldBirthday.on('change keyup', function() {
                        this.prepareCheckForSameUser(aData);
                    }.bind(this));

                }

                $j('.customernumber_search').keyup(function(e) {
                    this.prepareCheckForSameUser(aData, true, $j(e.target).parents('.customer_identification_results_field').get(0));
                }.bind(this));

                // ENDE

                // START Hidden Feld zum überschreibe von Schüler- und Buchungskontaktdaten
                if($('dialog_form_'+aData.id+'_'+this.hash)){
                    var oForm = $('dialog_form_'+aData.id+'_'+this.hash);

                    oForm.insert({
                        top: new Element('input', {
                            id : 'replaceCustomerId['+this.hash+']['+aData.id+']',
                            name: 'replaceCustomerId',
                            type: 'hidden',
                            value: 0
                        })
                    });

                    oForm.insert({
                        top: new Element('input', {
                            id : 'replaceBookerId['+this.hash+']['+aData.id+']',
                            name: 'replaceBookerId',
                            type: 'hidden',
                            value: 0
                        })
                    });

                    oForm.insert({
                        top: new Element('input', {
                            id : 'replaceHubspotContactId['+this.hash+']['+aData.id+']',
                            name: 'replaceHubspotContactId',
                            type: 'hidden',
                            value: 0
                        })
                    });
                }
                // ENDE

                // START Gruppen Checkbox von Kontaktperson die auch in Mitglied der Grupper werden soll
                if($('save['+this.hash+']['+aData.id+'][contact_is_customer][kg]')){
                    var oCheckbox = $('save['+this.hash+']['+aData.id+'][contact_is_customer][kg]');

                    Event.observe(oCheckbox, 'change', function() {
                        this.groupCustomerContact(aData, oCheckbox);
                    }.bind(this));
                }
                // ENDE

                // START Einfügen der Ferien
                $$('.holiday_id').each( function (elem) {

                    if ( $F(elem) != 'new' ) {
                        var sHolidayId  = 'holidays['+$F(elem)+'][id]';
                        this.reloadInquiryHolidaySelectFields ( $F(sHolidayId) ) ;
                    }
                }.bind(this));
                // ENDE

                // START Gruppen nachladen
                if(
                    aData.groups &&
                    aData.inquiry_group &&
                    $('save['+this.hash+']['+aData.id+'][group_id][ki]')
                ){
                    var oSelect = $('save['+this.hash+']['+aData.id+'][group_id][ki]');
                    oSelect.update();
                    $H(aData.groups).each( function (oOption) {
                        var bSelected = false;
                        if(oOption.key == aData.inquiry_group){
                            bSelected = true;
                        }
                        oSelect.insert({
                            top: new Element('option', {
                                value : oOption.key,
                                selected : bSelected
                            }).update(oOption.value)
                        });
                    }.bind(this))
                }
                // ENDE

                // Schul-Select deaktivieren
                var oSchool = $('save['+this.hash+']['+aData.id+'][school_id][ts_ij]');

                if(!oSchool) {
                    var oSchool = $('save['+this.hash+']['+aData.id+'][school_id][kg]');
                }

                if(oSchool) {

                    var oGroupId = $('save['+this.hash+']['+aData.id+'][group_id][ki]');

                    if(
                        aData.has_invoice == 1 ||
                        aData.all_school != 1 ||
                        (
                            oGroupId &&
                            $F(oGroupId) > 0
                        )
                    ) {

                        oSchool.addClassName('readonly');
                        oSchool.disabled = 'disabled';

                        var oHidden = new Element('input');
                        oHidden.name = oSchool.name;
                        oHidden.value = $F(oSchool);
                        oHidden.className = 'hidden_school';
                        oHidden.type = 'hidden';
                        oSchool.insert({after:oHidden});

                        this.updateFlexUploadFields($F(oSchool));

                    } else {
                        Event.observe(oSchool, 'change', function(e) {
                            return this.changeSchoolCallback(oSchool, aData);
                        }.bind(this));
                    }

                    /*
					 * Values das neuer Inquiry erneut setzten - auch dynamischen content da sonst nicht alle
					 * Abhängigkeiten korrekt initialisiert werden! (#9427)
					 */
                    if(aData.all_school === 1) {
                        this.aDataOfInquiry = aData.values;
                        if(sAction === 'new') {
                            this.loadNewSchoolData(oSchool);
                        }
                        if(sAction === 'edit') {
                            this.loadNewSchoolData(oSchool, true);
                        }
                    }

                } else {
                    console.error('No school select found!');
                }

                // START Kurse hinzufügen
                if(
                    $('add_new_course')){
                    var oIcon = $('add_new_course');
                    Event.observe(oIcon, 'click', function(e)
                    {
                        this.writeNewInquiryCourseOrAccommodation(e, aData);
                    }.bind(this));
                }
                // ENDE

                // START Kurse guide hinzufügen
                if(
                    $('add_new_course_guide')){
                    var oIcon = $('add_new_course_guide');
                    Event.observe(oIcon, 'click', function(e)
                    {
                        this.writeNewInquiryCourseOrAccommodation(e, aData, 'course_guide');
                    }.bind(this));
                }
                // ENDE

                // START Kurse entfernen
                $$('#dialog_'+aData.id+'_'+this.hash+' .course_block_remover').each(function(oInput){
                    Event.observe(oInput, 'click', function(e)
                    {
                        this.deleteInquiryCourseOrAccommodation(e, aData);
                    }.bind(this));
                }.bind(this));
                // ENDE

                // START Kurse guide entfernen
                $$('#dialog_'+aData.id+'_'+this.hash+' .course_guide_block_remover').each(function(oInput){
                    Event.observe(oInput, 'click', function(e)
                    {
                        this.deleteInquiryCourseOrAccommodation(e, aData, 'course_guide');
                    }.bind(this));
                }.bind(this));
                // ENDE


                // START Unterkünfte hinzufügen
                if($('add_new_accommodation')){
                    var oIcon = $('add_new_accommodation');
                    Event.observe(oIcon, 'click', function(e)
                    {
                        this.writeNewInquiryCourseOrAccommodation(e, aData, 'accommodation');
                    }.bind(this));
                }
                // ENDE

                // START Unterkünfte guide hinzufügen
                if($('add_new_accommodation_guide')){
                    var oIcon = $('add_new_accommodation_guide');
                    Event.observe(oIcon, 'click', function(e)
                    {
                        this.writeNewInquiryCourseOrAccommodation(e, aData, 'accommodation_guide');
                    }.bind(this));
                }
                // ENDE

                // START Unterkünfte löschen
                $$('#dialog_'+aData.id+'_'+this.hash+' .accommodation_block_remover').each(function(oInput){
                    Event.observe(oInput, 'click', function(e)
                    {
                        this.deleteInquiryCourseOrAccommodation(e, aData, 'accommodation');
                    }.bind(this));
                }.bind(this));
                // ENDE

                // START Unterkünfte guide löschen
                $$('#dialog_'+aData.id+'_'+this.hash+' .accommodation_guide_block_remover').each(function(oInput){
                    Event.observe(oInput, 'click', function(e)
                    {
                        this.deleteInquiryCourseOrAccommodation(e, aData, 'accommodation_guide');
                    }.bind(this));
                }.bind(this));
                // ENDE



                // START Versicherungen hinzufügen
                if($('add_new_insurance')){
                    Event.observe($('add_new_insurance'), 'click', function(e)
                    {
                        this.writeInsuranceMask(aData);
                    }.bind(this));
                }
                // ENDE

                // START Aktivitäten hinzufügen
                if($('add_new_activity')){
                    Event.observe($('add_new_activity'), 'click', function(e) {
                        this.writeNewInquiryCourseOrAccommodation(e, aData, 'activity');
                    }.bind(this));
                }
                // ENDE

                $j('#add_new_sponsoring_gurantee').click(function(e) {
                    this.writeNewInquiryCourseOrAccommodation(e, aData, 'sponsoring_gurantee');
                }.bind(this));

                // START Kalender durchgehen die readonly sind und sperren
                $$('#dialog_'+aData.id+'_'+this.hash+' .calendar_input').each(function(oInput){
                    if(
                        oInput.hasClassName('readonly')
                    ){
                        this.disableCalendarInput(oInput);
                    }
                }.bind(this));

                $$('#dialog_'+aData.id+'_'+this.hash+' .calculateAccUntil').each(function(oInput){
                    if(
                        oInput.hasClassName('readonly')
                    ){
                        this.disableCalendarInput(oInput);
                    }
                }.bind(this));

                $$('#dialog_'+aData.id+'_'+this.hash+' .calculateAccTo').each(function(oInput){
                    if(
                        oInput.hasClassName('readonly')
                    ){
                        this.disableCalendarInput(oInput);
                    }
                }.bind(this));

                $$('#dialog_'+aData.id+'_'+this.hash+' .calculateCourseTo').each(function(oInput){
                    if(
                        oInput.hasClassName('readonly')
                    ){
                        this.disableCalendarInput(oInput);
                    }
                }.bind(this));

                $$('#dialog_'+aData.id+'_'+this.hash+' .calculateCourseUntil').each(function(oInput){
                    if(
                        oInput.hasClassName('readonly')
                    ){
                        this.disableCalendarInput(oInput);
                    }
                }.bind(this));

                $$('#dialog_'+aData.id+'_'+this.hash+' .holiday_from').each(function(oInput){
                    if(
                        oInput.hasClassName('readonly')
                    ){
                        this.disableCalendarInput(oInput);
                    }
                }.bind(this));

                $$('#dialog_'+aData.id+'_'+this.hash+' .insurance_froms').each(function(oInput){
                    if(
                        oInput.hasClassName('readonly')
                    ){
                        this.disableCalendarInput(oInput);
                    }
                }.bind(this));

                $$('#dialog_'+aData.id+'_'+this.hash+' .insurance_untils').each(function(oInput){
                    if(
                        oInput.hasClassName('readonly')
                    ){
                        this.disableCalendarInput(oInput);
                    }
                }.bind(this));

                if(
                    $('save['+this.hash+']['+aData.id+'][date_from][ts_ijv]') &&
                    $('save['+this.hash+']['+aData.id+'][date_from][ts_ijv]').hasClassName('readonly')
                ){
                    var oInput = $('save['+this.hash+']['+aData.id+'][date_from][ts_ijv]');
                    this.disableCalendarInput(oInput);
                }

                if(
                    $('save['+this.hash+']['+aData.id+'][date_until][ts_ijv]') &&
                    $('save['+this.hash+']['+aData.id+'][date_until][ts_ijv]').hasClassName('readonly')
                ){
                    var oInput = $('save['+this.hash+']['+aData.id+'][date_until][ts_ijv]');
                    this.disableCalendarInput(oInput);
                }

                if(
                    $('save['+this.hash+']['+aData.id+'][passport_date_of_issue][ts_ijv]') &&
                    $('save['+this.hash+']['+aData.id+'][passport_date_of_issue][ts_ijv]').hasClassName('readonly')
                ){
                    var oInput = $('save['+this.hash+']['+aData.id+'][passport_date_of_issue][ts_ijv]');
                    this.disableCalendarInput(oInput);
                }

                if(
                    $('save['+this.hash+']['+aData.id+'][passport_due_date][ts_ijv]') &&
                    $('save['+this.hash+']['+aData.id+'][passport_due_date][ts_ijv]').hasClassName('readonly')
                ){
                    var oInput = $('save['+this.hash+']['+aData.id+'][passport_due_date][ts_ijv]');
                    this.disableCalendarInput(oInput);
                }
                // ENDE

                this.setGeneralObserver(aData);

                this.setCoursesObserver(aData);

                this.setAccommodationObserver(aData);

                this.setMatchingObserver(aData);

                this.setHolidayObserver(aData);

                this.setInsuranceObserver(aData);

                this.setActivityObserver(aData);

                this.setSponsoringObserver();

                this.setTransferObserver(aData);

                this.setCustomerObserver(aData);

                this.aBundledCourseLevels = aData.bundled_course_levels;
                this.sDateFormat = aData.date_format;

                jQuery.extend(this.translations, aData.translations);

                this.bindLevelEvents('.course_level_select');
                this.bindLevelEvents('.course_guide_level_select');

                this.initProgressReportObserver();

            }
            else if(sAction == 'document_edit')
            {

                // Wird zwingend benötigt
                this.document_id			= aData.id;

                // editierbare Layoutfelder
                var aEditableFields = aData.data['editable_fields'] ?? [];
                var aEditableFieldData = aData.data['editable_field_data'] ?? [];

                this.editable_field_data = aEditableFieldData;

                this.vatRates = aData.data['vat'];
                // Wenn Schule ext. Steuern hat dann 1 sonst 0
                this.addVat = aData.data['ext_vat'];
                this.iVatMode = aData.data['vat_mode'];

                // Zusatzkosten
                this.aAdditionalCosts = aData.data['additional_costs'];

                // Gesamtbetrag Spalte
                this.sTotalAmountColumn = aData.data['total_amount_column'];

                // Tooltips
                this.aPositionsTooltips = {};
                if(aData.data.position_tooltips) {
                    this.aPositionsTooltips = aData.data.position_tooltips;
                }

                // Gruppe
                this.bGroup = aData.data['group'];
                this.iCountOthers = aData.data['count_others'];
                this.iCountGuides = aData.data['count_guides'];

                var oTemplate = $('save['+this.hash+']['+aData.id+'][template_id]');
                var oSignatureUserId =  $('save['+this.hash+']['+aData.id+'][signature_user_id]');

                // Adress-Select
                var oAdressSelect = $('save['+this.hash+']['+aData.id+'][address_select]');
                var oInvoiceSelect = this.getDialogSaveField('invoice_select');
                var oPaymentConditionSelect = this.getDialogSaveField('payment_condition_select');
                var oPartialInvoiceCheckbox = this.getDialogSaveField('partial_invoice');
                var oCompanySelect = this.getDialogSaveField('company_id');

                if(oTemplate) {

                    // wenn schon ein template ge
                    var iTempValue = $F(oTemplate);

                    aData.template_field_data = aData.data['template_field_data'];

                    if(iTempValue > 0) {
                        var aTemp = aData.data['template_field_data'];
                        this.toggleTemplateFields(aTemp, aData.id, false, aData.data['document_id'], 0);
                    } else {
                        // gibt noch kein Template verstecke alle Positionen
                        if($('document_template_items')){
                            $('document_template_items').hide();
                        }
                    }

                    // Feld mit der Sprache suchen
                    var oLanguage = $('save['+this.hash+']['+aData.id+'][language]');

                    Event.observe(oTemplate, 'change', function() {
                        this.reloadTemplateLanguageSelect(oTemplate);
                    }.bind(this));

                    // Event für Änderung der Sprache
                    oLanguage.observe('change', function() {
                        this.executeReloadPositionsTableEvent(aData, oTemplate, oLanguage)
                    }.bind(this));

                    // Event für Änderung vom Adress-Select
                    if(oAdressSelect) {
                        oAdressSelect.observe('change', function() {
                            this.executeReloadPositionsTableEvent(aData, oTemplate, oLanguage)
                        }.bind(this));
                    }

                    oCompanySelect.change(function() {
                        this.executeReloadPositionsTableEvent(aData, oTemplate, oLanguage)
                    }.bind(this));

                    oInvoiceSelect.on('change', function() {
                        this.executeReloadPositionsTableEvent(aData, oTemplate, oLanguage)
                    }.bind(this));

                    // Zahlungsbedingung
                    oPaymentConditionSelect.focus(function() {
                        oPaymentConditionSelect.data('old', oPaymentConditionSelect.val());
                    });
                    oPaymentConditionSelect.change(function() {
                        if(oPaymentConditionSelect.data('old') === '0') {
                            if(confirm(this.getTranslation('change_payment_condition_question'))) {
                                this.executeReloadPositionsTableEvent(aData, oTemplate, oLanguage)
                            } else {
                                oPaymentConditionSelect.val('0');
                            }
                        } else {
                            this.executeReloadPositionsTableEvent(aData, oTemplate, oLanguage)
                        }
                    }.bind(this));

                    if(oSignatureUserId){
                        Event.observe(oSignatureUserId, 'change', function() {

                            var iTempValue = $F(oTemplate);
                            var aTemp = {};
                            aTemp.id = iTempValue;
                            aTemp.inquirypositions_view = aData.inquirypositions_view;
                            aTemp.language = $F(oLanguage);
                            aTemp.document_type = $F('save['+this.hash+']['+aData.id+'][document_type]');
                            aTemp.negate = $F('save['+this.hash+']['+aData.id+'][is_credit]');
                            aTemp.refresh = $F('save['+this.hash+']['+aData.id+'][is_refesh]');
                            aTemp.change_user_signature = 1;

                            // formulardaten mitschicken
                            aTemp.form = $('dialog_form_'+aData.id+'_'+this.hash).serialize();

                            this.reloadPositionsTable(aTemp, aData.id, aData.data['document_id']);

                        }.bind(this));
                    }
                }

                // Bei Teilrechnung nur Zahlungsbedingungen aktivieren, die auch dafür geeignet sind
                // Wenn es keine geeignete Zahlungsbedingung gibt, wird die Checkbox nicht angezeigt
                oPartialInvoiceCheckbox.change(function() {
                    var aInstallmentIds = oPartialInvoiceCheckbox.data('installment-ids');
                    if(oPartialInvoiceCheckbox.prop('checked')) {
                        oPaymentConditionSelect.children().prop('disabled', true);
                        aInstallmentIds.forEach(function(iId) {
                            oPaymentConditionSelect.children('[value=' + iId +']').prop('disabled', false);
                        });
                        if(oPaymentConditionSelect.val() === null) {
                            // Gesperrte Zahlungsbedingung ändern auf erste verfügbare
                            oPaymentConditionSelect.children(':enabled:first').prop('selected', true);
                            oPaymentConditionSelect.effect('highlight');
                        }
                    } else {
                        oPaymentConditionSelect.children().prop('disabled', false);
                    }
                });

                this.aCurrentAmount = [];

                if(oTemplate && oLanguage && $F(oLanguage) != "")
                {
                    // Observer setzen für Dokumente
                    this.setDocumentObserver();
                }

                // Editierbare Felder setzen
                this.showEditableFields(aData.id, aEditableFields, aEditableFieldData)

            } else if(sAction == 'transfer_provider') {
                this.setProviderObserver(aData);
            } else if(sAction=='openProgressReport') {
                this.initProgressReportObserver();
            } else if(sAction=='invoice') {
                // Rechnungsübersicht wird geöffnet
                this.calculateDocumentHistoryOverview(aData.id);
            } else if(sAction=='camera') {
                // Rechnungsübersicht wird geöffnet
                this.initCamera(aData);
            }

        } else if(sTask == 'writeMotherTongue') {
            this.reloadMotherTongue(aData);
        } else if(sTask == 'writeKorrespondenceTongue') {
            this.reloadKorrespondenceTongue(aData);
        } else if(sTask == 'reloadPositionsTable') {
            if(aData.close_dialog_id) {
                this.closeDialog(aData.close_dialog_id);
            }

            this.reloadPositionsTableCallback(aData);

        } else if(sTask == 'reloadTemplateLanguageSelect') {
            this.reloadTemplateLanguageSelectCallback(aData);
        } else if(sTask == 'writeCalculateCourseUntil') {
            this.writeUntilDate(aData);
            this.getPeriodData(aData, 'course');
        } else if(sTask == 'writeCalculateAccUntil'){
            this.writeUntilDate(aData);
            this.getPeriodData(aData, 'transfer');
        } else if(sTask == 'writeCalculateUntil'){
            this.writeUntilDate(aData);
        } else if(sTask == 'writeCalculateHolidayUntil'){
            var oInputUntil = this.writeUntilDate(aData);
            //Selectfelder nachladen
            this.setHolidayId(oInputUntil);
            this.reloadInquiryHolidaySelectFields(this.currendHolidayId);
        } else if(sTask == 'closeDocument'){

            // Dialog schließen
            // DOCUMENT_0 damit nach speichern einer neuen rechnung der dialog ebenfalls zu geht
            this.closeDialog('DOCUMENT_0');
            this.closeDialog(aData.id);

            if(aData.document_type != 'additional_document'){
                aData.document_type = 'invoice';
            }

            // Rechnungshistorie neu laden (da noch keine neue GUI)
            var aId = aData.id.split('_');

            //History aktualisieren
            this.updateHistoryHtml(aId[1],aData.parent_gui,aData.history_html);

            // TODO Update icons on invoices dialog???
            // this.requestDialog('&task=updateIcons', aData.id);

        } else if(sTask == 'resultRoomSharingCustomers') {

            // Ergebnisliste
            var oResultList = $('save['+this.hash+']['+aData.id+'][room_sharing_search_results_list]');
            oResultList.update('');
            // Auswahlliste
            var oSelectList = $('save['+this.hash+']['+aData.id+'][room_sharing_list_list]');

            oResultList.up().show();

            if(aData.searchResult.length == 0) {
                oResultList.insert({
                    top: new Element('div', {
                        className: 'p-2'
                    }).update(aDataOriginal.error.join(', '))
                });
            }

            var aCurrentElements = oSelectList.childElements();//

            $A(aData.searchResult).each( function ( oItem, iIndex ) {

                var bContinue = false;

                aCurrentElements.each(function(oDiv){
                    if(oDiv.id == 'matchingCustomer_'+oItem.id){
                        bContinue = true;
                    }
                });

                if(!bContinue){
                    var bChecked = false;
                    this.createCustomerDivForMatching(oResultList, oSelectList, oItem, bChecked);
                }

            }.bind(this));

            // Runterscrollen, damit man das DIV sieht
            var oMatchingTabContainer = $j('.GUITabBodyActive.tab_matching');
            oMatchingTabContainer.scrollTop(oMatchingTabContainer.prop("scrollHeight"));

        } else if (sTask == 'showHubspotContacts') {

            var container = document.getElementById(aData.container_id);

            // Einblenden des Ergebniss Divs
            var oResultList = $j(container).find('.customer_identification_entries');
            if(oResultList) {
                oResultList.parent().show();
                oResultList.html('');

                if(aData.searchResult !== null) {

                    oResultList.append('<div class="flex flex-col gap-1 mt-1 text-sm found-customer-list"></div>');

                    $j(container).find('.customer_box_result').text(aData.searchResultCount);

                    aData.searchResult.each(function(properties) {

                        var sCustomer = '';

                        if(properties.company) {
                            sCustomer += '<span class="font-semibold">'+properties.company+'</span> ';
                        }

                        if(
                            properties.lastname &&
                            properties.firstname
                        ) {
                            sCustomer += '<span class="font-semibold">'+properties.lastname+', '+properties.firstname+'</span> ';
                        } else if (properties.lastname) {
                            sCustomer += '<span class="font-semibold">'+properties.lastname+'</span> ';
                        } else if (properties.firstname) {
                            sCustomer += '<span class="font-semibold">'+properties.firstname+'</span> ';
                        }

                        if(properties.gender) {
                            sCustomer += properties.gender;
                        }

                        if(properties.date_of_birth) {
                            sCustomer += '<span class="text-muted pull-right">'+properties.date_of_birth+'</span>';
                        }

                        if(properties.email) {
                            sCustomer += '<br>' + properties.email;
                        }

                        $j(container).find('.found-customer-list')
                            .append(
                                '<a href="javascript:void(0);" class="p-1 text-gray-700 rounded bg-gray-50 hover:bg-gray-100/50 hover:text-gray-900" id="foundContact_'+properties.id+'">'
                                +sCustomer+
                                '</a>'
                            );
                        // link setzen
                        Event.observe($('foundContact_'+properties.id), 'click', function(container) {
                            $j(container).find('.customer_identification_entries a').css('color', 'inherit');
                            $j(container).find('#foundContact_' + properties.id).css('color', 'red');
                            this.fillUserData(aData, properties, container);
                        }.bind(this, container));

                    }.bind(this));
                } else {
                    oResultList.parent().hide();
                }

                $j(container).find('.loading').hide();
            }

        } else if (sTask == 'resultSearchForSomeUser') {

            var container = document.getElementById(aData.container_id);

            // Einblenden des Ergebniss Divs
            var oResultList = $j(container).find('.customer_identification_entries');
            if(oResultList) {
                oResultList.parent().show();
                oResultList.html('');

                if(aData.searchResult.length > 0) {

                    oResultList.append('<div class="flex flex-col gap-1 mt-1 text-sm found-customer-list"></div>');

                    $j(container).find('.customer_box_result').text(aData.searchResultCount);

                    aData.searchResult.each(function(aCustomer) {

                        var sCustomer = '';
                        if(aCustomer.customerNumber) {
                            // Anfragenkontakte müssen keine Nummer haben
                            sCustomer += aCustomer.customerNumber + ' ';
                        }

                        if(aCustomer.company) {
                            sCustomer += '<span class="font-semibold">'+aCustomer.company+'</span> ';
                        }

                        if(
                            aCustomer.lastname &&
                            aCustomer.firstname
                        ) {
                            sCustomer += '<span class="font-semibold">'+aCustomer.lastname+', '+aCustomer.firstname+'</span> ';
                        }

                        if(aCustomer.gender == 1) {
                            sCustomer += '<i class="fas fa-mars" aria-hidden="true"></i>';
                        } else if(aCustomer.gender == 2) {
                            sCustomer += '<i class="fas fa-venus" aria-hidden="true"></i>';
                        } else if(aCustomer.gender == 3) {
                            sCustomer += '<i class="fas fa-transgender-alt" aria-hidden="true"></i>';
                        }

                        if(aCustomer.birthday) {
                            sCustomer += '<span class="text-muted pull-right">'+aCustomer.birthday+'</span>';
                        }

                        if(aCustomer.email) {
                            sCustomer += '<br>' + aCustomer.email;
                        }

                        $j(container).find('.found-customer-list')
                            .append(
                                '<a href="javascript:void(0);" class="p-1 text-gray-700 rounded bg-gray-50 hover:bg-gray-100/50 hover:text-gray-900" id="foundCustomer_'+aCustomer.id+'">'
                                +sCustomer+
                                '</a>'
                            );

                        // link setzen
                        Event.observe($('foundCustomer_'+aCustomer.id), 'click', function(container) {
                            $j(container).find('.customer_identification_entries a').css('color', 'inherit');
                            $j(container).find('#foundCustomer_' + aCustomer.id).css('color', 'red');
                            this.fillUserData(aData, aCustomer, container);
                        }.bind(this, container));

                    }.bind(this));
                } else {
                    oResultList.parent().hide();
                }

                $j(container).find('.loading').hide();

            }

        } else if(sTask == 'resultCourseTransferData') {

            if(aData.returnData) {

                // Anzeigen im Accommodation Tab von wann bis wann Kurse gebucht sind
                // Wochentag bestimmen
                var bShowResult = true;
                if(
                    aData.returnData.first_i <= 0 ||
                    aData.returnData.last_i <= 0
                ){
                    bShowResult = false;
                }

                // Einzelbuchung
                $j('#dialog_'+aData.id+'_'+this.hash+' .accommodation_course_info').each(function(iIndex, oDiv) {
                    oDiv = $j(oDiv);
                    if(bShowResult) {
                        var sValue = '';
                        sValue = this.getTranslation('course_info') + ': ' + aData.returnData.first_weekday + ' ' + aData.returnData.first;
                        sValue += ' - ' + aData.returnData.last_weekday + ' ' + aData.returnData.last;
                        oDiv.find('h4').hide();
                        oDiv.find('div').html(sValue);
                        oDiv.show();
                    }else{
                        oDiv.hide();
                    }
                }.bind(this));

                var setAccommodationData = (aAccommodationData, guide) => {
                    var aInquiryAccommodationIds = aData.inquiryAccommodationIds;

                    iTempId = 0;

                    if(
                        aInquiryAccommodationIds &&
                        aInquiryAccommodationIds.length > 0
                    ){
                        var iTempId = aInquiryAccommodationIds[0];
                    }

                    // Wenn schon gespeichert wurde  nachfragen
                    var check = false;
                    /* Übernahme darf NUR bei komplett neuen Buchungen passieren laut Mark
					if(
						aData.id != 'ID_0' &&
						aData.id != 'GROUP_0' &&
						aInquiryAccommodationIds.length < 2
					){

						var sQuestion = this.getTranslation('accommodationquestion');
						sQuestion += ' '+aAccommodationData.first_weekday + ' ' + aAccommodationData.first;
						sQuestion += ' - '+aAccommodationData.last_weekday + ' ' + aAccommodationData.last+' ?\n';
						check = confirm(sQuestion);
					}
					*/

                    if(
                        aData.id == 'ID_0' ||
                        aData.id == 'GROUP_0' ||
                        iTempId == 0
                    ){
                        check = true;
                    }

                    if(check){

                        // inquiryId
                        var aId = aData.id.split('_');

                        var iInquiryId = aId[1];

                        var sPrefix = (guide) ? 'accommodation_guide' : 'accommodation';

                        aAccommodationData.forEach((entry) => {
                            var oWeek = $(sPrefix+'['+iInquiryId+']['+entry.index+'][weeks]');
                            var oFrom= $(sPrefix+'['+iInquiryId+']['+entry.index+'][from]');
                            var oUntil = $(sPrefix+'['+iInquiryId+']['+entry.index+'][until]');

                            if(
                                oWeek &&
                                oFrom &&
                                oUntil
                            ) {
                                oWeek.value = entry.dates.weeks_i;
                                this.updateCalendarValue(oFrom, entry.dates.first);
                                this.updateCalendarValue(oUntil, entry.dates.last);

                                // TODO Warum passiert das nicht in einem Request?
                                this.getPeriodData(aData, 'transfer');
                            }
                        })

                    }
                };


                // Automatisch errechnete Daten Für Unterkünfte anhand des Kursdatums
                if (aData.accommodationData) {
                    setAccommodationData(aData.accommodationData)
                }

                if (aData.accommodationGuideData) {
                    setAccommodationData(aData.accommodationGuideData, true)
                }

            }

        } else if(sTask == 'resultAccommodationData') {

            var oField = $(aData.field);

            if (oField && aData.returnData) {
                var oFromTime = $(aData.field.replace('accommodation_id', 'from_time'));
                var oUntilTime = $(aData.field.replace('accommodation_id', 'until_time'));

                if(oFromTime && oUntilTime) {
                    oFromTime.value = aData.returnData.time_from;
                    oUntilTime.value = aData.returnData.time_until;

                    var oWeek = $(aData.field.replace('accommodation_id', 'weeks'));
                    var oFrom = $(aData.field.replace('accommodation_id', 'from'));
                    var oUntil = $(aData.field.replace('accommodation_id', 'until'));

                    if (
                        oWeek && oFrom && oUntil &&
                        aData.returnData.first &&
                        aData.returnData.first.length > 0
                    ) {
                        oWeek.value = aData.returnData.weeks_i;
                        this.updateCalendarValue(oFrom, aData.returnData.first);
                        this.updateCalendarValue(oUntil, aData.returnData.last);

                        // TODO Warum passiert das nicht in einem Request?
                        this.getPeriodData(aData, 'transfer');
                    }
                }
            }

        } else if(sTask == 'resultAccommodationTransferData'){
            // Wenn es kein neuer Kunde gibt abfragen ob überschrieben werden soll

            var sFirst = aData.returnData.first;
            var sLast = aData.returnData.last;

            var oArrival = null;
            var oDeparture = null;

            // Einzelbuchung
            $$('#dialog_'+aData.id+'_'+this.hash+' .input_arrival_date').each(function(oInput){
                oArrival = oInput;
            }.bind(this));

            $$('#dialog_'+aData.id+'_'+this.hash+' .input_departure_date').each(function(oInput){
                oDeparture = oInput;
            }.bind(this));

            // Gruppenbuchung
            var oInput = $('save['+this.hash+']['+aData.id+'][arrival][kg]');
            if(oInput){
                oArrival = oInput
            }

            var oInput = $('save['+this.hash+']['+aData.id+'][departure][kg]');
            if(oInput){
                oDeparture = oInput
            }

            var check = false;

            if(
                aData.transfer_question == 1 &&
                aData.id != 'ID_0' &&
                aData.id != 'GROUP_0' &&
                sFirst != '' &&
                sLast != '' &&
                oArrival  &&
                oDeparture &&
                (
                    oArrival.value != '' ||
                    oDeparture.value != ''
                ) &&
                (
                    oArrival.value != sFirst ||
                    oDeparture.value != sLast
                )
            ){
                check = confirm(this.getTranslation('transferquestion')+' '+sFirst+' - '+sLast+' ?\n');
            }else if(
                (
                    aData.id == 'ID_0' ||
                    aData.id == 'GROUP_0'
                ) &&
                sFirst != '' &&
                sLast != '' &&
                oArrival  &&
                oDeparture
            ){
                check = true;
            }

            if(check){
                this.updateCalendarValue(oArrival, sFirst);
                this.updateCalendarValue(oDeparture, sLast);
            }

        } else if (sTask == 'writeInquiryHolidaySelectFields'){

            var aInquiry			    = aData.vars;
            var aCourses			    = aData.inquiry_courses;
            var aAccommodations		    = aData.inquiry_accommodations;

            var sHolidayCoursesId        = 'holidays['+aInquiry['idHoliday']+'][course_ids][]';
            var sHolidayAccommodationsId = 'holidays['+aInquiry['idHoliday']+'][accommodation_ids][]';

            // refresh course select field
            if ( $(sHolidayCoursesId) && ($(sHolidayCoursesId).tagName == 'SELECT') ) {
                var oSelect = $(sHolidayCoursesId);
                this.writeMultipleSelect(oSelect, aCourses);
            }

            // refresh accommodation select field
            if ( $(sHolidayAccommodationsId) && ($(sHolidayAccommodationsId).tagName == 'SELECT')) {
                var oSelect = $(sHolidayAccommodationsId);
                this.writeMultipleSelect(oSelect, aAccommodations);
            }

            if(aData.move_following_courses) {
                $('holidays['+aInquiry['idHoliday']+'][move_following_courses]').checked = aData.move_following_courses;
            }

            this.enableDisableFollowingCourses ( aInquiry['idHoliday'] );
            this.enableDisableFollowingAccommodations ( aInquiry['idHoliday'] );

        } else if(sTask == 'writeHolidayFollowingCourses'){

            var aCourses   = aData.courses;
            var mHolidayId = aData.vars.idHoliday;

            var sHolidayFollowingCoursesId       = 'holidays['+mHolidayId+'][following_courses][]';

            // refresh course select field
            if ( $(sHolidayFollowingCoursesId) && ($(sHolidayFollowingCoursesId).tagName == 'SELECT') ) {
                var oSelect = $(sHolidayFollowingCoursesId);
                this.writeMultipleSelect(oSelect, aCourses);
            }

        } else if(sTask == 'writeDeleteHolidaySet'){

            if(aError === null || !aError.length){
                var aId = aData.id.split('_');

                // Dialog speichern
                aData.save_id = aId[1];
                aData.id = aData.id;
                aData.task = 'saveDialog';
                aData.action = 'edit';
                var sAdditionalParam = '&dontSaveCourseAndAcco=1';

                this.prepareSaveDialog(aData, false, false, sAdditionalParam);
            }else{
                this.displayErrors(aError, aData.id, false, 1);
                //this.displayErrors(aError);
            }

        } else if(sTask == 'updateCommissionPositions') {

            $j.each(aData.positions, function(sPositionKey, fAmount) {
                var oInput = $('amount_provision_'+sPositionKey);
                if(oInput){
                    oInput.updateValue(this.thebingNumberFormat(fAmount));
                    this.recalculatePosition(sPositionKey);
                }
            }.bind(this));

        } else if(sTask == 'markAsCanceledConfirm'){
            this.markAsCanceledConfirm(aData);

        } else if(sTask == 'writeCourseInfo') {

            this.setCourseInfo(aDataOriginal);

        } else if(sTask == 'reloadPaymentTermRows') {

            this.reloadPaymentTermRows(aData);

        }

        if (
            aDataOriginal &&
            aDataOriginal.history_html
        ) {
            this.updateHistoryHtml(aDataOriginal.parent_id,aDataOriginal.parent_hash,aDataOriginal.history_html);
        }

    },

    setTransferPeriod: function (sDialogId, sArrivalDate, sDepartureDate, iTransferQuestion) {
        var oArrival = null;
        var oDeparture = null;

        // Einzelbuchung
        $$('#dialog_'+sDialogId+'_'+this.hash+' .input_arrival_date').each(function(oInput){
            oArrival = oInput;
        }.bind(this));

        $$('#dialog_'+sDialogId+'_'+this.hash+' .input_departure_date').each(function(oInput){
            oDeparture = oInput;
        }.bind(this));

        // Gruppenbuchung
        var oInput = $('save['+this.hash+']['+sDialogId+'][arrival][kg]');
        if(oInput){
            oArrival = oInput
        }

        var oInput = $('save['+this.hash+']['+sDialogId+'][departure][kg]');
        if(oInput){
            oDeparture = oInput
        }

        var check = false;

        if(
            iTransferQuestion == 1 &&
            sDialogId != 'ID_0' &&
            sDialogId != 'GROUP_0' &&
            sArrivalDate != '' &&
            sDepartureDate != '' &&
            oArrival  &&
            oDeparture &&
            (
                oArrival.value != '' ||
                oDeparture.value != ''
            ) &&
            (
                oArrival.value != sArrivalDate ||
                oDeparture.value != sDepartureDate
            )
        ){
            check = confirm(this.getTranslation('transferquestion')+' '+sArrivalDate+' - '+sDepartureDate+' ?\n');
        }else if(
            (
                sDialogId == 'ID_0' ||
                sDialogId == 'GROUP_0'
            ) &&
            sArrivalDate != '' &&
            sDepartureDate != '' &&
            oArrival  &&
            oDeparture
        ){
            check = true;
        }

        if(check){
            this.updateCalendarValue(oArrival, sArrivalDate);
            this.updateCalendarValue(oDeparture, sDepartureDate);
        }
    },

    updateHistoryHtml : function(iId,sParentHash,sHistoryHtml){
        if(
            sHistoryHtml &&
            sParentHash &&
            $('tabBody_1_DOCUMENTS_LIST_'+iId+'_'+sParentHash)
        ){
            $('tabBody_1_DOCUMENTS_LIST_'+iId+'_'+sParentHash).down('div').update(sHistoryHtml);
        }
    },

    // Gibt eine Abfrage ob wirklich storniert werden soll
    markAsCanceledConfirm : function(aData){

        var sQuestion = '';
        sQuestion += this.getTranslation('confirmCanceled');

        if(
            aData.proforma_numbers &&
            aData.proforma_numbers.length > 0
        ){
            sQuestion += '\n';
            sQuestion += '\n';
            sQuestion += this.getTranslation('confirmCanceledProforma') + ': ';
            sQuestion += '\n';
            aData.proforma_numbers.each(function(value){
                sQuestion += '- ' + value;
                sQuestion += '\n';
            });
        }

        if(confirm(sQuestion)){
            var sParam = '';
            sParam += '&task=markAsCanceled&confirmedCancelation=1';
            if(aData.document_id){
                sParam += '&document_id='+aData.document_id;
            }
            this.request(sParam);
        }
    },

    // Suchen ob es den Kunden schonmal gab
    prepareCheckForSameUser : function(aData, bSearchField, container) {

        if(!container) {
            container = $j('.customer_identification_results_field').get(0);
        }

        if(!container.id) {
            container.id = 'customer-search-container-'+Math.floor(Math.random()*99999);
        }

        if(this.searchForSameUser){
            clearTimeout(this.searchForSameUser);
        }

        this.searchForSameUser = setTimeout(this.checkForSameUser.bind(this), 800, aData, bSearchField, container);

    },

    changeSchoolCallback : function(oSchool, aData){

        this.aDataOfInquiry = aData.values;

        this.bSkipConfirm = true;

        if(
            this.iSelectedSchoolId == 0 ||
            confirm(this.getTranslation('confirm_change_school'))
        ) {

            $$('.course_block_remover').each(function(oImg){
                this._fireEvent('click', oImg);
            }.bind(this));

            $$('.accommodation_block_remover').each(function(oImg){
                this._fireEvent('click', oImg);
            }.bind(this));

            $$('.transfer_block_remover').each(function(oImg){
                this._fireEvent('click', oImg);
            }.bind(this));

            $$('.course_guide_block_remover').each(function(oImg){
                this._fireEvent('click', oImg);
            }.bind(this));

            $$('.accommodation_guide_block_remover').each(function(oImg){
                this._fireEvent('click', oImg);
            }.bind(this));

            this.loadNewSchoolData(oSchool);
            this.bSkipConfirm = false;
            return true;
        }

        this.bSkipConfirm = false;
        return false;


    },

    loadNewSchoolData : function(oSchool, bSkipDynamicContent) {

        this.iSelectedSchoolId = $F(oSchool);

        var sParam = '';
        sParam += '&task=loadNewSchoolData&school_id='+$F(oSchool);
        if(bSkipDynamicContent) {
            sParam += '&bSkipDynamicContent=1';
        }

        this.request(sParam);
    },

    loadNewSchoolDataCallback : function(aData){

        var bSkipDynamicContent = aData['bSkipDynamicContent'];
        var bReset = (bSkipDynamicContent == 0);

        if (bReset) {
            this.courseData = aData.course_data ?? [];
        }

        // Fixe Felder

        var oAgency = $('save['+this.hash+']['+aData.id+'][agency_id][ki]');
        if(!oAgency) {
            var oAgency = $('save['+this.hash+']['+aData.id+'][agency_id][kg]');
        }

        var oCurrency = $('save['+this.hash+']['+aData.id+'][currency_id][ki]');

        if(!oCurrency) {
            var oCurrency = $('save['+this.hash+']['+aData.id+'][currency_id][kg]');
        }

        var oStatus = $('save['+this.hash+']['+aData.id+'][status_id][ki]');
        var oReferer = $('save['+this.hash+']['+aData.id+'][referer_id][ki]');
        var oVisum = $('save['+this.hash+']['+aData.id+'][status][ts_ijv]');

        var oLang = $('save['+this.hash+']['+aData.id+'][corresponding_language][cdb1]');
        if(!oLang) {
            var oLang = $('save['+this.hash+']['+aData.id+'][correspondence_id][kg]');
        }

        var iCurrency	= 0;
        var iAgency = 0;
        var iStatus		= 0;
        var iReferer	= 0;
        var iVisum		= 0;
        var iLang		= 0;

        this.aDataOfInquiry.each(function(aValue){
            if(aValue.db_column == 'currency_id'){
                iCurrency = aValue.value;
            }
            if(aValue.db_column == 'agency_id'){
                iAgency = aValue.value;
            }
            if(aValue.db_column == 'status_id'){
                iStatus = aValue.value;
            }
            if(aValue.db_column == 'referer_select' || aValue.db_column == 'referer_id'){
                iReferer = aValue.value;
            }
            if(aValue.db_column == 'visum_status' || aValue.db_column == 'status'){
                iVisum = aValue.value;
            }
            if(
                aValue.db_column == 'corresponding_language' ||
                (iLang == 0 && aValue.db_column == 'correspondence_id')
            ){
                iLang = aValue.value;
            }
        });

        if(oAgency){
            this.writeSelectValuesForSchoolChange(oAgency, aData.agencies, iAgency);
        }

        if(oCurrency){
            this.writeSelectValuesForSchoolChange(oCurrency, aData.currency, iCurrency);
        }

        if(oStatus){
            this.writeSelectValuesForSchoolChange(oStatus, aData.status, iStatus);
        }

        if(oReferer){
            this.writeSelectValuesForSchoolChange(oReferer, aData.referer, iReferer);
        }

        if(oVisum){
            this.writeSelectValuesForSchoolChange(oVisum, aData.visum, iVisum);
            this._fireEvent('change', oVisum);
        }

        if(oLang){
            this.writeSelectValuesForSchoolChange(oLang, aData.school_lang, iLang);
        }

        // Kurse

        if(bReset) {

            $$('.course_category_select').each(function(oCourseCategory){
                this.writeSelectValuesForSchoolChange(oCourseCategory, aData.course_categories, $F(oCourseCategory));
            }.bind(this));

            $$('.courseSelect').each(function(oCourse){
                this.writeSelectValuesForSchoolChange(oCourse, aData.courses, $F(oCourse));
            }.bind(this));

            $$('.course_level_select').each(function(oCourseLevels){
                this.writeSelectValuesForSchoolChange(oCourseLevels, aData.courses_levels, $F(oCourseLevels));
            }.bind(this));

            //Aktivitaeten
            $$('.activity_ids').each(function(oActivity){
                this.writeSelectValuesForSchoolChange(oActivity, aData.activities, $F(oActivity));
            }.bind(this));

            // Unterkunfte
            $$('.accommodationSelect').each(function(oAccommodation){
                this.writeSelectValuesForSchoolChange(oAccommodation, aData.accommodation, $F(oAccommodation));
            }.bind(this));

            $$('.RoomtypeSelect').each(function(oRoomtype){
                this.writeSelectValuesForSchoolChange(oRoomtype, aData.roomtypes, $F(oRoomtype), true);
            }.bind(this));

            $$('.MealtypeSelect').each(function(oMeal){
                this.writeSelectValuesForSchoolChange(oMeal, aData.meals, $F(oMeal), true);
            }.bind(this));

            $$('.airports_arrival').each(function(oAirport){
                this.writeSelectValuesForSchoolChange(oAirport, aData.airports_arrival, $F(oAirport));
            }.bind(this));

            $$('.airports_departure').each(function(oAirport){
                this.writeSelectValuesForSchoolChange(oAirport, aData.airports_departure, $F(oAirport));
            }.bind(this));

            $$('.airports_individual').each(function(oAirport){
                this.writeSelectValuesForSchoolChange(oAirport, aData.airports_individual, $F(oAirport));
            }.bind(this));

            this.setCoursesObserver(aData)
            this.setAccommodationObserver(aData);

        }

        // Flex Uploads
        this.updateFlexUploadFields(aData.school_id, bReset);

    },

    // TODO Es wäre schön, wenn das so wie setDialogSaveFieldValues() laufen würde und nicht irgendetwas komplett eigenes
    writeSelectValuesForSchoolChange : function(oElement, aData, iSelected, bSetDisabledOptions){

        oElement.update();

        if(aData.length === undefined || aData.length > 0) {

            if(aData.length === undefined) { // Wenn nicht von 0 korrekt durchnummeriert
                aData = $H(aData);
            } else { // Wenn es doch mal ist...
                var aTemp = [];
                var i = 0;
                aData.each(function(mValue, mKey) {
                    aTemp[i] = [];
                    if(mValue instanceof Array) { // mValue.length klappt hier nicht, weil ein String z.B. das Attribut length hat
                        // Values sind selber wieder Arrays mit Key und Value als jeweils ein Element ...
                        aTemp[i][0] = mValue[0];
                        aTemp[i][1] = mValue[1];
                    } else {
                        // ... oder das Ganze ist eine Key/Value Liste
                        aTemp[i][0] = mKey;
                        aTemp[i][1] = mValue;
                    }
                    i++;
                });
                aData = aTemp;
            }

            var bValueFound = false;
            aData.each(function(aItem) {
                var oOption = new Element('option');
                oOption.value = aItem[0];
                oOption.update(aItem[1]);
                if(iSelected == aItem[0]) {
                    oOption.selected = true;
                    bValueFound = true;
                }
                if(
                    bSetDisabledOptions &&
                    aItem[0] != 0
                ) {
                    oOption.disabled = true;
                }
                oElement.appendChild(oOption);
            });

            if(
                iSelected && // Einfache Prüfung, damit hier 0 / null nicht reinkommen
                !bValueFound
            ) {
                this.addUnknownOption(oElement, iSelected);
            }

        }

    },

    calculatePaymentTermAmounts: function(iDataId) {

        var oPaymentTermRows = $j('tr.paymentterm_row');

        var bHasInstalments = false;
        oPaymentTermRows.each(function(iKey, oTr) {
            oTr = $j(oTr);
            var oTypeHidden = oTr.find('input[name*=type]');

            if(oTypeHidden.val() === 'installment') {
                bHasInstalments = true;
            }
        });

        // Bei Ratenzahlung, Eintrage per AJAX holen, bei einfachen Zahlungsbedingungen direkt errechnen
        if(bHasInstalments) {

            var sParam = '&task=request&action=paymentTermRows&';
            sParam += $('dialog_form_'+this.sCurrentDialogId+'_'+this.hash).serialize();

            this.requestBackground(sParam);
            return;
        }

        var fAmount;
        var fTotalAmount = this.getTotalAmount(iDataId);
        var fTotalAmountFinal = fTotalAmount;
        var oPaymentConditionSelect = this.getDialogSaveField('payment_condition_select');

        var sTotalSuffix = '';
        if(this.iVatMode === 1) {
            sTotalSuffix = '_gross';
        } else if(this.iVatMode === 2) {
            sTotalSuffix = '_net';
        }

        oPaymentTermRows.each(function(iKey, oTr) {
            oTr = $j(oTr);
            var oAmountInput = oTr.find('input[name*=amount]');
            var oTypeHidden = oTr.find('input[name*=type]');

            if(oTypeHidden.val() === 'deposit') {

                var aSettings = oAmountInput.data('setting') || []; // data-setting
                fAmount = 0;

                // Keine Bezahlbedingung ausgewählt: Manuelle Eingabe, daher immer vorhandene Beträge nehmen
                if(oPaymentConditionSelect.val() === '0') {
                    aSettings = [{
                        setting: 'amount',
                        amount: oAmountInput.val().parseNumber()
                    }];
                }

                aSettings.forEach(function(oSetting) {

                    // Gleiche Implementierung in Ext_TS_Document_PaymentCondition::getDepositPaymentRow()
                    if(oSetting.setting === 'amount') {
                        fAmount += oSetting.amount;
                    } else if(oSetting.setting === 'percent') {

                        this.aPositionCache.forEach(function(iPositionKey) {
                            var sType = $j('#type_' + iPositionKey).val().replace(/_/, '');
                            var iTypeId = parseInt($j('#type_id_' + iPositionKey).val());
                            var iTaxCategory = parseInt($j('#tax_category_' + iPositionKey).val());

                            if(
                                oSetting.type === 'all' || (
                                    oSetting.type === sType && (
                                        oSetting.type_id === iTypeId ||
                                        oSetting.type_id === 0
                                    )
                                )
                            ) {
                                var fPositionAmount = $j('#amount_total' + sTotalSuffix + '_' + iPositionKey).text().parseNumber();
                                if (this.iVatMode === 2) {
                                    // Externe Steuern müssen separat drauf gerechnet werden, da es dafür keine Zelle gibt
                                    // Überall werden Objekte im Thebing-JS verwendet, aber bei this.vatRates ist es plötzlich ein Array mit Arrays
                                    var fTaxRate = parseFloat(this.vatRates.filter(r => parseInt(r[0]) === iTaxCategory).first()?.[1]) || 0;
                                    fPositionAmount += fPositionAmount * (fTaxRate / 100);
                                }
                                fAmount += fPositionAmount / 100 * parseFloat(oSetting.amount);
                            }
                        }.bind(this));

                    }

                }.bind(this));

                oAmountInput.val(this.thebingNumberFormat(fAmount.toFixed(2)));

                // fTotalAmount verändern, damit Restzahlung mit Gesamtbetrag minus Anzahlungsbeträge rechnet
                fTotalAmount -= fAmount;
                fTotalAmountFinal -= fAmount;

            } else if(oTypeHidden.val() === 'installment') {

                var oSetting = oAmountInput.data('setting') || []; // data-setting
                fAmount = fTotalAmount * oSetting.percent;

                oAmountInput.val(this.thebingNumberFormat(fAmount.toFixed(2)));
                fTotalAmountFinal -= fAmount;

            } else {
                oAmountInput.val(this.thebingNumberFormat(fTotalAmountFinal.toFixed(2)));
            }
        }.bind(this));

    },

    reloadPaymentTermRows: function(aData) {

        var oPaymentTermRows = $j('tr.paymentterm_row');

        oPaymentTermRows.each(function(iKey, oTr) {
            oTr = $j(oTr);
            var oAmountInput = oTr.find('input[name*=amount]');
            if(
                aData[iKey] &&
                aData[iKey].fAmount
            ) {
                oAmountInput.val(this.thebingNumberFormat(aData[iKey].fAmount.toFixed(2)));
            } else {
                oAmountInput.val('ERR');
            }
        }.bind(this));

    },

    disableCalendarInput : function(oInput){
        if(
            oInput.previous('div') &&
            oInput.previous('div').hasClassName('GUIDialogRowWeekdayDiv') &&
            !oInput.previous('div').hasClassName('readonly')
        ){
            // Wochentag ausblenden
            oInput.previous('div').addClassName('readonly');
        }

        if(
            oInput.next('img') &&
            oInput.next('img').hasClassName('calendar_img')
        ){
            oInput.next('img').hide();
        }
    },

    // Setzt die Observer für Provider bei der TransferListe
    setProviderObserver : function(aData){

        var iProviderId		= aData.data.provider_id;
        var iDriverId		= aData.data.driver_id;

        // Fahrer verstecken
        if($('save['+this.hash+']['+aData.id+'][transfer][driver]')){
            var oSelect = $('save['+this.hash+']['+aData.id+'][transfer][driver]');
            oSelect.up('.GUIDialogRow').hide();
        }

        // Observer auf Anbieter um Driver zu wählen (wenn nicht Unterkunft)
        if($('save['+this.hash+']['+aData.id+'][transfer][provider]')){
            var oSelect = $('save['+this.hash+']['+aData.id+'][transfer][provider]');

            if(iProviderId != 0){
                oSelect.value = iProviderId;
                this.loadDriverSelect(aData, oSelect, iDriverId);
            }

            oSelect.stopObserving('change');
            Event.observe(oSelect, 'change', function(){
                this.loadDriverSelect(aData, oSelect, 0);
            }.bind(this));
        }

    },

    // Läd das Driver-Select nach bei Transfer
    loadDriverSelect : function (aData, oSelectProvider, iSelectedDriverId){

        if($('save['+this.hash+']['+aData.id+'][transfer][driver]')){
            var oSelectDriver = $('save['+this.hash+']['+aData.id+'][transfer][driver]');
            oSelectDriver.update();

            var aDrivers = aData.data.provider;

            var bHideSelect = true;
            $H(aDrivers).each(function(oArray){

                if(
                    oSelectProvider.value > 0 &&
                    oSelectProvider.value == oArray.key
                ){
                    // Fahrer zum Provider auslesen
                    $H(oArray.value.driver).each(function(oDriver){

                        var iDriverId = oDriver.key;
                        var sDrivername = oDriver.value.name;

                        // Fahrer vorselektiert
                        var selectDriver = false;
                        if(
                            iDriverId > 0 &&
                            oDriver.key == iSelectedDriverId
                        ){
                            selectDriver = true;
                        }
                        oSelectDriver.insert({
                            bottom: new Element('option', {
                                value: iDriverId,
                                selected : selectDriver
                            }).update(sDrivername)
                        });
                        bHideSelect = false;
                    });

                }
            });

            if(bHideSelect){
                oSelectDriver.up('.GUIDialogRow').hide();
            }else{
                oSelectDriver.up('.GUIDialogRow').show();
            }
        }
    },

    setPositionObserver : function() {

        var sPrefix = '#content_DOCUMENT_POSITIONS_0_'+this.hash;

        $$(sPrefix+' textarea').each(
            function(oElement) {
                this.resizeTextarea(oElement, 21);
                Event.observe(oElement, 'keyup', function(oEvent) {
                    this.resizeTextarea(oElement, 21);
                }.bind(this));
            }.bind(this)
        );

        $j(sPrefix+' .click').click(function(oEvent) {
            this.waitForInputEventStudent(oEvent);
        }.bind(this));

        $j(sPrefix+' .change').change(function(oEvent) {
            this.waitForInputEventStudent(oEvent);
        }.bind(this));

        $j(sPrefix+' .keyup').keyup(function(oEvent) {
            this.waitForInputEventStudent(oEvent);
        }.bind(this));

    },

    /*
     * Setzt die Observer für die Dokumente/Rechnungen etc.
     **/
    setDocumentObserver : function(sPrefix) {

        if(!sPrefix) {
            sPrefix = '.DocumentPositionDiv';

            // Button für das Hinzufügen von neuen Positionen
            if($('add_position_button')){
                Event.observe($('add_position_button'), 'click', this.writeExtraposition.bind(this));
            }

            // Kalender
            $$(sPrefix+' .calendar_input').each(function(oInput){
                if(
                    oInput.next('.calendar_img') &&
                    oInput.next('.calendar_img').id
                ){
                    this.prepareCalendar(oInput, oInput.next('.calendar_img'));
                }
            }.bind(this));
            this.executeCalendars();

            $$('.docment_date_field').each(function(oElement){
                oElement.observe('keyup', this.waitForInputEventStudent.bind(this));
            }.bind(this));
        }

        // Set Drag n Drop
        if($('position_container')) {

            $j('#position_container').sortable({
                handle: '.sort_handle',
                items: 'tr',
                update: function( event, ui ) {
                    var sortedIDs = $j('#position_container').sortable('toArray');
                    var iPosition = 1;
                    sortedIDs.forEach(function(sortedID){
                        itemID = sortedID.replace('position_row_', '');
                        if(!isNaN(itemID)) {
                            $('position_'+itemID).value = iPosition++;
                        }
                    });
                }
            });

        }

        this.setPaymentTermsEvents();

        this.updatePositionCache();

        this.recalculateSums();

        $$(sPrefix+' textarea').each(
            function(oElement) {
                this.resizeTextarea(oElement, 21);
                Event.observe(oElement, 'keyup', function(oEvent) {
                    this.resizeTextarea(oElement, 21);
                }.bind(this));
            }.bind(this)
        );

        $$(sPrefix+' .click').each(function(oElement){
            oElement.observe('click', this.waitForInputEventStudent.bind(this));
        }.bind(this));

        $$(sPrefix+' .change').each(function(oElement){
            oElement.observe('change', this.waitForInputEventStudent.bind(this));
        }.bind(this));

        $$(sPrefix+' .keyup').each(function(oElement){
            oElement.observe('keyup', this.waitForInputEventStudent.bind(this));
        }.bind(this));

        // Document positions tooltips
        $$(sPrefix+' .info').each(function(oElement){
            var sTooltipId = oElement.id;
            if(
                // this.aPositionsTooltips &&
                this.aPositionsTooltips[sTooltipId]
            ) {
                this.aTooltips[sTooltipId] = this.aPositionsTooltips[sTooltipId];
                Event.observe(oElement, 'mousemove', function(e) {
                    this.showTooltip(sTooltipId, e);
                }.bind(this));
                Event.observe(oElement, 'mouseout', function(e) {
                    this.hideTooltip(sTooltipId, e);
                }.bind(this));
            }
        }.bind(this));

        // Bei Teilrechnung Abrechnungszeitraum anzeigen und Felder sperren
        var oPartialInvoiceCheckbox = this.getDialogSaveField('partial_invoice');
        var oPaymentConditionSelect = this.getDialogSaveField('payment_condition_select');
        if(
            typeof this.aPositionsTooltips != 'undefined' &&
            'billing_period' in this.aPositionsTooltips
        ) {
            $j('.document_billing_period').text(this.aPositionsTooltips.billing_period);
            [oPartialInvoiceCheckbox, oPaymentConditionSelect].forEach(function(oElement) {
                oElement.prop('disabled', true);
                oElement.after($j('<input>', {
                    type: 'hidden',
                    name: oElement.attr('name'),
                    value: oElement.val()
                }));
            });
        } else {
            oPartialInvoiceCheckbox.closest('.GUIDialogRow').hide();
        }

        this.toggleDocumentOnPdfAllCheckbox();

    },

    setPaymentTermsEvents: function() {

        var oPaymentConditionSelect = this.getDialogSaveField('payment_condition_select');
        var oPaymentTermRows = $j('tr.paymentterm_row');

        var cCheckTermsEdit = function() {
            if(oPaymentConditionSelect.val() !== '0') {
                if(confirm(this.getTranslation('change_payment_conditions_question'))) {
                    oPaymentConditionSelect.val('0');
                    this.setPaymentTermsEvents();
                    return true;
                }
                return false;
            }
            return true;
        }.bind(this);

        oPaymentTermRows.each(function(iKey, oTr) {
            oTr = $j(oTr);

            // Wenn Wert gesetzt: Select deaktivieren und hidden generieren für gesetzten werden
            // Die Logik zum Sperren passiert später als die Generierung des Selects
            if(oTr.data('disable-payment-condition')) {
                // Nur einmal hinzufügen
                if(!$j('input[type=hidden][name=' + $j.escapeSelector(oPaymentConditionSelect.attr('name')) + ']').length) {
                    $j('<input>', {
                        type: 'hidden',
                        name: oPaymentConditionSelect.attr('name'),
                        value: oPaymentConditionSelect.val()
                    }).insertBefore(oPaymentConditionSelect);
                }
                oPaymentConditionSelect.prop('disabled', true);
                return;
            }

            var oTypeHidden = oTr.find('input[name*=type]');
            var oDateInput = oTr.find('input[name*=date]');
            var oAmountInput = oTr.find('input[name*=amount]');
            var oAdd = oTr.find('i.fa-plus-circle').off('click').hide();
            var oDelete = oTr.find('i.fa-minus-circle').off('click').hide();

            // Letztes oder vorletztes Element
            if(
                oPaymentTermRows.length === 1 ||
                iKey === oPaymentTermRows.length - 2
            ) {
                oAdd.show();
            }

            // Letztes Element (Restzahlung)
            if(iKey !== oPaymentTermRows.length - 1) {
                oDelete.show();
            }

            oAdd.click(function() {
                if(!cCheckTermsEdit()) {
                    return;
                }

                var oNewTr = oTr.clone();
                oNewTr.find('input').val('').css('cursor', '');
                oNewTr.find('td:first-child > span').text(this.getTranslation('deposit'));
                oNewTr.find('input[name*=type]').val('deposit'); // Manuell ist immer Anzahlung!
                this.prepareCalendar(oNewTr.find('input[name*=date]').get(0));

                if(oPaymentTermRows.length === 1) {
                    oTr.before(oNewTr);
                } else {
                    oTr.after(oNewTr);
                }

                this.setPaymentTermsEvents();
            }.bind(this));

            oDelete.click(function() {
                if(!cCheckTermsEdit()) {
                    return;
                }
                oTr.remove();
                this.setPaymentTermsEvents();
                this.calculatePaymentTermAmounts(this.document_id);
            }.bind(this));

            // Benötigt für reloadPositionTable
            this.prepareCalendar(oDateInput.get(0));

            if(oTypeHidden.val() === 'final') {
                // Final darf nicht manuell verändert werden!
                oAmountInput.prop('readonly', true);
            } else {
                if(
                    oPaymentConditionSelect.val() === '0' &&
                    oTypeHidden.val() === 'installment'
                ) {
                    // Bei manueller Eingabe gibt es nur Anzahlungen + Restzahlung
                    oTypeHidden.val('deposit');
                    oTr.find('td:first-child > span').text(this.getTranslation('deposit'));
                }

                oAmountInput.prop('readonly', oPaymentConditionSelect.val() !== '0');
            }

            oAmountInput.off('focus, keyup');
            oAmountInput.focus(function() {
                if(!cCheckTermsEdit()) {
                    oAmountInput.blur();
                }
            });
            oAmountInput.keyup(function() {
                // Funktioniert nicht mit dem waitForInputEventStudent-Konstrukt, da keine IDs vorhanden
                var sKey = 'paymentterm_' + iKey;
                if(this.oWaitForInputEventStudentObserver[sKey]) {
                    clearTimeout(this.oWaitForInputEventStudentObserver['paymentterm_' + iKey]);
                }
                this.oWaitForInputEventStudentObserver[sKey] = setTimeout(this.calculatePaymentTermAmounts.bind(this), 500);
            }.bind(this));

        }.bind(this));

    },

    /**
     * Checkbox zum Aktivieren/Deaktivieren aller Positionen aktivieren/deaktivieren
     *
     * Wenn eine Positions-Checkbox abgewählt wurde, muss die Alle-Checkbox auch abgewählt werden (aber ohne Aktion)
     */
    toggleDocumentOnPdfAllCheckbox: function() {
        var bCheckAllActiveCheckbox = true;
        $j('#position_container').children('tr').children().children('input[id^=onpdf_]:not([id$=XXX])').each(function(iKey, oInput) {
            if(
                (
                    oInput.type === 'checkbox' &&
                    !oInput.checked
                ) || (
                    // Siehe fetten Kommentar zum Hidden-Feld in toggleDocumentOnPdfCheckboxes()
                    oInput.type === 'hidden' &&
                    oInput.value != 1
                )
            ) {
                bCheckAllActiveCheckbox = false;
                return false;
            }
        });

        $j('.DocumentPositionDiv #onPdf_toggle_all').prop('checked', bCheckAllActiveCheckbox);
    },

    /**
     * Event beim Verändern der Checkbox für alle Positionen
     */
    toggleDocumentOnPdfCheckboxes: function() {
        var bAllCheckboxChecked = $j('.DocumentPositionDiv #onPdf_toggle_all').prop('checked');
        $j('#position_container').children('tr').children().children('input[id^=onpdf_]:not([id$=XXX])').each(function(iKey, oInput) {
            oInput = $j(oInput);
            var sKey = oInput.attr('id').split('_').pop(); // Müssen keinen fortlaufenden Zahlen sein…

            // Wenn Checkbox disabled ist, soll Row auch nicht umgeschaltet werden
            if(
                !(
                    oInput.prop('disabled') || (
                        /*
						 * Hier passiert etwas ganz Tolles: Wenn man im Positionsdialog die Anzahl (Gruppen) verändert,
						 * wird die Checkbox disabled. Allerdings wird die tatsächliche Checkbox zu einem Hidden
						 * umgewandelt und es wird eine Dummy-Checkbox danach eingefügt. Anstatt die originale
						 * Checkbox einfach auf readonly zu setzen, brauchte hier jemand wieder eine Extrawurst…
						 */
                        oInput.attr('type') === 'hidden' &&
                        oInput.next().attr('type') === 'checkbox' &&
                        oInput.next().prop('disabled')
                    )
                )
            ) {
                oInput.prop('checked', bAllCheckboxChecked);
                this.togglePositionActive(oInput.get(0), sKey, !bAllCheckboxChecked);
            }

        }.bind(this));
    },

    deleteMask : function(oImg){
        var oHead = oImg.up('.accordion');
        var oBody = oHead.next('.accordion');
        oHead.remove();
        oBody.remove();
    },

    calendarCloseHandler: function($super, oIdInput, oDate, bForFilter) {
        $super(oIdInput, oDate, bForFilter);

        // Ende Kursdatum calculateHolidayUntil
        var aResult = oIdInput.id.match(/^course.*\[from\]$/);
        if(aResult){
            this.getUntil(oIdInput, 'calculateCourseUntil');
        }
        // Unterkunftsende
        var aResult = oIdInput.id.match(/^accommodation.*\[from\]$/);
        if(aResult){
            this.getUntil(oIdInput, 'calculateAccUntil');
        }
        // Ferienende
        var aResult = oIdInput.id.match(/^holidays.*\[from\]$/);
        if(aResult){
            this.getUntil(oIdInput, 'calculateHolidayUntil');
        }
        // Versicherungsende / Aktivitätsende
        var aResult = oIdInput.id.match(/^(insurance|activity).*\[from\]/);
        if(aResult){
            this.getUntil(oIdInput, 'calculateUntil');
        }

        if(oIdInput.hasClassName('docment_date_field')){
            this.executeInputEvent(oIdInput);
        }
    },

    enableDisableFollowingCourses  : function(mHolidayId){

        var sHolidayCoursesId                = 'holidays['+mHolidayId+'][course_ids][]';
        var sHolidayFollowingCoursesDivId    = 'following_courses_section_'+mHolidayId;
        var sHolidayFollowingCoursesChkBoxId = 'holidays['+mHolidayId+'][move_following_courses]';
        var sHolidayFollowingCoursesId       = 'holidays['+mHolidayId+'][following_courses][]';

        if(
            !$(sHolidayFollowingCoursesChkBoxId)
        ) {
            return;
        }

        if ($A($F(sHolidayCoursesId)).length > 0) {
            $(sHolidayFollowingCoursesChkBoxId).up('DIV .GUIDialogRow').show();
        } else {
            $(sHolidayFollowingCoursesChkBoxId).checked = false;
            $(sHolidayFollowingCoursesChkBoxId).up('DIV .GUIDialogRow').hide();
            $(sHolidayFollowingCoursesId).disable();
            $(sHolidayFollowingCoursesDivId).hide();
        }

        if(
            $(sHolidayFollowingCoursesChkBoxId) &&
            $(sHolidayFollowingCoursesChkBoxId).checked
        ) {
            $(sHolidayFollowingCoursesId).enable();
            $(sHolidayFollowingCoursesDivId).show();
            this.reloadHolidayFollowingCourses(mHolidayId);
        } else {
            $(sHolidayFollowingCoursesId).disable();
            $(sHolidayFollowingCoursesDivId).hide();
        }

    },

    reloadHolidayFollowingCourses : function (mHolidayId){
        if (!mHolidayId) return (false);
        var sHolidayFromId    = 'holidays['+mHolidayId+'][from]';
        var sHolidayUntilId   = 'holidays['+mHolidayId+'][until]';
        var sHolidayWeeksId   = 'holidays['+mHolidayId+'][weeks]';
        var sHolidayCoursesId = 'holidays['+mHolidayId+'][course_ids][]';

        var sHolidayFollowingCoursesDivId    = 'following_courses_section_'+mHolidayId;
        var sHolidayFollowingCoursesId       = 'holidays['+mHolidayId+'][following_courses][]';

        var oHoliday = {
            from       : $(sHolidayFromId)    ? $F(sHolidayFromId)    : '',
            until      : $(sHolidayUntilId)   ? $F(sHolidayUntilId)   : '',
            weeks      : $(sHolidayWeeksId)   ? $F(sHolidayWeeksId)   : '',
            course_ids : $(sHolidayCoursesId) ? $F(sHolidayCoursesId) : ''
        };

        if (!$(sHolidayFollowingCoursesDivId)) {
            return (false);
        }
        if ( ((oHoliday.from == '') || (oHoliday.until == '')) || (oHoliday.weeks == '') || (oHoliday.course_ids == '') ) {
            return (false);
        }

        var strParameters = '&task=loadHolidayFollowingCourses';

        strParameters += '&idHoliday='+mHolidayId;
        strParameters += '&holiday[from]='+oHoliday.from;
        strParameters += '&holiday[until]='+oHoliday.until;
        strParameters += '&holiday[weeks]='+oHoliday.weeks;
        strParameters += '&holiday[course_ids]='+oHoliday.course_ids;

        this.request(strParameters);
    },

    enableDisableFollowingAccommodations : function ( mHolidayId ) {

        var sHolidayAccommodationsId                = 'holidays['+mHolidayId+'][accommodation_ids][]';
        var sHolidayFollowingAccommodationsDivId    = 'following_accommodations_section_'+mHolidayId;
        var sHolidayFollowingAccommodationsChkBoxId = 'holidays['+mHolidayId+'][move_following_accommodations]';
        var sHolidayFollowingAccommodationsId       = 'holidays['+mHolidayId+'][following_accommodations][]';

        if(
            !$(sHolidayFollowingAccommodationsChkBoxId)
        ) {
            return;
        }

        if ($A($F(sHolidayAccommodationsId)).length > 0) {
            $(sHolidayFollowingAccommodationsChkBoxId).up('DIV .GUIDialogRow').show();
        } else {
            $(sHolidayFollowingAccommodationsChkBoxId).checked = false;
            $(sHolidayFollowingAccommodationsChkBoxId).up('DIV .GUIDialogRow').hide();
        }

    },

    writeMultipleSelect : function (oSelect,aOptions){
        if(oSelect.tagName == 'SELECT'){

            var aOld = oSelect.childElements();
            aOld.each(function(oOption){
                oOption.remove();
            });

            aOptions.each(function(aValue){
                var newOption = document.createElement("option");
                newOption.innerHTML = aValue['value'];
                newOption.value = aValue['id'];

                if((aValue['selected']) == true){
                    newOption.selected = true;
                }
                if( (typeof aValue['title'] != 'undefined') && (aValue['title'] != '') ){
                    newOption.title = aValue['title'];
                }
                if((aValue['disabled']) == true){
                    newOption.disabled = 'disabled';
                    if (typeof newOption.disable == 'function') {
                        newOption.disable();
                    }
                }
                oSelect.appendChild(newOption);
            });

        }
    },

    // Prüft ob man Ferien anlegen kann
    checkIfHolidayIsAvailable : function(aData){

        var bAllowHollidays = false;
        $$('#tabBody_1_'+aData.id+'_'+this.hash+' .courseSelect').each(function(oSelect){
            // Anhand der Inquiry-Course ID gucken wenn > 0 dann wurde Kurs gespeichert

            // Wenn Select Disabled -> kann man auch keine Ferien anlegen
            // EDIT: Das stimmt! Aber gebuchte Ferien müssen dennoch angezeigt werden auch wenn Kurs enabled ist!
            //if(!oSelect.hasClassName('readonly')){
            var sFieldId = oSelect.id;
            var aMatch = sFieldId.match(/^course\[([0-9].*)\]\[([0-9].*)\]\[([a-z].*)\]$/);
            if(aMatch && aMatch[2] > 0){
                bAllowHollidays = true;
            }
            //}

        }.bind(this));

        $$('#tabBody_2_'+aData.id+'_'+this.hash+' .accommodationSelect').each(function(oSelect){
            // Anhand der Inquiry-Accommodation ID gucken wenn > 0 dann wurde Acc gespeichert

            // Wenn Select Disabled -> kann man auch keine Ferien anlegen
            // EDIT: Das stimmt! Aber gebuchte Ferien müssen dennoch angezeigt werden auch wenn Kurs enabled ist!
            //if(!oSelect.hasClassName('readonly')){
            var sFieldId = oSelect.id;
            var aMatch = sFieldId.match(/^accommodation\[([0-9].*)\]\[([0-9].*)\]\[([a-z].*)\]$/);
            if(aMatch && aMatch[2] > 0){
                bAllowHollidays = true;
            }
            //}
        }.bind(this));

        return bAllowHollidays;
    },

    // Observer Funktionen für Holidy Tab
    setHolidayObserver : function (aData){

        // Aufklappen der Headline
        var bCheck = this.checkIfHolidayIsAvailable(aData);

        if(bCheck){
            $$('.tab_holidays .headv').each(function(oDiv){
                oDiv.stopObserving('change');
                Event.observe(oDiv, 'click', function(){

                    if(
                        oDiv.down('input') &&
                        (
                            oDiv.down('input').value == '' ||
                            oDiv.down('input').value == 'new'
                        )
                    ){
                        oDiv.next('.accordion').toggle();
                    }

                }.bind(this));

                // Einmal zu beginn prüfen auch ob Ferien existieren
                oDiv.next('.accordion').show();

            }.bind(this));
        }else{
            // warnung anzeigen
            $$('.tab_holidays .holiday_info').each(function(oDiv) {
                oDiv.show();
            }.bind(this));
            // Ferien verstecken
            $$('.tab_holidays .holidayTab').each(function(oDiv){
                oDiv.hide();
            }.bind(this));
        }


        // Enddatum berechnen
        $$('.tab_holidays .holiday_from').each(function(oInput){

            oInput.stopObserving('change');

            Event.observe(oInput, 'change', function(){
                this.getUntil(oInput, 'calculateHolidayUntil', aData);
                this.setHolidayId(oInput);
            }.bind(this));
        }.bind(this));

        // Felder neuladen bei ändern des enddatums
        $$('.tab_holidays .holiday_until').each(function(oInput){
            oInput.stopObserving('change');

            Event.observe(oInput, 'change', function(){
                var aHolidayIdTemp = oInput.id.split('[');
                var iHolidayId = aHolidayIdTemp[1].replace(']', '');
                this.reloadInquiryHolidaySelectFields(iHolidayId);
            }.bind(this));
        }.bind(this));

        //
        $$('.tab_holidays .holiday_course_ids').each(function(oInput){
            oInput.stopObserving('change');

            Event.observe(oInput, 'change', function(){
                var aHolidayIdTemp = oInput.id.split('[');
                var iHolidayId = aHolidayIdTemp[1].replace(']', '');
                this.enableDisableFollowingCourses(iHolidayId);
            }.bind(this));
        }.bind(this));

        $$('.tab_holidays .move_following_courses').each(function(oInput){
            oInput.stopObserving('change');

            Event.observe(oInput, 'change', function(){
                var aHolidayIdTemp = oInput.id.split('[');
                var iHolidayId = aHolidayIdTemp[1].replace(']', '');
                this.enableDisableFollowingCourses(iHolidayId);
            }.bind(this));
        }.bind(this));

        $$('.tab_holidays .holiday_accommodation_ids').each(function(oInput){
            oInput.stopObserving('change');

            Event.observe(oInput, 'change', function(){
                var aHolidayIdTemp = oInput.id.split('[');
                var iHolidayId = aHolidayIdTemp[1].replace(']', '');
                this.enableDisableFollowingAccommodations(iHolidayId);
            }.bind(this));
        }.bind(this));

        $$('.tab_holidays .move_following_accommodations').each(function(oInput){
            oInput.stopObserving('change');

            Event.observe(oInput, 'change', function(){
                var aHolidayIdTemp = oInput.id.split('[');
                var iHolidayId = aHolidayIdTemp[1].replace(']', '');
                this.enableDisableFollowingAccommodations(iHolidayId);
            }.bind(this));
        }.bind(this));

        // Löschen von Ferien
        //$$('.tab_holidays .holiday_delete').each(function(oDiv){
        $$('.tab_holidays .holiday_block_remover').each(function(oDiv){
            oDiv.stopObserving('click');

            Event.observe(oDiv, 'click', function(){
                var aHolidayIdTemp = oDiv.id.split('_');
                var iHolidayId = aHolidayIdTemp[3];

                this.deleteHolidaySet(iHolidayId, aData);
            }.bind(this));
        }.bind(this));

        // Ferien Wochen refreshen
        $$('#dialog_'+aData.id+'_'+this.hash+' .recalculate_holiday_enddate').each(function(oButton){
            Event.stopObserving(oButton, 'click');
            Event.observe(oButton, 'click', function() {
                var sId = oButton.id.replace(/\[refresh]/, '[from]');
                var oInput = $(sId);
                this.getUntil(oInput, 'calculateHolidayUntil');
            }.bind(this));
        }.bind(this));
    },

    // Aktuelle Holiday ID speichern damit nach dem neuladen die Select Felder
    // neu geladen werden können
    setHolidayId : function(oInput){
        var aHolidayIdTemp = oInput.id.split('[');
        var iHolidayId = aHolidayIdTemp[1].replace(']', '');
        this.currendHolidayId = iHolidayId;
    },

    // Löschen von Ferien
    deleteHolidaySet : function (iHolidayId, aData){

        if(!confirm(this.getTranslation('holiday_delete_question'))) {
            return false;
        }

        var bRestoreService = false;
        var sType = $j('#holidays\\[' + iHolidayId + '\\]\\[type\\]').val();

        if(sType === 'student') {
            bRestoreService = confirm(this.getTranslation('holiday_delete_question_restore'));
        }

        var strParameters = '&task=deleteHolidaySet';

        strParameters += '&holiday_id=' + iHolidayId;
        strParameters += '&restore_service=' + (bRestoreService | 0);

        this.request(strParameters);
    },

    // Reload Holiday Select fields
    reloadInquiryHolidaySelectFields : function (mHolidayId){

        if(!mHolidayId){
            return false;
        }

        var sHolidayFromId  = 'holidays['+mHolidayId+'][from]';
        var sHolidayUntilId = 'holidays['+mHolidayId+'][until]';
        var sHolidayWeeksId = 'holidays['+mHolidayId+'][weeks]';
        var oHoliday = {
            from  : $(sHolidayFromId)  ? $F(sHolidayFromId)  : '',
            until : $(sHolidayUntilId) ? $F(sHolidayUntilId) : '',
            weeks : $(sHolidayWeeksId) ? $F(sHolidayWeeksId) : ''
        };

        var strParameters = '&task=loadInquiryHolidaySelectFields';
        strParameters += '&idHoliday='+mHolidayId;
        strParameters += '&holiday[from]='+oHoliday.from;
        strParameters += '&holiday[until]='+oHoliday.until;
        strParameters += '&holiday[weeks]='+oHoliday.weeks;

        this.request(strParameters);
    },

    // Die Funktion fügt bei Gruppen die Kontaktperson zu den Gruppenkunden hinzu
    groupCustomerContact : function (aData, oCheckbox){

        var bName = true;
        var bFirstName = true;
        var oLastCustomerName;
        if(
            $('save['+this.hash+']['+aData.id+'][firstname][cdb1]') &&
            $('save['+this.hash+']['+aData.id+'][lastname][cdb1]') &&
            $F($('save['+this.hash+']['+aData.id+'][firstname][cdb1]')) != '' &&
            $F($('save['+this.hash+']['+aData.id+'][lastname][cdb1]')) != '' &&
            oCheckbox.checked == true
        ){
            // Prüfen ob Kunde schon in Liste ist ansonsten einfügen
            $$('#tabBody_6_'+aData.id+'_'+this.hash+' .name').each(function(oInput){
                if($F(oInput) == $F($('save['+this.hash+']['+aData.id+'][lastname][cdb1]'))){
                    bName = false;
                }
                oLastCustomerName = oInput;
            }.bind(this));

            $$('#tabBody_6_'+aData.id+'_'+this.hash+' .firstname').each(function(oInput){
                if($F(oInput) == $F($('save['+this.hash+']['+aData.id+'][firstname][cdb1]'))){
                    bFirstName = false;
                }
            }.bind(this));

            if(bName == true && bFirstName == true){
                // Vorname
                oLastCustomerName.value = $F($('save['+this.hash+']['+aData.id+'][lastname][cdb1]'));
                // Nachname
                var oLastCustomerFirstname = oLastCustomerName.up('tr').down('td').next('.firstname_td').down('input');
                oLastCustomerFirstname.value = $F($('save['+this.hash+']['+aData.id+'][firstname][cdb1]'));



                // neue leere KundenZeile ans Ende einfügen
                this.writeNewGroupCustomerLine(aData, oLastCustomerName);
            }else{
                alert(this.getTranslation('groupContact'));
            }
        }

    },



    // Funktion lädt die Kundendaten eines anderen Kunden nach
    fillUserData : function (aData, oCustomer, container) {

        // Bei Gruppen darf das Kopieren nicht funktionieren! #5047
        // TODO Wäre es nicht sinnvoller, dass man die Suche bei Gruppenmitgliedern ganz deaktiviert?
        if(this.getDialogSaveField('group_id', 'ki').val() > 0) {
            alert(this.getTranslation('group_customer_overwrite_denied'));
            return;
        }

        var type = $j(container).data('type');

        if(type == 'booker') {
            this.fillBookerData(aData, oCustomer, container);
        } else if(type == 'hubspot') {
            this.fillTravellerDataFromHubSpot(aData, oCustomer, container);
        } else {
            this.fillTravellerData(aData, oCustomer, container);
        }

    },

    fillTravellerDataFromHubSpot : function (aData, properties, container) {

        // Hiddenfelder setzen
        if($('replaceHubspotContactId['+this.hash+']['+aData.id+']')){
            $('replaceHubspotContactId['+this.hash+']['+aData.id+']').value = properties.id;
        }

        if(
            $('replaceCustomerId['+this.hash+']['+aData.id+']') &&
            properties.traveller_id != null
        ){
            $('replaceCustomerId['+this.hash+']['+aData.id+']').value = properties.traveller_id;
        }

        $j.each(properties, function(sKey, mValue) {

            switch(sKey) {
                case 'date_of_birth':
                    this.setDialogSaveFieldValue('birthday', 'cdb1', mValue);
                    break;
                case 'email':
                    this.clearStudentRecordEmailRows();
                    if(mValue) {
                        this.addStudentRecordEmailRow(properties.id+aData.id, mValue);
                    } else {
                        this.addStudentRecordEmailRow();
                    }
                    break;
                // TODO Geschlecht (und Agenturmitarbeiter?) einbauen (In Hubspot kann das Geschlecht ein Textfeld sein, bei uns nicht)
                case 'gender':
                case 'agency_id':
                    break;
                // Erstmal nicht:
                // case 'agency_id':
                // 	this.setDialogSaveFieldValue(sKey, 'ki', mValue);
                // 	break;
                default:
                    this.setDialogSaveFieldValue(sKey, 'cdb1', mValue);
                    break;
            }

        }.bind(this));

    },

    fillBookerData : function (aData, oCustomer, container) {

        // Hiddenfeld setzen um beim speichern das correcte Customer Obj zu bekommen
        if($('replaceBookerId['+this.hash+']['+aData.id+']')){
            $('replaceBookerId['+this.hash+']['+aData.id+']').value = oCustomer.id;
        }

        $j.each(oCustomer, function(sKey, mValue) {

            switch(sKey) {
                case 'c_address':
                    $j.each(mValue, function(sAddressField, sValue) {
                        this.setDialogSaveFieldValue(sAddressField, 'tc_a_b', sValue);
                    }.bind(this));

                    break;
                case 'phone_private':
                    this.setDialogSaveFieldValue('detail_phone_private', 'tc_bc', mValue);
                    break;
                default:
                    this.setDialogSaveFieldValue(sKey, 'tc_bc', mValue);
                    break;
            }

        }.bind(this));

    },

    fillTravellerData : function (aData, oCustomer, container) {

        // Hiddenfeld setzen um beim speichern das correcte Customer Obj zu bekommen
        if($('replaceCustomerId['+this.hash+']['+aData.id+']')){
            $('replaceCustomerId['+this.hash+']['+aData.id+']').value = oCustomer.id;
        }

        if(
            oCustomer.booker &&
            oCustomer.booker.id
        ) {
            if($('replaceBookerId['+this.hash+']['+aData.id+']')){
                $('replaceBookerId['+this.hash+']['+aData.id+']').value = oCustomer.booker.id;
            }
        }

        $j.each(oCustomer, function(sKey, mValue) {

            switch(sKey) {
                case 'booker':
                    this.setDialogSaveFieldValue('firstname', 'tc_bc', mValue.firstname);
                    this.setDialogSaveFieldValue('lastname', 'tc_bc', mValue.lastname);
                    break;
                case 'c_address':
                case 'c_billing':

                    $j.each(mValue, function(sAddressField, sValue) {
                        var oInput;
                        if(sKey === 'c_billing') {
                            this.setDialogSaveFieldValue(sAddressField, 'tc_a_b', sValue);
                        } else {
                            this.setDialogSaveFieldValue(sAddressField, 'tc_a_c', sValue);
                        }
                    }.bind(this));

                    break;
                case 'emails':

                    this.clearStudentRecordEmailRows();
                    if(mValue.length === 0) {
                        this.addStudentRecordEmailRow();
                    } else {
                        mValue.forEach(function(oEmail) {
                            this.addStudentRecordEmailRow(oEmail.id, oEmail.email);
                        }.bind(this));
                    }

                    break;
                case 'visa_data':

                    $j.each(mValue, function(key, value) {
                        this.setDialogSaveFieldValue(key, 'ts_ijv', value);
                    }.bind(this));

                    break;
                default:

                    var oInput = this.getDialogSaveField(sKey, 'cdb1');
                    if(oInput.length === 0) {
                        this.setDialogSaveFieldValue(sKey, 'tc_c_d', mValue);
                    } else {
                        this.setDialogSaveFieldValue(sKey, 'cdb1', mValue);
                    }

                    break;
            }

        }.bind(this));

    },

    searchForHubspotContact: function(aData, container, direction) {

        if(!container.id) {
            container.id = 'hubspot-search-container-'+Math.floor(Math.random()*99999);
        }

        $j(container).find('.loading').show();

        if (direction == 'searchInHubspot') {
            var sSearch = $j(container).find('.hubspotcustomer_search').val();

            if(sSearch == '' || sSearch.length<2) {
                $j(container).find('.customer_identification_results').hide();
                $j(container).find('.loading').hide();
            } else {
                this.requestBackground('&task=searchForHubspotContact&search='+sSearch+'&container_id='+container.id);
            }
        } else if (direction == 'searchFromFideloToHubspot') {
            var sLastname  = $F($('save['+this.hash+']['+aData.id+'][lastname][cdb1]'));
            var sFirstname    = $F($('save['+this.hash+']['+aData.id+'][firstname][cdb1]'));
            var	sBirthday   = $F($('save['+this.hash+']['+aData.id+'][birthday][cdb1]'));
            var	sCompanyId   = $F($('save['+this.hash+']['+aData.id+'][agency_id][ki]'));

            var emailInputs = document.getElementById('container_contact_email').getElementsByTagName('input');
            var emailInputs = Array.from(emailInputs)
            var emails = [];
            var i = 0 ;

            emailInputs.each(function(emailInput){
                if (emailInput.value.length > 7) {
                    emails[i] = emailInput.value;
                    i++;
                }
            });

            if(
                sLastname.length > 0 ||
                sFirstname.length > 0 ||
                sBirthday.length > 0 ||
                sCompanyId != '0' ||
                emails.length > 0
            ) {
                this.requestBackground('&task=searchForHubspotContact&lastname='+sLastname+'&firstname='+sFirstname+'&bday='+sBirthday+'&companyId='+sCompanyId+'&emails='+emails+'&container_id='+container.id);
            } else {
                $j(container).find('.customer_identification_results').hide();
                $j(container).find('.loading').hide();
            }
        }
    },

    // Funktion sucht nach anderen/ähnlichen kunden
    checkForSameUser : function(aData, bSearchField, container) {

        $j(container).find('.loading').show();

        if(bSearchField) {

            var sSearch = $j(container).find('.customernumber_search').val();

            if(sSearch == '') {
                $j(container).find('.customer_identification_results').hide();
                $j(container).find('.loading').hide();
            } else {
                this.requestBackground('&task=searchForSameUser&search='+sSearch+'&contact_id='+aData['contact_id']+'&container_id='+container.id);
            }

        } else {

            var sLastname  = $F($('save['+this.hash+']['+aData.id+'][lastname][cdb1]'));
            var sFirstname    = $F($('save['+this.hash+']['+aData.id+'][firstname][cdb1]'));
            var	sBirthday   = $F($('save['+this.hash+']['+aData.id+'][birthday][cdb1]'));
            if(sLastname != '' && sFirstname != '' && sBirthday != ''){
                this.requestBackground('&task=searchForSameUser&lastname='+sLastname+'&firstname='+sFirstname+'&bday='+sBirthday+'&contact_id='+aData['contact_id']+'&container_id='+container.id);
            }

        }

    },

    createCustomerDivForMatching : function(oResultList, oSelectList, oItem, bChecked){

        oResultList.insert({
            top: new Element('div', {
                id: 'matchingCustomer_'+oItem.id,
                'class': 'flex flex-row items-center gap-2 p-1 matchingCustomer_'+oItem.id
            }).update(' ' + oItem.customerNumber + ' ' + oItem.name)
        });
        // Checkbox
        $('matchingCustomer_'+oItem.id).insert({
            top: new Element('input', {
                id: 'matchingCustomerCheck_'+oItem.id,
                name: 'roomSharingSelectedItems[]',
                type: 'checkbox',
                'class': 'roomSharingCheckbox',
                value: oItem.id,
                checked: bChecked,
                style: 'margin-top: 0;'
            })
        });

        var iDivId = oResultList.id;

        if(
            iDivId.match(/.*room_sharing_list_list.*/) != null
        ){
            var oTemp = oSelectList;
            oSelectList = oResultList;
            oResultList = oTemp;
        }

        // Observer setzen auf Checkbox
        Event.observe($('matchingCustomerCheck_'+oItem.id), 'change', function(){
            this.switchMatchingCustomer($('matchingCustomer_'+oItem.id), oResultList, oSelectList);
        }.bind(this));
    },

    changeCurrency : function(iCurrency, sDialogId, sAlias){

        if(!sAlias) {
            sAlias = this.sAlias;
        }

        var oCurrency = $('save['+this.hash+']['+sDialogId+'][currency_id]['+sAlias+']');

        if(oCurrency)
        {
            oCurrency.value = iCurrency;
        }

    },

    writePaymentMethodData : function(oAgency, oPaymentMethode, oPaymentMethodeComment, aData){

        aData = aData[$F(oAgency)];

        if($F(oAgency) > 0){
            oPaymentMethode.value = aData.id;
            oPaymentMethodeComment.value = aData.comment;
            this.enablePaymentMethod(oPaymentMethode);
        } else {
            oPaymentMethodeComment.value = '';
            this.disablePaymentMethod(oPaymentMethode);
        }

    },

    enablePaymentMethod : function(oPaymentMethode){
        oPaymentMethode.childElements().each(function(oOption){
            if(
                oOption.value == 0 ||
                oOption.value == 2
            ){
                oOption.disabled = false;
            }
        });

    },

    disablePaymentMethod : function(oPaymentMethode){

        if(oPaymentMethode.value != 3){
            oPaymentMethode.value = 1;
        }

        oPaymentMethode.childElements().each(function(oOption){

            if(
                oOption.value == 0 ||
                oOption.value == 2
            ){
                oOption.disabled = true;
            }
        });
    },

    // Switched den Kunden von einer zur anderer Box
    switchMatchingCustomer : function (oSwitchElement, oDiv1, oDiv2){

        var bFromDiv1 = false;

        var iDivId = oSwitchElement.up('.GUIDialogRowInputDiv').id;

        if(
            iDivId.match(/.*room_sharing_list_list.*/) == null
        ){
            bFromDiv1 = true;
        }

        if(bFromDiv1){
            oDiv2.insert({
                bottom: oSwitchElement
            });
        } else {

            oDiv1.insert({
                bottom: oSwitchElement
            });
        }

        // Falle eines der Divs leer ist verstecken
        var aElements = oDiv1.childElements();
        if(aElements.length == 0){
            oDiv1.up().hide();
        } else {
            oDiv1.up().show();
        }

        var aElements = oDiv2.childElements();
        if(aElements.length == 0){
            oDiv2.up().hide();
        } else {
            oDiv2.up().show();
        }
    },

    // Zusammenreisende Schüler
    setMatchingObserver : function (aData){

        // START Matching Infos anzeigen
        if(aData.sMatchingInformation){

            $$('#dialog_'+aData.id+'_'+this.hash+' .matching_info').each(function(oInput){
                oInput.value = aData.sMatchingInformation;
                oInput.up('.GUIDialogRow').show();
            }.bind(this));
        }
        // ENDE

        if($('save['+this.hash+']['+aData.id+'][acc_share_with_id]')){

            Event.observe($('save['+this.hash+']['+aData.id+'][acc_share_with_id]'), 'keyup', function(){
                this.prepareSearchMatchingCustomer(aData);
            }.bind(this));
        }

        // START Gespeicherte zusammenreisende Kunden anzeigen
        if( aData.aRoomSharingList &&
            aData.aRoomSharingList.length > 0 &&
            $('save['+this.hash+']['+aData.id+'][room_sharing_search_results_list]') &&
            $('save['+this.hash+']['+aData.id+'][room_sharing_list_list]')
        ){
            // Ergebnisliste
            var oResultList = $('save['+this.hash+']['+aData.id+'][room_sharing_search_results_list]');
            oResultList.update('');
            // Auswahlliste
            var oSelectList = $('save['+this.hash+']['+aData.id+'][room_sharing_list_list]');


            oSelectList.up().show();

            var bChecked = true;
            $A(aData.aRoomSharingList).each( function ( oItem, iIndex ) {
                this.createCustomerDivForMatching(oSelectList, oResultList, oItem, bChecked);
            }.bind(this));
        }
        // ENDE

        // START Ausblenden des "Zusammenreisende Schüler"- Suchfeldes + checkboxen in den Schülerlisten
        if(
            $('save['+this.hash+']['+aData.id+'][acc_share_with_id]') &&
            aData.bDisableMatchingSelect == 1
        ){
            $('save['+this.hash+']['+aData.id+'][acc_share_with_id]').up('.GUIDialogRow').hide();

            $$('#dialog_'+aData.id+'_'+this.hash+' .roomSharingCheckbox').each(function(oCheckbox){
                oCheckbox.readOnly = true;
                oCheckbox.disabled = true;
                oCheckbox.addClassName('readonly');
            }.bind(this));
        }
        // ENDE
    },

    // Sucht passende Kunden mit denen man zusammenreisen kann
    prepareSearchMatchingCustomer : function(aData){

        if(this.prepareSearchMatchingCustomerTimer){
            clearTimeout(this.prepareSearchMatchingCustomerTimer);
        }

        this.prepareSearchMatchingCustomerTimer = setTimeout(this.searchMatchingCustomer.bind(this), 800, aData);
    },

    // Sucht passende Kunden mit denen man zusammenreisen kann
    searchMatchingCustomer : function (aData){

        var sSearch = $F($('save['+this.hash+']['+aData.id+'][acc_share_with_id]'));

        if ( String( $F('save['+this.hash+']['+aData.id+'][acc_share_with_id]') ).blank() ) {
            $('save['+this.hash+']['+aData.id+'][room_sharing_search_results_list]').update('');
            return (false);
        }

        // Sucht nach Unterkunftszeiten und setzt den Request ab
        this.getPeriodData(aData, 'accommodation_room_sharing', '&search='+sSearch);
    },

    // Observer für den Kurs-Tab
    setCoursesObserver : function (aData) {

        var self = this;

        // Kurszeitraum ermitteln und Unterkunftstab anzeigen
        this.getPeriodData(aData, 'course', '&transfer_question=0');

        // Funktion zum Deaktivieren von Zeitfeltern bei entsprechender Gruppeneinstellung
        var disableFields = function(sFieldClass) {
            $$('#dialog_'+aData.id+'_'+this.hash+' .' + sFieldClass).each(function(oInput){
                oInput.addClassName('readonly');
                oInput.disabled = true;
                this.disableCalendarInput(oInput);
            }.bind(this));
        }.bind(this);

        // Datums- und Zeitfelder bei Kursen deaktivieren bei Gruppeneinstellung »gleiche Zeiträume«
        if(aData.course_info == 'only_time') {
            disableFields('calculateCourseUntil');
            disableFields('calculateCourseTo');
            disableFields('courseWeeks');
        }

        // Datums- und Zeitfelder bei Unterkünften deaktivieren bei Gruppeneinstellung »gleiche Zeiträume«
        if(aData.accommodation_info == 'only_time') {
            disableFields('calculateAccUntil');
            disableFields('calculateAccTo');
            disableFields('accommodationWeeks');
        }

        // Datums- und Zeitfelder bei Transfer deaktivieren bei Gruppeneinstellung »gleiche Zeiträume«
        if(aData.transfer_info == 'only_time') {
            disableFields('input_transfer_date');
            disableFields('input_transfer_time');
            disableFields('input_transfer_pickup_time');
        }

        // Kurskategorie
        $j('.course_category_select').off('change');
        $j('.course_category_select').change(function (e, keepValue) {
            var select = $j(e.target);
            var value = parseInt(select.val());
            var courses = this.courseData.filter(function (course) {
                if (!value) {
                    // Alle Kurse anzeigen
                    return true;
                }
                return course.id === 0 || course.category_id === value;
            }).map(function (course) {
                return { text: course.name, value: course.id };
            });

            var courseSelect = select.closest('.InquiryCourseContainer').find('select.courseSelect');
            self.updateSelectOptions(courseSelect.get(0), courses, false, !!keepValue);
        }.bind(this));

        // Kurs
        $j('.courseSelect').off('change');
        $j('.courseSelect').change(function (e, initial) {
            var select = $j(e.target);

            // Musste scheinbar schon immer hiermit synchron gehalten werden
            $j(select.attr('id').replace(/course_id/, 'course_id_hidden')).val(select.val());

            var courseData = this.courseData.filter(function(course) {
                return course.id === parseInt(select.val());
            }).shift() || {};

            var courseCategorySelect = select.closest('.InquiryCourseContainer').find('select.course_category_select');
            var periodContainer = select.closest('.InquiryCourseContainer').find('.course_period_container');
            var rowWeeks = select.closest('.InquiryCourseContainer').find('.row_weeks');
            var rowUntil = select.closest('.InquiryCourseContainer').find('.row_until');
            var rowUnits = select.closest('.InquiryCourseContainer').find('.row_units');
            var rowProgram = select.closest('.InquiryCourseContainer').find('.row_program');
            var rowFlexAllocation = select.closest('.InquiryCourseContainer').find('.row_flex_allocation');

            periodContainer.show();
            rowWeeks.show();
            rowUntil.show();
            rowUnits.hide();
            rowFlexAllocation.show();
            rowProgram.hide();

            this.updateSelectOptions(rowProgram.find('select').get(0), courseData.programs, false, true);

            var courseLanguageOptions = [];
            if(
                courseData.course_languages &&
                courseData.course_languages.length > 1
            ) {
                courseLanguageOptions = [
                    {text: '', value: 0}
                ];
                select.closest('.InquiryCourseContainer').find('.row_course_languages').show();
            } else {
                select.closest('.InquiryCourseContainer').find('.row_course_languages').hide();
            }

            if(
                courseData.course_languages &&
                courseData.course_languages.length > 0
            ) {
                courseData.course_languages.each(function(courseLanguageId) {
                    courseLanguageOptions.push({text: this.courseLanguages[courseLanguageId], value: courseLanguageId});
                }.bind(this));
            }

            this.updateSelectOptions(select.closest('.InquiryCourseContainer').find('.courseLanguageSelect').get(0), courseLanguageOptions, false, true);

            $j(rowUnits).find('span.lessons_unit').html(this.courseLessonsUnits[courseData.lessons_unit] ?? '')

            if (courseData.per_unit === 1) {

                // Lektionskurse

                var unitsInput = $j(rowUnits).find('.lessons-input')
                var unitsSelect = $j(rowUnits).find('.lessons-select')

                if (courseData.lessons_fix) {
                    $j(unitsInput).attr('name', $j(unitsInput).attr('name').replace('[units]', '[units_dummy]'))
                    $j(unitsInput).attr('id', $j(unitsInput).attr('name').replace('[units]', '[units_dummy]'))
                    $j(unitsInput).hide()
                    $j(unitsSelect).attr('name', $j(unitsSelect).attr('name').replace('[units_dummy]', '[units]'))
                    $j(unitsSelect).attr('id', $j(unitsSelect).attr('name').replace('[units_dummy]', '[units]'))
                    this.updateSelectOptions(unitsSelect[0], courseData.lessons, false, true)
                    $j(unitsSelect).show()
                } else {
                    $j(unitsInput).attr('name', $j(unitsInput).attr('name').replace('[units_dummy]', '[units]'))
                    $j(unitsInput).attr('id', $j(unitsInput).attr('name').replace('[units_dummy]', '[units]'))
                    $j(unitsInput).show()
                    $j(unitsSelect).attr('name', $j(unitsSelect).attr('name').replace('[units]', '[units_dummy]'))
                    $j(unitsSelect).attr('id', $j(unitsSelect).attr('name').replace('[units]', '[units_dummy]'))
                    $j(unitsSelect).hide()
                    if (!initial && courseData.lessons.length > 0) {
                        // Defaultwert bei neuen Kursen setzen
                        $j(unitsInput).val(courseData.lessons[0].value)
                    }
                }

                rowUnits.show();

            } else if (courseData.per_unit === 2) {
                // Prüfungen
                rowWeeks.hide();
                rowUntil.hide();
                rowFlexAllocation.hide();
            } else if (courseData.per_unit === 5) {
                // Programm
                periodContainer.hide();
                rowProgram.show();
            }

            // Kategorie setzen, wenn vorhanden
            // Nur beim Initialisieren, damit beim Anlegen eines Kurses nicht direkt die Kategorie gesetzt wird, falls falsch ausgewählt wird
            if (
                initial &&
                // courseData.category_id && // change auch bei neuem Kurs ausführen, da sonst die Options veraltet bleiben würden
                parseInt(courseCategorySelect.val()) === 0 &&
                courseData.category_id > 0 // Nur wenn die Kursdaten da sind. Ist der Kurs schon gelöscht (alte Buchung) kann es sein, dass die Daten nicht da sind
            ) {
                courseCategorySelect.val(courseData.category_id);
                courseCategorySelect.trigger('change', true); // true = keepValue
            }

            this.updateAdditionalServices('course', select.get(0));

            self.getCourseInfos(select);

            if (!initial) {
                this.setActivityObserver(aData);
            }

        }.bind(this)).trigger('change', true); // true = initial

        $$('#dialog_'+aData.id+'_'+this.hash+' .courseLanguageSelect').each(function(oSelect,iKey) {

            Event.stopObserving(oSelect, 'change');
            Event.observe(oSelect, 'change', function() {
                this.getCourseInfos(oSelect);
            }.bind(this));

        }.bind(this));

        $$('#dialog_'+aData.id+'_'+this.hash+' .course_level_select').each(function(oSelect,iKey) {

            Event.stopObserving(oSelect, 'change');
            Event.observe(oSelect, 'change', function() {
                this.getCourseInfos(oSelect);
            }.bind(this));

        }.bind(this));

        // START Kursende ermitteln
        $$('#dialog_'+aData.id+'_'+this.hash+' .calculateCourseUntil').each(function(oInput){
            Event.stopObserving(oInput, 'keyup');
            Event.observe(oInput, 'keyup', function() {
                this.getPrepareUntil(oInput, 'calculateCourseUntil');
            }.bind(this));
        }.bind(this));
        // ENDE

        // START Kurszeitraum ermitteln
        $$('#dialog_'+aData.id+'_'+this.hash+' .calculateCourseTo').each(function(oInput){
            Event.stopObserving(oInput, 'keyup');
            Event.observe(oInput, 'keyup', function() {
                this.getPeriodData(aData, 'course');
            }.bind(this));

            $j(oInput).change(function() {
                this.updateAdditionalServices('course', $j(oInput).parents('.InquiryCourseContainer').find('.courseSelect')[0]);
            }.bind(this))

        }.bind(this));
        // ENDE

        $$('#dialog_'+aData.id+'_'+this.hash+' .course_block_remover').each(function(oInput){
            Event.stopObserving(oInput, 'click');
            Event.observe(oInput, 'click', function(e){

                // Prüfen ob gelöscht werden darf
                var iCheckDelete = this.checkDelete(e, aData, 'course');

                if(iCheckDelete == 0){
                    this.deleteInquiryCourseOrAccommodation(e, aData);
                    // Anzeige im Accommodation Tab korrekt löschen
                    this.getPeriodData(aData, 'course');
                }else{
                    var sError = '';
                    switch(iCheckDelete){
                        case 1:
                            sError = this.getTranslation('delete_position_payment');
                            break;
                    }

                    alert(sError);
                }


            }.bind(this));
        }.bind(this));

        // START Kurszeitraum ermitteln
        $$('#dialog_'+aData.id+'_'+this.hash+' .course_block_visibility').each(function(oSelect){
            Event.stopObserving(oSelect, 'change');
            Event.observe(oSelect, 'change', function() {
                this.getPeriodData(aData, 'course');
            }.bind(this));
        }.bind(this));
        // ENDE


        // Wochenanzahl-aktualisierungs-button verbergen, wenn Feld nicht bearbeitbar
        $$('#dialog_'+aData.id+'_'+this.hash+' .courseWeeks').each(function(oInput){
            if(oInput.hasClassName('readonly')){
                var oButton = oInput.next('button');
                if(oButton){
                    oButton.hide();
                }
            }
        }.bind(this));

        // Kurs ID Hidden Feld entsperren
        $$('#dialog_'+aData.id+'_'+this.hash+' .courseSelectHidden').each(function(oInput){
            oInput.enable();
        }.bind(this));

        $$('#dialog_'+aData.id+'_'+this.hash+' .recalculate_course_enddate').each(function(oButton){
            Event.stopObserving(oButton, 'click');
            Event.observe(oButton, 'click', function() {
                var sId = oButton.id.replace(/\[refresh]/, '[from]');
                var oInput = $(sId);
                this.getUntil(oInput, 'calculateCourseUntil');
            }.bind(this));
        }.bind(this));

    },

    getCourseInfos: function(oInput) {

        var oCourseContainer = $j(oInput).parents('.InquiryCourseContainer');

        oCourseContainer.find('.course_info_container').empty();

        // Nicht so schön weil der Container keine eindeutige ID hat
        var sParam = '&task=request&action=CourseInfo';
        sParam += '&container='+oCourseContainer.find('.box-body').attr('id');
        sParam += '&course_id='+oCourseContainer.find('.courseSelect').val();
        sParam += '&courselanguage_id='+oCourseContainer.find('.courseLanguageSelect').val();
        sParam += '&level_id='+oCourseContainer.find('.course_level_select').val();

        this.request(sParam, '', '', false, 0, false);

    },

    setCourseInfo: function(aData) {

        var oCourseInfoContainer = $j('#'+aData.container+' .course_info_container');
        if (oCourseInfoContainer.length > 0) {

            if (this.oVueInstances[aData.container]) {
                // Existierende Vue-Instance unmounten
                this.oVueInstances[aData.container][0].unmount()
            }

            window.__FIDELO__.EMITTER.off(`course:start:${this.hash}:${aData.container}`)
            window.__FIDELO__.EMITTER.on(`course:start:${this.hash}:${aData.container}`, (date) => {
                var oCourseFrom = $j('#'+aData.container+' .calculateCourseUntil');
                var oCourseWeeks = $j('#'+aData.container+' .courseWeeks');

                if (oCourseFrom.length > 0 && oCourseWeeks.length > 0) {
                    var setDate = () => {
                        oCourseFrom.val(date.label);
                        oCourseWeeks.val(date.weeks)
                        this.getUntil(oCourseFrom.get(0), 'calculateCourseUntil');
                    }

                    if (oCourseFrom.val().length > 0) {
                        if (confirm(this.getTranslation('confirm_course_period_change'))) {
                            setDate(date);
                        }
                    } else {
                        setDate(date);
                    }

                }
            })

            aData.emitter = window.__FIDELO__.EMITTER
            this.oVueInstances[aData.container] = window.__FIDELO__.TuitionVueUtil.createVueApp('course/CourseAvailability', oCourseInfoContainer[0], this, aData);
        }

    },

    // Observer für den Unterkunftstab
    setAccommodationObserver : function (aData){

        // Weiche Matching Kriterien toggeln
        this.toggleSoftMatchingCriterion(aData);

        // START Raumart aktivieren | Anreisezeit updaten
        $$('#dialog_'+aData.id+'_'+this.hash+' .accommodationSelect').each(function(oSelect){
            Event.stopObserving(oSelect, 'change');
            Event.observe(oSelect, 'change', function() {
                this.disableAccRoomtype(oSelect, aData);
                // Anreisezeit festelegen
                //this.showArrDepTime(oSelect, aData);
                aData.field_id = oSelect.id;
                this.getPeriodData(aData, 'accommodation', '&field='+oSelect.id+'&category_id='+$j(oSelect).val()+'&transfer_question=1');
                // Weiche Matching Kriterien toggeln
                this.toggleSoftMatchingCriterion(aData);
            }.bind(this));

            // Einmal am Anfang ausführen
            this.disableAccRoomtype(oSelect, aData);

        }.bind(this));
        // ENDE

        // START Raumart aktivieren
        $$('#dialog_'+aData.id+'_'+this.hash+' .RoomtypeSelect').each(function(oSelect){
            Event.stopObserving(oSelect, 'change');
            Event.observe(oSelect, 'change', function() {
                this.disableAccMeal(oSelect, aData);
            }.bind(this));

            // Einmal am Anfang ausführen
            this.disableAccMeal(oSelect, aData);
        }.bind(this));
        // ENDE

        // START Verpflegung aktivieren
        $$('#dialog_'+aData.id+'_'+this.hash+' .MealtypeSelect').each(function(oSelect){
            Event.stopObserving(oSelect, 'change');
            Event.observe(oSelect, 'change', function() {
                this.updateAdditionalServices('accommodation', oSelect);
            }.bind(this));

            // Einmal am Anfang ausführen
            this.updateAdditionalServices('accommodation', oSelect);
        }.bind(this));
        // ENDE

        // START Unterkunftsende ermitteln / Transferübernahme
        $$('#dialog_'+aData.id+'_'+this.hash+' .calculateAccUntil').each(function(oInput){
            Event.stopObserving(oInput);
            Event.observe(oInput, 'keyup', function() {
                this.getPrepareUntil(oInput, 'calculateAccUntil');
            }.bind(this));

        }.bind(this));
        // ENDE

        // START Transfer Daten verändern bei manueller änderung von 'BIS' Datum
        $$('#dialog_'+aData.id+'_'+this.hash+' .calculateAccTo').each(function(oInput){
            Event.stopObserving(oInput);
            Event.observe(oInput, 'change', function() {
                this.getPeriodData(aData, 'transfer');
            }.bind(this));

            $j(oInput).change(function() {
                this.updateAdditionalServices('accommodation', $j(oInput).parents('.InquiryAccommodationContainer').find('.accommodationSelect')[0]);
            }.bind(this))

        }.bind(this));
        // ENDE

        $$('#dialog_'+aData.id+'_'+this.hash+' .accommodation_block_remover').each(function(oInput){
            Event.stopObserving(oInput, 'click');
            Event.observe(oInput, 'click', function(e){
                // Prüfen ob gelöscht werden darf
                var iCheckDelete = this.checkDelete(e, aData, 'accommodation');

                if(iCheckDelete == 0){
                    this.deleteInquiryCourseOrAccommodation(e, aData, 'accommodation');
                }else{
                    var sError = '';
                    switch(iCheckDelete){
                        case 1:
                            sError = this.getTranslation('delete_position_payment');
                            break;
                    }

                    alert(sError);
                }

            }.bind(this));
        }.bind(this));

        // Wochenanzahl-aktualisierungs-button verbergen, wenn Feld nicht bearbeitbar
        $$('#dialog_'+aData.id+'_'+this.hash+' .accommodationWeeks').each(function(oInput){
            if(oInput.hasClassName('readonly')){
                var oButton = oInput.next('button');
                if(oButton){
                    oButton.hide();
                }
            }
        }.bind(this));

        $$('#dialog_'+aData.id+'_'+this.hash+' .recalculate_accommodation_enddate').each(function(oButton){
            Event.observe(oButton, 'click', function() {
                var sId = oButton.id.replace(/\[refresh]/, '[from]');
                var oInput = $(sId);
                this.getUntil(oInput, 'calculateAccUntil');
            }.bind(this));
        }.bind(this));

    },

    updateAdditionalServices: function(type, select) {

        var item, key, serviceUntil, serviceUntilExists, datepicker;
        var additionalServices = [];

        if(type === 'course') {

            item = $j(select).parents('.InquiryCourseContainer');

            if (item.length === 0) {
                item = $j(select).parents('.InquiryCourseGuideContainer');
            }

            serviceUntilExists = item.find('.calculateCourseTo').val();
            datepicker = item.find('.calculateCourseTo').data('datepicker');
            if (
                serviceUntilExists &&
                datepicker
            ) {
                // Wenn die Buchung einer Gruppe zugehört und zum Kurs "alle Daten gleich" in der Gruppe ausgewählt wurde,
                // dann ist der Kursbuchungsdialog auf readOnly und bei readOnly Felder gibt es kein datePicker
                // -> dann unten alle Zusatzleistungen einfach anzeigen
                serviceUntil = datepicker.getDate();
            } else {
                // Wenn kein "Bis"-Datum angegeben wird, dann mit dem aktuellen Datum vergleichen unten
                serviceUntil = new Date();
            }
            if($j(select).val() > 0) {
                key = $j(select).val();
                additionalServices = this.additionalservicesCourse;
            }

        } else {

            item = $j(select).parents('.InquiryAccommodationContainer');

            if (item.length === 0) {
                item = $j(select).parents('.InquiryAccommodationGuideContainer');
            }

            serviceUntil = item.find('.calculateAccTo').val();
            datepicker = item.find('.calculateAccTo').data('datepicker');
            if (
                serviceUntilExists &&
                datepicker
            ) {
                // Wenn die Buchung einer Gruppe zugehört und zur Unterkunft "alle Daten gleich" in der Gruppe ausgewählt
                // wurde, dann ist der Unterkunftsbuchungsdialog auf readOnly und bei readOnly Felder gibt es kein datePicker
                // -> dann unten alle Zusatzleistungen einfach anzeigen
                serviceUntil = datepicker.getDate();
            } else {
                // Wenn kein "Bis"-Datum angegeben wird, dann mit dem aktuellen Datum vergleichen unten
                serviceUntil = new Date();
            }
            if(item.find('.MealtypeSelect').val() > 0) {
                key = item.find('.accommodationSelect').val()+'_'+
                    item.find('.RoomtypeSelect').val()+'_'+
                    item.find('.MealtypeSelect').val();

                additionalServices = this.additionalservicesAccommodation;
            }

        }

        additionalServicesSelect = item.find('.additionalservices_select').get(0);

        if(additionalServicesSelect) {

            var options = [];
            var bookedAdditionalServiceIds = $j(additionalServicesSelect).val();

            additionalServices.forEach(function(additionalService) {
                if(
                    additionalService.keys &&
                    additionalService.keys.includes(key)
                ) {
                    const validUntil = new Date(additionalService.valid_until);
                    if (
                        !datepicker ||
                        additionalService.valid_until == '0000-00-00' ||
                        validUntil >= serviceUntil ||
                        bookedAdditionalServiceIds.includes(additionalService.id)
                    ) {
                        // Dialog ist readOnly (s.o.) ODER
                        // Zusatzleistung ist gültig ODER
                        // Wenn die Zusatzleistung gebucht wurde (obwohl sie eigentlich deaktiviert ist bzw.
                        // danach für diesen Zeitraum deaktiviert wurde)
                        // -> Trotzdem anzeigen für die "Historie"
                        options.push({text: additionalService.name, value: additionalService.id})
                    }
                }
            })

            this.updateSelectOptions(additionalServicesSelect, options, false, true);

        }

    },

    // Observer für TransferTab
    setTransferObserver : function (aData){

        // START Ausblenden des Gruppenfeldes
        if(
            $('save['+this.hash+']['+aData.id+'][transfer_mode][ts_ij]') &&
            aData.bDisableTransferSelect == 1
        ){
            $('save['+this.hash+']['+aData.id+'][transfer_mode][ts_ij]').addClassName('readonly');
            $('save['+this.hash+']['+aData.id+'][transfer_mode][ts_ij]').readOnly = true;
            $('save['+this.hash+']['+aData.id+'][transfer_mode][ts_ij]').disabled = true;
        }
        // ENDE

        // Start Select
        $$('#dialog_'+aData.id+'_'+this.hash+' .additional_transfer_from').each(function(oSelect){
            Event.stopObserving(oSelect, 'change');
            Event.observe(oSelect, 'change', function(e){
                this.checkAdditionalTransferSelect(oSelect);
                this.updateAdditionalTransferSelect(aData, oSelect);
            }.bind(this));

            this.checkAdditionalTransferSelect(oSelect);
            this.updateAdditionalTransferSelect(aData, oSelect);
        }.bind(this));

        // Ende Select
        $$('#dialog_'+aData.id+'_'+this.hash+' .additional_transfer_end').each(function(oSelect){
            Event.stopObserving(oSelect, 'change');
            Event.observe(oSelect, 'change', function(e){
                this.updateAdditionalTransferSelect(aData, oSelect);
            }.bind(this));
            this.updateAdditionalTransferSelect(aData, oSelect);
        }.bind(this));

        // START Transfer hinzufügen
        if($('add_new_transfer')){
            var oIcon = $('add_new_transfer');
            Event.stopObserving(oIcon, 'click');
            Event.observe(oIcon, 'click', function(e)
            {
                this.writeNewInquiryCourseOrAccommodation(e, aData, 'transfer');
            }.bind(this));
        }
        // ENDE

        // START Transfer entfernen
        $$('#dialog_'+aData.id+'_'+this.hash+' .transfer_block_remover').each(function(oInput){
            Event.stopObserving(oInput, 'click');
            Event.observe(oInput, 'click', function(e){

                // Prüfen ob gelöscht werden darf
                var iCheckDelete = this.checkDelete(e, aData, 'transfer');

                if(iCheckDelete == 0){
                    this.deleteInquiryCourseOrAccommodation(e, aData, 'transfer');
                }else{
                    var sError = '';
                    switch(iCheckDelete){
                        case 1:
                            sError = this.getTranslation('delete_position_payment');
                            break;
                    }

                    alert(sError);
                }
            }.bind(this));
        }.bind(this));
        // ENDE

        // Datumsfelder sperren wenn Input gesperrt
        $$('#dialog_'+aData.id+'_'+this.hash+' .input_transfer_date').each(function(oInput){
            if(oInput.hasClassName('readonly')){
                this.disableCalendarInput(oInput);
            }
        }.bind(this));

    },

    removeGroupCustomerAddRowObserver: function(oTr){

        if(oTr){

            var oName		= oTr.down('.name');
            var oFirstname	= oTr.down('.firstname');

            Event.stopObserving(oName, 'focus');
            Event.stopObserving(oFirstname, 'focus');

        }

    },

    setGroupCustomerAddRowObserver: function(oTr, aData) {

        oTr = $(oTr);

        if(oTr){
            var oName = oTr.down('.name');
            var oFirstname = oTr.down('.firstname');

            Event.observe(oName, 'focus', function(e){
                this.writeNewGroupCustomerLine(aData, oName);
            }.bind(this));

            Event.observe(oFirstname, 'focus', function(e){
                this.writeNewGroupCustomerLine(aData, oFirstname);
            }.bind(this));

        }

    },

    // Observer setzen für KundenTab bei Gruppen
    setCustomerObserver: function(aData){

        // TR clonen
        var oTr = $('tr_group_inquiry_0');

        this.removeGroupCustomerAddRowObserver(oTr);
        this.setGroupCustomerAddRowObserver(oTr, aData);

        // TR löschen von gespeicherten Kunden
        $$('#dialog_'+aData.id+'_'+this.hash+' .delete_group_customer_img').each(function(oImg) {
            Event.stopObserving(oImg, 'click');
            Event.observe(oImg, 'click', function(e) {
                if(
                    oImg.up('tr') &&
                    oImg.up('tr').id != ''
                ) {
                    var bDeleteCustomer = confirm(this.getTranslation('delete_customer_question'));

                    var aTemp = oImg.up('tr').id.split('_');
                    var iInquiryId = 0;

                    if(
                        aTemp[3] &&
                        aTemp[3] > 0
                    ) {
                        iInquiryId = aTemp[3];
                    }

                    var groupId = aData.id.replace('GROUP_', '');
                    var oTr = $j('#tr_group_inquiry_' + iInquiryId);
                    var oDeleteHidden = $j('#delete_group_inquiry_' + iInquiryId);

                    // Prüfen, ob es die letzte gespeicherte Buchung einer gespeicherte Gruppe ist -> Löschen verhindern
                    if(
                        groupId > 0 &&
                        iInquiryId > 0
                    ) {
                        var foundSavedBooking = false;
                        // Liste durchgehen und schauen, ob noch eine andere gespeicherte Buchung da ist.
                        $j('#dialog_'+aData.id+'_'+this.hash+' .customer_tr').each(function(i, groupMemberTr) {
                            // Zu löschenden Eintrag nicht checken
                            if(oTr.get(0) !== groupMemberTr) {
                                var groupMemberInquiryId = groupMemberTr.id.replace('tr_group_inquiry_', '');
                                if(
                                    groupMemberInquiryId > 0 &&
                                    $j('#delete_group_inquiry_'+groupMemberInquiryId).val() == '0'
                                ) {
                                    foundSavedBooking = true;
                                    return false;
                                }
                            }
                        });
                        if(foundSavedBooking === false) {
                            alert(this.getTranslation('delete_last_customer_error'));
                            return false;
                        }
                    }

                    oTr.hide();
                    oDeleteHidden.val('delete_relation');
                    if(bDeleteCustomer) {
                        oDeleteHidden.val('delete_customer');
                    }

                    // Schüler neu durchnummerieren
                    this.countGroupCustomers();

                }
                //this.checkGuideCheckbox(aData, oImg);
            }.bind(this));
        }.bind(this));

        // TR löschen von neuen Kunden
        $$('#dialog_'+aData.id+'_'+this.hash+' .delete_group_customer_new_img').each(function(oImg){
            Event.stopObserving(oImg, 'change');

            Event.observe(oImg, 'click', function(e){
                if(
                    oImg.previous('input') &&
                    oImg.up('tr')
                ){
                    oImg.previous('input').value = 0;
                    oImg.up('tr').hide();

                    // Kunden neu durchcounten
                    this.countGroupCustomers();
                }

            }.bind(this));
        }.bind(this));

        // Guide Checkboxen
        $$('#dialog_'+aData.id+'_'+this.hash+' .guide_checkbox').each(function(oCheckbox){
            Event.stopObserving(oCheckbox, 'change');
            Event.observe(oCheckbox, 'change', function(e){
                this.checkGuideCheckbox(aData, oCheckbox);
            }.bind(this));
            this.checkGuideCheckbox(aData, oCheckbox);
        }.bind(this));
    },


    //Disabled alle "Umsonst-checkboxen" in abhängichkeit von der Guide Checkbox
    checkGuideCheckbox : function(aData, oCheckbox){
        var aMatch = oCheckbox.id.match(/guide_checkbox_([-0-9].*)/);
        var iInquiryId = aMatch[1];

        $$('#dialog_'+aData.id+'_'+this.hash+' .customer_group_free_checkbox_'+iInquiryId).each(function(oCheckboxFree){

            if(oCheckbox.checked == true){
                oCheckboxFree.readOnly = false;
                oCheckboxFree.disabled = false;
            }else{
                oCheckboxFree.checked = false;
                oCheckboxFree.readOnly = true;
                oCheckboxFree.disabled = true;
            }
        }.bind(this));
    },

    // Funktion blendet die An-Abreisefelder ein je nach Dropdownwahl
    checkTransfer : function(aData, oSelect){
        var sTransfer = oSelect.value;

        if(sTransfer == 'arrival') {
            $('div_departure_data').hide();
            $('div_arrival_data').show();
        } else if(sTransfer == 'departure') {
            $('div_arrival_data').hide();
            $('div_departure_data').show();
        }else if(sTransfer == 'arr_dep') {
            $('div_arrival_data').show();
            $('div_departure_data').show();
        }else {
            $('div_arrival_data').hide();
            $('div_departure_data').hide();
        }

    },

    // updatet die Zusatzinfos der Transferselects
    updateAdditionalTransferSelect: function(aData, oSelect) {

        if(this.name === 'ts_enquiry_combination') {
            // Terminals existieren bei Kombinationen nicht
            return;
        }

        oSelect = jQuery(oSelect);
        var oTerminalSelect = oSelect.closest('.GUIDialogRow').next('.GUIDialogRow').find('select');

        // Beim initialen Öffnen stehen für das Setzen der Values alle Terminals im Select
        var iSelectedTerminal = oTerminalSelect.val();

        oTerminalSelect.empty();
        oTerminalSelect.closest('.GUIDialogRow').hide();

        if(oSelect.val() in aData.transfer_location_terminals) {
            jQuery.each(aData.transfer_location_terminals[oSelect.val()], function(iKey, sValue) {
                oTerminalSelect.append(jQuery('<option>', {
                    value: iKey,
                    text: sValue
                }));
            });

            oTerminalSelect.closest('.GUIDialogRow').show();
            if(oTerminalSelect.find('option[value=' + iSelectedTerminal + ']').length) {
                oTerminalSelect.val(iSelectedTerminal);
            } else {
                // Erstes Terminal auswählen
                oTerminalSelect.prop('selectedIndex', 0);
            }
            oTerminalSelect.effect('highlight');

        }

    },

    //Funktion blendet die weichen Matching Kriterien ein, sobald eine HostFamily gebucht wurde
    toggleSoftMatchingCriterion : function(aData){

        if(aData.aHostFamilies){

            // Felder die ausgeblendet werden
            var aHideFields = [];
            aHideFields[aHideFields.length] = 'cats';
            aHideFields[aHideFields.length] = 'dogs';
            aHideFields[aHideFields.length] = 'pets';
            aHideFields[aHideFields.length] = 'smoker';
            aHideFields[aHideFields.length] = 'distance_to_school';
            aHideFields[aHideFields.length] = 'air_conditioner';
            aHideFields[aHideFields.length] = 'bath';
            aHideFields[aHideFields.length] = 'family_age';
            aHideFields[aHideFields.length] = 'residential_area';
            aHideFields[aHideFields.length] = 'family_kids';
            aHideFields[aHideFields.length] = 'internet';

            var iValue = 0;
            var bHostFamilyFound = false;
            $$('#dialog_'+aData.id+'_'+this.hash+' .accommodationSelect').each(function(oSelect){

                iValue = oSelect.value;

                $H(aData.aHostFamilies).each(function(oArray, iIndex){
                    if(oArray[0] == iValue){
                        // gewählte Unterkunft ist eine HF
                        bHostFamilyFound = true;

                    }
                });
            }.bind(this));

            if(bHostFamilyFound && $('matching_soft')){
                $('matching_soft').show();
            } else if($('matching_soft')) {
                $('matching_soft').hide();
            }

            aHideFields.each(function(sField){
                var oInput = $('save['+this.hash+']['+aData.id+']['+sField+'][ts_i_m_d]');
                if(oInput){
                    if(!bHostFamilyFound){
                        oInput.up('.GUIDialogRow').hide();
                    }else{
                        oInput.up('.GUIDialogRow').show();
                    }
                }

            }.bind(this));

        }

    },

    // Funktion disabled Ziel des Transfers wenn dieses == Start ist
    checkAdditionalTransferSelect : function (oSelect){

        var iStart = oSelect.value, oSelectEnd;

        if(this.name === 'ts_enquiry_combination') {
            oSelectEnd = oSelect.up('.GUIDialogRow').next('.GUIDialogRow').down('select');
        } else {
            // Buchungsdialog: Von Zusatz überspringen
            oSelectEnd = oSelect.up('.GUIDialogRow').next('.GUIDialogRow').next('.GUIDialogRow').down('select');
        }

        if(oSelectEnd){
            var aOptions = oSelectEnd.childElements();
            aOptions.each(function(oOption){
                if(
                    oOption.value == iStart &&
                    iStart != 0
                ){
                    oOption.disabled = true;
                    // Wenn Option selektiert war dann auf 0 setzen
                    if(oOption.selected == true){
                        oSelectEnd.value = 0;
                    }
                }else{
                    oOption.disabled = false;
                }
            });

        }

    },

    /**
     * Druckansicht des Progressreport
     * Fenster darf nicht automatisch geschlossen werden, da es im Chrome sonst nicht klappt!
     */
    openProgressReportPrint : function(oEvent) {

        var oContainer = oEvent.currentTarget.up().up();

        var oWin = window.open('', 'printWindow', 'location=no,status=no,width=950,height=600,scrollbars=yes');

        var sHTML = '<html style="height: auto; width: auto;"><head>';
        sHTML += '<script type="text/javascript" src="/admin/js/prototype/prototype.js"></script>';
        sHTML += '<link type="text/css" rel="stylesheet" href="/admin/assets/fonts/inter/inter.css?v=1.814"" media="" />';
        sHTML += '<link type="text/css" rel="stylesheet" href="/admin/assets/interface/css/tailwind.css"" media="" />';
        sHTML += '<link type="text/css" rel="stylesheet" href="/admin/css/admin.css" media="" />';
        sHTML += '<link type="text/css" rel="stylesheet" href="/admin/extensions/gui2/gui2.css" media="" />';
        sHTML += '<link type="text/css" rel="stylesheet" href="/assets/ts/css/gui2.css" media="" />';
        sHTML += '<link type="text/css" rel="stylesheet" href="/assets/ts-tuition/css/progress_report.css" media="" />';
        sHTML += '<style>* { overflow: visible !important; }</style>';

        sHTML += '<title></title>';
        sHTML += '</head><body style="height: auto; width: auto;"><div class="GUIDialogContentPadding">';
        oWin.document.open();
        oWin.document.writeln(sHTML);
        oWin.document.write(oContainer.innerHTML);

        var sEndHtml = '';
        sEndHtml += '</div><script type="text/javascript">';
        sEndHtml += 'Event.observe(window, "load", function() { $(\'sr_progressreport_print\').hide(); $$(\'.toggle, .inner2\').each(function(oToggle) { oToggle.show(); }); self.print(); });';
        sEndHtml += '</script></body></html>';

        oWin.document.writeln(sEndHtml);
        oWin.document.close();
        oWin.focus();

    },

    // Observer für Generelle Felder/Persönliche Daten etc.
    setGeneralObserver: function (aData) {

        // Start: E-Mails (wiederholbar)
        var oEmailContainer = $j('#container_contact_email');

        if(
            oEmailContainer.length === 1 &&
            // Muss abgefragt werden, da das ansonsten beim Enquiry-Dialog immer wieder ausgeführt wird (SR hat keine sAction)
            oEmailContainer.data('new_counter') === undefined
        ) {
            oEmailContainer.data('new_counter', 0); // Negative IDs für neue E-Mails
            oEmailContainer.data('row_template', oEmailContainer.children('.GUIDialogRow').remove());

            aData.contact_emails.forEach(function(oEmail) {
                this.addStudentRecordEmailRow(oEmail.id, oEmail.email);
            }.bind(this));

            if(aData.contact_emails.length === 0) {
                this.addStudentRecordEmailRow();
            }

            $j('#add_new_email').click(function(e) {
                e.stopPropagation();
                this.addStudentRecordEmailRow();
            }.bind(this));

            // Readonly manuell behandeln: Buttons löschen (die Buttons sind nur DIV-Suppe, keine Buttons…)
            if(oEmailContainer.data('row_template').find('input').hasClass('readonly')) {
                $j('#add_new_email').remove();
                oEmailContainer.find('.remove_icon').remove();
            }
        }

        // Ende: E-Mails (wiederholbar)

        // START Nationalitäts Select Übertrag
        if(
            $('save['+this.hash+']['+aData.id+'][nationality][cdb1]') &&
            $('save['+this.hash+']['+aData.id+'][language][cdb1]') &&
            $F($('save['+this.hash+']['+aData.id+'][language][cdb1]')) == 0
        ) {
            if (aData['mothertongues_by_nationality']) {
                this.aMothertonguesByNationality = aData['mothertongues_by_nationality'];
            } else if (
                aData['optional'] &&
                aData['optional']['mothertongues_by_nationality']
            ) {
                this.aMothertonguesByNationality = aData['optional']['mothertongues_by_nationality'];
            }

        }

        // Gruppen
        if(
            $('save['+this.hash+']['+aData.id+'][nationality_id][kg]') &&
            $('save['+this.hash+']['+aData.id+'][language_id][kg]') &&
            $F($('save['+this.hash+']['+aData.id+'][language_id][kg]')) == 0
        ){
            var oNationality = $('save['+this.hash+']['+aData.id+'][nationality_id][kg]');

            Event.observe(oNationality, 'change', function() {
                this.request('&task=reloadMotherTongue&idNationality='+$F(oNationality));
            }.bind(this));
        }
        // ENDE Nationalitäts Select ##

        // START Muttersprachen Select übertrag
        // Einzelbuchung
        // if(
        // 	$('save['+this.hash+']['+aData.id+'][language][cdb1]') &&
        // 	$('save['+this.hash+']['+aData.id+'][corresponding_language][cdb1]')
        // ){
        // 	var oMothertonge = $('save['+this.hash+']['+aData.id+'][language][cdb1]');
        //
        // 	Event.observe(oMothertonge, 'change', function() {
        // 		this.request('&task=reloadKorrespondenceTongue&idMothertonge='+$F(oMothertonge));
        // 	}.bind(this));
        // }
        //
        // // Gruppen
        // if(
        // 	$('save['+this.hash+']['+aData.id+'][language_id][kg]') &&
        // 	$('save['+this.hash+']['+aData.id+'][correspondence_id][kg]')
        // 	){
        // 	var oMothertonge = $('save['+this.hash+']['+aData.id+'][language_id][kg]');
        //
        // 	Event.observe(oMothertonge, 'change', function() {
        // 		this.request('&task=reloadKorrespondenceTongue&idMothertonge='+$F(oMothertonge));
        // 	}.bind(this));
        // }
        // ENDE Muttersprachen Select ##

        // START Ausblenden des Gruppenfeldes
        if($('save['+this.hash+']['+aData.id+'][group_id][ki]')){
            $('save['+this.hash+']['+aData.id+'][group_id][ki]').addClassName('readonly');
            $('save['+this.hash+']['+aData.id+'][group_id][ki]').readOnly = true;
            $('save['+this.hash+']['+aData.id+'][group_id][ki]').disabled = true;
        }
        // ENDE

        // START Ausblenden des Transfer-typ selectes in allen Listen AUßER der Inbox
        if(
            this.sView != 'inbox' &&
            this.name !== 'ts_enquiry_combination' &&
            $('save['+this.hash+']['+aData.id+'][transfer_mode][ts_ij]')
        ){
            $('save['+this.hash+']['+aData.id+'][transfer_mode][ts_ij]').addClassName('readonly');
            $('save['+this.hash+']['+aData.id+'][transfer_mode][ts_ij]').readOnly = true;
            $('save['+this.hash+']['+aData.id+'][transfer_mode][ts_ij]').disabled = true;
        }
        // ENDE

        // START Ausblenden Agentur Selects wenn Gruppe
        if(aData.group_id > 0){
            if($('save['+this.hash+']['+aData.id+'][currency_id][ki]')){
                $('save['+this.hash+']['+aData.id+'][currency_id][ki]').addClassName('readonly');
                $('save['+this.hash+']['+aData.id+'][currency_id][ki]').disabled = true;
            }

            if($('save['+this.hash+']['+aData.id+'][agency_id][ki]')){
                $('save['+this.hash+']['+aData.id+'][agency_id][ki]').addClassName('readonly');
                $('save['+this.hash+']['+aData.id+'][agency_id][ki]').disabled = true;
            }

            if($('save['+this.hash+']['+aData.id+'][payment_method][ki]')){
                $('save['+this.hash+']['+aData.id+'][payment_method][ki]').addClassName('readonly');
                $('save['+this.hash+']['+aData.id+'][payment_method][ki]').disabled = true;
            }
        }
        // ENDE

        // START Tabs im Gruppendialog ausblenden für Kurs/Unterk/Trans ja nach Gruppeneinstellung
        var oSelectGroupCourse = $('save['+this.hash+']['+aData.id+'][course_data][kg]');
        if(oSelectGroupCourse){
            Event.observe(oSelectGroupCourse, 'change', function() {
                this.toggleGroupDialogTabs(aData, oSelectGroupCourse);
            }.bind(this));
            this.toggleGroupDialogTabs(aData, oSelectGroupCourse);
        }

        var oSelectGroupAccommodation = $('save['+this.hash+']['+aData.id+'][accommodation_data][kg]');
        if(oSelectGroupAccommodation){
            Event.observe(oSelectGroupAccommodation, 'change', function() {
                this.toggleGroupDialogTabs(aData, oSelectGroupAccommodation);
            }.bind(this));
            this.toggleGroupDialogTabs(aData, oSelectGroupAccommodation);
        }

        var oSelectGroupTransfer = $('save['+this.hash+']['+aData.id+'][transfer_data][kg]');
        if(oSelectGroupTransfer){
            Event.observe(oSelectGroupTransfer, 'change', function() {
                this.toggleGroupDialogTabs(aData, oSelectGroupTransfer);
            }.bind(this));
            this.toggleGroupDialogTabs(aData, oSelectGroupTransfer);
        }

        // Guide Tabs
        var oSelectGroupCourseGuide = $('save['+this.hash+']['+aData.id+'][course_guide][kg]');
        if(oSelectGroupCourseGuide){
            Event.observe(oSelectGroupCourseGuide, 'change', function() {
                this.toggleGroupDialogTabs(aData, oSelectGroupCourseGuide);
            }.bind(this));
            this.toggleGroupDialogTabs(aData, oSelectGroupCourseGuide);
        }

        var oSelectGroupAccommodationGuide = $('save['+this.hash+']['+aData.id+'][accommodation_guide][kg]');
        if(oSelectGroupAccommodationGuide){
            Event.observe(oSelectGroupAccommodationGuide, 'change', function() {
                this.toggleGroupDialogTabs(aData, oSelectGroupAccommodationGuide);
            }.bind(this));
            this.toggleGroupDialogTabs(aData, oSelectGroupAccommodationGuide);
        }

        // ENDE

        // START Matching Info Textfeld ausblenden
        if($('save['+this.hash+']['+aData.id+'][acc_address][ki]')){
            $('save['+this.hash+']['+aData.id+'][acc_address][ki]').addClassName('readonly');
            $('save['+this.hash+']['+aData.id+'][acc_address][ki]').readonly = 'readonly';
            $('save['+this.hash+']['+aData.id+'][acc_address][ki]').disabled = true;
        }
        // ENDE

        // START Special Informationen anzeigen
        var oSpecialInfo = $('general_special_info');
        if(
            aData.special_info &&
            aData.special_info != '' &&
            oSpecialInfo
        ){
            oSpecialInfo.insert({
                bottom: new Element('div').update(aData.special_info)
            });
            oSpecialInfo.show();
        }
        // ENDE

        // START Währungsselect verbergen
        var oSelectCurrency;

        oSelectCurrency = $('save['+this.hash+']['+aData.id+'][currency_id][ki]');

        if(!oSelectCurrency)
        {
            oSelectCurrency = $('save['+this.hash+']['+aData.id+'][currency_id][kg]');
        }

        if(
            oSelectCurrency &&
            aData.bDisableCurrencySelect == 1
        ){
            oSelectCurrency.addClassName('readonly');
            oSelectCurrency.readOnly = true;
            oSelectCurrency.disabled = true;
        }
        // ENDE

        // Schülerfoto
        this.updateStudentPhotoContainer(aData);
        // ENDE Schülerfoto

        $j('[data-toggle="tooltip"]').bootstrapTooltip();

        // Wenn Hubspot aktiviert ist
        if ($j('.hubspot-contact-search').length>0) {
            $j('.search-in-hubspot').click(function (e) {
                this.searchForHubspotContact(aData, $j(e.target).parents('.box-body').find('.customer_identification_results_field').get(0), 'searchInHubspot');
            }.bind(this));

            $j('.search-from-fidelo-to-hubspot').click(function (e) {
                this.searchForHubspotContact(aData, $j(e.target).parents('.box-body').find('.customer_identification_results_field').get(0), 'searchFromFideloToHubspot');
            }.bind(this));
        }

    },

    updateStudentPhotoContainer: function(aData) {

        $j('.student_personal_data_photo .box-body .photo').empty();

        if(aData.student_photo) {

            $j('<img />').attr({
                'src': aData.student_photo + '?no_cache',
                'class': 'student_photo img-responsive'
            }).appendTo('.student_personal_data_photo .box-body .photo');

        } else {

            $j('<i />').attr({
                'class': 'fa fa-user student-dummy'
            }).appendTo('.student_personal_data_photo .box-body .photo');

        }

        $j('.student_personal_data_photo button').click(function() {
            this.toggleDialogTabByClass('student_record_upload');
        }.bind(this));

    },

    clearStudentRecordEmailRows: function() {

        var oEmailContainer = $j('#container_contact_email');

        if(oEmailContainer.length === 0) {
            return;
        }

        var oDialogForm = $j('#dialog_form_' + this.sCurrentDialogId + '_' + this.hash);

        oEmailContainer.children('.GUIDialogRow').remove();

        if(oDialogForm.length === 1) {
            oDialogForm.find('input[name^="deleted[contact_email]"]').remove();
        }

    },

    /**
     * (Manueller) Wiederholbarer Bereich: E-Mail
     *
     * @param {Number} [iId]
     * @param {String} [sEmail]
     */
    addStudentRecordEmailRow: function(iId, sEmail) {

        var oDialogForm = $j('#dialog_form_' + this.sCurrentDialogId + '_' + this.hash);
        var oEmailContainer = $j('#container_contact_email');
        var oEmailRow = oEmailContainer.data('row_template').clone();

        oEmailRow.data('id', iId);
        oEmailContainer.append(oEmailRow);

        if(!iId) {
            iId = oEmailContainer.data().new_counter--;
        }

        var oInput = oEmailRow.find('input');
        oInput.attr('name', oInput.attr('name') + '[' + iId + ']');
        oInput.val(sEmail);

        oEmailRow.find('.remove_icon').click(function(e, bNoNewField) {
            e.stopPropagation();

            if(
                !bNoNewField &&
                oInput.val() !== '' &&
                !confirm(this.getTranslation('delete_email_question'))
            ) {
                return;
            }

            oEmailRow.remove();

            if(iId > 0 && !bNoNewField) {
                // Analog zu den Leistungen
                oDialogForm.append($j('<input>', {
                    type: 'hidden',
                    name: 'deleted[contact_email][' + iId + ']',
                    value: 1
                }));
            }

            // Sonderfall Kundenerkennung: Kein neues Feld wieder hinzufügen
            if(!bNoNewField) {
                if(oEmailContainer.children().length === 0) {
                    this.addStudentRecordEmailRow();
                }
            }

        }.bind(this));

    },

    // Toggelt Gruppendialog Tabs
    toggleGroupDialogTabs : function(aData, oSelect){
        var aMatch = oSelect.name.match(/save\[([a-z].*)\]\[([a-z].*)\]/);
        var sValue = oSelect.value;

        var oTabCourse							= $('tabHeader_1_'+aData.id+'_'+this.hash);
        var oTabCourseGuide						= $('tabHeader_2_'+aData.id+'_'+this.hash);
        var oTabAccommodation					= $('tabHeader_3_'+aData.id+'_'+this.hash);
        var oTabAccommodationGuide				= $('tabHeader_4_'+aData.id+'_'+this.hash);
        var oTabTransfer						= $('tabHeader_5_'+aData.id+'_'+this.hash);

        var oSelectGroupCourseGuide				= $('save['+this.hash+']['+aData.id+'][course_guide][kg]');
        var oSelectGroupAccommodationGuide		= $('save['+this.hash+']['+aData.id+'][accommodation_guide][kg]');


        if(aMatch[1] == 'course_data'){
            // Guide Select einblenden
            var oGuideSelect = $('save['+this.hash+']['+aData.id+'][course_guide][kg]');

            if(sValue == 'no'){
                //oTabCourse.hide();
                //oTabCourseGuide.hide();

                //oGuideSelect.up('.GUIDialogRow').hide();
            }else{
                //oTabCourse.show();
                //oTabCourseGuide.show();

                //oGuideSelect.up('.GUIDialogRow').show();

                // Guide Select
                //this.toggleGroupDialogTabs(aData, oSelectGroupCourseGuide);
            }
        }else if(aMatch[1] == 'accommodation_data'){
            // Guide Select einblenden
            var oGuideSelect = $('save['+this.hash+']['+aData.id+'][accommodation_guide][kg]');

            if(sValue == 'no'){
                //oTabAccommodation.hide();
                //oTabAccommodationGuide.hide();

                //oGuideSelect.up('.GUIDialogRow').hide();
            }else{
                //oTabAccommodation.show();
                //oTabAccommodationGuide.show();

                //oGuideSelect.up('.GUIDialogRow').show();

                // Guide Select
                //this.toggleGroupDialogTabs(aData, oSelectGroupAccommodationGuide);
            }
        } else if(aMatch[1] == 'transfer_data'){
            if(sValue == 'no'){
                //oTabTransfer.hide();
            }else{
                //oTabTransfer.show();
            }
        } else if(
            aMatch[1] == 'course_guide' &&
            oTabCourseGuide
        ) {
            if(sValue == 'equal'){
                oTabCourseGuide.hide();
            }else{
                oTabCourseGuide.show();
            }
        } else if(
            aMatch[1] == 'accommodation_guide' &&
            oTabAccommodationGuide
        ) {
            if(sValue == 'equal'){
                oTabAccommodationGuide.hide();
            }else{
                oTabAccommodationGuide.show();
            }
        }

    },

    disableAccRoomtype : function(oSelect, aData){

        if(aData.aAccRooms) {

            var iAccommodationCategoryId = $F(oSelect);

            // Raumselect
            var oRoomSelect = oSelect.up('.GUIDialogRow').next('.GUIDialogRow').down('.RoomtypeSelect');
            var aOptions = oRoomSelect.childElements();

            // ...erst alle freischallten
            aOptions.each(function(oOption){
                oOption.disabled = true;
                if(iAccommodationCategoryId == 0 && oOption.value == 0){
                    oOption.disabled = false;
                }
            });

            // Alle  Räume durchgehen und passende zur Unterkunft suchen
            $H(aData.aAccRooms).each(function(oRooms) {

                if(iAccommodationCategoryId == oRooms.key) {

                    // ... dann sperren
                    aOptions.each(function(oOption) {
                        // Aller erlaubten Räume herausfinden
                        oRooms.value.each(function(iRoom){
                            if(oOption.value == iRoom){
                                oOption.disabled = false;
                            }
                        });
                    });
                }
            }.bind(this));

            // prüfen das kein disablete Option gesetzt ist
            aOptions.each(function(oOption){
                if(
                    oOption.disabled == true &&
                    oOption.selected == true
                ) {
                    oOption.selected = false;
                }
            });

            // auch Verpflegungsselect anpassen
            this.disableAccMeal(oRoomSelect, aData);
        }
    },

    // zeigt zu einer Unterkunftskategorie die gespeicherte
    // Anreisezeit/Abreisezeit an
    /*showArrDepTime : function(oSelect, aData){
		var sFromId = oSelect.id.replace('[accommodation_id]', '');
		sFromId = sFromId + '[from_time]';

		var sUntilId = oSelect.id.replace('[accommodation_id]', '');
		sUntilId = sUntilId + '[until_time]';

		if(
			$(sFromId) &&
			$(sUntilId) &&
			aData.aAccTimes
		){

			$H(aData.aAccTimes).each(function(oTimes){
				if(oTimes.key == oSelect.value){
					if(oTimes.value.from){
						$(sFromId).value = oTimes.value.from;
					}else{
						$(sFromId).value = '';
					}
					if(oTimes.value.until){
						$(sUntilId).value = oTimes.value.until;
					}else{
						$(sUntilId).value = '';
					}
				}

			}.bind(this));

		}

	},*/

    disableAccMeal: function(oRoomSelect, oData) {

        oRoomSelect = $j(oRoomSelect);
        var oAccommodationSelect = oRoomSelect.parents('.GUIDialogRow').prev('.GUIDialogRow').find('.accommodationSelect');
        var oMealSelect = oRoomSelect.parents('.GUIDialogRow').next('.GUIDialogRow').find('.MealtypeSelect');
        var iSelectedRoom = oRoomSelect.val();

        // Zuerst alle Options deaktivieren
        oMealSelect.children().prop('disabled', true);

        // Wenn Unterkunft == Leereintrag: Verpflegungs-Leereintrag ist immer aktiv, ansonsten nicht
        if(oAccommodationSelect.val() == 0) {
            oMealSelect.children('[value=0]').prop('disabled', false);
        }

        if(!$j.isPlainObject(oData.aRoomMeals)) {
            return;
        }

        // Optionen wieder aktivieren sofern Settings passen
        $j.each(oData.aRoomMeals, function(iAccommodationCategoryId, oRoomData) {
            if(oAccommodationSelect.val() != iAccommodationCategoryId) {
                return true;
            }

            $j.each(oRoomData, function(iRoomId, aMealIds) {
                if(iSelectedRoom != iRoomId) {
                    return true;
                }

                aMealIds.forEach(function(iMealId) {
                    oMealSelect.children('[value=' + iMealId + ']').prop('disabled', false);
                });
            })
        });

        // Selektierte Option, die jetzt deaktiviert ist, deselektieren (nächste aktive Option wird ausgewählt!)
        oMealSelect.children('[disabled]:selected').prop('selected', false);

        this.updateAdditionalServices('accommodation', oMealSelect);

    },

    // Input für Enddatum berechnen
    getUntil : function(oInput, sTask){
        this.oLastFromObject = oInput;
        var iIdInputWeeks = oInput.id.replace('[from]', '[weeks]');
        var oWeeks = $(iIdInputWeeks);
        var iIdInputCourseId = oInput.id.replace('[from]', '[course_id]');
        var oCourseSelect = $(iIdInputCourseId);
        var iIdInputAccommodationId = oInput.id.replace('[from]', '[accommodation_id]');
        var oAccommodationSelect = $(iIdInputAccommodationId);
        var iKey = this.findKey(oInput);

        var bExecute = true;

        if(sTask == 'calculateUntil')
        {
            var oContainer = oWeeks.up('.GUIDialogRow');

            if(oContainer && oContainer.style.display && oContainer.style.display == 'none')
            {
                bExecute = false;
            }
        }

        var oFieldBirthday = this.getDialogSaveField('birthday', 'cdb1');

        var sParam = '';

        if(oCourseSelect)
        {
            sParam += '&'+iIdInputCourseId+'='+$F(oCourseSelect);
        }

        if(oAccommodationSelect)
        {
            sParam += '&'+iIdInputAccommodationId+'='+$F(oAccommodationSelect);
            sParam += '&category_id='+$F(oAccommodationSelect)
        }

        if(oFieldBirthday) {

            var oDatepicker = oFieldBirthday.data('datepicker');
            if(oDatepicker) {
                // oDatepicker.getDate() = null bei leerem Datum
                var iDiff = Date.now() - oDatepicker.getDate()?.getTime();
                var oAgeDate = new Date(iDiff);
                var iAge = Math.abs(oAgeDate.getUTCFullYear() - 1970);

                sParam += '&age=' + iAge;
            }

        }

        sParam += '&task=' + sTask;
        sParam += '&from=' + $F(oInput);
        sParam += '&weeks=' + $F(oWeeks);
        sParam += '&key=' + iKey;
        sParam += '&field_id=' + oInput.id;

        if(bExecute)
        {
            this.request(sParam, '', '', false, 0, false);
        }

    },

    findKey : function(oInput){

        var iSearchKey = 0;

        $$('.calculateCourseUntil').each(function(oInputSearch,iKey){
            if(oInputSearch===oInput){
                iSearchKey = iKey;
            }
        });

        $$('.calculateAccommodationUntil').each(function(oInputSearch,iKey){
            if(oInputSearch===oInput){
                iSearchKey = iKey;
            }
        });

        return iSearchKey;
    },

    // Input vorbereiten für Enddatum berechnen
    getPrepareUntil : function(oInput, sTask){

        if(this.calculantePrepareUntil){
            clearTimeout(this.calculantePrepareUntil);
        }

        this.calculantePrepareUntil = setTimeout(this.getUntil.bind(this), 800, oInput, sTask);
    },


    toggleAllDiscountRow : function(){

        $$('.DocumentDiscountRow').each(function(oTr){
            if(this.bShowDiscountRows == true){
                oTr.hide();
            }else{
                oTr.show();
            }
        }.bind(this));

        // switchen der Klassenvariablen damit beimnächsten mal anders geswitcht werden kann
        this.bShowDiscountRows = !this.bShowDiscountRows;
    },

    // Prüft ob eine Position gelöscht weren dürfen wel ggf. schon Zahlungen etc. existieren
    //  1 == Zahlungen vorhanden
    checkDelete : function (Event, aData, sType){

        var iBack = 0;

        if(sType == 'course') {
            var oDiv = Event.target.up('.InquiryCourseContainer');
        } else if(sType == 'accommodation') {
            var oDiv = Event.target.up('.InquiryAccommodationContainer');
        } else if(sType == 'transfer') {
            var oDiv = Event.target.up('.InquiryTransferContainer');
        }

        if(oDiv){
            var iInquiryPositionId = 0;

            var oContainer = $j(oDiv).children('div:not(.GUIDialogRow,.GUIDialogNotification)');

            if(oContainer.length) {

                if(
                    sType == 'course' ||
                    sType == 'course_guide'
                ) {
                    iInquiryPositionId = oContainer.attr('class').replace(/course_/, '');
                }else if (
                    sType == 'accommodation' ||
                    sType == 'accommodation_guide'
                ) {
                    iInquiryPositionId = oContainer.attr('class').replace(/accommodation_/, '');
                } else if (
                    sType == 'transfer'
                ) {
                    iInquiryPositionId = oContainer.attr('class').replace(/transfer_/, '');
                }

                if(iInquiryPositionId > 0){

                    if(
                        sType == 'course' &&
                        aData.aCoursePayments &&
                        aData.aCoursePayments[iInquiryPositionId]
                    ){
                        return 1
                    }else if(
                        sType == 'accommodation' &&
                        aData.aAccommodationPayments &&
                        aData.aAccommodationPayments[iInquiryPositionId]
                    ){
                        return 1
                    } else if(
                        sType == 'transfer' &&
                        aData.aTransferPayments &&
                        aData.aTransferPayments[iInquiryPositionId]
                    ){
                        return 1
                    }
                }

            }
        }


        return iBack;
    },

    // Löscht einen Kurs, Unterkunft, Transfer
    deleteInquiryCourseOrAccommodation : function(Event, aData, sType) {

        if(!sType) {
            sType = 'course';
        }

        if(sType == 'course') {
            var oDiv = Event.target.up('.InquiryCourseContainer');
        } else if(sType == 'accommodation') {
            var oDiv = Event.target.up('.InquiryAccommodationContainer');
        } else if(sType == 'transfer') {
            var oDiv = Event.target.up('.InquiryTransferContainer');
        } else if(sType == 'course_guide') {
            var oDiv = Event.target.up('.InquiryCourseGuideContainer');
        } else if(sType == 'accommodation_guide') {
            var oDiv = Event.target.up('.InquiryAccommodationGuideContainer');
        }

        if(oDiv)
        {
            if(this.bSkipConfirm || confirm(this.translations.delete_question_inquiry)) {

                var oContainer = $j(oDiv).children('div:not(.GUIDialogRow,.GUIDialogNotification,.box-separator)');

                if(oContainer.length) {

                    var sHidden;
                    var oForm = $('dialog_form_'+aData.id+'_'+this.hash);
                    if(
                        sType == 'course' ||
                        sType == 'course_guide'
                    ) {
                        var sInquiryCourseId = oContainer.attr('id').replace(/course_/, '');
                        sHidden = '<input type="hidden" name="deleted['+sType+']['+sInquiryCourseId+'][]" value="1" />';
                    }else if (
                        sType == 'accommodation' ||
                        sType == 'accommodation_guide'
                    ) {
                        var sInquiryAccommodationId = oContainer.attr('id').replace(/accommodation_/, '');
                        sHidden = '<input type="hidden" name="deleted['+sType+']['+sInquiryAccommodationId+'][]" value="1" />';
                    } else if (sType == 'transfer'){
                        var sInquiryTransferId = oContainer.attr('id').replace(/transfer_/, '');
                        sHidden = '<input type="hidden" name="deleted['+sType+']['+sInquiryTransferId+'][]" value="1" />';
                    }
                    oForm.insert({top: sHidden});

                }

                var aCourseContainer = $$('.InquiryCourseContainer');
                var iCourseContainer = $A(aCourseContainer).length;
                if(iCourseContainer > 1) {
                    aCourseContainer.each(function(oContainer) {
                        if(oContainer.hasClassName('InquiryCourseGuideContainer')) {
                            --iCourseContainer;
                        }
                    }.bind(this));
                }

                // Kurse
                if(sType == 'course' && iCourseContainer == 1)
                {
                    $A($$('.InquiryCourseContainer .txt')).each(function(oInput)
                    {
                        if(
                            !oInput.hasClassName('course_block_visibility') &&
                            !oInput.hasClassName('course_guide_block_visibility')
                        ){
                            oInput.value = '';
                            oInput.selectedIndex = 0;
                            oInput.checked = false;
                        }
                    });

                    // Titel löschen
                    $$('#dialog_'+aData.id+'_'+this.hash+' .course_block_title').each(function(oTitleDiv){
                        oTitleDiv.update();
                    }.bind(this));
                }
                // Guide Kurse
                else if(sType == 'course_guide' && $A($$('.InquiryCourseGuideContainer')).length == 1)
                {
                    $A($$('.InquiryCourseGuideContainer .txt')).each(function(oInput)
                    {
                        if(
                            !oInput.hasClassName('course_block_visibility') &&
                            !oInput.hasClassName('course_guide_block_visibility')
                        ){
                            oInput.value = '';
                            oInput.selectedIndex = 0;
                            oInput.checked = false;
                        }
                    });

                    // Titel löschen
                    $$('#dialog_'+aData.id+'_'+this.hash+' .course_guide_block_title').each(function(oTitleDiv){
                        oTitleDiv.update();
                    }.bind(this));
                }
                // Accommodation
                else if(sType == 'accommodation' && $A($$('.InquiryAccommodationContainer')).length == 1)
                {
                    $A($$('.InquiryAccommodationContainer .txt')).each(function(oInput)
                    {
                        if(
                            !oInput.hasClassName('accommodation_block_visibility') &&
                            !oInput.hasClassName('accommodation_guide_block_visibility')
                        ){
                            oInput.value = '';
                            oInput.selectedIndex = 0;
                            oInput.checked = false;
                        }
                    });

                    // Titel löschen
                    $$('#dialog_'+aData.id+'_'+this.hash+' .accommodation_block_title').each(function(oTitleDiv){
                        oTitleDiv.update();
                    }.bind(this));
                }
                // GUide Accommodation
                else if(sType == 'accommodation_guide' && $A($$('.InquiryAccommodationGuideContainer')).length == 1)
                {
                    $A($$('.InquiryAccommodationGuideContainer .txt')).each(function(oInput)
                    {
                        if(
                            !oInput.hasClassName('accommodation_block_visibility') &&
                            !oInput.hasClassName('accommodation_guide_block_visibility')
                        ){
                            oInput.value = '';
                            oInput.selectedIndex = 0;
                            oInput.checked = false;
                        }
                    });

                    // Titel löschen
                    $$('#dialog_'+aData.id+'_'+this.hash+' .accommodation_guide_block_title').each(function(oTitleDiv){
                        oTitleDiv.update();
                    }.bind(this));
                }
                // Transfer

                else if(sType == 'transfer' && $A($$('.InquiryTransferContainer')).length == 1)
                {
                    $A($$('.InquiryTransferContainer .txt')).each(function(oInput)
                    {
                        oInput.value = '';
                        oInput.selectedIndex = 0;
                        oInput.checked = false;
                    });
                }
                else
                {
                    oDiv.remove();
                }
            }
        }

    },

    // Fügt eine neue Buchungsposition hinzu : Kurs, Unterkunft, Transfer
    writeNewInquiryCourseOrAccommodation : function(Event, aData, sType){

        // TODO : Versicherungen-Tab ist sehr ähnlich aufgebaut, und sollte
        // später hier mitreingenommen werden. Ein Anfang ist hier bereits in IF's/ELSE's drin...
        if(!sType){
            sType = 'course';
        }

        var iTypeId = aData.inquiry_id; // Inquiry_id

        // Bei neuen Gruppen ist aData.inquiry_id = undefined und aData.group_id = 0 #11266
        if(
            aData.group_id || (
                aData.hasOwnProperty('group_id') &&
                aData.inquiry_id == null
            )
        ) {
            iTypeId = aData.group_id; // Bei gruppen ist hier die Gruppen ID
        }

        if(this.oLastInquiryContainerAddCount[sType] === 0) {
            this.oLastInquiryContainerAddCount[sType] = -1;

            if(sType === 'transfer') {
                this.oLastInquiryContainerAddCount[sType] = -3;
            }
        }

        var iCount = this.oLastInquiryContainerAddCount[sType];

        var oButtonDiv = Event.target.up('.GUIDialogRow');

        var oContainer;
        if(sType === 'course') {
            oContainer = oButtonDiv.previous('.InquiryCourseContainer');
            if(oContainer) {
                var oLevelSelect = oContainer.down('.course_level_select');
                // Level ID merken
                var iSelectedLevel = $F(oLevelSelect);
            } else {
                var iSelectedLevel = 0;
            }
        } else if(sType === 'course_guide'){
            oContainer = oButtonDiv.previous('.InquiryCourseGuideContainer');
        } else if(sType === 'accommodation') {
            oContainer = oButtonDiv.previous('.InquiryAccommodationContainer');
        } else if(sType === 'accommodation_guide'){
            oContainer = oButtonDiv.previous('.InquiryAccommodationGuideContainer');
        } else if(sType === 'activity') {
            oContainer = oButtonDiv.previous('.activity_container');
        } else if(sType === 'insurance') {
            oContainer = oButtonDiv.previous('.insurance_container');
        } else if(sType === 'transfer') {
            oContainer = oButtonDiv.previous('.InquiryTransferContainer');
        } else if(sType === 'sponsoring_gurantee') {
            oContainer = oButtonDiv.previous('.sponsoring_guarantee_container');
        }

        var oNewContainer = oContainer.clone(true);

        oNewContainer.down('.'+sType+'_block_title').update(this.getTranslation('new_'+sType));

        $j(oNewContainer).find('.GUIDialogNotification').remove();

        var sNewHtml = oNewContainer.innerHTML;
        var sTemp1 = 'id="'+sType+'['+iTypeId+']['+iCount+']';
        var sTemp2 = 'name="'+sType+'['+iCount+']';
        var sTemp3 = 'id="'+sType+'_'+iCount;
        var sTemp4 = 'class="'+sType+'_'+iCount;

        var oRegex4 = new RegExp("class=\""+sType+"_([\\-0-9]+)", "g");
        sNewHtml = sNewHtml.replace(oRegex4, sTemp4);

        var oRegex3 = new RegExp("id=\""+sType+"_([\\-0-9]+)", "g");
        sNewHtml = sNewHtml.replace(oRegex3, sTemp3);

        var oRegex1 = new RegExp("id=\""+sType+"\\[([0-9]+)\\]\\[([\\-0-9]+)\\]", "g");
        sNewHtml = sNewHtml.replace(oRegex1, sTemp1);

        var oRegex2 = new RegExp("name=\""+sType+"\\[([\\-0-9]+)\\]", "g");
        sNewHtml = sNewHtml.replace(oRegex2, sTemp2);

        oNewContainer.innerHTML = sNewHtml;

        /*
		oButtonDiv.insert({
			before:oContainer
		});*/
        oButtonDiv.insert({
            before:oNewContainer
        });

        oNewContainer.id = 'temp_calendar_search_id';
        // Kalender
        $$('#'+oNewContainer.id+' .calendar_input').each(function(oInput){
            this.prepareCalendar(oInput, oInput.next('.calendar_img'));

            if(oInput.next('img')){
                oInput.next('img').show();
            }

            if(oInput.hasClassName('readonly')){
                oInput.removeClassName('readonly');
                oInput.enable();
                oInput.readOnly = false;
            }

            // Zeitfelder entsperren (falls vorhanden)
            if(
                oInput.up() &&
                oInput.up().next('input') &&
                oInput.up().next('input').hasClassName('readonly')
            ){
                oInput.up().next('input').removeClassName('readonly');
                oInput.up().next('input').enable();
                oInput.up().next('input').readOnly = false;
            }

            if(
                oInput.previous('.GUIDialogRowWeekdayDiv')&&
                oInput.previous('.GUIDialogRowWeekdayDiv').hasClassName('readonly')
            ){
                oInput.previous('.GUIDialogRowWeekdayDiv').removeClassName('readonly')
            }
        }.bind(this));

        this.executeCalendars();
        this.initializeAutoheightTextareas(aData);

        // Input Felder löschen
        $$('#'+oNewContainer.id+' .txt').each(function(oInput){

            if(
                oInput.tagName == 'INPUT' &&
                oInput.type != 'hidden'
            ) {
                if(oInput.type === 'checkbox') {
                    oInput.checked = false;
                } else {
                    oInput.value = '';
                }

                // TODO Wird das noch benötigt? Das war wohl mal für Ferien gut, als Einträge komplett gesperrt wurden
                if(
                    oInput.hasClassName('readonly') &&
                    oInput.type !== 'file' // Sponsoring Gurantee
                ) {
                    oInput.removeClassName('readonly');
                    oInput.enable();
                    oInput.readOnly = false;
                }

                if(oInput.next('button')){
                    oInput.next('button').show();
                }
            }
        }.bind(this));

        // Textarea löschen
        $$('#'+oNewContainer.id+' textarea').each(function(oInput){
            oInput.update();
        }.bind(this));

        // Wochentag löschen
        $$('#'+oNewContainer.id+' .GUIDialogRowWeekdayDiv').each(function(oDiv){
            oDiv.update();
        }.bind(this));

        // Select löschen
        $$('#'+oNewContainer.id+' select').each(function(oSelect){
            // Wenn aktiv_select angegeben ist, dann Wert nicht zurücksetzen beim Kopieren
            if(!oSelect.hasClassName('activ_select')){
                oSelect.value = 0;
            }

            // TODO Wird das noch benötigt? Das war wohl mal für Ferien gut, als Einträge komplett gesperrt wurden
            if(oSelect.hasClassName('readonly')){
                oSelect.removeClassName('readonly');
                oSelect.enable();
                oSelect.readOnly = false;
            }

            if($j(oSelect).hasClass('jQm')) {
                $j(oSelect).parent().find('.ui-multiselect').remove();
            }

        }.bind(this));

        // Uploads löschen (Sponsoring Gurantees)
        $j('#' + oNewContainer.id + ' .gui2_upload_existing_files').each(function(iIndex, oDiv) {
            oDiv = $j(oDiv);
            oDiv.closest('.GUIDialogRowInputDiv').find('input[type="file"]').show();
            oDiv.remove();
        });

        if (sType === 'sponsoring_gurantee') {
            // Neuer Container ist kopie des Vorherigen, der das Upload Input eventuell ausgeblendet hat, weil
            // er ein File hat.
            $j('#' + oNewContainer.id).find('.gui2_upload').first().show();
        }

        // lösch button einblenden (falls ausgeblendet)
        $$('#'+oNewContainer.id+' .'+sType+'_block_remover').each(function(oBlockRemover){
            oBlockRemover.show();
        }.bind(this));
        // ... Seperator auch einblenden
        $$('#'+oNewContainer.id+' .divToolbarSeparator').each(function(oSeperator){
            oSeperator.show();
        }.bind(this));

        if(sType === 'course') {

            // Level auf das des letzten Kurses setzen
            //var sLevelSelectId = 'course['+iTypeId+']['+iCount+'][level_id]';
            //var oLevelSelect = $(sLevelSelectId);

            //if(oLevelSelect){
            //oLevelSelect.value = iSelectedLevel;
            //}

            this.iLastInquiryCourseAddCount = this.iLastInquiryCourseAddCount - 1;
            this.setCoursesObserver(aData);

            this.bindLevelEvents('.course_level_select');
            this.bindLevelEvents('.course_guide_level_select');

            // Kursinfo leeren
            $j('#'+oNewContainer.id+' .course_info_container').empty();
            // Kategorie anstoßen damit die Kurse wieder alle zur Verfügung stehen
            $j('#'+oNewContainer.id+' .course_category_select ').trigger('change');

        } else if(sType === 'accommodation') {
            this.setAccommodationObserver(aData);
        } else if(sType === 'activity') {
            this.setActivityObserver(aData);
        } else if(sType === 'insurance') {
        } else if(sType === 'transfer') {
            this.setTransferObserver(aData);
        } else if(sType === 'course_guide'){

            this.setCoursesObserver(aData);

            // Kursinfo leeren
            $j('#'+oNewContainer.id+' .course_info_container').empty();
            // Kategorie anstoßen damit die Kurse wieder alle zur Verfügung stehen
            $j('#'+oNewContainer.id+' .course_category_select ').trigger('change');

        } else if(sType === 'accommodation_guide'){
            this.setAccommodationObserver(aData);
        } else if(sType === 'sponsoring_gurantee') {
            this.setSponsoringObserver(aData);
        }

        oNewContainer.id = '';

        this.oLastInquiryContainerAddCount[sType] -= 1;

        this.initializeMultiselects(aData);

    },

    convertProformaDocument : function(iDocumentId){
        this.request('&task=convertProformaDocument&iDocumentId='+iDocumentId);
    },

    prepareDocument : function(sType, iDocumentId, iInquiryId){
        this.requestDialog('&task=openDialog&action=document_edit&type='+sType+'&iDocumentId='+iDocumentId, 'DOCUMENTS_LIST_'+iInquiryId);
    },

    reloadTemplateLanguageSelect : function(oTemplate){
        this.request('&task=reloadTemplateLanguageSelect&iTemplate='+$F(oTemplate));
    },

    reloadTemplateLanguageSelectCallback : function(aData){

        var aLanguages = aData.languages;
        var sSelected = aData.default_language;
        var sSchoolLang = aData.school_language;
        var sInformation = aData.info_html;
        var sPlaceholderHtml = aData.placeholder_html;

        if(sPlaceholderHtml) {
            var oTab = $j('#dialog_' + aData.id + '_' + this.hash + ' .GUIDialogTabContentDiv .tab_placeholder');
            if(oTab.length == 1) {
                oTab.find('div').html(sPlaceholderHtml);
                this.preparePlaceholderElement(aData);
            }
        }

        var oSelect = $('save['+this.hash+']['+aData.id+'][language]');
        var oTemplate = $('save['+this.hash+']['+aData.id+'][template_id]');
        var bFound = false;

        // Was sollte der Blödsinn mit if(oSelect)? Wenn das nicht da ist, stürzte das JS sowieso bei _fireEvent() ab.
        // if(oSelect){

        oSelect.innerHTML = '';
        aLanguages.each(function(aItem){
            var oOption = new Element('option');
            oOption.value = aItem[0];
            oOption.update(aItem[1]);
            if(sSelected == aItem[0]){
                oOption.selected = true;
                bFound = true;
            }
            oSelect.appendChild(oOption);
        }.bind(this));

        // Fehlermeldung löschen, damit diese nicht mehrfach angezeigt wird
        if($('error_template_language')){
            $('error_template_language').remove();
        }

        // Fehlemeldung anzeigen
        if(!bFound){
            oSelect.value = sSchoolLang;
            if($F(oTemplate) != 0){
                oSelect.up('.GUIDialogRow').insert({after:sInformation});
            }
        }

        // }

        this._fireEvent('change', oSelect);


    },

    reloadPositionsTable : function(aData, sDialogId, iDocumentId) {

        if(!aData.language || aData.language.length <= 0){
            return;
        }

        if(aData && !aData.inquirypositions_view){
            aData.inquirypositions_view = 0;
        }

        var sParam = '&task=reloadPositionsTable';
        sParam +=  '&sDialogId='+sDialogId;
        sParam +=  '&iDocumentId='+iDocumentId;

        sParam += this.getFilterparam(null, true);

        if(
            aData &&
            aData.form
        ){
            sParam +=  '&'+aData.form;
        }

        if(aData && aData.id){
            sParam +=  '&template_id='+aData.id;
        }
        if(aData && aData.id){
            sParam +=  '&inquirypositions_view='+aData.inquirypositions_view;
        }
        if(aData && aData.language){
            sParam +=  '&language='+aData.language;
        }
        if(aData && aData.document_type){
            sParam +=  '&document_type='+aData.document_type;
        }
        if(aData && aData.refresh){
            sParam +=  '&refresh='+aData.refresh;
        }
        if(aData && aData.negate){
            sParam +=  '&negate='+aData.negate;
        }

        if(aData && aData.change_user_signature==1){
            sParam +=  '&change_user_signature=1';
        }

        if($j('#dialog_' + sDialogId + '_' + this.hash + ' .GUIDialogTabDiv .tab_documents').length) {
            sParam += '&load_attached_documents=1';
        }

        this.request(sParam);

    },

    reloadPositionsTableCallback : function(aData) {

        if(aData.template_field_data.error) {
            this.displayErrors(aData.template_field_data.error);
        } else if(aData.error) {
            this.displayErrors(aData.error);
        } else {
            // Manche Dialoge in diesem Ablauf haben gar keine ID
            if(this.sCurrentDialogId) {
                this.removeErrors(this.sCurrentDialogId);
            }
        }

        // neuschreiben der Templatefelder
        var bWriteValues = true;

        if(
            aData.write_values == 0
        ){
            bWriteValues = false;
        }

        if(this.getDialog(aData.id)) {
            sDialogId = aData.id;
        } else {
            sDialogId = this.sCurrentDialogId;
        }

        this.toggleTemplateFields(
            aData.template_field_data,
            sDialogId,
            bWriteValues,
            aData.iDocumentId,
            aData.change_user_signature,
            aData.document_type
        );

        var sSaveIdPart = 'save['+this.hash+']['+aData.id+']';

        var oTableContainer = $(sSaveIdPart+'[positionsTable]');

        if(
            oTableContainer &&
            aData.html &&
            aData.update == true
        ){
            oTableContainer.update(aData.html);
        }

        this.setPaymentTermsEvents();

        // Editierbare Layout-Boxen müssen angezeigt und mit Daten gefüllt werden
        if(
            aData &&
            aData.editable_fields &&
            aData.editable_fields.length > 0
        ){
            this.showEditableFields(aData.id, aData.editable_fields, aData.editable_field_data);
        }

        // Gesamtbetrag Spalte
        if(aData.total_amount_column) {
            this.sTotalAmountColumn = aData.total_amount_column;
        }

        // Tooltips
        this.aPositionsTooltips = {};
        if(aData.position_tooltips) {
            this.aPositionsTooltips = aData.position_tooltips;
        }

        // Observer setzen für Dokumente
        if(aData.inquirypositions_view != 0) {
            this.setDocumentObserver();
        }

        if(aData.documents_tab) {
            var oTab = $j('#dialog_' + aData.id + '_' + this.hash + ' .GUIDialogTabContentDiv .tab_documents');
            if(oTab.length == 1) {
                oTab.find('div').html(aData.documents_tab);
            }
        }

        if(aData.numberrange) {
            oNumberrangeSelect = $('save['+this.hash+']['+sDialogId+'][numberrange_id]');
            if(oNumberrangeSelect) {
                this.updateSelectOptions(oNumberrangeSelect, aData.numberrange.options, true, true);
                $j(oNumberrangeSelect).val(aData.numberrange.default);
            }
        }
        this.resizeDialogSize(aData);
    },

    toggleTemplateFields : function(aData, sDialogId, bWriteValues, iDocumentId, iChangeSignature, sDocumentType) {

        if(!aData || aData == null || aData.length <= 0){
            // kein Template gewählt also Fixe Elemente verstecken
            if($('document_template_items')){
                $('document_template_items').hide();
            }
            return;
        }

        // Fixe Elemente einblenden
        if($('document_template_items')){
            $('document_template_items').show();
        }

        if(bWriteValues !== false){
            bWriteValues = true;
        }

        var sSaveIdPart = 'save['+this.hash+']['+sDialogId+']';

        if($(sSaveIdPart+'[date]')){

            if(
                aData &&
                aData['element_date'] == 1
            ) {
                $(sSaveIdPart+'[date]').up('.templateField').show();
                if(
                    !$(sSaveIdPart+'[date]').hasClassName('required') &&
                    sDocumentType != 'additional_document'
                ){
                    $(sSaveIdPart+'[date]').addClassName('required');
                }
                if(bWriteValues && !$(sSaveIdPart+'[date]').hasClassName('readonly')){
                    this.updateCalendarValue($(sSaveIdPart+'[date]'), aData['element_date_html']);
                }
            } else {
                $(sSaveIdPart+'[date]').up('.templateField').hide();
                $(sSaveIdPart+'[date]').removeClassName('required');
            }
        }

        if($(sSaveIdPart+'[address]')){
            if(aData && aData['element_address'] == 1){
                $(sSaveIdPart+'[address]').up('.templateField').show();
                if(bWriteValues){
                    $(sSaveIdPart+'[address]').updateValue(aData['element_address_html'], false);
                }
            } else {
                $(sSaveIdPart+'[address]').up('.templateField').hide();
            }
        }

        if($(sSaveIdPart+'[subject]')){
            if(aData && aData['element_subject'] == 1){
                $(sSaveIdPart+'[subject]').up('.templateField').show();
                if(bWriteValues){
                    $(sSaveIdPart+'[subject]').value = aData['element_subject_html'];
                }
            }else {
                $(sSaveIdPart+'[subject]').up('.templateField').hide();
            }
        }

        if($(sSaveIdPart+'[intro]')){
            if(aData && aData['element_text1'] == 1){
                $(sSaveIdPart+'[intro]').up('.templateField').show();

                if(bWriteValues) {
                    $(sSaveIdPart+'[intro]').updateValue(aData['element_text1_html'], false);
//					var oEditor = tinyMCE.get(sSaveIdPart + '[intro]');
//					oEditor.setContent(aData['element_text1_html']);
                    //var x = $(oEditor.editorContainer);
                    //x.show();
                }
            } else {
                $(sSaveIdPart+'[intro]').up('.templateField').hide();
            }
        }

        if($(sSaveIdPart+'[positionsTable]')){
            var oInvoiceSelect = this.getDialogSaveField('invoice_select');
            if(aData && aData['inquirypositions_view'] > 0) {
                // Positionstabelle
                $(sSaveIdPart+'[positionsTable]').show();
                // Anzahlung Restbetrag
                if($(sSaveIdPart+'[paymentDueTable]')){
                    $(sSaveIdPart+'[paymentDueTable]').show();
                }
                oInvoiceSelect.closest('.GUIDialogRow').show();
            } else {
                // Positionstabelle
                $(sSaveIdPart+'[positionsTable]').hide();
                // Anzahlung Restbetrag
                if($(sSaveIdPart+'[paymentDueTable]')){
                    $(sSaveIdPart+'[paymentDueTable]').hide();
                }
                oInvoiceSelect.closest('.GUIDialogRow').hide();
            }
        }

        if($(sSaveIdPart+'[paymentDueTable]') && $(sSaveIdPart+'[paymentDueTable]').hasClassName('do_not_display')){
            $(sSaveIdPart+'[paymentDueTable]').hide();
        }

        if($(sSaveIdPart+'[outro]')){
            if(aData && aData['element_text2'] == 1){
                $(sSaveIdPart+'[outro]').up('.templateField').show();
                if(bWriteValues) {
                    $(sSaveIdPart+'[outro]').updateValue(aData['element_text2_html'], false);
//					var oEditor = tinyMCE.get(sSaveIdPart+'[outro]');
//					oEditor.setContent(aData['element_text2_html']);
                }
            } else {
                $(sSaveIdPart+'[outro]').up('.templateField').hide();
            }
        }

        if($(sSaveIdPart+'[signature_txt]')){
            if(
                aData &&
                (
                    bWriteValues ||
                    iChangeSignature == 1
                ) &&
                aData['element_signature_text'] == 1
            ){
                $(sSaveIdPart+'[signature_txt]').up('.GUIDialogRow').show();
                $(sSaveIdPart+'[signature_txt]').updateValue(aData['signatur_text_html'], false);
//				var oEditor = tinyMCE.get(sSaveIdPart+'[signature_txt]');
//				oEditor.setContent(aData['signatur_text_html']);
            } else if(
                aData &&
                aData['element_signature_text'] == 0
            ) {
                $(sSaveIdPart+'[signature_txt]').up('.GUIDialogRow').hide();
            }
        }

        if($(sSaveIdPart+'[signature_img]')){
            if(
                aData &&
                (
                    bWriteValues ||
                    iChangeSignature == 1
                ) &&
                aData['element_signature_img'] == 1
            ){
                $(sSaveIdPart+'[signature_img]').up('.GUIDialogRow').show();
                $(sSaveIdPart+'[signature_img]').value = aData['signatur_img_html'];
            } else if(
                aData &&
                aData['element_signature_img'] == 0
            ) {
                $(sSaveIdPart+'[signature_img]').up('.GUIDialogRow').hide();
            }
        }

        var oUserSignatureSelect = $(sSaveIdPart+'[signature_user_id]');
        if(oUserSignatureSelect){
            if(
                aData &&
                (
                    aData['element_signature_text'] == 1 ||
                    aData['element_signature_img'] == 1
                ) &&
                (
                    bWriteValues ||
                    iChangeSignature == 1
                )
            ){
                if(aData['user_signature']==1){
                    var oFirstOption = oUserSignatureSelect.firstChild;
                    if(oFirstOption.value==0){
                        this.deletedSignatureOption = oFirstOption;
                        oUserSignatureSelect.removeChild(oFirstOption);
                    }
                }else{
                    var oFirstOption = oUserSignatureSelect.firstChild;
                    if(oFirstOption.value!=0){
                        oUserSignatureSelect.insertBefore(this.deletedSignatureOption,oFirstOption);
                    }
                }
                oUserSignatureSelect.up('.GUIDialogRow').show();
                oUserSignatureSelect.value = aData['signature_user_id'];
            } else if(
                aData &&
                aData['element_signature_img'] == 0 &&
                aData['element_signature_text'] == 0
            ) {
                $(sSaveIdPart+'[signature_user_id]').up('.GUIDialogRow').hide();
            }
        }

    },

    // Schreibt die Enddaten in die Until Felder
    // Returned Obj des Enddatums
    writeUntilDate : function(aData){

        var oInput;
        var sUntil = aData.to;
        // Kurse/Unterkünfte
        var iIdUntil = this.oLastFromObject.id.replace('[from]', '[until]');

        if($(iIdUntil)) {
            oInput = $(iIdUntil);
            this.updateCalendarValue(oInput, sUntil);
            return oInput;
        }

    },

    autodiscoverMotherTongue: function() {

        var oLanguage = $('save['+this.hash+']['+this.sCurrentDialogId+'][language][cdb1]');

        // Wenn schon eine Muttersprache gewählt ist, abbrechen
        if($F(oLanguage) != 0) {
            return;
        }

        // Einzelbuchung
        var oNationality = $('save['+this.hash+']['+this.sCurrentDialogId+'][nationality][cdb1]');

        if(this.aMothertonguesByNationality[$F(oNationality)]) {
            this.reloadMotherTongue({id: this.sCurrentDialogId, idMothertonge: this.aMothertonguesByNationality[$F(oNationality)]});
        }

    },

    reloadMotherTongue : function(aData){

        var sDialogId = aData.id;

        var sMothertongue = aData.idMothertonge;

        if($('save['+this.hash+']['+aData.id+'][language][cdb1]')){
            // Einzelbuchung
            var oSelect = $('save['+this.hash+']['+aData.id+'][language][cdb1]');
        }else if($('save['+this.hash+']['+aData.id+'][language_id][kg]')){
            // Gruppenbuchung
            var oSelect = $('save['+this.hash+']['+aData.id+'][language_id][kg]');
        }else{
            return;
        }

        var aOptions = oSelect.childElements();

        aOptions.each(function(oOption){
            if(sMothertongue == ''){
                oOption.selected = false;
            }else if(oOption.value == sMothertongue){
                oOption.selected = true;
            }
        });

        // Auch Korrespondenzsprache nachladen wenn möglich
        // if(
        // 	$('save['+this.hash+']['+aData.id+'][corresponding_language][cdb1]')
        // ){
        // 	this.requestDialog('&task=reloadKorrespondenceTongue&idMothertonge='+$F(oSelect), sDialogId);
        // }
        //
        // if(
        // 	$('save['+this.hash+']['+aData.id+'][correspondence_id][kg]')
        // ){
        // 	this.requestDialog('&task=reloadKorrespondenceTongue&idMothertonge='+$F(oSelect), sDialogId);
        // }
    },

    reloadKorrespondenceTongue : function(aData){

        var sCorrespondencetongue = aData.idCorrespondence;

        if($('save['+this.hash+']['+aData.id+'][corresponding_language][cdb1]')){
            var oSelect = $('save['+this.hash+']['+aData.id+'][corresponding_language][cdb1]');
        }else if($('save['+this.hash+']['+aData.id+'][correspondence_id][kg]')){
            var oSelect = $('save['+this.hash+']['+aData.id+'][correspondence_id][kg]');
        }else{
            return;
        }
        var aOptions = oSelect.childElements();

        aOptions.each(function(oOption){
            if(sCorrespondencetongue == ''){
                oOption.selected = false;
            }else if(oOption.value == sCorrespondencetongue){
                oOption.selected = true;
            }
        });

    },

    writeInsuranceMask: function(aData) {

        var oCopy = $('insurance_container_0').clone(true);

        var aContainer = $A($$('.insurance_container'));

        oCopy.id = 'insurance_container_' + aContainer.length;

        var iInquiryID = 0;
        if(this.selectedRowId !== null) {
            var iInquiryID = this.selectedRowId[0];
        }

        var iNewContainer = (aContainer.length + 1);

        oCopy.down('.insurance_ids').id = 'insurance[' + iInquiryID + '][id][' + iNewContainer + ']';
        oCopy.down('.insurance_ids').name = 'insurance[' + iInquiryID + '][id][' + iNewContainer + ']';
        oCopy.down('.insurance_ids').value = '0';
        oCopy.down('.insurance_weeks').id = 'insurance[' + iInquiryID + '][weeks][' + iNewContainer + ']';
        oCopy.down('.insurance_weeks').name = 'insurance[' + iInquiryID + '][weeks][' + iNewContainer + ']';
        oCopy.down('.insurance_weeks').value = '';
        oCopy.down('.recalculate_insurance_enddate').id = 'insurance[' + iInquiryID + '][refresh][' + iNewContainer + ']';
        oCopy.down('.recalculate_insurance_enddate').name = 'insurance[' + iInquiryID + '][refresh][' + iNewContainer + ']';
        oCopy.down('.insurance_froms').id = 'insurance[' + iInquiryID + '][from][' + iNewContainer + ']';
        oCopy.down('.insurance_froms').name = 'insurance[' + iInquiryID + '][from][' + iNewContainer + ']';
        oCopy.down('.insurance_froms').value = '';
        oCopy.down('.insurance_untils').id = 'insurance[' + iInquiryID + '][until][' + iNewContainer + ']';
        oCopy.down('.insurance_untils').name = 'insurance[' + iInquiryID + '][until][' + iNewContainer + ']';
        oCopy.down('.insurance_untils').value = '';
        oCopy.down('.insurance_block_visibility').id = 'insurance[' + iInquiryID + '][visible][' + iNewContainer + ']';
        oCopy.down('.insurance_block_visibility').name = 'insurance[' + iInquiryID + '][visible][' + iNewContainer + ']';

        if(oCopy.down('.insurance_hiddens')) {
            oCopy.down('.insurance_hiddens').name = 'insurance[' + iInquiryID + '][update][' + iNewContainer + ']';
            oCopy.down('.insurance_hiddens').value = 0;
        }

        var oAddDIV = $('add_new_insurance').up('.GUIDialogRow');
        oAddDIV.insert({before: oCopy});

        this.setInsuranceObserver(aData);

    },

    setActivityObserver: function(aData) {

        // Aktivitäten-Tab da?
        if(!aData.activity_config) {
            return;
        }

        const activitySelects = $j('#dialog_'+aData.id+'_'+this.hash+' .activity_ids');
        const courseIds =  $j('.courseSelect').get().map(e => $j(e).val());

        for (const [id, config] of Object.entries(aData.activity_config)) {
            const hasCourse = !config.course_ids || config.course_ids.some(c => courseIds.includes(c));
            activitySelects.children(`option[value=${id}]`).prop('disabled', !hasCourse);
        }

        activitySelects.change(e => {
            const select = $j(e.currentTarget);
            const config = aData.activity_config[select.val()] ?? {};
            const blockInputRow = $j(e.currentTarget).closest('.activity_container').find('.row_activity_blocks');
            config.billing_period === 'payment_per_block' ? blockInputRow.show() : blockInputRow.hide();
        }).trigger('change');

        var aContainer = $j('.activity_container');
        aContainer.each(function(iIndex, oContainer) {
            oContainer = $j(oContainer);
            var oRemoveButton = oContainer.find('.activity_block_remover');
            oRemoveButton.off('click').click(function() {
                if(
                    this.bSkipConfirm ||
                    confirm(this.translations.delete_question_inquiry)
                ) {
                    var sHidden = '<input type="hidden" name="deleted[activity][' + oContainer.find('.activity_hiddens').val() + '][]" value="1" />';
                    $j('#add_new_activity').closest('.GUIDialogRow').before(sHidden);
                    if(iIndex > 0) {
                        oContainer.remove();
                    } else {
                        $j(oContainer).find('select[name*=activity_id]').val('0').change();
                        $j(oContainer).find('input, textarea').val('');
                    }
                }
            }.bind(this));
        }.bind(this));

        $$('#dialog_'+aData.id+'_'+this.hash+' .recalculate_activity_enddate').each(function(oButton){
            Event.observe(oButton, 'click', function() {
                var sId = oButton.id.replace(/\[refresh]/, '[from]');
                var oInput = $(sId);
                this.getUntil(oInput, 'calculateUntil');
            }.bind(this));
        }.bind(this));

        this.executeCalendars();
        this.initializeAutoheightTextareas(aData);

    },

    setSponsoringObserver: function(aData) {

        $j('.sponsoring_guarantee_container').each(function(iIndex, oContainer) {
            oContainer = $j(oContainer);
            oContainer.find('.sponsoring_gurantee_block_remover').off('click').click(function() {
                var sHidden = '<input type="hidden" name="deleted[sponsoring_guarantee][' + oContainer.find('.sponsoring_guarantee_hiddens').val() + '][]" value="1" />';
                $j('#add_new_sponsoring_gurantee').closest('.GUIDialogRow').before(sHidden);
                if(iIndex > 0) {
                    oContainer.remove();
                } else {
                    oContainer.find('input, textarea').val('');
                    oContainer.find('.gui2_upload_existing_files').remove();
                    oContainer.find('input[type="file"]').show();
                }
            });
        });

    },

    setInsuranceObserver: function(aData)
    {
        // START Versicherungsende ermitteln
        // $$('#dialog_'+aData.id+'_'+this.hash+' .insurance_froms').each(function(oInput){
        // 	Event.observe(oInput, 'change', function() {
        // 		this.getUntil(oInput, 'calculateInsuranceUntil', aData);
        // 	}.bind(this));
        // }.bind(this));
        // ENDE

        var aContainer = $A($$('.insurance_container'));

        var iInquiryID = 0;
        if(this.selectedRowId != null)
        {
            var iInquiryID = this.selectedRowId[0];
        }

        aContainer.each(function(oContainer)
        {
            var iContainerID = parseInt(oContainer.id.replace(/insurance_container_/, ''));

            // this.showInsuranceWeeksInput(oContainer, aData);

            /* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Title

            // Warum wurde das denn gemacht?
//			if(iContainerID > 0)
//			{
//				var oInsuranceBlockTitle = oContainer.down('.insurance_block_title');
//				if(oInsuranceBlockTitle){
//					oInsuranceBlockTitle.innerHTML = '';
//				}
//			}

            /* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // From

            if(oContainer.down('.calendar_img')){
                var oCalendarFrom = oContainer.down('.calendar_img');

                oCalendarFrom.stopObserving('click');

                oCalendarFrom.id = 'calendar_id_' + parseInt(iContainerID * 2);

                this.prepareCalendar(oContainer.down('.insurance_froms'), oCalendarFrom);
            }

            /* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Till

            if(oContainer.down('.calendar_img', 1)){
                var oCalendarTill = oContainer.down('.calendar_img', 1);

                oCalendarTill.stopObserving('click');

                oCalendarTill.id = 'calendar_id_' + parseInt(iContainerID * 2 + 1);

                this.prepareCalendar(oContainer.down('.insurance_untils'), oCalendarTill);
            }

            /* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Insurance DD

            // Muss unter from/until passieren, da this.showInsuranceWeeksInput den Kalender benötigt
            var oInsuranceSelect = $j(oContainer).find('.insurance_ids');
            oInsuranceSelect.off('change');
            oInsuranceSelect.change(function() {
                this.showInsuranceWeeksInput(oContainer, aData);
            }.bind(this));
            oInsuranceSelect.change();

            /* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */ // Remove

            if(oContainer.down('.insurance_block_remover')){
                oContainer.down('.insurance_block_remover').stopObserving('click');

                Event.observe(oContainer.down('.insurance_block_remover'), 'click', function(e)
                {
                    if(this.bSkipConfirm || confirm(this.translations.delete_question_inquiry))
                    {
                        var sHidden = '<input type="hidden" name="deleted[insurance][' + oContainer.down('.insurance_hiddens').value + '][]" value="1" />';

                        var oAddDIV = $('add_new_insurance').up('.GUIDialogRow');
                        oAddDIV.insert({before: sHidden});

                        if(iContainerID > 0) {
                            oContainer.remove();
                        } else {

                            oContainer.down('.insurance_ids').selectedIndex = 0;
                            oContainer.down('.insurance_weeks').value = '';
                            oContainer.down('.insurance_froms').value = '';
                            oContainer.down('.insurance_untils').value = '';
                        }
                    }
                }.bind(this));
            }

        }.bind(this));

        $$('#dialog_'+aData.id+'_'+this.hash+' .recalculate_insurance_enddate').each(function(oButton){
            Event.observe(oButton, 'click', function() {
                var sId = oButton.id.replace(/\[refresh]/, '[from]');
                var oInput = $(sId);
                this.getUntil(oInput, 'calculateUntil');
            }.bind(this));
        }.bind(this));

        this.executeCalendars();
        this.initializeAutoheightTextareas(aData);

    },

    showInsuranceWeeksInput: function(oContainer, aData)
    {
        for(var n = 0; n < aData['aInsurancesTypeLinks'].length; n++)
        {
            if(aData['aInsurancesTypeLinks'][n]['id'] == oContainer.down('.insurance_ids').value)
            {
                if(aData['aInsurancesTypeLinks'][n]['payment'] == 3)
                {
                    oContainer.down('.insurance_ids').up('.GUIDialogRow').next().show();
                    // oContainer.down('.insurance_untils').readOnly = true;
                    // // Nicht besser lösbar mit diesem Datepicker
                    // $j(oContainer.down('.insurance_untils')).bootstrapDatePicker('setDaysOfWeekDisabled', [0, 1, 2, 3, 4, 5, 6]);

                    $$('#dialog_'+aData.id+'_'+this.hash+' .recalculate_insurance_enddate').each(function(oButton){
                        Event.observe(oButton, 'click', function() {
                            var sId = oButton.id.replace(/\[refresh]/, '[from]');
                            var oInput = $(sId);
                            this.getUntil(oInput, 'calculateUntil');
                        }.bind(this));
                    }.bind(this));

                } else {
                    oContainer.down('.insurance_ids').up('.GUIDialogRow').next().hide();
                    // oContainer.down('.insurance_untils').readOnly = false;
                    // $j(oContainer.down('.insurance_untils')).bootstrapDatePicker('setDaysOfWeekDisabled', []);
                }

                break;
            }
        }
    },


    writeNewGroupCustomerLine : function(aData, oElement){

        if(!this.iLastNewGroupCount){
            this.iLastNewGroupCount = 0;
        }

        // Inquiry rausfinden
        var aMatch = oElement.name.match(/customer\[([-0-9].*)\]\[([a-z].*)\]/);

        // Vor/Nachname Felder
        var oFirstname = $('customer['+aMatch[0]+'][firstname]');
        var oLastname = $('customer['+aMatch[0]+'][lastname]');

        if(
            oFirstname &&
            oLastname &&
            $F(oLastname) == '' &&
            $F(oFirstname) == ''
        ){
            return;
        }

        this.iLastNewGroupCount = parseInt(this.iLastNewGroupCount);

        var oTr = oElement.up('tr');

        // START Nummer incrementieren
        var oNewTr = oTr.clone(true);

        var iCustomerCount = oNewTr.down('td').innerHTML;
        if(iCustomerCount == ''){
            iCustomerCount = 1;
        }else{
            iCustomerCount = parseInt(iCustomerCount);
        }

        oNewTr.down('td').innerHTML = (iCustomerCount + 1);
        // ENDE

        var sTempHtml = oNewTr.innerHTML;

        var iNewGroupCount = (this.iLastNewGroupCount-1);

        var sNewHtml = sTempHtml.replace(/customer\[[\-0-9]+\]/g, 'customer['+iNewGroupCount+']');
        sNewHtml = sNewHtml.replace(/guide_checkbox_[\-0-9]+/g, 'guide_checkbox_'+iNewGroupCount);

        sNewHtml = sNewHtml.replace(/customer_group_free_checkbox_[\-0-9]+/g, 'customer_group_free_checkbox_'+iNewGroupCount);
        sNewHtml = sNewHtml.replace(/inquiry_flag\[[\-0-9]+\]/g, 'inquiry_flag['+iNewGroupCount+']');
        //sNewHtml = sNewHtml.replace(/calendar\[[\-0-9]+\]/g, 'calendar['+iNewGroupCount+']');
        sNewHtml = sNewHtml.replace(/calendarimg\[[\-0-9]+\]/g, 'calendarimg['+iNewGroupCount+']');

        oNewTr.innerHTML = sNewHtml;
        oNewTr.className = 'tr['+iNewGroupCount+'][tr] tr_'+iNewGroupCount+' customer_tr';

        oTr.insert({
            after:oNewTr
        });
        oTr.id = null;
        oTr.removeClassName('customer_tr');

        $('customer['+iNewGroupCount+'][data]').setValue('');
        // Werte auf 0 setzen bei neuer Reihe
        $j('.tr_'+iNewGroupCount+' select').prop('selectedIndex', 0);

        this.iLastNewGroupCount--;

        // Kalendar setzen
        var oInputBirthday = $(oElement.up('tr').next('tr').down('td').next('.groupCustomerBirthday').down('input').id);
        this.prepareCalendar(oInputBirthday, oInputBirthday.next('.calendar_img'));
        this.executeCalendars();

        // Observer setzen
        this.setCustomerObserver(aData);

        this.removeGroupCustomerAddRowObserver(oTr);
        this.setGroupCustomerAddRowObserver(oNewTr, aData);
    },

    writeExtraposition : function (oButton) {

        if(!this.iExtrapositionCounter) {
            this.iExtrapositionCounter = 0;
        }

        var iPositionKey = 'EP'+this.iExtrapositionCounter;

        this.iExtrapositionCounter++;

        var oRow = $('position_row_XXX').clone(true);

        oRow.id = 'position_row_'+iPositionKey;

        $('position_row_XXX').insert({before: oRow});

        $(oRow.id).show();
        var aElements = $(oRow.id).descendants();

        var oIconActive;
        aElements.each(function(oElement){
            if(oElement.hasClassName('onPdf')) {
                oIconActive = oElement;
            }
            if(oElement.id) {
                oElement.id = oElement.id.replace(/XXX/, iPositionKey);
            }
            if(oElement.name) {
                oElement.name = oElement.name.replace(/XXX/, iPositionKey);
            }
        });

        var iSelectedItem = $F('add_position');
        var iCount = 0;

        var sDescription = $('add_position').options[$('add_position').selectedIndex].text;

        if(iSelectedItem == 0) {
            $('type_'+iPositionKey).value = 'extraPosition';
            iCount = this.iCountGuides + this.iCountOthers;
        } else {

            var aData = this.aAdditionalCosts[iSelectedItem];

            if(aData) {

                $('description_'+iPositionKey).updateValue(aData.name);

                var iFactor = 1;

                if(aData.tax_category) {
                    if ($j('#tax_category_'+iPositionKey).prop('disabled')) {
                        /* Bei disabled select wird der Wert im input nicht gesetzt, weil das nur passiert,
						   wenn active checkbox deaktiviert wird. Manuell setzen. */
                        $j('#tax_category_'+iPositionKey+' option').removeAttr('selected');
                        $j('#tax_category_'+iPositionKey+' option[value="'+aData.tax_category+'"]').attr('selected', 'selected');
                        $j('input[name="position['+iPositionKey+'][tax_category]"]').val(aData.tax_category);
                    } else {
                        $j('#tax_category_'+iPositionKey).val(aData.tax_category);
                    }
                }

                if(
                    aData.group_option == 1 &&
                    this.bGroup == 1
                ) {
                    // Für jedes Gruppenmitglied
                    iCount = this.iCountGuides + this.iCountOthers;
                    iFactor = this.iCountGuides + this.iCountOthers;
                } else if(
                    aData.group_option == 2 &&
                    this.bGroup == 1
                ) {
                    // Für jedes Gruppenmitglied außer Leader
                    iCount = this.iCountOthers;
                    iFactor = this.iCountOthers;
                } else if(
                    aData.group_option == 4 &&
                    this.bGroup == 1
                ){
                    // Nie für Gruppe
                    iFactor = 0;
                    iCount = this.iCountGuides + this.iCountOthers;
                    sDescription += ' (' + this.getTranslation('free') + ')';
                } else {
                    iCount = this.iCountGuides + this.iCountOthers;
                }

                $('amount_'+iPositionKey).updateValue(this.thebingNumberFormat(aData.price * iFactor));

                if(aData.provision) {
                    var oInput = $('amount_provision_'+iPositionKey);
                    if(oInput) {
                        oInput.updateValue(this.thebingNumberFormat(aData.provision * iFactor));
                    }
                }

                if(
                    aData.initalcost &&
                    aData.initalcost == 1
                ){
                    $('initalcost_'+iPositionKey).checked = true;
                }
            }

            $('type_'+iPositionKey).value = 'additional_general';

            $('description_'+iPositionKey).updateValue(sDescription);
        }

        // Wenn kein Leistungszeitraum vorhanden: Versionsdatum nehmen
        if (
            !$j('#index_from_' + iPositionKey).val() ||
            !$j('#index_until_' + iPositionKey).val()
        ) {
            var date = this.getDialogSaveField('date').data('datepicker').getFormattedDate('yyyy-mm-dd');
            $j('#index_from_' + iPositionKey).val(date);
            $j('#index_until_' + iPositionKey).val(date);
        }

        if($('count_'+iPositionKey)) {
            $('count_'+iPositionKey).innerHTML = iCount+' / '+iCount;
        }

        $('type_id_'+iPositionKey).value = iSelectedItem;

        oIconActive.checked = true;
        this.togglePositionActive(oIconActive, iPositionKey);

        this.recalculatePosition(iPositionKey);

        var sPrefix = '#position_row_'+iPositionKey;

        // Observer für Dokument neu setzen
        this.setDocumentObserver(sPrefix);

    },


    // Funktion liefert den Leistungszeitraum aller gebuchten (ausgewählten) Leistungen zurück ja nach Type
    getPeriodData : function (aData, sType, sParams){

        if (typeof sParams === 'undefined') {
            sParams = ''
        }

        var sClassUntil			= '';
        var sClassTo			= '';
        var sTask				= '';
        var sClassWeek			= '';
        var sClassActive		= '';
        var sClassActiveOther	= '';

        // Anhand der Field ID muss bestimmt werden um welchhen Feld Typen es sich handelt das ist wichtig
        // Für Gruppen und Guides
        var sFieldType = 'course';
        if(aData.field_id){
            var aMatch = aData.field_id.split('[');
            sFieldType = aMatch[0];
        }

        // Evtl müssen andere Klassen verwendet werden, bei Gruppen
        var oInputCourseGuides = $('save['+this.hash+']['+aData.id+'][course_guide][kg]');
        var oInputAccommodationGuides = $('save['+this.hash+']['+aData.id+'][accommodation_guide][kg]');

        if(sType == 'course'){
            sClassUntil			= 'calculateCourseUntil';
            sClassTo			= 'calculateCourseTo';
            sClassWeek			= 'courseWeeks';
            sClassActive		= 'course_block_visibility';
            sClassActiveOther	= 'course_guide_block_visibility';

            sTask = 'getCoursePeriod';

            var aAccommodationUpdatePrefixes = ['accommodation'];

            if(oInputAccommodationGuides){
                var sGuideSelectAccommodation = $F(oInputAccommodationGuides);
                var sGuideSelectCourse = $F(oInputCourseGuides);

                if (sFieldType === 'course_guide') {
                    aAccommodationUpdatePrefixes = ['accommodation_guide']
                } else if (
                    sGuideSelectAccommodation === 'different' &&
                    sGuideSelectCourse === 'equal'
                ) {
                    aAccommodationUpdatePrefixes = ['accommodation', 'accommodation_guide']
                }

                if (sGuideSelectAccommodation == 'different') {
                    if(sFieldType == 'course'){
                        sClassUntil			= 'calculateCourseUntilNormal';
                        sClassTo			= 'calculateCourseToNormal';
                        sClassActive		= 'course_block_visibility';
                        sClassActiveOther	= 'course_block_visibility';
                    }else if(sFieldType == 'course_guide'){
                        sClassUntil			= 'calculateCourseUntilGuide';
                        sClassTo			= 'calculateCourseToGuide';
                        sClassActive		= 'course_guide_block_visibility';
                        sClassActiveOther	= 'course_guide_block_visibility';
                    }
                }else if(sGuideSelectCourse == 'equal'){
                    sClassUntil			= 'calculateCourseUntilNormal';
                    sClassTo			= 'calculateCourseToNormal';
                    sClassActive		= 'course_block_visibility';
                    sClassActiveOther	= 'course_block_visibility';
                }
            }

            aAccommodationUpdatePrefixes.forEach((sAccommodationPrefix) => {
                if (this.oLastInquiryContainerAddCount && this.oLastInquiryContainerAddCount.hasOwnProperty(sAccommodationPrefix)) {
                    // Alle hinzugefügten Unterkünfte die noch kein Startdatum haben mitschicken
                    for (var index = this.oLastInquiryContainerAddCount[sAccommodationPrefix]; index <= 0; index++) {
                        var accommodation, accommodationFrom;
                        if(oInputAccommodationGuides) {
                            accommodation = $(sAccommodationPrefix + '[' + aData.id.replace('GROUP_', '') + '][' + index + '][accommodation_id]');
                            accommodationFrom = $(sAccommodationPrefix + '[' + aData.id.replace('GROUP_', '') + '][' + index + '][from]');
                        } else {
                            accommodation = $(sAccommodationPrefix + '[' + aData.id.replace('ID_', '') + '][' + index + '][accommodation_id]');
                            accommodationFrom = $(sAccommodationPrefix + '[' + aData.id.replace('ID_', '') + '][' + index + '][from]');
                        }
                        if (
                            accommodation && accommodationFrom &&
                            // TODO auch befüllen wenn "accommodationFrom" befüllt ist?
                            $j(accommodation).val() > 0 && $j(accommodationFrom).val() === ''
                        ) {
                            sParams += '&'+sAccommodationPrefix+'['+index+']='+$j(accommodation).val();
                        }
                    }
                }
            })

        } else if(sType == 'accommodation') {
            sClassUntil = 'calculateCourseUntil';
            sClassTo = 'calculateCourseTo';

            if (oInputAccommodationGuides) {
                var sGuideSelectCourse = $F(oInputCourseGuides);

                if(sFieldType == 'accommodation_guide') {
                    if (sGuideSelectCourse === 'different') {
                        sClassUntil			= 'calculateCourseUntilGuide';
                        sClassTo			= 'calculateCourseToGuide';
                    } else {
                        sClassUntil			= 'calculateCourseUntilNormal';
                        sClassTo			= 'calculateCourseToNormal';
                    }
                } else {
                    sClassUntil			= 'calculateCourseUntilNormal';
                    sClassTo			= 'calculateCourseToNormal';
                }
            }

            sTask = 'getAccommodationPeriod';
        } else if(sType == 'transfer') {
            sClassUntil = 'calculateAccUntil';
            sClassTo = 'calculateAccTo';
            sClassWeek = 'accommodationWeeks';

            sTask = 'getDatesForTransfer';
        } else if(sType == 'accommodation_room_sharing'){
            sClassUntil = 'calculateAccUntil';
            sClassTo = 'calculateAccTo';

            sTask = 'searchRoomSharingCustomers';
        }

        var aPeriodTimeFrom = [];
        var i = 0;
        $$('#dialog_'+aData.id+'_'+this.hash+' .'+sClassUntil).each(function(oInput){
            aPeriodTimeFrom[i] = $F(oInput);
            i++;
        }.bind(this));

        var aPeriodTimeUntil = [];
        i = 0;
        $$('#dialog_'+aData.id+'_'+this.hash+' .'+sClassTo).each(function(oInput){
            aPeriodTimeUntil[i] = $F(oInput);
            i++;
        }.bind(this));

        //Wochen auch mitschicken
        var aWeeks = [];
        i = 0;
        if(sClassWeek != ''){
            $$('#dialog_'+aData.id+'_'+this.hash+' .'+sClassWeek).each(function(oInput){
                aWeeks[i] = $F(oInput);
                i++;
            }.bind(this));
        }

        // Aktivfeld mitschicken
        var aActive = [];
        i = 0;
        if(sClassActive != ''){
            $$(
                '#dialog_'+aData.id+'_'+this.hash+' .'+sClassActive,
                '#dialog_'+aData.id+'_'+this.hash+' .'+sClassActiveOther
            ).each(function(oSelect){
                aActive[i] = $F(oSelect);
                i++;
            }.bind(this));
        }

        var sTimeFrom		= JSON.stringify(aPeriodTimeFrom);
        var sTimeUntil		= JSON.stringify(aPeriodTimeUntil);
        var sWeeks			= JSON.stringify(aWeeks);
        var sActive			= JSON.stringify(aActive);

        var sRequest = '&task='+sTask;
        sRequest += '&sFrom='+sTimeFrom;
        sRequest += '&sUntil='+sTimeUntil;
        sRequest += '&aWeeks='+sWeeks;
        sRequest += '&aActive='+sActive;
        sRequest += '&sFieldtype='+sFieldType;
        sRequest += sParams;

        this.request(sRequest, '', '', false, 0, false);
    },

    // Request ableiten damit (falls vorhanden) die gewähle schule mitgeschickt wird
    // ist wichtig da diese sich von der aktuellen bzw. der bei der inquiry gespeicherten unterscheiden kann
    request : function ($super, mParam, sUrl, sHash, bAsNewWindow, iCurrentId, bShowLoading, bCallback, bDownload) {

        var iSchool = 0;
        $$('.school_select').each(function(oSchool) {
            iSchool = $F(oSchool);
        });

        var sParam = '&school_for_data=' + iSchool;
        if(typeof mParam == 'object') {
            this.appendFormData(mParam, sParam);
        } else {
            mParam += sParam;
        }

        $super(mParam, sUrl, sHash, bAsNewWindow, iCurrentId, bShowLoading, bCallback, bDownload);

    },

    // Funktion läd die Agenturabhängigen Felder im SR nach
    reloadAgencyDependingFields : function(aData, bDisablePaymentMethod, sAlias){
        // felder die neu geladen werden sollen

        if(!sAlias) {
            sAlias = this.sAlias;
        }

        var oAgency = $('save['+this.hash+']['+aData.id+'][agency_id]['+sAlias+']');
        var oPaymentMethode = $('save['+this.hash+']['+aData.id+'][payment_method]['+sAlias+']');
        var oPaymentMethodeComment = $('save['+this.hash+']['+aData.id+'][payment_method_comment]['+sAlias+']');

        if(
            oAgency &&
            oPaymentMethode &&
            oPaymentMethodeComment
        ){

            if(
                $F(oAgency) <= 0 &&
                bDisablePaymentMethod == 1
            ){
                // Beim Dialog öffnen
                this.disablePaymentMethod(oPaymentMethode);
            }

            if(bDisablePaymentMethod != 1){
                // Beim Agenturwechseln
                if(
                    aData['agency_currency'][$F(oAgency)] &&
                    aData['agency_currency'][$F(oAgency)] > 0
                ){
                    this.changeCurrency(aData['agency_currency'][$F(oAgency)], aData.id, sAlias);
                }

                this.writePaymentMethodData(oAgency, oPaymentMethode, oPaymentMethodeComment, aData['agency_payment_method']);
            }

        }


        // Gruppen Dialog - Agenturmitarbeiter

        var oGroupAgencySelect = $('save['+this.hash+']['+aData.id+'][agency_id][kg]');

        if(
            oGroupAgencySelect &&
            aData['agency_contacts'] &&
            aData['agency_contacts'][$F(oGroupAgencySelect)]
        ) {
            var sAgencyContactSelect = oGroupAgencySelect.id.replace('agency_id', 'agency_contact_id');
            var oAgencyContact = $(sAgencyContactSelect);
            if(oAgencyContact) {
                var aSelectOptions = aData['agency_contacts'][$F(oGroupAgencySelect)];
                this.updateSelectOptions(oAgencyContact, aSelectOptions, true, true);
            }
        }

    },

    bindLevelEvents: function(sLevelSelectClass) {

        var cGetCourseLevelGroupId = function(oSelect) {
            var oCourseSelect = $j('#' + $j.escapeSelector(oSelect.attr('id').replace('level_id', 'course_id')));
            return this.aBundledCourseLevels[oCourseSelect.val()];
        }.bind(this);

        $j(sLevelSelectClass).change(function(oSelect) {
            oSelect = $j(this);

            $j(sLevelSelectClass).each(function() {
                var oSelectOther = $j(this);

                if(oSelect.is(oSelectOther)) {
                    return true;
                }

                var iLevelGroupId = cGetCourseLevelGroupId(oSelect);
                var iLevelGroupOtherId = cGetCourseLevelGroupId(oSelectOther);
                if(iLevelGroupId === iLevelGroupOtherId) {
                    oSelectOther.val(oSelect.val());
                    oSelectOther.get(0).highlight();
                }
            });
        });

    },

    // Funktionen die beim update icon callback ausgeführt werden sollen
    updateIconsCheck : function(aData){
        // Unterkunftskommunikationsliste
        var aIcons = [];
        aIcons[0] = $('confirm_customer_agency__'+this.hash);
        aIcons[1] = $('confirm_provider__'+this.hash);
        var bShowLable1 = false;
        aIcons[2] = $('revoce_customer_agency__'+this.hash);
        aIcons[3] = $('revoce_provider__'+this.hash);
        var bShowLable2 = false;

        aIcons.each(function(oIconDiv, iCount){
            if(oIconDiv){
                var oDiv2 = oIconDiv.up('.guiBarElement');
                if(oDiv2.hasClassName('guiBarInactive')){
                    oDiv2.hide();
                }else{
                    oDiv2.show();
                    if(
                        iCount == 0 ||
                        iCount == 2
                    ){
                        bShowLable1 = true;
                    }else{
                        bShowLable2 = true;
                    }

                }
            }
        });

        if(
            aIcons[0] &&
            aIcons[1]
        ){
            var oDiv2 = aIcons[0].up('.guiBarElement');
            var oLabel2 = oDiv2.previous('.divToolbarLabelGroup');
            var oSeperator2 = oDiv2.previous('.divToolbarSeparator');

            var oDiv3 = aIcons[1].up('.guiBarElement');
            var oLabel3 = oDiv3.previous('.divToolbarLabelGroup');
            var oSeperator3 = oDiv3.previous('.divToolbarSeparator');

            if(bShowLable1){
                oLabel2.show();
                oSeperator2.show();
            }else{
                oLabel2.hide();
                oSeperator2.hide();
            }

            if(bShowLable2){
                oLabel3.show();
                oSeperator3.show();
            }else{
                oLabel3.hide();
                oSeperator3.hide();
            }
        }


    },

    showEditableFields : function(sDialogId, aEditableFields, aEditableFieldData){

        // Alle Felder löschen damit sie neu geschrieben werden können
        $$('#dialog_'+sDialogId+'_'+this.hash+' .editable_field').each(function(oInput) {
            var sName = oInput.name;
            var aMatch = sName.match(/\[([a-z].*)_([a-z].*)_([a-z].*)_([0-9].*)\]/);
            if(aMatch[4] > 0) {
                if(aMatch[2] == 'html') {
                    this.removeEditor(oInput.id);
                }
                oInput.up('.GUIDialogRow').remove();
            }
        }.bind(this));

        // Container einblenden
        var bShow = false;
        var oEditableFieldContainer = $('editable_field_container');

        var oInsertRow = false;

        aEditableFields.each(function(aFieldData) {

            var sFieldId		= aFieldData.id;
            var sFieldType		= aFieldData.type;
            var sFieldName		= aFieldData.name;
            var sDefaultValue	= aFieldData.value;

            var oCloneRow = '';
            var oField = '';
            var sType = '';

            // zu klonende Elemente herausfinden
            if(sFieldType == 'html') {
                if($('save['+this.hash+']['+sDialogId+'][editable_html_field_0]')){
                    oField = $('save['+this.hash+']['+sDialogId+'][editable_html_field_0]');
                    oCloneRow = oField.up('.GUIDialogRow');
                    sType = 'textarea';
                }
            } else if(sFieldType == 'date') {
                if($('save['+this.hash+']['+sDialogId+'][editable_date_field_0]')){
                    oField = $('save['+this.hash+']['+sDialogId+'][editable_date_field_0]');
                    oCloneRow = oField.up('.GUIDialogRow');
                    sType = 'input';
                }
            } else if(sFieldType == 'text') {
                if($('save['+this.hash+']['+sDialogId+'][editable_text_field_0]')){
                    oField = $('save['+this.hash+']['+sDialogId+'][editable_text_field_0]');
                    oCloneRow = oField.up('.GUIDialogRow');
                    sType = 'input';
                }
            }

            if(!oInsertRow) {
                oInsertRow = oCloneRow;
            }

            // Wenn Zeile gefunden dann klonen
            if(oCloneRow != ''){
                // Hauptcontainer nachher einblenden
                bShow = true;

                var oNewRow = oCloneRow.clone(true);
                // id / name umschreiben
                var oNewField = oNewRow.down(sType);
                oNewField.id	= 'save['+this.hash+']['+sDialogId+'][editable_'+sFieldType+'_field_'+sFieldId+']';
                oNewField.name	= 'save[editable_'+sFieldType+'_field_'+sFieldId+']';
                // Label umschreiben
                var oLabel = oNewRow.down('.GUIDialogRowLabelDiv');
                oLabel.update(sFieldName);

                // Felddaten in Felder schreiben
                // Prüfen ob Feld gesetzt wurde bzw. vorhanden ist
                var bFieldValueSet = false;
                $H(aEditableFieldData).each(function(oArray){
                    if(oArray.key == sFieldId){
                        if(sFieldType == 'date') {
                            oNewField.value = oArray.value;
                            bFieldValueSet = true;
                        } else if(sFieldType == 'html') {
                            oNewField.value = oArray.value;
                            bFieldValueSet = true;
                        } else if(sFieldType == 'text') {
                            oNewField.value = oArray.value;
                            bFieldValueSet = true;
                        }

                    }

                }.bind(this));

                // Wenn kein Value gefunden wurde dann das Default Value schreiben
                if(!bFieldValueSet) {
                    oNewField.value = sDefaultValue;
                }

                // Zeile einfügen
                oNewRow.show();
                oInsertRow.insert({
                    before: oNewRow
                });

                // Wenn Calender dann initialisieren
                if(
                    sFieldType == 'date' &&
                    oNewField.next('img')
                ) {

                    oNewField.next('img').id = 'save['+this.hash+']['+sDialogId+'][calendar][editable_'+sFieldType+'_field_'+sFieldId+']';

                    this.prepareCalendar(oNewField, oNewField.next('img'));

                } else if(sFieldType == 'html'){
                    // html Editor initialisieren
                    oNewField.addClassName('GuiDialogHtmlEditor');
                }

            }

        }.bind(this));

        // Calender starten wenn vorhanden
        this.executeCalendars();

        /**
         * Diese Zeile hat Probleme verursacht, weil der Editor zum 2.Mal gestartet wurde und tinyMCE
         * 2 hidden spans für den Inhalt mit der gleichen ID erstellt hatte (_parent)
         * Siehe T-2688
         */
        // html editor starten wenn vorhanden
        this.pepareHtmlEditors(sDialogId);

        // Container einblenden mit allen Feldern

        if(oEditableFieldContainer){
            if(bShow){
                oEditableFieldContainer.show();
            }else{
                oEditableFieldContainer.hide();
            }

        }

    },

    //Observer für Progress-Report
    initProgressReportObserver: function(){

        // Drucken Button
        if(
            $('sr_progressreport_print')
        ){
            Event.observe($('sr_progressreport_print'), 'click', function(oEvent) {
                this.openProgressReportPrint(oEvent);
            }.bind(this));
        }

        var oToggle;
        var oParent;

        $$('.allocation .ui-icon').each(function(oImg){

            Event.observe(oImg,'click',function(){

                var oParent = this.up('.second');
                var oToggle	= false;
                if(oParent){
                    oToggle = oParent.next('.toggle');
                }

                if(oToggle){
                    if(oToggle.style.display=='none' || oToggle.style.display==''){
                        oToggle.style.display = 'block';
                    }else{
                        oToggle.style.display = 'none';
                    }
                } else {
                    if($j(oImg).parent().hasClass('inner-toggle2')) {
                        $j(oImg).parent().nextAll('.inner2')[0].toggle();
                    }
                }
            });

        });
    },

    // Gruppenkunden wieder neu durchzählen im Dialog
    countGroupCustomers : function(){

        var iCount = 0;

        $$('.customer_tr').each(function(oCustomerTr){

            if(
                oCustomerTr.down('td') &&
                oCustomerTr.visible()
            ){
                iCount++;
                oCustomerTr.down('td').update(iCount);
            }

        });

    },

    // Errechnet die Divhöhe des Rechnungsübersichts Dialogs neu
    calculateDocumentHistoryOverview : function(iDialogId){

        var oHistoryDiv = $('dialog_document_history');

        if(oHistoryDiv) {

            var oDialog = this.getDialog(iDialogId);

            var iDialogLegendHeight = 0;

            if(jQuery(oHistoryDiv).parent().find('.divToolbar')) {
                iDialogLegendHeight = jQuery(oHistoryDiv).parent().find('.divToolbar').height();
            }

            oHistoryDiv.style.height = (jQuery(oHistoryDiv).parent().parent().height() - iDialogLegendHeight-11)  + 'px';
        }

    },

    // Hook der nach dem Wechseln eines Tabs ausgeführt wird
    toggleDialogTabHook: function($super, iTab, iDialogId) {

        $super(iTab, iDialogId);

        this.calculateDocumentHistoryOverview(iDialogId);

    },

    resizeDialogSize: function($super, aData) {

        $super(aData);

        this.calculateDocumentHistoryOverview(aData.id);

        if(aData.id.indexOf('DOCUMENT_') === 0) {
            var oDialog = this.getDialog(aData.id);

            var oCol = $j(oDialog.content).find('col.description');
            var iRemaining = oCol.data('remaining');

            var iTableWidth = $j(oDialog.content).width() - 37;

            var iDescriptionWidth = (iTableWidth-iRemaining);

            var aTextareas = $j(oDialog.content).find('textarea.description');
            aTextareas.each($j.proxy(function(i, oTextarea) {
                $j(oTextarea).css('width', iDescriptionWidth-12);
                this.resizeTextarea(oTextarea, 21);
            }, this));

            oCol.css('width', iDescriptionWidth);

        }

    },

    executeReloadPositionsTableEvent: function(aData, oTemplate, oLanguage) {

        var iTempValue = $F(oTemplate);
        var aTemp = {};
        aTemp.id = iTempValue;
        aTemp.inquirypositions_view = aData.inquirypositions_view;
        aTemp.language = $F(oLanguage);
        aTemp.document_type = $F('save['+this.hash+']['+aData.id+'][document_type]');
        aTemp.negate = $F('save['+this.hash+']['+aData.id+'][is_credit]');
        aTemp.refresh = $F('save['+this.hash+']['+aData.id+'][is_refesh]');

        // formulardaten mitschicken
        aTemp.form = $('dialog_form_'+aData.id+'_'+this.hash).serialize();

        this.reloadPositionsTable(aTemp, aData.id, aData.data['document_id']);

    },

    getIndividualErrorMessage: function(sErrorMessage, aError) {

        if(
            aError &&
            aError.input &&
            aError.input.object &&
            aError.input.object.id &&
            aError.input.object.id.indexOf('description_EP') != -1
        ) {
            return sErrorMessage.replace('%s', this.getTranslation('document_position_description'));
        }

        return sErrorMessage;

    },

    updateFlexUploadFields: function(iSchoolId, bReset) {

        $j('.FlexUploadContainerSchool').each(function(iKey, oDiv) {
            oDiv = $j(oDiv);

            if(oDiv.data('schools').indexOf(iSchoolId.toString()) !== -1) {
                oDiv.show();
            } else {
                oDiv.hide();
            }

            if(bReset) {
                oDiv.find('input[type=file]').each(function(iIndex, oInput) {
                    oInput.value = '';
                });
                oDiv.find('input[type=checkbox]').each(function(iIndex, oInput) {
                    oInput.checked = false;
                });
                oDiv.find('.gui2_upload_save_message').each(function(iIndex, oMessageBox) {
                    $j(oMessageBox).remove();
                });
            }
        });
    },

    closeDialog: function($super, sDialogID, sHash) {

        if(
            this.oWebcamStream &&
            this.oWebcamVideo
        ) {
            this.oWebcamVideo.pause();
            this.oWebcamVideo.src = "";

            var aTracks = this.oWebcamStream.getTracks();
            $j.each( aTracks, function(iKey, oTrack) {
                oTrack.stop();
            });
            delete this.oWebcamVideo;
            delete this.oWebcamStream;
        }

        $super(sDialogID, sHash);

    },

    loadXlsxJs: function() {

        if(typeof XLSX !== 'undefined') {
            return;
        }

        var resource = document.createElement('script');
        resource.async = "true";
        resource.src = "/assets-public/ts/js/xlsx/xlsx.full.min.js";
        var script = document.getElementsByTagName('script')[0];
        script.parentNode.insertBefore(resource, script);

    },

    startImportCustomer: function(oEvent) {

        //Get the files from Upload control
        var files = oEvent.target.files;
        var i, f;

        var iSuccessfulFieldUpdates = 0;

        //Loop through files
        for (i = 0, f = files[i]; i != files.length; ++i) {

            var reader = new FileReader();
            var name = f.name;
            var sExtension = name.split(".").pop();

            if (['xls','xlsx'].indexOf(sExtension) < 0) {
                this.displayErrors([this.getTranslation('invalid_file')]);
                return;
            }

            reader.onload = function (e) {

                var data = e.target.result;

                var result;
                var workbook = XLSX.read(data, { type: 'array', cellText:false, cellDates:true });

                // TODO Anständige, übersetzbare Fehlermeldung
                if(workbook.Workbook.WBProps.date1904 === true) {
                    alert('This file is not supported (date1904 issue)!');
                    return false;
                }

                var first_sheet_name = workbook.SheetNames[0];

                var worksheet = workbook.Sheets[first_sheet_name];

                var items = XLSX.utils.sheet_to_json(worksheet, {dateNF: this.sDateFormat});

                var bReplace = $j('#replace_students').prop('checked');

                for(item in items) {
                    if (items.hasOwnProperty(item)) {
                        var iSuccessfulRowFieldUpdates = 0;

                        // Find last row (Last row is always empty)
                        var aFirstnameFields = $j('.group-table .firstname');
                        if(bReplace == true) {
                            var oFirstnameField = aFirstnameFields[item];
                        } else {
                            var oFirstnameField = aFirstnameFields[aFirstnameFields.length-1];
                        }

                        var sIndex = oFirstnameField.id.replace('customer[', '').replace('][firstname]', '');

                        // Kompletten Datensatz als JSON in hidden schreiben
                        if($('customer['+sIndex+'][data]')) {
                            $('customer['+sIndex+'][data]').setValue(JSON.stringify(items[item]));
                        }

                        for (field in items[item]) {
                            if (items[item].hasOwnProperty(field)) {

                                if(field === 'gender') {
                                    items[item][field] = this.convertGender(items[item][field]);
                                }

                                // Falls die Spaltenüberschrift Leerzeichen oder Groß- und Kleinschreibung enthält
                                var sFieldKey = field.toLowerCase().replace(' ', '_');

                                if(sFieldKey === 'guide') {

                                    if(items[item][field] == 1) {
                                        $('guide_checkbox_'+sIndex).checked = true;
                                    } else {
                                        $('guide_checkbox_'+sIndex).checked = false;
                                    }

                                } else if($('customer['+sIndex+']['+sFieldKey+']')) {

                                    $('customer['+sIndex+']['+sFieldKey+']').setValue(items[item][field]);

                                    if($('customer['+sIndex+']['+sFieldKey+']').hasClassName('calendar_input')) {
                                        this.updateCalendarValue($('customer['+sIndex+']['+sFieldKey+']'));
                                    }

                                    iSuccessfulFieldUpdates++;
                                    iSuccessfulRowFieldUpdates++;

                                }

                            }
                        }

                        // Nur neue Zeile wenn auch Werte geschrieben wurden
                        if(
                            bReplace === false &&
                            iSuccessfulFieldUpdates > 0
                        ) {
                            aData = {id: this.sCurrentDialogId};
                            this.writeNewGroupCustomerLine(aData, oFirstnameField);
                        }

                    }
                }

                if(iSuccessfulFieldUpdates === 0) {
                    this.displayErrors([this.getTranslation('no_items')], this.sCurrentDialogId, false, false, true);
                } else {
                    this.removeErrors(this.sCurrentDialogId);
                }

            }.bind(this);

            reader.readAsArrayBuffer(f);

        }

    },

    convertGender: function(sGender) {

        var iGender;

        sGender = sGender.replace(/\|+$/g, '');
        sGender = sGender.toLowerCase();

        if(
            sGender == 'mr' ||
            sGender == 'herr' ||
            sGender == 'm' ||
            sGender == 'male'
        ) {
            iGender = 1;
        } else if(
            sGender == 'ms' ||
            sGender == 'mme' ||
            sGender == 'mrs' ||
            sGender == 'frau' ||
            sGender == 'f' ||
            sGender == 'w' ||
            sGender == 'female'
        ) {
            iGender = 2;
        } else {
            iGender = 0;
        }

        return iGender;
    },

    prepareAction: function($super, aElement, aData) {

        if(aElement.task === 'snap') {

            // Grab elements, create settings, etc.
            var canvas = document.getElementById("canvas"),
                canvas_preview = document.getElementById("canvas_preview"),
                context = canvas.getContext("2d"),
                context_preview = canvas_preview.getContext("2d"),
                video = document.getElementById("video");

            var aPosition = $j('#camera_frame').position();

            var iXFactor = video.videoWidth / 640;
            var iYFactor = video.videoHeight / 480;

            var iLeft = (aPosition.left+5)*iXFactor;
            var iTop = (aPosition.top+5)*iYFactor;

            context.drawImage(video, iLeft, iTop, 280, 360, 0, 0, 700, 900);
            context_preview.drawImage(video, iLeft, iTop, 280, 360, 0, 0, 350, 450);

        } else if(aElement.task === 'openImportCustomerModal') {

            this.toggleDialogTabByClass('customer_data_tab', this.sCurrentDialogId);

            $j('#customer_import_container').show();
            $j('#customer_import_file').unbind('change');
            $j('#customer_import_file').change(this.startImportCustomer.bind(this));

            this.loadXlsxJs();

        } else {
            $super(aElement, aData);
        }

    }

});
