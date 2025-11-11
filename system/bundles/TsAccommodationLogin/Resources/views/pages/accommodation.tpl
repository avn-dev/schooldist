{extends file="system/bundles/TsAccommodationLogin/Resources/views/layout/master.tpl"}

{block name="title"}{'Dashboard'|L10N} - {$oSchool->name|L10N}{/block}

{block name="content"}
    <div class="content-header">
        <h1>{'Dashboard'|L10N}</h1>
    </div>
    <div class="content">

        {include file="./students.tpl" items=$currentAllocations type='allocations' title='Current students'}
        {include file="./students.tpl" items=$upcomingAllocations type='allocations' title='Upcoming students'}
    </div>
{/block}