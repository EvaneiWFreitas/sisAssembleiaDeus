<?php
$podeExcluir = \App\Auth::hasPermission('documentos', 'excluir');
$base = url('/admin/modulos/documentos');
$valorFiltro = static function (string $campo) use ($filtros): string {
    return (string) ($filtros[$campo] ?? '');
};
$selecionado = static function (mixed $atual, mixed $esperado): string {
    return (string) $atual === (string) $esperado ? 'selected' : '';
};
$referencia = static function (array $documento): string {
    $dados = \App\DocumentoRepository::dadosDocumento($documento);
    foreach (['convidado', 'nome', 'titulo'] as $campo) {
        if (isset($dados[$campo]) && (string) $dados[$campo] !== '') {
            return (string) $dados[$campo];
        }
    }
    return (string) ($documento['referencia_id'] ?? '') !== '' ? 'Registro #' . (string) $documento['referencia_id'] : '—';
};
?>
<div class="card-admin">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <ul class="nav nav-pills gap-2">
      <li class="nav-item"><a class="nav-link" href="<?= e($base) ?>"><i class="fa-solid fa-envelope-open-text me-1"></i>Convites</a></li>
      <li class="nav-item"><a class="nav-link active" href="<?= e($base . '/emitidos') ?>"><i class="fa-solid fa-file-lines me-1"></i>Documentos emitidos</a></li>
    </ul>
    <span class="text-muted small">Registro central de impressões e emissões do sistema.</span>
  </div>

  <form class="row g-2 align-items-center mb-3" method="get" action="<?= e($base . '/emitidos') ?>">
    <div class="col-12 col-md-4">
      <input class="form-control form-control-sm" type="search" name="q" value="<?= e($valorFiltro('q')) ?>" placeholder="Número, tipo, pessoa ou emissor" aria-label="Buscar">
    </div>
    <div class="col-6 col-md-3">
      <select class="form-select form-select-sm" name="tipo" aria-label="Tipo">
        <option value="">Todos os tipos</option>
        <?php foreach (\App\DocumentoRepository::TIPOS as $chave => $rotulo): ?>
          <option value="<?= e($chave) ?>" <?= $selecionado($valorFiltro('tipo'), $chave) ?>><?= e($rotulo) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-6 col-md-2">
      <input class="form-control form-control-sm" type="date" name="de" value="<?= e($valorFiltro('de')) ?>" aria-label="Data inicial">
    </div>
    <div class="col-6 col-md-2">
      <input class="form-control form-control-sm" type="date" name="ate" value="<?= e($valorFiltro('ate')) ?>" aria-label="Data final">
    </div>
    <div class="col-6 col-md-1 d-flex gap-1">
      <button class="btn btn-sm btn-dark" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
      <a class="btn btn-sm btn-outline-secondary" href="<?= e($base . '/emitidos') ?>"><i class="fa-solid fa-rotate-left"></i></a>
    </div>
  </form>

  <div class="table-responsive">
    <table class="table table-hover align-middle admin-table mb-0">
      <thead>
        <tr>
          <th>Número</th><th>Tipo</th><th>Referência</th><th>Modelo</th><th>Emitido em</th><th>Emitido por</th><th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($documentos === []): ?>
          <tr><td colspan="7" class="text-center text-muted py-4">Nenhum documento emitido encontrado.</td></tr>
        <?php endif; ?>
        <?php foreach ($documentos as $documento): ?>
          <?php
          $id = (int) $documento['id'];
          $tipo = (string) $documento['tipo'];
          $imprimir = $tipo === 'carta_convite'
              ? $base . '/' . (int) $documento['referencia_id'] . '/imprimir'
              : $base . '/emitidos/' . $id . '/imprimir';
          ?>
          <tr>
            <td><span class="fw-semibold"><?= e((string) $documento['numero']) ?></span></td>
            <td><?= e(\App\DocumentoRepository::rotuloTipo($tipo)) ?></td>
            <td><?= e($referencia($documento)) ?></td>
            <td><?= e((string) ($documento['modelo'] ?? '') !== '' ? \App\DocumentoRepository::rotuloModelo((string) $documento['modelo']) : '—') ?></td>
            <td><?= e(format_date((string) $documento['emitido_em'], 'd/m/Y H:i')) ?></td>
            <td><?= e((string) ($documento['emitido_por_nome'] ?? '—')) ?></td>
            <td class="text-end">
              <a class="btn btn-sm btn-outline-primary" href="<?= e($imprimir) ?>" target="_blank" rel="noopener noreferrer" title="Imprimir"><i class="fa-solid fa-print"></i></a>
              <?php if ($podeExcluir): ?>
                <form class="d-inline" method="post" action="<?= e($base . '/emitidos/' . $id . '/excluir') ?>" onsubmit="return confirm('Cancelar este documento emitido?');">
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
