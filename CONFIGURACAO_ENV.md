# Configuração do Arquivo .env

## Problema Comum

O PhpDotenv não aceita espaços nos valores das variáveis de ambiente. O arquivo `.env` original tinha espaços nos valores, o que causou erro de parsing.

## Solução

Use o arquivo `.env.teste` como modelo e renomeie para `.env` após configurar:

```env
APP_NAME=GetXML_SEFAZ
APP_ENV=development
APP_DEBUG=true
SEFAZ_UF=SP
SEFAZ_AMBIENTE=2
SEFAZ_CERTIFICADO=
SEFAZ_SENHA_CERTIFICADO=
CNPJ_CNPJ=
CNPJ_IE=
DATA_INICIO=
DATA_FIM=
STORAGE_PATH=storage/xmls
```

## Preenchendo as Variáveis

### 1. APP_NAME
Nome da aplicação (sem espaços, use underscores):
```env
APP_NAME=GetXML_SEFAZ
```

### 2. APP_ENV
Ambiente de execução:
```env
APP_ENV=development    # Para desenvolvimento
APP_ENV=production     # Para produção
```

### 3. APP_DEBUG
Modo de debug:
```env
APP_DEBUG=true   # Mostra erros detalhados (desenvolvimento)
APP_DEBUG=false  # Esconde erros (produção)
```

### 4. APP_URL
URL base do sistema:
```env
APP_URL=http://localhost/getxml/public
```

### 5. APP_PUBLIC_PATH
Caminho da pasta pública:
```env
APP_PUBLIC_PATH=/public
```

**Nota**: O sistema usa `.htaccess` para redirecionar automaticamente para `/public/`. Não é necessário adicionar `/public` na URL manualmente.

### 4. SEFAZ_UF
Estado da SEFAZ:
```env
SEFAZ_UF=SP    # São Paulo
SEFAZ_UF=MG    # Minas Gerais
SEFAZ_UF=PR    # Paraná
# ... etc
```

### 5. SEFAZ_AMBIENTE
Ambiente da SEFAZ:
```env
SEFAZ_AMBIENTE=1    # Produção
SEFAZ_AMBIENTE=2    # Homologação
```

### 6. SEFAZ_CERTIFICADO
Caminho completo do certificado digital (sem espaços):
```env
SEFAZ_CERTIFICADO=C:\certificados\meu_certificado.pfx
```

### 7. SEFAZ_SENHA_CERTIFICADO
Senha do certificado (sem espaços especiais):
```env
SEFAZ_SENHA_CERTIFICADO=SuaSenha123
```

### 8. CNPJ_CNPJ
CNPJ (apenas números ou com formatação, sem espaços):
```env
CNPJ_CNPJ=12345678000190
# ou
CNPJ_CNPJ=12.345.678/0001-90
```

### 9. CNPJ_IE
Inscrição Estadual:
```env
CNPJ_IE=123456789
```

### 10. DATA_INICIO e DATA_FIM
Período de captura (opcional):
```env
DATA_INICIO=2024-01-01
DATA_FIM=2024-12-31
```

### 11. STORAGE_PATH
Caminho para armazenamento dos XMLs:
```env
STORAGE_PATH=storage/xmls
```

### 12. Configurações do Banco de Dados
Credenciais do MySQL:
```env
DB_HOST=localhost
DB_DATABASE=getxml
DB_USERNAME=getxml
DB_PASSWORD=gX7#kLp$2Qz!vN9@@@
```

⚠️ **IMPORTANTE**: Não use o usuário root em produção. Crie um usuário dedicado como mostrado acima.

## Comandos para Configurar

### Windows PowerShell
```powershell
# Copiar arquivo de exemplo
Copy-Item .env.teste .env

# Editar o arquivo (usando notepad)
notepad .env
```

### Windows CMD
```cmd
# Copiar arquivo de exemplo
copy .env.teste .env

# Editar o arquivo
notepad .env
```

## Validação

Após configurar, execute o teste para validar:

```bash
php test.php
```

Todos os testes devem passar (exceto CNPJ configurado que é opcional até você preencher).

## Erros Comuns

### Erro: "Failed to parse dotenv file"
**Causa**: Espaços nos valores ou linhas em branco extras
**Solução**: Remova espaços extras e linhas em branco

### Erro: "CNPJ não configurado"
**Causa**: Variável CNPJ_CNPJ vazia
**Solução**: Preencha com seu CNPJ

### Erro: "Caminho do certificado não encontrado"
**Causa**: Caminho do certificado incorreto
**Solução**: Use caminho absoluto e verifique se o arquivo existe

## Dicas

1. **Sempre use underscores em vez de espaços** nos valores
2. **Use caminhos absolutos** para arquivos
3. **Não use caracteres especiais** em senhas se possível
4. **Mantenha o .env fora do controle de versão** (já está no .gitignore)
5. **Faça backup** do arquivo .env após configurar

## Exemplo Completo Configurado

```env
APP_NAME=GetXML_SEFAZ
APP_ENV=development
APP_DEBUG=true
SEFAZ_UF=SP
SEFAZ_AMBIENTE=2
SEFAZ_CERTIFICADO=C:\Users\Usuario\certificados\sefaz.pfx
SEFAZ_SENHA_CERTIFICADO=MinhaSenhaSegura123
CNPJ_CNPJ=12.345.678/0001-90
CNPJ_IE=123456789
DATA_INICIO=2024-01-01
DATA_FIM=2024-12-31
STORAGE_PATH=storage/xmls
DB_HOST=localhost
DB_DATABASE=getxml
DB_USERNAME=getxml
DB_PASSWORD=gX7#kLp$2Qz!vN9@@@
```

Após configurar, acesse: `http://localhost/getxml/public/`
