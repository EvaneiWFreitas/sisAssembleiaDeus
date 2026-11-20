<?php
$podeCriar = \App\Auth::hasPermission('estrutura', 'criar');
$podeEditar = \App\Auth::hasPermission('estrutura', 'editar');
$podeExcluir = \App\Auth::hasPermission('estrutura', 'excluir');

$situacao = static function (string $valor): string {
    return $valor === 'ativa'
        ? '<span class="badge text-bg-success rounded-pill">Ativa</span>'
        : '<span class="badge text-bg-secondary rounded-pill">Inativa</span>';
};

$acoes = static function (array $item, string $tipo) use ($podeEditar, $podeExcluir): string {
    $id = (int) $item['id'];
    $html = '<div class="btn-group btn-group-sm">';
    if ($podeEditar) {
        $html .= '<a class="btn btn-outline-dark" href="' . e(url('/admin/modulos/estrutura/' . $tipo . '/' . $id . '/editar')) . '"><i class="fa-solid fa-pen"></i> Editar</a>';
    }
    if ($podeExcluir) {
        $rotulo = ($item['situacao'] ?? '') === 'ativa' ? 'Desativar' : 'Ativar';
        $cor = ($item['situacao'] ?? '') === 'ativa' ? 'danger' : 'success';
        $icone = ($item['situacao'] ?? '') === 'ativa' ? 'fa-circle-xmark' : 'fa-circle-check';
        $html .= '<form method="post" action="' . e(url('/admin/modulos/estrutura/' . $tipo . '/' . $id . '/situacao')) . '" onsubmit="return confirm(\'Deseja alterar a situação deste registro?\');">'
            . csrf_field()
            . '<button class="btn btn-outline-' . $cor . '" type="submit"><i class="fa-solid ' . $icone . '"></i> ' . $rotulo . '</button>'
            . '</form>';
    }
    $html .= '</div>';

    return $html;
};

