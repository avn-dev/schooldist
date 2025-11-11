{extends file="system/bundles/AdminLte/Resources/views/base.tpl"}

{block name="header"}
	<link rel="stylesheet" href="/assets/ts-reporting/css/reporting.css?v={\System::d('version')}">
{/block}

{block name="content"}
	<div id="app" class="content"></div>
{/block}

{block name="footer"}
	<script src="/assets/core/js/vue.js?v={\System::d('version')}"></script>
	<script src="/assets/ts-reporting/js/reporting.js?v={\System::d('version')}"></script>
	<script>
		bPageLoaderDisabled = true;
		const APP = Vue.createApp(__FIDELO__.TsReporting.ReportApp, { reports: {json_encode($reports)} })
		APP.provide('translations', {json_encode($translations)})
		APP.provide('debugmode', {System::d('debugmode')});
		APP.provide('locale', '{System::getInterfaceLanguage()}');
		APP.provide('date_format', '{$dateFormat}');
		APP.mount('#app')
	</script>
{/block}
