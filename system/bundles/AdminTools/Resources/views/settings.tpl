{extends file="system/bundles/AdminTools/Resources/views/layout.tpl"}

{block name="heading"}
	<h1>Settings</h1>
{/block}

{block name="content"}
	<iframe src="/gui2/page/AdminTools_settings" style="width: 100%; border: none; min-height: 90vh;" onload="this.style.height=(this.contentWindow.document.body.scrollHeight+20)+'px';">></iframe>
{/block}
