{extends file="system/bundles/AdminLte/Resources/views/base.tpl"}

{block name="header"}
		<!-- bootstrap wysihtml5 - text editor -->
		<link rel="stylesheet" href="/wdmvc/assets/adminlte/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.min.css">
		
		<style>
			.timeline > li > .timeline-item {
				margin-right: 0;
			}
			.timeline > li {
				margin-right: 0;
			}
			textarea {
				width: 100%;
				height: 100px; font-size: 14px; line-height: 18px; border: 1px solid #dddddd; padding: 10px;
			}
		</style>
		
{/block}

{block name="content"}
		<script>
			var sClass = '{$sClass|escape:"javascript"}';
			var iId = '{$iId|escape:"javascript"}';
		</script>

		<!-- Content Header (Page header) -->
		<section class="content-header">
		  <h1>
			{'Notizen'|L10N}
			<small>{$oEntity->getName()}</small>
		  </h1>
		</section>

		<!-- Main content -->
		<section class="content">

			{if Access_Backend::getInstance()->hasRight(['core_communication_notes', 'new'])}
			<div class="box collapsed-box">
				<form method="post">
				<div class="box-header with-border">
					<h3 class="box-title">{'Neue Notiz'|L10N}</h3>

					<div class="box-tools pull-right">
					  <button type="button" class="btn btn-box-tool" data-widget="collapse"><i class="fa fa-plus"></i>
					  </button>
					</div>
					<!-- /.box-tools -->
				</div>
				<div class="box-body pad">

					<textarea class="textarea" name="notice" placeholder="{'Neue Notiz'|L10N|escape}"></textarea>

				</div>
				<div class="box-footer">
					<button type="submit" class="btn btn-primary pull-right">{'Notiz speichern'|L10N}</button>
				</div>
				</form>
			</div>
			{/if}

			<form method="post" action="{route name="Notices.notices_save" sClass=$sClass iId=$iId}">
			<ul class="timeline">

				{assign var=sDate value=""}
				{foreach $aNotices as $oNotice}

				{if $sDate != $oNotice->created|date_format:'%d.%m.%Y'}
				<!-- timeline time label -->
				<li class="time-label">
					<span class="bg-red">
						{$oNotice->created|date_format:'%d.%m.%Y'}
					</span>
				</li>
				<!-- /.timeline-label -->
				{/if}
				
				<!-- timeline item -->
				<li data-id="{$oNotice->id}">
					<!-- timeline icon -->
					<i class="fa fa-sticky-note bg-blue"></i>
					<div class="timeline-item">
						<span class="time">
							<i class="fa fa-clock-o"></i> {$oNotice->created|date_format:'%H:%M'}
						</span>

						<h3 class="timeline-header"><strong>{$oNotice->getJoinedObject('creator')->firstname} {$oNotice->getJoinedObject('creator')->lastname}</strong> {if empty($iId)}{$oController->getEntity($oNotice->entity, $oNotice->entity_id)->getName()}{/if}</h3>

						<div class="timeline-body">
							{$oNotice->getJoinedObject('latest_version')->notice}
						</div>

						<div class="timeline-footer text-right">

							<a href="/notices/interface/view?entity={$sClass}&id={$iId}" class="btn btn-default btn-xs cancel" style="display: none;">{'Abbrechen'|L10N}</a>
							<button type="submit" class="btn btn-primary btn-xs save" style="display: none;">{'Speichern'|L10N}</button>

							{if Access_Backend::getInstance()->hasRight(['core_communication_notes', 'edit'])}
							<button type="button" class="btn btn-primary btn-xs edit">{'Editieren'|L10N}</button>
							{/if}
							{if Access_Backend::getInstance()->hasRight(['core_communication_notes', 'delete'])}
							<button type="button" class="btn btn-danger btn-xs delete">{'Löschen'|L10N}</button>
							{/if}
						</div>

					</div>
				</li>
					{assign var=sDate value=$oNotice->created|date_format:'%d.%m.%Y'}
				{/foreach}

				<!-- END timeline item -->
				<li>
					<i class="fa fa-clock-o bg-gray"></i>
				</li>

			</ul>
			</form>

		</section>
{/block}
					
{block name="footer"}
		<!-- Bootstrap WYSIHTML5 -->
		<script src="/wdmvc/assets/adminlte/plugins/bootstrap-wysihtml5/bootstrap3-wysihtml5.all.min.js?v={\System::d('version')}"></script>

		<script>
		  $(function () {
			//bootstrap WYSIHTML5 - text editor
			$(".textarea").wysihtml5({
				toolbar: {
					image: false,
					link: false,
					html: true,
					color: true
				}
			});
			
			toastr.options = {
				"closeButton": true,
				"progressBar": true
			  }

			$('.edit').click(function() {

				var container = $(this).parents('li');

				$('.edit,.delete').hide();
				container.find('.save,.cancel').show();

				var body = $(this).parent().parent().find('.timeline-body');

				var editor = $('<div>').addClass('editor-container');
				var textarea = $('<textarea>').addClass('editor').attr('name', 'versions['+$(this).parents('li').data('id')+']');

				body.html(editor.html(textarea.html(body.html())));

				textarea.wysihtml5({
					toolbar: {
						image: false,
						link: false,
						html: true,
						color: true
					}
				});

			});

			$('.delete').click(function() {

				if(confirm('{'Soll diese Notiz wirklich gelöscht werden?'|L10N}')) {
					location.href = '{route name="Notices.notices_delete" sClass=$sClass iId=$iId}?noticeId='+$(this).parents('li').data('id');
				}

			});

			  {foreach $session->getFlashBag()->get('error', []) as $sMessage}
			  toastr.error('{$sMessage|escape}');
			  {/foreach}

			  {foreach $session->getFlashBag()->get('success', []) as $sMessage}
			  toastr.success('{$sMessage|escape}');
			  {/foreach}

		  });
		</script>
{/block}