$indicadores = [
    ['Matrizes', (int) ($contadores['matriz']['total'] ?? 0), (int) ($contadores['matriz']['ativas'] ?? 0), 'fa-church'],
    ['Congregações', (int) ($contadores['congregacao']['total'] ?? 0), (int) ($contadores['congregacao']['ativas'] ?? 0), 'fa-house-chimney'],
    ['Pontos de pregação', (int) ($contadores['ponto']['total'] ?? 0), (int) ($contadores['ponto']['ativas'] ?? 0), 'fa-location-dot'],
];
?>
<div class="row g-3 mb-3">
  <?php foreach ($indicadores as $indicador): ?>
    <div class="col-md-4">
      <div class="card-admin stat-card">
        <span class="stat-icon"><i class="fa-solid <?= e($indicador[3]) ?>"></i></span>
        <div>
          <div class="stat-value"><?= e($indicador[1]) ?></div>
          <div class="stat-label"><?= e($indicador[0]) ?> · <?= e($indicador[2]) ?> ativa(s)</div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="card-admin mb-3">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
      <h2 class="h6 mb-1">Matrizes</h2>
      <p class="small text-muted mb-0">Sedes responsáveis pelas congregações e pontos de pregação.</p>
    </div>
    <?php if ($podeCriar): ?>
      <a class="btn btn-sm btn-warning rounded-pill" href="<?= e(url('/admin/modulos/estrutura/matriz/novo')) ?>"><i class="fa-solid fa-plus me-1"></i>Nova matriz</a>
    <?php endif; ?>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle admin-table mb-0">
      <thead>
        <tr>
          <th>Matriz</th>
          <th>CNPJ</th>
          <th>Pastor responsável</th>
          <th>Congregações</th>
          <th>Status</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($matrizes === []): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">Nenhuma matriz cadastrada.</td></tr>
        <?php endif; ?>
        <?php foreach ($matrizes as $matriz): ?>
          <tr>
            <td>
              <strong class="d-block"><?= e((string) $matriz['nome']) ?></strong>
              <small class="text-muted"><?= e(trim((string) ($matriz['cidade'] ?? '') . ' / ' . (string) ($matriz['estado'] ?? ''), ' /')) ?></small>
            </td>
            <td class="small"><?= e((string) ($matriz['cnpj'] ?? '') !== '' ? (string) $matriz['cnpj'] : '—') ?></td>
            <td><?= e((string) ($matriz['pastor_nome'] ?? '') !== '' ? (string) $matriz['pastor_nome'] : '—') ?></td>
            <td><span class="badge text-bg-light"><?= e((int) ($matriz['total_congregacoes'] ?? 0)) ?></span></td>
            <td><?= $situacao((string) $matriz['situacao']) ?></td>
            <td class="text-end"><?= $acoes($matriz, 'matriz') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card-admin mb-3">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
      <h2 class="h6 mb-1">Congregações</h2>
      <p class="small text-muted mb-0">Congregações vinculadas a uma matriz.</p>
    </div>
    <?php if ($podeCriar): ?>
      <a class="btn btn-sm btn-warning rounded-pill" href="<?= e(url('/admin/modulos/estrutura/congregacao/novo')) ?>"><i class="fa-solid fa-plus me-1"></i>Nova congregação</a>
    <?php endif; ?>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle admin-table mb-0">
      <thead>
        <tr>
          <th>Congregação</th>
          <th>Matriz</th>
          <th>Pastor responsável</th>
          <th>Pontos</th>
          <th>Status</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($congregacoes === []): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">Nenhuma congregação cadastrada.</td></tr>
        <?php endif; ?>
        <?php foreach ($congregacoes as $congregacao): ?>
          <tr>
            <td>
              <strong class="d-block"><?= e((string) $congregacao['nome']) ?></strong>
              <small class="text-muted"><?= e((string) $congregacao['codigo']) ?></small>
            </td>
            <td><?= e((string) ($congregacao['matriz_nome'] ?? '—')) ?></td>
            <td><?= e((string) ($congregacao['pastor_nome'] ?? '') !== '' ? (string) $congregacao['pastor_nome'] : '—') ?></td>
            <td><span class="badge text-bg-light"><?= e((int) ($congregacao['total_pontos'] ?? 0)) ?></span></td>
            <td><?= $situacao((string) $congregacao['situacao']) ?></td>
            <td class="text-end"><?= $acoes($congregacao, 'congregacao') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card-admin">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
      <h2 class="h6 mb-1">Pontos de pregação</h2>
      <p class="small text-muted mb-0">Locais de pregação vinculados a uma congregação.</p>
    </div>
    <?php if ($podeCriar): ?>
      <a class="btn btn-sm btn-warning rounded-pill" href="<?= e(url('/admin/modulos/estrutura/ponto/novo')) ?>"><i class="fa-solid fa-plus me-1"></i>Novo ponto</a>
    <?php endif; ?>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle admin-table mb-0">
      <thead>
        <tr>
          <th>Ponto de pregação</th>
          <th>Congregação / Matriz</th>
          <th>Responsável</th>
          <th>Dias e horários</th>
          <th>Status</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($pontos === []): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">Nenhum ponto de pregação cadastrado.</td></tr>
        <?php endif; ?>
        <?php foreach ($pontos as $ponto): ?>
          <tr>
            <td>
              <strong class="d-block"><?= e((string) $ponto['identificacao']) ?></strong>
              <small class="text-muted"><?= e(trim((string) ($ponto['bairro'] ?? '') . ' · ' . (string) ($ponto['cidade'] ?? ''), ' ·')) ?></small>
            </td>
            <td>
              <span class="d-block"><?= e((string) $ponto['congregacao_nome']) ?></span>
              <small class="text-muted"><?= e((string) $ponto['matriz_nome']) ?></small>
            </td>
            <td><?= e((string) ($ponto['responsavel'] ?? '') !== '' ? (string) $ponto['responsavel'] : '—') ?></td>
            <td class="small"><?= e((string) ($ponto['dias_horarios'] ?? '') !== '' ? (string) $ponto['dias_horarios'] : '—') ?></td>
            <td><?= $situacao((string) $ponto['situacao']) ?></td>
            <td class="text-end"><?= $acoes($ponto, 'ponto') ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
