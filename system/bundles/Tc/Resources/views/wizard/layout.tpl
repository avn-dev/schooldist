{extends file="system/bundles/AdminLte/Resources/views/base.tpl"}

{block name="header"}
    <link rel="stylesheet" href="/assets/tc/css/wizard.css?v={System::d('version')}" />
    <link rel="stylesheet" href="/admin/assets/custom/css/custom.css?v={System::d('version')}" />
    {block name="header-additional"}{/block}
{/block}


{block name="content"}

    {block name="sidebar"}{/block}

    <main class="{block name="main-class"}{/block}">
        <section class="content-header">
            {if $wizard->heading}
                <h1>
                    {block name="header-before"}{/block}
                    {$wizard->heading}
                    {if $wizard->subHeading}
                        <small> {$wizard->subHeading} </small>
                    {/if}
                </h1>
            {/if}
            {block name="breadcrumbs"}{/block}
        </section>
        <section class="content">

            {foreach $wizard->getMessages('success') as $message}
                <div class="alert alert-success alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <i class="icon fa fa-check"></i> {$message}
                </div>
            {/foreach}

            {foreach $wizard->getMessages('error') as $message}
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <i class="icon fa fa-times"></i> {$message}
                </div>
            {/foreach}

            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">{block name="box-title"}{/block}</h3>
                    <div class="box-tools pull-right">{block name="box-tags"}{/block}</div>
                </div>

                {block name="box-content"}{/block}

            </div>

        </section>

    </main>

    {if $helpTextMode}
        <div class="modal fade out" id="helpTextModal">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span></button>
                        <h4 class="modal-title">{$wizard->translate('Info-Text')}</h4>
                    </div>
                    <form method="post" action="{$wizard->route('help-text.save')}" class="form-horizontal">
                        <input type="hidden" id="helpTextKey" name="key"/>
                        <input type="hidden" id="helpTextField" name="field"/>
                        <div class="modal-body">
                            {foreach $helpTextLanguages as $language}
                                <div class="form-group">
                                    <label for="helpTextLanguage_{$language[0]}" class="col-sm-2 control-label">
                                        {$language[1]}
                                    </label>
                                    <div class="col-sm-10">
                                       <textarea id="helpTextLanguage_{$language[0]}" name="values[{$language[0]}]" class="form-control html-element help-text" rows="5" style="resize: vertical"></textarea>
                                    </div>
                                </div>
                            {/foreach}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-default pull-left" data-dismiss="modal">{$wizard->translate('Abbrechen')}</button>
                            <button type="submit" class="btn btn-primary">{$wizard->translate('Speichern')}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    {/if}

{/block}


{block name="footer"}

    <script type="text/javascript" src="/tinymce/resource/basic/tinymce.min.js?v=1.814"></script>

    <script>
        var datepicker = jQuery.fn.datepicker.noConflict();
        jQuery.fn.bootstrapDatePicker = datepicker;

        var tooltip = jQuery.fn.tooltip.noConflict();
        jQuery.fn.bootstrapTooltip = tooltip;
    </script>

    <script>
        $(function() {
            $('[data-toggle="tooltip"]').bootstrapTooltip();

            initTinyMce();

        });

        function editHelpText(key, field, type) {

            fetch('{$wizard->route('help-text.load')}?key='+key+'&field='+field)
                .then(function (response) {
                    response.json().then(function(json) {
                        tinyMCE.remove();
                        $('textarea.help-text').val("");
                        $('#helpTextKey').val(key);
                        $('#helpTextField').val(field);
                        Object.keys(json.texts).forEach(function (language) {
                            $('#helpTextLanguage_'+language).val(json.texts[language]);
                        });
                        initTinyMce();

                        $('#helpTextModal').modal('show');
                    });
                })
        }

        function initTinyMce() {
            tinymce.init({
                selector: '.html-element',
                language: '{System::getInterfaceLanguage()}',
                mode: "none",
                theme: "modern",
                skin: "lightgray",
                plugins:[ 'searchreplace code fullscreen preview table visualblocks visualchars image charmap save',
                    'contextmenu link importcss'],
                toolbar1: ' undo redo | searchreplace pastetext visualblocks visualchars link | preview code fullscreen | table formatselect removeformat | responsivefilemanager ' ,
                toolbar2:'bold italic underline charmap | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent ',
                menubar: false,
                image_advtab: true ,
                relative_urls: false,
                verify_html: false,
                convert_urls: false,
                remove_script_host: true,
                external_plugins: { "filemanager" : "/tinymce/resource/filemanager/plugin.min.js"},
            });
        }
    </script>

    {block name="footer-additional-js"}{/block}
{/block}