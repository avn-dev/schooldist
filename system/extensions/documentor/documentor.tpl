{literal}
	<script type="text/javascript">
		function toggleContent(iID)
		{
			$('c_'+iID).toggle();
		}
	</script>
{/literal}

<div style="padding:30px;">
	<form method="post" action="">
		<div>
			Bitte die zu dokumentierende Erweiterung auswählen:
			<select name="dir">
				{foreach from=$aExtensions item=aExtension}
					<option value="{$aExtension.file}" {if $sSelected == $aExtension.file}selected="selected"{/if}>{$aExtension.output}</option>
				{/foreach}
			</select>
			<input type="hidden" name="action" value="show_docu" />
			<input type="submit" value="Dokumentation anzeigen" />
			<br /><br />
		</div>
	</form>

	{if count($aOutput) > 0}
		{assign var="iCounter" value=1}
		{foreach from=$aOutput item=aValue key=sFile}
			{assign var="iCounter" value=$iCounter+1}
			{if count($aValue.classes) > 0 || count($aValue.functions) > 0}
				<div style="border: 1px solid #CCC; padding-left:5px; margin-bottom:3px; cursor:pointer;" onclick="toggleContent({$iCounter});">
					<h3>{$sFile}</h3>
				</div>
				<div style="display:none;" id="c_{$iCounter}">
					<ul>
						{foreach from=$aValue.classes.classes item=aName key=iKey}
							<li>
								{$aName}
								{if $aValue.classes.comments.$iKey != ''}
									<br />
									<span style="color:#AAA;">{$aValue.classes.comments.$iKey}</span>
									<br />
								{/if}
								<br />
								<ul>
									<li>
										<b>Eigenschaften:</b>
										<br />
										<ul>
											{foreach from=$aValue.classes.properties.$iKey item=aProperty}
												<li>
													{$aProperty.property}
													<br />
													<span style="color:#AAA;">{$aProperty.comment}</span><br /><br />
												</li>
											{/foreach}
										</ul>
									</li>
									<li>
										<b>Methoden:</b>
										<br />
										<ul>
											{foreach from=$aValue.classes.methods.$iKey item=aMethod}
												<li>
													{$aMethod.method}
													<br />
													<span style="color:#AAA;">{$aMethod.comment}</span><br /><br />
												</li>
											{/foreach}
										</ul>
									</li>
								</ul>
							</li>
						{/foreach}
						{if count($aValue.functions) > 0}
							<li>
								<b>Funktionen:</b>
								<br />
								<ul>
									{foreach from=$aValue.functions item=aFunction}
										<li>
											{$aFunction.function}
											<br />
											<span style="color:#AAA;">{$aFunction.comment}</span>
										</li>
									{/foreach}
								</ul>
							</li>
						{/if}
					</ul>
				</div>
			{/if}
		{/foreach}
	{/if}
</div>

{*out var=$aExtensions*}
{*out var=$aOutput*}