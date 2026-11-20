<?php
$podeFechar = \App\Auth::hasPermission('financeiro', 'fechar');
$base = url('/admin/modulos/financeiro');
$valor = static function (string $campo) use ($valores): string {
    return (string) ($valores[$campo] ?? '');
};
$selecionado = static function (mixed $atual, mixed $esperado): string {
    return (string) $atual === (string) $esperado ? 'selected' : '';
};
?>
<div class="card-admin">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <ul class="nav nav-pills gap-2">
      <li class="nav-item"><a class="nav-link" href="<?= e($base) ?>"><i class="fa-solid fa-list me-1"></i>Movimentações</a></li>
      <li class="nav-item"><a class="nav-link" href="<?= e($base . '/categorias') ?>"><i class="fa-solid fa-tags me-1"></i>Categorias</a></li>
      <li class="nav-item"><a class="nav-link active" href="<?= e($base . '/fechamentos') ?>"><i class="fa-solid fa-calendar-check me-1"></i>Fechamentos</a></li>
    </ul>
  </div>

  <?php if ($podeFechar): ?>
    <form class="row g-3 align-items-end mb-4" method="post" action="<?= e($base . '/fechamentos') ?>">
      <?= csrf_field() ?>
      <div class="col-md-3">
        <label class="form-label" for="competencia">Competência</label>
        <input class="form-control" id="competencia" name="competencia" type="month" value="<?= e($valor('competencia') !== '' ? $valor('competencia') : date('Y-m')) ?>" required>
      </div>
      <div class="col-md-4">
        <label class="form-label" for="congregacao_id">Congregação</label>
        <select class="form-select" id="congregacao_id" name="congregacao_id">
          <option value="0">Todas as congregações</option>
          <?php foreach ($congregacoes as $congregacao): ?>
            <option value="<?= e((int) $congregacao['id']) ?>" <?= $selecionado($valor('congregacao_id'), (int) $congregacao['id']) ?>><?= e((string) $congregacao['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label" for="observacoes">Observações</label>
        <input class="form-control" id="observacoes" name="observacoes" value="<?= e($valor('observacoes')) ?>" maxlength="1000">
      </div>
      <div class="col-md-2">
        <button class="btn btn-warning w-100" type="submit" onclick="return confirm('Fechar esta competência? As movimentações do período serão bloqueadas para edição.');"><i class="fa-solid fa-lock me-1"></i>Fechar</button>
      </div>
    </form>
  <?php else: ?>
    <div class="alert alert-info">Você tem permissão apenas para consultar os fechamentos.</div>
  <?php endif; ?>

  <div class="table-responsive">
    <table class="table table-hover align-middle admin-table mb-0">
      <thead><tr><th>Competência</th><th>Congregação</th><th class="text-end">Entradas</th><th class="text-end">Saídas</th><th class="text-end">Saldo</th><th>Fechado por</th><th>Data</th></tr></thead>
      <tbody>
        <?php if ($fechamentos === []): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">Nenhum fechamento registrado.</td></tr>
        <?php endif; ?>
        <?php foreach ($fechamentos as $fechamento): ?>
          <tr>
            <td><strong><?= e((string) $fechamento['competencia']) ?></strong></td>
            <td class="small"><?= e((string) ($fechamento['congregacao_nome'] ?? 'Todas as congregações')) ?></td>
            <td class="text-end small"><?= e(format_money((float) $fechamento['total_entradas'])) ?></td>
            <td class="text-end small"><?= e(format_money((float) $fechamento['total_saidas'])) ?></td>
            <td class="text-end small fw-semibold"><?= e(format_money((float) $fechamento['saldo'])) ?></td>
            <td class="small"><?= e((string) ($fechamento['fechado_por_nome'] ?? '—')) ?></td>
            <td class="small"><?= e(format_date((string) $fechamento['fechado_em'], 'd/m/Y H:i')) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
