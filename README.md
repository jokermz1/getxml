# GetXML SEFAZ - Sistema de Captura de Notas Fiscais

Sistema MVC em PHP para captura e gerenciamento de XMLs de notas fiscais da SEFAZ (Secretaria da Fazenda). Desenvolvido com Composer, seguindo o padrão Model-View-Controller.

## 🎯 Características

- ✅ **Autenticação de usuários** (admin e contadores)
- ✅ **Upload de certificados digitais** por usuário
- ✅ **MySQL** para persistência de dados
- ✅ **Painel administrativo** para gerenciamento
- ✅ **Isolamento de dados** por usuário
- ✅ **Busca de notas** na SEFAZ (com assinatura digital)
- ✅ **Gerenciamento de XMLs** personalizados
- ✅ **Interface web responsiva**
- ✅ **Conformidade total** com documentação SEFAZ
- ✅ **Suporte a produção e homologação**
- ✅ **Assinatura digital XML** conforme padrão XML-DSig
- ✅ **Paginação por NSU** conforme normas SEFAZ
- ✅ **Suporte a todos os estados** brasileiros

## 📋 Pré-requisitos

- PHP >= 7.4
- Composer
- MySQL/MariaDB
- Servidor web (Apache, Nginx, ou XAMPP)
- Certificado digital A1 (pfx/p12) para acesso aos serviços da SEFAZ
- Extensões PHP: openssl, xml, mbstring

## 🚀 Instalação

1. Clone ou baixe este repositório para o diretório do seu servidor:
```bash
cd C:\xampp\htdocs\getxml
```

2. Instale as dependências do Composer:
```bash
composer install
```

3. Configure as variáveis de ambiente editando o arquivo `.env`:
```bash
cp .env.teste .env
# Edite o arquivo .env com suas configurações
```

4. Configure o MySQL no `.env`:
```env
DB_HOST=localhost
DB_DATABASE=getxml
DB_USERNAME=getxml
DB_PASSWORD=gX7#kLp$2Qz!vN9@@@
```

5. Configure a URL do sistema no `.env`:
```env
APP_URL=http://localhost/getxml/public
APP_PUBLIC_PATH=/public
```

6. Crie o usuário do banco de dados usando o script SQL:
   - Abra o arquivo `database/manipulacao_banco.sql`
   - Descomente a seção 1.1 (criação de banco e usuário)
   - Execute no seu cliente MySQL (phpMyAdmin, Workbench, etc.)
   - Ou altere a senha no script se preferir uma senha diferente

7. Instale o banco de dados:
```bash
php database/install.php
```

## ⚙️ Configuração

Edite o arquivo `.env` na raiz do projeto com as seguintes informações:

```env
# Configurações do Sistema
APP_NAME=GetXML_SEFAZ
APP_ENV=development
APP_DEBUG=true

# Configurações de URL
APP_URL=http://localhost/getxml/public
APP_PUBLIC_PATH=/public

# Configurações SEFAZ
SEFAZ_UF=SP                    # UF do estado (SP, MG, PR, etc)
SEFAZ_AMBIENTE=2               # 1=Produção, 2=Homologação
SEFAZ_CERTIFICADO=             # Opcional (usado se não tiver certificado por usuário)
SEFAZ_SENHA_CERTIFICADO=      # Opcional (usado se não tiver certificado por usuário)

# Configurações MySQL
DB_HOST=localhost
DB_DATABASE=getxml
DB_USERNAME=getxml
DB_PASSWORD=gX7#kLp$2Qz!vN9@@@

# CNPJ para captura das notas (opcional)
CNPJ_CNPJ=
CNPJ_IE=

# Período de captura (opcional)
DATA_INICIO=
DATA_FIM=

# Configurações de armazenamento
STORAGE_PATH=storage/xmls
```

### 🔀 Redirecionamento Automático

O sistema possui redirecionamento automático via `.htaccess`. Ao acessar:
- `http://localhost/getxml/` → redireciona automaticamente para `http://localhost/getxml/public/`
- Não é necessário adicionar `/public` na URL manualmente

Para alterar a URL base, modifique as variáveis `APP_URL` e `APP_PUBLIC_PATH` no `.env`.

## 🌐 Como Usar

### Acessar o Sistema

Abra seu navegador e acesse:
```
http://localhost/getxml/
```

**Nota**: O sistema redireciona automaticamente para `/public/` via `.htaccess`. Não é necessário adicionar `/public` na URL manualmente.

### Usuários Padrão

Após a instalação, você terá acesso a:

- **Admin**: `admin@getxml.com` / `admin123`
- **Contador 1**: `contador1@teste.com` / `contador123`
- **Contador 2**: `contador2@teste.com` / `contador123`

⚠️ **IMPORTANTE**: Altere as senhas padrão em produção!

### Fluxo de Uso

1. **Login**: Acesse com suas credenciais
2. **Perfil**: Configure CNPJ e IE
3. **Certificado**: Faça upload do certificado digital (ou configure no .env)
4. **Buscar Notas**: Consulte notas na SEFAZ
5. **Gerenciar**: Liste e gerencie suas notas

### Configuração do Certificado Digital

Você tem **duas opções** para configurar o certificado digital:

#### Opção 1: Upload via Interface (Recomendado) ✅
1. Faça login no sistema
2. Acesse "Certificados" no menu
3. Faça upload do seu certificado (.pfx ou .p12)
4. Informe a senha do certificado (com botão para mostrar/ocultar)
5. Selecione a UF da SEFAZ
6. O sistema usará automaticamente este certificado

