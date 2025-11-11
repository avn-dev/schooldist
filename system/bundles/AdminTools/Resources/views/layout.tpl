{* TODO Layout-Redundanz (Lehrerportal) entfernen *}
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
	<link rel="stylesheet" href="/assets/adminlte/bootstrap/css/bootstrap.min.css">
	<link rel="stylesheet" href="/assets/adminlte/css/AdminLTE.min.css">
	<link rel="stylesheet" href="/assets/adminlte/css/skins/skin-blue.min.css">
	<!-- Font Awesome -->
	<link rel="stylesheet" href="/admin/assets/fontawesome5/css/all.min.css?v=4.741.8">
	<link rel="stylesheet" href="/admin/assets/fontawesome5/css/v4-shims.css?v=4.741.8">
	<link rel="stylesheet" href="/assets/admin-tools/layout.css">
	<style>
		{\System::getSystemColorStyles()}
	</style>
	<title>{block name="title"}Tools{/block} – {\Util::getHost()}</title>
</head>
{$container = ($fluidContainer) ? 'container-fluid' : 'container'}
<body class="hold-transition skin-blue layout-top-nav">
<div class="wrapper" style="height: auto; min-height: 100%;">
	<header class="main-header">
		<nav class="navbar navbar-static-top">
			<div class="{$container}">
				<div class="navbar-header">
					<a href="/admin/tools" class="navbar-brand">FIDELO TOOLS</a>
					<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar-collapse" onclick="jQuery('#navbar-collapse').toggleClass('in')">
						<i class="fa fa-bars"></i>
					</button>
				</div>
				<div class="collapse navbar-collapse pull-right" id="navbar-collapse">
					<ul class="nav navbar-nav">
						{foreach $menu as $item}
							{if $item['submenu']}
								TODO
							{else}
								<li class="{($item['active']) ? 'active' : ''}">
									<a href="{$item['url']}">
										<i class="{$item['icon']}"></i>
										{$item['text']}
									</a>
								</li>
							{/if}
						{/foreach}
					</ul>
				</div>
			</div>
		</nav>
	</header>
	<div class="content-wrapper">
		<div class="{$container}">
			<section class="content-header">
				{block name="heading"}{/block}
			</section>
			<section class="content">
				{block name="content"}{/block}
			</section>
		</div>
	</div>
</div>
<script src="/assets/core/jquery/jquery.min.js"></script>
<script src="/assets/adminlte/js/adminlte.min.js"></script>
</body>
</html>
