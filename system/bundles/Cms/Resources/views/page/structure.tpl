{extends file="system/bundles/Cms/Resources/views/content/structure.tpl"}

{function printPageStructure iLevel=0}
	<ul class="level{$iLevel} sortable-list{if $iLevel == 0} sortable-list-master{/if}">
	{foreach $aItems as $aChild}
		<li class="sortable-list-item sortableListsOpen{if $aChild['folder'] == true} folder{/if}" id="page_{$aChild['page-id']}">
			<div class="">
				<span class="pull-right">
					{if !$aChild['folder'] && !$aChild['indexpage']}
					<i data-page_id="{$aChild['page-id']}" class="fa fa-home ignore"></i></a>
					{/if}
					<i data-page_id="{$aChild['page-id']}" class="fa fa-trash ignore"></i></a>
					<i data-page_id="{$aChild['page-id']}" class="fa fa-edit ignore"></i></a>
				</span>
				<i data-page_id="{$aChild['page-id']}" class="{$aChild.icon}"></i></a> 
				{$aChild['text']}
			</div>
			{if !empty($aChild['nodes'])}
				{printPageStructure aItems=$aChild['nodes'] iLevel=$iLevel+1}
			{/if}
		</li>
	{/foreach}
	</ul>
{/function}

{block name="header" append}

	<style>

			li.sortable-list-item div:hover {
				background-color: #22bbff;
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
			{'Seitenverwaltung'|L10N:'CMS'}
			<small>{$oPage->title}</small>
		  </h1>
		</section>

		<!-- Main content -->
		<section class="content">

			<form method="get">
				<select name="site_id" class="form-control" onChange="this.form.submit();">
					<option value="">{'Bitte wählen'|L10N:'CMS'}</option>
					{html_options options=$aSites selected=$iSiteId}
				</select>
			</form>
			<br>
			
			{if $iSiteId}
			<div class="nav-tabs-custom">
				<ul class="nav nav-tabs">
					{foreach $aLanguages as $aLanguage}
					<li{if $aLanguage.code == $sLanguage} class="active"{/if}><a href="{route name="Cms.cms_structure_edit"}?site_id={$iSiteId|escape}&language={$aLanguage.code}">{$aLanguage.name}</a></li>
					{/foreach}
				</ul>
				<div class="tab-content">
				  {printPageStructure aItems=$aStructure}
				</div>
			</div>
			{/if}
			
		</section>
{/block}
					
{block name="footer"}
		<!-- Bootstrap WYSIHTML5 -->
		<script src="/assets/cms/js/jquery-sortable-lists.js?v={\System::d('version')}"></script>

		<script>
			$(function () {
				var options = {
					opener: {
						active: true,
						as: 'html',  // if as is not set plugin uses background image
						close: '<i class="fa fa-minus c3"></i>',  // or 'fa-minus c3',  // or './imgs/Remove2.png',
						open: '<i class="fa fa-plus"></i>',  // or 'fa-plus',  // or'./imgs/Add2.png',
						openerCss: {
							'display': 'inline-block',
							//'width': '18px', 'height': '18px',
							'float': 'left',
							'margin-left': '-35px',
							'margin-right': '5px',
							//'background-position': 'center center', 'background-repeat': 'no-repeat',
							'font-size': '1.1em'
						}
					},
					isAllowed: function( cEl, hint, target ) {

						if(target.hasClass('folder')) {
							hint.css('background-color', '#c6efce');
							return true;
						}
						
						hint.css('background-color', '#ffc7ce');
						return false;
					},
					ignoreClass: 'ignore',
					complete: function() {
						var aItems = $('.sortable-list-master').sortableListsToArray();
						$.ajax({
							type: "POST",
							url: '/admin/cms/structure/save',
							data: JSON.stringify(aItems)
						});
					}
				};
				
				$('.sortable-list-master').sortableLists( options );

				$('.fa-trash').click(function() {

					if(!confirm('{'Möchten Sie diese Seite wirklich löschen?'|L10N}')) {
						return false;
					}

					var iPageId = $(this).data('page_id');
					$.ajax({
						type: "POST",
						url: '/admin/cms/structure/delete',
						data: 'page_id='+iPageId,
						success: function() {
							$('#page_'+iPageId).remove();
						}
					});
				});

				$('.fa-edit').click(function() {

					var iPageId = $(this).data('page_id');

					parent.Page.editPage(iPageId);

				});

				$('.fa-home').click(function() {

					var iPageId = $(this).data('page_id');
					$.ajax({
						type: "POST",
						url: '/admin/cms/structure/home/'+iPageId,
						success: function() {
							location.reload();
						}
					});

				});

		  });
		</script>
{/block}