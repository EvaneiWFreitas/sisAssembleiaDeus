<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card-admin">
      <span class="stat-icon"><i class="fa-solid <?= e($modulo['icone']) ?>"></i></span>
      <h2 class="h5 mt-3"><?= e($modulo['nome']) ?></h2>
      <p class="text-secondary"><?= e($modulo['descricao']) ?></p>
      <div class="alert alert-info">
        <i class="fa-solid fa-circle-info me-2"></i>
        Este módulo está planejado para uma etapa posterior. A rota, a permissão e a navegação já estão protegidas pelo perfil conectado.
      </div>
      <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-dark rounded-pill" href="<?= e(url('/admin')) ?>"><i class="fa-solid fa-arrow-left me-2"></i>Voltar ao painel</a>
        <span class="btn btn-outline-secondary rounded-pill disabled"><i class="fa-solid fa-key me-2"></i><?= e($modulo['modulo'] . '.' . $modulo['acao']) ?></span>
      </div>
    </div>
  </div>
</div>
