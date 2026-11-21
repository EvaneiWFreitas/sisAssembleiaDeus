<?php
$rotuloTipo = static function (string $tipo): string {
    return match ($tipo) {
        'membro' => 'Membro',
        'obreiro' => 'Obreiro',
        'contato' => 'Contato',
        default => ucfirst($tipo),
    };
};
$filtrosTexto = array_filter([
    (($filtros['titular_tipo'] ?? '') !== '') ? 'Tipo: ' . $rotuloTipo((string) $filtros['titular_tipo']) : '',
    (($filtros['finalidade'] ?? '') !== '') ? 'Finalidade: ' . (string) $filtros['finalidade'] : '',
    (($filtros['consentido'] ?? '') !== '') ? ((string) $filtros['consentido'] === '1' ? 'Consentidos' : 'Não consentidos') : '',
    'Período: ' . (($filtros['de'] ?? '') !== '' ? $filtros['de'] : 'início') . ' a ' . (($filtros['ate'] ?? '') !== '' ? $filtros['ate'] : 'hoje'),
    (($filtros['q'] ?? '') !== '') ? 'Busca: ' . (string) $filtros['q'] : '',
], static fn (string $item): bool => $item !== '');
?>
<header class="print-top">
  <img src="<?= e($logotipo ?? \App\SiteRepository::logotipo()) ?>" alt="">
  <div>
    <strong>Assembleia de Deus</strong>
    <small>sisAssembleiaDeus · LGPD</small>
  </div>
</header>

<section class="diploma-paper">
  <p class="diploma-eyebrow">Assembleia de Deus · Ministério Unção do Espírito</p>
  <h1>Consentimentos e Retenção de Dados</h1>
  <p class="text-center text-muted mb-2"><?= e(implode(' · ', $filtrosTexto)) ?></p>
  <p class="text-center text-muted mb-4">Consentidos: <?= e((int) ($lgpdEstatisticas['consentidos'] ?? 0)) ?> · Recusados: <?= e((int) ($lgpdEstatisticas['recusados'] ?? 0)) ?> · Retenção de auditoria: <?= e((string) ($retencao['prazo_retencao_auditoria_anos'] ?? 5)) ?> ano(s)</p>

  <table class="table table-sm">
    <thead>
      <tr>
        <th>Data e hora</th>
        <th>Titular</th>
        <th>Tipo</th>
        <th>Finalidade</th>
        <th>Situação</th>
        <th>IP</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($registrosLgpd === []): ?>
        <tr><td colspan="6" class="text-center text-muted">Nenhum consentimento encontrado.</td></tr>
      <?php endif; ?>
      <?php foreach ($registrosLgpd as $registro): ?>
        <tr>
          <td class="text-nowrap"><?= e(format_date((string) $registro['data_consentimento'])) ?></td>
          <td><?= e((string) ($registro['titular_nome'] ?? 'Titular #' . (int) $registro['titular_id'])) ?></td>
          <td><?= e($rotuloTipo((string) $registro['titular_tipo'])) ?></td>
          <td><?= e((string) $registro['finalidade']) ?></td>
          <td><?= (int) $registro['consentido'] === 1 ? 'Consentido' : 'Não consentido' ?></td>
          <td><?= e((string) ($registro['ip'] ?? '—')) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>
