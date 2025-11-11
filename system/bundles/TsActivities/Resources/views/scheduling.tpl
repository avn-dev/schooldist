{*{extends file="system/bundles/AdminLte/Resources/views/base.tpl"}

{block name="header"}
	<link rel="stylesheet" href="/admin/assets/custom/css/custom.css?v={System::d('version')}" />
	<link rel="stylesheet" href="/ts/activities/resources/css/scheduling.css?v={\System::d('version')}"/>
    {$gui->getHtmlHeader()}
{/block}

{block name="content"}
	<div id="app"></div>
{/block}

{block name="footer"}
    {$gui->getJsFooter()}
    {$gui->getJsInitCode()}
	<script>
		var SchedulingGuiClass = Class.create(CommunicationGui, {
			requestCallbackHook: function($super, data) {
				$super(data)
				if (data.action === 'saveDialogCallback') {
					this.emitter.emit('refetchEvents')
				}
			}
		});

		window.initPage();
		var SchedulingGUI = aGUI['{$gui->hash}'];
		SchedulingGUI.options = {$gui->generateOptionsArray()|json_encode};
		SchedulingGUI.translations = {$gui->getDataObject()->getTranslations($gui->gui_description)|json_encode};
	</script>
	<script src="/ts/activities/resources/js/scheduling.js"></script>
{/block}*}


<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0" />

		<title>{System::d('project_name')} - {'Administration'|L10N:'Framework'}</title>

		<!-- Font Awesome -->
		<link rel="stylesheet" href="{$router->generateUrl('Admin.assets', ['sType' => 'fontawesome5', 'sFile' => 'css/all.min.css'])}?v={$version}">
		<link rel="stylesheet" href="{$router->generateUrl('Admin.assets', ['sType' => 'fontawesome5', 'sFile' => 'css/v4-shims.css'])}?v={$version}">

		<!-- App -->
		<link rel="stylesheet" href="{$router->generateUrl('Admin.assets', ['sType' => 'fonts', 'sFile' => 'inter/inter.css'])}?v={$version}"/>
		<link rel="stylesheet" href="{$router->generateUrl('Admin.assets', ['sType' => 'interface', 'sFile' => 'css/tailwind.css'])}?v={$version}"/>
		<link rel="stylesheet" href="{$router->generateUrl('Admin.assets', ['sType' => 'interface', 'sFile' => 'css/app.css'])}?v={$version}" />
		<link rel="stylesheet" href="{$router->generateUrl('Admin.assets', ['sType' => 'interface', 'sFile' => 'css/bootstrap-wrapper.css'])}?v={$version}" />
		<link rel="stylesheet" href="{$router->generateUrl('Admin.assets', ['sType' => 'custom', 'sFile' => 'css/custom.css'])}?v={$version}" />

		<link rel="stylesheet" href="/ts/activities/resources/css/scheduling.css?v={$version}"/>
		{$gui->getHtmlHeader()}

	</head>
	<body class="font-body" data-mode="light">

		<div id="app"></div>
		<div id="admin-app"></div>

		<script src="{$router->generateUrl('Tinymce.tinymce_resources', ['sFile' => 'tinymce.min.js'])}?v={$version}"></script>
		<script src="{$router->generateUrl('Core.assets', ['sFile' => 'js/vue.js'])}?v={$version}"></script>
		<script src="{$router->generateUrl('Admin.assets', ['sType' => 'interface', 'sFile' => 'js/admin-iframe.js'])}?v={$version}"></script>

		{$gui->getJsFooter()}
		{$gui->getJsInitCode()}
		<script>
			var SchedulingGuiClass = Class.create(CommunicationGui, {
				requestCallbackHook: function($super, data) {
					$super(data)
					if (data.action === 'saveDialogCallback') {
						this.emitter.emit('refetchEvents')
					}
				}
			});

			window.initPage();
			var SchedulingGUI = aGUI['{$gui->hash}'];
			SchedulingGUI.options = {$gui->generateOptionsArray()|json_encode};
			SchedulingGUI.translations = {$gui->getDataObject()->getTranslations($gui->gui_description)|json_encode};

            __ADMIN__.createAdminSlimApp({
                'common.back': '{Admin\Facades\Admin::translate('Zurück')}',
                'common.cancel': '{Admin\Facades\Admin::translate('Abbrechen')}',
                'common.close': '{Admin\Facades\Admin::translate('Schließen')}',
                'common.confirm': '{Admin\Facades\Admin::translate('Okay')}',
            }).mount('#admin-app')

		</script>

		<script src="/ts/activities/resources/js/scheduling.js?v={$version}"></script>
	</body>
</html>