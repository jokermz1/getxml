# Guia de Instalação e Configuração Detalhada

## 1. Configuração do Certificado Digital

### Obtendo o Certificado Digital

Para acessar os serviços da SEFAZ, você precisa de um certificado digital A1 válido. Este certificado pode ser obtido junto a autoridades certificadoras como:

- Serasa
- Certisign
- Caixa Econômica Federal
- Outras autoridades certificadoras autorizadas

### Convertendo Certificado (se necessário)

Se você recebeu o certificado em outro formato, pode precisar convertê-lo para .pfx:

```bash
# Exemplo usando OpenSSL (se disponível)
openssl pkcs12 -export -out certificado.pfx -inkey chave_privada.pem -in certificado.pem
```

### Configurando no Sistema

1. Coloque seu arquivo `.pfx` ou `.p12` em um local seguro no servidor
2. Edite o arquivo `.env` e configure:
```env
SEFAZ_CERTIFICADO=C:\caminho\completo\certificado.pfx
SEFAZ_SENHA_CERTIFICADO=sua_senha_aqui
```

## 2. Configuração do CNPJ

### CNPJ do Contador

Se você é um contador e precisa acessar notas de vários clientes:

1. Configure o CNPJ do seu escritório no `.env`:
```env
CNPJ_CNPJ=00.000.000/0000-00
CNPJ_IE=123456789
```

2. Certifique-se de que o certificado digital está vinculado a este CNPJ

### Acessando Notas de Clientes

Para acessar notas de clientes específicos, você precisará:

1. Ter procuração eletrônica cadastrada na SEFAZ
2. Ou o cliente deve ter autorizado seu CNPJ a acessar as notas

## 3. Configuração do Apache

### Habilitando mod_rewrite

Certifique-se de que o módulo rewrite do Apache está habilitado:

1. Edite `httpd.conf` ou `apache2.conf`
2. Descomente a linha:
```apache
LoadModule rewrite_module modules/mod_rewrite.so
```
3. Reinicie o Apache

### Configuração de Virtual Host (Opcional)

Para uma configuração mais profissional, adicione um virtual host:

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

## 4. Configuração de Permissões

### Windows

Certifique-se de que o diretório `storage/xmls` tem permissões de escrita:

1. Clique com o botão direito no diretório `storage/xmls`
2. Propriedades → Segurança
3. Adicione permissão de escrita para o usuário do IIS ou Apache

### Linux

```bash
chmod -R 755 storage/xmls
chown -R www-data:www-data storage/xmls
```

## 5. Testando a Instalação

### Verificando Dependências

Acesse no navegador:
```
http://localhost/getxml/public/
```

Você deve ver a página inicial do sistema.

### Testando a Configuração

1. Acesse "Configurações" no menu
2. Verifique se todas as configurações estão corretas
3. Se houver erro, revise o arquivo `.env`

### Testando a Busca de Notas

1. Acesse "Buscar Notas"
2. Informe um período (ex: 01/01/2024 a 31/01/2024)
3. Clique em "Buscar Notas"
4. Se funcionar, você verá as notas encontradas

## 6. Solução de Problemas Comuns

### Erro: "Cannot open cert file"

**Causa**: Caminho do certificado incorreto ou arquivo não existe

**Solução**:
- Verifique se o caminho no `.env` está correto
- Use caminhos absolutos (ex: `C:\certificados\meu_cert.pfx`)
- Verifique se o arquivo existe e tem a extensão correta

### Erro: "Cannot load cert file"

**Causa**: Senha do certificado incorreta ou formato inválido

**Solução**:
- Verifique a senha no `.env`
- Tente abrir o arquivo .pfx no Windows para confirmar a senha
- Verifique se o certificado é válido (não expirou)

### Erro: "cURL error 60"

**Causa**: Problema com certificado SSL

**Solução**:
- O sistema já configura `verify => false` no Guzzle
- Se persistir, verifique a configuração do PHP para cURL

### Erro: "Timeout"

**Causa**: Serviço da SEFAZ demorando a responder

**Solução**:
- Aumente o timeout em `app/Models/SefazModel.php`
- Tente acessar em horários de menor movimento
- Verifique sua conexão com a internet

## 7. Ambiente de Produção

### Configurações de Segurança

Para ambiente de produção:

1. Altere no `.env`:
```env
APP_ENV=production
APP_DEBUG=false
```

2. Configure o certificado para produção:
```env
SEFAZ_AMBIENTE=1
```

3. Configure permissões adequadas nos diretórios

4. Configure HTTPS no servidor web

### Backup

Implemente rotinas de backup para:

- Arquivos XML em `storage/xmls`
- Banco de dados local (`storage/notas_fiscais.json`)
- Arquivo de configuração `.env`

## 8. Integração com Outros Sistemas

### API REST (Futura)

Você pode estender o sistema para criar uma API REST:

```php
// Exemplo futuro em app/Controllers/ApiController.php
public function apiNotas()
{
    header('Content-Type: application/json');
    $notas = $this->notaFiscalModel->listarNotas();
    echo json_encode($notas);
}
```

### Integração Contábil

Os XMLs capturados podem ser:

- Importados por sistemas contábeis
- Processados para extração de dados
- Integrados com ERP

## 9. Atualização do Sistema

### Atualizando Dependências

```bash
composer update
```

### Backup Antes de Atualizar

Sempre faça backup antes de atualizar:

1. Copie o diretório `storage/xmls`
2. Copie o arquivo `.env`
3. Copie qualquer customização que você tenha feito

## 10. Suporte

Para dúvidas específicas da SEFAZ do seu estado, consulte:

- Portal da SEFAZ do seu estado
- Documentação técnica da NFe
- Suporte da autoridade certificadora

---

**Dica**: Mantenha este arquivo atualizado com suas próprias anotações e soluções de problemas que você encontrar.
