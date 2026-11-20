<?php
$edicao = $obreiro !== null;
$acao = $edicao ? url('/admin/modulos/obreiros/' . (int) $obreiro['id']) : url('/admin/modulos/obreiros');
$bloqueado = $congregacoes === [] || $cargos === [];
$erroCampo = static function (string $campo) use ($erros): string {
    return isset($erros[$campo]) ? '<div class="invalid-feedback d-block">' . e($erros[$campo]) . '</div>' : '';
};
$selecionado = static function (mixed $valorCampo, mixed $atual): string {
    return (string) $valorCampo === (string) $atual ? 'selected' : '';
};
$valor = static function (string $campo) use ($valores): string {
    return (string) ($valores[$campo] ?? '');
};
$fotoAtual = $valor('foto');
?>
<form method="post" action="<?= e($acao) ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-lg-8">
      <?php if ($congregacoes === []): ?>
        <div class="alert alert-warning">Cadastre ao menos uma congregação antes de cadastrar um obreiro. <a class="alert-link" href="<?= e(url('/admin/modulos/estrutura/congregacao/novo')) ?>">Cadastrar congregação</a>.</div>
      <?php endif; ?>
      <?php if ($cargos === []): ?>
        <div class="alert alert-warning">Nenhum cargo ministerial ativo disponível.</div>
      <?php endif; ?>

      <div class="card-admin mb-3">
        <h2 class="h6 mb-3">Dados pessoais</h2>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label" for="codigo">Código</label>
            <input class="form-control <?= isset($erros['codigo']) ? 'is-invalid' : '' ?>" id="codigo" name="codigo" value="<?= e($valor('codigo')) ?>" maxlength="30" required>
            <?= $erroCampo('codigo') ?>
          </div>
          <div class="col-md-8">
            <label class="form-label" for="nome">Nome completo</label>
            <input class="form-control <?= isset($erros['nome']) ? 'is-invalid' : '' ?>" id="nome" name="nome" value="<?= e($valor('nome')) ?>" maxlength="180" required>
            <?= $erroCampo('nome') ?>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="membro_id">Membro vinculado</label>
            <select class="form-select <?= isset($erros['membro_id']) ? 'is-invalid' : '' ?>" id="membro_id" name="membro_id">
              <option value="">Não informado</option>
              <?php foreach ($membros as $membro): ?>
                <option value="<?= e((int) $membro['id']) ?>" <?= $selecionado($membro['id'], $valor('membro_id')) ?>><?= e((string) $membro['nome']) ?></option>
              <?php endforeach; ?>
            </select>
            <?= $erroCampo('membro_id') ?>
          </div>
          <div class="col-md-3">
            <label class="form-label" for="cpf">CPF</label>
            <input class="form-control <?= isset($erros['cpf']) ? 'is-invalid' : '' ?>" id="cpf" name="cpf" value="<?= e($valor('cpf')) ?>" maxlength="14" placeholder="000.000.000-00">
            <?= $erroCampo('cpf') ?>
          </div>
          <div class="col-md-3">
            <label class="form-label" for="rg">RG</label>
            <input class="form-control <?= isset($erros['rg']) ? 'is-invalid' : '' ?>" id="rg" name="rg" value="<?= e($valor('rg')) ?>" maxlength="30">
            <?= $erroCampo('rg') ?>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="data_nascimento">Data de nascimento</label>
            <input class="form-control <?= isset($erros['data_nascimento']) ? 'is-invalid' : '' ?>" id="data_nascimento" name="data_nascimento" type="date" value="<?= e($valor('data_nascimento')) ?>">
            <?= $erroCampo('data_nascimento') ?>
          </div>
        </div>
      </div>

      <div class="card-admin mb-3">
        <h2 class="h6 mb-3">Contato</h2>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label" for="telefone">Telefone</label>
            <input class="form-control <?= isset($erros['telefone']) ? 'is-invalid' : '' ?>" id="telefone" name="telefone" value="<?= e($valor('telefone')) ?>" maxlength="30">
            <?= $erroCampo('telefone') ?>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="whatsapp">WhatsApp</label>
            <input class="form-control <?= isset($erros['whatsapp']) ? 'is-invalid' : '' ?>" id="whatsapp" name="whatsapp" value="<?= e($valor('whatsapp')) ?>" maxlength="30">
            <?= $erroCampo('whatsapp') ?>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="email">E-mail</label>
            <input class="form-control <?= isset($erros['email']) ? 'is-invalid' : '' ?>" id="email" name="email" type="email" value="<?= e($valor('email')) ?>" maxlength="190">
            <?= $erroCampo('email') ?>
          </div>
        </div>
      </div>

      <div class="card-admin mb-3">
        <h2 class="h6 mb-3">Endereço</h2>
        <div class="row g-3">
          <div class="col-md-8">
            <label class="form-label" for="endereco">Endereço</label>
            <input class="form-control <?= isset($erros['endereco']) ? 'is-invalid' : '' ?>" id="endereco" name="endereco" value="<?= e($valor('endereco')) ?>" maxlength="200">
            <?= $erroCampo('endereco') ?>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="numero">Número</label>
            <input class="form-control <?= isset($erros['numero']) ? 'is-invalid' : '' ?>" id="numero" name="numero" value="<?= e($valor('numero')) ?>" maxlength="20">
            <?= $erroCampo('numero') ?>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="complemento">Complemento</label>
            <input class="form-control <?= isset($erros['complemento']) ? 'is-invalid' : '' ?>" id="complemento" name="complemento" value="<?= e($valor('complemento')) ?>" maxlength="100">
            <?= $erroCampo('complemento') ?>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="bairro">Bairro</label>
            <input class="form-control <?= isset($erros['bairro']) ? 'is-invalid' : '' ?>" id="bairro" name="bairro" value="<?= e($valor('bairro')) ?>" maxlength="100">
            <?= $erroCampo('bairro') ?>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="cidade">Cidade</label>
            <input class="form-control <?= isset($erros['cidade']) ? 'is-invalid' : '' ?>" id="cidade" name="cidade" value="<?= e($valor('cidade')) ?>" maxlength="100">
            <?= $erroCampo('cidade') ?>
          </div>
          <div class="col-md-3">
            <label class="form-label" for="estado">UF</label>
            <input class="form-control text-uppercase <?= isset($erros['estado']) ? 'is-invalid' : '' ?>" id="estado" name="estado" value="<?= e($valor('estado')) ?>" maxlength="2">
            <?= $erroCampo('estado') ?>
          </div>
          <div class="col-md-3">
            <label class="form-label" for="cep">CEP</label>
            <input class="form-control <?= isset($erros['cep']) ? 'is-invalid' : '' ?>" id="cep" name="cep" value="<?= e($valor('cep')) ?>" maxlength="9" placeholder="00000-000">
            <?= $erroCampo('cep') ?>
          </div>
        </div>
      </div>

      <div class="card-admin mb-3">
        <h2 class="h6 mb-3">Vínculo ministerial</h2>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label" for="cargo_id">Cargo ministerial</label>
            <select class="form-select <?= isset($erros['cargo_id']) ? 'is-invalid' : '' ?>" id="cargo_id" name="cargo_id" required <?= $cargos === [] ? 'disabled' : '' ?>>
              <option value="">Selecione...</option>
              <?php foreach ($cargos as $cargo): ?>
                <option value="<?= e((int) $cargo['id']) ?>" <?= $selecionado($cargo['id'], $valor('cargo_id')) ?>><?= e((string) $cargo['nome']) ?></option>
              <?php endforeach; ?>
            </select>
            <?= $erroCampo('cargo_id') ?>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="congregacao_id">Congregação</label>
            <select class="form-select <?= isset($erros['congregacao_id']) ? 'is-invalid' : '' ?>" id="congregacao_id" name="congregacao_id" required <?= $congregacoes === [] ? 'disabled' : '' ?>>
              <option value="">Selecione...</option>
              <?php foreach ($congregacoes as $congregacao): ?>
                <option value="<?= e((int) $congregacao['id']) ?>" <?= $selecionado($congregacao['id'], $valor('congregacao_id')) ?>><?= e((string) $congregacao['nome']) ?></option>
              <?php endforeach; ?>
            </select>
            <?= $erroCampo('congregacao_id') ?>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="igreja_origem_id">Igreja de origem</label>
            <select class="form-select <?= isset($erros['igreja_origem_id']) ? 'is-invalid' : '' ?>" id="igreja_origem_id" name="igreja_origem_id">
              <option value="">Não informada</option>
              <?php foreach ($matrizes as $matriz): ?>
                <option value="<?= e((int) $matriz['id']) ?>" <?= $selecionado($matriz['id'], $valor('igreja_origem_id')) ?>><?= e((string) $matriz['nome']) ?></option>
              <?php endforeach; ?>
            </select>
            <?= $erroCampo('igreja_origem_id') ?>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="situacao">Situação</label>
            <select class="form-select <?= isset($erros['situacao']) ? 'is-invalid' : '' ?>" id="situacao" name="situacao" required>
              <?php foreach (['ativo' => 'Ativo', 'licenciado' => 'Licenciado', 'desligado' => 'Desligado'] as $chave => $rotulo): ?>
                <option value="<?= e($chave) ?>" <?= $selecionado($chave, $valor('situacao')) ?>><?= e($rotulo) ?></option>
              <?php endforeach; ?>
            </select>
            <?= $erroCampo('situacao') ?>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="data_consagracao">Data de consagração</label>
            <input class="form-control <?= isset($erros['data_consagracao']) ? 'is-invalid' : '' ?>" id="data_consagracao" name="data_consagracao" type="date" value="<?= e($valor('data_consagracao')) ?>">
            <?= $erroCampo('data_consagracao') ?>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="registro_ministerial">Registro ministerial</label>
            <input class="form-control <?= isset($erros['registro_ministerial']) ? 'is-invalid' : '' ?>" id="registro_ministerial" name="registro_ministerial" value="<?= e($valor('registro_ministerial')) ?>" maxlength="50">
            <?= $erroCampo('registro_ministerial') ?>
          </div>
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 mb-3">
        <button class="btn btn-dark rounded-pill" type="submit" <?= $bloqueado ? 'disabled' : '' ?>><i class="fa-solid fa-floppy-disk me-1"></i>Salvar</button>
        <a class="btn btn-outline-secondary rounded-pill" href="<?= e(url('/admin/modulos/obreiros')) ?>">Voltar</a>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card-admin mb-3">
        <h2 class="h6 mb-3">Foto</h2>
        <div class="d-flex align-items-center gap-3 mb-3">
          <?php if ($fotoAtual !== ''): ?>
            <img class="admin-thumb-lg" src="<?= e(upload_url($fotoAtual)) ?>" alt="Foto do obreiro">
          <?php else: ?>
            <span class="admin-avatar admin-avatar-lg"><i class="fa-solid fa-user-tie"></i></span>
          <?php endif; ?>
          <div class="small text-muted">
            JPG, PNG, WEBP ou GIF.<br>
            Máximo <?= e((int) config('security.upload_max_mb', 5)) ?> MB.
          </div>
        </div>
        <input class="form-control <?= isset($erros['foto']) ? 'is-invalid' : '' ?>" id="foto" name="foto" type="file" accept="image/jpeg,image/png,image/webp,image/gif">
        <?= $erroCampo('foto') ?>
        <input type="hidden" name="foto" value="<?= e($fotoAtual) ?>">
        <?php if ($fotoAtual !== ''): ?>
          <div class="form-check mt-3">
            <input class="form-check-input" type="checkbox" name="remover_foto" value="1" id="remover_foto">
            <label class="form-check-label small" for="remover_foto">Remover foto atual</label>
          </div>
        <?php endif; ?>
      </div>

      <div class="card-admin mb-3">
        <h2 class="h6 mb-3">Observações</h2>
        <textarea class="form-control <?= isset($erros['observacoes']) ? 'is-invalid' : '' ?>" id="observacoes" name="observacoes" rows="5" maxlength="1000"><?= e($valor('observacoes')) ?></textarea>
        <?= $erroCampo('observacoes') ?>
      </div>

      <?php if ($edicao): ?>
        <div class="card-admin">
          <h2 class="h6 mb-3">Registro</h2>
          <dl class="row small mb-3">
            <dt class="col-6">Criado em</dt><dd class="col-6 text-end"><?= e(format_date((string) $obreiro['created_at'], 'd/m/Y H:i')) ?></dd>
            <dt class="col-6">Atualizado em</dt><dd class="col-6 text-end"><?= e(format_date((string) $obreiro['updated_at'], 'd/m/Y H:i')) ?></dd>
          </dl>
          <?php if (!empty($obreiro['deleted_at'])): ?>
            <div class="alert alert-danger small mb-3">Este obreiro está na lista de excluídos.</div>
          <?php endif; ?>
          <div class="d-grid gap-2">
            <?php if (\App\Auth::hasPermission('obreiros', 'transferir')): ?>
              <a class="btn btn-sm btn-outline-warning" href="<?= e(url('/admin/modulos/obreiros/' . (int) $obreiro['id'] . '/transferir')) ?>"><i class="fa-solid fa-arrows-rotate me-1"></i>Transferir</a>
            <?php endif; ?>
            <?php if (\App\Auth::hasPermission('obreiros', 'historico')): ?>
              <a class="btn btn-sm btn-outline-info" href="<?= e(url('/admin/modulos/obreiros/' . (int) $obreiro['id'] . '/historico')) ?>"><i class="fa-solid fa-clock-rotate-left me-1"></i>Ver histórico</a>
            <?php endif; ?>
            <?php if (\App\Auth::hasPermission('obreiros', 'imprimir')): ?>
              <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/admin/modulos/obreiros/' . (int) $obreiro['id'] . '/ficha')) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-print me-1"></i>Imprimir ficha</a>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</form>
