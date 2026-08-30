<?php

/**
 * MADThumbnailGallery - Renderizador de galeria de miniaturas
 * 
 * Esta classe é especializada em transformar valores de colunas contendo arquivos
 * em uma galeria visual com miniaturas para imagens e ícones para documentos.
 * Inclui funcionalidade de modal para visualização avançada de arquivos.
 * 
 * Suporta tanto caminhos locais quanto URLs completas de CDN:
 * - Caminhos locais: 'arquivo.pdf', 'imagem.jpg'
 * - URLs completas: 'https://cdn.example.com/arquivo.pdf', '//cdn.example.com/imagem.jpg'
 * 
 * Exemplo de uso com URLs de CDN:
 * MADThumbnailGallery::render([
 *     'https://cdn.example.com/image1.jpg',
 *     'https://cdn.example.com/document.pdf',
 *     'arquivo-local.jpg'
 * ], ['enableModal' => true, 'modalOptions' => ['width' => '90vw', 'maxWidth' => '95vw']]);
 * 
 */
class MADThumbnailGallery
{
    /**
     * Configurações padrão da galeria
     */
    private static $defaultConfig = [
        'downloadUrl' => 'download.php?file=',
        'thumbnailWidth' => 120,
        'thumbnailHeight' => 80,
        'showFileName' => true,
        'maxFileNameLength' => 15,
        'enableHover' => true,
        'enableTooltip' => true,
        'galleryGap' => '8px',
        'preventEventPropagation' => true,
        // Configurações da modal
        'enableModal' => false,
        'modalOptions' => [
            'showFileName' => true,
            'showDownloadButton' => true,
            'showCloseButton' => true,
            'backdrop' => true,
            'keyboard' => true,
            'width' => null,
            'height' => null,
            'maxWidth' => '90vw',
            'maxHeight' => '90vh'
        ]
    ];
    
    /**
     * Mapeamento de extensões para ícones Font Awesome 5
     */
    private static $iconMap = [
        // Documentos
        'pdf' => 'fas fa-file-pdf text-danger',
        'doc' => 'fas fa-file-word text-primary',
        'docx' => 'fas fa-file-word text-primary',
        'xls' => 'fas fa-file-excel text-success',
        'xlsx' => 'fas fa-file-excel text-success',
        'ppt' => 'fas fa-file-powerpoint text-warning',
        'pptx' => 'fas fa-file-powerpoint text-warning',
        
        // Imagens
        'jpg' => 'fas fa-file-image text-info',
        'jpeg' => 'fas fa-file-image text-info',
        'png' => 'fas fa-file-image text-info',
        'gif' => 'fas fa-file-image text-info',
        'svg' => 'fas fa-file-image text-info',
        'bmp' => 'fas fa-file-image text-info',
        'webp' => 'fas fa-file-image text-info',
        
        // Arquivos de texto
        'txt' => 'fas fa-file-alt text-secondary',
        'rtf' => 'fas fa-file-alt text-secondary',
        'md' => 'fas fa-file-alt text-secondary',
        
        // Arquivos comprimidos
        'zip' => 'fas fa-file-archive text-dark',
        'rar' => 'fas fa-file-archive text-dark',
        '7z' => 'fas fa-file-archive text-dark',
        'tar' => 'fas fa-file-archive text-dark',
        'gz' => 'fas fa-file-archive text-dark',
        
        // Código
        'php' => 'fas fa-file-code text-info',
        'js' => 'fas fa-file-code text-warning',
        'css' => 'fas fa-file-code text-info',
        'html' => 'fas fa-file-code text-danger',
        'xml' => 'fas fa-file-code text-warning',
        'json' => 'fas fa-file-code text-success',
        
        // Áudio/Vídeo
        'mp3' => 'fas fa-file-audio text-success',
        'wav' => 'fas fa-file-audio text-success',
        'mp4' => 'fas fa-file-video text-danger',
        'avi' => 'fas fa-file-video text-danger',
        'mov' => 'fas fa-file-video text-danger',
        
        // Padrão
        'default' => 'fas fa-file text-muted'
    ];
    
    /**
     * Extensões consideradas imagens (exibirão miniatura)
     */
    private static $imageExtensions = [
        'jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg', 'ico'
    ];
    
    /**
     * Merge recursivo de arrays
     * 
     * @param array $array1
     * @param array $array2
     * @return array
     */
    private static function arrayMergeRecursive(array $array1, array $array2)
    {
        $merged = $array1;
        
        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = self::arrayMergeRecursive($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }
        
        return $merged;
    }
    
