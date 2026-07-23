# GetXML SEFAZ - Sistema de Captura de Notas Fiscais

Sistema MVC em PHP para captura e gerenciamento de XMLs de notas fiscais da SEFAZ (Secretaria da Fazenda). Desenvolvido com Composer, seguindo o padrão Model-View-Controller.

## 🎯 Características

- ✅ **Autenticação de usuários** (admin e contadores)
- ✅ **Upload de certificados digitais** por usuário
- ✅ **MySQL** para persistência de dados
- ✅ **Painel administrativo** para gerenciamento
- ✅ **Isolamento de dados** por usuário
- ✅ **Busca de notas** na SEFAZ
- ✅ **Gerenciamento de XMLs** personalizados
- ✅ **Interface web responsiva**

## 📋 Pré-requisitos

- PHP >= 7.4
- Composer
- MySQL/MariaDB
- Servidor web (Apache, Nginx, ou XAMPP)
- Certificado digital A1 (pfx/p12) para acesso aos serviços da SEFAZ

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
DB_USERNAME=root
DB_PASSWORD=
```

5. Instale o banco de dados:
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

# Configurações SEFAZ
SEFAZ_UF=SP                    # UF do estado (SP, MG, PR, etc)
SEFAZ_AMBIENTE=2               # 1=Produção, 2=Homologação
SEFAZ_CERTIFICADO=             # Opcional (usado se não tiver certificado por usuário)
SEFAZ_SENHA_CERTIFICADO=      # Opcional (usado se não tiver certificado por usuário)

# Configurações MySQL
DB_HOST=localhost
DB_DATABASE=getxml
DB_USERNAME=root
DB_PASSWORD=

# CNPJ para captura das notas (opcional)
CNPJ_CNPJ=
CNPJ_IE=

# Período de captura (opcional)
DATA_INICIO=
DATA_FIM=

# Configurações de armazenamento
STORAGE_PATH=storage/xmls
```

## 🌐 Como Usar

### Acessar o Sistema

Abra seu navegador e acesse:
```
http://localhost/getxml/public/
```

### Usuários Padrão

Após a instalação, você terá acesso a:

- **Admin**: `admin@getxml.com` / `admin123`
- **Contador 1**: `contador1@teste.com` / `contador123`
- **Contador 2**: `contador2@teste.com` / `contador123`

⚠️ **IMPORTANTE**: Altere as senhas padrão em produção!

### Fluxo de Uso

1. **Login**: Acesse com suas credenciais
2. **Perfil**: Configure CNPJ e IE
3. **Certificado**: Faça upload do certificado digital
4. **Buscar Notas**: Consulte notas na SEFAZ
5. **Gerenciar**: Liste e gerencie suas notas

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
