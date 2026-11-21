<?php
$podeResponder = \App\Auth::hasPermission('contato', 'responder');
$base = url('/admin/modulos/contato');
$valorFiltro = static function (string $campo) use ($filtros): string {
    return (string) ($filtros[$campo] ?? '');
};
$selecionado = static function (mixed $atual, mixed $esperado): string {
    return (string) $atual === (string) $esperado ? 'selected' : '';
};
$resumo = static function (string $texto): string {
    $texto = trim($texto);
    return mb_strlen($texto) > 90 ? mb_substr($texto, 0, 87) . '...' : $texto;
};
$indicadores = [
    ['Mensagens', (string) ($estatisticas['total'] ?? 0), 'fa-envelope'],
    ['Não lidas', (string) ($estatisticas['nao_lidas'] ?? 0), 'fa-envelope-circle-check'],
    ['Respondidas', (string) ($estatisticas['respondidas'] ?? 0), 'fa-circle-check'],
];
?>
<div class="row g-3 mb-3">
  <?php foreach ($indicadores as $indicador): ?>
    <div class="col-12 col-md-4">
      <div class="card-admin stat-card">
        <span class="stat-icon"><i class="fa-solid <?= e($indicador[2]) ?>"></i></span>
        <div>
          <div class="stat-value fs-6"><?= e($indicador[1]) ?></div>
          <div class="stat-label"><?= e($indicador[0]) ?></div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="card-admin">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
      <h5 class="mb-0">Mensagens recebidas</h5>
      <div class="small text-muted">Mensagens enviadas pelo formulário de contato do site.</div>
    </div>
    <?php if ($podeResponder && (int) ($estatisticas['lidas'] ?? 0) > 0): ?>
      <form method="post" action="<?= e($base . '/excluir-lidas') ?>" onsubmit="return confirm('Excluir todas as mensagens lidas? Esta ação não pode ser desfeita.');">
        <?= csrf_field() ?>
        <button class="btn btn-sm btn-outline-danger rounded-pill" type="submit"><i class="fa-solid fa-trash me-1"></i>Excluir lidas (<?= (int) $estatisticas['lidas'] ?>)</button>
      </form>
    <?php endif; ?>
  </div>

  <form class="row g-2 align-items-center mb-3" method="get" action="<?= e($base) ?>">
    <div class="col-12 col-md-7">
      <input class="form-control form-control-sm" type="search" name="q" value="<?= e($valorFiltro('q')) ?>" placeholder="Nome, e-mail, assunto ou mensagem" aria-label="Buscar">
    </div>
    <div class="col-8 col-md-4">
      <select class="form-select form-select-sm" name="situacao" aria-label="Situação">
        <option value="">Todas as situações</option>
        <option value="nao_lidas" <?= $selecionado($valorFiltro('situacao'), 'nao_lidas') ?>>Não lidas</option>
        <option value="lidas" <?= $selecionado($valorFiltro('situacao'), 'lidas') ?>>Lidas</option>
        <option value="respondidas" <?= $selecionado($valorFiltro('situacao'), 'respondidas') ?>>Respondidas</option>
        <option value="nao_respondidas" <?= $selecionado($valorFiltro('situacao'), 'nao_respondidas') ?>>Aguardando resposta</option>
      </select>
    </div>
    <div class="col-4 col-md-1 d-flex gap-1">
      <button class="btn btn-sm btn-dark" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
      <a class="btn btn-sm btn-outline-secondary" href="<?= e($base) ?>"><i class="fa-solid fa-rotate-left"></i></a>
    </div>
  </form>

  <?php if ($mensagens === []): ?>
    <div class="text-center text-muted py-5">Nenhuma mensagem encontrada.</div>
  <?php else: ?>
    <div class="table-responsive">
      <table class="table table-hover align-middle admin-table mb-0">
        <thead>
          <tr>
            <th>Remetente</th>
            <th>Assunto</th>
            <th>Recebida</th>
            <th>Situação</th>
            <th class="text-end">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($mensagens as $mensagem): ?>
            <?php $id = (int) $mensagem['id']; ?>
            <tr>
              <td>
                <div class="fw-semibold"><?= e((string) $mensagem['nome']) ?></div>
                <div class="small text-muted"><?= e((string) $mensagem['email']) ?></div>
              </td>
              <td>
                <div class="fw-semibold text-break-anywhere"><?= e((string) ($mensagem['assunto'] !== '' ? $mensagem['assunto'] : 'Sem assunto')) ?></div>
                <div class="small text-muted text-break-anywhere"><?= e($resumo((string) $mensagem['mensagem'])) ?></div>
              </td>
              <td><?= e(format_date((string) $mensagem['created_at'])) ?></td>
              <td>
                <span class="badge <?= (int) $mensagem['lida'] === 1 ? 'text-bg-secondary' : 'text-bg-warning' ?>"><?= (int) $mensagem['lida'] === 1 ? 'Lida' : 'Não lida' ?></span>
                <?php if ($mensagem['respondida_em'] !== null): ?>
                  <span class="badge text-bg-success">Respondida</span>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <a class="btn btn-sm btn-outline-primary" href="<?= e($base . '/' . $id) ?>" title="Ver detalhes"><i class="fa-solid fa-eye"></i></a>
                <?php if ($podeResponder && (int) $mensagem['lida'] === 1): ?>
                  <form class="d-inline" method="post" action="<?= e($base . '/' . $id . '/excluir') ?>" onsubmit="return confirm('Excluir esta mensagem lida? Esta ação não pode ser desfeita.');">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-danger" type="submit" title="Excluir mensagem lida"><i class="fa-solid fa-trash"></i></button>
                  </form>
                <?php endif; ?>
                <?php if ($podeResponder): ?>
                  <?php if ((int) $mensagem['lida'] === 0): ?>
                    <form class="d-inline" method="post" action="<?= e($base . '/' . $id . '/lida') ?>">
                      <?= csrf_field() ?>
                      <button class="btn btn-sm btn-outline-info" type="submit" title="Marcar como lida"><i class="fa-solid fa-check"></i></button>
                    </form>
                  <?php endif; ?>
                  <form class="d-inline" method="post" action="<?= e($base . '/' . $id . ($mensagem['respondida_em'] === null ? '/respondida' : '/resposta/desmarcar')) ?>">
                    <?= csrf_field() ?>
                    <button class="btn btn-sm btn-outline-<?= $mensagem['respondida_em'] === null ? 'success' : 'secondary' ?>" type="submit" title="<?= $mensagem['respondida_em'] === null ? 'Marcar como respondida' : 'Desmarcar resposta' ?>"><i class="fa-solid fa-reply"></i></button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
