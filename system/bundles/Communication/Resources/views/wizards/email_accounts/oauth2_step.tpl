{extends file="system/bundles/Tc/Resources/views/wizard/step_layout.tpl"}

{block name="step-content"}
    <p>
        {$wizard->translate('Sie werden jetzt auf eine andere Seite weitergeleitet. Sie müssen sich dort mit Ihren Zugangsdaten anmelden und den Zugriff erlauben.')}
    </p>

    <div style="text-align: center; margin: 20px 0;">
        <button type="button" id="redirectBtn" class="btn btn-primary" onclick="redirect()">
            {$wizard->translate('Seite öffnen')}
        </button>
    </div>

    <div id="access_success" class="alert alert-success alert-dismissible" style="display: none;">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
        <i class="icon fa fa-check"></i> {$wizard->translate('Verbindung wurde erfolgreich hergestellt.')}
    </div>

    <div id="access_error" class="alert alert-danger alert-dismissible" style="display: none;">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
        <i class="icon fa fa-times"></i> {$wizard->translate('Verbindung konnte nicht hergestellt werden.')}
    </div>

    <textarea name="access_data" id="access_token" style="display: none;"></textarea>

{/block}

{block name="step-buttons"}
    <button type="submit" id="btnNext" class="btn btn-primary" style="display: none;">{$wizard->translate('Weiter')}</button>
{/block}

{block name="footer-additional-js"}

    <script>

        var oWindow = null;

        function receiveMessage(event) {

            if (
                event.origin !== window.location.origin ||
                !event.data
            ) {
                return;
            }

            var success = event.data.success;

            if (success) {
                $('#access_token').html(JSON.stringify(event.data));
                $('#btnNext').show();
                $('#access_success').show();
                $('#access_error').hide();
            } else {
                $('#access_success').hide();
                $('#access_error').show();
            }

        }

        function redirect() {

            $('#access_success').hide();
            $('#access_error').hide();

            var redirectUrl = "{$redirectUrl}";

            // window features
            const strWindowFeatures =
                'toolbar=no, menubar=no, width=600, height=700, top=100, left=100';

            if (oWindow === null || oWindow.closed) {
                /* if the pointer to the window object in memory does not exist
                 or if such pointer exists but the window was closed */
                oWindow = window.open(redirectUrl, 'oauth2', strWindowFeatures);
            } else {
                /* else the window reference must exist and the window
                 is not closed; therefore, we can bring it back on top of any other
                 window with the focus() method. There would be no need to re-create
                 the window or to reload the referenced resource. */
                oWindow.focus();
            }

            window.addEventListener('message', receiveMessage, false);

        }

    </script>

{/block}