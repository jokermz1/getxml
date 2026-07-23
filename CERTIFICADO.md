# Guia de Configuração de Certificado Digital

## O que é um Certificado Digital A1?

O certificado digital A1 é um arquivo eletrônico que:
- Identifica pessoa física ou jurídica
- Permite acesso a serviços governamentais
- É armazenado em arquivo (geralmente .pfx ou .p12)
- Tem validade geralmente de 1 a 3 anos

## Obtendo o Certificado Digital

### Autoridades Certificadoras

No Brasil, as principais autoridades certificadoras são:

- **Serasa**: https://www.serasa.com.br/certificado-digital/
- **Certisign**: https://www.certisign.com.br/
- **Caixa Econômica Federal**: https://www.caixa.gov.br/
- **Soluti**: https://www.soluti.com.br/
- **Valid Certificadora**: https://www.valid.com.br/

### Processo de Emissão

1. **Escolha a Autoridade Certificadora**
   - Compare preços e prazos
   - Verifique qual atende melhor sua necessidade

2. **Solicitação do Certificado**
   - Acesse o site da autoridade escolhida
   - Preencha os dados cadastrais
   - Escolha o tipo (e-CPF ou e-CNPJ)
   - Escolha a validade

3. **Validação de Identidade**
   - Pessoa física: compareça a um posto de atendimento
   - Pessoa jurídica: envie documentação
   - Pode ser necessário videoconferência

4. **Emissão e Download**
   - Após aprovação, você receberá instruções
   - Faça o download do arquivo .pfx
   - Defina uma senha de proteção

## Tipos de Certificado

### e-CPF (Pessoa Física)
- Para pessoas físicas
- Útil para MEIs e profissionais liberais
- CNPJ vinculado ao CPF

### e-CNPJ (Pessoa Jurídica)
- Para empresas
- Pode ter múltiplos responsáveis
- Requer documentação da empresa

### Certificado de Contador
- Específico para contadores
- Permite acesso a notas de múltiplos clientes
- Requer registro em conselho de contabilidade

## Configurando no Sistema

### Passo 1: Armazenar o Arquivo

Escolha um local seguro para armazenar o certificado:

**Windows**:
```
C:\certificados\sefaz\certificado.pfx
```

**Linux**:
```
/home/usuario/certificados/sefaz/certificado.pfx
```

### Passo 2: Configurar o .env

Edite o arquivo `.env`:

```env
SEFAZ_CERTIFICADO=C:\certificados\sefaz\certificado.pfx
SEFAZ_SENHA_CERTIFICADO=SuaSenhaAqui
```

### Passo 3: Testar a Configuração

1. Acesse o sistema
2. Vá em "Configurações"
3. Verifique se o certificado aparece como configurado
4. Tente buscar notas para testar

## Solução de Problemas

### Erro: "Não é possível abrir o arquivo"

**Causas possíveis**:
- Caminho do arquivo incorreto
- Arquivo não existe
- Permissões insuficientes

**Soluções**:
1. Verifique se o caminho está correto
2. Use caminho absoluto (ex: `C:\certificados\cert.pfx`)
3. Verifique permissões do arquivo

### Erro: "Senha incorreta"

**Causas possíveis**:
- Senha digitada errada
- Certificado corrompido
- Problema de codificação de caracteres

**Soluções**:
1. Tente abrir o certificado no Windows para confirmar a senha
2. Verifique se não há espaços em branco extras
3. Se necessário, solicite novo certificado

### Erro: "Certificado expirado"

**Causas possíveis**:
- Validade do certificado acabou
- Data/hora do servidor incorreta

**Soluções**:
1. Verifique a data de validade do certificado
2. Renove o certificado com a autoridade certificadora
3. Verifique a data/hora do servidor

### Erro: "Certificado revogado"

**Causas possíveis**:
- Certificado foi cancelado
- Problemas na emissão

**Soluções**:
1. Entre em contato com a autoridade certificadora
2. Solicite novo certificado

## Boas Práticas de Segurança

### 1. Proteção do Arquivo
- Armazene em local seguro
- Faça backup do arquivo
- Não compartilhe o arquivo
- Mantenha a senha em local seguro

