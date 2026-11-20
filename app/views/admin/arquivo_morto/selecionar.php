<div class="card-admin">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
      <h2 class="h6 mb-1">Selecione o obreiro</h2>
      <p class="small text-muted mb-0">Somente obreiros ainda não arquivados aparecem nesta lista.</p>
    </div>
    <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/modulos/arquivo_morto')) ?>">Voltar</a>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle admin-table mb-0">
      <thead>
        <tr>
          <th>Obreiro</th>
          <th>Cargo</th>
          <th>Congregação</th>
          <th>Situação atual</th>
          <th class="text-end">Ação</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($obreiros === []): ?>
          <tr><td colspan="5" class="text-center text-muted py-4">Nenhum obreiro disponível para arquivamento.</td></tr>
        <?php endif; ?>
        <?php foreach ($obreiros as $obreiro): ?>
          <tr>
            <td>
              <strong class="d-block"><?= e((string) $obreiro['nome']) ?></strong>
              <small class="text-muted"><?= e((string) $obreiro['codigo']) ?></small>
            </td>
            <td><?= e((string) ($obreiro['cargo_nome'] ?? '—')) ?></td>
            <td><?= e((string) ($obreiro['congregacao_nome'] ?? '—')) ?></td>
            <td><span class="badge text-bg-light"><?= e(ucfirst((string) $obreiro['situacao'])) ?></span></td>
            <td class="text-end">
              <a class="btn btn-sm btn-warning" href="<?= e(url('/admin/modulos/arquivo_morto/arquivar/' . (int) $obreiro['id'])) ?>"><i class="fa-solid fa-box-archive me-1"></i>Arquivar</a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
