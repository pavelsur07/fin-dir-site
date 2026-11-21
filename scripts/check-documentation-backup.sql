INSERT INTO documentation_section (title, slug, description, position, updated_at)
VALUES ('Smoke', 'backup-check', '', 0, CURRENT_TIMESTAMP);

INSERT INTO documentation_page (section_id, title, slug, description, body, search_text, position, status, created_at, updated_at)
SELECT id, 'Screenshot', 'backup-image', 'Backup verification',
       '![Form](doc-image:9bcb5e71-70aa-4ce5-8c88-1bf827128b4a)', 'Form', 0, 'draft', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP
FROM documentation_section WHERE slug = 'backup-check';

INSERT INTO documentation_image (id, page_id, sha256, width, height, contents, byte_size, referenced, created_at)
SELECT '9bcb5e71-70aa-4ce5-8c88-1bf827128b4a', id,
       '6935feac68e40560f78d63a3ec3524d939f149c17bee9af54e392d6cd558699e', 8, 8, image, octet_length(image), true, CURRENT_TIMESTAMP
FROM documentation_page
CROSS JOIN (SELECT decode('524946461a000000574542505650384c0e0000002f07c00100071011fd0f4444ff03', 'hex') AS image) AS fixture
WHERE slug = 'backup-image';
