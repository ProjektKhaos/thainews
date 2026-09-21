<?php
// Senast uppdaterad: 2026-09-20 19:40 | Core persistence integration coverage

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use ThaiNews\Config;
use ThaiNews\Digest\DigestService;
use ThaiNews\Digest\Mailer;
use ThaiNews\Logger;
use ThaiNews\News\ArticleCandidate;
use ThaiNews\News\AdapterRegistry;
use ThaiNews\News\FetchResult;
use ThaiNews\News\FetchService;
use ThaiNews\News\NewsRepository;
use ThaiNews\News\SourceAdapterInterface;
use ThaiNews\News\SourceDefinition;
use ThaiNews\Preferences\PreferencesRepository;
use ThaiNews\Security\RateLimiter;
use ThaiNews\Security\Tokens;
use ThaiNews\Subscription\SubscriberRepository;
use ThaiNews\Subscription\SubscriptionService;
use ThaiNews\Translation\TranslationProviderInterface;
use ThaiNews\Translation\TranslationRepository;
use ThaiNews\Translation\TranslationService;

final class ApplicationIntegrationTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $dsn=getenv('THAI_NEWS_TEST_DSN')?:'';
        if($dsn==='')$this->markTestSkipped('THAI_NEWS_TEST_DSN not configured');
        $this->pdo=new PDO($dsn,getenv('THAI_NEWS_TEST_USER')?:'',getenv('THAI_NEWS_TEST_PASS')?:'',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
        $this->pdo->exec("SET time_zone = '+00:00'");
        $this->pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        if(isset($this->pdo)&&$this->pdo->inTransaction())$this->pdo->rollBack();
    }

    public function testArticleUpsertDeduplicates(): void
    {
        $repo=new NewsRepository($this->pdo);$source=$repo->enabledSources('bangkok-post')[0];
        $candidate=new ArticleCandidate('integration-dedupe','Integration title','https://example.com/integration-dedupe','Excerpt',null,new DateTimeImmutable('2026-09-20T10:00:00Z'));
        $first=$repo->upsertArticle($source,$candidate);$second=$repo->upsertArticle($source,$candidate);
        self::assertSame(1,$first['inserted']);self::assertSame(1,$second['updated']);
        self::assertSame('1',(string)$this->pdo->query("SELECT COUNT(*) FROM articles WHERE external_id='integration-dedupe'")->fetchColumn());
    }

    public function testBrokenArticleAndSourceDoNotStopOtherFetchWork(): void
    {
        $this->pdo->exec("UPDATE news_sources SET enabled=0 WHERE slug <> 'bangkok-post'");
        $this->pdo->exec("INSERT INTO news_sources(slug,name,website_url,feed_url,adapter_type,source_language,enabled,default_order,created_at,updated_at) VALUES('broken-source','Broken source','https://example.com','https://example.com/feed','always_fails','en',1,2,UTC_TIMESTAMP(),UTC_TIMESTAMP())");
        $registry=new AdapterRegistry();
        $registry->register('bangkok_post',new class implements SourceAdapterInterface {
            public function fetch(SourceDefinition $source):FetchResult{return new FetchResult([
                new ArticleCandidate('integration-good','Good fetched article','https://example.com/integration-good','',null,new DateTimeImmutable('2026-09-20T10:00:00Z')),
                new ArticleCandidate('integration-bad',str_repeat('x',2000),'https://example.com/integration-bad','',null,new DateTimeImmutable('2026-09-20T10:00:00Z')),
            ]);}
        });
        $registry->register('always_fails',new class implements SourceAdapterInterface {
            public function fetch(SourceDefinition $source):FetchResult{throw new RuntimeException('fixture failure');}
        });
        $result=(new FetchService(new NewsRepository($this->pdo),$registry,new Logger(sys_get_temp_dir())))->run();
        self::assertSame(2,$result['sources']);self::assertSame(1,$result['failed']);self::assertSame(1,$result['inserted']);self::assertSame(1,$result['rejected']);
        self::assertSame('partial',(string)$this->pdo->query('SELECT status FROM fetch_runs ORDER BY id DESC LIMIT 1')->fetchColumn());
    }

    public function testPreferencesPersistVisibilityOrderAndLanguage(): void
    {
        $this->pdo->exec("INSERT INTO news_sources(slug,name,website_url,feed_url,adapter_type,source_language,enabled,default_order,created_at,updated_at) VALUES('fixture-source','Fixture','https://example.com','https://example.com/feed','bangkok_post','en',1,2,UTC_TIMESTAMP(),UTC_TIMESTAMP())");
        $repo=new PreferencesRepository($this->pdo);$visitor=str_repeat('a',64);
        $repo->save($visitor,'sv',[
            ['slug'=>'fixture-source','position'=>1,'visible'=>true],
            ['slug'=>'bangkok-post','position'=>2,'visible'=>false],
        ]);
        $saved=$repo->get($visitor,'en');
        self::assertSame('sv',$saved['language']);self::assertSame('fixture-source',$saved['sources'][0]['slug']);self::assertFalse((bool)$saved['sources'][1]['visible']);
    }

    public function testTranslationsAreCachedAndInvalidatedBySourceTitle(): void
    {
        $repo=new NewsRepository($this->pdo);$source=$repo->enabledSources('bangkok-post')[0];
        $repo->upsertArticle($source,new ArticleCandidate('integration-translate','Original headline','https://example.com/integration-translate','',null,new DateTimeImmutable('2026-09-20T10:00:00Z')));
        $provider=new class implements TranslationProviderInterface {
            public int $calls=0;
            public function name():string{return'fake';}
            public function translate(array $texts,string $sourceLanguage,string $targetLanguage):array{$this->calls++;return array_map(static fn(string $text):string=>$text.' ['.$targetLanguage.']',$texts);}
        };
        $service=new TranslationService(new TranslationRepository($this->pdo),$provider,new Logger(sys_get_temp_dir()));
        $first=$service->run('sv',10);$second=$service->run('sv',10);
        self::assertSame(1,$first['translated']);self::assertSame(0,$second['selected']);self::assertSame(1,$provider->calls);
        $this->pdo->exec("UPDATE articles SET title='Changed headline' WHERE external_id='integration-translate'");
        $third=$service->run('sv',10);
        self::assertSame(1,$third['translated']);self::assertSame(2,$provider->calls);
        self::assertSame('Changed headline [sv]',(string)$this->pdo->query("SELECT translated_title FROM article_translations t JOIN articles a ON a.id=t.article_id WHERE a.external_id='integration-translate' AND t.language='sv'")->fetchColumn());
        $repo->upsertArticle($source,new ArticleCandidate('integration-translate-reuse','Changed headline','https://example.com/integration-translate-reuse','',null,new DateTimeImmutable('2026-09-20T10:01:00Z')));
        $fourth=$service->run('sv',10);
        self::assertSame(1,$fourth['translated']);self::assertSame(2,$provider->calls,'Identical source text should reuse translation memory without a provider call.');
        self::assertSame('1',(string)$this->pdo->query("SELECT COUNT(*) FROM translation_memory WHERE target_language='sv' AND source_text='Changed headline'")->fetchColumn());
    }

    public function testDigestDryRunDoesNotWriteOrSend(): void
    {
        $repo=new NewsRepository($this->pdo);$source=$repo->enabledSources('bangkok-post')[0];
        $repo->upsertArticle($source,new ArticleCandidate('integration-digest','Digest title','https://example.com/integration-digest','',null,new DateTimeImmutable('2026-09-20T10:00:00Z')));
        $this->pdo->exec("UPDATE articles SET first_seen_at='2026-09-20 10:00:00' WHERE external_id='integration-digest'");
        $this->pdo->exec("INSERT INTO subscribers(email,email_hash,language,status,confirmed_at,unsubscribe_token_version,created_at,updated_at) VALUES('digest@example.com',SHA2('digest@example.com',256),'en','active','2026-09-20 09:00:00',1,'2026-09-20 09:00:00','2026-09-20 09:00:00')");
        $config=new Config(['public_origin'=>'https://thainews.aberg.online','base_url'=>'','timezone'=>'Asia/Bangkok','app_secret'=>str_repeat('x',32),'smtp'=>['enabled'=>false],'digest'=>['max_per_source'=>10,'max_total'=>40]]);
        $service=new DigestService($this->pdo,new SubscriberRepository($this->pdo),new Mailer($config),$config,new Logger(sys_get_temp_dir()));
        $result=$service->run(new DateTimeImmutable('2026-09-20T18:00:00+07:00'),true,100);
        self::assertSame(1,$result['would_send']);self::assertSame('0',(string)$this->pdo->query('SELECT COUNT(*) FROM digest_log')->fetchColumn());
    }

    public function testDigestHasBrandedHtmlPlainTextAndUnsubscribeLink(): void
    {
        $repo=new NewsRepository($this->pdo);$source=$repo->enabledSources('bangkok-post')[0];
        $repo->upsertArticle($source,new ArticleCandidate('integration-digest-content','Digest content title','https://example.com/integration-digest-content','Digest excerpt',null,new DateTimeImmutable('2026-09-20T10:00:00Z')));
        $this->pdo->exec("UPDATE articles SET first_seen_at='2026-09-20 10:00:00' WHERE external_id='integration-digest-content'");
        $this->pdo->exec("INSERT INTO subscribers(email,email_hash,language,status,confirmed_at,unsubscribe_token_version,created_at,updated_at) VALUES('content@example.com',SHA2('content@example.com',256),'sv','active','2026-09-20 09:00:00',1,'2026-09-20 09:00:00','2026-09-20 09:00:00')");
        $config=new Config(['public_origin'=>'https://thainews.aberg.online','base_url'=>'','timezone'=>'Asia/Bangkok','app_secret'=>str_repeat('x',32),'smtp'=>['enabled'=>false],'digest'=>['max_per_source'=>10,'max_total'=>40]]);
        $mailer=new class($config) extends Mailer {
            public string $html='';public string $text='';
            public function sendDigest(string $to,string $subject,string $html,string $text):?string{$this->html=$html;$this->text=$text;return'test-message-id';}
        };
        $service=new DigestService($this->pdo,new SubscriberRepository($this->pdo),$mailer,$config,new Logger(sys_get_temp_dir()));
        $result=$service->run(new DateTimeImmutable('2026-09-20T18:00:00+07:00'),false,100);
        self::assertSame(1,$result['sent']);
        self::assertStringContainsString('<html>',$mailer->html);
        self::assertStringContainsString('img/thainews_logo1.png',$mailer->html);
        self::assertStringContainsString('unsubscribe.php?',$mailer->html);
        self::assertStringContainsString('https://example.com/integration-digest-content',$mailer->text);
        self::assertStringContainsString('unsubscribe.php?',$mailer->text);
        self::assertSame('sent',(string)$this->pdo->query('SELECT status FROM digest_log LIMIT 1')->fetchColumn());
    }

    public function testDoubleOptInDuplicateProtectionAndSignedUnsubscribe(): void
    {
        $config=new Config([
            'public_origin'=>'https://thainews.aberg.online','base_url'=>'','timezone'=>'Asia/Bangkok',
            'app_secret'=>str_repeat('x',32),'smtp'=>['enabled'=>false],
        ]);
        $mailer=new class($config) extends Mailer {
            /** @var list<string> */ public array $links=[];
            public function sendConfirmation(string $to,string $language,string $link):void{$this->links[]=$link;}
        };
        $repo=new SubscriberRepository($this->pdo);
        $service=new SubscriptionService($repo,new RateLimiter($this->pdo,str_repeat('r',32)),$mailer,$config);
        $service->request(' Opt-In@example.com ','sv','integration-client');
        $service->request('opt-in@example.com','sv','integration-client');

        self::assertCount(1,$mailer->links,'A pending address must not be mailed repeatedly inside the cooldown.');
        parse_str((string)parse_url($mailer->links[0],PHP_URL_QUERY),$query);
        $subscriber=$repo->findById((int)$query['sid']);
        self::assertNotNull($subscriber);
        self::assertSame('pending',$subscriber['status']);
        self::assertTrue($repo->confirm((int)$query['sid'],hash('sha256',(string)$query['token'])));
        self::assertSame('active',$repo->findById((int)$query['sid'])['status']);

        $version=(int)$repo->findById((int)$query['sid'])['unsubscribe_token_version'];
        $signature=Tokens::unsubscribeSignature((int)$query['sid'],$version,str_repeat('x',32));
        self::assertTrue(Tokens::verifyUnsubscribe((int)$query['sid'],$version,$signature,str_repeat('x',32)));
        self::assertTrue($repo->unsubscribe((int)$query['sid'],$version));
        self::assertSame('unsubscribed',$repo->findById((int)$query['sid'])['status']);
    }
}
