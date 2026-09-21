-- Thai News source seed
-- Senast uppdaterad: 2026-09-21 08:58
-- Idempotent: safe to run again when new sources are added.

INSERT INTO news_sources(slug,name,website_url,feed_url,adapter_type,source_language,enabled,default_order,adapter_config,created_at,updated_at)
VALUES
('bangkok-post','Bangkok Post','https://www.bangkokpost.com/','https://www.bangkokpost.com/rss/data/topstories.xml','bangkok_post','en',1,1,JSON_OBJECT(),UTC_TIMESTAMP(),UTC_TIMESTAMP()),

('thai-pbs-world','Thai PBS World','https://www.thaipbsworld.com/','https://news.google.com/rss/search?q=site%3Athaipbsworld.com&hl=en-US&gl=TH&ceid=TH%3Aen','rss','en',1,2,
 JSON_OBJECT('feed_note','Google News RSS is used as a compatibility feed for Thai PBS World.'),UTC_TIMESTAMP(),UTC_TIMESTAMP()),

('nation-thailand','The Nation Thailand','https://www.nationthailand.com/','https://news.google.com/rss/search?q=site%3Anationthailand.com&hl=en-US&gl=TH&ceid=TH%3Aen','rss','en',1,3,
 JSON_OBJECT('feed_note','Google News RSS is used because /rss currently serves a web page rather than a parseable RSS document.'),UTC_TIMESTAMP(),UTC_TIMESTAMP()),

('khaosod-english','Khaosod English','https://www.khaosodenglish.com/','https://www.khaosodenglish.com/feed/','rss','en',1,4,
 JSON_OBJECT('fallback_feed_urls',JSON_ARRAY('https://news.google.com/rss/search?q=site%3Akhaosodenglish.com&hl=en-US&gl=TH&ceid=TH%3Aen')),UTC_TIMESTAMP(),UTC_TIMESTAMP()),

('the-thaiger','The Thaiger','https://thethaiger.com/','https://thethaiger.com/feed','rss','en',1,5,
 JSON_OBJECT('fallback_feed_urls',JSON_ARRAY('https://thethaiger.com/news/northern-thailand/feed','https://news.google.com/rss/search?q=site%3Athethaiger.com&hl=en-US&gl=TH&ceid=TH%3Aen')),UTC_TIMESTAMP(),UTC_TIMESTAMP()),

('chiang-mai-citynews','Chiang Mai CityNews','https://www.chiangmaicitylife.com/citynews/','https://www.chiangmaicitylife.com/citynews/feed/','rss','en',1,6,
 JSON_OBJECT('fallback_feed_urls',JSON_ARRAY('https://news.google.com/rss/search?q=site%3Achiangmaicitylife.com%2Fcitynews&hl=en-US&gl=TH&ceid=TH%3Aen')),UTC_TIMESTAMP(),UTC_TIMESTAMP()),

('chiang-rai-times','Chiang Rai Times','https://www.chiangraitimes.com/','https://www.chiangraitimes.com/feed/','rss','en',1,7,
 JSON_OBJECT('fallback_feed_urls',JSON_ARRAY('https://news.google.com/rss/search?q=site%3Achiangraitimes.com&hl=en-US&gl=TH&ceid=TH%3Aen')),UTC_TIMESTAMP(),UTC_TIMESTAMP()),

('pattaya-mail','Pattaya Mail','https://www.pattayamail.com/','https://www.pattayamail.com/feed','rss','en',1,8,
 JSON_OBJECT('fallback_feed_urls',JSON_ARRAY('https://news.google.com/rss/search?q=site%3Apattayamail.com&hl=en-US&gl=TH&ceid=TH%3Aen')),UTC_TIMESTAMP(),UTC_TIMESTAMP()),

('tat-newsroom','TAT Newsroom','https://www.tatnews.org/','https://www.tatnews.org/feed/','rss','en',1,9,
 JSON_OBJECT(),UTC_TIMESTAMP(),UTC_TIMESTAMP()),

('phuket-news','The Phuket News','https://www.thephuketnews.com/','https://news.google.com/rss/search?q=site%3Athephuketnews.com&hl=en-US&gl=TH&ceid=TH%3Aen','rss','en',1,10,
 JSON_OBJECT('feed_note','Google News RSS compatibility feed.'),UTC_TIMESTAMP(),UTC_TIMESTAMP()),

('asean-now','ASEAN NOW','https://aseannow.com/','https://news.google.com/rss/search?q=site%3Aaseannow.com&hl=en-US&gl=TH&ceid=TH%3Aen','rss','en',1,11,
 JSON_OBJECT('feed_note','Google News RSS compatibility feed.'),UTC_TIMESTAMP(),UTC_TIMESTAMP())

ON DUPLICATE KEY UPDATE
 name=VALUES(name),
 website_url=VALUES(website_url),
 feed_url=VALUES(feed_url),
 adapter_type=VALUES(adapter_type),
 source_language=VALUES(source_language),
 enabled=VALUES(enabled),
 default_order=VALUES(default_order),
 adapter_config=VALUES(adapter_config),
 updated_at=UTC_TIMESTAMP();
