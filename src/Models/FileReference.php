<?php

namespace Ged\ApiLaravel\Models;

use Ged\ApiLaravel\Exceptions\GedApiException;

/**
 * ============================================================================
 * FileReference - Referência de Arquivo
 * ============================================================================
 *
 * Abstrai as diferentes formas de referenciar um arquivo:
 * - Caminho no sistema de arquivos
 * - Conteúdo em base64
 * - Conteúdo bruto (binário)
 * - Resultado de operação anterior
 * - URL remota
 *
 * Facilita o envio de arquivos para a API de múltiplas formas
 * ============================================================================
 */
class FileReference
{
    /**
     * Caminho do arquivo no sistema
     * @var string|null
     */
    private ?string $path = null;
    
    /**
     * Conteúdo do arquivo em base64
     * @var string|null
     */
    private ?string $base64Content = null;
    
    /**
     * Conteúdo do arquivo bruto (binário)
     * @var string|null
     */
    private ?string $rawContent = null;
    
    /**
     * Token de resultado de operação anterior
     * @var string|null
     */
    private ?string $resultToken = null;
    
    /**
     * URL remota do arquivo
     * @var string|null
     */
    private ?string $url = null;
    
    /**
     * Cria referência a partir de um caminho de arquivo
     * 
     * @param string $path Caminho do arquivo
     * @return self
     * @throws GedApiException Se o arquivo não existir
     */
    public static function fromFile(string $path): self
    {
        if (!file_exists($path)) {
            throw new GedApiException("Arquivo não encontrado: {$path}");
        }
        
        if (!is_readable($path)) {
            throw new GedApiException("Arquivo não pode ser lido: {$path}");
        }
        
        $ref = new self();
        $ref->path = $path;
        
        return $ref;
    }
    
    /**
     * Cria referência a partir de conteúdo em base64
     * 
     * @param string $base64 Conteúdo em base64
     * @return self
     * @throws GedApiException Se o base64 for inválido
     */
    public static function fromContentBase64(string $base64): self
    {
        // Validar se é base64 válido
        if (base64_decode($base64, true) === false) {
            throw new GedApiException("Conteúdo base64 inválido");
        }
        
        $ref = new self();
        $ref->base64Content = $base64;
        
        return $ref;
    }
    
    /**
     * Cria referência a partir de conteúdo bruto (binário)
     * 
     * @param string $content Conteúdo bruto
     * @return self
     */
    public static function fromContentRaw(string $content): self
    {
        $ref = new self();
        $ref->rawContent = $content;
        
        return $ref;
    }
    
    /**
     * Cria referência a partir de resultado de operação anterior
     * 
     * @param string $token Token do resultado
     * @return self
     */
    public static function fromResult(string $token): self
    {
        $ref = new self();
        $ref->resultToken = $token;
        
        return $ref;
    }
    
