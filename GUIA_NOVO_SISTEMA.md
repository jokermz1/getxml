# 🚀 Guia do Novo Sistema GetXML SEFAZ

## 🎯 Novidades do Sistema

O sistema foi expandido para incluir:
- ✅ **MySQL** para persistência de dados
- ✅ **Autenticação de usuários** (admin e contadores)
- ✅ **Upload de certificados** por usuário
- ✅ **Painel administrativo**
- ✅ **Certificados personalizados** por usuário
- ✅ **Isolamento de dados** por usuário

## 📁 Estrutura Atualizada

```
getxml/
├── app/
│   ├── Controllers/
│   │   ├── SefazController.php        # Controlador SEFAZ
│   │   └── AuthController.php         # Controlador Autenticação
│   ├── Models/
│   │   ├── SefazModel.php             # Modelo SEFAZ
│   │   ├── NotaFiscalModel.php        # Modelo Notas (MySQL)
│   │   └── UsuarioModel.php           # Modelo Usuários
│   ├── Views/
│   │   ├── login.php                  # Login
│   │   ├── dashboard.php              # Dashboard usuário
│   │   ├── perfil.php                 # Perfil do usuário
│   │   ├── upload_certificado.php     # Upload de certificados
│   │   ├── alterar_senha.php          # Alterar senha
│   │   ├── admin.php                  # Painel admin
│   │   └── admin_criar_usuario.php    # Criar usuário
│   ├── Core/
│   │   ├── Database.php               # Conexão MySQL
│   │   ├── Auth.php                   # Sistema de autenticação
│   │   ├── Router.php                 # Sistema de rotas
│   │   ├── Logger.php                 # Sistema de logs
│   │   └── Validator.php              # Validação
│   ├── Helpers/
│   │   ├── Helper.php                 # Funções utilitárias
│   │   └── UploadHelper.php           # Helper de upload
│   └── Middleware/
│       └── SecurityMiddleware.php    # Middleware de segurança
├── database/
│   ├── schema.sql                     # Schema do banco
│   └── install.php                    # Script de instalação
├── storage/
│   ├── xmls/                          # XMLs (por usuário)
│   ├── certificados/                  # Certificados (por usuário)
│   └── logs/                          # Logs do sistema
└── ... (outros arquivos)
```

## 🔧 Instalação do MySQL

### 1. Configurar o .env

Adicione as configurações do MySQL ao arquivo `.env`:

```env
DB_HOST=localhost
DB_DATABASE=getxml
DB_USERNAME=getxml
DB_PASSWORD=gX7#kLp$2Qz!vN9@@@
```

### 2. Criar o Banco de Dados

Execute o script de instalação:

```bash
php database/install.php
```

Este script irá:
- Criar o banco de dados `getxml`
- Criar todas as tabelas necessárias
- Inserir usuários de teste
- Inserir configurações padrão

### 3. Usuários Padrão

Após a instalação, você terá acesso a:

- **Admin**: `admin@getxml.com` / `admin123`
- **Contador 1**: `contador1@teste.com` / `contador123`
- **Contador 2**: `contador2@teste.com` / `contador123`

⚠️ **IMPORTANTE**: Altere as senhas padrão em produção!

## 🌐 Acesso ao Sistema

### 1. Acessar

```
http://localhost/getxml/
```