    /**
     * Renderiza uma galeria de miniaturas
     * 
     * @param mixed $value Valor da coluna (string com vírgulas, array, ou string única)
     * @param array $config Configurações personalizadas
     * @return TElement|string Galeria renderizada ou valor original em caso de erro
     */
    public static function render($value, array $config = [])
    {
        try {
            // Mescla configurações recursivamente
            $config = self::arrayMergeRecursive(self::$defaultConfig, $config);
            
            // Normaliza valor para array
            $files = self::parseFileValue($value);
            
            if (empty($files)) {
                return '';
            }
            
            $config['galleryId'] = 'mad-gallery-' . uniqid();
            // Cria container da galeria
            $gallery = self::createGalleryContainer($config);
            
            // Adiciona cada arquivo
            foreach ($files as $file) {
                $fileElement = self::createFileItem($file, $config);
                if ($fileElement) {
                    $gallery->add($fileElement);
                }
            }

            if($config['enableModal'])
            {
                $gallery->add(self::initModal($config['galleryId'], $config));
            }
            
            return $gallery;
            
        } catch (Exception $e) {
            error_log("[MADThumbnailGallery] Erro: " . $e->getMessage());
            return self::fallbackRender($value);
        }
    }
    
    /**
     * Converte valor em array de arquivos
     * 
     * @param mixed $value
     * @return array
     */
    private static function parseFileValue($value)
    {
        // Se já é array, filtra valores vazios
        if (is_array($value)) {
            return array_filter(array_map('trim', $value));
        }
        
        // Se é string vazia ou null
        if (empty($value)) {
            return [];
        }
        
        // Se contém vírgulas, faz explode
        if (is_string($value) && strpos($value, ',') !== false) {
            return array_filter(array_map('trim', explode(',', $value)));
        }
        
        // Valor único
        return [trim($value)];
    }
    
    /**
     * Cria o container principal da galeria
     * 
     * @param array $config
     * @return TElement
     */
    private static function createGalleryContainer(array $config)
    {
        $container = new TElement('div');
        $container->class = 'mad-thumbnail-gallery d-flex flex-wrap';
        $container->style = "gap: {$config['galleryGap']};";
        $container->{'data-mad-gallery'} = 'true';
        
        // Adiciona atributos para modal se habilitada
        if ($config['enableModal']) {
            $container->{'data-modal-enabled'} = 'true';
            $container->{'data-download-url'} = $config['downloadUrl'];
            
            // Gera ID único se não especificado
            
            if (!$config['galleryId']) {
                $container->id = 'mad-gallery-' . uniqid();
            }
            else{
                $container->id = $config['galleryId'];
            }
            $container->{'data-gallery-id'} = $container->id;
        }
        
        return $container;
    }
    
    /**
     * Cria um item de arquivo individual
     * 
     * @param string $file
     * @param array $config
     * @return TElement|null
     */
    private static function createFileItem($file, array $config)
    {
        if (empty($file)) {
            return null;
        }
        
        $fileName = self::extractFileName($file);

        if(!$fileName)
        {
            return null;
        }

        // Container do item
        $item = new TElement('div');
        $item->class = 'mad-thumbnail-gallery__item';
        $item->style = "width: {$config['thumbnailWidth']}px;";
        
        if ($config['enableHover']) {
            $item->class .= ' mad-thumbnail-gallery__item--hover';
        }
        
        // Link de download
        $link = self::createDownloadLink($fileName, $config);
        
        // Conteúdo visual (miniatura ou ícone)
        $visual = self::createVisualElement($fileName, $config);
        $link->add($visual);
        
        $item->add($link);
        
        // Nome do arquivo (opcional)
        if ($config['showFileName']) {
            $nameElement = self::createFileNameElement($fileName, $config);
            $item->add($nameElement);
        }
        
        return $item;
    }
    
    /**
     * Extrai nome do arquivo (decodifica JSON se necessário)
     * 
     * @param string $file
     * @return string
     */
    private static function extractFileName($file)
    {
        // Verifica se está em formato JSON codificado
        if (strpos($file, '%7B') !== false) {
            try {
                $decoded = json_decode(urldecode($file), true);
                
                if(!empty($decoded['delFile']))
                {
                    return false;
                }

                if (isset($decoded['fileName'])) {
                    return $decoded['fileName'];
                }
            } catch (Exception $e) {
                // Se falhar, usa o valor original
            }
        }
        
        return $file;
    }
    
