<div id="app" props='{$props|json_encode|escape}'></div>

<script src="/assets/core/js/vue.js?v={\System::d('version')}"></script>
<script src="/assets/open-banking/js/finAPI/external_app.js?v={\System::d('version')}"></script>

<div class="box-footer">
    <a href="{route name="TcExternalApps.list"}" class="btn btn-default">{'Zurück'|L10N}</a>
</div>