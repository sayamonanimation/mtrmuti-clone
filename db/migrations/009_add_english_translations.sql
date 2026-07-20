-- Run this only if you already imported db/schema.sql (or an earlier migration) before this change.
-- (A fresh import of schema.sql already includes these.)
SET NAMES utf8mb4;

ALTER TABLE pages
    ADD COLUMN title_en VARCHAR(255) DEFAULT NULL AFTER content,
    ADD COLUMN subtitle_en VARCHAR(255) DEFAULT NULL AFTER title_en,
    ADD COLUMN content_en LONGTEXT AFTER subtitle_en;

ALTER TABLE sliders
    ADD COLUMN caption_en VARCHAR(255) DEFAULT NULL AFTER subtitle,
    ADD COLUMN subtitle_en VARCHAR(255) DEFAULT NULL AFTER caption_en;

ALTER TABLE posts
    ADD COLUMN title_en VARCHAR(255) DEFAULT NULL AFTER link_url,
    ADD COLUMN excerpt_en TEXT AFTER title_en;

ALTER TABLE fields_of_study
    ADD COLUMN name_en VARCHAR(150) DEFAULT NULL AFTER icon_path,
    ADD COLUMN description_en TEXT AFTER name_en;

ALTER TABLE teachers
    ADD COLUMN position_en VARCHAR(150) DEFAULT NULL AFTER awards,
    ADD COLUMN bio_en TEXT AFTER position_en,
    ADD COLUMN education_en TEXT AFTER bio_en,
    ADD COLUMN subjects_taught_en TEXT AFTER education_en,
    ADD COLUMN research_works_en TEXT AFTER subjects_taught_en,
    ADD COLUMN creative_works_en TEXT AFTER research_works_en,
    ADD COLUMN innovation_works_en TEXT AFTER creative_works_en,
    ADD COLUMN competition_works_en TEXT AFTER innovation_works_en,
    ADD COLUMN intellectual_property_en TEXT AFTER competition_works_en,
    ADD COLUMN copyrights_patents_en TEXT AFTER intellectual_property_en,
    ADD COLUMN awards_en TEXT AFTER copyrights_patents_en;
