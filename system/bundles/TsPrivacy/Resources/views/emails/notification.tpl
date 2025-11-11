{'Es wurden für den Datenschutz Bereinigungseinstellungen vorgenommen. Nachfolgende Datensätze werden in einer Woche automatisch bereinigt.

Bitte passen Sie die Datenschutz-Einstellungen unter Admin - Administration - Generelle Einstellungen an, wenn dies nicht gewünscht ist.'|L10N:$sTranslationPath}

{foreach $aEntitiesLabels as $aEntity}
{$aEntity['label']}: {$aEntity['count']}
{/foreach}


{foreach $aEntitiesLabels as $sEntity => $aEntity}
{'%s, welche %s werden:'|L10N:$sTranslationPath|sprintf:$aEntity['label']:$aEntity['action_label']}

{foreach $aEntitiesFormatted[$sEntity] as $sLabel}
{$sLabel}
{/foreach}



{/foreach}
