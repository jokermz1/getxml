# GetXML SEFAZ - Sistema de Captura de Notas Fiscais

Sistema MVC em PHP para captura e gerenciamento de XMLs de notas fiscais da SEFAZ.

## Caracteristicas

- Autenticacao de usuarios
- Upload de certificados digitais por usuario
- Busca de notas na SEFAZ via SOAP/TLS com certificado digital
- Gerenciamento de XMLs locais
- Interface web responsiva
- Paginação por NSU
- Suporte a todos os estados brasileiros

## Requisitos

- PHP >= 7.4
- Composer
- MySQL/MariaDB
- Certificado digital A1 em `.pfx` ou `.p12`
- Extensoes PHP: `openssl`, `xml`, `mbstring`

## Configuracao

Edite o arquivo `.env` na raiz do projeto:

```env
APP_NAME=GetXML_SEFAZ
APP_ENV=development
APP_DEBUG=true

APP_URL=http://localhost/getxml/public
APP_PUBLIC_PATH=/public

SEFAZ_UF=SP
SEFAZ_AMBIENTE=2
SEFAZ_CERTIFICADO=
SEFAZ_SENHA_CERTIFICADO=
SEFAZ_CA_BUNDLE=

DB_HOST=localhost
DB_DATABASE=getxml
DB_USERNAME=getxml
DB_PASSWORD=sua_senha

CNPJ_CNPJ=
CNPJ_IE=

DATA_INICIO=
DATA_FIM=

STORAGE_PATH=storage/xmls
```

## Uso

1. Instale as dependencias com `composer install`
2. Configure o `.env`
3. Execute a instalacao do banco com `php database/install.php`
4. Acesse `http://localhost/getxml/`

### Teste de homologacao

- Execute `php test_homologacao_sefaz.php` para validar o envelope SOAP e o parser local
- Defina `SEFAZ_LIVE_TEST=1` no `.env` para tentar uma chamada real em homologacao
- Se quiser consultar uma chave especifica, configure `SEFAZ_TEST_CHAVE`

## Certificado digital

- O certificado pode ser enviado pela interface do usuario
- Alternativamente, pode ser configurado no `.env`
- O sistema usa o certificado no transporte HTTPS/TLS
- Nao ha assinatura XML embutida no pedido de consulta

## Documentacao

- [INSTALACAO.md](C:/xampp/htdocs/getxml/INSTALACAO.md)
- [ANALISE_SEFAZ.md](C:/xampp/htdocs/getxml/ANALISE_SEFAZ.md)
- [CERTIFICADO.md](C:/xampp/htdocs/getxml/CERTIFICADO.md)
