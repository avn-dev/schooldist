{extends file="system/bundles/AdminLte/Resources/views/base.tpl"}

{assign var=number value=0}

{function printCourseStructure level=0}
	<ul class="level{$level} sortable-list{if $level == 0} sortable-list-master{/if}">
	{foreach $items as $child}
		<li class="sortable-list-item sortableListsOpen{if $child->getType() == 'category'} folder{else} course course-{$child->getId()}{/if}" id="id-{$number++}" data-value="{$child->getData()|json_encode|escape}">
			<div class="">
				
				<span class="pull-right">
					<i data-page_id="" class="fa fa-plus ignore"></i>
					<i data-page_id="" class="fa fa-trash ignore"></i></a>
					<i data-page_id="" class="fa fa-edit ignore"></i></a>
				</span>
				
				<i data-page_id="" class="fa {if $child->getType() == 'course'}fa-book{else}fa-folder{/if}"></i>
				{$child->getName()}
			</div>
			{if !empty($child->getChilds())}
				{printCourseStructure items=$child->getChilds() level=$level+1}
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
			
			.sortable-list-master .sortable-list-item {
				cursor: move;
			}
			
			.sortable-list-master span.pull-right .fa-plus {
				display: none;
			}
			
			.sortable-list-available span.pull-right .fa-edit,
			.sortable-list-available span.pull-right .fa-trash {
				display: none;
			}
			
			.sortable-list-master li.course .fa-edit {
				display: none;
			}
			
			ul, ul.sortable-list, li.sortable-list-item {
				list-style-type:none;
				margin:0; 
				padding:0;
				
			}

			li.sortable-list-item {
				margin:4px; 
				border:1px solid #d2d6de;
			}

			.sortable-list-master li.sortable-list-item.folder {
				padding-left:40px;
			}
			
			li.sortable-list-item.area {
				color: #999;
			}
			
			li.sortable-list-item.area.ignore {
				cursor: not-allowed;
			}
			
			i.fa-trash,
			i.fa-edit,
			i.fa-plus,
			i.fa-minus {
				cursor: pointer!important;
				color: #000;
			}
			
			li.sortable-list-item.block {
				background-color: #fff;
				color: #000;
				cursor: move;
			}
			
			li.sortable-list-item div {
				padding: 3px 3px 3px 6px;
			}
			
			li.sortable-list-item div span {
				margin-right: 4px;
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
			
			li.sortable-list-item div:hover {
				background-color: #22bbff;
			}
			
			i.ignore {
				cursor: pointer;
			}
			
	</style>

{/block}

{block name="content"}
		<!-- Main content -->
		<section class="content">

			<div class="row">
				<div class="col-md-8">

					<div class="box box-default color-palette-box">
						<div class="box-header">
							<h3 class="box-title">{'Kursstruktur'|L10N} "{$school->getName()}"</h3>
							<div class="box-tools pull-right">
								{if $default}
									{'Standardstruktur'|L10N}
								{else}
									<button class="btn btn-default btn-xs" id="btn-reset">{'Struktur zurücksetzen'|L10N}</button>
								{/if}
							</div>
						</div>
						<div class="box-body">
							{printCourseStructure items=$structure->getChilds()}
						</div>
						<div class="box-footer">
							<button id="save" class="btn btn-primary pull-right">{'Speichern'|L10N}</button>
						</div>
					</div>

				</div>
				<div class="col-md-4">

					<div class="box box-default color-palette-box">
						<div class="box-header">
							<h3 class="box-title">{'Verfügbare Elemente'|L10N}</h3>
						</div>
						<div class="box-body">
							<ul class="level0 sortable-list sortable-list-available">
							{foreach $courses as $course}
								<li class="sortable-list-item course course-{$course->id}" data-value="{['course_id'=> $course->id]|json_encode|escape}" style="display:none;">
									<div class="">
										<span class="pull-right">
											<i data-page_id="" class="fa fa-plus ignore"></i>
											<i data-page_id="" class="fa fa-trash ignore"></i>
											<i data-page_id="" class="fa fa-edit ignore"></i>
										</span>
										<i data-page_id="" class="fa fa-book"></i>
										{$course->getName()}
									</div>
								</li>
							{/foreach}
								<li class="sortable-list-item folder" data-value="{}">
									<div class="">
										<span class="pull-right">
											<i data-page_id="" class="fa fa-plus ignore"></i>
											<i data-page_id="" class="fa fa-trash ignore"></i>
											<i data-page_id="" class="fa fa-edit ignore"></i>
										</span>
										<i data-page_id="" class="fa fa-folder"></i>
										{'Neue Kategorie'|L10N}
									</div>
								</li>
							</ul>
						</div>
					</div>

				</div>
			</div>
			
		</section>
										
		<div class="modal fade" id="modal-edit">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">{'Kategorie bearbeiten'|L10N}</h4>
              </div>
              <div class="modal-body">
				  <form id="modal-form">
					  <input type="hidden" name="modal-id" id="modal-id" value="">
					{foreach $languages as $language=>$label}
					<div class="form-group">
						<label for="modal-input-{$language}">{$label}</label>
						<input type="text" class="form-control" name="{$language}" id="modal-input-{$language}" placeholder="{$label|escape}">
					</div>
					{/foreach}
					<div class="form-group">
						<label for="modal-input-icon">{'Icon-Klasse'|escape}</label>
						<input type="text" class="form-control" name="icon" id="modal-input-icon" placeholder="{'Icon-Klasse'|escape}">
					</div>
				  </form>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-default pull-left" data-dismiss="modal">{'Schliessen'|L10N}</button>
                <button type="button" class="btn btn-primary" id="modal-save">{'Speichern'|L10N}</button>
              </div>
            </div>
            <!-- /.modal-content -->
          </div>
          <!-- /.modal-dialog -->
        </div>
        <!-- /.modal -->
		
		<div class="modal fade" id="modal-error">
          <div class="modal-dialog">
            <div class="modal-content">
              <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                  <span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">{'Es ist ein Fehler aufgetreten'|L10N}</h4>
              </div>
              <div class="modal-body">
				  {'Kurse dürfen nicht zusammen mit Kategorien auf einer Ebene sein!'|L10N}
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">{'Schliessen'|L10N}</button>
              </div>
            </div>
            <!-- /.modal-content -->
          </div>
          <!-- /.modal-dialog -->
        </div>
        <!-- /.modal -->
									
		
{/block}
					
{block name="footer"}
		<!-- Bootstrap WYSIHTML5 -->
		<script src="/assets/cms/js/jquery-sortable-lists.js?v={\System::d('version')}"></script>

		<script>
			
			function updateItemEvents() {
				
				$('.fa-trash').unbind('click');
				$('.fa-trash').click(function() {

					if(!confirm('{'Möchten Sie dieses Element wirklich löschen?'|L10N}')) {
						return false;
					}

					$(this).closest('li').remove();
				
					updateCourseVisibility();

				});

				$('.fa-edit').unbind('click');
				$('.fa-edit').click(function() {

					$('#modal-id').val($(this).closest('li').attr('id'));

					var data = $(this).closest('li').data('value');

					$('#modal-edit input[type=text]').each(function() {
						if(data[this.name]) {
							$('#modal-input-'+this.name).val(data[this.name]);
						} else {
							$('#modal-input-'+this.name).val('');
						}
					});
					
					$('#modal-edit').modal('show');
				});
				
			}
			
			function updateCourseVisibility() {
				
				$('.sortable-list-available li.course').show();
				$('.sortable-list-master li.course').each(function() {
					
					var data = $(this).data('value');
					$('.sortable-list-available li.course-'+data.course_id).hide();
					
				});
				
			}
			
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
							'margin-left': '-30px',
							'margin-right': '5px',
						}
					},
					isAllowed: function( cEl, hint, target ) {

						if(
							target.length == 0 && 
							cEl.hasClass('folder')
						) {
							
							hint.css('background-color', '#c6efce');
							return true;
							
						} else if(target.hasClass('folder')) {
							// Nur zwei Ebenenen
							if(
								cEl.hasClass('folder') && 
								target.prevObject.length > 1
							) {
								hint.css('background-color', '#ffc7ce');
								return false;
							}
							
							// Kurs
							if(!cEl.hasClass('folder')) {
								
								// Es gibt eine Kategorie
								if($(target).children('ul').children('li.folder').length > 0) {
									hint.css('background-color', '#ffc7ce');
									return false;
								}
								
								var data = cEl.data('value');
								
								// Kurs ist schon da.
								if($(target).children('ul').children('li.course-'+data.course_id).length > 0) {
									hint.css('background-color', '#ffc7ce');
									return false;
								}
								
							}
							
							hint.css('background-color', '#c6efce');
							return true;
						}
						
						hint.css('background-color', '#ffc7ce');
						return false;
					},
					onDragStart: function(e, el) {
						$(el).removeClass('bg-danger');
					},
					ignoreClass: 'ignore'
				};
				
				$('.sortable-list-master').sortableLists( options );

				updateItemEvents();
				updateCourseVisibility();

				$('#modal-save').click(function() {
					
					var li = $('#'+$('#modal-id').val());

					var data = li.data('value');
					
					if(typeof data != 'Object') {
						data = {};
					}
					
					$('#modal-edit input[type=text]').each(function() {
						data[this.name] = this.value;
					});

					li.data('value', data);

					$('#modal-edit').modal('hide');
					
				});

				$('i.fa-plus').on('click', function( e ) {
					
					var li = $(this).closest('li').get(0).cloneNode(true);
					
					if(li) {
						// Durchnummerieren weil muss da sein. Hat keine Relevanz.
						li.id = 'id-'+$('.sortable-list-master li').length;
						$('.sortable-list-master').append(li);
						updateItemEvents();
						updateCourseVisibility();
					}
					
				});

				$('#btn-reset').on('click', function() {
					
					$('.page-loader').show();
					
					$.ajax({
						type: "POST",
						url: '{route name="TsFrontend.course_structure_page_reset"}',
						success: function() {
							location.reload();
						}
					});
					
				});

				$('#save').on('click', function() {
					
					// Validieren: Alle Kurse durchlaufen, und sicherstellen, dass sie nicht mit Ordnern auf einer Ebene sind
					var bError = false;
					$('.sortable-list-master li.course').each(function() {
						if($(this).parent().children('li.folder').length > 0) {
							bError = true;
							$(this).addClass('bg-danger');
						}
					});
					
					if(bError === true) {
						$('#modal-error').modal('show');
						return false;
					}
					
					// Loader einblenden					
					$('.page-loader').show();
					
					// Daten absenden
					var aItems = $('.sortable-list-master').sortableListsToHierarchy();
					
					$.ajax({
						type: "POST",
						url: '{route name="TsFrontend.course_structure_page_save"}',
						data: JSON.stringify(aItems),
						success: function() {
							location.reload();
						}
					});
					
				});
		  });
		</script>
{/block}