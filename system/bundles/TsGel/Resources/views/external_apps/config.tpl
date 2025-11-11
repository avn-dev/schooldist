{function displayField label="" type="" nameAttr="" value="" errors=[]}
	<div class="form-group {(isset($errors[$nameAttr])) ? 'has-error' : ''}">
		<label for="{$nameAttr}" class="col-sm-2 control-label">{$label}</label>
		<div class="col-sm-10">
			{if $type === 'input'}
				<input type="text" name="{$nameAttr}" class="form-control" id="{$nameAttr}" value="{$value}">
			{elseif $type === 'checkbox'}
				<input type="hidden" name="{$nameAttr}" id="{$nameAttr}" value="0">
				<input type="checkbox" name="{$nameAttr}" id="{$nameAttr}" value="1" {if $value == 1}checked{/if}>
			{elseif $type === 'select_multiple'}
				<select name="{$nameAttr}[]" id="{$nameAttr}" class="form-control" multiple>
					{foreach $options as $option => $text}
						<option value="{$option}" {if in_array($option, $value)}selected{/if}>{$text}</option>
					{/foreach}
				</select>
			{elseif $type === 'select'}
				<select name="{$nameAttr}" id="{$nameAttr}" class="form-control">
					<option value="0"></option>
					{foreach $options as $option => $text}
						<option value="{$option}" {if $option === $value}selected{/if}>{$text}</option>
					{/foreach}
				</select>
			{else}
				Unknown
			{/if}
			{if isset($errors[$nameAttr])}
				<span class="help-block">
					{foreach $errors[$nameAttr] as $message}
						{$message}<br/>
					{/foreach}
				</span>
			{/if}
		</div>
	</div>
{/function}

<form class="form-horizontal" method="post" action="{route name="TcExternalApps.save" sAppKey=$appKey}">
	<div class="box-body">

		<div class="box-group">

            {call displayField label="{"URL"|L10N}" type="input" nameAttr="{\TsGel\Handler\ExternalApp::CONFIG_SERVER}" value="{\System::d(\TsGel\Handler\ExternalApp::CONFIG_SERVER)|escape}" errors=$fieldErrors}
            {call displayField label="{"Token"|L10N}" type="input" nameAttr="{\TsGel\Handler\ExternalApp::CONFIG_API_TOKEN}" value="{\System::d(\TsGel\Handler\ExternalApp::CONFIG_API_TOKEN)|escape}" errors=$fieldErrors}
            {call displayField label="{"Schulen"|L10N}" type="select_multiple" nameAttr="{\TsGel\Handler\ExternalApp::CONFIG_SCHOOLS}" value=\TsGel\Handler\ExternalApp::getSchools() options=$schoolsSelection errors=$fieldErrors}
            {call displayField label="{"Kurskategorien"|L10N}" type="select_multiple" nameAttr="{\TsGel\Handler\ExternalApp::CONFIG_COURSE_CATEGORIES}" value=\TsGel\Handler\ExternalApp::getCourseCategories() options=$allCategoriesSelection errors=$fieldErrors}
            {call displayField label="{"Bezahlstatus"|L10N}" type="select" nameAttr="{\TsGel\Handler\ExternalApp::CONFIG_PAYMENT_STATE}" value={\System::d(\TsGel\Handler\ExternalApp::CONFIG_PAYMENT_STATE)|escape} options=$paymentStatesSelection errors=$fieldErrors}
            {call displayField label="{"Buchungen"|L10N}" type="select" nameAttr="{\TsGel\Handler\ExternalApp::CONFIG_BOOKING_STATUS}" value={\System::d(\TsGel\Handler\ExternalApp::CONFIG_BOOKING_STATUS, 'all')|escape} options=$bookingStatusSelection errors=$fieldErrors}

		</div>

	</div>
	<!-- /.box-body -->
	<div class="box-footer">
		<a href="{route name="TcExternalApps.list"}" class="btn btn-default">{'Zurück'|L10N}</a>
		<button type="submit" class="btn btn-primary pull-right">{'Speichern'|L10N}</button>
	</div>
	<!-- /.box-footer -->
</form>

<script>

	document.addEventListener("DOMContentLoaded", function() {
		matchCourseCategories()
		document.getElementById('gel_api_schools').addEventListener('change', matchCourseCategories)
	});

	function matchCourseCategories() {

		var schools = document.getElementById('gel_api_schools');
		var categories = document.getElementById('gel_api_course_categories');
		var groupedCategories = {$categoriesSelection|json_encode};

		var selectedSchools = getMultiselectValues(schools);

		var final = {};
		for (var schoolId of selectedSchools) {

			if (Object.keys(groupedCategories).indexOf(schoolId) !== -1) {
				for (var key of Object.keys(groupedCategories[schoolId])) {
					final[key] = groupedCategories[schoolId][key];
				}
			}

		}

		updateSelectOptions(categories, final);
	}

	function getMultiselectValues(oSelect) {
		return Array.from(oSelect.options).filter(function (option) {
			return option.selected;
		}).map(function (option) {
			return option.value;
		});
	}

	function updateSelectOptions(select, options) {

		var oldValues = getMultiselectValues(select);

		if (select.hasChildNodes()) {
			while(select.childNodes.length >= 1) {
				select.removeChild( select.firstChild );
			}
		}

		for (var key of Object.keys(options)) {
			var option = document.createElement('option');
			option.value = key;
			option.text = options[key];
			if (oldValues.indexOf(key) !== -1) {
				option.selected = true;
			}
			select.appendChild(option);
		}

	}

</script>