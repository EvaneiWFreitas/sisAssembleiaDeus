<?php
$edicao = $registro !== null;
$acao = $edicao ? url('/admin/modulos/financeiro/' . (int) $registro['id']) : url('/admin/modulos/financeiro');
$valor = static function (string $campo) use ($valores): string {
    return (string) ($valores[$campo] ?? '');
};
$erroCampo = static function (string $campo) use ($erros): string {
    return isset($erros[$campo]) ? '<div class="invalid-feedback d-block">' . e($erros[$campo]) . '</div>' : '';
};
$selecionado = static function (mixed $atual, mixed $esperado): string {
    return (string) $atual === (string) $esperado ? 'selected' : '';
};
$fechado = $edicao && (int) $registro['fechado'] === 1;
$origem = $valor('origem') !== '' ? $valor('origem') : 'geral';
$tipo = $valor('tipo') !== '' ? $valor('tipo') : 'entrada';
$rotulosForma = [
    'dinheiro' => 'Dinheiro', 'pix' => 'Pix', 'boleto' => 'Boleto', 'cartao' => 'Cartão',
    'transferencia' => 'Transferência', 'cheque' => 'Cheque', 'outro' => 'Outro',
];
$rotulosOrigem = ['geral' => 'Geral', 'dizimo' => 'Dízimo', 'oferta' => 'Oferta'];
?>
<?php if ($fechado): ?>
  <div class="alert alert-warning">Esta movimentação pertence a uma competência fechada e não pode mais ser alterada.</div>
