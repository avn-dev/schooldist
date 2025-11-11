<?php

/**
 * @TODO Wird nur noch für diesen merkwürdigen Query benötigt
 */
class Ext_Thebing_Accounting_Accommodation_Payment_List {

	public static function getNightsQueryPart($sAccommodationId="`u2_cdb4`.`id`", $sInquiryAccommodationId="`u2_kia`.`id`", $sWeek="`week_table`.`week`") {

		$sPart = "				
											SELECT
												SUM(
													DATEDIFF(
														IF(
															(
																(
																	".$sWeek."  +
																	INTERVAL 1 WEEK
																)
															) < DATE(`u2_sub_kra`.`until`),
															(
																(
																	".$sWeek."  +
																	INTERVAL 1 WEEK
																)
															),
															DATE(`u2_sub_kra`.`until`)
														),
														IF(
															".$sWeek."  > DATE(`u2_sub_kra`.`from`),
															".$sWeek." ,
															DATE(`u2_sub_kra`.`from`)
														)
													) -
													IF(
														/* Wenn der Startag am ende der ersten Woche liegt */
														".$sWeek." < `u2_sub_kra`.`from`,
														0 ,
														IF(
															DAYNAME(
																IF(
																	(
																		(
																			".$sWeek."  +
																			INTERVAL 1 WEEK
																		)
																	) < DATE(`u2_sub_kra`.`until`),
																	(
																		(
																			".$sWeek."  +
																			INTERVAL 1 WEEK
																		) -
																		INTERVAL 1 SECOND
																	),
																	DATE(`u2_sub_kra`.`until`)
																)
															) =
															DAYNAME(
																IF(
																	".$sWeek."  > DATE(`u2_sub_kra`.`from`),
																	".$sWeek." ,
																	DATE(`u2_sub_kra`.`from`)
																)
															),
															1,
															0
														)
													)
												)
											FROM
												`customer_db_4` `u2_sub_cdb4` INNER JOIN
												`kolumbus_rooms` `u2_sub_kr` ON
													`u2_sub_cdb4`.`id` = `u2_sub_kr`.`accommodation_id` INNER JOIN
												`kolumbus_accommodations_allocations` `u2_sub_kra` ON
													`u2_sub_kra`.`room_id` = `u2_sub_kr`.`id`
											WHERE
												`u2_sub_cdb4`.`id` = ".$sAccommodationId." AND
												`u2_sub_kra`.`active` = 1 AND
												`u2_sub_kra`.`room_id` > 0 AND
												`u2_sub_kra`.`inquiry_accommodation_id` = ".$sInquiryAccommodationId."  AND
												".$sWeek." <=
												DATE(`u2_sub_kra`.`until`) AND
												(
													".$sWeek." +
													INTERVAL 1 WEEK -
													INTERVAL 1 SECOND
												) >=
												DATE(`u2_sub_kra`.`from`)
												";

		return $sPart;

	}

}