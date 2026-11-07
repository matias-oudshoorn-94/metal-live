<?php
$success = App\Core\Session::get('success');
$error = App\Core\Session::get('error');
?>
<?php if ($success): ?>
  <div class="alert alert-success" role="status" data-autohide><?= icon('check') ?><span><?= e($success) ?></span></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="alert alert-danger" role="alert"><?= icon('alert') ?><span><?= e($error) ?></span></div>
<?php endif; ?>