<?php endif; ?>
<form method="post" action="<?= e($acao) ?>" id="form-financeiro">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card-admin mb-3">
        <h2 class="h6 mb-3">Lançamento</h2>
        <div class="row g-3">
          <div class="col-md-4">
            <label class="form-label" for="tipo">Tipo</label>
            <select class="form-select <?= isset($erros['tipo']) ? 'is-invalid' : '' ?>" id="tipo" name="tipo" required>
              <option value="entrada" <?= $selecionado($tipo, 'entrada') ?>>Entrada</option>
              <option value="saida" <?= $selecionado($tipo, 'saida') ?>>Saída</option>
            </select>
            <?= $erroCampo('tipo') ?>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="origem">Origem</label>
            <select class="form-select <?= isset($erros['origem']) ? 'is-invalid' : '' ?>" id="origem" name="origem" required>
              <?php foreach ($rotulosOrigem as $chave => $rotulo): ?>
                <option value="<?= e($chave) ?>" <?= $selecionado($origem, $chave) ?>><?= e($rotulo) ?></option>
              <?php endforeach; ?>
            </select>
            <?= $erroCampo('origem') ?>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="categoria_id">Categoria</label>
            <select class="form-select <?= isset($erros['categoria_id']) ? 'is-invalid' : '' ?>" id="categoria_id" name="categoria_id" required <?= $categorias === [] ? 'disabled' : '' ?>>
              <option value="">Selecione...</option>
              <optgroup label="Entradas">
                <?php foreach ($categorias as $categoria): ?>
                  <?php if ((string) $categoria['tipo'] !== 'entrada') { continue; } ?>
                  <option value="<?= e((int) $categoria['id']) ?>" <?= $selecionado($valor('categoria_id'), (int) $categoria['id']) ?>><?= e((string) $categoria['nome']) ?></option>
                <?php endforeach; ?>
              </optgroup>
              <optgroup label="Saídas">
                <?php foreach ($categorias as $categoria): ?>
                  <?php if ((string) $categoria['tipo'] !== 'saida') { continue; } ?>
                  <option value="<?= e((int) $categoria['id']) ?>" <?= $selecionado($valor('categoria_id'), (int) $categoria['id']) ?>><?= e((string) $categoria['nome']) ?></option>
                <?php endforeach; ?>
              </optgroup>
            </select>
            <?= $erroCampo('categoria_id') ?>
          </div>
          <div class="col-12">
            <label class="form-label" for="descricao">Descrição</label>
            <input class="form-control <?= isset($erros['descricao']) ? 'is-invalid' : '' ?>" id="descricao" name="descricao" value="<?= e($valor('descricao')) ?>" maxlength="255" required>
            <?= $erroCampo('descricao') ?>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="valor">Valor (R$)</label>
            <input class="form-control <?= isset($erros['valor']) ? 'is-invalid' : '' ?>" id="valor" name="valor" type="number" step="0.01" min="0.01" value="<?= e($valor('valor')) ?>" required>
            <?= $erroCampo('valor') ?>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="data_movimentacao">Data do lançamento</label>
            <input class="form-control <?= isset($erros['data_movimentacao']) ? 'is-invalid' : '' ?>" id="data_movimentacao" name="data_movimentacao" type="date" value="<?= e($valor('data_movimentacao')) ?>" required>
            <?= $erroCampo('data_movimentacao') ?>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="forma_pagamento">Forma de pagamento</label>
            <select class="form-select <?= isset($erros['forma_pagamento']) ? 'is-invalid' : '' ?>" id="forma_pagamento" name="forma_pagamento" required>
              <?php foreach ($rotulosForma as $chave => $rotulo): ?>
                <option value="<?= e($chave) ?>" <?= $selecionado($valor('forma_pagamento') !== '' ? $valor('forma_pagamento') : 'dinheiro', $chave) ?>><?= e($rotulo) ?></option>
              <?php endforeach; ?>
            </select>
            <?= $erroCampo('forma_pagamento') ?>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="competencia">Competência</label>
            <input class="form-control <?= isset($erros['competencia']) ? 'is-invalid' : '' ?>" id="competencia" name="competencia" type="month" value="<?= e($valor('competencia')) ?>" required>
            <?= $erroCampo('competencia') ?>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="referencia_mes">Mês de referência</label>
            <input class="form-control <?= isset($erros['referencia_mes']) ? 'is-invalid' : '' ?>" id="referencia_mes" name="referencia_mes" type="month" value="<?= e($valor('referencia_mes')) ?>">
            <?= $erroCampo('referencia_mes') ?>
          </div>
          <div class="col-md-4">
            <label class="form-label" for="congregacao_id">Congregação</label>
            <select class="form-select <?= isset($erros['congregacao_id']) ? 'is-invalid' : '' ?>" id="congregacao_id" name="congregacao_id">
              <option value="">Global</option>
              <?php foreach ($congregacoes as $congregacao): ?>
                <option value="<?= e((int) $congregacao['id']) ?>" <?= $selecionado($valor('congregacao_id'), (int) $congregacao['id']) ?>><?= e((string) $congregacao['nome']) ?></option>
              <?php endforeach; ?>
            </select>
            <?= $erroCampo('congregacao_id') ?>
          </div>
        </div>
      </div>

      <div class="card-admin mb-3" id="campos-dizimo" <?= $origem !== 'dizimo' ? 'hidden' : '' ?>>
        <h2 class="h6 mb-3">Dados do dízimo</h2>
        <div class="row g-3">
          <div class="col-md-8">
            <label class="form-label" for="membro_id">Dizimista</label>
            <select class="form-select <?= isset($erros['membro_id']) ? 'is-invalid' : '' ?>" id="membro_id" name="membro_id">
              <option value="">Não identificado</option>
              <?php foreach ($membros as $membro): ?>
                <option value="<?= e((int) $membro['id']) ?>" <?= $selecionado($valor('membro_id'), (int) $membro['id']) ?>><?= e((string) $membro['nome']) ?> · <?= e((string) $membro['codigo']) ?></option>
              <?php endforeach; ?>
            </select>
            <?= $erroCampo('membro_id') ?>
          </div>
        </div>
      </div>

      <div class="card-admin mb-3" id="campos-oferta" <?= $origem !== 'oferta' ? 'hidden' : '' ?>>
        <h2 class="h6 mb-3">Dados da oferta</h2>
        <div class="row g-3">
          <div class="col-md-7">
            <label class="form-label" for="ofertante_id">Ofertante</label>
            <select class="form-select <?= isset($erros['ofertante_id']) ? 'is-invalid' : '' ?>" id="ofertante_id" name="ofertante_id">
              <option value="">Não identificado</option>
              <?php foreach ($membros as $membro): ?>
                <option value="<?= e((int) $membro['id']) ?>" <?= $selecionado($valor('ofertante_id'), (int) $membro['id']) ?>><?= e((string) $membro['nome']) ?> · <?= e((string) $membro['codigo']) ?></option>
              <?php endforeach; ?>
            </select>
            <?= $erroCampo('ofertante_id') ?>
          </div>
          <div class="col-md-5">
            <label class="form-label" for="tipo_oferta">Tipo de oferta</label>
            <input class="form-control <?= isset($erros['tipo_oferta']) ? 'is-invalid' : '' ?>" id="tipo_oferta" name="tipo_oferta" value="<?= e($valor('tipo_oferta')) ?>" maxlength="100" placeholder="Ex.: Oferta missionária">
            <?= $erroCampo('tipo_oferta') ?>
          </div>
        </div>
      </div>

      <div class="card-admin mb-3">
        <h2 class="h6 mb-3">Observações</h2>
        <textarea class="form-control <?= isset($erros['observacoes']) ? 'is-invalid' : '' ?>" id="observacoes" name="observacoes" rows="3" maxlength="1000"><?= e($valor('observacoes')) ?></textarea>
        <?= $erroCampo('observacoes') ?>
      </div>

      <div class="d-flex flex-wrap gap-2 mb-3">
        <button class="btn btn-dark rounded-pill" type="submit" <?= $fechado || $categorias === [] ? 'disabled' : '' ?>><i class="fa-solid fa-floppy-disk me-1"></i>Salvar</button>
        <a class="btn btn-outline-secondary rounded-pill" href="<?= e(url('/admin/modulos/financeiro')) ?>">Voltar</a>
      </div>
    </div>

    <div class="col-lg-4">
      <div class="card-admin">
        <h2 class="h6 mb-3">Responsável</h2>
        <p class="small text-muted mb-3">O lançamento será registrado em nome do usuário autenticado.</p>
        <?php if ($edicao): ?>
          <dl class="row small mb-0">
            <dt class="col-6">Criado em</dt><dd class="col-6 text-end"><?= e(format_date((string) $registro['created_at'], 'd/m/Y H:i')) ?></dd>
            <dt class="col-6">Atualizado em</dt><dd class="col-6 text-end"><?= e(format_date((string) $registro['updated_at'], 'd/m/Y H:i')) ?></dd>
          </dl>
        <?php else: ?>
          <div class="alert alert-info small mb-0">A competência organiza o fechamento mensal e pode ser ajustada antes de salvar.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</form>
<script>
  const origemSelect = document.getElementById('origem');
  const camposDizimo = document.getElementById('campos-dizimo');
  const camposOferta = document.getElementById('campos-oferta');
  const alternarOrigem = () => {
    camposDizimo.hidden = origemSelect.value !== 'dizimo';
    camposOferta.hidden = origemSelect.value !== 'oferta';
  };
  origemSelect.addEventListener('change', alternarOrigem);
  alternarOrigem();
</script>