**Vantagens:**
- ✅ Não precisa editar arquivos de configuração
- ✅ Cada usuário pode ter seu próprio certificado
- ✅ Interface amigável com validação
- ✅ Botão com ícone SVG para mostrar/ocultar senha

#### Opção 2: Configuração via .env
Edite o arquivo `.env` e adicione:
```env
SEFAZ_CERTIFICADO=C:\caminho\completo\certificado.pfx
SEFAZ_SENHA_CERTIFICADO=SuaSenha123
```

**Nota**: O sistema prioriza o certificado uploadado via interface. Se não houver certificado uploadado, usa o configurado no .env.

## 👤 Papéis de Usuário

### Administrador
- Acesso ao painel administrativo
- Criar e gerenciar usuários
- Ativar/desativar usuários
- Acesso total ao sistema

### Contador
- Acesso ao dashboard pessoal
- Upload de certificados
- Buscar notas fiscais
- Gerenciar suas notas
- Alterar dados pessoais

## 📁 Estrutura do Projeto

```
getxml/
├── app/
│   ├── Controllers/          # Controladores
│   ├── Models/             # Modelos de dados
│   ├── Views/              # Templates HTML
│   ├── Core/               # Sistema principal
│   ├── Helpers/            # Funções utilitárias
│   └── Middleware/         # Middleware de segurança
├── database/               # Scripts do banco
├── public/                 # Arquivos públicos
├── storage/                # Armazenamento
└── vendor/                 # Dependências
```

## 📊 Banco de Dados

### Tabelas

- **usuarios**: Dados dos usuários
- **certificados**: Certificados digitais por usuário
- **notas_fiscais**: Notas fiscais capturadas
- **logs_sistema**: Logs de ações
- **configuracoes**: Configurações do sistema

### Script de Manipulação do Banco de Dados

Para gerenciar o banco de dados manualmente, utilize o script `database/manipulacao_banco.sql`. Este script permite:

- ✅ Criar o banco de dados completo do zero
- ✅ Gerenciar usuários (criar, ativar, desativar, alterar senhas)
- ✅ Gerenciar certificados digitais
- ✅ Operações com notas fiscais
- ✅ Consultas e relatórios
- ✅ Limpeza de dados e logs
- ✅ Configuração de ambiente (produção/homologação)

#### Como usar o script:

1. Abra seu cliente MySQL preferido (phpMyAdmin, MySQL Workbench, DBeaver, etc.)
2. Abra o arquivo `database/manipulacao_banco.sql`
3. Descomente a operação desejada (remova os `--` no início das linhas)
4. Execute o script

#### Operações principais disponíveis no script:

**Criação do banco:**
- Seção 1: Criação completa do banco de dados e tabelas
- Seção 2: Operações de manutenção (backup, restore, limpeza)
- Seção 3: Gerenciamento de usuários
- Seção 4: Gerenciamento de certificados
- Seção 5: Operações com notas fiscais
- Seção 6: Gerenciamento de logs
- Seção 7: Configurações do sistema
- Seção 8: Consultas úteis e relatórios
- Seção 9: Alternar entre produção e homologação
- Seção 10: Segurança em produção

⚠️ **IMPORTANTE**: Em produção, sempre altere as senhas padrão após a instalação!

## 🔒 Segurança

- Autenticação segura com hash de senhas
- Upload validado de certificados
- Proteção CSRF
- Isolamento de dados por usuário
- Logs de todas as ações

## 📚 Documentação Adicional

- **GUIA_NOVO_SISTEMA.md**: Guia completo do novo sistema
- **INSTALACAO.md**: Guia de instalação detalhado
- **CERTIFICADO.md**: Guia de certificados digitais
- **CONFIGURACAO_ENV.md**: Configuração do .env
- **EXEMPLO_USO.md**: Exemplos práticos de uso
- **ANALISE_SEFAZ.md**: Análise de conformidade com documentação SEFAZ

## ✅ Conformidade com SEFAZ

O sistema está em conformidade total com a documentação oficial da SEFAZ:

- ✅ **Assinatura digital XML** conforme padrão XML-DSig
- ✅ **Leiaute versão 1.01** compatível com schemas atuais
- ✅ **Paginação por NSU** conforme Nota Técnica 2014.002
- ✅ **Suporte a produção e homologação** (tpAmb 1 e 2)
- ✅ **Suporte a CNPJ e CPF** para consulta
- ✅ **Todos os tipos de consulta**: distNSU, consNSU, consChNFe
- ✅ **URLs para todos os estados** brasileiros
- ✅ **Processamento completo** de respostas (notas, resumos, eventos)
- ✅ **Autenticação SSL mútua** com certificado A1

Para detalhes técnicos, consulte `ANALISE_SEFAZ.md`.

## 🐛 Solução de Problemas

### Erro: "Banco de dados não disponível"

Verifique se o MySQL está rodando e as credenciais no `.env` estão corretas.

### Erro: "Usuário ou senha incorretos"

Use os usuários de teste ou peça ao admin para verificar seu status.

### Erro: "Erro ao fazer upload"

Verifique se o arquivo é .pfx ou .p12 e se tem menos de 10MB.

## 🎉 Pronto para Usar!

O sistema está completo e pronto para uso. Configure seu certificado digital e comece a capturar notas fiscais da SEFAZ!

---

**Desenvolvido com ❤️ para simplificar a captura de notas fiscais da SEFAZ**
