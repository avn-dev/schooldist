{extends file="system/bundles/Tc/Resources/views/wizard/step_layout.tpl"}

{block name="form-attributes"}enctype='multipart/form-data'{/block}

{block name="step-content"}

    <div data-help-key="description">
        {$step->getHelpText('description', '')}
    </div>

    <div class="form-group {(isset($errors['import_file'])) ? 'has-error' : ''}">
        <label for="import_file" class="col-sm-2 control-label">
            {$wizard->translate('Datei (.xlsx, .csv)')}*
        </label>
        <div class="col-sm-10">

            <input type="file" id="import_file" name="import_file" class="form-control">

            {if isset($errors['import_file'])}
                <span class="help-block">
                    {foreach $errors['import_file'] as $message}
                        {$message}{if !$message@last}<br/>{/if}
                    {/foreach}
                </span>
            {/if}
        </div>
    </div>

    <div class="form-group">
        <div class="col-sm-offset-2 col-sm-10">
            <div class="checkbox">
                <label>
                    <input type="checkbox" id="ignore_first_row" name="ignore_first_row" value="1" {(!isset($old['ignore_first_row']) || $old['ignore_first_row'] == 1) ? 'checked' : ''}/> {$wizard->translate('Die erste Zeile der Datei enthält die Spaltennamen')}
                </label>
            </div>
        </div>
    </div>

{/block}

{block name="step-buttons"}
    {if !empty($demoFile)}
        <a href="{$demoFile}" class="btn btn-info" target="_blank">
            <i class="fa fa-download"></i>
            {$wizard->translate('Beispieldatei anzeigen')}
        </a>
    {/if}

    <button type="submit" class="btn btn-primary">{$wizard->translate('Importieren')}</button>
{/block}