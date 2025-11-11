{if $connectionActive}
	
	<div class="alert alert-success alert-dismissible">
		{'Die Synchronisierung ist aktiv.'|L10N}
	</div>

<form class="form-horizontal" method="post" action="{route name="TcExternalApps.edit" sAppKey=$appKey}">

    <table class="table table-hover table-striped">

        <colgroup>
            <col style="width:50%">
            <col style="width:50%">
        </colgroup>

        <tr>
            <th>{'Bereits existierende Kontakte in HubSpot (gleiche E-Mail-Adressen)'|L10N}</th>
            <td>
                <select name="config[hubspot_already_existing_contact_action]" id="config[hubspot_already_existing_contact_action]" class="form-control alreadyExistingContactAction">
                    {* new_deal automatisch als Default, weil es der 1. Eintrag ist oder "new_contact bei Kunden, die es
                     so nur so benutzt haben vor dieser Änderung*}
                    <option value="new_deal" {if $alreadyExistingContactAction == "new_deal"} selected {/if}> {"Weiteren Deal ergänzen"|L10N} </option>
                    <option value="new_contact" {if $alreadyExistingContactAction == "new_contact"} selected {/if}> {"Neuen Kontakt ergänzen"|L10N} </option>
                    <option value="send_error" {if $alreadyExistingContactAction == "send_error"} selected {/if}> {"Nicht übermitteln"|L10N} </option>
                </select>
            </td>
        </tr>

        {* style="display:none;" und bei "new_contact" von oben wird es sichtbar gemacht im JS unten*}
        <tr class="additionalMultipleEmails" {if $alreadyExistingContactAction != "new_contact"} style="display:none;" {/if}>
            <th>
                {'Optional ergänzende Zeichen'|L10N}
            </th>
            <td>
                <input type="text" value="{$hubspotAdditionalMultipleEmails}" name="config[hubspot_additional_multiple_emails]" id="config[hubspot_additional_multiple_emails]" class="form-control">
            </td>
        </tr>

        <tr>
            <th></th>
            <th></th>
        </tr>

        <tr>
            <th>{'Fidelo:'}</th>
            <th>{'HubSpot:'}</th>
        </tr>

        {function objectSelect}
            <th>
                <select name="config[{$key}]" id="config[{$key}]" class="form-control object-select">
                    <option value=""></option>
                    {foreach $customObjects as $customObjectId => $customObjectLabel}
                        <option value="{$customObjectId}" {if $selectedObjectId == $customObjectId}selected{/if}>{$customObjectLabel}</option>
                    {/foreach}
                </select>
            </th>
        {/function}

        {function objectPropertiesSelect}
            <td>
                <table width="100%" style="table-layout:fixed;">
                    <tr>
                        {for $i = 1; $i <= ${$service}PropertiesColumnAmount; $i++}
                            <td>
                                <select name="config[{$key}][{$rowNumber}][hubspot_property_name_{$i}]" id="config[{$key}][{$rowNumber}][hubspot_property_name_{$i}]" class="form-control">
                                    <option value=""></option>
                                    {foreach $properties as $property}
                                        {if is_array($property)}
                                            <option value="{$property['name']}" {if $selectedProperties["hubspot_property_name_{$i}"] == $property['name']}selected{/if}>{$property['label']}</option>
                                        {else}
                                            <option value="{$property->getName()}" {if $selectedProperties["hubspot_property_name_{$i}"] == $property->getName()}selected{/if}>{$property->getLabel()}</option>
                                        {/if}
                                    {/foreach}
                                </select>
                            </td>
{*                            Padding *}
                            {if $i != ${$service}PropertiesColumnAmount}
{*                                Nicht beim letzten Durchgang*}
                                <td width="5px"></td>
                            {/if}
                        {/for}
                    </tr>
                </table>
            </td>
        {/function}

        {function customFieldsAndPropertiesSelects}
            <td>{'Individuelle Felder'|L10N}:</td>
            <td></td>

            {foreach ${$service}CustomFieldRows as $customFieldRow => $customFieldRowInformation}
                <tr>
                    <td>
                        {assign var=key value="hubspot_{$serviceSingular}_customfields"}

                        <select name="config[{$key}][{$customFieldRow}][fidelo_custom_field_id]" id="config[{$key}][{$customFieldRow}][fidelo_custom_field_id]" class="form-control">
                            <option value=""></option>
                            {foreach ${$service}CustomFields as $customField}
                                <option value="{$customField->id}" {if $customFieldRowInformation['fidelo_custom_field_id'] == $customField->id}selected{/if}>{$customField->title}</option>
                            {/foreach}
                        </select>
                    </td>
                {objectPropertiesSelect key={$key} properties=$hubspot{ucfirst($service)}Properties selectedProperties=$customFieldRowInformation rowNumber=$customFieldRow service=$service}

                <td>
                    <button type="button" class="btn btn-danger delete-row-custom-fields" data-service="{$service}" data-row-count="{$customFieldRow}">{'-'}</button>
                </td>

            {/foreach}

            <td>
                <button type="button" class="btn btn-primary new-row-custom-fields" data-service="{$service}">{'+'}</button>
            </td>

        {/function}

        {function additionalServicesAndPropertiesSelects}
        <tr>
            <td>{'Zusatzleistungen gebucht'|L10N}:</td>
            <td></td>

            {foreach ${$service}AdditionalServicesRows as $additionalServicesRow => $additionalServicesRowInformation}
                <tr>
                    <td>
                        {assign var=key value="hubspot_{$serviceSingular}_additional_services"}
                        <select name="config[{$key}][{$additionalServicesRow}][fidelo_additional_service_id]" id="config[{$key}][{$additionalServicesRow}][fidelo_additional_service_id]" class="form-control">
                            <option value=""></option>
                            {foreach ${$service}AdditionalServices as $additionalServiceId => $additonalServiceLabel}
                                <option value="{$additionalServiceId}" {if $additionalServicesRowInformation['fidelo_additional_service_id'] == $additionalServiceId}selected{/if}>{$additonalServiceLabel|L10N}</option>
                            {/foreach}
                        </select>
                    </td>
                    {objectPropertiesSelect key={$key} properties=$hubspot{ucfirst($service)}Properties selectedProperties=$additionalServicesRowInformation rowNumber=$additionalServicesRow service=$service}
                    <td>
                        <button type="button" class="btn btn-danger delete-row-additional-services" data-service="{$service}" data-row-count="{$additionalServicesRow}">{'-'}</button>
                    </td>
            {/foreach}
            <td>
                <button type="button" class="btn btn-primary new-row-additional-services" data-service="{$service}">{'+'}</button>
            </td>
        </tr>
        {/function}

        {function fieldsAndPropertiesSelects}
            <td>{'Felder'|L10N}:</td>
            <td></td>

            {foreach ${$service}FieldRows as $fieldRow => $fieldRowInformation}
                <tr>
                    <td>
                        {assign var=key value="hubspot_{$serviceSingular}_fields"}
                        <select name="config[{$key}][{$fieldRow}][fidelo_field]" id="config[{$key}][{$fieldRow}][fidelo_field]" class="form-control">
                            <option value=""></option>
                            {foreach ${$serviceSingular}Fields as $field => $fieldLabel}
                                <option value="{$field}" {if $fieldRowInformation['fidelo_field'] == $field}selected{/if}>{$fieldLabel|L10N}</option>
                            {/foreach}
                        </select>
                    </td>
                {objectPropertiesSelect key={$key} properties=$hubspot{ucfirst($service)}Properties selectedProperties=$fieldRowInformation rowNumber=$fieldRow service=$service}
                <td>
                    <button type="button" class="btn btn-danger delete-row-fields" data-service="{$service}" data-row-count="{$fieldRow}">{'-'}</button>
                </td>
            {/foreach}
            <td>
                <button type="button" class="btn btn-primary new-row-fields" data-service="{$service}">{'+'}</button>
            </td>
        </tr>
        {/function}

        {function fieldsAndPropertiesAndAdditionalServicesSelectsWithHeading}

            {assign var = 'serviceClass' value = "TsHubspot\Service\Inquiry{$service}"}
            {assign var = 'serviceSelectedObjectId' value = $selectedObjectId{$service}}
            {assign var=serviceSingular value=strtolower(substr($service, 0, -1))}

            <tr>
                <th>{$heading|L10N}</th>
                {objectSelect key=$serviceClass::HUBSPOT_OBJECT_KEY selectedObjectId=$serviceSelectedObjectId}
            </tr>

            {if !empty($serviceSelectedObjectId)}
                {fieldsAndPropertiesSelects service=strtolower($service) serviceSingular=$serviceSingular}
                {* Andere Leistungen haben (noch) keine Individuellen Felder, aber theoretisch für alle Leistungen eingebaut (-> hier und in den Hubspot-Services *}
                {if $service == 'Courses'}
                    {customFieldsAndPropertiesSelects service=strtolower($service) serviceSingular=$serviceSingular}
                {/if}

                {* Es gibt (noch) nur Zusatzleistungen für Kurse und Unterkünfte*}
                {if
                    $service === 'Courses' ||
                    $service === 'Accommodations'
                }
                    {additionalServicesAndPropertiesSelects service=strtolower($service) serviceSingular=$serviceSingular}
                {/if}
            {/if}

        {/function}

        {function fieldsAndPropertiesSelectsAndCustomFields}
            {fieldsAndPropertiesSelects service=$service serviceSingular=$serviceSingular}
            {customFieldsAndPropertiesSelects service=$service serviceSingular=$serviceSingular}
        {/function}

        {function hiddenInputs}
            <td>
                <input type="hidden" name="config[add_row_{$rowType}]" id="config[add_row_{$rowType}]" class="form-control add-row-{$rowTypeForClass}">
            </td>
            <td>
                <input type="hidden" name="config[remove_row_{$rowType}][service]" id="config[remove_row_{$rowType}][service]" class="form-control remove-row-{$rowTypeForClass}">
            </td>
            <td>
                <input type="hidden" name="config[remove_row_{$rowType}][row_count]" id="config[remove_row_{$rowType}][row_count]" class="form-control remove-row-{$rowTypeForClass}">
            </td>
        {/function}

        <tr>
            <th>{'Buchungen'|L10N}</th>
            <th>{'Deals und Kontakte'|L10N}</th>
        </tr>
        {fieldsAndPropertiesSelectsAndCustomFields service='inquiries' serviceSingular='inquiry'}

        {fieldsAndPropertiesAndAdditionalServicesSelectsWithHeading service='Courses' heading='Kurse'}
        {fieldsAndPropertiesAndAdditionalServicesSelectsWithHeading service='Accommodations' heading='Unterkünfte'}
        {fieldsAndPropertiesAndAdditionalServicesSelectsWithHeading service='Transfers' heading='Transfers'}
        {fieldsAndPropertiesAndAdditionalServicesSelectsWithHeading service='Insurances' heading='Versicherungen'}
        {fieldsAndPropertiesAndAdditionalServicesSelectsWithHeading service='Payments' heading='Zahlungen'}

        <tr>
            <th>{'Aktivität gebucht'|L10N}</th>
            <th>{'Deals und Kontakte'|L10N}</th>
        </tr>

        {foreach $activityMappingRows as $mappingRow => $mappingRowInformation}
        <tr>
            <td>
                {assign var=key value="hubspot_activity_mapping"}
                <select name="config[{$key}][{$mappingRow}][fidelo_activity_id]" id="config[{$key}][{$mappingRow}][fidelo_activity_id]" class="form-control">
                    <option value=""></option>
                    {foreach $activities as $activityId => $activityLabel}
                        <option value="{$activityId}" {if $mappingRowInformation['fidelo_activity_id'] == $activityId}selected{/if}>{$activityLabel|L10N}</option>
                    {/foreach}
                </select>
            </td>
            {objectPropertiesSelect key={$key} properties=$hubspotActivitiesProperties selectedProperties=$mappingRowInformation rowNumber=$mappingRow service='activities'}
            <td>
                <button type="button" class="btn btn-danger delete-row-mappings" data-service="activities" data-row-count="{$mappingRow}">{'-'}</button>
            </td>
            {/foreach}
            <td>
                <button type="button" class="btn btn-primary new-row-mappings" data-service="activities">{'+'}</button>
            </td>
        </tr>

        {if !empty($hubspotAgencyIdsNotFoundInHubspot)}
            <tr>
                <th>{'Agenturen'|L10N}:</th>
                <td></td>
            </tr>
            {foreach $agencyRows as $agencyRow => $agencyRowInformation}
                <tr>
                    <td>
                        <select name="config[hubspot_agencies][{$agencyRow}][fidelo_agency_id]" id="config[hubspot_agencies}][{$agencyRow}][fidelo_agency_id]" class="form-control">
                            <option value=""></option>
                            {foreach $hubspotAgencyIdsNotFoundInHubspot as $agencyId}
                                {assign var=agency value=Ext_Thebing_Agency::getInstance($agencyId)}
                                <option value="{$agencyId}" {if $agencyRowInformation['fidelo_agency_id'] == $agencyId}selected{/if}>{$agency->getName(true)}</option>
                            {/foreach}
                        </select>
                    </td>

                    <td>
                        <select name="config[hubspot_agencies][{$agencyRow}][hubspot_agency_id]" id="config[hubspot_agencies][{$agencyRow}][hubspot_agency_id]" class="form-control">
                            <option value=""></option>
                            {foreach $hubspotAgencies as $companyId => $hubspotAgency}
                                <option value="{$companyId}" {if $agencyRowInformation['hubspot_agency_id'] == $companyId}selected{/if}>{$hubspotAgency}</option>
                            {/foreach}
                        </select>
                    </td>

                    <td>
                        <button type="button" class="btn btn-danger delete-row-fields" data-service="agenciesObjects" data-row-count="{$agencyRow}">{'-'}</button>
                    </td>

            {/foreach}
                    <td>
                        <button type="button" class="btn btn-primary new-row-fields" data-service="agenciesObjects">{'+'}</button>
                    </td>
                </tr>

            {fieldsAndPropertiesSelectsAndCustomFields service='agencies' serviceSingular='agency'}
        {/if}

        {hiddenInputs rowType=customfields rowTypeForClass=customfields}
        {hiddenInputs rowType=fields rowTypeForClass=fields}
        {hiddenInputs rowType=additional_services rowTypeForClass="additional-services"}
        {hiddenInputs rowType=mappings rowTypeForClass=mappings}

    </table>

    <div class="box-footer">
        <a href="{route name="TcExternalApps.list"}" class="btn btn-default">{'Zurück'|L10N}</a>
        <button type="submit" class="btn btn-primary pull-right save">{'Speichern'|L10N}</button>
</form>

        <form method="post" class="form-horizontal" action="{route name="TsHubspot.admin_hubspot_deactivate"}">
            <p>
                <button type="submit" class="btn btn-danger pull-right">{'Verbindung zurücksetzen'|L10N}</button>
            </p>
        </form>
    </div>

    <script>

		document.addEventListener("DOMContentLoaded", function() {

            $('.object-select').change(function() {
                this.form.submit();
            });

			$('.new-row-custom-fields').click(function() {
				hiddenInputAddRow = $('.add-row-customfields')[0];
				// Info, bei welcher Leistung eine Reihe hinzugefügt werden soll
				hiddenInputAddRow.value = $(this).attr('data-service');
				this.form.submit();
			});

			$('.delete-row-custom-fields').click(function() {
				hiddenInputRemoveRowService = $('.remove-row-customfields')[0];
				hiddenInputRemoveRowRowCount = $('.remove-row-customfields')[1];
				// Info, bei welcher Leistung eine Reihe entfernt werden soll und welche Reihe
				hiddenInputRemoveRowService.value = $(this).attr('data-service');
				hiddenInputRemoveRowRowCount.value = $(this).attr('data-row-count');
				this.form.submit();
			});

			$('.new-row-fields').click(function() {
				hiddenInputAddRow = $('.add-row-fields')[0];
				// Info, bei welcher Leistung eine Reihe hinzugefügt werden soll
				hiddenInputAddRow.value = $(this).attr('data-service');
				this.form.submit();
			});

			$('.delete-row-fields').click(function() {
				hiddenInputRemoveRowService = $('.remove-row-fields')[0];
				hiddenInputRemoveRowRowCount = $('.remove-row-fields')[1];
				// Info, bei welcher Leistung eine Reihe entfernt werden soll und welche Reihe
				hiddenInputRemoveRowService.value = $(this).attr('data-service');
				hiddenInputRemoveRowRowCount.value = $(this).attr('data-row-count');
				this.form.submit();
			});

			$('.new-row-additional-services').click(function() {
				hiddenInputAddRow = $('.add-row-additional-services')[0];
				// Info, bei welcher Leistung eine Reihe hinzugefügt werden soll
				hiddenInputAddRow.value = $(this).attr('data-service');
				this.form.submit();
			});

			$('.delete-row-additional-services').click(function() {
				hiddenInputRemoveRowService = $('.remove-row-additional-services')[0];
				hiddenInputRemoveRowRowCount = $('.remove-row-additional-services')[1];
				// Info, bei welcher Leistung eine Reihe entfernt werden soll und welche Reihe
				hiddenInputRemoveRowService.value = $(this).attr('data-service');
				hiddenInputRemoveRowRowCount.value = $(this).attr('data-row-count');
				this.form.submit();
			});

			$('.new-row-mappings').click(function() {
				hiddenInputAddRow = $('.add-row-mappings')[0];
				// Info, bei welcher Leistung eine Reihe hinzugefügt werden soll
				hiddenInputAddRow.value = $(this).attr('data-service');
				this.form.submit();
			});

			$('.delete-row-mappings').click(function() {
				hiddenInputRemoveRowService = $('.remove-row-mappings')[0];
				hiddenInputRemoveRowRowCount = $('.remove-row-mappings')[1];
				// Info, bei welcher Leistung eine Reihe entfernt werden soll und welche Reihe
				hiddenInputRemoveRowService.value = $(this).attr('data-service');
				hiddenInputRemoveRowRowCount.value = $(this).attr('data-row-count');
				this.form.submit();
			});

			$('.save').click(function() {
				this.form.action='{route name="TcExternalApps.save" sAppKey=$appKey}';
			});

			$('.alreadyExistingContactAction').change(function() {
				tr = $('.additionalMultipleEmails')
                if ($(this).val() == 'new_contact') {
					tr.show()
				} else {
					tr.hide()
				}
			});

		});
    </script>

{else}
			
<!-- Man muss hier target="_blank" setzen, da Hubspot anscheinend Zugriff in iframes verbietet -->
<form method="post" target="_blank" class="form-horizontal" action="{route name="TsHubspot.admin_hubspot_activate"}">
	<div class="box-body">
        <p>{$oApp->getDescription()}</p>
{*        Gibt es erstmal nicht:*}
{*        <div class="form-group" id="check-div">*}
{*            <label class="col-sm-3 control-label" for="sync-checkbox">{'Alle Agenturen und Mitarbeiter direkt synchronisieren'|L10N}</label>*}
{*            <div class="col-sm-9">*}
{*                <input type="checkbox" id="sync-checkbox" onchange="$('#sync').val($(this).prop('checked'))">*}
{*                <input type="hidden" name="sync" id="sync" value="0">*}
{*            </div>*}
{*        </div>*}

	</div>
    <div class="box-footer">
        <button type="submit" class="btn btn-primary pull-right">{'Hubspot aktivieren'|L10N}</button>
    </div>
    {foreach $oSession->getFlashBag()->get('success', array()) as $sMessage}
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <i class="icon fa fa-check"></i> {$sMessage}
        </div>
    {/foreach}
    {foreach $oSession->getFlashBag()->get('error', array()) as $sMessage}
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <i class="icon fa fa-check"></i> {$sMessage}
        </div>
    {/foreach}
</form>

<script>
    function handleSelect() {

		if($('#target').val() === 'agency') {
			$('#check-div').show();
		} else {
			$('#check-div').hide();
			$('#sync-checkbox').prop('checked', false);
		}

	}
</script>

{/if}