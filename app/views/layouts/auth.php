<!doctype html>
<html lang="pt-BR">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($titulo ?? 'Acesso') ?> | sisAssembleiaDeus</title>
  <link rel="icon" href="<?= e(asset('img/logo-assembleia.svg')) ?>" type="image/svg+xml">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
  <link href="<?= e(asset('css/admin.css')) ?>" rel="stylesheet">
</head>
<body class="auth-page">
  <?= $conteudo ?>
</body>
</html>
