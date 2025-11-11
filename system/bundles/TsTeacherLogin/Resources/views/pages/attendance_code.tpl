{extends file="system/bundles/TsTeacherLogin/Resources/views/layout/master.tpl"}

{block name="title"}{'Attendance'|L10N}{/block}

{block name="content"}

    <div class="content-header">
        <h1>{'Attendance'|L10N}</h1>
    </div>
    <div class="content">
        <div class="box">
            <div class="box-body">
                <div class="alert alert-info">
                    <p>{'Please scan the code below.'|L10N}</p>
                </div>
                <!-- SVG QR-Code -->
                <div id="qr-code">{$sCode}</div>
            </div>
            <!-- /.box-body -->
        </div>
    </div>

{/block}

{block name="footer_js"}
    <script>
/*var conn = new WebSocket('wss://school.box/ws/qr-code');
conn.onopen = function(e) {
console.log("Connection established!");
};

conn.onmessage = function(e) {
console.log(e.data);
};*/
    </script>
{/block}
