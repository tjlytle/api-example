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
WHERE `id`=:id;