UPDATE
	kolumbus_accommodations_allocations kaa INNER JOIN
	ts_inquiries_journeys_accommodations ts_ija ON
		ts_ija.id = kaa.inquiry_accommodation_id AND
		ts_ija.active = 0
SET
	kaa.active = 0
WHERE
	kaa.active = 1 AND
	kaa.status = 0;
