<?php
// Senast uppdaterad: 2026-09-20 18:46
use PHPUnit\Framework\TestCase;
final class DatabaseIntegrationTest extends TestCase{
 private PDO $pdo;
 protected function setUp():void{$dsn=getenv('THAI_NEWS_TEST_DSN')?:'';if($dsn==='')$this->markTestSkipped('THAI_NEWS_TEST_DSN not configured');$this->pdo=new PDO($dsn,getenv('THAI_NEWS_TEST_USER')?:'',getenv('THAI_NEWS_TEST_PASS')?:'',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);}
 public function testSchemaAndSeedAreRepeatable():void{$root=dirname(__DIR__,2);foreach([1,2] as $_){$this->execSqlFile($root.'/sql/schema.sql');$this->execSqlFile($root.'/sql/seed.sql');}$this->assertSame('11',(string)$this->pdo->query('SELECT COUNT(*) FROM news_sources')->fetchColumn());$this->assertSame('11',(string)$this->pdo->query('SELECT COUNT(*) FROM news_sources WHERE enabled=1')->fetchColumn());$this->assertSame('1',(string)$this->pdo->query("SELECT COUNT(*) FROM news_sources WHERE slug='bangkok-post'")->fetchColumn());}
 private function execSqlFile(string $file):void{$sql=file_get_contents($file);foreach(preg_split('/;\s*(?:\R|$)/',$sql) as $statement){$statement=trim($statement);if($statement!==''&&!str_starts_with($statement,'--'))$this->pdo->exec($statement);}}
}
