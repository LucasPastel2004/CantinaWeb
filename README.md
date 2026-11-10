# Cantina Web — Sistema de Gestão Escolar e Cantina

> **Aviso Institucional & Open Source:**  
> Este sistema foi idealizado e desenvolvido sob medida para as necessidades operacionais, financeiras e nutricionais do **Colégio Monteiro Lobato**. Por essa razão, determinadas regras de negócio, fluxos de corte de turnos e parametrizações foram desenhadas especificamente para o dia a dia da nossa cantina.  
> O projeto foi disponibilizado como **código aberto (Open Source)** sob a licença **Apache 2.0** para que outras instituições de ensino, operadores de cantinas e desenvolvedores tenham uma base corporativa sólida, auditada, sem dependências de frameworks pesados e pronta para entrar em produção. Fique à vontade para adaptar e expandir o código para a realidade da sua escola!

---

## 📌 Sumário

1. [Sobre o Projeto](#-sobre-o-projeto)
2. [Principais Funcionalidades](#-principais-funcionalidades)
3. [Arquitetura e Stack Tecnológica](#-arquitetura-e-stack-tecnológica)
4. [Como Rodar Localmente](#-como-rodar-localmente)
   - [Opção 1: Com Docker (Recomendado — Linux & Windows)](#opção-1-com-docker-recomendado)
   - [Opção 2: Sem Docker no Linux (Ubuntu / Debian)](#opção-2-sem-docker-no-linux-ubuntu--debian)
   - [Opção 3: Sem Docker no Windows (XAMPP / Laragon / Nativo)](#opção-3-sem-docker-no-windows-xampp--laragon)
5. [Acesso e Credenciais Iniciais](#-acesso-e-credenciais-iniciais)
6. [Guia Completo de Deploy em Produção](#-guia-completo-de-deploy-em-produção)
7. [Execução de Testes Automatizados](#-execução-de-testes-automatizados)
8. [Estrutura de Diretórios](#-estrutura-de-diretórios)
9. [Licença](#-licença)

---

## 🚀 Sobre o Projeto

O **Cantina Web** é uma solução completa em PHP 8.2 voltada para a gestão de ponta a ponta de cantinas escolares. O sistema elimina as tradicionais filas de recreio, erradica o manuseio de dinheiro físico por crianças e automatiza a produção diária de lanches com base em horários rígidos de corte.

### Problemas que o sistema resolve:
- **Zero filas no recreio:** Atendimento no balcão sem digitação ou leitores lentos: os lanches são encomendados previamente e os tickets térmicos (80mm) são impressos em lote por série/turma e entregues diretamente nas salas de aula. Na hora do intervalo, o aluno apenas entrega o ticket impresso e retira o produto.
- **Zero desperdício de alimentos:** A cantina compra ou produz salgados com base na consolidação exata das encomendas feitas pelos pais até o horário limite de corte (08:00 para o turno matutino e 13:30 para o vespertino).
- **Carteira Digital Familiar & Pix Dinâmico:** Pais e responsáveis possuem carteira pré-paga unificada, com recargas via Pix (QR Code canônico ISO/IEC 18004 e código Copia e Cola) lido instantaneamente por qualquer banco.
- **Segurança Alimentar e Alérgenos:** Cadastro e alerta visual imediato para os 8 grandes alérgenos (Glúten, Lactose, Amendoim, Ovos, Soja, Frutos do Mar, Nozes e Tartrazina).
- **Conta Familiar Compartilhada:** Pai e Mãe possuem acessos independentes (cada um com seu CPF e senha), compartilhando o mesmo saldo e os mesmos dependentes, com trava atômica que impede agendamentos duplicados para a mesma criança.
- **Emissão 100% Nativa (Print to PDF):** Zero dependência de Node.js, Puppeteer ou microsserviços Docker pesados (sem Gotenberg). Todo e qualquer relatório ou cupom utiliza folhas de estilo `@media print` nativas do navegador (`window.print()`).

---

## 🎯 Principais Funcionalidades

| Módulo | Descrição das Funcionalidades |
| :--- | :--- |
| **Portal da Família** | Dashboard de dependentes, extrato de lançamentos, recarga Pix com confirmação automática, agendamento semanal de lanches, bloqueio de corte de turno e cadastro de restrições alimentares. |
| **Operacional da Cantina** | Painel de corte de pedidos por turno, conferência física de recebimento de fornecedores, exportação para fornecedor (WhatsApp/PDF A4) e impressão em lote de tickets térmicos por turma. |
| **Frente de Caixa (PDV)** | Atendimento balcão com vendas avulsas em dinheiro (com cálculo de troco) e Pix balcão, busca instantânea de itens com fotos e atalhos de teclado. |
| **Estorno e Auditoria** | Fluxo de cancelamento de vendas com opção de devolução ao estoque ou registro contábil de perda, garantindo integridade no fechamento de caixa. |
| **Controle de Estoque** | Entrada de mercadorias, conferência de lotes, apontamento de perdas com motivos regulamentares, alerta visual de estoque mínimo e histórico completo de movimentações. |
| **Financeiro & DRE** | Abertura e fechamento de caixas por turno, relatório de arrecadação financeira com conciliação, Demonstrativo do Resultado do Exercício (DRE) segregando Regime de Caixa e Competência, Livro Caixa Diário e recibos timbrados para fornecedores. |
| **Administração Escolar** | Importação em lote de planilhas escolares (CSV/Excel) de turmas, alunos e responsáveis com pré-conferência *dry-run*, gestão de cardápio, tabela de preços e calendário letivo. |

---

## 🏗 Arquitetura e Stack Tecnológica

O sistema foi concebido sob uma **arquitetura MVC nativa em PHP 8.2**, dispensando frameworks monolíticos pesados (como Laravel ou Symfony). Isso garante consumo de memória baixíssimo (~10MB a 20MB de RAM), tempo de resposta na casa dos milissegundos e portabilidade total para qualquer hospedagem compartilhada.

- **Linguagem Backend:** PHP 8.2+ fortemente tipado e orientado a objetos.
- **Banco de Dados:** MySQL 8.0+ ou MariaDB 10.4+, utilizando o driver `PDO_MYSQL` com Prepared Statements nativos sem emulação (`ATTR_EMULATE_PREPARES => false`) e codificação `utf8mb4`.
- **Frontend:** HTML5 semântico, folha de estilo corporativa de alta densidade (`assets/css/custom.css`), Vanilla JS assíncrono modular (`assets/js/app.js`) e Bootstrap 5.3.3 local compilado (sem links para CDNs externas).
- **Segurança:** Autenticação baseada em sessões seguras (`HttpOnly`, `SameSite=Lax`), criptografia de senhas com Bcrypt (`password_hash`), tokens CSRF descartáveis em todas as mutações POST, sanitização contra XSS e bloqueio restrito de diretórios internos via `.htaccess`.

---

## 💻 Como Rodar Localmente

### Opção 1: Com Docker (Recomendado — Linux & Windows)

O projeto já inclui um `docker-compose.yml` pré-configurado contendo Apache com PHP 8.2 e MySQL 8.0.

#### 1. Pré-requisitos:
- [Git](https://git-scm.com/)
- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (Windows) ou Docker Engine + Docker Compose (Linux).

#### 2. Passo a Passo:

1. **Clone o repositório:**
   ```bash
   git clone https://github.com/LucasPastel2004/CantinaWeb.git
   cd CantinaWeb
   ```

2. **Crie o arquivo de configuração local:**
   - **No Linux / macOS:**
     ```bash
     cp config/config.example.php config/config.php
     ```
   - **No Windows (PowerShell):**
     ```powershell
     Copy-Item config\config.example.php config\config.php
     ```

3. **Inicie os contêineres Docker:**
   ```bash
   docker compose up -d
   ```
   *O Docker baixará as imagens oficiais, configurará o MySQL 8.0 na porta 3306 e o Apache/PHP na porta 8080.*

4. **Execute as migrações do banco de dados:**
   ```bash
   docker compose exec app php database/runner.php
   ```
   *Todas as 36 tabelas e migrações estruturais serão criadas e populadas automaticamente.*

5. **Pronto! Acesse no navegador:**
   - URL: `http://localhost:8080`

---

### Opção 2: Sem Docker no Linux (Ubuntu / Debian)

Para rodar diretamente na sua máquina Linux ou em uma VPS sem Docker:

1. **Instale o Apache, PHP 8.2 e as extensões obrigatórias:**
   ```bash
   sudo apt update
   sudo apt install -y apache2 mysql-server \
       php8.2 php8.2-mysql php8.2-mbstring php8.2-xml \
       php8.2-gd php8.2-curl php8.2-zip libapache2-mod-php8.2
   ```

2. **Habilite os módulos do Apache:**
   ```bash
   sudo a2enmod rewrite headers
   sudo systemctl restart apache2
   ```

3. **Clone o projeto na pasta do servidor web:**
   ```bash
   cd /var/www/html
   sudo git clone https://github.com/LucasPastel2004/CantinaWeb.git cantina
   cd cantina
   ```

4. **Configure permissões das pastas de escrita e uploads:**
   ```bash
   sudo chown -R www-data:www-data storage/ config/
   sudo chmod -R 775 storage/
   ```

5. **Habilite suporte a `.htaccess` no Apache:**  
   Edite o arquivo `/etc/apache2/apache2.conf` ou o VirtualHost e certifique-se de que a diretiva `AllowOverride` esteja definida como `All`:
   ```apache
   <Directory /var/www/html>
       Options Indexes FollowSymLinks
       AllowOverride All
       Require all granted
   </Directory>
   ```
   Reinicie o Apache: `sudo systemctl restart apache2`

6. **Crie o banco de dados MySQL:**
   ```bash
   sudo mysql -u root -p
   ```
   Dentro do MySQL, execute:
   ```sql
   CREATE DATABASE cantina CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'cantina_user'@'localhost' IDENTIFIED BY 'SuaSenhaForte123!';
   GRANT ALL PRIVILEGES ON cantina.* TO 'cantina_user'@'localhost';
   FLUSH PRIVILEGES;
   EXIT;
   ```

7. **Configure as credenciais da aplicação:**
   ```bash
   cp config/config.example.php config/config.php
   nano config/config.php
   ```
   Ajuste as linhas de banco de dados (`host`, `database`, `username`, `password`) com os dados que acabou de criar.

8. **Execute as migrações:**
   ```bash
   php database/runner.php
   ```

9. **Acesse no seu navegador:**
   - `http://localhost/cantina`

---

### Opção 3: Sem Docker no Windows (XAMPP / Laragon)

1. **Instale o XAMPP ou Laragon** com suporte a PHP 8.2+ e MySQL.
2. Abra o painel e inicie os módulos **Apache** e **MySQL**.
3. No painel do Apache, certifique-se de que `mod_rewrite` e `mod_headers` estão ativados no `httpd.conf`.
4. Copie a pasta do projeto para `C:\xampp\htdocs\cantina` (ou pasta raiz do Laragon).
5. Abra o **phpMyAdmin** (`http://localhost/phpmyadmin`) e crie um banco de dados chamado `cantina` com o charset `utf8mb4_unicode_ci`.
6. Copie o arquivo `config/config.example.php` para `config/config.php`.
7. Abra o arquivo `config/config.php` em um editor e certifique-se de que o bloco de banco de dados está apontando para:
   ```php
   'db' => [
       'driver'   => 'mysql',
       'host'     => '127.0.0.1',
       'port'     => 3306,
       'database' => 'cantina',
       'username' => 'root',
       'password' => '', // ou sua senha local
   ],
   ```
8. Abra o terminal (PowerShell ou Prompt de Comando) na pasta do projeto e execute:
   ```powershell
   php database/runner.php
   ```
9. Acesse no navegador:
   - `http://localhost/cantina`

---

## 🔑 Acesso e Credenciais Iniciais

Após rodar as migrações, o sistema cria automaticamente o usuário administrador inicial para o primeiro acesso ao painel gerencial:

| Campo | Valor Inicial |
| :--- | :--- |
| **URL Administrativa** | `http://localhost:8080/login` |
| **Usuário** | `admin` |
| **Senha Padrão** | `Admin@123` |
| **Perfil** | Administrador Geral |

> ⚠️ **Importante:** Altere a senha padrão do usuário administrador imediatamente ao realizar o primeiro acesso em um ambiente de produção!

Para acessar o **Portal da Família** (`/portal`), você pode cadastrar um responsável pelo painel administrativo ou usar a função de primeiro acesso vinculando um aluno matriculado.

---

## 🌐 Guia Completo de Deploy em Produção

### Por que o projeto roda em qualquer hospedagem compartilhada?
Muitas escolas utilizam serviços de hospedagem gerenciada tradicionais (como **Hostnet**, **cPanel**, **Locaweb**, **HostGator**, etc.) que **não oferecem contêineres Docker, Node.js ou daemons em background**. 

O **Cantina Web foi projetado especificamente para ser 100% autossuficiente**:
- Não precisa de Docker em produção;
- Não depende de Composer rodando no servidor;
- Não necessita de comandos de compilação de assets (Bootstrap e scripts são servidos localmente);
- Suporta rodar tanto na raiz do domínio (`https://cantina.seucolegio.com.br`) quanto em um subdiretório (`https://seucolegio.com.br/cantina/`).

### Passo a Passo do Deploy:

#### 1. Upload dos Arquivos
Faça o upload do projeto para o diretório `public_html/` da sua hospedagem via FTP, SFTP ou Git.  
O projeto inclui o arquivo `deploy-exclude.txt` que lista todos os arquivos locais que **não devem** subir para a produção (como a pasta `.git/`, arquivos de testes em `tests/`, etc.).

#### 2. Configuração do Banco de Dados em Produção
1. No painel de controle da sua hospedagem, crie uma base de dados MySQL (ex: `cantina_prod`) e um usuário com privilégios totais.
2. No servidor, crie o arquivo `config/config.php` a partir do `config/config.example.php`.
3. Preencha as credenciais reais de banco, desative o debug e configure uma chave de migração forte:
   ```php
   'app' => [
       'name'     => 'Cantina Web — Colégio Monteiro Lobato',
       'env'      => 'production',
       'debug'    => false,
       'url'      => 'https://seucolegio.com.br/cantina',
       'timezone' => 'America/Sao_Paulo',
       'key'      => 'COLOQUE_UMA_CHAVE_ALEATORIA_LONGA_DE_32_CHARS',
   ],
   'db' => [
       'host'     => 'mysql.seusite.com.br', // ou 127.0.0.1
       'database' => 'nome_do_banco_criado',
       'username' => 'usuario_do_banco',
       'password' => 'senha_forte_do_banco',
       'charset'  => 'utf8mb4',
   ],
   'security' => [
       'migration_token' => 'SUA_CHAVE_SECRETA_DE_MIGRACAO_AQUI',
   ],
   ```

#### 3. Execução das Migrações no Servidor de Produção

Você tem duas formas de executar as migrações em produção:

- **Via SSH (Se sua hospedagem tiver terminal):**
  ```bash
  php database/runner.php
  ```

- **Via Navegador Web (Para hospedagens sem SSH, como Hostnet básico):**  
  O script `database/runner.php` foi especialmente programado para rodar de forma segura pela web, protegido por token:
  ```text
  https://seusite.com.br/database/runner.php?token=SUA_CHAVE_SECRETA_DE_MIGRACAO_AQUI
  ```
  O navegador exibirá em texto puro a aplicação sequencial e o status de cada migração executada com sucesso.

#### 4. Configuração de E-mail Transacional (SMTP)
No `config/config.php`, configure os parâmetros do servidor SMTP para envio de confirmações de recarga, recuperação de senha e comunicados aos pais.  
*(Exemplo para Hostnet Ultramail: o campo `smtp_user` utiliza o caractere `=` em vez de `@`, como `contato=seucolegio.com.br`).*

#### 5. Certificado SSL (HTTPS)
Ative o certificado SSL gratuito (Let's Encrypt) no painel da sua hospedagem. O arquivo `.htaccess` na raiz já cuidará do roteamento e preservação de sessões criptografadas.

---

## 🧪 Execução de Testes Automatizados

O sistema conta com suítes de testes unitários e de integração cobrindo fluxos financeiros, integridade contábil, trava de saldo negativo, geração de QR Code Pix e conciliação do DRE.

Para rodar os testes via Docker:
```bash
# Teste de emissão e leitura óptica do QR Code Pix
docker compose exec app php tests/pix_qrcode_leitura_test.php

# Teste do ciclo de carteira e Pix dinâmico
docker compose exec app php tests/fase9_test.php

# Teste do DRE e segregação de regimes contábeis
docker compose exec app php tests/arrecadacao_dre_regimes_test.php

# Teste de todos os espelhos e impressões Print to PDF A4
docker compose exec app php tests/teste_todos_pdfs.php
```

Para rodar os testes localmente sem Docker:
```bash
php tests/pix_qrcode_leitura_test.php
php tests/fase9_test.php
```

---

## 📁 Estrutura de Diretórios

```text
├── index.php                 # Front Controller e ponto único de entrada
├── .htaccess                 # Roteamento central e proteção de pastas ocultas
├── docker-compose.yml        # Orquestração local do ambiente de desenvolvimento
│
├── config/                   # Configurações do sistema (blindado por .htaccess)
│   ├── config.example.php    # Modelo versionado sem credenciais
│   ├── config.php            # Configurações locais ativas (ignorado no Git)
│   └── database.php          # Fábrica Singleton de conexão PDO com MySQL
│
├── app/                      # Núcleo da arquitetura MVC
│   ├── core/                 # Classes fundamentais (Router, Controller, Model, Session, Auth, RBAC)
│   ├── controllers/          # Controladores separados por domínio funcional
│   ├── models/               # Modelos de dados com regras de negócio e transações PDO
│   ├── views/                # Telas e templates (Layouts, Portais, PDV, Operacional, Relatórios)
│   └── services/             # Adaptadores de integração (Pix, QR Code, XML NF-e, Planilhas, E-mail)
│
├── database/                 # Migrações e banco de dados
│   ├── migrations/           # 36 migrações sequenciais versionadas
│   └── runner.php            # Executor idempotente de migrações (CLI ou HTTP com Token)
│
├── assets/                   # Recursos estáticos locais do frontend
│   ├── css/                  # Folhas de estilo (Bootstrap 5.3.3 local e custom.css)
│   ├── js/                   # Scripts interativos (Vanilla JS modular e app.js)
│   └── img/                  # Logotipo e imagens institucionais
│
├── storage/                  # Logs locais e diretórios de armazenamento
│   ├── logs/                 # Logs de auditoria, segurança e contingência
│   └── uploads/              # Uploads de fotos de produtos e planilhas privadas
│
├── docs/                     # Documentação viva de engenharia, manuais e histórico
└── tests/                    # Suítes de testes automatizados e integração
```

---

## 📄 Licença

Este projeto é distribuído sob os termos da licença **Apache 2.0**. Consulte o arquivo [LICENSE](LICENSE) para obter o texto integral.

---

Desenvolvido para a cantina do **Colégio Monteiro Lobato** e compartilhado com a comunidade. Sinta-se livre para abrir *Issues*, sugerir melhorias e enviar *Pull Requests*!
