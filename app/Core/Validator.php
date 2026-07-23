<?php

namespace App\Core;

class Validator
{
    private $dados;
    private $regras;
    private $erros = [];
    private $mensagensPersonalizadas = [];

    /**
     * Cria instância do validador
     */
    public static function make($dados, $regras)
    {
        return new self($dados, $regras);
    }

    public function __construct($dados, $regras)
    {
        $this->dados = $dados;
        $this->regras = $regras;
    }

    /**
     * Define mensagens de erro personalizadas
     */
    public function messages($mensagens)
    {
        $this->mensagensPersonalizadas = $mensagens;
        return $this;
    }

    /**
     * Executa validação
     */
    public function validate()
    {
        foreach ($this->regras as $campo => $regrasCampo) {
            $regrasArray = explode('|', $regrasCampo);
            
            foreach ($regrasArray as $regra) {
                $this->validarCampo($campo, $regra);
            }
        }

        return empty($this->erros);
    }

    /**
     * Valida um campo específico
     */
    private function validarCampo($campo, $regra)
    {
        // Extrai parâmetros da regra (ex: min:3)
        $parametros = [];
        if (strpos($regra, ':') !== false) {
            list($regra, $parametrosStr) = explode(':', $regra);
            $parametros = explode(',', $parametrosStr);
        }

        $valor = $this->dados[$campo] ?? null;
        $nomeCampo = $this->formatarNomeCampo($campo);

        switch ($regra) {
            case 'required':
                if (empty($valor)) {
                    $this->adicionarErro($campo, 'required', $nomeCampo);
                }
                break;

            case 'email':
                if (!empty($valor) && !filter_var($valor, FILTER_VALIDATE_EMAIL)) {
                    $this->adicionarErro($campo, 'email', $nomeCampo);
                }
                break;

            case 'min':
                if (!empty($valor) && strlen($valor) < $parametros[0]) {
                    $this->adicionarErro($campo, 'min', $nomeCampo, $parametros[0]);
                }
                break;

            case 'max':
                if (!empty($valor) && strlen($valor) > $parametros[0]) {
                    $this->adicionarErro($campo, 'max', $nomeCampo, $parametros[0]);
                }
                break;

            case 'numeric':
                if (!empty($valor) && !is_numeric($valor)) {
                    $this->adicionarErro($campo, 'numeric', $nomeCampo);
                }
                break;

            case 'integer':
                if (!empty($valor) && !filter_var($valor, FILTER_VALIDATE_INT)) {
                    $this->adicionarErro($campo, 'integer', $nomeCampo);
                }
                break;

            case 'date':
                if (!empty($valor) && !$this->validarData($valor)) {
                    $this->adicionarErro($campo, 'date', $nomeCampo);
                }
                break;

            case 'cnpj':
                if (!empty($valor) && !$this->validarCNPJ($valor)) {
                    $this->adicionarErro($campo, 'cnpj', $nomeCampo);
                }
                break;

            case 'cpf':
                if (!empty($valor) && !$this->validarCPF($valor)) {
                    $this->adicionarErro($campo, 'cpf', $nomeCampo);
                }
                break;

            case 'confirmed':
                $campoConfirmacao = $campo . '_confirmation';
                if (!empty($valor) && $valor !== ($this->dados[$campoConfirmacao] ?? '')) {
                    $this->adicionarErro($campo, 'confirmed', $nomeCampo);
                }
                break;

            case 'in':
                if (!empty($valor) && !in_array($valor, $parametros)) {
                    $this->adicionarErro($campo, 'in', $nomeCampo, implode(', ', $parametros));
                }
                break;

            case 'url':
                if (!empty($valor) && !filter_var($valor, FILTER_VALIDATE_URL)) {
                    $this->adicionarErro($campo, 'url', $nomeCampo);
                }
                break;

            case 'alpha':
                if (!empty($valor) && !ctype_alpha(str_replace(' ', '', $valor))) {
                    $this->adicionarErro($campo, 'alpha', $nomeCampo);
                }
                break;

            case 'alnum':
                if (!empty($valor) && !ctype_alnum(str_replace(' ', '', $valor))) {
                    $this->adicionarErro($campo, 'alnum', $nomeCampo);
                }
                break;

            case 'regex':
                if (!empty($parametros[0]) && !preg_match($parametros[0], $valor)) {
                    $this->adicionarErro($campo, 'regex', $nomeCampo);
                }
                break;
        }
    }

    /**
     * Adiciona erro à lista
     */
    private function adicionarErro($campo, $regra, $nomeCampo, $parametro = null)
    {
        $chave = "{$campo}.{$regra}";
        
        // Verifica se há mensagem personalizada
        if (isset($this->mensagensPersonalizadas[$chave])) {
            $mensagem = $this->mensagensPersonalizadas[$chave];
        } else {
            $mensagem = $this->getMensagemPadrao($regra, $nomeCampo, $parametro);
        }

        if (!isset($this->erros[$campo])) {
            $this->erros[$campo] = [];
        }

        $this->erros[$campo][] = $mensagem;
    }

