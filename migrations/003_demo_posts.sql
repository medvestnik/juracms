-- Demo blog content: lorem ipsum posts with placeholder images
INSERT IGNORE INTO jura_post_categories (title, slug, description)
VALUES ('Новини', 'news', '');

INSERT IGNORE INTO jura_posts (author_id, title, slug, excerpt, content, status, meta_title, meta_description, featured_image, sort_order, published_at, created_at, updated_at)
SELECT u.id,
  'Lorem Ipsum Dolor Sit Amet',
  'lorem-ipsum-dolor-sit-amet',
  'Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
  '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p><p>Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>',
  'published', 'Lorem Ipsum Dolor Sit Amet', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
  'demo-placeholder-01.svg', 1, NOW() - INTERVAL 4 DAY, NOW(), NOW()
FROM jura_users u ORDER BY u.id LIMIT 1;

INSERT IGNORE INTO jura_posts (author_id, title, slug, excerpt, content, status, meta_title, meta_description, featured_image, sort_order, published_at, created_at, updated_at)
SELECT u.id,
  'Consectetur Adipiscing Elit',
  'consectetur-adipiscing-elit',
  'Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium.',
  '<p>Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo.</p><p>Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt.</p>',
  'published', 'Consectetur Adipiscing Elit', 'Sed ut perspiciatis unde omnis iste natus error sit voluptatem.',
  'demo-placeholder-02.svg', 2, NOW() - INTERVAL 3 DAY, NOW(), NOW()
FROM jura_users u ORDER BY u.id LIMIT 1;

INSERT IGNORE INTO jura_posts (author_id, title, slug, excerpt, content, status, meta_title, meta_description, featured_image, sort_order, published_at, created_at, updated_at)
SELECT u.id,
  'Sed Do Eiusmod Tempor',
  'sed-do-eiusmod-tempor',
  'Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit.',
  '<p>Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet, consectetur, adipisci velit, sed quia non numquam eius modi tempora incidunt ut labore et dolore magnam aliquam quaerat voluptatem.</p><p>Ut enim ad minima veniam, quis nostrum exercitationem ullam corporis suscipit laboriosam, nisi ut aliquid ex ea commodi consequatur.</p>',
  'published', 'Sed Do Eiusmod Tempor', 'Neque porro quisquam est, qui dolorem ipsum quia dolor sit amet.',
  'demo-placeholder-03.svg', 3, NOW() - INTERVAL 2 DAY, NOW(), NOW()
FROM jura_users u ORDER BY u.id LIMIT 1;

INSERT IGNORE INTO jura_posts (author_id, title, slug, excerpt, content, status, meta_title, meta_description, featured_image, sort_order, published_at, created_at, updated_at)
SELECT u.id,
  'Ut Enim Ad Minim Veniam',
  'ut-enim-ad-minim-veniam',
  'At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum.',
  '<p>At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident.</p><p>Similique sunt in culpa qui officia deserunt mollitia animi, id est laborum et dolorum fuga. Et harum quidem rerum facilis est et expedita distinctio.</p>',
  'published', 'Ut Enim Ad Minim Veniam', 'At vero eos et accusamus et iusto odio dignissimos ducimus.',
  'demo-placeholder-04.svg', 4, NOW() - INTERVAL 1 DAY, NOW(), NOW()
FROM jura_users u ORDER BY u.id LIMIT 1;

INSERT IGNORE INTO jura_posts (author_id, title, slug, excerpt, content, status, meta_title, meta_description, featured_image, sort_order, published_at, created_at, updated_at)
SELECT u.id,
  'Duis Aute Irure Dolor',
  'duis-aute-irure-dolor',
  'Nam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit quo minus id quod maxime placeat.',
  '<p>Nam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit quo minus id quod maxime placeat facere possimus, omnis voluptas assumenda est, omnis dolor repellendus.</p><p>Temporibus autem quibusdam et aut officiis debitis aut rerum necessitatibus saepe eveniet ut et voluptates repudiandae sint et molestiae non recusandae.</p>',
  'published', 'Duis Aute Irure Dolor', 'Nam libero tempore, cum soluta nobis est eligendi optio cumque.',
  'demo-placeholder-05.svg', 5, NOW(), NOW(), NOW()
FROM jura_users u ORDER BY u.id LIMIT 1;

-- Link demo posts to the "news" category
INSERT IGNORE INTO jura_post_category_relations (post_id, category_id)
SELECT p.id, c.id FROM jura_posts p, jura_post_categories c
WHERE c.slug = 'news' AND p.slug IN (
  'lorem-ipsum-dolor-sit-amet', 'consectetur-adipiscing-elit', 'sed-do-eiusmod-tempor',
  'ut-enim-ad-minim-veniam', 'duis-aute-irure-dolor'
);

-- Public routes for the demo posts
INSERT IGNORE INTO jura_routes (path, entity_type, entity_id, status)
SELECT CONCAT('/blog/', p.slug), 'post', p.id, 'active'
FROM jura_posts p
WHERE p.slug IN (
  'lorem-ipsum-dolor-sit-amet', 'consectetur-adipiscing-elit', 'sed-do-eiusmod-tempor',
  'ut-enim-ad-minim-veniam', 'duis-aute-irure-dolor'
);
