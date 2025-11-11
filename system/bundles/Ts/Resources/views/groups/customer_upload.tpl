<div id="customer_import_container" style="display:none">
    <h4 class="flex flex-row justify-between">
        {$gui2->t('Kunden importieren')}
        <a href="/storage/download/templates/school_group_import.xlsx" onclick="bPageLoaderDisabled = true" class="text-xs">
            <i class="fa fa-file-excel-o" aria-hidden="true"></i> {$gui2->t('Excel-Vorlage für die Importdatei')}
        </a>
    </h4>
    <div class="flex flex-row items-center justify-between text-xs py-2 gap-4">
        <input type="file" id="customer_import_file" class="grow text-xs p-1 rounded items-center relative border-none ring-1 bg-white text-gray-500 ring-gray-100/75 hover:text-gray-600 hover:ring-gray-200 dark:bg-gray-600 dark:text-gray-200 dark:ring-gray-500 dark:hover:bg-gray-500 dark:hover:text-gray-100">
        <div class="flex-none">
            <input id="replace_students" type="checkbox">
            <label for="replace_students">{$gui2->t('Vorhandene Schüler ersetzen')}</label>
        </div>
    </div>
</div>