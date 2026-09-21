<?php
// Senast uppdaterad: 2026-09-21 08:58 | Fetch all enabled news sources

declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/includes/bootstrap.php';
use ThaiNews\HttpClient;
use ThaiNews\News\{AdapterRegistry,BangkokPostAdapter,GenericRssAdapter,FetchService,NewsRepository,RssAtomParser};

$options=getopt('',['source:','dry-run']);
$slug=$options['source']??null;
$dry=array_key_exists('dry-run',$options);
$lockPath=(string)config()->get('storage.locks',dirname(__DIR__).'/storage/locks').'/fetch_news.lock';
$lock=fopen($lockPath,'c');
if(!$lock||!flock($lock,LOCK_EX|LOCK_NB)){logger()->log('fetch','info','fetch_skipped_locked');exit(0);}

try {
    $http = new HttpClient(config());
    $parser = new RssAtomParser();
    $registry = new AdapterRegistry();
    $registry->register('bangkok_post', new BangkokPostAdapter($http, $parser));
    $registry->register('rss', new GenericRssAdapter($http, $parser));

    $service = new FetchService(new NewsRepository(db()), $registry, logger());
    $r = $service->run(is_string($slug) ? $slug : null, $dry);
    echo json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
    exit($r['sources'] > 0 && $r['failed'] === $r['sources'] ? 1 : 0);
} finally {
    flock($lock,LOCK_UN);
    fclose($lock);
}
