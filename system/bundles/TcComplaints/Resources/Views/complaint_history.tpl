<div class="p-1 bg-gray-50 rounded-md">
	<div class="complaint_history_header">
		<h5>{$name}</h5>
	</div>
	<!-- /.box-header -->
	<div class="box-body no-padding">
		<div class="mailbox-read-info px-2">
			<div class="rounded p-1 bg-gray-100/50">{$comment}</div>
			<div>
                <span class="font-semibold">{'Status'|L10N}:</span> {$comment_state}
				{if $comment_followup != ''}
					- {$comment_followup}
				{/if}
			    <span class="pull-right font-semibold">
                    {$comment_date}
                </span>
            </div>
		</div>
	</div>
	<!-- /.box-body -->
</div>