    /**
     * Cria link de download
     * 
     * @param string $fileName
     * @param array $config
     * @return TElement
     */
    private static function createDownloadLink($fileName, array $config)
    {
        $link = new TElement('a');
        $link->class = 'mad-thumbnail-gallery__link';
        
        $fileUrl = self::buildFileUrl($fileName, $config);
        
        // Se modal está habilitada, o JavaScript vai interceptar os cliques
        if ($config['enableModal']) {
            // Remove comportamento padrão para permitir que a modal funcione
            $link->href = '#';
            $link->{'data-file-url'} = $fileUrl;
            $link->{'data-file-name'} = $fileName;
            
            // Ainda previne propagação se configurado
            if ($config['preventEventPropagation']) {
                $link->onclick = 'event.stopPropagation();';
            }
        } else {
            // Comportamento normal de download
            $link->href = $fileUrl;
            $link->target = '_blank';
            
            // Previne propagação de eventos se habilitado
            if ($config['preventEventPropagation']) {
                $link->onclick = 'event.stopPropagation(); return true;';
                $link->{'data-no-propagate'} = 'true';
            }
        }
        
        if ($config['enableTooltip']) {
            $link->title = $fileName;
        }
        
        return $link;
    }
    
    /**
     * Cria elemento visual (miniatura ou ícone)
     * 
     * @param string $fileName
     * @param array $config
     * @return TElement
     */
    private static function createVisualElement($fileName, array $config)
    {
        if (self::isImage($fileName)) {
            return self::createImageThumbnail($fileName, $config);
        } else {
            return self::createFileIcon($fileName, $config);
        }
    }
    
