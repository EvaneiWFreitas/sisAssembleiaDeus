# ⛪ sisAssembleiaDeus
### Sistema de Gestão Administrativa e Eclesiástica de Igrejas

**O sisAssembleiaDeus** é um sistema web desenvolvido para auxiliar na administração, organização e gerenciamento das atividades administrativas, financeiras e eclesiásticas de igrejas, ministérios, congregações e pontos de pregação.

A proposta é oferecer uma plataforma centralizada, prática e intuitiva, facilitando o controle de membros, obreiros, unidades religiosas e movimentações financeiras.

---

## 📌 Sumário

- [Sobre o Projeto](#-sobre-o-projeto)
- [Objetivos](#-objetivos)
- [Funcionalidades](#-funcionalidades)
- [Tecnologias Utilizadas](#-tecnologias-utilizadas)
- [Estrutura do Projeto](#-estrutura-do-projeto)
- [Requisitos do Sistema](#-requisitos-do-sistema)
- [Instalação](#-instalação)
- [Configuração do Banco de Dados](#-configuração-do-banco-de-dados)
- [Executando o Sistema](#-executando-o-sistema)
- [Segurança](#-segurança)
- [Hospedagem](#-hospedagem)
- [Desenvolvedor](#-desenvolvedor)

---

## 🎯 Sobre o Projeto

O sisAssembleiaDeus foi idealizado para atender às necessidades de gestão de instituições religiosas, permitindo organizar informações administrativas e financeiras em um único ambiente.

O sistema busca reduzir processos manuais, facilitar o acesso às informações e proporcionar maior controle sobre as atividades da igreja.

É um projeto voltado à modernização da administração eclesiástica por meio da tecnologia.

## 🚀 Objetivos

- Centralizar as informações da instituição.
- Facilitar o gerenciamento de membros e obreiros.
- Organizar igrejas, congregações e pontos de pregação.
- Controlar entradas financeiras provenientes de dízimos e ofertas.
- Melhorar o acompanhamento administrativo.
- Facilitar a consulta e organização dos registros.

## ⚙️ Funcionalidades

### 👥 Gestão de Pessoas
- Cadastro de membros.
- Cadastro de obreiros.
- Organização dos dados cadastrais.
- Consulta das informações registradas.

### ⛪ Gestão Eclesiástica
- Cadastro da Igreja Matriz.
- Cadastro de congregações.
- Cadastro de pontos de pregação.
- Organização das unidades vinculadas à instituição.

### 💰 Gestão Financeira
- Controle de dízimos.
- Registro de ofertas.
- Organização das movimentações financeiras.
- Consulta dos registros financeiros.

### 🪪 Identificação
- Organização dos dados para identificação de membros e obreiros.
- Estrutura para emissão de carteirinhas de identificação.

> As funcionalidades disponíveis dependem da implementação e da versão instalada do sistema.

## 💻 Tecnologias Utilizadas

O projeto utiliza tecnologias de desenvolvimento web:

| Tecnologia | Aplicação |
|---|---|
| PHP | Linguagem de programação backend |
| MySQL | Gerenciamento do banco de dados |
| Bootstrap | Desenvolvimento da interface responsiva |
| HTML5 | Estrutura das páginas |
| CSS3 | Estilização visual |
| JavaScript | Interatividade da aplicação |
| Apache | Servidor web |

## 📂 Estrutura do Projeto

O repositório está organizado nas seguintes pastas:

```text
sisAssembleiaDeus/
│
├── app/                 # Aplicação e funcionalidades
├── config/              # Arquivos de configuração
├── database/            # Arquivos relacionados ao banco de dados
├── docs/                # Documentação do projeto
├── public/              # Arquivos públicos da aplicação
├── routes/              # Rotas do sistema
├── storage/             # Armazenamento de arquivos
│
├── .gitignore           # Arquivos ignorados pelo Git
├── .htaccess            # Configurações do servidor Apache
└── index.php            # Ponto de entrada da aplicação
```

## 📋 Requisitos do Sistema

Para executar o projeto localmente, recomenda-se:

- Windows, Linux ou macOS.
- PHP 8.x.
- MySQL 5.7 ou superior.
- Apache.
- Extensão PDO para PHP.
- Navegador atualizado.
- XAMPP ou ambiente equivalente.

## 🔧 Instalação

### 1. Clonar o repositório

Abra o terminal ou PowerShell e execute:

```bash
git clone https://github.com/EvaneiWFreitas/sisAssembleiaDeus.git
```

### 2. Acessar a pasta

```bash
cd sisAssembleiaDeus
```

### 3. Instalar o ambiente local

Instale o [XAMPP](https://www.apachefriends.org/pt_br/index.html).

Copie a pasta do projeto para:

```text
C:\xampp\htdocs\
```

### 4. Iniciar os serviços

Abra o painel de controle do XAMPP e inicie:

- Apache
- MySQL

## 🗄️ Configuração do Banco de Dados

O projeto utiliza MySQL para armazenar as informações da instituição.

Nome do banco de dados utilizado no ambiente de hospedagem:

```text
ezyro_43462517_gestao_igreja
```

### Configuração local

Crie o banco de dados pelo phpMyAdmin:

```sql
CREATE DATABASE gestao_igreja
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
```

Em seguida:

1. Acesse o phpMyAdmin.
2. Selecione o banco de dados criado.
3. Importe o arquivo SQL disponibilizado na pasta `database`, caso presente.
4. Configure as credenciais de conexão no arquivo de configuração correspondente.

Exemplo de configuração:

```php
<?php

$host = 'localhost';
$database = 'gestao_igreja';
$username = 'root';
$password = '';

$dsn = "mysql:host={$host};dbname={$database};charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

$pdo = new PDO($dsn, $username, $password, $options);
```

**Importante:** o exemplo acima é ilustrativo. Utilize o arquivo de conexão efetivamente adotado pelo projeto e ajuste os parâmetros conforme seu ambiente.

## 🌐 Executando o Sistema

Após configurar o Apache, MySQL e banco de dados, abra o navegador e acesse:

```text
http://localhost/sisAssembleiaDeus/
```

Caso esteja utilizando uma hospedagem online, acesse o domínio configurado para sua instalação.

## 🔐 Segurança

Recomendações importantes para manter a aplicação protegida:

- Não disponibilizar senhas de banco de dados publicamente.
- Utilizar consultas preparadas com PDO.
- Validar e sanitizar os dados recebidos.
- Proteger as áreas administrativas com autenticação.
- Implementar controle de permissões de usuários.
- Utilizar HTTPS na hospedagem.
- Realizar backups periódicos do banco de dados.
- Restringir o acesso aos diretórios internos da aplicação.
- Proteger os arquivos enviados por upload.

## ☁️ Hospedagem

O sistema pode ser implantado em uma hospedagem que ofereça suporte a PHP, MySQL e Apache.

Para publicar:

1. Envie os arquivos para o diretório público da hospedagem.
2. Configure a conexão com o banco de dados remoto.
3. Importe a estrutura e os dados necessários.
4. Ajuste o endereço da aplicação e as configurações de produção.
5. Verifique as permissões das pastas de armazenamento e uploads.
6. Teste as funcionalidades após a publicação.

**Atenção:** não utilize as credenciais do ambiente local na produção. Mantenha os arquivos de configuração e senhas protegidos.

## 👨‍💻 Desenvolvedor

**Dev Evanei Freitas**

Desenvolvedor de Sistemas | Bacharel em Engenharia de Software

Pós-graduando em Computação e Tecnologias da Informação e Comunicação (TIC), com ênfase em Cybercrime e Cybersecurity: Prevenção e Investigação de Crimes Digitais.

Entusiasta da tecnologia, desenvolvimento web, programação e soluções digitais.

### Contato e Portfólio

- **GitHub:** https://github.com/EvaneiWFreitas
- **Projeto:** https://github.com/EvaneiWFreitas/sisAssembleiaDeus

---

## 🙏 Considerações Finais

A tecnologia também pode ser uma ferramenta de apoio à obra de Deus.

O sisAssembleiaDeus nasce com o propósito de contribuir para uma administração eclesiástica mais organizada, eficiente e moderna, auxiliando igrejas e ministérios na gestão de suas atividades.

**"Mas faça-se tudo decentemente e com ordem." — 1 Coríntios 14:40**

---

<sub>Desenvolvido por Evanei William Nascimento de Freitas | sisAssembleiaDeus | 2026</sub>
