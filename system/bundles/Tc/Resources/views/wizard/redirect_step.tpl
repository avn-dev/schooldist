{extends file="system/bundles/Tc/Resources/views/wizard/step_layout.tpl"}

{block name="step-content"}
    <p>
        {$wizard->translate('Sie werden jetzt auf eine andere Seite weitergeleitet. Pflegen Sie dort bitte alle ihre Daten und kehren Sie zu dieser Seite zurück.')}
    </p>

    <div style="text-align: center; margin: 20px 0;">
        <button type="button" id="redirectBtn" class="btn btn-primary" onclick="redirect()">
            <i class="fa fa-circle-notch fa-spin"></i>
            {assign var=text value=$wizard->translate('Sie werden in %s weitergeleitet')}
            {$text|replace:'%s':"<span>`$time`</span>"}
        </button>
    </div>

{/block}

{block name="step-buttons"}
    <button type="button" class="btn btn-primary" onclick="goNext()">{$wizard->translate('Weiter')}</button>
{/block}

{block name="footer-additional-js"}

    <script type="text/javascript" src="/assets/core/js/vue.js?v={\System::d('version')}"></script>
    <script type="text/javascript" src="/admin/assets/interface/js/admin-iframe.js?v={\System::d('version')}"></script>

    <script>

        var oWindow = null;
        var oTab = null;
        var redirectTimer = null;

        $(function() {
            startCounter();
        });

        function startCounter() {

            var btn = $('#redirectBtn');
            var max = {$time};
            var counter = 1;

            redirectTimer = setInterval(function() {
                if(counter > max){
                    redirect();
                    return;
                }
                $(btn).find('span').html(max - counter);
                counter += 1;
            }, 1000);

        }

        function stopCounter() {
            var btn = $('#redirectBtn');
            $(btn).html('{$wizard->translate('Seite erneut öffnen')}');
            if (redirectTimer) {
                clearInterval(redirectTimer);
            }
        }

        function redirect() {

            stopCounter();

            if (window.__ADMIN__) {
                window.__ADMIN__.instance.action({$routerAction|json_encode})
            } else {
                console.warn('No admin interface')
            }

            /*if (
                window.parent &&
                typeof window.parent.loadContentByUrl !== "undefined"
            ) {
                window.parent.loadContentByUrl('{$tabKey}', '{$redirectName|escape:'javascript'}', redirectUrl);
            } else {
                oWindow = window.open(redirectUrl);
            }*/

        }

        function goNext() {

            if (oWindow) {
                oWindow.close();
            }

            if (
                window.parent &&
                typeof window.parent.closeContentByKey !== "undefined"
            ) {
                window.parent.closeContentByKey('{$tabKey}');
            }

            $('#stepForm').submit();
        }

    </script>

{/block}