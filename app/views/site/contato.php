<section class="page-hero">
  <div class="container">
    <div class="breadcrumb-site"><a href="<?= e(url('/')) ?>">Início</a> / Contato</div>
    <h1>Fale conosco</h1>
    <p>Tem alguma dúvida, pedido de oração ou deseja saber mais sobre a igreja? Escreva para nós.</p>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-7">
        <div class="card-site contact-card">
          <h2 class="h4 mb-1">Envie sua mensagem</h2>
          <p class="text-secondary small mb-4">Responderemos assim que possível.</p>

          <?php if ($sucesso !== ''): ?>
            <div class="alert-success-site mb-4"><i class="fa-solid fa-circle-check me-2"></i><?= e($sucesso) ?></div>
          <?php endif; ?>
          <?php if ($erroEnvio !== ''): ?>
            <div class="alert-error-site mb-4"><i class="fa-solid fa-circle-exclamation me-2"></i><?= e($erroEnvio) ?></div>
          <?php endif; ?>

          <form method="post" action="<?= e(url('/contato')) ?>" novalidate>
            <?= csrf_field() ?>
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label" for="nome">Nome *</label>
                <input class="form-control <?= isset($erros['nome']) ? 'is-invalid' : '' ?>" id="nome" name="nome" type="text" maxlength="150" required value="<?= e($valores['nome'] ?? '') ?>" placeholder="Seu nome completo">
                <?php if (isset($erros['nome'])): ?><span class="field-error"><?= e($erros['nome']) ?></span><?php endif; ?>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="email">E-mail *</label>
                <input class="form-control <?= isset($erros['email']) ? 'is-invalid' : '' ?>" id="email" name="email" type="email" maxlength="190" required value="<?= e($valores['email'] ?? '') ?>" placeholder="voce@email.com">
                <?php if (isset($erros['email'])): ?><span class="field-error"><?= e($erros['email']) ?></span><?php endif; ?>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="telefone">Telefone</label>
                <input class="form-control <?= isset($erros['telefone']) ? 'is-invalid' : '' ?>" id="telefone" name="telefone" type="tel" maxlength="30" value="<?= e($valores['telefone'] ?? '') ?>" placeholder="(00) 00000-0000">
                <?php if (isset($erros['telefone'])): ?><span class="field-error"><?= e($erros['telefone']) ?></span><?php endif; ?>
              </div>
              <div class="col-md-6">
                <label class="form-label" for="assunto">Assunto</label>
                <input class="form-control <?= isset($erros['assunto']) ? 'is-invalid' : '' ?>" id="assunto" name="assunto" type="text" maxlength="180" value="<?= e($valores['assunto'] ?? '') ?>" placeholder="Assunto da mensagem">
                <?php if (isset($erros['assunto'])): ?><span class="field-error"><?= e($erros['assunto']) ?></span><?php endif; ?>
              </div>
              <div class="col-12">
                <label class="form-label" for="mensagem">Mensagem *</label>
                <textarea class="form-control <?= isset($erros['mensagem']) ? 'is-invalid' : '' ?>" id="mensagem" name="mensagem" rows="6" maxlength="2000" required placeholder="Escreva sua mensagem"><?= e($valores['mensagem'] ?? '') ?></textarea>
                <?php if (isset($erros['mensagem'])): ?><span class="field-error"><?= e($erros['mensagem']) ?></span><?php endif; ?>
              </div>
            </div>
            <button class="btn btn-site mt-4" type="submit"><i class="fa-solid fa-paper-plane me-2"></i>Enviar mensagem</button>
          </form>
        </div>
      </div>

      <div class="col-lg-5">
        <div class="card-site mb-3">
          <span class="icon-box"><i class="fa-solid fa-location-dot"></i></span>
          <h2 class="h6">Endereço</h2>
          <p class="text-secondary small mb-0"><?= e($endereco) ?></p>
        </div>
        <div class="card-site mb-3">
          <span class="icon-box"><i class="fa-solid fa-phone"></i></span>
          <h2 class="h6">Telefone e WhatsApp</h2>
          <?php if ($telefone !== ''): ?>
            <p class="text-secondary small mb-1"><a href="tel:<?= e(preg_replace('/\D/', '', $telefone)) ?>"><?= e($telefone) ?></a></p>
          <?php endif; ?>
          <?php if ($whatsappLink !== ''): ?>
            <a class="small fw-semibold text-decoration-none" href="<?= e($whatsappLink) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-brands fa-whatsapp me-1"></i>Abrir WhatsApp</a>
          <?php else: ?>
            <p class="text-secondary small mb-0">Informações a serem configuradas.</p>
          <?php endif; ?>
        </div>
        <div class="card-site">
          <span class="icon-box"><i class="fa-solid fa-envelope"></i></span>
          <h2 class="h6">E-mail</h2>
          <?php if ($email !== ''): ?>
            <p class="text-secondary small mb-0"><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a></p>
          <?php else: ?>
            <p class="text-secondary small mb-0">Informações a serem configuradas.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>
