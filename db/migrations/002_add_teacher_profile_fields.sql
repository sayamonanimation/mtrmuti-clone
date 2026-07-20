-- Run this only if you already imported db/schema.sql before this change.
-- (A fresh import of schema.sql already includes these columns.)
SET NAMES utf8mb4;

ALTER TABLE teachers
    ADD COLUMN education TEXT AFTER bio,
    ADD COLUMN subjects_taught TEXT AFTER education,
    ADD COLUMN research_works TEXT AFTER subjects_taught,
    ADD COLUMN creative_works TEXT AFTER research_works,
    ADD COLUMN intellectual_property TEXT AFTER creative_works,
    ADD COLUMN copyrights_patents TEXT AFTER intellectual_property,
    ADD COLUMN awards TEXT AFTER copyrights_patents,
    ADD COLUMN classroom_online_url VARCHAR(255) AFTER awards;
