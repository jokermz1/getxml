<?php

/**
 * Script de Teste do Sistema GetXML SEFAZ
 * 
 * Este script verifica se todos os componentes do sistema estão funcionando corretamente.
 */

require_once __DIR__ . '/vendor/autoload.php';

// Configuração de teste
$config = [
    'app' => [
        'name' => 'GetXML SEFAZ',
        'env' => 'development',
        'debug' => true,
    ],
    'sefaz' => [
        'uf' => 'SP',
        'ambiente' => '2',
        'certificado' => '',
        'senha_certificado' => '',
    ],
    'cnpj' => [
        'cnpj' => '',
        'ie' => '',
    ],
    'periodo' => [
        'data_inicio' => null,
        'data_fim' => null,
    ],
    'storage' => [
        'path' => 'storage/xmls',
    ],
];

use App\Helpers\Helper;
use App\Core\Validator;
use App\Core\Logger;
use App\Middleware\SecurityMiddleware;

class TesteSistema
{
    private $config;
    private $resultados = [];
    private $logger;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->logger = new Logger('storage/logs', 'teste');
    }

    /**
     * Executa todos os testes
     */
    public function executar()
    {
        echo "=== Sistema de Teste GetXML SEFAZ ===\n\n";
        
        $this->testeAmbiente();
        $this->testeConfiguracao();
        $this->testeDependencias();
        $this->testeHelper();
        $this->testeValidator();
        $this->testeLogger();
        $this->testeSecurityMiddleware();
        $this->testeDiretorios();
        $this->testePermissoes();
        
        $this->exibirResultados();
    }

    /**
     * Testa ambiente PHP
     */
    private function testeAmbiente()
    {
        $this->resultado('Versão PHP', version_compare(PHP_VERSION, '7.4', '>='));
        $this->resultado('Extensão JSON', extension_loaded('json'));
        $this->resultado('Extensão cURL', extension_loaded('curl'));
        $this->resultado('Extensión OpenSSL', extension_loaded('openssl'));
        $this->resultado('Extensão MBString', extension_loaded('mbstring'));
        $this->resultado('Extensão PDO', extension_loaded('pdo'));
    }

    /**
     * Testa configuração
     */
    private function testeConfiguracao()
    {
        $this->resultado('Arquivo .env existe', file_exists(__DIR__ . '/.env') || file_exists(__DIR__ . '/.env.teste'));
        $this->resultado('Config carregada', !empty($this->config));
        $this->resultado('CNPJ configurado', !empty($this->config['cnpj']['cnpj']), true); // Opcional
        $this->resultado('UF configurada', !empty($this->config['sefaz']['uf']));
        $this->resultado('Ambiente configurado', !empty($this->config['app']['env']));
    }

    /**
     * Testa dependências
     */
    private function testeDependencias()
    {
        $this->resultado('Composer autoload', file_exists(__DIR__ . '/vendor/autoload.php'));
        $this->resultado('Guzzle HTTP', class_exists('GuzzleHttp\Client'));
        $this->resultado('PhpDotenv', class_exists('Dotenv\Dotenv'));
    }

    /**
     * Testa Helper
     */
    private function testeHelper()
    {
        $this->resultado('Helper::formatarCNPJ', Helper::formatarCNPJ('12345678000190') === '12.345.678/0001-90');
        $this->resultado('Helper::formatarMoeda', Helper::formatarMoeda(1234.56) === 'R$ 1.234,56');
        $this->resultado('Helper::validarCPF', Helper::validarCPF('52998224725') === true);
        $this->resultado('Helper::formatarData', Helper::formatarData('2024-01-15 10:30:00') === '15/01/2024 10:30');
    }

    /**
     * Testa Validator
     */
    private function testeValidator()
    {
        $validator = Validator::make([
            'nome' => 'João Silva',
            'email' => 'joao@teste.com',
            'idade' => 25
        ], [
            'nome' => 'required|min:3',
            'email' => 'required|email',
            'idade' => 'required|numeric'
        ]);

        $this->resultado('Validator::make', $validator !== null);
        $this->resultado('Validator::validate', $validator->validate() === true);
        $this->resultado('Validator::getErros', empty($validator->getErros()));
    }

    /**
     * Testa Logger
     */
    private function testeLogger()
    {
        $this->logger->info('Teste de log info');
        $this->logger->debug('Teste de log debug');
        $this->logger->aviso('Teste de log aviso');
        
        $arquivoLog = 'storage/logs/teste_' . date('Y-m-d') . '.log';
        $this->resultado('Logger::info', file_exists($arquivoLog));
        $this->resultado('Logger arquivo criado', filesize($arquivoLog) > 0);
    }

    /**
     * Testa SecurityMiddleware
     */
    private function testeSecurityMiddleware()
    {
        $middleware = new SecurityMiddleware($this->config);
        
        $this->resultado('SecurityMiddleware criado', $middleware !== null);
        $this->resultado('SecurityMiddleware::gerarToken', strlen(SecurityMiddleware::gerarToken()) === 64);
        $this->resultado('SecurityMiddleware::validarSenha', SecurityMiddleware::validarSenha('Senha@123') === true);
        $this->resultado('SecurityMiddleware::hashSenha', strlen(SecurityMiddleware::hashSenha('teste')) === 60);
    }

    /**
     * Testa diretórios
     */
    private function testeDiretorios()
    {
        $this->resultado('Diretório app', is_dir(__DIR__ . '/app'));
        $this->resultado('Diretório app/Controllers', is_dir(__DIR__ . '/app/Controllers'));
        $this->resultado('Diretório app/Models', is_dir(__DIR__ . '/app/Models'));
        $this->resultado('Diretório app/Views', is_dir(__DIR__ . '/app/Views'));
        $this->resultado('Diretório public', is_dir(__DIR__ . '/public'));
        $this->resultado('Diretório storage', is_dir(__DIR__ . '/storage'));
        $this->resultado('Diretório storage/xmls', is_dir(__DIR__ . '/storage/xmls'));
        $this->resultado('Diretório storage/logs', is_dir(__DIR__ . '/storage/logs'));
        $this->resultado('Diretório public/assets', is_dir(__DIR__ . '/public/assets'));
        $this->resultado('Diretório public/assets/js', is_dir(__DIR__ . '/public/assets/js'));
        $this->resultado('Diretório public/assets/css', is_dir(__DIR__ . '/public/assets/css'));
    }

    /**
     * Testa permissões
     */
    private function testePermissoes()
    {
        $arquivoTeste = __DIR__ . '/storage/xmls/teste_permissao.txt';
        
        // Testa escrita
        $escrita = file_put_contents($arquivoTeste, 'teste');
        $this->resultado('Permissão escrita storage/xmls', $escrita !== false);
        
        // Testa leitura
        $leitura = file_get_contents($arquivoTeste);
        $this->resultado('Permissão leitura storage/xmls', $leitura === 'teste');
        
        // Remove arquivo de teste
        if (file_exists($arquivoTeste)) {
            unlink($arquivoTeste);
        }
        
        // Testa logs
        $arquivoLog = __DIR__ . '/storage/logs/teste_' . date('Y-m-d') . '.log';
        $this->resultado('Permissão escrita storage/logs', is_writable(__DIR__ . '/storage/logs'));
    }

    /**
     * Registra resultado de teste
     */
    private function resultado($teste, $sucesso, $opcional = false)
    {
        $this->resultados[$teste] = $sucesso;
        
        if ($opcional && !$sucesso) {
            $status = '○ OPCIONAL';
        } else {
            $status = $sucesso ? '✓ PASSOU' : '✗ FALHOU';
        }
        
        echo sprintf("%-40s %s\n", $teste, $status);
    }

    /**
     * Exibe resultados finais
     */
    private function exibirResultados()
    {
        echo "\n=== Resumo ===\n";
        
        $total = count($this->resultados);
        $passou = count(array_filter($this->resultados));
        $falhou = $total - $passou;
        
        echo "Total de testes: {$total}\n";
        echo "Passou: {$passou}\n";
        echo "Falhou: {$falhou}\n";
        
        $porcentagem = ($passou / $total) * 100;
        echo "Taxa de sucesso: " . number_format($porcentagem, 2) . "%\n";
        
        if ($falhou > 0) {
            echo "\n=== Testes que falharam ===\n";
            foreach ($this->resultados as $teste => $sucesso) {
                if (!$sucesso) {
                    echo "- {$teste}\n";
                }
            }
        }
        
        echo "\n=== Conclusão ===\n";
        if ($falhou === 0) {
            echo "✓ Todos os testes passaram! O sistema está pronto para uso.\n";
        } elseif ($falhou <= 3) {
            echo "⚠ Alguns testes falharam. Verifique os itens acima.\n";
        } else {
            echo "✗ Muitos testes falharam. Verifique a instalação e configuração.\n";
        }
        
        echo "\n=== Próximos Passos ===\n";
        echo "1. Configure o arquivo .env com suas credenciais\n";
        echo "2. Configure o caminho do certificado digital\n";
        echo "3. Acesse http://localhost/getxml/public/ no navegador\n";
        echo "4. Leia a documentação em README.md\n";
    }
}

// Executa testes
try {
    $teste = new TesteSistema($config);
    $teste->executar();
} catch (Exception $e) {
    echo "Erro ao executar testes: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
