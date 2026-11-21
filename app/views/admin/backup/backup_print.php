<?php
$formatarBytesPrint = static function (mixed $bytes): string {
    $bytes = (float) $bytes;
    if ($bytes <= 0) {
        return '0 B';
    }
    $unidades = ['B', 'KB', 'MB', 'GB'];
    $indice = 0;
    while ($bytes >= 1024 && $indice < count($unidades) - 1) {
        $bytes /= 1024;
        $indice++;
    }
    return number_format($bytes, $indice === 0 ? 0 : 1, ',', '.') . ' ' . $unidades[$indice];
};
$rotuloStatusPrint = static function (string $status): string {
    return match ($status) {
        'concluido' => 'Concluído',
        'falhado' => 'Falhado',
        'gerando' => 'Gerando',
        default => ucfirst($status),
    };
};
$filtrosTexto = array_filter([
    (($backupFiltros['de'] ?? '') !== '') ? 'De: ' . (string) $backupFiltros['de'] : '',
    (($backupFiltros['ate'] ?? '') !== '') ? 'Até: ' . (string) $backupFiltros['ate'] : '',
    (($backupFiltros['status'] ?? '') !== '') ? 'Situação: ' . $rotuloStatusPrint((string) $backupFiltros['status']) : '',
    (($backupFiltros['q'] ?? '') !== '') ? 'Busca: ' . (string) $backupFiltros['q'] : '',
], static fn (string $item): bool => $item !== '');
?>
<header class="print-top">
  <img src="<?= e($logotipo ?? \App\SiteRepository::logotipo()) ?>" alt="">
  <div>
    <strong>Assembleia de Deus</strong>
    <small>sisAssembleiaDeus · Backup</small>
  </div>
</header>

<section class="diploma-paper">
  <p class="diploma-eyebrow">Assembleia de Deus · Ministério Unção do Espírito</p>
  <h1>Histórico de Cópias de Segurança</h1>
  <p class="text-center text-muted mb-2"><?= e(implode(' · ', $filtrosTexto)) ?></p>
  <p class="text-center text-muted mb-4">Backups: <?= e((int) ($backupEstatisticas['total'] ?? 0)) ?> · Concluídos: <?= e((int) ($backupEstatisticas['concluidos'] ?? 0)) ?> · Espaço utilizado: <?= e($formatarBytesPrint($backupEstatisticas['bytes'] ?? 0)) ?></p>

  <table class="table table-sm">
    <thead>
      <tr>
        <th>Arquivo</th>
        <th>Gerado em</th>
        <th>Gerado por</th>
        <th>Tabelas</th>
        <th>Tamanho</th>
        <th>Situação</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($registrosBackup === []): ?>
        <tr><td colspan="6" class="text-center text-muted">Nenhum backup encontrado.</td></tr>
      <?php endif; ?>
      <?php foreach ($registrosBackup as $registro): ?>
        <tr>
          <td class="small text-break"><?= e((string) $registro['nome_arquivo']) ?></td>
          <td class="text-nowrap small"><?= e(format_date((string) $registro['gerado_em'], 'd/m/Y H:i')) ?></td>
          <td class="small"><?= e((string) ($registro['usuario_nome'] ?? '—')) ?></td>
          <td class="small"><?= e(number_format((int) $registro['tabelas'], 0, ',', '.')) ?></td>
          <td class="small"><?= e($formatarBytesPrint($registro['tamanho_bytes'])) ?></td>
          <td class="small"><?= e($rotuloStatusPrint((string) $registro['status'])) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</section>
