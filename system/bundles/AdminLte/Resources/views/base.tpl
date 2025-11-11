<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<title>{System::d('project_name')} - {'Administration'|L10N:'Framework'}</title>
		<!-- Tell the browser to be responsive to screen width -->
		<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
		
		{include file=Factory::executeStatic('\Admin_Html', 'getHeadIncludeFile')}
		
		{block name="system_head"}
		<style>
			{\System::getSystemColorStyles()}
		</style>
		{/block}
		
		{block name="header"}{/block}

		<!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
		<!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
		<!--[if lt IE 9]>
			<script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
			<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
		<![endif]-->
	</head>
	<body class="hold-transition skin-blue" style="background: #ecf0f5;" data-mode="light">

		<div class="page-loader">
			<div class="pl-cube1 pl-cube"></div>
			<div class="pl-cube2 pl-cube"></div>
			<div class="pl-cube4 pl-cube"></div>
			<div class="pl-cube3 pl-cube"></div>
		</div>
		
		{block name="content"}{/block}

		{block name="footer_js"}
			{include file="system/bundles/AdminLte/Resources/views/footer.js.inc.tpl"}
		{/block}

		{block name="footer"}{/block}

	</body>
</html>
