<?php $e = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); ?>
<section class="page-shell">
    <div class="page-heading"><div><p class="eyebrow">Insights</p><h1>Report center</h1><p>Generate tenant-isolated, server-side PDF reports for audits and operations.</p></div></div>
    <div class="metric-grid">
        <?php foreach (['employees' => 'Active employees', 'parts' => 'Tracked parts', 'computers' => 'Computers', 'accessories' => 'Accessories'] as $key => $label): ?>
            <article class="metric-card"><span><?= $e($label) ?></span><strong><?= number_format((int) ($summary[$key] ?? 0)) ?></strong></article>
        <?php endforeach; ?>
    </div>
    <div class="report-grid">
        <?php foreach ($templates as $template): ?>
            <article class="report-card"><div class="report-icon" aria-hidden="true">PDF</div><div><h2><?= $e($template['name']) ?></h2><p><?= $e($template['description']) ?></p>
                <small><?= $e($template['paper_size']) ?> · <?= $e(ucfirst($template['orientation'])) ?></small></div>
                <a class="primary-button" href="/reports/pdf/<?= rawurlencode($template['code']) ?>">Generate PDF</a></article>
        <?php endforeach; ?>
    </div>
</section>
