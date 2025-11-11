<div class="drop" id="{$uid}" data-action="{$request_file}">
    <div class="hidden">
        <input type="hidden" class="file_types" value="{$file_types}"/>
        {if $max_file_size !== null}
            <input type="hidden" class="max_file_size" value="{$max_file_size}"/>
        {/if}
        <input type="hidden" class="current_files" value="{$current_files}"/>
        <input type="file" multiple="multiple" class="file_input" />
    </div>
    <div class="bg"></div>
    <div class="content">
        <img class="preview" style="display: none;" />
        <div class="fileinfo">
            <div class="filename"></div>
        </div>
        <div class="progress_info">
            <div class="progress_spacer" style="">
                <div class="progress" style="display:none;"></div>
            </div>
            <div class="upload_response"></div>
            <button class="btn btn-primary start_btn" onclick="return false;" type="button">{$btn_upload}</button>
            <button class="btn btn-default add_files_btn" onclick="return false;">{$btn_add}</button>
            <button class="btn btn-default delete_files_btn" onclick="return false;">{$btn_delete}</button>
            <div style="clear: both;"></div>
        </div>
    </div>    
    <div style="clear: both"></div>
    <div class="error" style="display:none"></div>
    <div class="error2" style="display:none">An error occurred while uploading the file</div>
    <div class="abort" style="display:none">The upload has been canceled by the user or the browser dropped the connection</div>
    <div class="warnsize" style="display:none">Your file is very big. We can't accept it. Please select more small file</div>
</div> 
