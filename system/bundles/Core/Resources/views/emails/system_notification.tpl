{extends file="system/bundles/Admin/Resources/views/emails/email.tpl"}

{block name="content"}

	{$group_title}

	<br/>
	<br/>

	<p>
		{$message|nl2br}
	</p>
{/block}