{extends file="system/bundles/TsAccommodationLogin/Resources/views/layout/master.tpl"}

{block name="title"}{'Availability requests'|L10N} - {$oSchool->name|L10N}{/block}

{block name="content"}
	<div class="content-header">
        <h1>{'Availability requests'|L10N}</h1>
    </div>
	
	<div class="content">
	{include file="../pages/students.tpl" items=$pendingRequests type='requests' title='Pending requests'}
	{include file="../pages/students.tpl" items=$closedRequests type='closed_requests' title='Latest closed requests'}
	</div>
	
{/block}


