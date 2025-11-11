 
<form name="poll" method="post" action="{$sFormAction}">
<input type="hidden" name="poll_id" value="{$iPollId|escape}" />
<input type="hidden" name="idPage" value="{$iPageId|escape}" />
<input type="hidden" name="loop" value="{$iLoop|escape}" />
<input type="hidden" name="tan" value="{$tan|escape}" />
<input type="hidden" name="task" value="saveStats" />
<input type="hidden" name="action" value="nextPage" />

{if $bError}
	{foreach from=$aErrorMessages item=sErrorMessage}
		<div class="error_message">{$sErrorMessage}</div>
	{foreachelse}
		<div class="error_message">{$aPollConfig.error}</div>
	{/foreach}
{/if}

{foreach from=$aParagraphs item=aParagraph}
	{if $aParagraph.title}
	<h2>{$aParagraph.title}</h2>
	{/if}
	{if $aParagraph.description}
	<p class="pDescription">{$aParagraph.description}</p>
	{/if}
	<div class="poll_content">
	{foreach from=$aParagraph.questions item=aQuestion}

		{if $aQuestion.error}
			<div class="error_code">
		{/if}

		{if $aQuestion.hidden == 1}

			<input type="hidden" name="{$aQuestion.name|escape}" value="{$aQuestion.value|escape}" />

		{elseif $aQuestion.hidden == 2}

			<input type="hidden" name="{$aQuestion.name|escape}" value="{$aQuestion.value|escape}" />

		{else}

			{if $aQuestion.template == 'block_start' || $aQuestion.template == 'block_item'}

				{if	$aQuestion.template == 'block_start'}

					<table cellspacing="0" cellpadding="0" border="0" class="poll_table" style="width: 100%; table-layout: fixed;">
						<colgroup>
							<col style="width: {$aParagraph.label_width};" />
							{foreach from=$aQuestion.data item=aOption}
								<col {$aOption.parameter} />
							{/foreach}
						</colgroup>
						<tbody id="question_container_{$aQuestion.id}">
							<tr>
								<th style="width: {$aParagraph.label_width};">&nbsp;</th>
								{if $aQuestion.block_head}
									<th colspan="{$aQuestion.block_head_colspan}">{$aQuestion.block_head}</th>
								{else}
									{foreach from=$aQuestion.data item=aOption}
										<th>{$aOption.text|escape}</th>
									{/foreach}
								{/if}
							</tr>
							<tr {$aQuestion.input_addon}>
								<td style="width: {$aParagraph.label_width};">{$aQuestion.title}</td>
								{foreach from=$aQuestion.data item=aOption}
									<td align="center" {$aOption.parameter}><input type="radio" class="{$aOption.class|escape}" name="{$aOption.name|escape}" value="{$aOption.value|escape}" {if $aOption.selected}checked="checked"{/if} style="{$aOption.css}" /></td>
								{/foreach}
								{if $aQuestion.title_right}
								<td>{$aQuestion.title_right}</td>
								{/if}
							</tr>
						</tbody>
					
					</table>
					

				{else}

					<table cellspacing="0" cellpadding="0" border="0" class="poll_table" style="width: 100%; table-layout: fixed;">
						<colgroup>
							<col style="width: {$aParagraph.label_width};" />
							{foreach from=$aQuestion.data item=aOption}
								<col {$aOption.parameter} />
							{/foreach}
						</colgroup>
						<tbody>
						<tr {$aQuestion.input_addon} id="question_container_{$aQuestion.id}">
							<td style="width: {$aParagraph.label_width};">{$aQuestion.title}</td>
							{foreach from=$aQuestion.data item=aOption}
								<td align="center" {$aOption.parameter}><input type="radio" class="{$aOption.class|escape}" name="{$aOption.name|escape}" value="{$aOption.value|escape}" {if $aOption.selected}checked="checked"{/if} style="{$aOption.css}" /></td>
							{/foreach}
							{if $aQuestion.title_right}
							<td>{$aQuestion.title_right}</td>
							{/if}
						</tr>
						</tbody>
					
					</table>
					

				{/if}

			{elseif $aQuestion.template == 'matrix'}

				{if $aQuestion.title}
				<p>{$aQuestion.title}</p>
				{/if}
				
				<table cellspacing="0" cellpadding="0" border="0" class="poll_table">
					<tr>
						<th style="width: {$aParagraph.label_width};">&nbsp;</th>
						{foreach from=$aQuestion.data.answer_groups item=aAnswerGroup}
							<th colspan="{$aAnswerGroup.cols}" style="{$aAnswerGroup.css_value}" {$aAnswerGroup.parameter}>{$aAnswerGroup.title|escape}</th>
						{/foreach}
					</tr>
					<tr>
						<th style="width: {$aParagraph.label_width};">&nbsp;</th>
						{foreach from=$aQuestion.data.answer_groups item=aAnswerGroup}
							{foreach from=$aAnswerGroup.options item=aAnswerGroupOption}
							<th style="{$aAnswerGroupOption.css_value}" {$aAnswerGroupOption.parameter}>{$aAnswerGroupOption.title|escape}</th>
							{/foreach}
						{/foreach}
					</tr>
					{foreach from=$aQuestion.data.questions item=aQuestionsQuestion}
					<tr {$aQuestionsQuestion.parameter} style="{$aQuestionsQuestion.css_value}">
						<td style="width: {$aParagraph.label_width};{$aQuestion.parameter}">{$aQuestionsQuestion.title}</td>
						{foreach from=$aQuestion.data.answer_groups item=aAnswerGroup}
						
							{if $aAnswerGroup.type == 'text'}
								<td style="width: {$aParagraph.label_width};{$aQuestion.css_value}">
									<input type="text" class="txt w250 {$aQuestion.class|escape}" name="result[{$aQuestion.id}][{$aQuestionsQuestion.key}][{$aAnswerGroup.key}]" value="{$aQuestion.value[$aQuestionsQuestion.key][$aAnswerGroup.key]|escape}" {$aQuestion.parameter} />&nbsp;
								</td>
							{elseif $aAnswerGroup.type == 'select' ||$aAnswerGroup.type == 'list' ||$aAnswerGroup.type == 'reference'}
								<td style="width: {$aParagraph.label_width};{$aQuestion.css_value}">
									<select class="txt {$aQuestion.class|escape}" name="result[{$aQuestion.id}][{$aQuestionsQuestion.key}][{$aAnswerGroup.key}]" {$aQuestion.parameter} style="{$aQuestion.css_value}">
									{foreach from=$aAnswerGroup.options item=aOption}
										<option value="{$aOption.value|escape}" {if $aOption.selected[$aQuestionsQuestion.key]}selected="selected"{/if}>{$aOption.text|escape}</option>
									{/foreach}
									</select>
								</td>
							{elseif $aAnswerGroup.type == 'radio'}
								{foreach from=$aAnswerGroup.options item=aOption}
									<td style="{$aOption.css_value}"><input type="radio" name="result[{$aQuestion.id}][{$aQuestionsQuestion.key}][{$aAnswerGroup.key}]" style="{$aOption.css_value}" value="{$aOption.value|escape}" {if $aOption.selected[$aQuestionsQuestion.key]}checked="checked"{/if}> {$aOption.text|escape}<br /></td>
								{/foreach}
							{elseif $aAnswerGroup.type == 'check'}
								{foreach from=$aAnswerGroup.options item=aOption}
									<td style="{$aOption.css_value}"><input type="checkbox" name="result[{$aQuestion.id}][{$aQuestionsQuestion.key}][{$aAnswerGroup.key}][]" style="{$aOption.css_value}" value="{$aOption.value|escape}" {if $aOption.selected[$aQuestionsQuestion.key]}checked="checked"{/if}> {$aOption.text|escape}<br /></td>
								{/foreach}
							{/if}

						{/foreach}
					</tr>
					{/foreach}

				</table>

			{elseif $aQuestion.template == 'stars'}
				
				<table cellspacing="0" cellpadding="0" border="0" class="poll_table" id="question_container_{$aQuestion.id}">
					<tr>
						<td style="width: {$aParagraph.label_width};{$aQuestion.css}">{$aQuestion.title}&nbsp;</td>
						<td>
							{foreach from=$aQuestion.data item=aOption}
								<input type="radio" name="{$aOption.name|escape}" class="{$aOption.class|escape}" value="{$aOption.value|escape}" {if $aOption.selected}checked="checked"{/if}> {$aOption.text|escape}<br />
							{/foreach}
						</td>
					</tr>
				</table>
						
			{else}

				<table cellspacing="0" cellpadding="0" border="0" class="poll_table" id="question_container_{$aQuestion.id}">
				{if $aQuestion.template == 'text'}
					<tr>
						<td style="width: {$aParagraph.label_width};">{$aQuestion.title}&nbsp;</td>
						<td>
							<input type="text" class="txt w250 {$aQuestion.class|escape}" name="{$aQuestion.name}" value="{$aQuestion.value|escape}" {$aQuestion.parameter} />&nbsp;
						</td>
					</tr>
				{elseif $aQuestion.template == 'textarea'}
					<tr>
						<td style="width: {$aParagraph.label_width};{$aQuestion.css}">{$aQuestion.title}&nbsp;</td>
						<td>
							<textarea class="txt {$aQuestion.class|escape}" name="{$aQuestion.name}" {$aQuestion.parameter} style="{$aQuestion.css_input}">{$aQuestion.value|escape}</textarea>
						</td>
					</tr>
				{elseif $aQuestion.template == 'select' ||$aQuestion.template == 'list' ||$aQuestion.template == 'reference'}
					<tr>
						<td style="width: {$aParagraph.label_width};{$aQuestion.css}">{$aQuestion.title}&nbsp;</td>
						<td>
							<select class="txt {$aQuestion.class|escape}" name="{$aQuestion.name}" {$aQuestion.parameter} {$aQuestion.css_input}>
							{foreach from=$aQuestion.data item=aOption}
								<option value="{$aOption.value|escape}" {if $aOption.selected}selected="selected"{/if}>{$aOption.text|escape}</option>
							{/foreach}
							</select>&nbsp;
						</td>
					</tr>
				{elseif $aQuestion.template == 'radio'}
					<tr>
						<td style="width: {$aParagraph.label_width};{$aQuestion.css}">{$aQuestion.title}&nbsp;</td>
						<td>
							{foreach from=$aQuestion.data item=aOption}
								<input type="radio" name="{$aOption.name|escape}" class="{$aOption.class|escape}" value="{$aOption.value|escape}" {if $aOption.selected}checked="checked"{/if}> {$aOption.text|escape}<br />
							{/foreach}
						</td>
					</tr>
				{elseif $aQuestion.template == 'check'}
					<tr>
						<td style="width: {$aParagraph.label_width};{$aQuestion.css}">{$aQuestion.title}&nbsp;</td>
						<td>
							{foreach from=$aQuestion.data item=aOption}
								<input type="checkbox" name="{$aOption.name|escape}" class="{$aOption.class|escape}" value="{$aOption.value|escape}" {if $aOption.selected}checked="checked"{/if}> {$aOption.text|escape}<br />
							{/foreach}
						</td>
					</tr>
				{/if}
				</table>
			{/if}

		{/if}

		{if $aQuestion.error}
		</div>
		{/if}

	{/foreach}
	</div>
{/foreach}

{if $bPdf == false}
<table cellspacing="0" cellpadding="0" border="0" width="100%" class="poll_footer_2">
	<tr>
		<td width="120">
			<div style="width:106px;padding:1px;height:10px;border:1px solid #666666">
				<div style="float:left;background-color:#999999;width:{$iProgress}%;height:10px;"><img src="/media/spacer.gif" height="10" width="1"></div>
			</div>
		</td>
		<td width="100">
			{if 
				1 ||
				$iPollId == 130 && 
				$iCurrentPage != 1 &&
				$iCurrentPage != $iTotalPages
			}
				{$aPollConfig.page}
			{/if}
		</td>
		<td align="right">
			{if $bBack && $aPollConfig.last != ''}<input type="button" class="btn" value="{$aPollConfig.last}" onclick="this.form.idPage.value = -1; this.form.submit();" />{/if}
			{if $bForward}<input type="submit" class="btn" value="{$aPollConfig.next}" />{/if}
		</td>
	</tr>
</table>
{/if}
<div style="clear:both;display:block;"></div>

</form>
