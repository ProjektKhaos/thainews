<?php
// Senast uppdaterad: 2026-09-21 | Thai News source card grid

declare(strict_types=1);
require __DIR__.'/includes/bootstrap.php';

use ThaiNews\Metadata; use ThaiNews\News\NewsRepository;
$meta=Metadata::build(config(),t('meta.title'),t('meta.description'));
require __DIR__.'/includes/views/header.php';
$prefs=$GLOBALS['tn_prefs_repo']->get($GLOBALS['tn_visitor_hash'],translator()->language()); $repo=new NewsRepository(db()); $any=false;
?>
<div class="source-grid">
<?php foreach($prefs['sources'] as $source): if(!(bool)$source['visible'])continue; $articles=$repo->latestForSource((int)$source['id'],translator()->language(),5); if(!$articles)continue; $any=true; ?>
<section class="source-section"><div class="source-heading"><h2><?= e((string)$source['name']) ?></h2></div><div class="news-list"><?php foreach($articles as $article)require __DIR__.'/includes/views/article_card.php';?></div></section>
<?php endforeach; ?>
</div>
<?php if(!$any):?><section class="empty-state"><p><?= e(t('home.no_news')) ?></p></section><?php endif; ?>
<?php require __DIR__.'/includes/views/footer.php';
