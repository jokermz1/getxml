# Guia de InstalaÃ§Ã£o e ConfiguraÃ§Ã£o Detalhada

## 1. ConfiguraÃ§Ã£o do Certificado Digital

### Obtendo o Certificado Digital

Para acessar os serviÃ§os da SEFAZ, vocÃª precisa de um certificado digital A1 vÃ¡lido. Este certificado pode ser obtido junto a autoridades certificadoras como:

- Serasa
- Certisign
- Caixa EconÃ´mica Federal
- Outras autoridades certificadoras autorizadas

### Convertendo Certificado (se necessÃ¡rio)

Se vocÃª recebeu o certificado em outro formato, pode precisar convertÃª-lo para .pfx:

```bash
# Exemplo usando OpenSSL (se disponÃ­vel)
openssl pkcs12 -export -out certificado.pfx -inkey chave_privada.pem -in certificado.pem
```

### Configurando no Sistema

1. Coloque seu arquivo `.pfx` ou `.p12` em um local seguro no servidor
2. Edite o arquivo `.env` e configure:
```env
SEFAZ_CERTIFICADO=C:\caminho\completo\certificado.pfx
SEFAZ_SENHA_CERTIFICADO=sua_senha_aqui
```

## 2. ConfiguraÃ§Ã£o do CNPJ

### CNPJ do Contador

Se vocÃª Ã© um contador e precisa acessar notas de vÃ¡rios clientes:

1. Configure o CNPJ do seu escritÃ³rio no `.env`:
```env
CNPJ_CNPJ=00.000.000/0000-00
CNPJ_IE=123456789
```

2. Certifique-se de que o certificado digital estÃ¡ vinculado a este CNPJ

### Acessando Notas de Clientes

Para acessar notas de clientes especÃ­ficos, vocÃª precisarÃ¡:

1. Ter procuraÃ§Ã£o eletrÃ´nica cadastrada na SEFAZ
2. Ou o cliente deve ter autorizado seu CNPJ a acessar as notas

## 3. ConfiguraÃ§Ã£o do Apache

### Habilitando mod_rewrite

Certifique-se de que o mÃ³dulo rewrite do Apache estÃ¡ habilitado:

1. Edite `httpd.conf` ou `apache2.conf`
2. Descomente a linha:
```apache
LoadModule rewrite_module modules/mod_rewrite.so
```
3. Reinicie o Apache

### ConfiguraÃ§Ã£o de Virtual Host (Opcional)

Para uma configuraÃ§Ã£o mais profissional, adicione um virtual host:

```apache
<VirtualHost *:80>
    ServerName getxml.local
    DocumentRoot "C:/xampp/htdocs/getxml/public"
    
    <Directory "C:/xampp/htdocs/getxml/public">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

Adicione ao arquivo `hosts` do Windows:
```
127.0.0.1 getxml.local
```

## 4. ConfiguraÃ§Ã£o de PermissÃµes

### Windows

Certifique-se de que o diretÃ³rio `storage/xmls` tem permissÃµes de escrita:

1. Clique com o botÃ£o direito no diretÃ³rio `storage/xmls`
2. Propriedades â†’ SeguranÃ§a
3. Adicione permissÃ£o de escrita para o usuÃ¡rio do IIS ou Apache

### Linux

```bash
chmod -R 755 storage/xmls
chown -R www-data:www-data storage/xmls
```

## 5. Testando a InstalaÃ§Ã£o

### Verificando DependÃªncias

Acesse no navegador:
```
http://localhost/getxml/public/
```

VocÃª deve ver a pÃ¡gina inicial do sistema.

### Testando a ConfiguraÃ§Ã£o

1. Acesse "ConfiguraÃ§Ãµes" no menu
2. Verifique se todas as configuraÃ§Ãµes estÃ£o corretas
3. Se houver erro, revise o arquivo `.env`

### Testando a Busca de Notas

1. Acesse "Buscar Notas"
2. Informe um perÃ­odo (ex: 01/01/2024 a 31/01/2024)
3. Clique em "Buscar Notas"
4. Se funcionar, vocÃª verÃ¡ as notas encontradas

## 6. SoluÃ§Ã£o de Problemas Comuns

### Erro: "Cannot open cert file"

**Causa**: Caminho do certificado incorreto ou arquivo nÃ£o existe

**SoluÃ§Ã£o**:
- Verifique se o caminho no `.env` estÃ¡ correto
- Use caminhos absolutos (ex: `C:\certificados\meu_cert.pfx`)
- Verifique se o arquivo existe e tem a extensÃ£o correta

### Erro: "Cannot load cert file"

**Causa**: Senha do certificado incorreta ou formato invÃ¡lido

**SoluÃ§Ã£o**:
- Verifique a senha no `.env`
- Tente abrir o arquivo .pfx no Windows para confirmar a senha
- Verifique se o certificado Ã© vÃ¡lido (nÃ£o expirou)

### Erro: "cURL error 60"

**Causa**: Problema com certificado SSL

**SoluÃ§Ã£o**:
- O sistema usa verificacao TLS segura por padrao e aceita `SEFAZ_CA_BUNDLE` para validar o certificado do servidor
- Se persistir, verifique a configuraÃ§Ã£o do PHP para cURL

### Erro: "Timeout"

**Causa**: ServiÃ§o da SEFAZ demorando a responder

**SoluÃ§Ã£o**:
- Aumente o timeout em `app/Models/SefazModel.php`
- Tente acessar em horÃ¡rios de menor movimento
- Verifique sua conexÃ£o com a internet

## 7. Ambiente de ProduÃ§Ã£o

### ConfiguraÃ§Ãµes de SeguranÃ§a

Para ambiente de produÃ§Ã£o:

1. Altere no `.env`:
```env
APP_ENV=production
APP_DEBUG=false
```

2. Configure o certificado para produÃ§Ã£o:
```env
SEFAZ_AMBIENTE=1
```

3. Configure permissÃµes adequadas nos diretÃ³rios

4. Configure HTTPS no servidor web

### Backup

Implemente rotinas de backup para:

- Arquivos XML em `storage/xmls`
- Banco de dados local (`storage/notas_fiscais.json`)
- Arquivo de configuraÃ§Ã£o `.env`

## 8. IntegraÃ§Ã£o com Outros Sistemas

### API REST (Futura)

VocÃª pode estender o sistema para criar uma API REST:

```php
// Exemplo futuro em app/Controllers/ApiController.php
public function apiNotas()
{
    header('Content-Type: application/json');
    $notas = $this->notaFiscalModel->listarNotas();
    echo json_encode($notas);
}
```

### IntegraÃ§Ã£o ContÃ¡bil

Os XMLs capturados podem ser:

- Importados por sistemas contÃ¡beis
- Processados para extraÃ§Ã£o de dados
- Integrados com ERP

## 9. AtualizaÃ§Ã£o do Sistema

### Atualizando DependÃªncias

```bash
composer update
```

### Backup Antes de Atualizar

Sempre faÃ§a backup antes de atualizar:

1. Copie o diretÃ³rio `storage/xmls`
2. Copie o arquivo `.env`
3. Copie qualquer customizaÃ§Ã£o que vocÃª tenha feito

## 10. Suporte

Para dÃºvidas especÃ­ficas da SEFAZ do seu estado, consulte:

- Portal da SEFAZ do seu estado
- DocumentaÃ§Ã£o tÃ©cnica da NFe
- Suporte da autoridade certificadora

---

**Dica**: Mantenha este arquivo atualizado com suas prÃ³prias anotaÃ§Ãµes e soluÃ§Ãµes de problemas que vocÃª encontrar.
