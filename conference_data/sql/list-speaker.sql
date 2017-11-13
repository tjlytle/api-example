SELECT
  "bio",
  "company",
  "facebook",
  "id",
  "linkedin",
  "name",
  "twitter"
FROM "speakers"
LIMIT :size
OFFSET :pos;