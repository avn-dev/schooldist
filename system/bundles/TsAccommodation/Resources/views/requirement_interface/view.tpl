{extends file="system/bundles/AdminLte/Resources/views/base.tpl"}

{block name="header"}
    <link rel="stylesheet" href="/admin/extensions/gui2/jquery/css/ui.multiselect.css?v=3.893"/>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.0/themes/smoothness/jquery-ui.css"/>
    <style>
        .timeline > li > .timeline-item {
            margin-right: 0;
        }

        .timeline > li {
            margin-right: 0;
        }

        #name, #validity {
            max-width: 95%;
        }

        #buttonsDiv button {
            margin-bottom: 10px;
        }
    </style>
{/block}

{block name="content"}
    <!-- Main content -->
    <section class="content-header">
        <h1>
            {'Voraussetzung'|L10N}
            <small>{$oRequirement->getName()}</small>
        </h1>
    </section>

    <section class="content">

        {if $sRequiredFrom == 'accommodation_provider'}
            {if $bDocumentMissing == true}
                <div class="alert alert-danger">{'Dokument fehlt'|L10N}!</div>
            {/if}
            {if $bDocumentExpired == true}
                <div class="alert alert-warning">{'Dokument abgelaufen'|L10N}!</div>
            {/if}
        {else}
            {if !empty($aMembersWithMissingDocuments)}
                <div class="alert alert-danger">
                    {'Zugehörige mit fehlenden Dokumenten:'|L10N}
                    <ul>
                    {foreach $aMembersWithMissingDocuments as $oMember}
                        <li>{$oMember->lastname}, {$oMember->firstname}</li>
                    {/foreach}
                    </ul>
                </div>
            {/if}
            {if !empty($aMembersWithExpiredDocuments)}
                <div class="alert alert-warning">
                    {'Zugehörige mit abgelaufenen Dokumenten:'|L10N}<br>
                    <ul>
                    {foreach $aMembersWithExpiredDocuments as $oMember}
                        <li>{$oMember->lastname}, {$oMember->firstname}</li>
                    {/foreach}
                    </ul>
                </div>
            {/if}
        {/if}
        <form method="post" class="form-horizontal" enctype="multipart/form-data">
            <!-- Für jeden Vorhandenen Nachweis einen Block erzeugen -->
            {foreach $aDocuments as $oDocument}
                <div class="box box-solid box-requirement {if !$oDocument->isValid()}box-warning{/if}" data-id="{$oDocument->id}">

                    <div class="box-header with-border">
                        <h3 class="box-title">{'Voraussetzung'|L10N}</h3>
                        <div class="box-tools pull-right">
                            <button type="button" class="btn btn-box-tool" data-widget="remove"><i
                                        class="fa fa-times"></i></button>
                        </div>
                        <!-- /.box-tools -->
                    </div>
                    <!-- /.box-header -->
                    <div class="box-body">
                        <div class="form-group">
                            <label class="col-sm-2 control-label" for="file_{$oDocument->id}">{'Dokument hochladen'|L10N}</label>
                            <div class="col-sm-10">
                                <input id="file_{$oDocument->id}" name="save[{$oDocument->id}][file]" type="file">
                                {if $oDocument->file}
                                    <span class="help-block">{'Aktuelles Dokument'|L10N}: <a href="{$oDocument->getFileUrl()}" target="_blank">{$oDocument->file}</a></span>
                                {/if}
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-sm-2 control-label" for="name">{'Bezeichnung'|L10N}</label>
                            <div class="col-sm-10">
                                <input id="name" name="save[{$oDocument->id}][name]" class="form-control" placeholder="{'Bezeichnung'|L10N}" type="text" value="{$oDocument->name}">
                            </div>
                        </div>
                        <div class="form-group always_valid_container">
                            <div class="col-sm-offset-2 col-sm-10">
                                <div class="checkbox">
                                    <label class="control-label" for="always_valid_{$oDocument->id}">
                                        <input class="always_valid" id="always_valid_{$oDocument->id}"
                                               name="save[{$oDocument->id}][always_valid]" value="1" type="checkbox"
                                               {if $oDocument->always_valid == 1}checked{/if}>
                                        {'Unbegrenzt gültig'|L10N}
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="form-group validity_container"{if $oDocument->always_valid != 0} style="display:none;" {/if}>
                            <label class="col-sm-2 control-label" for="validity_{$oDocument->id}">{'Gültigkeit'|L10N}</label>
                            <div class="col-sm-10">
                                <input id="validity_{$oDocument->id}" name="save[{$oDocument->id}][valid]"
                                       class="form-control datepicker validity" placeholder="{'Gültigkeit'|L10N}"
                                       value="{$oDateFormat->formatByValue($oDocument->valid)}" type="text">
                            </div>
                        </div>
                        {if $sRequiredFrom == 'member'}
                            <div class="form-group">
                                <label class="col-sm-2 control-label" for="requirement_person">{'Person'|L10N}</label>
                                <div class="col-sm-10">
                                    <select id="requirement_person" name="save[{$oDocument->id}][members][]"
                                            class="form-control multiple" placeholder="Person" multiple required>
                                        {html_options options=$aMembers selected=$oDocument->members}
                                    </select>
                                </div>
                            </div>
                        {/if}
                    </div>
                    <!-- /.box-body -->
                </div>
            {/foreach}
            <div class="box box-solid" id="buttonsDiv">
                <button type="submit" name="addbutton" value="1" class="btn btn-primary pull-left"><i
                            class="fa fa-plus"></i></button>
                <button type="submit" name="savebutton" value="1"
                        class="btn btn-primary pull-right">{'Speichern'|L10N}</button>
            </div>
        </form>
    </section>
    <!-- Content Header (Page header) -->

{/block}

{block name="footer"}

    <script>
		var datepicker = jQuery.fn.datepicker.noConflict();
		jQuery.fn.bootstrapDatePicker = datepicker;
    </script>

    <script type="text/javascript" src="https://code.jquery.com/ui/1.12.0/jquery-ui.min.js"></script>
    <script type="text/javascript" src="/admin/extensions/gui2/jquery/ui/ui.multiselect.js"></script>
    <script>

		$(window).on("load", function () {

			$('.box-requirement').on('removed.boxwidget', function () {

				var iId = $(this).attr('data-id');

				$('form').append('<input type="hidden" name="delete[]" value="' + iId + '">');

			});

			toastr.options = {
				"closeButton": true,
				"progressBar": true
			};

            {if $bSave}
			toastr.success('{'Die Daten wurden erfolgreich gespeichert.'|L10N}');
            {/if}

			var ojQLocale = {
				addAll: '{'Alle +'|L10N}',
				removeAll: '{'Alle -'|L10N}',
				itemsCount: '{'ausgewählt'|L10N}'
			};

			$('select.multiple').multiselect({ldelim}sortable: false, searchable: true, locale: ojQLocale{rdelim});

			$('.datepicker').bootstrapDatePicker({
				format: '{$sDatepickerFormat}'
			});

			$('.always_valid').change(function () {

				var oValidityContainer = $(this).closest('.always_valid_container').next();

				if (this.checked) {
					oValidityContainer.slideUp('slow');
				} else {
					oValidityContainer.slideDown('slow');
					oValidityContainer.find('.validity').val('');
				}
			});

		});
    </script>
{/block}