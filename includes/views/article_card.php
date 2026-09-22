<?php
// Senast uppdaterad: 2026-09-21 08:58 | Minimal text-only news item
/** @var array<string,mixed> $article */
$fullTitle = (string)($article['display_title'] ?? $article['title']);
$visibleTitle = mb_strlen($fullTitle, 'UTF-8') > 34
    ? mb_substr($fullTitle, 0, 33, 'UTF-8') . '…'
    : $fullTitle;
$fullExcerpt = (string)$article['excerpt'];
$excerptIsTruncated = mb_strlen($fullExcerpt, 'UTF-8') > 95;
$visibleExcerpt = $excerptIsTruncated
    ? mb_substr($fullExcerpt, 0, 94, 'UTF-8') . '…'
    : $fullExcerpt;
?>
<article class="news-item">
    <h3><a href="<?= e((string)$article['canonical_url']) ?>" target="_blank" rel="noopener noreferrer external" title="<?= e($fullTitle) ?>" aria-label="<?= e($fullTitle) ?>"><?= e($visibleTitle) ?></a></h3>
    <?php if ($fullExcerpt !== ''): ?>
        <p><span class="excerpt-text"><?= e($visibleExcerpt) ?></span></p>
    <?php endif; ?>
</article>
