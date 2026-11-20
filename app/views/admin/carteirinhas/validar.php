<?php
$base = url('/admin/modulos/carteirinhas');
$codigo = (string) ($codigo ?? '');
$resultadoValidacao = $resultado ?? null;
$classes = [
    'valida' => ['text-bg-success', 'Válida'],
    'vencida' => ['text-bg-warning', 'Vencida'],
    'inativa' => ['text-bg-danger', 'Inativa'],
];
?>
<div class="row justify-content-center">
  <div class="col-lg-8">
    <div class="card-admin mb-3">
      <ul class="nav nav-pills gap-2">
        <li class="nav-item"><a class="nav-link" href="<?= e($base) ?>"><i class="fa-solid fa-id-card me-1"></i>Carteirinhas</a></li>
        <li class="nav-item"><a class="nav-link active" href="<?= e($base . '/validar') ?>"><i class="fa-solid fa-qrcode me-1"></i>Validar</a></li>
      </ul>
    </div>

    <div class="card-admin mb-3">
      <h2 class="h6 mb-3">Validar autenticidade</h2>
      <form method="post" action="<?= e($base . '/validar') ?>" class="row g-2 align-items-end">
        <?= csrf_field() ?>
        <div class="col-12 col-md-9">
          <label class="form-label" for="codigo">Código de validação</label>
          <input class="form-control <?= isset($erros['codigo']) ? 'is-invalid' : '' ?>" id="codigo" name="codigo" value="<?= e($codigo) ?>" placeholder="CAR-2026-XXXXXX" maxlength="64" required autofocus>
          <?php if (isset($erros['codigo'])): ?>
            <div class="invalid-feedback d-block"><?= e($erros['codigo']) ?></div>
          <?php endif; ?>
          <div class="form-text">Leia o QR Code ou digite o código impresso na carteirinha.</div>
        </div>
        <div class="col-12 col-md-3">
          <button class="btn btn-dark rounded-pill w-100" type="submit"><i class="fa-solid fa-magnifying-glass me-1"></i>Validar</button>
        </div>
      </form>
    </div>

    <?php if ($resultadoValidacao !== null): ?>
      <?php $dados = $resultadoValidacao['carteirinha']; ?>
      <?php [$classe, $rotulo] = $classes[$resultadoValidacao['resultado']] ?? ['text-bg-secondary', 'Desconhecido']; ?>
      <div class="card-admin">
        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
          <div>
            <h2 class="h6 mb-1"><?= e((string) $dados['pessoa_nome']) ?></h2>
            <p class="small text-muted mb-0"><?= e((string) $dados['codigo_validacao']) ?></p>
          </div>
          <span class="badge <?= e($classe) ?>"><?= e($rotulo) ?></span>
        </div>
        <div class="alert <?= $resultadoValidacao['resultado'] === 'valida' ? 'alert-success' : 'alert-warning' ?> mb-3">
          <?= e($resultadoValidacao['mensagem']) ?>
        </div>
        <div class="row g-3">
          <div class="col-md-6"><span class="text-muted small">Tipo</span><div><?= e(\App\CarteirinhaRepository::rotuloTipo((string) $dados['tipo'])) ?></div></div>
          <div class="col-md-6"><span class="text-muted small">Congregação</span><div><?= e((string) ($dados['congregacao_nome'] ?? '—')) ?></div></div>
          <div class="col-md-6"><span class="text-muted small">Emissão</span><div><?= e(format_date((string) $dados['data_emissao'], 'd/m/Y H:i')) ?></div></div>
          <div class="col-md-6"><span class="text-muted small">Validade</span><div><?= e((string) ($dados['validade'] ?? '') !== '' ? format_date((string) $dados['validade']) : 'Indeterminada') ?></div></div>
          <div class="col-md-6"><span class="text-muted small">Via</span><div><?= (int) $dados['segunda_via'] === 1 ? 'Segunda via' : 'Primeira via' ?></div></div>
          <div class="col-md-6"><span class="text-muted small">Emitida por</span><div><?= e((string) ($dados['emitida_por_nome'] ?? '—')) ?></div></div>
        </div>
      </div>
    <?php elseif ($codigo !== ''): ?>
      <div class="alert alert-danger">Nenhuma carteirinha encontrada para o código informado.</div>
    <?php endif; ?>
  </div>
</div>