    /**
     * Verifica se é arquivo de imagem
     * 
     * @param string $fileName
     * @return bool
     */
    private static function isImage($fileName)
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        return in_array($extension, self::$imageExtensions);
    }
    
    /**
     * Verifica se é uma URL completa
     * 
     * @param string $str
     * @return bool
     */
    private static function isFullUrl($str)
    {
        if (empty($str)) return false;
        return (strpos($str, 'http://') === 0 || 
                strpos($str, 'https://') === 0 || 
                strpos($str, '//') === 0);
    }
    
    /**
     * Constrói URL completa do arquivo
     * 
     * @param string $fileName
     * @param array $config
     * @return string
     */
    private static function buildFileUrl($fileName, array $config)
    {
        // Se já é URL completa, retorna como está
        if (self::isFullUrl($fileName)) {
            return $fileName;
        }
        
        // Caso contrário, concatena com downloadUrl
        return $config['downloadUrl'] . urlencode($fileName);
    }
    
    /**
     * Cria miniatura de imagem
     * 
     * @param string $fileName
     * @param array $config
     * @return TElement
     */
    private static function createImageThumbnail($fileName, array $config)
    {
        $container = new TElement('div');
        $container->class = 'mad-thumbnail-gallery__thumbnail';
        $container->style = "height: {$config['thumbnailHeight']}px;";
        
        $img = new TElement('img');
        $img->src = self::buildFileUrl($fileName, $config);
        $img->class = 'mad-thumbnail-gallery__image';
        $img->style = "max-height: " . ($config['thumbnailHeight'] - 8) . "px;";
        $img->alt = pathinfo($fileName, PATHINFO_FILENAME);
        $img->loading = 'lazy';
        
        $container->add($img);
        return $container;
    }
    
    /**
     * Cria ícone de arquivo
     * 
     * @param string $fileName
     * @param array $config
     * @return TElement
     */
    private static function createFileIcon($fileName, array $config)
    {
        $container = new TElement('div');
        $container->class = 'mad-thumbnail-gallery__icon';
        $container->style = "height: {$config['thumbnailHeight']}px;";
        
        $icon = new TElement('i');
        $icon->class = self::getIconClass($fileName) . ' mad-thumbnail-gallery__icon-element';
        
        $container->add($icon);
        return $container;
    }
    
    /**
     * Obtém classe do ícone baseada na extensão
     * 
     * @param string $fileName
     * @return string
     */
    private static function getIconClass($fileName)
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        return self::$iconMap[$extension] ?? self::$iconMap['default'];
    }
    
    /**
     * Cria elemento do nome do arquivo
     * 
     * @param string $fileName
     * @param array $config
     * @return TElement
     */
    private static function createFileNameElement($fileName, array $config)
    {
        $nameElement = new TElement('div');
        $nameElement->class = 'mad-thumbnail-gallery__filename';
        $nameElement->style = "max-width: " . ($config['thumbnailWidth'] - 10) . "px;";
        
        // Trunca nome se muito longo
        $displayName = self::truncateFileName($fileName, $config['maxFileNameLength']);
        $nameElement->add($displayName);
        
        return $nameElement;
    }
    
    /**
     * Trunca nome do arquivo
     * 
     * @param string $fileName
     * @param int $maxLength
     * @return string
     */
    private static function truncateFileName($fileName, $maxLength)
    {
        if (strlen($fileName) <= $maxLength) {
            return $fileName;
        }
        
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        $name = pathinfo($fileName, PATHINFO_FILENAME);
        
        $availableLength = $maxLength - strlen($extension) - 4; // 4 para "..." + "."
        
        if ($availableLength > 0) {
            return substr($name, 0, $availableLength) . '...' . ($extension ? '.' . $extension : '');
        }
        
        return substr($fileName, 0, $maxLength - 3) . '...';
    }
    
    /**
     * Renderização de fallback em caso de erro
     * 
     * @param mixed $value
     * @return string
     */
    private static function fallbackRender($value)
    {
        if (is_string($value)) {
            return $value;
        }
        
        if (is_array($value)) {
            return implode(', ', $value);
        }
        
        return '';
    }
    
    
    /**
     * Renderiza galeria com modal habilitada
     * 
     * @param mixed $value Valor da coluna
     * @param array $config Configurações
     * @return TElement|string
     */
    public static function renderWithModal($value, array $config = [])
    {
        $config['enableModal'] = true;
        return self::render($value, $config);
    }
    
    /**
     * Inicializa modal para uma galeria específica via JavaScript
     * 
     * @param string $galleryId ID da galeria
     * @param array $modalConfig Configurações da modal
     * @return string Código JavaScript para inicialização
     */
    public static function initModal($galleryId, array $config = [])
    {
        $defaultModalConfig = [
            'galleryId' => $galleryId,
            'downloadUrl' => 'download.php?file=',
            'enableModal' => true,
            'modalOptions' => [
                'showFileName' => true,
                'showDownloadButton' => true,
                'showCloseButton' => true,
                'backdrop' => true,
                'keyboard' => true,
                'width' => null,
                'height' => null,
                'maxWidth' => '90vw',
                'maxHeight' => '90vh'
            ]
        ];
        
        $config = self::arrayMergeRecursive($defaultModalConfig, $config);
        $configJson = json_encode($config);
        
        return "
        <script>
            window.MADThumbnailGallery.init({$configJson});
        </script>";
    }
    
    /**
     * Cria configuração de modal personalizada
     * 
     * @param array $options Opções da modal
     * @return array
     */
    public static function createModalConfig(array $options = [])
    {
        $defaultOptions = [
            'showFileName' => true,
            'showDownloadButton' => true,
            'showCloseButton' => true,
            'backdrop' => true,
            'keyboard' => true,
            'width' => null,
            'height' => null,
            'maxWidth' => '90vw',
            'maxHeight' => '90vh'
        ];
        
        return array_merge($defaultOptions, $options);
    }
    
    /**
     * Retorna configurações padrão
     * 
     * @return array
     */
    public static function getDefaultConfig()
    {
        return self::$defaultConfig;
    }
    
    /**
     * Define configurações padrão globalmente
     * 
     * @param array $config
     */
    public static function setDefaultConfig(array $config)
    {
        self::$defaultConfig = array_merge(self::$defaultConfig, $config);
    }
    
    /**
     * Adiciona novo mapeamento de ícone
     * 
     * @param string $extension Extensão do arquivo
     * @param string $iconClass Classe do ícone Font Awesome 5
     */
    public static function addIconMapping($extension, $iconClass)
    {
        self::$iconMap[strtolower($extension)] = $iconClass;
    }
    
    /**
     * Adiciona extensão como imagem
     * 
     * @param string $extension
     */
    public static function addImageExtension($extension)
    {
        $extension = strtolower($extension);
        if (!in_array($extension, self::$imageExtensions)) {
            self::$imageExtensions[] = $extension;
        }
    }
    
    /**
     * Obtém todas as extensões de imagem suportadas
     * 
     * @return array
     */
    public static function getImageExtensions()
    {
        return self::$imageExtensions;
    }
    
    /**
     * Obtém mapeamento de ícones
     * 
     * @return array
     */
    public static function getIconMap()
    {
        return self::$iconMap;
    }
    
    /**
     * Define novo mapeamento de ícones
     * 
     * @param array $iconMap
     */
    public static function setIconMap(array $iconMap)
    {
        self::$iconMap = array_merge(self::$iconMap, $iconMap);
    }
    
}

?>