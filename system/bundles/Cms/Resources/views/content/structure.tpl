{extends file="system/bundles/AdminLte/Resources/views/base.tpl"}

{function printStructure iLevel=0}

	<ul class="level{$iLevel} sortable-list{if $iLevel == 0} sortable-list-master{/if}">
	{foreach $aItems as $aChild}
		<li class="sortable-list-item sortableListsOpen {$aChild['type']}{if $aChild['type'] == 'area'} ignore{/if}{if $aChild['display_condition'] === true} display_condition{/if}" {if $aChild['id']}id="block_{$aChild['id']}"{/if}{if $aChild['area']}id="area_{$aChild['area']}"{/if}>
			<div class="{$aChild['type']}{if $aChild['type'] == 'area'} ignore{/if}">
				{if $aChild['type'] == 'block'}<i data-content_id="{$aChild['id']}" class="fa fa-trash ignore"></i></a> {/if}
				{if $aChild['type'] == 'area'}<i data-area="{$aChild['area']}" class="fa fa-edit ignore"></i></a> {/if}
				{$aChild['label']}
			</div>
			{if !empty($aChild['childs'])}
				{printStructure aItems=$aChild['childs'] iLevel=$iLevel+1}
			{/if}
		</li>
	{/foreach}
	</ul>
{/function}

{block name="header"}

		<style>

			li.sortable-list-item {
				border: 1px solid #d2d6de;
			}
			
			ul.sortable-list, li.sortable-list-item {
				list-style-type:none;
				margin:0; 
				padding:0;
				
			}

			li.sortable-list-item {
				padding-left:50px;
				margin:5px; 
				border:1px solid #d2d6de;
			}
			
			li.sortable-list-item.area {
				color: #999;
			}
			
			li.sortable-list-item.area.ignore {
				cursor: not-allowed;
			}
			
			i.fa-trash,
			i.fa-edit {
				cursor: pointer!important;
				color: #000;
			}
			
			li.sortable-list-item.block {
				background-color: #fff;
				color: #000;
				cursor: move;
			}
			
			li.sortable-list-item div {
				padding:7px;
			}
			
			#sortableListsPlaceholder {
				background-color: #ff8;
			}
			
			#sortableListsHint {
				background-color: #bbf;
			}
			
			#sortableListsHintWrapper {
				background-color: #bbf;
			}
			
			li.sortable-list-item.block.display_condition {
				background-color: #e98b7f;
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
			{'Blockübersicht'|L10N}
			<small>{$oPage->title}</small>
		  </h1>
		</section>

		<!-- Main content -->
		<section class="content">
			
			<div class="box">
				<div class="box-body pad">
				  
					{printStructure aItems=$aStructure}

				</div>
			</div>
			
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
						
						if(cEl.hasClass('area')) {
							hint.css('background-color', '#ff9999');
							return false;
						}
						
						if(!target.hasClass('area')) {
							hint.css('background-color', '#ff9999');
							return false;
						} else {
							hint.css('background-color', '#99ff99');
							return true;
						}
					},
					ignoreClass: 'ignore',
					complete: function() {
						var aItems = $('.sortable-list-master').sortableListsToArray();
						$.ajax({
							type: "POST",
							url: '/wdmvc/cms/content/save-structure',
							data: JSON.stringify(aItems)
						});
					}
				};
				
				$('.sortable-list-master').sortableLists( options );

				if(parent && parent.Page) {
					parent.Page.showActions();
					parent.Page.selectTab('structure');
				}

				$('.fa-trash').click(function() {
					
					if(!confirm('{'Möchten Sie den Block wirklich löschen?'|L10N}')) {
						return false;
					}
					
					var iContentId = $(this).data('content_id');
					$.ajax({
						type: "POST",
						url: '/wdmvc/cms/content/delete-content',
						data: 'content_id='+iContentId,
						success: function() {
							$('#block_'+iContentId).remove();
						}
					});
				});

				$('.fa-edit').click(function() {

					var sArea = $(this).data('area');
					
					var aArea = sArea.split('_');

					parent.Page.editArea(aArea[0], aArea[1]);

				});

		  });
		</script>
{/block}