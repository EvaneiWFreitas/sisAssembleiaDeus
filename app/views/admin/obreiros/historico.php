<?php
$rotulos = [
    'cargo' => 'Alteração de cargo',
    'transferencia' => 'Transferência de congregação',
    'situacao' => 'Alteração de situação',
    'consagracao' => 'Consagração',
    'observacao' => 'Observação',
];
?>
<div class="card-admin mb-3">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
      <h2 class="h6 mb-1"><?= e((string) $obreiro['nome']) ?></h2>
      <p class="small text-muted mb-0">Código <?= e((string) $obreiro['codigo']) ?> · Cargo <?= e((string) $obreiro['cargo_nome']) ?> · Congregação <?= e((string) $obreiro['congregacao_nome']) ?></p>
    </div>
    <div class="d-flex gap-2">
      <?php if (\App\Auth::hasPermission('obreiros', 'editar')): ?>
        <a class="btn btn-sm btn-outline-dark" href="<?= e(url('/admin/modulos/obreiros/' . (int) $obreiro['id'] . '/editar')) ?>">Voltar ao cadastro</a>
      <?php endif; ?>
      <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/modulos/obreiros')) ?>">Listar obreiros</a>
    </div>
  </div>
</div>

<div class="card-admin">
  <h2 class="h6 mb-3">Histórico ministerial</h2>
  <?php if ($historico === []): ?>
    <p class="text-muted mb-0">Nenhum evento ministerial registrado para este obreiro.</p>
  <?php else: ?>
    <div class="timeline">
      <?php foreach ($historico as $evento): ?>
        <?php
        $tipo = (string) $evento['tipo'];
        $descricao = (string) ($evento['descricao'] ?? '');
        if ($tipo === 'cargo') {
            $descricao = 'De ' . (string) ($evento['cargo_anterior_nome'] ?? '—') . ' para ' . (string) ($evento['cargo_novo_nome'] ?? '—') . '.';
        } elseif ($tipo === 'transferencia') {
            $descricao = 'De ' . (string) ($evento['congregacao_anterior_nome'] ?? '—') . ' para ' . (string) ($evento['congregacao_nova_nome'] ?? '—') . '.'
                . ($descricao !== '' ? ' ' . $descricao : '');
        } elseif ($tipo === 'situacao') {
            $descricao = 'De ' . (string) ($evento['situacao_anterior'] ?? '—') . ' para ' . (string) ($evento['situacao_nova'] ?? '—') . '.'
                . ($descricao !== '' ? ' ' . $descricao : '');
        }
        ?>
        <div class="activity-item">
          <strong><?= e($rotulos[$tipo] ?? ucfirst($tipo)) ?></strong>
          <small>
            <?= e((string) ($evento['usuario'] ?? 'Sistema')) ?> ·
            <?= e(format_date((string) $evento['data_historico'], 'd/m/Y H:i')) ?>
          </small>
          <?php if ($descricao !== ''): ?><div class="mt-1 small"><?= e($descricao) ?></div><?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
