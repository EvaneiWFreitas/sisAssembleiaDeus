<?php
$podeLancar = \App\Auth::hasPermission('financeiro', 'lancar');
$podeEditar = \App\Auth::hasPermission('financeiro', 'editar');
$podeExcluir = \App\Auth::hasPermission('financeiro', 'excluir');
$base = url('/admin/modulos/financeiro');
$editando = $categoria !== null;
$acao = $editando
    ? $base . '/categorias/' . (int) $categoria['id']
    : $base . '/categorias';
$valor = static function (string $campo) use ($valores, $categoria): string {
    if ($valores !== []) {
        return (string) ($valores[$campo] ?? '');
    }
    return $categoria === null ? '' : (string) ($categoria[$campo] ?? '');
};
$erroCampo = static function (string $campo) use ($erros): string {
    return isset($erros[$campo]) ? '<div class="invalid-feedback d-block">' . e($erros[$campo]) . '</div>' : '';
};
$selecionado = static function (mixed $atual, mixed $esperado): string {
    return (string) $atual === (string) $esperado ? 'selected' : '';
};
$ativo = $valores !== [] ? (string) ($valores['ativo'] ?? '1') !== '0' : ($categoria === null || (int) $categoria['ativo'] === 1);
?>
<div class="card-admin">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <ul class="nav nav-pills gap-2">
      <li class="nav-item"><a class="nav-link" href="<?= e($base) ?>"><i class="fa-solid fa-list me-1"></i>Movimentações</a></li>
      <li class="nav-item"><a class="nav-link active" href="<?= e($base . '/categorias') ?>"><i class="fa-solid fa-tags me-1"></i>Categorias</a></li>
      <li class="nav-item"><a class="nav-link" href="<?= e($base . '/fechamentos') ?>"><i class="fa-solid fa-calendar-check me-1"></i>Fechamentos</a></li>
    </ul>
  </div>

  <div class="row g-3">
    <div class="col-lg-5">
      <h2 class="h6 mb-3"><?= $editando ? 'Editar categoria' : 'Nova categoria' ?></h2>
      <?php if ($podeLancar || ($editando && $podeEditar)): ?>
        <form method="post" action="<?= e($acao) ?>">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label" for="nome">Nome</label>
            <input class="form-control <?= isset($erros['nome']) ? 'is-invalid' : '' ?>" id="nome" name="nome" value="<?= e($valor('nome')) ?>" maxlength="100" required>
            <?= $erroCampo('nome') ?>
          </div>
          <div class="mb-3">
            <label class="form-label" for="tipo">Tipo</label>
            <select class="form-select <?= isset($erros['tipo']) ? 'is-invalid' : '' ?>" id="tipo" name="tipo" required>
              <option value="entrada" <?= $selecionado($valor('tipo') !== '' ? $valor('tipo') : 'entrada', 'entrada') ?>>Entrada</option>
              <option value="saida" <?= $selecionado($valor('tipo'), 'saida') ?>>Saída</option>
            </select>
            <?= $erroCampo('tipo') ?>
          </div>
          <div class="mb-3">
            <label class="form-label" for="descricao">Descrição</label>
            <input class="form-control <?= isset($erros['descricao']) ? 'is-invalid' : '' ?>" id="descricao" name="descricao" value="<?= e($valor('descricao')) ?>" maxlength="255">
            <?= $erroCampo('descricao') ?>
          </div>
          <div class="form-check mb-3">
            <input type="hidden" name="ativo" value="0">
            <input class="form-check-input" type="checkbox" id="ativo" name="ativo" value="1" <?= $ativo ? 'checked' : '' ?>>
            <label class="form-check-label" for="ativo">Categoria ativa</label>
          </div>
          <div class="d-flex flex-wrap gap-2">
            <button class="btn btn-sm btn-dark rounded-pill" type="submit"><i class="fa-solid fa-floppy-disk me-1"></i>Salvar</button>
            <?php if ($editando): ?>
              <a class="btn btn-sm btn-outline-secondary rounded-pill" href="<?= e($base . '/categorias') ?>">Cancelar</a>
            <?php endif; ?>
          </div>
        </form>
      <?php else: ?>
        <div class="alert alert-info">Você tem permissão apenas para consultar as categorias.</div>
      <?php endif; ?>
    </div>

    <div class="col-lg-7">
      <div class="table-responsive">
        <table class="table table-hover align-middle admin-table mb-0">
          <thead><tr><th>Categoria</th><th>Tipo</th><th>Situação</th><th class="text-end">Ações</th></tr></thead>
          <tbody>
            <?php if ($categorias === []): ?>
              <tr><td colspan="4" class="text-center text-muted py-4">Nenhuma categoria cadastrada.</td></tr>
            <?php endif; ?>
            <?php foreach ($categorias as $item): ?>
              <tr>
                <td>
                  <strong class="d-block"><?= e((string) $item['nome']) ?></strong>
                  <small class="text-muted"><?= e((string) ($item['descricao'] ?? '')) ?></small>
                </td>
                <td><span class="badge rounded-pill <?= (string) $item['tipo'] === 'entrada' ? 'text-bg-success' : 'text-bg-danger' ?>"><?= (string) $item['tipo'] === 'entrada' ? 'Entrada' : 'Saída' ?></span></td>
                <td><span class="badge text-bg-light"><?= (int) $item['ativo'] === 1 ? 'Ativa' : 'Inativa' ?></span></td>
                <td class="text-end">
                  <div class="btn-group btn-group-sm">
                    <?php if ($podeEditar): ?>
                      <a class="btn btn-outline-dark" href="<?= e($base . '/categorias?editar=' . (int) $item['id']) ?>"><i class="fa-solid fa-pen"></i></a>
                    <?php endif; ?>
                    <?php if ($podeExcluir): ?>
                      <form method="post" action="<?= e($base . '/categorias/' . (int) $item['id'] . '/excluir') ?>" onsubmit="return confirm('Deseja excluir esta categoria?');">
                        <?= csrf_field() ?>
                        <button class="btn btn-outline-danger" type="submit"><i class="fa-solid fa-trash-can"></i></button>
                      </form>
                    <?php endif; ?>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
