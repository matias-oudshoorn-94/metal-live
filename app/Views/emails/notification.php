<?php /** @var App\Services\Notifications\Message $message */ ?>
<!doctype html>
<html lang="es">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title><?= e($message->subject) ?></title></head>
<body style="margin:0;padding:0;background:#f2f5f9;font-family:Segoe UI,Helvetica,Arial,sans-serif;color:#0f1e33">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f2f5f9;padding:24px 12px">
    <tr><td align="center">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:12px;overflow:hidden">
        <tr><td style="background:#0b1b36;padding:18px 24px;color:#ffffff;font-size:18px;font-weight:700;letter-spacing:.5px">
          <span style="color:#0794fb">●</span> METAL LIVE
        </td></tr>
        <tr><td style="padding:24px">
          <h1 style="margin:0 0 12px;font-size:19px;line-height:1.3"><?= e($message->subject) ?></h1>
          <p style="margin:0 0 18px;font-size:15px;line-height:1.5;color:#334155"><?= e($message->intro) ?></p>
          <?php if ($message->details): ?>
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:14px">
            <?php foreach ($message->details as $label => $value): ?>
              <tr>
                <td style="padding:8px 10px;border-bottom:1px solid #e6ebf2;color:#64748b;width:38%;vertical-align:top"><?= e($label) ?></td>
                <td style="padding:8px 10px;border-bottom:1px solid #e6ebf2;font-weight:600"><?= nl2br(e($value)) ?></td>
              </tr>
            <?php endforeach; ?>
          </table>
          <?php endif; ?>
          <?php if ($message->actionUrl): ?>
            <p style="margin:24px 0 8px"><a href="<?= e($message->actionUrl) ?>" style="display:inline-block;background:#0794fb;color:#ffffff;text-decoration:none;font-weight:700;padding:12px 22px;border-radius:8px"><?= e($message->actionLabel ?? 'Abrir') ?></a></p>
          <?php endif; ?>
          <?php if ($message->outro): ?>
            <p style="margin:16px 0 0;font-size:13px;color:#64748b"><?= e($message->outro) ?></p>
          <?php endif; ?>
        </td></tr>
        <tr><td style="padding:14px 24px;background:#f8fafc;font-size:12px;color:#94a3b8">Aviso automático del sistema de gestión de cirugías de Metal Live.</td></tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
