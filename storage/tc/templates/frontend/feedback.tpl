<style>
	html {
		font-family: Arial, sans-serif;
	}
	.question {
		width: 150px;
	}
	div.contain {
		padding: 5px 0px 5px 0px;
		vertical-align: middle;
		display: flex
	}
	div.question, div.answer {
		border-bottom: 1px dotted #7B7B7B;
		padding: 8px;
	}
	div.headline-title {
		font-weight:bold;
		text-align:center;
	}
	h1, h2, h3 {
		color: #F10000;
	}
	.button {
		background-color: #F10000;
		border: 1px solid #F10000;
		border-radius: 3px;
		color: #FFFFFF;
		cursor: pointer;
		font-size: 12px;
		font-weight: bold;
		line-height: 15px;
		padding: 3px 10px;
	}
	.rating {
		float:left;
	}
	.rating:not(:checked) > input {
		position:absolute;
		clip:rect(0,0,0,0);
	}
	.rating:not(:checked) > label {
		float:right;
		width:1em;
		padding:0 .1em;
		overflow:hidden;
		white-space:nowrap;
		cursor:pointer;
		font-size:200%;
		line-height:1.2;
		color:#ddd;
		text-shadow:1px 1px #bbb, 2px 2px #666, .1em .1em .2em rgba(0,0,0,.5);
	}
	.rating:not(:checked) > label:before {
		content: '★ ';
	}
	.rating > input:checked ~ label {
		color: #f70;
		text-shadow:1px 1px #c60, 2px 2px #940, .1em .1em .2em rgba(0,0,0,.5);
	}
	.rating:not(:checked) > label:hover,
	.rating:not(:checked) > label:hover ~ label {
		color: gold;
		text-shadow:1px 1px goldenrod, 2px 2px #B57340, .1em .1em .2em rgba(0,0,0,.5);
	}
	.rating > input:checked + label:hover,
	.rating > input:checked + label:hover ~ label,
	.rating > input:checked ~ label:hover,
	.rating > input:checked ~ label:hover ~ label,
	.rating > label:hover ~ input:checked ~ label {
		color: #ea0;
		text-shadow:1px 1px goldenrod, 2px 2px #B57340, .1em .1em .2em rgba(0,0,0,.5);
	}
	.rating > label:active {
		position:relative;
		top:2px;
		left:2px;
	}
	.error {
		color: #FF0000;
		font-weight: bold;
	}
	.success {
		color: #088A08;
		font-weight: bold;
	}
</style>
{block name="header_css"}{/block}

{if $sSuccess ne ""}
<span class="success">{$sSuccess}</span>
{elseif $sError ne ""}
<span class="error">{$sError}</span>
{else}
<span class="error">{$sFormError}</span>
<form class="form-feedback" action="{$actionUrl}" method="post">
	<input type="hidden" name="save">
	<input type="hidden" name="csrf" value="{$sCsrf}">
	{foreach from=$aQuestions item=questionGroupsValue}
	{if $questionGroupsValue.heading.text ne ""}
	<{$questionGroupsValue.heading.type}>{$questionGroupsValue.heading.text}</{$questionGroupsValue.heading.type}>
{else}
<div class="contain">
	<div class="question">{$questionGroupsValue.questionText} {if $questionGroupsValue.questionRequired eq "1"}*{/if}</div>
	{foreach from=$questionGroupsValue.columns item=column}
		<div class="answer">
			<div>
				<div class="headline-title">{$column.title}</div>
				<div>
					{if $questionGroupsValue.questionType eq "stars"}
						<div class="rating">
							{$sCheckedStar = $aAnswers[{$questionGroupsValue.questionGroupQuestionId}][{$column.dependencyId}]}
							{for $star=0 to $questionGroupsValue.quantityStars - 1}
								{$iCurrentStar = $questionGroupsValue.quantityStars - $star}
								<input type="radio" value="{$questionGroupsValue.quantityStars - $star}" name="question[{$questionGroupsValue.questionGroupQuestionId}][{$column.dependencyId}]" id="star_{$star}_{$questionGroupsValue.questionGroupQuestionId}_{$column.dependencyId}" {if $sCheckedStar == $iCurrentStar}checked{/if} />
								<label for="star_{$star}_{$questionGroupsValue.questionGroupQuestionId}_{$column.dependencyId}">{$iCurrentStar}</label>
							{/for}
						</div>
					{elseif $questionGroupsValue.questionType eq "textfield"}
						<textarea name="question[{$questionGroupsValue.questionGroupQuestionId}][{$column.dependencyId}]">{$aAnswers[{$questionGroupsValue.questionGroupQuestionId}][{$column.dependencyId}]}</textarea>
					{else}
						{foreach from=$questionGroupsValue.options item=option}
							<input type="radio" value="{$option.id}" id="question[{$questionGroupsValue.questionGroupQuestionId}][{$column.dependencyId}]" name="question[{$questionGroupsValue.questionGroupQuestionId}][{$column.dependencyId}]" {if {$aAnswers[{$questionGroupsValue.questionGroupQuestionId}][{$column.dependencyId}]} eq {$option.id}}checked{/if} />
							<label for="question[{$questionGroupsValue.questionGroupQuestionId}][{$column.dependencyId}]">{$option.title}</label> <br />
						{/foreach}
					{/if}
				</div>
			</div>
		</div>
	{/foreach}
</div>
{/if}
{/foreach}
<p><i>* {$sQuestionRequiredLabel}</i></p>
<p><input type="submit" name="submit" value="{$sButtonLabel}" class="button" /></p>
</form>
{/if}