    /**
     * Cria referência a partir de URL remota
     * 
     * @param string $url URL do arquivo
     * @return self
     * @throws GedApiException Se a URL for inválida
     */
    public static function fromUrl(string $url): self
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new GedApiException("URL inválida: {$url}");
        }
        
        $ref = new self();
        $ref->url = $url;
        
        return $ref;
    }
    
    /**
     * Retorna o conteúdo do arquivo em formato bruto
     * 
     * @return string Conteúdo bruto
     * @throws GedApiException Se não conseguir obter o conteúdo
     */
    public function getContentRaw(): string
    {
        // Já tem conteúdo bruto
        if ($this->rawContent !== null) {
            return $this->rawContent;
        }
        
        // Tem base64, decodificar
        if ($this->base64Content !== null) {
            return base64_decode($this->base64Content);
        }
        
        // Tem caminho, ler arquivo
        if ($this->path !== null) {
            $content = file_get_contents($this->path);
            if ($content === false) {
                throw new GedApiException("Erro ao ler arquivo: {$this->path}");
            }
            return $content;
        }
        
        // Tem URL, baixar
        if ($this->url !== null) {
            $content = @file_get_contents($this->url);
            if ($content === false) {
                throw new GedApiException("Erro ao baixar arquivo de: {$this->url}");
            }
            return $content;
        }
        
        throw new GedApiException("Nenhum conteúdo disponível");
    }
    
    /**
     * Retorna o conteúdo do arquivo em base64
     * 
     * @return string Conteúdo em base64
     */
    public function getContentBase64(): string
    {
        if ($this->base64Content !== null) {
            return $this->base64Content;
        }
        
        return base64_encode($this->getContentRaw());
    }
    
    /**
     * Retorna o caminho do arquivo (se disponível)
     * 
     * @return string|null Caminho do arquivo
     */
    public function getPath(): ?string
    {
        return $this->path;
    }
    
    /**
     * Retorna o token de resultado (se disponível)
     * 
     * @return string|null Token do resultado
     */
    public function getResultToken(): ?string
    {
        return $this->resultToken;
    }
    
    /**
     * Retorna a URL (se disponível)
     * 
     * @return string|null URL do arquivo
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }
    
    /**
     * Verifica se é uma referência a arquivo local
     * 
     * @return bool True se for arquivo local
     */
    public function isLocalFile(): bool
    {
        return $this->path !== null;
    }
    
    /**
     * Verifica se é uma referência a conteúdo em memória
     * 
     * @return bool True se for conteúdo em memória
     */
    public function isContentBased(): bool
    {
        return $this->base64Content !== null || $this->rawContent !== null;
    }
    
    /**
     * Verifica se é uma referência a resultado anterior
     * 
     * @return bool True se for resultado anterior
     */
    public function isResultBased(): bool
    {
        return $this->resultToken !== null;
    }
    
    /**
     * Verifica se é uma referência a URL remota
     * 
     * @return bool True se for URL remota
     */
    public function isUrlBased(): bool
    {
        return $this->url !== null;
    }
    
    /**
     * Retorna o tamanho do arquivo em bytes
     * 
     * @return int Tamanho em bytes
     * @throws GedApiException Se não conseguir determinar o tamanho
     */
    public function getSize(): int
    {
        if ($this->path !== null) {
            $size = filesize($this->path);
            if ($size === false) {
                throw new GedApiException("Erro ao obter tamanho do arquivo");
            }
            return $size;
        }
        
        return strlen($this->getContentRaw());
    }
    
    /**
     * Retorna o tamanho do arquivo formatado (KB, MB, etc.)
     * 
     * @return string Tamanho formatado
     */
    public function getFormattedSize(): string
    {
        $bytes = $this->getSize();
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        
        return number_format($bytes / pow(1024, $power), 2, ',', '.') . ' ' . $units[$power];
    }
    
    /**
     * Calcula o hash do conteúdo
     * 
     * @param string $algorithm Algoritmo (md5, sha1, sha256, etc.)
     * @return string Hash hexadecimal
     */
    public function computeHash(string $algorithm = 'sha256'): string
    {
        return hash($algorithm, $this->getContentRaw());
    }
    
    /**
     * Calcula múltiplos hashes do conteúdo
     * 
     * @param array $algorithms Lista de algoritmos
     * @return array Mapa [algoritmo => hash]
     */
    public function computeDataHashes(array $algorithms): array
    {
        $content = $this->getContentRaw();
        $hashes = [];
        
        foreach ($algorithms as $algorithm) {
            $hashes[$algorithm] = hash($algorithm, $content);
        }
        
        return $hashes;
    }
    
    /**
     * Salva o conteúdo em um arquivo
     * 
     * @param string $destinationPath Caminho de destino
     * @return bool True se salvou com sucesso
     * @throws GedApiException Se não conseguir salvar
     */
    public function saveToFile(string $destinationPath): bool
    {
        $content = $this->getContentRaw();
        
        $result = file_put_contents($destinationPath, $content);
        
        if ($result === false) {
            throw new GedApiException("Erro ao salvar arquivo em: {$destinationPath}");
        }
        
        return true;
    }
    
    /**
     * Prepara o arquivo para upload/referência na API
     * 
     * @return array Payload para API
     */
    public function prepareForUpload(): array
    {
        if ($this->resultToken !== null) {
            return ['resultToken' => $this->resultToken];
        }
        
        if ($this->url !== null) {
            return ['url' => $this->url];
        }
        
        return ['content' => $this->getContentBase64()];
    }
    
    /**
     * Detecta o tipo MIME do arquivo
     * 
     * @return string Tipo MIME
     */
    public function getMimeType(): string
    {
        if ($this->path !== null && function_exists('mime_content_type')) {
            $mime = mime_content_type($this->path);
            if ($mime !== false) {
                return $mime;
            }
        }
        
        // Fallback: detectar por conteúdo
        $content = $this->getContentRaw();
        
        // PDF
        if (str_starts_with($content, '%PDF')) {
            return 'application/pdf';
        }
        
        return 'application/octet-stream';
    }
    
    /**
     * Verifica se o arquivo é um PDF
     * 
     * @return bool True se for PDF
     */
    public function isPdf(): bool
    {
        return $this->getMimeType() === 'application/pdf';
    }
    
    /**
     * Retorna representação em string para debug
     * 
     * @return string
     */
    public function __toString(): string
    {
        if ($this->path !== null) {
            return "FileReference(path={$this->path})";
        }
        
        if ($this->resultToken !== null) {
            return "FileReference(token={$this->resultToken})";
        }
        
        if ($this->url !== null) {
            return "FileReference(url={$this->url})";
        }
        
        return "FileReference(content={$this->getFormattedSize()})";
    }
}

