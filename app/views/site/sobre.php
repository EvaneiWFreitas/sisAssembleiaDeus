<section class="page-hero">
  <div class="container">
    <div class="breadcrumb-site"><a href="<?= e(url('/')) ?>">Início</a> / Sobre</div>
    <h1>Sobre a igreja</h1>
    <p>Conheça um pouco da nossa identidade, da nossa fé e do compromisso que nos move como comunidade.</p>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-4">
        <div class="card-site h-100">
          <span class="icon-box"><i class="fa-solid fa-bullseye"></i></span>
          <h2 class="h5">Missão</h2>
          <p class="text-secondary mb-0">Anunciar o evangelho de Cristo, discipular pessoas e formar uma comunidade que vive a Palavra de Deus.</p>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="card-site h-100">
          <span class="icon-box"><i class="fa-solid fa-eye"></i></span>
          <h2 class="h5">Visão</h2>
          <p class="text-secondary mb-0">Ser uma igreja fiel, acolhedora e missionária, comprometida com o crescimento espiritual de cada pessoa.</p>
        </div>
      </div>
      <div class="col-lg-4">
        <div class="card-site h-100">
          <span class="icon-box"><i class="fa-solid fa-shield-heart"></i></span>
          <h2 class="h5">Valores</h2>
          <p class="text-secondary mb-0">Palavra, oração, comunhão, serviço, integridade e zelo pela missão confiada à igreja.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section section-alt">
  <div class="container">
    <div class="row g-5 align-items-center">
      <div class="col-lg-6">
        <span class="hero-eyebrow text-warning">Nossa história</span>
        <h2 class="section-title mt-2">Uma família construída pela fé</h2>
        <p class="text-secondary">A história desta igreja é feita de pessoas que responderam ao chamado de Deus para viverem juntas, servirem com alegria e testemunharem o amor de Cristo.</p>
        <p class="text-secondary mb-0">Ao longo dos anos, a comunidade tem se dedicado ao ensino bíblico, à oração, ao cuidado com as famílias e ao apoio missionário, mantendo o compromisso de ser luz onde está.</p>
      </div>
      <div class="col-lg-6">
        <div class="card-site">
          <div class="d-flex gap-3 mb-3">
            <span class="icon-box mb-0"><i class="fa-solid fa-book-bible"></i></span>
            <div>
              <h3 class="h6 mb-1">Ensino bíblico</h3>
              <p class="text-secondary small mb-0">Estudo expositivo e aplicação prática da Palavra.</p>
            </div>
          </div>
          <div class="d-flex gap-3 mb-3">
            <span class="icon-box mb-0"><i class="fa-solid fa-place-of-worship"></i></span>
            <div>
              <h3 class="h6 mb-1">Vida comunitária</h3>
              <p class="text-secondary small mb-0">Comunhão sincera entre irmãos de todas as idades.</p>
            </div>
          </div>
          <div class="d-flex gap-3">
            <span class="icon-box mb-0"><i class="fa-solid fa-hand-holding-heart"></i></span>
            <div>
              <h3 class="h6 mb-1">Serviço e missão</h3>
              <p class="text-secondary small mb-0">Ação social e apoio à obra missionária local e global.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container text-center">
    <span class="hero-eyebrow text-warning">Liderança</span>
    <h2 class="section-title">Pastor presidente</h2>
    <p class="section-lead">Servidor de Deus, comprometido com a Palavra, a oração e o cuidado da família da igreja.</p>
    <div class="card-site mx-auto" style="max-width: 420px;">
      <span class="icon-box mx-auto"><i class="fa-solid fa-user-tie"></i></span>
      <h3 class="h5 mb-1"><?= e($pastorPresidente !== '' ? $pastorPresidente : 'Pastor Presidente') ?></h3>
      <p class="text-secondary small mb-0"><?= e($nomeIgreja) ?></p>
    </div>
  </div>
</section>