    /**
     * Obtém mensagem padrão de erro
     */
    private function getMensagemPadrao($regra, $nomeCampo, $parametro)
    {
        $mensagens = [
            'required' => "O campo {$nomeCampo} é obrigatório.",
            'email' => "O campo {$nomeCampo} deve ser um e-mail válido.",
            'min' => "O campo {$nomeCampo} deve ter no mínimo {$parametro} caracteres.",
            'max' => "O campo {$nomeCampo} deve ter no máximo {$parametro} caracteres.",
            'numeric' => "O campo {$nomeCampo} deve ser numérico.",
            'integer' => "O campo {$nomeCampo} deve ser um número inteiro.",
            'date' => "O campo {$nomeCampo} deve ser uma data válida.",
            'cnpj' => "O campo {$nomeCampo} deve ser um CNPJ válido.",
            'cpf' => "O campo {$nomeCampo} deve ser um CPF válido.",
            'confirmed' => "A confirmação do campo {$nomeCampo} não confere.",
            'in' => "O campo {$nomeCampo} deve ser um dos seguintes valores: {$parametro}.",
            'url' => "O campo {$nomeCampo} deve ser uma URL válida.",
            'alpha' => "O campo {$nomeCampo} deve conter apenas letras.",
            'alnum' => "O campo {$nomeCampo} deve conter apenas letras e números.",
            'regex' => "O campo {$nomeCampo} tem formato inválido."
        ];

        return $mensagens[$regra] ?? "O campo {$nomeCampo} é inválido.";
    }

    /**
     * Formata nome do campo para exibição
     */
    private function formatarNomeCampo($campo)
    {
        return ucfirst(str_replace('_', ' ', $campo));
    }

    /**
     * Obtém erros de validação
     */
    public function getErros()
    {
        return $this->erros;
    }

    /**
     * Obtém primeiro erro de um campo
     */
    public function getPrimeiroErro($campo)
    {
        return $this->erros[$campo][0] ?? null;
    }

    /**
     * Verifica se há erros
     */
    public function falhou()
    {
        return !empty($this->erros);
    }

    /**
     * Obtém todos os erros como array simples
     */
    public function errosPlenos()
    {
        $erros = [];
        foreach ($this->erros as $errosCampo) {
            $erros = array_merge($erros, $errosCampo);
        }
        return $erros;
    }

    /**
     * Valida data
     */
    private function validarData($data)
    {
        $data = str_replace('/', '-', $data);
        $timestamp = strtotime($data);
        return $timestamp !== false;
    }

    /**
     * Valida CNPJ
     */
    private function validarCNPJ($cnpj)
    {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        
        if (strlen($cnpj) != 14) {
            return false;
        }
        
        if (preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }
        
        for ($i = 0; $i <= 1; $i++) {
            $soma = 0;
            $multiplicador = $i == 0 ? 5 : 6;
            
            for ($j = 0; $j < 12 + $i; $j++) {
                $soma += $cnpj[$j] * $multiplicador;
                $multiplicador = $multiplicador == 9 ? 2 : $multiplicador + 1;
            }
            
            $resto = $soma % 11;
            $digito = $resto < 2 ? 0 : 11 - $resto;
            
            if ($cnpj[12 + $i] != $digito) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Valida CPF
     */
    private function validarCPF($cpf)
    {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        
        if (strlen($cpf) != 11) {
            return false;
        }
        
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }
        
        for ($i = 0; $i <= 1; $i++) {
            $soma = 0;
            $multiplicador = $i == 0 ? 10 : 11;
            
            for ($j = 0; $j < 9 + $i; $j++) {
                $soma += $cpf[$j] * $multiplicador;
                $multiplicador--;
            }
            
            $resto = $soma % 11;
            $digito = $resto < 2 ? 0 : 11 - $resto;
            
            if ($cpf[9 + $i] != $digito) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Valida arquivo
     */
    public static function validarArquivo($arquivo, $regras = [])
    {
        $erros = [];
        
        if (!isset($arquivo['tmp_name']) || !is_uploaded_file($arquivo['tmp_name'])) {
            return ['O arquivo não foi enviado corretamente.'];
        }
        
        // Valida tamanho
        if (isset($regras['max_size'])) {
            $tamanhoBytes = $arquivo['size'];
            $tamanhoMaximo = $regras['max_size'] * 1024 * 1024; // Converte MB para bytes
            
            if ($tamanhoBytes > $tamanhoMaximo) {
                $erros[] = "O arquivo excede o tamanho máximo de {$regras['max_size']}MB.";
            }
        }
        
        // Valida tipo
        if (isset($regras['allowed_types'])) {
            $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
            $tiposPermitidos = array_map('strtolower', $regras['allowed_types']);
            
            if (!in_array($extensao, $tiposPermitidos)) {
                $erros[] = "Tipo de arquivo não permitido. Tipos permitidos: " . implode(', ', $regras['allowed_types']);
            }
        }
        
        // Valida MIME type
        if (isset($regras['allowed_mimes'])) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $arquivo['tmp_name']);
            finfo_close($finfo);
            
            if (!in_array($mime, $regras['allowed_mimes'])) {
                $erros[] = "Tipo MIME não permitido.";
            }
        }
        
        return $erros;
    }

    /**
     * Sanitiza dados de entrada
     */
    public static function sanitizar($dados)
    {
        if (is_array($dados)) {
            return array_map([self::class, 'sanitizar'], $dados);
        }
        
        return htmlspecialchars(strip_tags(trim($dados)), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Obtém dados validados
     */
    public function getDadosValidados()
    {
        return $this->dados;
    }

    /**
     * Obtém dados sanitizados
     */
    public function getDadosSanitizados()
    {
        return self::sanitizar($this->dados);
    }
}
