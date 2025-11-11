<dl class="row">
    {if isset($startDate)}
        <dt class="col-md-3">Starttag</dt>
        <dd class="col-md-9">
            {$startDate}
        </dd>
    {/if}
    {if isset($duration)}
        <dt class="col-md-3">Dauer (Wochen)</dt>
        <dd class="col-md-9">
            {$duration}
        </dd>
    {/if}
    {if isset($scheduleFrom)}
        <dt class="col-md-3">Termine ab</dt>
        <dd class="col-md-9">
            {$scheduleFrom}
        </dd>
    {/if}
    {if isset($days)}
        <dt class="col-md-3">Tage</dt>
        <dd class="col-md-9">
            {foreach from=$days item=day}
                {$day}
            {/foreach}
        </dd>
    {/if}
    {if isset($languages)}
        <dt class="col-md-3">Sprachen</dt>
        <dd class="col-md-9">
            {$languages}
        </dd>
    {elseif isset($language)}
        <dt class="col-md-3">Sprache</dt>
        <dd class="col-md-9">
            {$language}
        </dd>
    {/if}
</dl>