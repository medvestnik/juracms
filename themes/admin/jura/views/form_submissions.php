<?php $rows=$submissions??[]; ?>
<section class="jura-card">
  <h2 style="margin-top:0">Заявки з сайту <small style="font-weight:400;color:#64748b">(<?= (int)$total ?>)</small></h2>
  <p style="color:#64748b">Усі заявки, отримані через стандартні форми JuraCMS. Сповіщення поштою та Telegram налаштовуються в розділі <a href="/admin/settings">Налаштування</a>.</p>
  <div style="overflow-x:auto">
    <table style="width:100%;border-collapse:collapse">
      <thead><tr><th style="text-align:left;padding:12px">Дата</th><th style="text-align:left;padding:12px">Форма</th><th style="text-align:left;padding:12px">Контакт</th><th style="text-align:left;padding:12px">Тема та повідомлення</th></tr></thead>
      <tbody>
      <?php foreach($rows as $item): $payload=json_decode((string)($item['payload_json']??'{}'),true);$payload=is_array($payload)?$payload:[]; ?>
        <tr style="border-top:1px solid #e2e8f0">
          <td style="padding:12px;white-space:nowrap"><?= e((string)($item['created_at']??'')) ?></td>
          <td style="padding:12px"><?= e((string)($item['form_code']??'')) ?></td>
          <td style="padding:12px"><strong><?= e((string)($item['name']??'')) ?></strong><div><?= e((string)($item['phone']??'')) ?></div><div><?= e((string)($item['email']??'')) ?></div></td>
          <td style="padding:12px;max-width:540px">
            <?php if(!empty($payload['topic'])): ?><strong><?= e((string)$payload['topic']) ?></strong><?php endif; ?>
            <div style="white-space:pre-wrap;overflow-wrap:anywhere"><?= e((string)($item['message']??'')) ?></div>
            <?php if(!empty($item['source_url'])): ?><small style="color:#64748b"><?= e((string)$item['source_url']) ?></small><?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if(!$rows): ?><tr><td colspan="4" style="padding:20px;color:#64748b">Заявок ще немає.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if($pages>1): ?><nav style="display:flex;gap:8px;margin-top:20px">
    <?php if($page>1): ?><a class="jura-btn" href="?page=<?= $page-1 ?>">← Попередні</a><?php endif; ?>
    <span style="padding:10px">Сторінка <?= (int)$page ?> із <?= (int)$pages ?></span>
    <?php if($page<$pages): ?><a class="jura-btn" href="?page=<?= $page+1 ?>">Наступні →</a><?php endif; ?>
  </nav><?php endif; ?>
</section>
