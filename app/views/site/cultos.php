<?php
$diasSemana = [
    'domingo' => 'Domingo',
    'segunda' => 'Segunda-feira',
    'terca' => 'Terça-feira',
    'quarta' => 'Quarta-feira',
    'quinta' => 'Quinta-feira',
    'sexta' => 'Sexta-feira',
    'sabado' => 'Sábado',
];
?>
<section class="page-hero">
  <div class="container">
    <div class="breadcrumb-site"><a href="<?= e(url('/')) ?>">Início</a> / Cultos</div>
    <h1>Programação de cultos</h1>
    <p>Nossos encontros são momentos de louvor, oração, ensino bíblico e comunhão. Todos são bem-vindos.</p>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-8">
        <h2 class="section-title">Horários</h2>
        <?php foreach ($cultos as $culto): ?>
          <div class="schedule-row">
            <span class="schedule-day"><?= e($diasSemana[(string) ($culto['dia_semana'] ?? '')] ?? ucfirst((string) ($culto['dia_semana'] ?? ''))) ?></span>
            <span class="schedule-time"><?= e(substr((string) ($culto['horario'] ?? ''), 0, 5)) ?></span>
            <div>
              <strong class="d-block"><?= e((string) ($culto['tipo'] ?? 'Culto')) ?></strong>
              <small class="text-muted d-block"><?= e((string) ($culto['descricao'] ?? '')) ?></small>
              <?php if (($culto['congregacao'] ?? '') !== ''): ?>
                <small class="text-muted"><i class="fa-solid fa-location-dot ms-1 me-1"></i><?= e((string) $culto['congregacao']) ?></small>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="col-lg-4">
        <div class="card-site">
          <span class="icon-box"><i class="fa-solid fa-location-dot"></i></span>
          <h2 class="h5">Onde estamos</h2>
          <p class="text-secondary small"><?= e($endereco) ?></p>
          <?php if ($telefone !== ''): ?>
            <p class="small mb-2"><i class="fa-solid fa-phone me-2"></i><?= e($telefone) ?></p>
          <?php endif; ?>
          <?php if ($whatsappLink !== ''): ?>
            <a class="btn btn-site btn-sm mt-2" href="<?= e($whatsappLink) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-whatsapp me-2"></i>Chamar no WhatsApp</a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section section-alt text-center">
  <div class="container">
    <h2 class="section-title">Venha nos visitar</h2>
    <p class="section-lead">Se esta é a sua primeira visita, será um prazer recebê-lo. Vá até a recepção após o culto.</p>
    <a class="btn btn-site" href="<?= e(url('/contato')) ?>">Perguntar sobre a igreja</a>
  </div>
</section>
