<?php
$podeEditar = \App\Auth::hasPermission('configuracoes', 'editar');
$desabilitado = $podeEditar ? '' : 'disabled';
$valorFiltro = static function (string $chave) use ($valores): string {
    return (string) ($valores[$chave] ?? '');
};
$erroCampo = static function (string $chave) use ($erros): string {
    return (string) ($erros[$chave] ?? '');
};
$textAreas = ['versiculo_texto', 'palavra_pastoral_texto', 'mensagem_rodape'];
?>
<div class="card-admin mb-3">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
      <h5 class="mb-0">Configurações gerais</h5>
      <div class="small text-muted">Os valores abaixo alimentam o site, os documentos e os dados de contato da igreja.</div>
    </div>
    <?php if (!$podeEditar): ?>
      <span class="badge text-bg-secondary">Somente leitura</span>
    <?php endif; ?>
  </div>
  <div class="nav nav-pills gap-2 mt-3">
    <?php foreach ($grupos as $grupo => $itens): ?>
      <li class="nav-item"><a class="nav-link" href="#config-group-<?= e($grupo) ?>"><?= e(\App\ConfiguracaoRepository::rotuloGrupo($grupo)) ?></a></li>
    <?php endforeach; ?>
  </div>
</div>

<form method="post" action="<?= e(url('/admin/modulos/configuracoes')) ?>" id="form-configuracoes" enctype="multipart/form-data" novalidate>
  <?= csrf_field() ?>
  <?php foreach ($grupos as $grupo => $itens): ?>
    <div class="card-admin mb-3" id="config-group-<?= e($grupo) ?>">
      <h5 class="mb-3"><?= e(\App\ConfiguracaoRepository::rotuloGrupo($grupo)) ?></h5>
      <div class="row g-3">
        <?php foreach ($itens as $item): ?>
          <?php
          $chave = (string) $item['chave'];
          $tipo = (string) $item['tipo'];
          $valor = $valorFiltro($chave);
          $erro = $erroCampo($chave);
          ?>
          <div class="col-12 col-lg-6">
            <label class="form-label small fw-semibold" for="config-<?= e($chave) ?>"><?= e((string) ($item['descricao'] ?? $chave)) ?></label>

            <?php if ($tipo === 'arquivo'): ?>
              <?php if ($valor !== ''): ?>
                <div class="mb-2"><img src="<?= e(\App\SiteRepository::imagem($valor, 'assets/img/logo-assembleia.svg')) ?>" alt="" style="max-height:90px;max-width:100%;object-fit:contain;background:#f1f5f9;padding:8px;"></div>
              <?php endif; ?>
              <input class="form-control form-control-sm <?= $erro !== '' ? 'is-invalid' : '' ?>" type="file" id="config-<?= e($chave) ?>" name="arquivo[<?= e($chave) ?>]" accept="image/jpeg,image/png,image/webp,image/gif" <?= $desabilitado ?>>
              <?php if ($podeEditar && $valor !== ''): ?>
                <div class="form-check mt-2">
                  <input class="form-check-input" type="checkbox" id="remover-<?= e($chave) ?>" name="remover[<?= e($chave) ?>]" value="1">
                  <label class="form-check-label small" for="remover-<?= e($chave) ?>">Remover arquivo atual</label>
                </div>
              <?php endif; ?>
            <?php elseif ($tipo === 'cor'): ?>
              <input class="form-control form-control-sm <?= $erro !== '' ? 'is-invalid' : '' ?>" type="color" id="config-<?= e($chave) ?>" name="config[<?= e($chave) ?>]" value="<?= e($valor !== '' ? $valor : '#0b2545') ?>" <?= $desabilitado ?>>
            <?php elseif ($tipo === 'numero'): ?>
              <input class="form-control form-control-sm <?= $erro !== '' ? 'is-invalid' : '' ?>" type="number" min="1" step="1" id="config-<?= e($chave) ?>" name="config[<?= e($chave) ?>]" value="<?= e($valor) ?>" <?= $desabilitado ?>>
            <?php elseif ($tipo === 'email'): ?>
              <input class="form-control form-control-sm <?= $erro !== '' ? 'is-invalid' : '' ?>" type="email" id="config-<?= e($chave) ?>" name="config[<?= e($chave) ?>]" value="<?= e($valor) ?>" <?= $desabilitado ?>>
            <?php elseif ($tipo === 'url'): ?>
              <input class="form-control form-control-sm <?= $erro !== '' ? 'is-invalid' : '' ?>" type="url" id="config-<?= e($chave) ?>" name="config[<?= e($chave) ?>]" value="<?= e($valor) ?>" placeholder="https://" <?= $desabilitado ?>>
            <?php elseif (in_array($chave, $textAreas, true)): ?>
              <textarea class="form-control form-control-sm <?= $erro !== '' ? 'is-invalid' : '' ?>" id="config-<?= e($chave) ?>" name="config[<?= e($chave) ?>]" rows="4" <?= $desabilitado ?>><?= e($valor) ?></textarea>
            <?php else: ?>
              <input class="form-control form-control-sm <?= $erro !== '' ? 'is-invalid' : '' ?>" type="text" id="config-<?= e($chave) ?>" name="config[<?= e($chave) ?>]" value="<?= e($valor) ?>" <?= $desabilitado ?>>
            <?php endif; ?>

            <?php if ($erro !== ''): ?><span class="field-error"><?= e($erro) ?></span><?php endif; ?>
            <div class="form-text small">Chave: <code><?= e($chave) ?></code> · Tipo: <?= e($tipo) ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endforeach; ?>

  <?php if ($podeEditar): ?>
    <div class="d-flex justify-content-end gap-2">
      <a class="btn btn-sm btn-outline-secondary rounded-pill" href="<?= e(url('/admin/modulos/configuracoes')) ?>">Cancelar</a>
      <button class="btn btn-sm btn-warning rounded-pill" type="submit"><i class="fa-solid fa-floppy-disk me-1"></i>Salvar configurações</button>
    </div>
  <?php endif; ?>
</form>
