<?php
$podeResponder = \App\Auth::hasPermission('contato', 'responder');
$base = url('/admin/modulos/contato');
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <a class="btn btn-sm btn-outline-secondary rounded-pill" href="<?= e($base) ?>"><i class="fa-solid fa-arrow-left me-1"></i>Voltar para mensagens</a>
</div>

<div class="card-admin">
  <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
    <div>
      <h5 class="mb-1 text-break-anywhere"><?= e((string) ($mensagem['assunto'] !== '' ? $mensagem['assunto'] : 'Sem assunto')) ?></h5>
      <div class="small text-muted">Recebida em <?= e(format_date((string) $mensagem['created_at'])) ?></div>
    </div>
    <div class="d-flex flex-wrap gap-2">
      <span class="badge <?= (int) $mensagem['lida'] === 1 ? 'text-bg-secondary' : 'text-bg-warning' ?>"><?= (int) $mensagem['lida'] === 1 ? 'Lida' : 'Não lida' ?></span>
      <?php if ($mensagem['respondida_em'] !== null): ?>
        <span class="badge text-bg-success">Respondida em <?= e(format_date((string) $mensagem['respondida_em'])) ?></span>
      <?php else: ?>
        <span class="badge text-bg-secondary">Aguardando resposta</span>
      <?php endif; ?>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-md-6">
      <div class="border rounded p-3 h-100">
        <div class="small text-muted mb-1">Remetente</div>
        <div class="fw-semibold"><?= e((string) $mensagem['nome']) ?></div>
        <a class="small text-decoration-none text-break-anywhere" href="mailto:<?= e((string) $mensagem['email']) ?>"><?= e((string) $mensagem['email']) ?></a>
      </div>
    </div>
    <div class="col-md-6">
      <div class="border rounded p-3 h-100">
        <div class="small text-muted mb-1">Contato</div>
        <?php if ((string) ($mensagem['telefone'] ?? '') !== ''): ?>
          <a class="text-decoration-none" href="tel:<?= e(preg_replace('/\D/', '', (string) $mensagem['telefone'])) ?>"><?= e((string) $mensagem['telefone']) ?></a>
        <?php else: ?>
          <span class="text-muted">Não informado</span>
        <?php endif; ?>
        <div class="small text-muted mt-2">IP: <?= e((string) ($mensagem['ip'] ?? '—')) ?></div>
      </div>
    </div>
  </div>

  <div class="border rounded p-3 mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
      <div class="small text-muted">Mensagem</div>
      <div class="small text-muted"><?= e((string) mb_strlen((string) $mensagem['mensagem'])) ?> caracteres</div>
    </div>
    <div class="contact-message"><?= e((string) $mensagem['mensagem']) ?></div>
  </div>

  <?php if ($podeResponder): ?>
    <div class="d-flex flex-wrap gap-2">
      <?php if ((int) $mensagem['lida'] === 0): ?>
        <form method="post" action="<?= e($base . '/' . (int) $mensagem['id'] . '/lida') ?>">
          <?= csrf_field() ?>
          <button class="btn btn-sm btn-outline-info rounded-pill" type="submit"><i class="fa-solid fa-check me-1"></i>Marcar como lida</button>
        </form>
      <?php endif; ?>
      <form method="post" action="<?= e($base . '/' . (int) $mensagem['id'] . ($mensagem['respondida_em'] === null ? '/respondida' : '/resposta/desmarcar')) ?>">
        <?= csrf_field() ?>
        <button class="btn btn-sm btn-outline-<?= $mensagem['respondida_em'] === null ? 'success' : 'secondary' ?> rounded-pill" type="submit">
          <i class="fa-solid fa-reply me-1"></i><?= $mensagem['respondida_em'] === null ? 'Marcar como respondida' : 'Desmarcar resposta' ?>
        </button>
      </form>
<<<<<<< HEAD
      <?php if ((int) $mensagem['lida'] === 1): ?>
        <form method="post" action="<?= e($base . '/' . (int) $mensagem['id'] . '/excluir') ?>" onsubmit="return confirm('Excluir esta mensagem lida? Esta ação não pode ser desfeita.');">
          <?= csrf_field() ?>
          <button class="btn btn-sm btn-outline-danger rounded-pill" type="submit"><i class="fa-solid fa-trash me-1"></i>Excluir mensagem</button>
        </form>
      <?php endif; ?>
=======
      <form method="post" action="<?= e($base . '/' . (int) $mensagem['id'] . '/excluir') ?>" onsubmit="return confirm('Excluir esta mensagem?');">
        <?= csrf_field() ?>
        <button class="btn btn-sm btn-outline-danger rounded-pill" type="submit"><i class="fa-solid fa-trash me-1"></i>Excluir mensagem</button>
      </form>
>>>>>>> cd83d20b3a8e458f36185ab3e53e975ae3c57e98
    </div>
  <?php endif; ?>
</div>
