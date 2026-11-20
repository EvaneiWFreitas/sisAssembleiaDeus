<?php
$situacoes = [
    'ativo' => 'Ativo',
    'licenciado' => 'Licenciado',
    'desligado' => 'Desligado',
];
$foto = (string) ($obreiro['foto'] ?? '');
$endereco = trim(
    (string) ($obreiro['endereco'] ?? '') . ' ' . (string) ($obreiro['numero'] ?? '') . ' ' . (string) ($obreiro['complemento'] ?? '')
    . ' · ' . (string) ($obreiro['bairro'] ?? '') . ' · ' . (string) ($obreiro['cidade'] ?? '') . '/' . (string) ($obreiro['estado'] ?? '')
    . ' · CEP ' . (string) ($obreiro['cep'] ?? ''),
    ' ·'
);
?>
<header class="print-top">
  <img src="<?= e($logotipo ?? \App\SiteRepository::logotipo()) ?>" alt="">
  <div>
    <strong>Assembleia de Deus</strong>
    <small>sisAssembleiaDeus · Cadastro de obreiros</small>
  </div>
</header>

<div class="print-title">
  <h1>Ficha de obreiro</h1>
  <p>Documento gerado em <?= e(date('d/m/Y H:i')) ?></p>
</div>

<?php if ($foto !== ''): ?>
  <img src="<?= e(upload_url($foto)) ?>" alt="Foto do obreiro" style="border-radius:8px;float:right;height:96px;object-fit:cover;width:96px;">
<?php endif; ?>

<div class="print-grid">
  <div class="print-field print-full"><span>Nome completo</span><strong><?= e((string) $obreiro['nome']) ?></strong></div>
  <div class="print-field"><span>Código</span><strong><?= e((string) $obreiro['codigo']) ?></strong></div>
  <div class="print-field"><span>Situação</span><strong><?= e($situacoes[(string) $obreiro['situacao']] ?? (string) $obreiro['situacao']) ?></strong></div>
  <div class="print-field"><span>Cargo ministerial</span><strong><?= e((string) $obreiro['cargo_nome']) ?></strong></div>
  <div class="print-field"><span>Congregação</span><strong><?= e((string) $obreiro['congregacao_nome']) ?></strong></div>
  <div class="print-field"><span>Igreja de origem</span><strong><?= e((string) ($obreiro['matriz_nome'] ?? '') !== '' ? (string) $obreiro['matriz_nome'] : '—') ?></strong></div>
  <div class="print-field"><span>Registro ministerial</span><strong><?= e((string) ($obreiro['registro_ministerial'] ?? '') !== '' ? (string) $obreiro['registro_ministerial'] : '—') ?></strong></div>
  <div class="print-field"><span>Data de consagração</span><strong><?= e(format_date($obreiro['data_consagracao'] ?? null)) ?></strong></div>
  <div class="print-field"><span>Membro vinculado</span><strong><?= e((string) ($obreiro['membro_nome'] ?? '') !== '' ? (string) $obreiro['membro_nome'] : '—') ?></strong></div>
  <div class="print-field"><span>CPF</span><strong><?= e((string) ($obreiro['cpf'] ?? '') !== '' ? (string) $obreiro['cpf'] : '—') ?></strong></div>
  <div class="print-field"><span>RG</span><strong><?= e((string) ($obreiro['rg'] ?? '') !== '' ? (string) $obreiro['rg'] : '—') ?></strong></div>
  <div class="print-field"><span>Data de nascimento</span><strong><?= e(format_date($obreiro['data_nascimento'] ?? null)) ?></strong></div>
  <div class="print-field"><span>Telefone</span><strong><?= e((string) ($obreiro['telefone'] ?? '') !== '' ? (string) $obreiro['telefone'] : '—') ?></strong></div>
  <div class="print-field"><span>WhatsApp</span><strong><?= e((string) ($obreiro['whatsapp'] ?? '') !== '' ? (string) $obreiro['whatsapp'] : '—') ?></strong></div>
  <div class="print-field print-full"><span>E-mail</span><strong><?= e((string) ($obreiro['email'] ?? '') !== '' ? (string) $obreiro['email'] : '—') ?></strong></div>
  <div class="print-field print-full"><span>Endereço</span><strong><?= e($endereco !== '' ? $endereco : '—') ?></strong></div>
  <div class="print-field print-full"><span>Observações</span><strong><?= e((string) ($obreiro['observacoes'] ?? '') !== '' ? (string) $obreiro['observacoes'] : '—') ?></strong></div>
</div>

<footer class="print-footer">
  <span>sisAssembleiaDeus</span>
  <span>Documento interno · não substitui registro civil</span>
</footer>
