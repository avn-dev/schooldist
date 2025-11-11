<!-- Umfrage -->
<#poll#>
	<#block_elements#>
	<table cellspacing="0" cellpadding="0" border="0" width="100%" class="poll_footer_2">
		<tr>
			<td width="120">
				<div style="width:106px;padding:1px;height:10px;border:1px solid #666666">
					<div style="float:left;background-color:#999999;width:<#progress#>%;height:10px;"><img src="/media/images/spacer.gif" height="10" width="1"></div>
				</div>
			</td>
			<td width="100"><#count_page_title#></td>
			<td align="right">
				<#last_page#><input type="button" class="btn" value="<#last_page_title#>" onclick="this.form.idPage.value = (this.form.idPage.value-2); this.form.submit();" /><#/last_page#>
				<#next_page#><input type="submit" class="btn" value="<#next_page_title#>" /><#/next_page#>
			</td>
		</tr>
	</table>

	<div style="clear:both;display:block;"></div>
<#/poll#>

<#block#>
	<h2><#block_title#></h2>
	<p class="pDescription"><#block_description#></p>
	<div class="poll_content"><#block_content#></div>
<#/block#>

<!-- Contentelemente -->

<!-- Eingabefeld -->
<#template_text#>
<table cellspacing="0" cellpadding="0" border="0" class="poll_table">
	<tr>
		<td style="width: 350px;"><#title#>&nbsp;</td>
		<td>
			<input type="text" class="txt w250" name="<#text_name#>" value="<#text_value#>" <#parameter#> />&nbsp;
		</td>
	</tr>
</table>
<#/template_text#>

<!-- Eingabebereich -->
<#template_textarea#>
<table cellspacing="0" cellpadding="0" border="0" class="poll_table">
	<tr>
		<td style="width: 350px;"><#title#></td>
		<td><textarea class="txt" name="<#textarea_name#>" <#parameter#>><#textarea_value#></textarea></td>
	</tr>
</table>
<#/template_textarea#>

<!-- Dropdown-->
<#template_select#>
<table cellspacing="0" cellpadding="0" border="0" class="poll_table">
	<tr>
		<td style="width: 350px;"><#title#>&nbsp;</td>
		<td>
			<select class="txt" name="<#select_name#>" <#parameter#>>
				<#select_list#>
				<option value="<#select_item_value#>" <#selected#>><#select_item_title#></option>
				<#/select_list#>
			</select>&nbsp;
		</td>
	</tr>
</table>
<#/template_select#>

<!-- Mehrfachauswahl -->
<#template_list#>
<table cellspacing="0" cellpadding="0" border="0" class="poll_table">
	<tr>
		<td style="width: 350px;"><#title#></td>
		<td><select class="txt" name="<#list_name#>" multiple>
				<#list_list#>
				<option value="<#list_item_value#>" <#selected#>><#list_item_title#></option>
				<#/list_list#>
			</select>
		</td>
	</tr>
</table>
<#/template_list#>

<!-- Radiobuttons -->
<#template_radio#>
<table cellspacing="0" cellpadding="0" border="0" class="poll_table radio">
	<tr>
		<td style="width: 350px;"><#title#></td>
	</tr>
	<tr>
		<td>
			<#radio_list#>
				<input type="radio" name="<#radio_name#>" value="<#radio_item_value#>" <#selected#>> <#radio_item_title#><br />
			<#/radio_list#>
		</td>
	</tr>
</table>
<#/template_radio#>

<!-- Checkbox -->
<#template_check#>
<table cellspacing="0" cellpadding="0" border="0" class="poll_table checkbox">
	<tr>
		<td style="width: 350px;"><#title#></td>
	</tr>
	<tr>
		<td>
			<#check_list#>
				<input type="checkbox" name="<#check_name#>" value="<#check_item_value#>" <#selected#>> <#check_item_title#><br />
			<#/check_list#>
		</td>
	</tr>
</table>
<#/template_check#>

<!-- Blockcontainer -->
<#template_block#>
<table cellspacing="0" cellpadding="0" border="0" class="poll_table">
	<#content_elements#>
</table>
<#/template_block#>

<!-- Blockanfang -->
<#template_block_start#>
<tr>
	<th style="width: 350px;">&nbsp;</th>
	<#block_start_title_loop#>
	<th><#block_start_title#></th>
	<#/block_start_title_loop#>
</tr>
<tr>
	<td style="width: 350px;<#parameter#>"><#title#></td>
	<#block_start_value_loop#>
	<td align="center" <#block_start_parameter#>><#block_start_value#></td>
	<#/block_start_value_loop#>
	<td><#title_right#></td>
</tr>
<#/template_block_start#>

<!-- Blockelement -->
<#template_block_item#>
<tr>
	<td style="width: 350px;<#parameter#>"><#title#></td>
	<#block_item_value_loop#>
	<td align="center" <#block_item_parameter#>><#block_item_value#></td>
	<#/block_item_value_loop#>
	<td><#title_right#></td>
</tr>
<#/template_block_item#>

<#template_reference#>
<table cellspacing="0" cellpadding="0" border="0" class="poll_table">
	<tr>
		<td><#title#></td>
		<td>
			<select class="txt" name="<#reference_name#>" <#parameter#>>
				<#reference_list#>
				<option value="<#reference_item_value#>" <#selected#>><#reference_item_title#></option>
				<#/reference_list#>
			</select>
		</td>
	</tr>
</table>
<#/template_reference#>

<!-- Fehlermeldung für die Pflichtfelder -->
<#error_message#>
<div class="error_message"><#error_message_title#></div>
<#/error_message#>

<!-- Wrap um den Titel der Frage -->
<#error_code#>
<div class="error_code"><#content_element#></div>
<#/error_code#>