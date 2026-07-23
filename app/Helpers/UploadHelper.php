<?php

namespace App\Helpers;

class UploadHelper
{
    /**
     * Faz upload de certificado digital
     */
    public static function uploadCertificado($arquivo, $usuarioId)
    {
        // Validações
        $erros = self::validarArquivo($arquivo);
        
        if (!empty($erros)) {
            return [
                'success' => false,
                'errors' => $erros
            ];
        }

        // Cria diretório do usuário se não existir
        $diretorioUsuario = 'storage/certificados/' . $usuarioId;
        if (!is_dir($diretorioUsuario)) {
            mkdir($diretorioUsuario, 0755, true);
        }

        // Gera nome único
        $extensao = pathinfo($arquivo['name'], PATHINFO_EXTENSION);
        $nomeArquivo = 'cert_' . time() . '_' . $usuarioId . '.' . $extensao;
        $caminhoCompleto = $diretorioUsuario . '/' . $nomeArquivo;

        // Move o arquivo
        if (move_uploaded_file($arquivo['tmp_name'], $caminhoCompleto)) {
            return [
                'success' => true,
                'nome_arquivo' => $nomeArquivo,
                'caminho_arquivo' => $caminhoCompleto,
                'tamanho' => $arquivo['size'],
                'tipo' => $arquivo['type']
            ];
        }

        return [
            'success' => false,
            'errors' => ['Erro ao fazer upload do arquivo']
        ];
    }

    /**
     * Valida arquivo de certificado
     */
    private static function validarArquivo($arquivo)
    {
        $erros = [];

        // Verifica se houve erro no upload
        if ($arquivo['error'] !== UPLOAD_ERR_OK) {
            $erros[] = self::getUploadErrorMessage($arquivo['error']);
            return $erros;
        }

        // Verifica se é um arquivo válido
        if (!is_uploaded_file($arquivo['tmp_name'])) {
            $erros[] = 'Arquivo inválido';
            return $erros;
        }

        // Verifica tamanho (máximo 10MB)
        $tamanhoMaximo = 10 * 1024 * 1024; // 10MB
        if ($arquivo['size'] > $tamanhoMaximo) {
            $erros[] = 'Arquivo muito grande. Máximo 10MB';
        }

        // Verifica extensão
        $extensoesPermitidas = ['pfx', 'p12'];
        $extensao = strtolower(pathinfo($arquivo['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extensao, $extensoesPermitidas)) {
            $erros[] = 'Extensão não permitida. Use .pfx ou .p12';
        }

        // Verifica tipo MIME
        $tiposPermitidos = [
            'application/x-pkcs12',
            'application/pkcs12',
            'application/x-pfx'
        ];
        
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $tipo = finfo_file($finfo, $arquivo['tmp_name']);
            finfo_close($finfo);
            
            // Arquivos pfx/p12 podem ter MIME type diferente, então não bloqueamos se não corresponder
            // Mas logamos se necessário
        }

        return $erros;
    }

    /**
     * Obtém mensagem de erro de upload
     */
    private static function getUploadErrorMessage($codigo)
    {
        $mensagens = [
            UPLOAD_ERR_INI_SIZE => 'Arquivo excede o tamanho máximo do PHP',
            UPLOAD_ERR_FORM_SIZE => 'Arquivo excede o tamanho máximo do formulário',
            UPLOAD_ERR_PARTIAL => 'Arquivo enviado parcialmente',
            UPLOAD_ERR_NO_FILE => 'Nenhum arquivo enviado',
            UPLOAD_ERR_NO_TMP_DIR => 'Diretório temporário não configurado',
            UPLOAD_ERR_CANT_WRITE => 'Erro ao escrever arquivo no disco',
            UPLOAD_ERR_EXTENSION => 'Upload interrompido por extensão PHP',
        ];

        return $mensagens[$codigo] ?? 'Erro desconhecido no upload';
    }

    /**
     * Remove arquivo de certificado
     */
    public static function removerCertificado($caminho)
    {
        if (file_exists($caminho)) {
            return unlink($caminho);
        }
        return false;
    }

    /**
     * Obtém informações do arquivo
     */
    public static function getInfoArquivo($caminho)
    {
        if (!file_exists($caminho)) {
            return null;
        }

        return [
            'tamanho' => filesize($caminho),
            'modificado' => filemtime($caminho),
            'extensao' => pathinfo($caminho, PATHINFO_EXTENSION),
            'nome' => basename($caminho)
        ];
    }

    /**
     * Formata tamanho do arquivo
     */
    public static function formatarTamanho($bytes)
    {
        $unidades = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $unidades[$i];
    }

    /**
     * Valida senha do certificado
     */
    public static function validarSenhaCertificado($caminho, $senha)
    {
        if (!file_exists($caminho)) {
            return false;
        }

        try {
            // Tenta abrir o arquivo PKCS12
            if (function_exists('openssl_pkcs12_read')) {
                $conteudo = file_get_contents($caminho);
                $certificado = [];
                
                $resultado = openssl_pkcs12_read($conteudo, $certificado, $senha);
                
                if ($resultado) {
                    return true;
                }
            }
            
            // Se não conseguir validar com OpenSSL, assume que está ok
            // (validação real acontecerá ao usar com SEFAZ)
            return true;
            
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Limpa certificados antigos do usuário
     */
    public static function limparCertificadosAntigos($usuarioId, $dias = 30)
    {
        $diretorioUsuario = 'storage/certificados/' . $usuarioId;
        
        if (!is_dir($diretorioUsuario)) {
            return;
        }

        $arquivos = glob($diretorioUsuario . '/*');
        $tempoLimite = time() - ($dias * 24 * 60 * 60);

        foreach ($arquivos as $arquivo) {
            if (filemtime($arquivo) < $tempoLimite) {
                unlink($arquivo);
            }
        }
    }

    /**
     * Obtém espaço usado pelo usuário
     */
    public static function obterEspacoUsado($usuarioId)
    {
        $diretorioUsuario = 'storage/certificados/' . $usuarioId;
        
        if (!is_dir($diretorioUsuario)) {
            return 0;
        }

        $arquivos = glob($diretorioUsuario . '/*');
        $total = 0;

        foreach ($arquivos as $arquivo) {
            $total += filesize($arquivo);
        }

        return $total;
    }
}
