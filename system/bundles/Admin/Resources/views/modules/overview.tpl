{extends file="system/bundles/AdminLte/Resources/views/base.tpl"}

{block name="header"}
{/block}

{block name="content"}
		
		<!-- Content Header (Page header) -->
		<section class="content-header">
		  <h1>
			{'Erweiterungen'|L10N}
		  </h1>
		</section>

		<!-- Main content -->
		<section class="content">

			<div class="box box-default">
				
				<div class="box-body">

					{$sContent}

				</div>
				<!-- /.box-body -->
				<div id="container-loading" class="overlay" style="display: none;">
					<i class="fa fa-refresh fa-spin"></i>
				</div>
			</div>
			
		</section>

{/block}
					
{block name="footer"}
{/block}
