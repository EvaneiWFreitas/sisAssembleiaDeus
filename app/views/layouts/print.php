<?php
declare(strict_types=1);
?>
<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($titulo ?? 'Documento') ?> | sisAssembleiaDeus</title>
  <link rel="icon" href="<?= e(asset('img/logo-assembleia.svg')) ?>" type="image/svg+xml">
  <style>
    body { background: #f1f5f9; color: #0f172a; font-family: Arial, Helvetica, sans-serif; margin: 0; padding: 24px; }
    .print-page { background: #fff; border: 1px solid #cbd5e1; margin: 0 auto; max-width: 850px; padding: 32px; }
    .print-top { align-items: center; border-bottom: 2px solid #0b2545; display: flex; gap: 14px; padding-bottom: 16px; }
    .print-top img { height: 54px; width: 54px; }
    .print-top strong { color: #0b2545; display: block; font-size: 1.15rem; }
    .print-top small { color: #64748b; letter-spacing: .08em; text-transform: uppercase; }
    .print-title { margin: 22px 0 6px; }
    .print-title h1 { color: #0b2545; font-size: 1.4rem; margin: 0; }
    .print-title p { color: #64748b; margin: 4px 0 0; }
    .print-grid { display: grid; gap: 12px 24px; grid-template-columns: repeat(2, minmax(0, 1fr)); margin-top: 22px; }
    .print-field { border-bottom: 1px solid #e2e8f0; padding-bottom: 6px; }
    .print-field span { color: #64748b; display: block; font-size: .72rem; text-transform: uppercase; }
    .print-field strong { font-size: .95rem; }
    .print-full { grid-column: 1 / -1; }
    .print-footer { border-top: 1px solid #e2e8f0; color: #64748b; display: flex; font-size: .75rem; justify-content: space-between; margin-top: 28px; padding-top: 12px; }
    .print-actions { margin: 0 auto 16px; max-width: 850px; text-align: right; }
    .print-actions button { background: #0b2545; border: 0; border-radius: 999px; color: #fff; cursor: pointer; font-size: .85rem; padding: 9px 18px; }
    @media print {
      body { background: #fff; padding: 0; }
      .print-page { border: 0; margin: 0; max-width: none; padding: 0; }
      .print-actions { display: none; }
    }
  </style>
</head>
<body>
  <div class="print-actions"><button type="button" onclick="window.print()">Imprimir</button></div>
  <main class="print-page">
    <?= $conteudo ?>
  </main>
</body>
</html>
