<?php
$tituloEvento = (string) ($evento['titulo'] ?? 'Evento');
$dataEvento = (string) ($evento['data_inicio'] ?? '');
$horaEvento = (string) ($evento['horario'] ?? '');
if ($horaEvento === '' && $dataEvento !== '') {
    $timestamp = strtotime($dataEvento);
    $horaEvento = $timestamp === false ? '' : date('H:i', $timestamp);
}
$descricaoEvento = (string) ($evento['descricao'] ?? '');
$localEvento = (string) ($evento['local'] ?? 'Igreja Matriz');
$tipoEvento = (string) ($evento['tipo'] ?? 'culto');
$fotoEvento = \App\SiteRepository::imagem((string) ($evento['foto'] ?? ''), 'assets/img/hero-igreja.svg');
?>
<article class="card card-site card-evento">
  <img class="card-image" src="<?= e($fotoEvento) ?>" alt="<?= e($tituloEvento) ?>" loading="lazy">
  <div class="card-body d-flex flex-column flex-grow-1">
    <span class="card-meta">
      <i class="fa-regular fa-calendar me-1"></i><?= e(format_date($dataEvento)) ?>
      <?php if ($horaEvento !== ''): ?>
        <span class="mx-1">·</span><i class="fa-regular fa-clock me-1"></i><?= e($horaEvento) ?>
      <?php endif; ?>
    </span>
    <h3 class="card-title h5 mt-2"><?= e($tituloEvento) ?></h3>
    <p class="text-secondary small mb-3"><?= e($descricaoEvento) ?></p>
    <div class="mt-auto d-flex flex-wrap gap-2 justify-content-between align-items-center">
      <span class="tag-pill mb-0"><?= e(str_replace('_', ' ', $tipoEvento)) ?></span>
      <span class="small text-muted"><i class="fa-solid fa-location-dot me-1"></i><?= e($localEvento) ?></span>
    </div>
  </div>
</article>
