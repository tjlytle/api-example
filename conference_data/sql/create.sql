------------- SQLite3 Dump File -------------

-- ------------------------------------------
-- Dump of "talks"
-- ------------------------------------------

CREATE TABLE "talks"(
	"id" Text NOT NULL PRIMARY KEY,
	"title" Text NOT NULL,
	"description" Text NOT NULL,
	"keywords" Text NOT NULL,
	"date" DateTime NOT NULL,
	"room" Text NOT NULL,
	"type" Text NOT NULL,
	"level" Text NOT NULL,
	"speaker_id" Text NOT NULL,
CONSTRAINT "unique_id" UNIQUE ( "id" ) );


-- ------------------------------------------
-- Dump of "speakers"
-- ------------------------------------------

CREATE TABLE "speakers"(
	"id" Text NOT NULL PRIMARY KEY,
	"name" Text NOT NULL,
	"company" Text,
	"bio" Text,
	"twitter" Text,
	"facebook" Text,
	"linkedin" Text,
CONSTRAINT "unique_id" UNIQUE ( "id" ) );


