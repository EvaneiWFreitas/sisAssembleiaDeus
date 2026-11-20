<div class="card-admin mb-3">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
      <h2 class="h6 mb-1"><?= e((string) $membro['nome']) ?></h2>
      <p class="small text-muted mb-0">Código <?= e((string) $membro['codigo']) ?> · Situação <?= e(ucfirst((string) $membro['situacao'])) ?></p>
    </div>
    <div class="d-flex gap-2">
      <?php if (\App\Auth::hasPermission('membros', 'editar')): ?>
        <a class="btn btn-sm btn-outline-dark" href="<?= e(url('/admin/modulos/membros/' . (int) $membro['id'] . '/editar')) ?>">Voltar ao cadastro</a>
      <?php endif; ?>
      <a class="btn btn-sm btn-outline-secondary" href="<?= e(url('/admin/modulos/membros')) ?>">Listar membros</a>
    </div>
  </div>
</div>

<div class="card-admin">
  <h2 class="h6 mb-3">Alterações registradas</h2>
  <?php if ($historico === []): ?>
    <p class="text-muted mb-0">Nenhuma alteração registrada para este membro.</p>
  <?php else: ?>
    <div class="timeline">
      <?php
      $rotulos = [
          'criar' => 'Cadastro criado',
          'editar' => 'Cadastro atualizado',
          'excluir' => 'Movido para excluídos',
      ];
      ?>
      <?php foreach ($historico as $evento): ?>
        <div class="activity-item">
          <strong><?= e($rotulos[(string) $evento['acao']] ?? (string) $evento['acao']) ?></strong>
          <small>
            <?= e((string) ($evento['usuario'] ?? 'Sistema')) ?> ·
            <?= e(format_date((string) $evento['created_at'], 'd/m/Y H:i')) ?>
          </small>
          <?php if (!empty($evento['dados_novos'])): ?>
            <div class="mt-1"><code class="small"><?= e(mb_substr((string) $evento['dados_novos'], 0, 220)) ?></code></div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
