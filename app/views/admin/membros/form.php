<?php
$edicao = $membro !== null;
$acao = $edicao ? url('/admin/modulos/membros/' . (int) $membro['id']) : url('/admin/modulos/membros');
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
          <div class="col-md-4">
            <label class="form-label" for="cpf">CPF</label>
            <input class="form-control <?= isset($erros['cpf']) ? 'is-invalid' : '' ?>" id="cpf" name="cpf" value="<?= e($valor('cpf')) ?>" maxlength="14" placeholder="000.000.000-00">
            <?= $erroCampo('cpf') ?>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="rg">RG</label>
            <input class="form-control <?= isset($erros['rg']) ? 'is-invalid' : '' ?>" id="rg" name="rg" value="<?= e($valor('rg')) ?>" maxlength="30">
            <?= $erroCampo('rg') ?>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="data_nascimento">Data de nascimento</label>
            <input class="form-control <?= isset($erros['data_nascimento']) ? 'is-invalid' : '' ?>" id="data_nascimento" name="data_nascimento" type="date" value="<?= e($valor('data_nascimento')) ?>">
            <?= $erroCampo('data_nascimento') ?>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="sexo">Sexo</label>
            <select class="form-select <?= isset($erros['sexo']) ? 'is-invalid' : '' ?>" id="sexo" name="sexo" required>
              <?php foreach (['masculino' => 'Masculino', 'feminino' => 'Feminino', 'outro' => 'Outro', 'nao_informado' => 'Não informado'] as $chave => $rotulo): ?>
                <option value="<?= e($chave) ?>" <?= $selecionado($chave, $valor('sexo')) ?>><?= e($rotulo) ?></option>
              <?php endforeach; ?>
            </select>
            <?= $erroCampo('sexo') ?>
          </div>
          <div class="col-md-6">
            <label class="form-label" for="estado_civil">Estado civil</label>
            <select class="form-select <?= isset($erros['estado_civil']) ? 'is-invalid' : '' ?>" id="estado_civil" name="estado_civil" required>
              <?php foreach (['solteiro' => 'Solteiro', 'casado' => 'Casado', 'divorciado' => 'Divorciado', 'viuvo' => 'Viuvo', 'outro' => 'Outro', 'nao_informado' => 'Não informado'] as $chave => $rotulo): ?>
                <option value="<?= e($chave) ?>" <?= $selecionado($chave, $valor('estado_civil')) ?>><?= e($rotulo) ?></option>
              <?php endforeach; ?>
            </select>
            <?= $erroCampo('estado_civil') ?>
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
        <h2 class="h6 mb-3">Vínculo com a igreja</h2>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label" for="data_ingresso">Data de ingresso</label>
            <input class="form-control <?= isset($erros['data_ingresso']) ? 'is-invalid' : '' ?>" id="data_ingresso" name="data_ingresso" type="date" value="<?= e($valor('data_ingresso')) ?>">
            <?= $erroCampo('data_ingresso') ?>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="data_batismo">Data de batismo</label>
            <input class="form-control <?= isset($erros['data_batismo']) ? 'is-invalid' : '' ?>" id="data_batismo" name="data_batismo" type="date" value="<?= e($valor('data_batismo')) ?>">
            <?= $erroCampo('data_batismo') ?>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="situacao">Situação</label>
            <select class="form-select <?= isset($erros['situacao']) ? 'is-invalid' : '' ?>" id="situacao" name="situacao" required>
              <?php foreach (['ativo' => 'Ativo', 'inativo' => 'Inativo', 'transferido' => 'Transferido', 'falecido' => 'Falecido'] as $chave => $rotulo): ?>
                <option value="<?= e($chave) ?>" <?= $selecionado($chave, $valor('situacao')) ?>><?= e($rotulo) ?></option>
              <?php endforeach; ?>
            </select>
            <?= $erroCampo('situacao') ?>
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
            <label class="form-label" for="congregacao_id">Congregação</label>
            <select class="form-select <?= isset($erros['congregacao_id']) ? 'is-invalid' : '' ?>" id="congregacao_id" name="congregacao_id">
              <option value="">Não informada</option>
              <?php foreach ($congregacoes as $congregacao): ?>
                <option value="<?= e((int) $congregacao['id']) ?>" <?= $selecionado($congregacao['id'], $valor('congregacao_id')) ?>><?= e((string) $congregacao['nome']) ?></option>
              <?php endforeach; ?>
            </select>
            <?= $erroCampo('congregacao_id') ?>
          </div>
        </div>
      </div>

      <div class="d-flex flex-wrap gap-2 mb-3">
        <button class="btn btn-dark rounded-pill" type="submit"><i class="fa-solid fa-floppy-disk me-1"></i>Salvar</button>
        <a class="btn btn-outline-secondary rounded-pill" href="<?= e(url('/admin/modulos/membros')) ?>">Voltar</a>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card-admin mb-3">
        <h2 class="h6 mb-3">Foto</h2>
        <div class="d-flex align-items-center gap-3 mb-3">
          <?php if ($fotoAtual !== ''): ?>
            <img class="admin-thumb-lg" src="<?= e(upload_url($fotoAtual)) ?>" alt="Foto do membro">
          <?php else: ?>
            <span class="admin-avatar admin-avatar-lg"><i class="fa-solid fa-user"></i></span>
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
            <dt class="col-6">Criado em</dt><dd class="col-6 text-end"><?= e(format_date((string) $membro['created_at'], 'd/m/Y H:i')) ?></dd>
            <dt class="col-6">Atualizado em</dt><dd class="col-6 text-end"><?= e(format_date((string) $membro['updated_at'], 'd/m/Y H:i')) ?></dd>
          </dl>
          <?php if (!empty($membro['deleted_at'])): ?>
            <div class="alert alert-danger small mb-0">Este membro está na lista de excluídos.</div>
          <?php endif; ?>
          <div class="d-grid gap-2">
            <?php if (\App\Auth::hasPermission('membros', 'historico')): ?>
              <a class="btn btn-sm btn-outline-info" href="<?= e(url('/admin/modulos/membros/' . (int) $membro['id'] . '/historico')) ?>"><i class="fa-solid fa-clock-rotate-left me-1"></i>Ver histórico</a>
            <?php endif; ?>
            <?php if (\App\Auth::hasPermission('membros', 'imprimir')): ?>
              <a class="btn btn-sm btn-outline-primary" href="<?= e(url('/admin/modulos/membros/' . (int) $membro['id'] . '/ficha')) ?>" target="_blank" rel="noopener noreferrer"><i class="fa-solid fa-print me-1"></i>Imprimir ficha</a>
            <?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</form>
