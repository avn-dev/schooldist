DELETE
`tc_gf`,
`tc_gfb`,
`tc_gfbe`,
`tc_gfbeb`,
`tc_gfbei`,
`tc_gfbtu`
FROM
`tc_gui2_filtersets` `tc_gf` LEFT JOIN
`tc_gui2_filtersets_bars` `tc_gfb` ON tc_gf.id = tc_gfb.set_id LEFT JOIN
`tc_gui2_filtersets_bars_elements`  `tc_gfbe` ON tc_gfb.id = tc_gfbe.bar_id LEFT JOIN
`tc_gui2_filtersets_bars_elements_basedon` `tc_gfbeb` ON tc_gfbe.id = tc_gfbeb.element_id LEFT JOIN
`tc_gui2_filtersets_bars_elements_i18n` `tc_gfbei` ON tc_gfbe.id = tc_gfbei.element_id LEFT JOIN
`tc_gui2_filtersets_bars_to_usergroups` `tc_gfbtu` ON tc_gfb.id = tc_gfbtu.bar_id

WHERE application IN ('ts_students_visum',
'ts_students_simple',
'ts_students_departure',
'ts_students_arrival',
'ts_students_checkedin',
'ts_document_release',
'ts_document',
'ts_students_payments',
'ts_inquiry');
