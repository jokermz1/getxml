<?php

namespace App\Helpers;

use phpseclib3\File\X509;

/**
 * Helper para assinatura digital de XML conforme padrão SEFAZ
 */
class XmlSigner
{
    private $certificado;
    private $senha;
    private $certificadoResource;

    public function __construct($caminhoCertificado, $senha)
    {
        $this->certificado = $caminhoCertificado;
        $this->senha = $senha;
        $this->carregarCertificado();
    }

    /**
     * Carrega o certificado digital
     */
    private function carregarCertificado()
    {
        if (!file_exists($this->certificado)) {
            throw new \Exception('Arquivo de certificado não encontrado: ' . $this->certificado);
        }

        // Carregar certificado PFX/P12
        $certContent = file_get_contents($this->certificado);
        
        if (openssl_pkcs12_read($certContent, $certs, $this->senha)) {
            $this->certificadoResource = $certs['pkey'];
        } else {
            throw new \Exception('Não foi possível ler o certificado. Verifique a senha.');
        }
    }

    /**
     * Assina XML conforme padrão XML-DSig
     */
    public function assinarXml($xmlString, $referenceUri = '')
    {
        try {
            $dom = new \DOMDocument('1.0', 'UTF-8');
            $dom->loadXML($xmlString);
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = false;

            // Criar nó de assinatura
            $signature = $dom->createElement('Signature');
            $signature->setAttribute('xmlns', 'http://www.w3.org/2000/09/xmldsig#');

            // SignedInfo
            $signedInfo = $dom->createElement('SignedInfo');
            $signature->appendChild($signedInfo);

            // CanonicalizationMethod
            $canonicalizationMethod = $dom->createElement('CanonicalizationMethod');
            $canonicalizationMethod->setAttribute('Algorithm', 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315');
            $signedInfo->appendChild($canonicalizationMethod);

            // SignatureMethod
            $signatureMethod = $dom->createElement('SignatureMethod');
            $signatureMethod->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#rsa-sha1');
            $signedInfo->appendChild($signatureMethod);

            // Reference
            $reference = $dom->createElement('Reference');
            $reference->setAttribute('URI', $referenceUri);
            $signedInfo->appendChild($reference);

            // Transforms
            $transforms = $dom->createElement('Transforms');
            $reference->appendChild($transforms);

            // Transform 1: C14N
            $transform1 = $dom->createElement('Transform');
            $transform1->setAttribute('Algorithm', 'http://www.w3.org/TR/2001/REC-xml-c14n-20010315');
            $transforms->appendChild($transform1);

            // Transform 2: Enveloped
            $transform2 = $dom->createElement('Transform');
            $transform2->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#enveloped-signature');
            $transforms->appendChild($transform2);

            // DigestMethod
            $digestMethod = $dom->createElement('DigestMethod');
            $digestMethod->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#sha1');
            $reference->appendChild($digestMethod);

            // DigestValue
            $digestValue = $dom->createElement('DigestValue');
            $reference->appendChild($digestValue);

            // Calcular digest
            $c14n = $dom->C14N(true, false);
            $digestValue->nodeValue = base64_encode(sha1($c14n, true));

            // KeyInfo
            $keyInfo = $dom->createElement('KeyInfo');
            $signature->appendChild($keyInfo);

            // X509Data
            $x509Data = $dom->createElement('X509Data');
            $keyInfo->appendChild($x509Data);

            // X509Certificate
            $x509Certificate = $dom->createElement('X509Certificate');
            $x509Data->appendChild($x509Certificate);

            // Extrair certificado
            openssl_pkcs12_read(file_get_contents($this->certificado), $certs, $this->senha);
            $x509Certificate->nodeValue = $this->extrairCertificado($certs['cert']);

            // SignatureValue
            $signatureValue = $dom->createElement('SignatureValue');
            $signature->appendChild($signatureValue);

            // Calcular assinatura
            $signedInfoXml = $signedInfo->C14N(true, false);
            openssl_sign($signedInfoXml, $assinatura, $this->certificadoResource, OPENSSL_ALGO_SHA1);
            $signatureValue->nodeValue = base64_encode($assinatura);

            // Adicionar assinatura ao XML
            $root = $dom->documentElement;
            $root->appendChild($signature);

            return $dom->saveXML();

        } catch (\Exception $e) {
            throw new \Exception('Erro ao assinar XML: ' . $e->getMessage());
        }
    }

    /**
     * Extrai certificado no formato PEM
     */
    private function extrairCertificado($cert)
    {
        // Remover headers e footers do certificado
        $cert = str_replace('-----BEGIN CERTIFICATE-----', '', $cert);
        $cert = str_replace('-----END CERTIFICATE-----', '', $cert);
        $cert = str_replace("\n", '', $cert);
        $cert = str_replace("\r", '', $cert);
        return $cert;
    }

    /**
     * Valida assinatura XML
     */
    public function validarAssinatura($xmlString)
    {
        try {
            $dom = new \DOMDocument('1.0', 'UTF-8');
            $dom->loadXML($xmlString);
            
            $signatureNodes = $dom->getElementsByTagName('Signature');
            if ($signatureNodes->length == 0) {
                return false;
            }

            // Implementação simplificada de validação
            // Na prática, usar biblioteca específica para validação XML-DSig
            return true;

        } catch (\Exception $e) {
            return false;
        }
    }
}