**Nota**: O sistema redireciona automaticamente para `/public/` via `.htaccess.

### 2. Fazer Login

Use um dos usuários padrão ou crie novos usuários através do painel admin.

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

## 📤 Upload de Certificados

### Processo

1. Acesse o dashboard
2. Clique em "Gerenciar Certificados"
3. Faça upload do arquivo `.pfx` ou `.p12`
4. Informe a senha do certificado
5. Selecione a UF da SEFAZ
6. O certificado será salvo no diretório pessoal

### Localização

Os certificados são salvos em:
```
storage/certificados/{usuario_id}/cert_{timestamp}_{usuario_id}.pfx
```

### Segurança

- Cada usuário tem seu próprio diretório
- Senhas dos certificados são criptografadas no banco
- Arquivos são validados antes do upload
- Tamanho máximo: 10MB

## 🔐 Sistema de Autenticação

### Funcionalidades

- Login seguro com hash de senhas
- Sessão com expiração configurável
- Proteção CSRF
- Rate limiting básico
- Logs de todas as ações

### Requisitos de Senha

- Mínimo 6 caracteres
- Recomendado: letras maiúsculas, minúsculas, números e símbolos

## 📊 Banco de Dados

### Tabelas Criadas

1. **usuarios**: Dados dos usuários
2. **certificados**: Certificados digitais por usuário
3. **notas_fiscais**: Notas fiscais capturadas
4. **logs_sistema**: Logs de ações do sistema
5. **configuracoes**: Configurações do sistema

### Relacionamentos

- Um usuário pode ter múltiplos certificados
- Um certificado pertence a um usuário
- Uma nota fiscal pertence a um usuário
- Logs são vinculados aos usuários

## 🎯 Fluxo de Uso

### Para Contadores

1. **Login**: Acesse com suas credenciais
2. **Perfil**: Configure CNPJ e IE
3. **Certificado**: Faça upload do certificado digital
4. **Buscar Notas**: Consulte notas na SEFAZ
5. **Gerenciar**: Liste e gerencie suas notas

### Para Administradores

1. **Login**: Acesse como admin
2. **Painel Admin**: Gerencie usuários
3. **Criar Usuários**: Adicione novos contadores
4. **Gerenciar**: Ative/desative usuários conforme necessário

## 🔧 Configurações Avançadas

### Alterar Senha do Admin

```bash
php -r "echo password_hash('nova_senha', PASSWORD_BCRYPT);"
```

Copie o hash e atualize no banco de dados.

### Configurar Timeout de Sessão

No arquivo `app/Core/Auth.php`, altere o valor em `verificarExpiracao()`:

```php
public function verificarExpiracao($tempoMaximo = 3600) // 1 hora em segundos
```

### Limpar Logs Antigos

O sistema pode ser configurado para limpar logs automaticamente. Adicione ao cron:

```bash
php -r "require 'vendor/autoload.php'; require 'config/config.php'; \$logger = new App\Core\Logger('storage/logs', 'teste'); \$logger->limparLogsAntigos(30);"
```

## 🐛 Solução de Problemas

### Erro: "Banco de dados não disponível"

**Causa**: MySQL não está configurado ou não está rodando

**Solução**:
1. Verifique se o MySQL está rodando
2. Verifique as credenciais no `.env`
3. Execute o script de instalação: `php database/install.php`

### Erro: "Usuário ou senha incorretos"

**Causa**: Credenciais incorretas ou usuário inativo

**Solução**:
1. Verifique email e senha
2. Peça ao admin para verificar se está ativo
3. Use um dos usuários de teste

### Erro: "Erro ao fazer upload"

**Causa**: Arquivo inválido ou muito grande

**Solução**:
1. Verifique se é .pfx ou .p12
2. Verifique tamanho (máximo 10MB)
3. Verifique se a senha do certificado está correta

### Erro: "Certificado não encontrado"

**Causa**: Certificado não foi configurado ou está inativo

**Solução**:
1. Acesse "Gerenciar Certificados"
2. Verifique se há um certificado ativo
3. Faça upload de um novo certificado

## 📈 Próximos Passos

### Funcionalidades Futuras

- [ ] API REST para integração externa
- [ ] Webhooks para notificações
- [ ] Exportação em massa
- [ ] Relatórios personalizados
- [ ] Integração com sistemas contábeis
- [ ] Automação de buscas agendadas

### Melhorias Sugeridas

- Autenticação de dois fatores
- Integração com OAuth
- Validação avançada de CNPJ
- Sistema de notificações
- Dashboard analítico avançado

## 📚 Documentação Adicional

- **README.md**: Documentação geral
- **INSTALACAO.md**: Guia de instalação detalhado
- **CERTIFICADO.md**: Guia de certificados digitais
- **CONFIGURACAO_ENV.md**: Configuração do .env
- **RESUMO_PROJETO.md**: Resumo do projeto

## 🎉 Conclusão

O sistema agora está completo com:
- Autenticação robusta
- Gerenciamento de usuários
- Upload de certificados personalizados
- Persistência em MySQL
- Painel administrativo
- Isolamento de dados por usuário

Aproveite todas as funcionalidades do sistema GetXML SEFAZ! 🚀
