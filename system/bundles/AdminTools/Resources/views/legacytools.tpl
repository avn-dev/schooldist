{extends file="system/bundles/AdminTools/Resources/views/layout.tpl"}

{block name="heading"}

{/block}

{block name="content"}
	<iframe src="/admin/extensions/tc/tools.html" style="width: 100%; border: none;" onload="this.style.height=(this.contentWindow.document.body.scrollHeight+20)+'px';">></iframe>
{/block}
