# Análise de Conformidade com a SEFAZ

## Resumo

O fluxo atual de consulta está alinhado com o modelo de distribuição de DF-e da SEFAZ:

- pedido `distDFeInt` com `distNSU`, `consNSU` e `consChNFe`
- transporte SOAP 1.2 com `nfeCabecMsg` e `nfeDadosMsg`
- certificado digital usado no TLS do cliente HTTP
- `docZip` tratado com base64 + descompressão
- NSU normalizado para 15 dígitos

## O que foi removido

- assinatura XML embutida no pedido
- dependência `phpseclib/phpseclib`
- helper legado `app/Helpers/XmlSigner.php`

## Pontos importantes

- `buscarNotasPorPeriodo()` continua existindo como filtro local após a coleta por NSU
- a verificação TLS do servidor agora é configurável via `SEFAZ_CA_BUNDLE`
- o ambiente padrão continua sendo homologação quando o `.env` não está carregado

## Estrutura atual do pedido

```xml
<distDFeInt xmlns="http://www.portalfiscal.inf.br/nfe" versao="1.01">
    <tpAmb>1 ou 2</tpAmb>
    <cUFAutor>código UF</cUFAutor>
    <CNPJ>ou CPF</CNPJ>
    <distNSU>
        <ultNSU>000000000000000</ultNSU>
    </distNSU>
</distDFeInt>
```

## Arquivos relevantes

- [app/Models/SefazModel.php](C:/xampp/htdocs/getxml/app/Models/SefazModel.php)
- [config/config.php](C:/xampp/htdocs/getxml/config/config.php)
- [test_sefaz.php](C:/xampp/htdocs/getxml/test_sefaz.php)

## Observação

Se você quiser validar contra a SEFAZ em produção real, o próximo passo é testar com certificado válido e CNPJ autorizado no ambiente de homologação antes de trocar para produção.
