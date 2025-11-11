{* @TODO Wird nicht verwendet *}

{extends file="system/bundles/AdminLte/Resources/views/base.tpl"}

{block name="header"}
{/block}

{block name="content"}

		<!-- Content Header (Page header) -->
		<section class="content-header">
		  <h1>
			{'Anwesenheitsübersicht'|L10N}
			<small>{$sFrom} - {$sUntil}</small>
		  </h1>
		</section>

		<!-- Main content -->
		<section class="content">

			{foreach $aClasses as $aClass}
			<div class="box box-default">
				<div class="box-header with-border">
				  <h3 class="box-title">{$aClass.class}</h3>

				  <div class="box-tools pull-right">
					<button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-minus"></i>
					</button>
				  </div>
				  <!-- /.box-tools -->
				</div>
				<!-- /.box-header -->
				<div class="box-body table-responsive no-padding">

					<table class="table table-bordered table-hover" style="width: auto;table-layout:fixed;">
						<thead>
							<tr>
								<td></td>
								{foreach $aClass.month as $sMonth=>$aMonth}
								<th colspan="{count($aMonth.dates)}">{$aMonth.label}</th>
								{/foreach}
								<td></td>
								<td></td>
								<td></td>
							</tr>
							<tr>
								<td style="width: 200px;"></td>
								{foreach $aClass.month as $sMonth=>$aMonth}
								{foreach $aMonth.dates as $oDate}
								<th style="width: 34px;text-align: right;">{$oDate->format('d')}</th>
								{/foreach}
								{/foreach}
								<th style="width: 34px;font-size: 11px;" class="bg-success" title="{'Anwesend'|L10N}"></th>
								<th style="width: 34px;font-size: 11px;" class="bg-danger" title="{'Abwesend'|L10N}"></th>
								<th style="width: 60px;">{'%'|L10N}</th>
							</tr>
						</thead>
						<tbody>
							{foreach $aClass.students as $aStudent}
								{assign var=iPresent value=0}
							<th>{$aStudent.name}</th>
							{foreach $aClass.month as $sMonth=>$aMonth}
							{foreach $aMonth.dates as $oDate}
							<td class="{if $aStudent['dates'][$oDate->format('Y-m-d')]}{assign var=iPresent value=$iPresent+1}bg-success{/if}"></td>
							{/foreach}
							{/foreach}
							<td class="text-right">{$iPresent}</td>
							<td class="text-right">0</td>
							<td class="text-right">100 %</td>
							{/foreach}
						</tbody>
					</table>

				</div>
				<!-- /.box-body -->
			</div>
			{/foreach}

		</section>

{/block}
					
{block name="footer"}
{/block}
