# Exemplo de Uso do Sistema GetXML SEFAZ

## Cenário 1: Primeiro Acesso e Configuração

### Passo 1: Configurar o .env

Edite o arquivo `.env` na raiz do projeto:

```env
# Configurações do Sistema
APP_NAME=GetXML SEFAZ
APP_ENV=development
APP_DEBUG=true

# Configurações SEFAZ
SEFAZ_UF=SP
SEFAZ_AMBIENTE=2  # Comece com homologação (2)
SEFAZ_CERTIFICADO=C:\certificados\meu_certificado.pfx
SEFAZ_SENHA_CERTIFICADO=MinhaSenha123

# CNPJ para captura das notas
CNPJ_CNPJ=12.345.678/0001-90
CNPJ_IE=123456789

# Período de captura (opcional)
DATA_INICIO=
DATA_FIM=

# Configurações de armazenamento
STORAGE_PATH=storage/xmls
```

### Passo 2: Acessar o Sistema

Abra o navegador e acesse:
```
http://localhost/getxml/public/
```

Você verá a página inicial com estatísticas (inicialmente zeros).

### Passo 3: Verificar Configurações

1. Clique em "Configurações" no menu
2. Verifique se todas as informações estão corretas
3. Se houver campos em branco, revise o arquivo `.env`

## Cenário 2: Buscar Notas Fiscais

### Passo 1: Acessar a Busca

1. Clique em "Buscar Notas" no menu
2. Você verá o formulário de busca

### Passo 2: Informar o Período

1. Data Início: `01/01/2024`
2. Data Fim: `31/01/2024`
3. Clique em "Buscar Notas"

### Passo 3: Analisar os Resultados

O sistema mostrará:
- Total de notas encontradas
- Lista com detalhes de cada nota
- Opção de salvar cada nota individualmente

### Passo 4: Salvar as Notas

1. Para cada nota que deseja salvar, clique em "Salvar XML"
2. O sistema confirmará o sucesso da operação
3. O XML será salvo em `storage/xmls/`

## Cenário 3: Listar e Gerenciar Notas

### Passo 1: Acessar a Listagem

1. Clique em "Listar Notas" no menu
2. Você verá todas as notas capturadas

### Passo 2: Filtrar Notas

Use os filtros para refinar a busca:

- **Por período**: Informe data início e data fim
- **Por CNPJ**: Digite o CNPJ do emitente (parcial ou completo)
- Clique em "Filtrar"

### Passo 3: Visualizar XML

1. Encontre a nota desejada na lista
2. Clique em "Ver XML"
3. O XML abrirá em uma nova aba do navegador

### Passo 4: Excluir Nota

1. Clique em "Excluir" ao lado da nota
2. Confirme a operação
3. A nota será removida do sistema

## Cenário 4: Integração com Sistema Contábil

### Exportar XMLs

Os XMLs salvos em `storage/xmls/` podem ser:

1. **Importados manualmente**: Copie os arquivos para importar em outro sistema
2. **Processados automaticamente**: Crie scripts para ler e processar os XMLs
3. **Enviados por API**: Desenvolva uma API para enviar os XMLs

### Exemplo de Script de Processamento

```php
<?php
// processar_xmls.php
$diretorio = 'storage/xmls/';
$arquivos = glob($diretorio . '*.xml');

foreach ($arquivos as $arquivo) {
    $xml = simplexml_load_file($arquivo);
    
    // Extrair dados do XML
    $chave = (string)$xml->infNFe['Id'];
    $numero = (string)$xml->infNFe->ide->nNF;
    $valor = (string)$xml->infNFe->total->ICMSTot->vNF;
    
    // Processar conforme necessário
    echo "Nota {$numero}: R$ {$valor}\n";
}
```

## Cenário 5: Tratamento de Erros

### Erro de Certificado

**Sintoma**: Mensagem "Caminho do certificado digital não configurado"

**Solução**:
1. Verifique se o arquivo `.pfx` existe no caminho informado
2. Confirme a senha do certificado
3. Teste abrir o certificado no Windows para validar a senha

### Erro de Conexão

**Sintoma**: Mensagem "Erro ao consultar SEFAZ"

**Solução**:
1. Verifique sua conexão com a internet
2. Confirme se o serviço da SEFAZ está online
3. Tente acessar em horário de menor movimento
4. Verifique se está usando o ambiente correto (produção/homologação)

### Notas Não Encontradas

**Sintoma**: Mensagem "Nenhuma nota encontrada no período informado"

**Solução**:
1. Verifique se existem notas no período informado
2. Confirme se o CNPJ está correto
3. Tente um período mais longo
4. Verifique se tem autorização para acessar as notas

## Cenário 6: Trabalhando com Múltiplos Clientes

### Configuração por Cliente

Se você atende múltiplos clientes:

1. **Opção 1**: Múltiplas instalações
   - Copie todo o projeto para cada cliente
   - Configure o `.env` específico para cada um

2. **Opção 2**: Alterne configurações
   - Mantenha múltiplos arquivos `.env`
   - Copie o `.env` do cliente desejado antes de usar

3. **Opção 3**: Desenvolva um sistema multi-tenant
   - Adicione campo de identificação do cliente
   - Crie diretórios separados por cliente
   - Estenda o sistema para suportar múltiplos CNPJs

### Exemplo de Estrutura Multi-Cliente

```
storage/
├── xmls/
│   ├── cliente1/
│   ├── cliente2/
│   └── cliente3/
└── notas_fiscais_cliente1.json
├── notas_fiscais_cliente2.json
└── notas_fiscais_cliente3.json
```

## Cenário 7: Agendamento de Buscas Automáticas

### Usando Tarefas Agendadas (Windows)

1. Abra o "Agendador de Tarefas do Windows"
2. Crie uma nova tarefa
3. Configure para executar diariamente
4. Ação: Executar script PHP

### Script de Busca Automática

```php
<?php
// buscar_automatico.php
require_once __DIR__ . '/vendor/autoload.php';
$config = require_once __DIR__ . '/config/config.php';

use App\Controllers\SefazController;

$controller = new SefazController($config);

// Buscar notas do último mês
$dataInicio = date('Y-m-01');
$dataFim = date('Y-m-t');

// Implementar lógica de busca automática
// ... (código de busca e salvamento automático)
```

## Dicas de Uso Avançado

### 1. Validação de XMLs

Use validadores de XML da SEFAZ para garantir integridade:
- Validador online da SEFAZ
- Aplicativos de validação locais

### 2. Monitoramento de Erros

Implemente logs para monitorar:
- Erros de conexão
- Notas que falharam ao salvar
- Problemas com certificado

### 3. Otimização de Performance

- Implemente cache de consultas
- Use filas para processamento em lote
- Otimize consultas ao banco de dados local

### 4. Segurança Adicional

- Criptografe os XMLs salvos
- Implemente autenticação no sistema
- Use HTTPS em produção

---

**Próximos Passos**: Após dominar o uso básico, explore as customizações possíveis e integrações com seus sistemas existentes.
