<?php
$nomeUsuario = 'Todos';
foreach ($usuarios as $usuario) {
    if ((int) $usuario['id'] === (int) ($filtros['usuario_id'] ?? 0)) {
        $nomeUsuario = (string) $usuario['nome'];
        break;
    }
}
$filtrosTexto = array_filter([
    'Período: ' . (($filtros['de'] ?? '') !== '' ? $filtros['de'] : 'início') . ' a ' . (($filtros['ate'] ?? '') !== '' ? $filtros['ate'] : 'hoje'),
    'Usuário: ' . $nomeUsuario,
    (($filtros['acao'] ?? '') !== '') ? 'Ação: ' . \App\AuditoriaRepository::rotuloAcao((string) $filtros['acao']) : '',
    (($filtros['modulo'] ?? '') !== '') ? 'Módulo: ' . (string) $filtros['modulo'] : '',
    (($filtros['entidade'] ?? '') !== '') ? 'Entidade: ' . (string) $filtros['entidade'] : '',
    (($filtros['q'] ?? '') !== '') ? 'Busca: ' . (string) $filtros['q'] : '',
], static fn (string $item): bool => $item !== '');
?>
<header class="print-top">
  <img src="<?= e(asset('img/logo-assembleia.svg')) ?>" alt="">
  <div>
    <strong>Assembleia de Deus</strong>
    <small>sisAssembleiaDeus · Trilha de auditoria</small>
  </div>
</header>

<section class="diploma-paper">
  <p class="diploma-eyebrow">Assembleia de Deus · Ministério Unção do Espírito</p>
  <h1>Trilha de Auditoria</h1>
  <p class="text-center text-muted mb-2"><?= e(implode(' · ', $filtrosTexto)) ?></p>
  <p class="text-center text-muted mb-4">Registros encontrados: <?= e((int) ($estatisticas['total'] ?? 0)) ?> · Ações em 7 dias: <?= e((int) ($estatisticas['ultimos_7_dias'] ?? 0)) ?></p>

  <table class="table table-sm">
    <thead>
      <tr>
        <th>Data e hora</th>
        <th>Usuário</th>
        <th>Ação</th>
        <th>Módulo</th>
        <th>Entidade</th>
        <th>IP</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($registros === []): ?>
        <tr><td colspan="6" class="text-center text-muted">Nenhum registro encontrado.</td></tr>
      <?php endif; ?>
      <?php foreach ($registros as $registro): ?>
        <tr>
          <td class="text-nowrap"><?= e(format_date((string) $registro['created_at'])) ?></td>
          <td><?= e((string) ($registro['usuario_nome'] ?? 'Sistema')) ?></td>
          <td><?= e(\App\AuditoriaRepository::rotuloAcao((string) $registro['acao'])) ?></td>
          <td><?= e((string) $registro['modulo']) ?></td>
          <td><?= e((string) ($registro['entidade'] ?? '—')) ?><?= !empty($registro['entidade_id']) ? ' #' . (int) $registro['entidade_id'] : '' ?></td>
          <td><?= e((string) ($registro['ip'] ?? '—')) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>
