{if $session->getFlashBag()->has('error')}
<div id='errorBox'>
    {foreach $session->getFlashBag()->get('error', array()) as $errorMessage}
        {$errorMessage}<br/>
    {/foreach}
</div>
{/if}


{if $session->getFlashBag()->has('success')}
    {foreach $session->getFlashBag()->get('success', array()) as $successMessage}
        {$successMessage}<br/>
    {/foreach}
{/if}

<div id='headerBox'></div>
<div id='outerBox' class='form-placementtest-old'>

{if $displayForm}
    <form method='post' action='' accept-charset="utf-8">
    <input type='hidden' name='r' value='{$key}'>

    {foreach $categories as $category}
        {if !empty($category->getQuestions())}
            <div class='categoryBox'>
                <h1>{$category->category}</h1>
            </div>
            <br/>
            <br/>
        {/if}
        {foreach $category->getQuestions() as $question}
            <div class='placementBox'>
                <div class='questionBox'>
                    {if isset($missingRequired[$question->id])}
                        <span style='color:red;'>
                            {$question->text}
                        </span>
                    {else}
                        {$question->text}
                    {/if}

                    {if $question->optional == 0}
                        {'*'}
                    {/if}
                </div>

                <div class='answerBox'>
                {assign var=answers value=$question->getJoinedObjectChilds('answers')}

                {if $question->type == $question::TYPE_SELECT}
                    <label for='ans{$question->id}' class='label'>&nbsp;</label>
                    <select id='ans{$question->id}' name='save[{$question->id}]' class='select'>
                    <option value=""></option>
                {elseif $question->type == $question::TYPE_MULTISELECT}
                    <label for='ans{$question->id}' class='label'>&nbsp;</label>
                    <select id='ans{$question->id}' multiple='multiple' name='save[{$question->id}][]' class='select_multiple'>
                {elseif $question->type == $question::TYPE_TEXT}
                    <input id='ans{$question->id}' type='text' name='save[{$question->id}]' value='{$results[$question->id]|escape}' class='text'/>
                {elseif $question->type == $question::TYPE_TEXTAREA}
                    <textarea id='ans{$question->id}' name='save[{$question->id}]' class='textarea'>{$results[$question->id]|escape}</textarea>
                {/if}

                {foreach $answers as $answer}
                    {if $question->type == $question::TYPE_CHECKBOX}
                        {assign var=checkedString value=""}
                        {if $results[$question->id][$answer->id] == $answer->id}
                            {assign var=checkedString value="checked='checked'"}
                        {/if}
                        <input id='ans{$answer->id}' type='checkbox' name='save[{$question->id}][{$answer->id}]' value='{$answer->id}' class='checkbox' {$checkedString}/>
                        <label for='ans{$answer->id}' class='label'>{$answer->text}</label>
                        <div style="clear:both"></div>
                    {/if}
                    {assign var=selectedString value=""}
                    {if
                    (
                        $question->type == $question::TYPE_SELECT &&
                        isset($results[$question->id]) &&
                        $answer->id == $results[$question->id]
                    ) ||
                    (
                        $question->type == $question::TYPE_MULTISELECT &&
                        isset($results[$question->id]) &&
                        in_array($answer->id, $results[$question->id])
                    )
                    }
                        {assign var=selectedString value='selected="selected"'}
                    {/if}
                    {if
                        $question->type == $question::TYPE_SELECT ||
                        $question->type == $question::TYPE_MULTISELECT
                    }
                        <option value='{$answer->id}' {$selectedString}>{$answer->text}</option>
                    {/if}
                {/foreach}
                {if
                    $question->type == $question::TYPE_SELECT ||
                    $question->type == $question::TYPE_MULTISELECT
                }
                        </select>
                {/if}
                        <div style="clear:both"></div>
                </div>
                <div class="cleaner">
                </div>
            </div>
        {/foreach}
    {/foreach}
    <div class='answerBox'>
        <br/>
        <br/>
    </div>
    <div id='optionBox'>
        {'* = required'|L10N}
    </div>
    <div id='submitBox'>
        <input type='submit' id='submit' value='{'Submit'|L10N}'/>
    </div>
    </form>
{/if}
</div>