<?php
$podeEmitir = \App\Auth::hasPermission('carteirinhas', 'emitir');
$podeValidar = \App\Auth::hasPermission('carteirinhas', 'validar');
$podeExcluir = \App\Auth::hasPermission('carteirinhas', 'excluir');
$base = url('/admin/modulos/carteirinhas');
$valorFiltro = static function (string $campo) use ($filtros): string {
    return (string) ($filtros[$campo] ?? '');
};
$selecionado = static function (mixed $atual, mixed $esperado): string {
    return (string) $atual === (string) $esperado ? 'selected' : '';
};
$indicadores = [
    ['Emitidas', (string) ($estatisticas['total'] ?? 0), 'fa-id-card'],
    ['Vigentes', (string) ($estatisticas['vigentes'] ?? 0), 'fa-circle-check'],
    ['Segundas vias', (string) ($estatisticas['segundas'] ?? 0), 'fa-copy'],
    ['Vencidas', (string) ($estatisticas['vencidas'] ?? 0), 'fa-circle-exclamation'],
];
$situacaoRotulos = ['valida' => 'Vigentes', 'vencida' => 'Vencidas'];
?>
<div class="row g-3 mb-3">
  <?php foreach ($indicadores as $indicador): ?>
    <div class="col-6 col-lg-3">
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
    <ul class="nav nav-pills gap-2">
      <li class="nav-item"><a class="nav-link active" href="<?= e($base) ?>"><i class="fa-solid fa-id-card me-1"></i>Carteirinhas</a></li>
      <?php if ($podeValidar): ?>
        <li class="nav-item"><a class="nav-link" href="<?= e($base . '/validar') ?>"><i class="fa-solid fa-qrcode me-1"></i>Validar</a></li>
      <?php endif; ?>
    </ul>
    <?php if ($podeEmitir): ?>
      <a class="btn btn-sm btn-warning rounded-pill" href="<?= e($base . '/novo') ?>"><i class="fa-solid fa-plus me-1"></i>Nova carteirinha</a>
    <?php endif; ?>
  </div>

  <form class="row g-2 align-items-center mb-3" method="get" action="<?= e($base) ?>">
    <div class="col-12 col-md-3">
      <input class="form-control form-control-sm" type="search" name="q" value="<?= e($valorFiltro('q')) ?>" placeholder="Código, pessoa ou congregação" aria-label="Buscar">
    </div>
    <div class="col-6 col-md-2">
      <select class="form-select form-select-sm" name="tipo" aria-label="Tipo">
        <option value="">Todos os tipos</option>
        <?php foreach (\App\CarteirinhaRepository::TIPOS as $chave => $rotulo): ?>
          <option value="<?= e($chave) ?>" <?= $selecionado($valorFiltro('tipo'), $chave) ?>><?= e($rotulo) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-2">
      <select class="form-select form-select-sm" name="modelo" aria-label="Modelo">
        <option value="">Todos os modelos</option>
        <?php foreach (\App\CarteirinhaRepository::MODELOS as $chave => $rotulo): ?>
          <option value="<?= e($chave) ?>" <?= $selecionado($valorFiltro('modelo'), $chave) ?>><?= e($rotulo) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-2">
      <select class="form-select form-select-sm" name="congregacao_id" aria-label="Congregação">
        <option value="">Todas</option>
        <?php foreach ($congregacoes as $congregacao): ?>
          <option value="<?= e((int) $congregacao['id']) ?>" <?= $selecionado($valorFiltro('congregacao_id'), (int) $congregacao['id']) ?>><?= e((string) $congregacao['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-1">
      <select class="form-select form-select-sm" name="situacao" aria-label="Situação">
        <option value="">Todas</option>
        <?php foreach ($situacaoRotulos as $chave => $rotulo): ?>
          <option value="<?= e($chave) ?>" <?= $selecionado($valorFiltro('situacao'), $chave) ?>><?= e($rotulo) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-1">
      <select class="form-select form-select-sm" name="segunda_via" aria-label="Via">
        <option value="">Todas</option>
        <option value="0" <?= $selecionado($valorFiltro('segunda_via'), '0') ?>>1ª via</option>
        <option value="1" <?= $selecionado($valorFiltro('segunda_via'), '1') ?>>2ª via</option>
      </select>
    </div>
    <div class="col-12 col-md-1 d-flex gap-1">
      <button class="btn btn-sm btn-dark" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
      <a class="btn btn-sm btn-outline-secondary" href="<?= e($base) ?>"><i class="fa-solid fa-rotate-left"></i></a>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table table-hover align-middle admin-table mb-0">
      <thead>
        <tr>
          <th>Código</th><th>Pessoa</th><th>Tipo</th><th>Congregação</th><th>Modelo</th><th>Emissão</th><th>Validade</th><th>Via</th><th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($carteirinhas === []): ?>
          <tr><td colspan="9" class="text-center text-muted py-4">Nenhuma carteirinha encontrada.</td></tr>
        <?php endif; ?>
        <?php foreach ($carteirinhas as $carteirinha): ?>
          <?php
          $id = (int) $carteirinha['id'];
          $validade = (string) ($carteirinha['validade'] ?? '');
          $vencida = $validade !== '' && strtotime($validade) < strtotime(date('Y-m-d'));
          ?>
          <tr>
            <td><span class="fw-semibold"><?= e((string) $carteirinha['codigo_validacao']) ?></span></td>
            <td>
              <?= e((string) ($carteirinha['pessoa_nome'] ?? '—')) ?>
              <div class="small text-muted"><?= e((string) ($carteirinha['pessoa_codigo'] ?? '')) ?></div>
            </td>
            <td><?= e(\App\CarteirinhaRepository::rotuloTipo((string) $carteirinha['tipo'])) ?></td>
            <td><?= e((string) ($carteirinha['congregacao_nome'] ?? '—')) ?></td>
            <td><?= e(\App\CarteirinhaRepository::rotuloModelo((string) $carteirinha['modelo'])) ?></td>
            <td><?= e(format_date((string) $carteirinha['data_emissao'], 'd/m/Y')) ?></td>
            <td>
              <?php if ($validade === ''): ?>
                <span class="text-muted">Sem validade</span>
              <?php else: ?>
                <span class="<?= $vencida ? 'text-danger fw-semibold' : '' ?>"><?= e(format_date($validade)) ?></span>
              <?php endif; ?>
            </td>
            <td><?= (int) $carteirinha['segunda_via'] === 1 ? '2ª' : '1ª' ?></td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary" href="<?= e($base . '/' . $id . '/imprimir') ?>" target="_blank" rel="noopener noreferrer" title="Imprimir"><i class="fa-solid fa-print"></i></a>
              <?php if ($podeEmitir): ?>
                <a class="btn btn-sm btn-outline-secondary" href="<?= e($base . '/' . $id . '/editar') ?>" title="Editar"><i class="fa-solid fa-pen"></i></a>
              <?php endif; ?>
              <?php if ($podeExcluir): ?>
                <form class="d-inline" method="post" action="<?= e($base . '/' . $id . '/excluir') ?>" onsubmit="return confirm('Cancelar esta carteirinha e seu registro de emissão?');">
                  <?= csrf_field() ?>
                  <button class="btn btn-sm btn-outline-danger" type="submit" title="Cancelar"><i class="fa-solid fa-trash"></i></button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
