SELECT
  "date",
  "description",
  "id",
  "keywords",
  "level",
  "room",
  "speaker_id",
  "title",
  "type"
FROM "talks"
WHERE `date` < :before AND
      `date` > :after
ORDER BY `date` ASC
LIMIT :size
OFFSET :pos;