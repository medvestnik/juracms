<section class="jura-card"><h1><?= e($edit ? 'Edit page':'Pages'); ?></h1>
<?php if ($edit!==null || (($_SERVER['REQUEST_URI']??'')==='/admin/pages/create')): $p=$edit??[]; ?>
<form method="post" action="<?= e($edit?('/admin/pages/'.$p['id']):'/admin/pages') ?>">
<input name="title" placeholder="Title" value="<?=e($p['title']??'')?>" required>
<input name="slug" placeholder="Slug" value="<?=e($p['slug']??'')?>">
<input name="route_path" placeholder="Route" value="<?=e($p['route_path']??'')?>">
<select name="status"><?php foreach(['draft','published','hidden'] as $s):?><option value="<?=$s?>" <?=($p['status']??'draft')===$s?'selected':''?>><?=$s?></option><?php endforeach;?></select>
<select name="template"><?php foreach(['home','page','blog'] as $t):?><option value="<?=$t?>" <?=($p['template']??'page')===$t?'selected':''?>><?=$t?></option><?php endforeach;?></select>
<textarea name="excerpt" placeholder="Excerpt"><?=e($p['excerpt']??'')?></textarea>
<textarea name="content" data-editor="simple-js-editor" rows="10" placeholder="Content"><?=e($p['content']??'')?></textarea>
<input name="meta_title" placeholder="Meta title" value="<?=e($p['meta_title']??'')?>"><input name="meta_description" placeholder="Meta description" value="<?=e($p['meta_description']??'')?>"><input type="number" name="sort_order" value="<?=e((string)($p['sort_order']??0))?>">
<button class="jura-btn jura-btn-primary">Save</button></form>
<?php else: ?><a class="jura-btn jura-btn-primary" href="/admin/pages/create">Add page</a>
<table class="jura-table"><thead><tr><th>ID</th><th>Title</th><th>Slug</th><th>Route</th><th>Status</th><th>Template</th><th>Updated</th><th>Actions</th></tr></thead><tbody><?php foreach(($pages??[]) as $p):?><tr><td><?=e($p['id'])?></td><td><?=e($p['title'])?></td><td><?=e($p['slug'])?></td><td><?=e($p['route_path']??'')?></td><td><?=e($p['status'])?></td><td><?=e($p['template']??'page')?></td><td><?=e($p['updated_at'])?></td><td><a href="/admin/pages/<?=$p['id']?>/edit">Edit</a> <a href="<?=e($p['route_path']??'/')?>" target="_blank">View</a><form method="post" action="/admin/pages/<?=$p['id']?>/delete" style="display:inline"><button>Delete</button></form></td></tr><?php endforeach;?></tbody></table><?php endif; ?></section>
