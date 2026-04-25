<?php use App\Core\View; ?>
<h1>Pages</h1>
<div class="jura-actions">
    <a href="#" class="jura-btn jura-btn-primary">Create page</a>
</div>

<?php View::component('table', [
    'headers' => ['Title', 'Slug', 'Status', 'Updated', 'Actions'],
    'rows' => [],
]); ?>

<div class="jura-empty">
    <p>No pages yet. TODO: implement pages repository.</p>
</div>
