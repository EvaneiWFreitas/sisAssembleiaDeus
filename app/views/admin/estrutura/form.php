<?php
$edicao = $item !== null;
$acao = $edicao
    ? url('/admin/modulos/estrutura/' . $tipo . '/' . (int) $item['id'])
    : url('/admin/modulos/estrutura/' . $tipo);
$bloqueado = ($tipo === 'congregacao' && $matrizes === []) || ($tipo === 'ponto' && $congregacoes === []);
$erroCampo = static function (string $campo) use ($erros): string {
    return isset($erros[$campo]) ? '<div class="invalid-feedback d-block">' . e($erros[$campo]) . '</div>' : '';
};
$selecionado = static function (mixed $valor, mixed $atual): string {
    return (string) $valor === (string) $atual ? 'selected' : '';
};
$valor = static function (string $campo) use ($valores): string {
    return (string) ($valores[$campo] ?? '');
};
?>
<form method="post" action="<?= e($acao) ?>">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-lg-8">
      <?php if ($tipo === 'congregacao' && $matrizes === []): ?>
        <div class="alert alert-warning">Cadastre ao menos uma matriz antes de criar uma congregação. <a class="alert-link" href="<?= e(url('/admin/modulos/estrutura/matriz/novo')) ?>">Cadastrar matriz</a>.</div>
      <?php endif; ?>
      <?php if ($tipo === 'ponto' && $congregacoes === []): ?>
        <div class="alert alert-warning">Cadastre ao menos uma congregação antes de criar um ponto de pregação. <a class="alert-link" href="<?= e(url('/admin/modulos/estrutura/congregacao/novo')) ?>">Cadastrar congregação</a>.</div>
      <?php endif; ?>

      <div class="card-admin mb-3">
        <h2 class="h6 mb-3">Dados principais</h2>
        <div class="row g-3">
          <?php if ($tipo === 'matriz'): ?>
            <div class="col-md-8">
              <label class="form-label" for="nome">Nome da matriz</label>
              <input class="form-control <?= isset($erros['nome']) ? 'is-invalid' : '' ?>" id="nome" name="nome" value="<?= e($valor('nome')) ?>" maxlength="150" required>
              <?= $erroCampo('nome') ?>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="cnpj">CNPJ</label>
              <input class="form-control <?= isset($erros['cnpj']) ? 'is-invalid' : '' ?>" id="cnpj" name="cnpj" value="<?= e($valor('cnpj')) ?>" maxlength="18" placeholder="00.000.000/0000-00">
              <?= $erroCampo('cnpj') ?>
            </div>
            <div class="col-12">
              <label class="form-label" for="logotipo">Logotipo (URL)</label>
              <input class="form-control <?= isset($erros['logotipo']) ? 'is-invalid' : '' ?>" id="logotipo" name="logotipo" value="<?= e($valor('logotipo')) ?>" maxlength="255" placeholder="https://...">
              <?= $erroCampo('logotipo') ?>
            </div>
          <?php elseif ($tipo === 'congregacao'): ?>
            <div class="col-md-4">
              <label class="form-label" for="codigo">Código</label>
              <input class="form-control <?= isset($erros['codigo']) ? 'is-invalid' : '' ?>" id="codigo" name="codigo" value="<?= e($valor('codigo')) ?>" maxlength="30" required>
              <?= $erroCampo('codigo') ?>
            </div>
            <div class="col-md-8">
              <label class="form-label" for="nome">Nome da congregação</label>
              <input class="form-control <?= isset($erros['nome']) ? 'is-invalid' : '' ?>" id="nome" name="nome" value="<?= e($valor('nome')) ?>" maxlength="150" required>
              <?= $erroCampo('nome') ?>
            </div>
            <div class="col-md-12">
              <label class="form-label" for="igreja_matriz_id">Matriz</label>
              <select class="form-select <?= isset($erros['igreja_matriz_id']) ? 'is-invalid' : '' ?>" id="igreja_matriz_id" name="igreja_matriz_id" required <?= $matrizes === [] ? 'disabled' : '' ?>>
                <option value="">Selecione...</option>
                <?php foreach ($matrizes as $matriz): ?>
                  <option value="<?= e((int) $matriz['id']) ?>" <?= $selecionado($matriz['id'], $valor('igreja_matriz_id')) ?>><?= e((string) $matriz['nome']) ?></option>
                <?php endforeach; ?>
              </select>
              <?= $erroCampo('igreja_matriz_id') ?>
            </div>
          <?php else: ?>
            <div class="col-md-12">
              <label class="form-label" for="congregacao_id">Congregação</label>
              <select class="form-select <?= isset($erros['congregacao_id']) ? 'is-invalid' : '' ?>" id="congregacao_id" name="congregacao_id" required <?= $congregacoes === [] ? 'disabled' : '' ?>>
                <option value="">Selecione...</option>
                <?php foreach ($congregacoes as $congregacao): ?>
                  <option value="<?= e((int) $congregacao['id']) ?>" <?= $selecionado($congregacao['id'], $valor('congregacao_id')) ?>><?= e((string) $congregacao['nome']) ?></option>
                <?php endforeach; ?>
              </select>
              <?= $erroCampo('congregacao_id') ?>
            </div>
            <div class="col-md-7">
              <label class="form-label" for="identificacao">Identificação</label>
              <input class="form-control <?= isset($erros['identificacao']) ? 'is-invalid' : '' ?>" id="identificacao" name="identificacao" value="<?= e($valor('identificacao')) ?>" maxlength="150" required>
              <?= $erroCampo('identificacao') ?>
            </div>
            <div class="col-md-5">
              <label class="form-label" for="responsavel">Responsável</label>
              <input class="form-control <?= isset($erros['responsavel']) ? 'is-invalid' : '' ?>" id="responsavel" name="responsavel" value="<?= e($valor('responsavel')) ?>" maxlength="150">
              <?= $erroCampo('responsavel') ?>
            </div>
            <div class="col-12">
              <label class="form-label" for="dias_horarios">Dias e horários</label>
              <input class="form-control <?= isset($erros['dias_horarios']) ? 'is-invalid' : '' ?>" id="dias_horarios" name="dias_horarios" value="<?= e($valor('dias_horarios')) ?>" maxlength="255" placeholder="Ex.: Domingos às 19h, quartas às 20h">
              <?= $erroCampo('dias_horarios') ?>
            </div>
          <?php endif; ?>
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
          <?php if ($tipo !== 'ponto'): ?>
            <div class="col-md-6">
              <label class="form-label" for="complemento">Complemento</label>
              <input class="form-control <?= isset($erros['complemento']) ? 'is-invalid' : '' ?>" id="complemento" name="complemento" value="<?= e($valor('complemento')) ?>" maxlength="100">
              <?= $erroCampo('complemento') ?>
            </div>
          <?php endif; ?>
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

      <?php if ($tipo !== 'ponto'): ?>
        <div class="card-admin mb-3">
          <h2 class="h6 mb-3">Contato</h2>
          <div class="row g-3">
            <div class="col-md-5">
              <label class="form-label" for="telefone">Telefone</label>
              <input class="form-control <?= isset($erros['telefone']) ? 'is-invalid' : '' ?>" id="telefone" name="telefone" value="<?= e($valor('telefone')) ?>" maxlength="30">
              <?= $erroCampo('telefone') ?>
            </div>
            <div class="col-md-7">
              <label class="form-label" for="email">E-mail</label>
              <input class="form-control <?= isset($erros['email']) ? 'is-invalid' : '' ?>" id="email" name="email" type="email" value="<?= e($valor('email')) ?>" maxlength="190">
              <?= $erroCampo('email') ?>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <div class="d-flex flex-wrap gap-2 mb-3">
        <button class="btn btn-dark rounded-pill" type="submit" <?= $bloqueado ? 'disabled' : '' ?>><i class="fa-solid fa-floppy-disk me-1"></i>Salvar</button>
        <a class="btn btn-outline-secondary rounded-pill" href="<?= e(url('/admin/modulos/estrutura')) ?>">Voltar</a>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card-admin mb-3">
        <h2 class="h6 mb-3">Informações ministeriais</h2>
        <div class="row g-3">
          <?php if ($tipo !== 'ponto'): ?>
            <div class="col-12">
              <label class="form-label" for="pastor_responsavel_id">Pastor responsável</label>
              <select class="form-select <?= isset($erros['pastor_responsavel_id']) ? 'is-invalid' : '' ?>" id="pastor_responsavel_id" name="pastor_responsavel_id">
                <option value="">Não informado</option>
                <?php foreach ($obreiros as $obreiro): ?>
                  <option value="<?= e((int) $obreiro['id']) ?>" <?= $selecionado($obreiro['id'], $valor('pastor_responsavel_id')) ?>><?= e((string) $obreiro['nome']) ?></option>
                <?php endforeach; ?>
              </select>
              <?= $erroCampo('pastor_responsavel_id') ?>
              <?php if ($obreiros === []): ?><div class="form-text">Nenhum obreiro ativo cadastrado.</div><?php endif; ?>
            </div>
            <div class="col-12">
              <label class="form-label" for="<?= $tipo === 'matriz' ? 'data_fundacao' : 'data_abertura' ?>"><?= $tipo === 'matriz' ? 'Data de fundação' : 'Data de abertura' ?></label>
              <input class="form-control <?= isset($erros[$tipo === 'matriz' ? 'data_fundacao' : 'data_abertura']) ? 'is-invalid' : '' ?>" id="<?= $tipo === 'matriz' ? 'data_fundacao' : 'data_abertura' ?>" name="<?= $tipo === 'matriz' ? 'data_fundacao' : 'data_abertura' ?>" type="date" value="<?= e($valor($tipo === 'matriz' ? 'data_fundacao' : 'data_abertura')) ?>">
              <?= $erroCampo($tipo === 'matriz' ? 'data_fundacao' : 'data_abertura') ?>
            </div>
          <?php else: ?>
            <div class="col-12">
              <label class="form-label" for="data_abertura">Data de abertura</label>
              <input class="form-control <?= isset($erros['data_abertura']) ? 'is-invalid' : '' ?>" id="data_abertura" name="data_abertura" type="date" value="<?= e($valor('data_abertura')) ?>">
              <?= $erroCampo('data_abertura') ?>
            </div>
          <?php endif; ?>
          <div class="col-12">
            <label class="form-label" for="situacao">Situação</label>
            <select class="form-select <?= isset($erros['situacao']) ? 'is-invalid' : '' ?>" id="situacao" name="situacao">
              <option value="ativa" <?= $selecionado('ativa', $valor('situacao')) ?>>Ativa</option>
              <option value="inativa" <?= $selecionado('inativa', $valor('situacao')) ?>>Inativa</option>
            </select>
            <?= $erroCampo('situacao') ?>
          </div>
        </div>
      </div>

      <div class="card-admin">
        <h2 class="h6 mb-3">Observações</h2>
        <textarea class="form-control <?= isset($erros['observacoes']) ? 'is-invalid' : '' ?>" id="observacoes" name="observacoes" rows="5" maxlength="1000"><?= e($valor('observacoes')) ?></textarea>
        <?= $erroCampo('observacoes') ?>

        <?php if ($edicao): ?>
          <hr>
          <dl class="row small mb-0">
            <dt class="col-6">Criado em</dt><dd class="col-6 text-end"><?= e(format_date((string) $item['created_at'], 'd/m/Y H:i')) ?></dd>
            <dt class="col-6">Atualizado em</dt><dd class="col-6 text-end"><?= e(format_date((string) $item['updated_at'], 'd/m/Y H:i')) ?></dd>
          </dl>
        <?php endif; ?>
      </div>
    </div>
  </div>
</form>
