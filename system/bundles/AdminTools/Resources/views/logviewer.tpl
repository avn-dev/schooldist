{extends file="system/bundles/AdminTools/Resources/views/layout.tpl"}

{block name="title"}Log-Viewer{/block}

{block name="heading"}
	<h1>Log-Viewer</h1>
{/block}

{block name="content"}
	<div class="box" id="app"></div>
	<script src="/assets/core/js/vue.js"></script>
	<script>window.__FILE_OPTIONS__ = {$fileOptions|json_encode};</script>
	<script src="/assets/admin-tools/js/logviewer.js"></script>
{/block}
