<?php
// Render operator-managed plain text with semantic headings and safe article links.
// The complete source wording also remains in the stored acceptance snapshot.
$paragraphs = preg_split('/\R{2,}/u', $currentTerms) ?: [];
$articles = [];
foreach ($paragraphs as $paragraph) {
    if (preg_match('/^ARTICLE\s+(\d{1,2})\s+(.+)$/su', trim($paragraph), $match)) {
        $articles[(int) $match[1]] = preg_replace('/\s+/u', ' ', $match[2]);
    }
}
?>
<?php if (str_contains($currentTerms, 'LEGAL REVIEW NOTICE:')): ?>
    <p class="alert alert-info">The supplied document is marked as a draft for legal review.</p>
<?php endif ?>
<?php if ($articles !== []): ?>
    <nav class="terms-article-nav mb-4" aria-label="Terms articles">
        <h2 class="h4">Browse the articles</h2>
        <ol>
            <?php foreach ($articles as $number => $heading): ?>
                <li><a href="#terms-article-<?= (int) $number ?>"><?= esc($heading) ?></a></li>
            <?php endforeach ?>
        </ol>
    </nav>
<?php endif ?>
<div class="current-terms-content">
<?php foreach ($paragraphs as $paragraph): $paragraph = trim($paragraph); ?>
    <?php if ($paragraph === ''): continue; endif ?>
    <?php if (preg_match('/^ARTICLE\s+(\d{1,2})\s+(.+)$/su', $paragraph, $match)): ?>
        <h2 class="terms-article-heading" id="terms-article-<?= (int) $match[1] ?>"><?= esc($paragraph) ?></h2>
    <?php elseif (preg_match('/^\d{1,2}\.\d{1,2}\s+[^\r\n]+$/u', $paragraph)): ?>
        <h3 class="h5"><?= esc($paragraph) ?></h3>
    <?php else: ?>
        <p class="terms-text"><?= esc($paragraph) ?></p>
    <?php endif ?>
<?php endforeach ?>
</div>
