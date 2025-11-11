<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<title>{$oGui->gui_title}</title>
		<!-- Tell the browser to be responsive to screen width -->
		<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
		
		{include file=Factory::executeStatic('\Admin_Html', 'getHeadIncludeFile', [true])}

		{$aOptions.additional_top}
		{$aOptions.additional}
		{$aOptions.additional_bottom}

		{block name="system_head"}{/block}
	</head>
	<body class="font-body h-full p-2" data-mode="light">

		<div class="page-loader" {( $oGui && $oGui->hasDialogOnlyMode()) ? 'style="height: 0 !important; width: 0 !important; opacity: 0;"' : ''}>
			<div class="pl-cube1 pl-cube"></div>
			<div class="pl-cube2 pl-cube"></div>
			<div class="pl-cube4 pl-cube"></div>
			<div class="pl-cube3 pl-cube"></div>
		</div>
		
		{*if \System::d('debugmode') === 2}
		<section class="content-header">
			<h1>
			{$oGui->gui_title}
			<small></small>
			</h1>
			<ol class="breadcrumb">
				<li><a href="#"><i class="fa fa-dashboard"></i> Home</a></li>
				<li><a href="#">Tables</a></li>
				<li class="active">Data tables</li>
			</ol>
		</section>
		{/if*}
		
		<section class="text-xs rounded">
			<!-- COLOR PALETTE -->
			<div class="color-palette-box">
			  <div class="" {($oGui && $oGui->hasDialogOnlyMode()) ? 'style="height: 0 !important; width: 0 !important; opacity: 0;"' : ''}>
				  {block name="html"}
					  {$sHtml}
				  {/block}
			  </div>
			  <!-- /.box-body -->
			</div>
		</section>

        <div id="admin-app"></div>

		{$sJs}

		{block name="system_footer"}{/block}

		<script type="text/javascript">

			function processLoading() {
				console.debug('processLoading');
			}

			jQuery(function() {
				initPage();
				{block name="system_footer_ready"}{/block}
			});

            __ADMIN__.createAdminSlimApp({
                'common.back': '{Admin\Facades\Admin::translate('Zurück')}',
                'common.cancel': '{Admin\Facades\Admin::translate('Abbrechen')}',
                'common.close': '{Admin\Facades\Admin::translate('Schließen')}',
                'common.confirm': '{Admin\Facades\Admin::translate('Okay')}',
            }).mount('#admin-app')

		</script>
		
	</body>
</html>
