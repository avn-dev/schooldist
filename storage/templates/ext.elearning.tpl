{if $bActive == 1}

	<form method="post" action="{$_SERVER.PHP_SELF}">
	<input type="hidden" name="task" value="" />

	{if $sShow == 'result'}

		{if $aExam.show_result}

		#page:_LANG['Ergebnis']#

		Max: {$iMaximumScore}<br/>
		Min: {$iMinimumScore}<br/>
		Result: {$iResultScore}<br/>

			{if $aExam.show_answers}
				Falsche Antworten:<br/>
				{foreach from=$aWrongQuestions item=aWrongQuestion}
					<p>{$aWrongQuestion.question}<br/>
					{foreach from=$aWrongQuestion.wrong_answers item=aAnswer}
						{$aAnswer.answer}<br/>
					{/foreach}
					{foreach from=$aWrongQuestion.correct_answers item=aAnswer}
						{$aAnswer.answer}<br/>
					{/foreach}
				{/foreach}
			{/if}

			<a href="{$sResultPdfPath}">PDF</a>

			{if $bShowCommentField}
				<textarea name="comment"></textarea>
				<input type="button" value="Kommentar abgeben" onclick="this.form.task.value='comment'; this.form.submit();" />
			{/if}

		{/if}

	{elseif $sShow == 'comment'}

		<h1>{$aExam.name}<h1>

		<p>Vielen Dank, der Kommentar wurden gespeichert!</p>

	{elseif $sShow == 'intro'}
		<h1>{$aExam.name}<h1>
		{$aExam.intro}

		<a href="{$_SERVER.PHP_SELF}?task=start">Start</a>

	{else}

		{if $bErrorNoAnswer}
			#page:_LANG['Bitte beantworten Sie die Frage!']#
		{/if}

		{foreach from=$aGroups item=aGroup}

			{foreach from=$aGroup.questions item=aQuestion}

				<p>{$aGroup.name} - {$aQuestion.question}</p>

				{if $aQuestion.type == 'true_false'}



				{else}

					
					<h1>{$aGroup.name} - #page:_LANG['Question']# {$aQuestion.name}</h1>
					<div class="divLine"></div>
					
					{if $bErrorNoAnswer}
						<p class="pError">#page:_LANG['Please answer the question!']#</p>
					{/if}
					
					<h2>{$aQuestion.question}</h2>
					
					{if $aQuestion.wrong}
						<p class="pError">#page:_LANG['Leider haben Sie diese Frage nicht korrekt beantwortet!']#</p>
					{/if}
					
					{foreach from=$aQuestion.answers item=aAnswer}
		
						<div class="divInput">
						{if $aQuestion.type == 'choice_unique'}

							<input id="answer_{$aAnswer.id}" type="radio" name="result[{$aQuestion.id}]" value="{$aAnswer.id}" {if $aAnswer.checked}checked="checked"{/if} {if $aQuestion.disabled}disabled="disabled"{/if} />

						{elseif $aQuestion.type == 'choice_multiple'}

							<input id="answer_{$aAnswer.id}" type="checkbox" name="result[{$aQuestion.id}][]" value="{$aAnswer.id}" {if $aAnswer.checked}checked="checked"{/if} {if $aQuestion.disabled}disabled="disabled"{/if} />

						{/if}
						</div>
						
						{if $aQuestion.wrong}
							{if $aAnswer.correct}
							<label style="color: green;" for="answer_{$aAnswer.id}">
							{elseif $aAnswer.checked}
							<label style="color: red;" for="answer_{$aAnswer.id}">
							{else}
							<label for="answer_{$aAnswer.id}">
							{/if}
						{else}
							<label for="answer_{$aAnswer.id}">
						{/if}
						{$aAnswer.answer}</label>

					{/foreach}

					{if $aQuestion.wrong}
						<div class="divCleaner"></div>
						<p>
							<strong>#page:_LANG['Comment']#:</strong><br/>
							{$aQuestion.description}
						</p>
					{/if}


				{/if}

			{/foreach}

		{/foreach}

		<input type="button" value="zurück" onclick="this.form.task.value='back'; this.form.submit();" />
		<input type="button" value="weiter" onclick="this.form.task.value='next'; this.form.submit();" />

	{/if}

	</form>

{else}

	#page:_LANG['Diese Prüfung ist aktuell nicht aktiv.']#

{/if}