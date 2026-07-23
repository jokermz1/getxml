# Instruções para Configurar o MySQL

## 📝 Execute o SQL Manualmente

Como o usuário MySQL ainda não existe, você precisa criar manualmente. Execute este SQL:

### Opção 1: Via phpMyAdmin
1. Acesse phpMyAdmin em `http://localhost/phpmyadmin`
2. Clique na aba "SQL"
3. Cole e execute o seguinte código:

```sql
CREATE DATABASE IF NOT EXISTS getxml CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'getxml'@'localhost' IDENTIFIED BY 'gX7#kLp$2Qz!vN9@';
GRANT ALL PRIVILEGES ON getxml.* TO 'getxml'@'localhost';
FLUSH PRIVILEGES;
```

### Opção 2: Via Linha de Comando
```bash
mysql -u root -p
```

Depois de fazer login, execute:
```sql
CREATE DATABASE IF NOT EXISTS getxml CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'getxml'@'localhost' IDENTIFIED BY 'gX7#kLp$2Qz!vN9@';
GRANT ALL PRIVILEGES ON getxml.* TO 'getxml'@'localhost';
FLUSH PRIVILEGES;
```

### Opção 3: Via MySQL Workbench
1. Abra MySQL Workbench
2. Conecte como root
3. Execute o SQL acima

## 🚀 Após Criar o Usuário

Depois de criar o usuário e banco, execute:

```bash
php database/install.php
```

Isso irá criar as tabelas e usuários padrão do sistema.

## ✅ Verificação

Para verificar se funcionou, tente acessar:
```
http://localhost/getxml/public/
```

Você deve ver a página de login.

## 🔐 Credenciais Padrão

Após a instalação, use:
- **Admin**: `admin@getxml.com` / `admin123`
- **Contador 1**: `contador1@teste.com` / `contador123`
- **Contador 2**: `contador2@teste.com` / `contador123`

⚠️ **IMPORTANTE**: Altere estas senhas após o primeiro acesso!
