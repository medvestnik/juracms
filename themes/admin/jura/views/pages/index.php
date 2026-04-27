<?php use App\Core\View; ?>
<h1>Pages</h1>
<div class="jura-actions">
    <a href="/admin/pages/edit" class="jura-btn jura-btn-primary">Create page</a>
    <a href="/admin/articles/edit" class="jura-btn jura-btn-secondary">Create article</a>
    <a href="/admin/blocks/edit" class="jura-btn jura-btn-secondary">Create block</a>
</div>

<?php View::component('table', [
    'headers' => ['Title', 'Slug', 'Status', 'Updated', 'Actions'],
    'rows' => [],
]); ?>

<div class="jura-empty">
    <p>No pages yet. TODO: implement pages repository.</p>
</div>
