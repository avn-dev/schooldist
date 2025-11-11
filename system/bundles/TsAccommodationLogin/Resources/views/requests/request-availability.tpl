{extends file="system/bundles/TsAccommodationLogin/Resources/views/layout/login.tpl"}

{block name="title"}{$oSchool->name|L10N}{/block}

{block name="login"}
	
	<h4>{$info}</h4>
	
	{if $task}
		<form method="post" action="{route name='TsAccommodationLogin.accommodation_request_availability_confirm' task=$task key=$key}"

		{if $task === 'reject'}
			<p>{'Please confirm that you would like to reject this request.'|L10N}</p>
			<p>
				<input type="submit" class="btn btn-danger btn-block" value="{'Reject'|L10N}">
			</p>
		{else if $task === 'accept'}

				<p>{'Please select the bed in which you wish to place the student and confirm the request.'|L10N}</p>

				<div class="form-group">
					<label for="room_id">{'Room / Bed'|L10N}</label>
					<select class="form-control" id="room_id" name="room_id">
						{html_options options=$availableBeds}
					</select>
				</div>

				<p>
					<input type="submit" class="btn btn-success btn-block" value="{'Confirm'|L10N}">
				</p>

		{/if}

		</form>
	{/if}
		
{/block}

