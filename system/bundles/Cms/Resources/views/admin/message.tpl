{extends file="system/bundles/AdminLte/Resources/views/base.tpl"}

{block name="content"}
	
		<!-- Content Header (Page header) -->
		<section class="content-header">
		  <h1>
			{'Information'|L10N:'CMS'}
			<small>{$oPage->title}</small>
		  </h1>
		</section>

		<!-- Main content -->
		<section class="content">
			
			<div class="box box-info">
				<div class="box-body pad">
				  
					{$sMessage}

				</div>
				{if $sButtonLabel}
				<div class="box-footer">
					<a class="btn btn-primary pull-right" href="{$sButtonTarget}">{$sButtonLabel}</a>
				</div>
				{/if}
			</div>
			
		</section>
{/block}