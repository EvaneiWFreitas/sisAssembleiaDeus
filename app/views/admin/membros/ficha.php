<?php
$sexos = [
    'masculino' => 'Masculino',
    'feminino' => 'Feminino',
    'outro' => 'Outro',
    'nao_informado' => 'Não informado',
];
$estadosCivis = [
    'solteiro' => 'Solteiro',
    'casado' => 'Casado',
    'divorciado' => 'Divorciado',
    'viuvo' => 'Viuvo',
    'outro' => 'Outro',
    'nao_informado' => 'Não informado',
];
$situacoes = [
    'ativo' => 'Ativo',
    'inativo' => 'Inativo',
    'transferido' => 'Transferido',
    'falecido' => 'Falecido',
];
$foto = (string) ($membro['foto'] ?? '');
$endereco = trim(
    (string) ($membro['endereco'] ?? '') . ' ' . (string) ($membro['numero'] ?? '') . ' ' . (string) ($membro['complemento'] ?? '')
    . ' · ' . (string) ($membro['bairro'] ?? '') . ' · ' . (string) ($membro['cidade'] ?? '') . '/' . (string) ($membro['estado'] ?? '')
    . ' · CEP ' . (string) ($membro['cep'] ?? ''),
    ' ·'
);
?>
<header class="print-top">
  <img src="<?= e($logotipo ?? \App\SiteRepository::logotipo()) ?>" alt="">
  <div>
    <strong>Assembleia de Deus</strong>
    <small>sisAssembleiaDeus · Cadastro de membros</small>
  </div>
</header>

<div class="print-title">
  <h1>Ficha de membro</h1>
  <p>Documento gerado em <?= e(date('d/m/Y H:i')) ?></p>
</div>

<?php if ($foto !== ''): ?>
  <img src="<?= e(upload_url($foto)) ?>" alt="Foto do membro" style="border-radius:8px;float:right;height:96px;object-fit:cover;width:96px;">
<?php endif; ?>

<div class="print-grid">
  <div class="print-field print-full"><span>Nome completo</span><strong><?= e((string) $membro['nome']) ?></strong></div>
  <div class="print-field"><span>Código</span><strong><?= e((string) $membro['codigo']) ?></strong></div>
  <div class="print-field"><span>Situação</span><strong><?= e($situacoes[(string) $membro['situacao']] ?? (string) $membro['situacao']) ?></strong></div>
  <div class="print-field"><span>CPF</span><strong><?= e((string) ($membro['cpf'] ?? '') !== '' ? (string) $membro['cpf'] : '—') ?></strong></div>
  <div class="print-field"><span>RG</span><strong><?= e((string) ($membro['rg'] ?? '') !== '' ? (string) $membro['rg'] : '—') ?></strong></div>
  <div class="print-field"><span>Data de nascimento</span><strong><?= e(format_date($membro['data_nascimento'] ?? null)) ?></strong></div>
  <div class="print-field"><span>Sexo</span><strong><?= e($sexos[(string) ($membro['sexo'] ?? '')] ?? '—') ?></strong></div>
  <div class="print-field"><span>Estado civil</span><strong><?= e($estadosCivis[(string) ($membro['estado_civil'] ?? '')] ?? '—') ?></strong></div>
  <div class="print-field"><span>Telefone</span><strong><?= e((string) ($membro['telefone'] ?? '') !== '' ? (string) $membro['telefone'] : '—') ?></strong></div>
  <div class="print-field"><span>WhatsApp</span><strong><?= e((string) ($membro['whatsapp'] ?? '') !== '' ? (string) $membro['whatsapp'] : '—') ?></strong></div>
  <div class="print-field print-full"><span>E-mail</span><strong><?= e((string) ($membro['email'] ?? '') !== '' ? (string) $membro['email'] : '—') ?></strong></div>
  <div class="print-field print-full"><span>Endereço</span><strong><?= e($endereco !== '' ? $endereco : '—') ?></strong></div>
  <div class="print-field"><span>Igreja de origem</span><strong><?= e((string) ($membro['igreja_nome'] ?? '') !== '' ? (string) $membro['igreja_nome'] : '—') ?></strong></div>
  <div class="print-field"><span>Congregação</span><strong><?= e((string) ($membro['congregacao_nome'] ?? '') !== '' ? (string) $membro['congregacao_nome'] : '—') ?></strong></div>
  <div class="print-field"><span>Data de ingresso</span><strong><?= e(format_date($membro['data_ingresso'] ?? null)) ?></strong></div>
  <div class="print-field"><span>Data de batismo</span><strong><?= e(format_date($membro['data_batismo'] ?? null)) ?></strong></div>
  <div class="print-field print-full"><span>Observações</span><strong><?= e((string) ($membro['observacoes'] ?? '') !== '' ? (string) $membro['observacoes'] : '—') ?></strong></div>
</div>

<footer class="print-footer">
  <span>sisAssembleiaDeus</span>
  <span>Documento interno · não substitui registro civil</span>
</footer>
