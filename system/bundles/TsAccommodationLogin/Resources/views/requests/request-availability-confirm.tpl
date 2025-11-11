{extends file="system/bundles/TsAccommodationLogin/Resources/views/layout/login.tpl"}

{block name="title"}{$oSchool->name|L10N}{/block}

{block name="login"}
	
	<h4>{$info}</h4>
	
	{if $success}
		<div class="alert alert-success" role="alert">{'Thank you for your response!'|L10N}</div>
	{/if}
	
{/block}