### 2. Proteção da Senha
- Use senha forte
- Não reutilize senhas
- Anote a senha em local seguro
- Troque a senha se comprometida

### 3. Controle de Acesso
- Limite quem tem acesso ao certificado
- Registre quem usou o certificado
- Revogue acessos quando necessário

## 🆕 Duas Formas de Configuração

Este sistema oferece **duas opções** para configurar o certificado digital:

### Opção 1: Upload via Interface (Recomendado) ✅

**Vantagens:**
- ✅ Interface amigável e intuitiva
- ✅ Cada usuário pode ter seu próprio certificado
- ✅ Botão para mostrar/ocultar senha (👁️)
- ✅ Gerenciamento fácil (ativar/desativar/remover)
- ✅ Validação automática de formato e tamanho
- ✅ Não precisa editar arquivos de configuração

**Como usar:**
1. Faça login no sistema
2. Acesse "Certificados" no menu
3. Faça upload do arquivo .pfx/.p12
4. Informe a senha (com botão para mostrar/ocultar)
5. Selecione a UF da SEFAZ
6. Clique em "Enviar Certificado"

### Opção 2: Configuração via .env

**Quando usar:**
- Para configuração global do sistema
- Para ambientes de desenvolvimento
- Quando não quiser usar upload via interface

**Como usar:**
1. Coloque o arquivo em local seguro
2. Edite o arquivo .env:
   ```env
   SEFAZ_CERTIFICADO=C:\caminho\certificado.pfx
   SEFAZ_SENHA_CERTIFICADO=SuaSenha
   ```

**Nota:** O sistema prioriza o certificado uploadado via interface. Se não houver certificado uploadado, usa o configurado no .env.

### 4. Renovação
- Monitore a data de validade
- Renove antes de expirar
- Teste o novo certificado antes de usar

## Integrando com Múltiplos Certificados

### Cenário: Múltiplos Clientes

Se você precisa usar múltiplos certificados:

**Opção 1: Múltiplas Instalações**
```bash
getxml-cliente1/
getxml-cliente2/
getxml-cliente3/
```

**Opção 2: Alterne Configurações**
```php
// Script para alternar certificados
$certificados = [
    'cliente1' => [
        'caminho' => 'C:\certificados\cliente1.pfx',
        'senha' => 'senha1'
    ],
    'cliente2' => [
        'caminho' => 'C:\certificados\cliente2.pfx',
        'senha' => 'senha2'
    ]
];
```

**Opção 3: Seleção Dinâmica**
- Adicione campo para selecionar o certificado
- Carregue o certificado selecionado dinamicamente
- Gerencie múltiplos certificados no sistema

## Validando o Certificado

### Verificação Manual

**Windows**:
1. Clique duas vezes no arquivo .pfx
2. Digite a senha
3. Verifique as informações do certificado
4. Confirme a data de validade

### Verificação via PHP

```php
<?php
// script_verificar_certificado.php
$certificado = 'caminho/certificado.pfx';
$senha = 'sua_senha';

if (file_exists($certificado)) {
    $pkcs12 = file_get_contents($certificado);
    $certs = [];
    if (openssl_pkcs12_read($pkcs12, $certs, $senha)) {
        echo "Certificado válido!\n";
        echo "Emitido para: " . $certs['cert']['subject'] . "\n";
        echo "Válido até: " . $certs['cert']['validTo'] . "\n";
    } else {
        echo "Erro ao ler certificado: " . openssl_error_string() . "\n";
    }
} else {
    echo "Arquivo não encontrado\n";
}
```

## Suporte

### Autoridade Certificadora
- Entre em contato com a autoridade que emitiu o certificado
- Eles podem ajudar com problemas específicos do certificado

### SEFAZ
- Consulte o portal da SEFAZ do seu estado
- Verifique a documentação técnica

### Comunidade
- Fóruns de discussão sobre NFe
- Comunidades de desenvolvedores

---

**Importante**: O certificado digital é um documento eletrônico com validade jurídica. Mantenha-o seguro e protegido.
