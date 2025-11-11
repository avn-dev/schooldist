{extends file="system/bundles/AdminLte/Resources/views/base.tpl"}

{block name="header"}

{/block}

{block name="content"}
	
	<section class="content">
		
		{foreach $oSession->getFlashBag()->get('error', array()) as $sMessage}
			<div class="alert alert-danger alert-dismissible">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
				<i class="icon fa fa-check"></i> {$sMessage}
			</div>
		{/foreach}
		
		{foreach $oSession->getFlashBag()->get('success', array()) as $sMessage}
			<div class="alert alert-success alert-dismissible">
				<button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
				<i class="icon fa fa-check"></i> {$sMessage}
			</div>
		{/foreach}
		
		<div class="box box-default">
			<div class="box-header with-border">
				<h3 class="box-title">{$oApp->getTitle()}</h3>
			</div>
			
			{assign var=sContent value=$oApp->getContent()}
			
			{if $sContent}
			
				{$sContent|unescape:'html'}	
			
			{else}
				<div class="box-body">{'Keine Einstellungen verfügbar!'|L10N}</div>
				<!-- /.box-body -->
				<div class="box-footer">
					<a href="{route name="TcExternalApps.list"}" class="btn btn-default">{'Zurück'|L10N}</a>
				</div>
			{/if}
	
				
		</div>

    </section>	
				
{/block}	

{block name="footer"}

{/block}