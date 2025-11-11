{extends file="system/bundles/Tc/Resources/views/wizard/step_layout.tpl"}

{block name="header-additional"}
    <link rel="stylesheet" href="/admin/extensions/gui2/jquery/css/jquery-ui.min.css">
    <link rel="stylesheet" href="/admin/extensions/gui2/jquery/css/jquery-ui.structure.min.css">
    <link rel="stylesheet" href="/admin/extensions/gui2/jquery/css/jquery-ui.theme.min.css">
    <link rel="stylesheet" href="/admin/extensions/gui2/jquery/css/ui.multiselect.css">
{/block}

{block name="form-attributes"}enctype='multipart/form-data'{/block}

{block name="step-content"}

    <div data-help-key="description" style="margin-bottom: 10px">
        {$step->getHelpText('description', '')}
    </div>

    {foreach $form->getFieldsWithValues() as $key => $field}
        {if $field['type'] === \Tc\Service\Wizard\Structure\Form::HEADING}
            <{$field['config']['size']}>{$field['label']}</{$field['config']['size']}>
        {elseif $field['type'] === \Tc\Service\Wizard\Structure\Form::SEPARATOR}
            <hr/>
        {else}
            <div class="form-group {$field['config']['css_class']} {(isset($errors[$key])) ? 'has-error' : ''}" style="{($field['type'] === \Tc\Service\Wizard\Structure\Form::FIELD_HIDDEN) ? 'display: none;' : ''}">
                <label for="{$key}" class="col-sm-2 control-label">
                    {if !isset($field['config']['show_label']) || $field['config']['show_label'] === true}
                        {$field['label']}
                        {if $field['config']['rules']|contains:'required'}*{/if}
                        {if !empty($field['config']['tooltip'])}
                            <i class="fa fa-info-circle info-icon" data-help-key="{$key}" data-toggle="tooltip" title="{$field['config']['tooltip']|strip_tags}""></i>
                        {elseif $helpTextMode}
                            <i class="fa fa-info-circle info-icon inactive" data-help-key="{$key}"></i>
                        {/if}
                    {/if}
                </label>
                <div class="col-sm-10">
                    <div class="{(isset($field['config']['addon'])) ? 'input-group' : ''}">

                        {if
                            $field['type'] === \Tc\Service\Wizard\Structure\Form::FIELD_INPUT ||
                            $field['type'] === \Tc\Service\Wizard\Structure\Form::FIELD_HIDDEN
                        }
                            {if isset($field['config']['addon'])}
                                <span class="input-group-addon">{$field['config']['addon']}</span>
                            {/if}
                            <input type="text" id="{$key}" name="{$key}" class="form-control" value="{($old[$key]) ? $old[$key] : $field['value']}" />
                        {elseif $field['type'] === \Tc\Service\Wizard\Structure\Form::FIELD_COLOR}
                            <input type="text" id="{$key}" name="{$key}" class="form-control" value="{($old[$key]) ? $old[$key] : $field['value']}" />
                            <span class="input-group-addon"><i></i></span>
                        {elseif $field['type'] === \Tc\Service\Wizard\Structure\Form::FIELD_PASSWORD}
                            {if isset($field['config']['addon'])}
                                <span class="input-group-addon">{$field['config']['addon']}</span>
                            {/if}
                            <input type="password" id="{$key}" name="{$key}" class="form-control" value="{($old[$key]) ? $old[$key] : $field['value']}" />
                        {elseif $field['type'] === \Tc\Service\Wizard\Structure\Form::FIELD_UPLOAD}
                            {if isset($field['config']['addon'])}
                                <span class="input-group-addon">{$field['config']['addon']}</span>
                            {/if}
                            <input type="file" id="{$key}" name="{$key}" class="form-control" />
                        {elseif $field['type'] === \Tc\Service\Wizard\Structure\Form::FIELD_DATE}
                            <span class="input-group-addon"><i class="fa fa-calendar"></i></span>
                            <input type="input" id="{$key}" name="{$key}" class="form-control" value="{($old[$key]) ? $old[$key] : $field['value']}" />
                        {elseif $field['type'] === \Tc\Service\Wizard\Structure\Form::FIELD_CHECKBOX}
                            <input type="hidden" name="{$key}" class="" value="0" />
                            <input type="checkbox" id="{$key}" name="{$key}" value="1" {($old[$key] != 0 || $field['value'] != 0) ? 'checked' : ''} />
                        {elseif $field['type'] === \Tc\Service\Wizard\Structure\Form::FIELD_SELECT}
                            {assign var=value value=($old[$key]) ? $old[$key] : $field['value']}

                            {if $field['config']['multiple']}
                                <select id="{$key}" name="{$key}[]" class="form-control" multiple="multiple">
                            {else}
                                <select id="{$key}" name="{$key}" class="form-control">
                            {/if}
                                {foreach $field['config']['options'] as $optionKey => $optionLabel}
                                    <option value="{$optionKey}" {((is_array($value) && in_array($optionKey, $value)) || (is_scalar($value) && $optionKey == $value)) ? 'selected' : ''}>{$optionLabel}</option>
                                {/foreach}
                            </select>
                        {/if}
                    </div>

                    {if isset($errors[$key])}
                        <span class="help-block">
                            {foreach $errors[$key] as $message}
                                {$message}{if !$message@last}<br/>{/if}
                            {/foreach}
                        </span>
                    {/if}
                </div>
            </div>
        {/if}
    {/foreach}

    {block name="form-content"}{/block}

{/block}

{block name="step-buttons"}
    <button type="submit" class="btn btn-primary">{$wizard->translate('Weiter')}</button>
{/block}

{block name="footer-additional-js" prepend}

    <script src="/admin/extensions/gui2/jquery/ui/jquery-ui.min.js"></script>
    <script src="/admin/extensions/gui2/jquery/ui/ui.multiselect.js"></script>

    <script>
        $(function() {
            $('form .colorpicker-element').colorpicker();

            {assign var=dateFormat value=$form->getDateFormat()|replace:"%d":'dd'|replace:"%m":'mm'|replace:"%Y":'yyyy'|replace:"%Y":'yy'}

            {if !empty($dateFormat)}
                $('form .datepicker-element input').bootstrapDatePicker({
                    language: '{\System::getInterfaceLanguage()}',
                    format: '{$dateFormat}',
                    autoclose: true
                });
            {/if}

            var l10n = {
                addAll: '{$wizard->translate('Alle')} +',
                removeAll: '{$wizard->translate('Alle')} -',
                itemsCount: '{$wizard->translate('ausgewählt')}'
            };

            $('form select[multiple = multiple]').multiselect({ldelim}sortable: false, searchable: true, locale: l10n{rdelim});

        });
    </script>
{/block}