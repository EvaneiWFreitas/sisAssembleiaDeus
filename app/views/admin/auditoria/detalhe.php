<?php
$base = url('/admin/modulos/auditoria');
$valorFiltro = static function (string $campo) use ($filtros): string {
    return (string) ($filtros[$campo] ?? '');
};
$query = array_filter([
    'de' => $valorFiltro('de'),
    'ate' => $valorFiltro('ate'),
    'usuario_id' => (string) ($filtros['usuario_id'] ?? ''),
    'acao' => $valorFiltro('acao'),
    'modulo' => $valorFiltro('modulo'),
    'entidade' => $valorFiltro('entidade'),
    'q' => $valorFiltro('q'),
    'limite' => (string) ($filtros['limite'] ?? ''),
], static fn (string $item): bool => $item !== '' && $item !== '0');
$queryString = $query === [] ? '' : '?' . http_build_query($query);
$metadados = [
    'Registro' => '#' . (int) $registro['id'],
    'Data e hora' => format_date((string) $registro['created_at']),
    'Usuário' => (string) ($registro['usuario_nome'] ?? 'Sistema') . ((string) ($registro['usuario_email'] ?? '') !== '' ? ' · ' . (string) $registro['usuario_email'] : ''),
    'Ação' => \App\AuditoriaRepository::rotuloAcao((string) $registro['acao']),
    'Módulo' => (string) $registro['modulo'],
    'Entidade' => (string) ($registro['entidade'] ?? '—') . (!empty($registro['entidade_id']) ? ' #' . (int) $registro['entidade_id'] : ''),
    'IP' => (string) ($registro['ip'] ?? '—'),
    'User agent' => (string) ($registro['user_agent'] ?? '—'),
];
?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <a class="btn btn-sm btn-outline-secondary rounded-pill" href="<?= e($base . $queryString) ?>"><i class="fa-solid fa-arrow-left me-1"></i>Voltar</a>
  </div>
  <div class="small text-muted">Consulta somente leitura da trilha de auditoria.</div>
</div>

<div class="card-admin mb-3">
  <h5 class="mb-3">Detalhes da ação</h5>
  <div class="row g-2">
    <?php foreach ($metadados as $rotulo => $valor): ?>
      <div class="col-12 col-md-6 col-xl-4">
        <div class="border rounded p-3 h-100">
          <div class="small text-muted"><?= e($rotulo) ?></div>
          <div class="small fw-semibold text-break"><?= e($valor) ?></div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="card-admin h-100">
      <h5 class="mb-3">Dados anteriores</h5>
      <pre class="small bg-light rounded p-3 mb-0" style="white-space:pre-wrap;word-break:break-word;max-height:420px;overflow:auto;"><?= e(\App\AuditoriaRepository::jsonSeguro((string) ($registro['dados_anteriores'] ?? ''))) ?></pre>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="card-admin h-100">
      <h5 class="mb-3">Dados novos</h5>
      <pre class="small bg-light rounded p-3 mb-0" style="white-space:pre-wrap;word-break:break-word;max-height:420px;overflow:auto;"><?= e(\App\AuditoriaRepository::jsonSeguro((string) ($registro['dados_novos'] ?? ''))) ?></pre>
    </div>
  </div>
</div>
