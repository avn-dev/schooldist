<div class="alert alert-info alert-dismissible">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
    ...
</div>
<!-- Man muss hier target="_blank" setzen, da Hubspot anscheinend Zugriff in iframes verbietet -->
<form method="post" target="_blank" class="form-horizontal" action="{route name="TsRdstation.ts_rdstation_forward"}">
	
	{if !$bConnected}
    <div class="box-footer">
        <button type="submit" class="btn btn-primary pull-right">{'RD Station aktivieren'|L10N}</button>
    </div>
	{else}
		{'Currently connected with account "%s"'|sprintf:$aAccountInfo.name}
	{/if}

    {foreach $oSession->getFlashBag()->get('success', array()) as $sMessage}
        <div class="alert alert-success alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <i class="icon fa fa-check"></i> {$sMessage}
        </div>
    {/foreach}
    {foreach $oSession->getFlashBag()->get('error', array()) as $sMessage}
        <div class="alert alert-danger alert-dismissible">
            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
            <i class="icon fa fa-check"></i> {$sMessage}
        </div>
    {/foreach}
</form>
