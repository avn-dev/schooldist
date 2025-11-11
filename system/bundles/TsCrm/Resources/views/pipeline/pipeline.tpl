{extends file="system/bundles/AdminLte/Resources/views/base.tpl"}

{block name="header"}
	{$oGui->getHtmlHeader()}
{/block}

{block name="content"}

	<div id="pipeline" class="container-fluid">
		
	</div>


{/block}

{block name="footer_js"}
	{$oGui->getJsFooter()}
	{$oGui->getJsInitCode()}
{/block}

{block name="footer"}

	<script src="/assets/adminlte/components/moment/moment.js"></script>
	<script src="/assets/adminlte/components/fullcalendar/dist/fullcalendar.min.js"></script>
	<script src='/assets/adminlte/components/fullcalendar/dist/locale-all.js'></script>
	<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/vue@2.5.17/dist/vue.min.js"></script>

	<script>

		$j(function () {

			$j('[data-toggle="tooltip"]').tooltip();

			initPage();

			aGUI['{$oGui->hash}'].request('&task=translations');

			var aStudentStatusFilter = [{ value: 0, label: "-- {'Schülerstatus'|L10N} --" }];
			{foreach $student_status_filter as $iStatusId => $sStatus}
				aStudentStatusFilter.push({ value: {$iStatusId}, label: '{$sStatus|escape:'javascript'}' });
			{/foreach}

			var aActivitesFilter = [{ value: 0, label: "-- {'Aktivität'|L10N} --" }];
			{foreach $activity_filter as $iActivityId => $sActivity}
				aActivitesFilter.push({ value: {$iActivityId}, label: '{$sActivity|escape:'javascript'}' });
			{/foreach}

			oVueJs = {
				locale: '{$sSchoolLanguage}',
				l10n: {
					loading: "{'Lädt'|L10N|escape}",
					calendar: {
						check_delete: "{'Möchten Sie die Zuweisung dieses Schülers löschen?'|L10N|escape}",
						msg: "",
						goahead: "{'Weiter'|L10N|escape}",
						goback: "{'Zurück'|L10N|escape}",
						choose_activity: "{'Dieser Block enthält mehr als eine Aktivität. Wählen Sie die Aktivität aus, die Sie dem Schüler zuweisen möchten.'|L10N|escape}",
						student_has_no_activity: "{'Dieser Schüler hat keine Aktivität gebucht. Wenn Sie ihn diesem Block zuweisen, wird die Aktivität automatisch gebucht.'|L10N|escape}",
						allready_allocated: "{'Ein Schüler kann jeder Aktivität innerhalb eines Blocks nur einmal zugewiesen werden.'|L10N|escape}"
					}
				},
				
			};

		});

	</script>

	<script src="{route name='TsCrm.ts_crm_resource' sFile= 'js/pipeline.js'}"></script>

	<script>
		$j(function () {
			TsCrm.translations = {$aTranslations|json_encode};
		});
	</script>

{/block}
