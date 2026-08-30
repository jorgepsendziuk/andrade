/**
 * MADThumbnailGallery - Classe para exibir arquivos em modal
 * 
 * Esta classe permite visualizar arquivos da galeria de miniaturas em uma modal,
 * com suporte a PDFs (iframe), imagens (visualização direta) e outros arquivos (download).
 */
class MADThumbnailGallery {
    constructor() {
        this.modal = null;
        this.isInitialized = false;
        this.galleries = new Map(); // Armazena as configurações de cada galeria
        this.currentFile = null;
        
        // Extensões de arquivo suportadas
        this.imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg', 'ico'];
        this.pdfExtensions = ['pdf'];
        
        // Bind dos métodos para manter contexto
        this.handleClick = this.handleClick.bind(this);
        this.closeModal = this.closeModal.bind(this);
        this.handleKeyDown = this.handleKeyDown.bind(this);
        this.handleOutsideClick = this.handleOutsideClick.bind(this);
    }
    
    /**
     * Inicializa a modal para uma galeria específica
     * 
     * @param {Object} config - Configuração da galeria
     * @param {string} config.galleryId - ID da galeria
     * @param {string} config.downloadUrl - URL base para download
     * @param {boolean} config.enableModal - Se deve habilitar a modal
     * @param {Object} config.modalOptions - Opções específicas da modal
     */
    init(config) {
        const defaultConfig = {
            galleryId: 'mad-thumbnail-gallery',
            downloadUrl: 'download.php?file=',
            enableModal: true,
            modalOptions: {
                showFileName: true,
                showDownloadButton: true,
                showCloseButton: true,
                backdrop: true,
                keyboard: true,
                width: null,
                height: null,
                maxWidth: '90vw',
                maxHeight: '90vh'
            }
        };
        
        // Mescla configurações
        const mergedConfig = this.mergeConfig(defaultConfig, config);
        
        if (!mergedConfig.enableModal) {
            return;
        }
        
        // Armazena configuração da galeria
        this.galleries.set(mergedConfig.galleryId, mergedConfig);
        
        // Cria a modal se ainda não existe
        if (!this.isInitialized) {
            this.createModal();
            this.addEventListeners();
            this.isInitialized = true;
        }
        
        // Adiciona listeners para esta galeria específica
        this.attachGalleryListeners(mergedConfig.galleryId, mergedConfig);
    }
    
    /**
     * Mescla configurações de forma recursiva
     */
    mergeConfig(target, source) {
        const result = { ...target };
        
        for (const key in source) {
            if (source[key] && typeof source[key] === 'object' && !Array.isArray(source[key])) {
                result[key] = this.mergeConfig(target[key] || {}, source[key]);
            } else {
                result[key] = source[key];
            }
        }
        
        return result;
    }
    
    /**
     * Cria a estrutura HTML da modal
     */
    createModal() {
        // Remove modal existente se houver
        const existingModal = document.getElementById('mad-thumbnail-modal');
        if (existingModal) {
            existingModal.remove();
        }
        
        const modalHTML = `
            <div id="mad-thumbnail-modal" class="mad-modal" style="display: none;">
                <div class="mad-modal__backdrop"></div>
                <div class="mad-modal__container">
                    <div class="mad-modal__header">
                        <h5 class="mad-modal__title" id="mad-modal-title">Visualizar Arquivo</h5>
                        <button type="button" class="mad-modal__close" id="mad-modal-close" aria-label="Fechar">
                            <span>&times;</span>
                        </button>
                    </div>
                    <div class="mad-modal__body" id="mad-modal-body">
                        <!-- Conteúdo será inserido dinamicamente -->
                    </div>
                    <div class="mad-modal__footer" id="mad-modal-footer">
                        <!-- Botões serão inseridos dinamicamente -->
                    </div>
                </div>
            </div>
        `;
        
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        this.modal = document.getElementById('mad-thumbnail-modal');
        
        // Adiciona CSS da modal
        this.addModalCSS();
    }
    
    /**
     * Adiciona CSS da modal
     */
    addModalCSS() {
        const cssId = 'mad-modal-css';
        
        if (document.getElementById(cssId)) {
            return;
        }
        
        const css = `
            /* MADThumbnailGallery CSS */
            .mad-modal {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                z-index: 9999;
                display: flex;
                align-items: center;
                justify-content: center;
                opacity: 0;
                transition: opacity 0.3s ease;
            }
            
            .mad-modal.mad-modal--show {
                opacity: 1;
            }
            
            .mad-modal__backdrop {
                position: absolute;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.8);
                cursor: pointer;
            }
            
            .mad-modal__container {
                position: relative;
                background: white;
                border-radius: 8px;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
                max-width: 90vw;
                max-height: 90vh;
                display: flex;
                flex-direction: column;
                transform: scale(0.9);
                transition: transform 0.3s ease;
            }
            
            .mad-modal.mad-modal--show .mad-modal__container {
                transform: scale(1);
            }
            
            .mad-modal__header {
                padding: 15px 20px;
                border-bottom: 1px solid #dee2e6;
                display: flex;
                justify-content: space-between;
                align-items: center;
                background: #f8f9fa;
                border-radius: 8px 8px 0 0;
            }
            
            .mad-modal__title {
                margin: 0;
                font-size: 16px;
                font-weight: 600;
                color: #333;
            }
            
            .mad-modal__close {
                background: none;
                border: none;
                font-size: 24px;
                cursor: pointer;
                color: #666;
                padding: 0;
                width: 30px;
                height: 30px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 4px;
                transition: all 0.2s ease;
            }
            
            .mad-modal__close:hover {
                background: #e9ecef;
                color: #333;
            }
            
            .mad-modal__body {
                padding: 20px;
                flex: 1;
                overflow: auto;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 200px;
            }
            
            .mad-modal__footer {
                padding: 15px 20px;
                border-top: 1px solid #dee2e6;
                background: #f8f9fa;
                border-radius: 0 0 8px 8px;
                display: flex;
                justify-content: flex-end;
                gap: 10px;
            }
            
            .mad-modal__footer:empty {
                display: none;
            }
            
            /* Conteúdo específico */
            .mad-modal__image {
                max-width: 100%;
                max-height: 70vh;
                object-fit: contain;
                border-radius: 4px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            }
            
            .mad-modal__iframe {
                width: 80vw;
                height: 70vh;
                border: none;
                border-radius: 4px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            }
            
            .mad-modal__file-info {
                text-align: center;
                padding: 40px 20px;
            }
            
            .mad-modal__file-icon {
                font-size: 4rem;
                margin-bottom: 20px;
                display: block;
            }
            
            .mad-modal__file-name {
                font-size: 18px;
                font-weight: 500;
                margin-bottom: 10px;
                color: #333;
                word-break: break-all;
            }
            
            .mad-modal__file-size {
                color: #666;
                font-size: 14px;
                margin-bottom: 20px;
            }
            
            /* Botões */
            .mad-modal__btn {
                padding: 8px 16px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 14px;
                font-weight: 500;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                transition: all 0.2s ease;
            }
            
            .mad-modal__btn--primary {
                background: #007bff;
                color: white;
            }
            
            .mad-modal__btn--primary:hover {
                background: #0056b3;
                color: white;
                text-decoration: none;
            }
            
            .mad-modal__btn--secondary {
                background: #6c757d;
                color: white;
            }
            
            .mad-modal__btn--secondary:hover {
                background: #545b62;
                color: white;
                text-decoration: none;
            }
            
            /* Loading */
            .mad-modal__loading {
                display: flex;
                align-items: center;
                justify-content: center;
                flex-direction: column;
                padding: 40px;
                color: #666;
            }
            
            .mad-modal__spinner {
                width: 40px;
                height: 40px;
                border: 4px solid #f3f3f3;
                border-top: 4px solid #007bff;
                border-radius: 50%;
                animation: mad-modal-spin 1s linear infinite;
                margin-bottom: 15px;
            }
            
            @keyframes mad-modal-spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            
            /* Responsividade */
            @media (max-width: 768px) {
                .mad-modal__container {
                    max-width: 95vw;
                    max-height: 95vh;
                    margin: 10px;
                }
                
                .mad-modal__iframe {
                    width: 100%;
                    height: 60vh;
                }
                
                .mad-modal__header {
                    padding: 10px 15px;
                }
                
                .mad-modal__body {
                    padding: 15px;
                }
                
                .mad-modal__footer {
                    padding: 10px 15px;
                    flex-direction: column;
                }
                
                .mad-modal__btn {
                    width: 100%;
                    justify-content: center;
                }
            }
        `;
        
        const style = document.createElement('style');
        style.id = cssId;
        style.textContent = css;
        document.head.appendChild(style);
    }
    
    /**
     * Adiciona event listeners globais
     */
    addEventListeners() {
        // Fechar modal com ESC
        document.addEventListener('keydown', this.handleKeyDown);
        
        // Fechar modal clicando no backdrop ou botão fechar
        this.modal.addEventListener('click', this.handleOutsideClick);
        
        const closeButton = document.getElementById('mad-modal-close');
        if (closeButton) {
            closeButton.addEventListener('click', this.closeModal);
        }
    }
    
    /**
     * Adiciona listeners para uma galeria específica
     */
    attachGalleryListeners(galleryId, config) {
     
        const that = this;
        // Adiciona listener para cliques nos links da galeria
        $(`#${galleryId} .mad-thumbnail-gallery__link`).on('click', function(e) {        
            const link = $(this);

            if (link) {
                e.preventDefault();
                e.stopPropagation();
                
                const fileName = that.extractFileNameFromLink(link);
                that.openModal(fileName, config);
            }
        });
    }
    
    /**
     * Extrai nome do arquivo do link
     */
    extractFileNameFromLink(link) {
       return link.attr('data-file-name')
    }
    
    /**
     * Manipula cliques
     */
    handleClick(e) {
        const link = e.target.closest('.mad-thumbnail-gallery__link');
        if (!link) return;
        
        e.preventDefault();
        e.stopPropagation();
        
        const fileName = this.extractFileNameFromLink(link);
        const galleryElement = link.closest('.mad-thumbnail-gallery');
        const galleryId = galleryElement?.id || galleryElement?.getAttribute('data-gallery-id') || 'default';
        
        const config = this.galleries.get(galleryId) || this.galleries.values().next().value;
        if (config) {
            this.openModal(fileName, config);
        }
    }
    
    /**
     * Abre a modal com o arquivo especificado
     */
    openModal(fileName, config) {
        if (!this.modal) return;
        
        // Verifica se fileName já é uma URL completa
        const fileUrl = this.isFullUrl(fileName) 
            ? fileName 
            : config.downloadUrl + encodeURIComponent(fileName);
        
        this.currentFile = {
            name: this.extractDisplayName(fileName),
            url: fileUrl,
            config: config
        };
        
        // Atualiza título
        const title = document.getElementById('mad-modal-title');
        if (title && config.modalOptions.showFileName) {
            title.textContent = this.currentFile.name;
        }
        
        // Aplica dimensões ao container
        const container = this.modal.querySelector('.mad-modal__container');
        if (container && config.modalOptions) {
            if (config.modalOptions.width) {
                container.style.width = config.modalOptions.width;
            }
            if (config.modalOptions.height) {
                container.style.height = config.modalOptions.height;
            }
            if (config.modalOptions.maxWidth) {
                container.style.maxWidth = config.modalOptions.maxWidth;
            }
            if (config.modalOptions.maxHeight) {
                container.style.maxHeight = config.modalOptions.maxHeight;
            }
        }
        
        // Mostra loading
        this.showLoading();
        
        // Mostra modal
        this.modal.style.display = 'flex';
        setTimeout(() => {
            this.modal.classList.add('mad-modal--show');
        }, 10);
        
        // Carrega conteúdo
        this.loadFileContent(this.currentFile);
        
        // Bloqueia scroll do body
        document.body.style.overflow = 'hidden';
    }
    
    /**
     * Mostra indicador de loading
     */
    showLoading() {
        const body = document.getElementById('mad-modal-body');
        const footer = document.getElementById('mad-modal-footer');
        
        body.innerHTML = `
            <div class="mad-modal__loading">
                <div class="mad-modal__spinner"></div>
                <div>Carregando arquivo...</div>
            </div>
        `;
        
        footer.innerHTML = '';
    }
    
    /**
     * Carrega o conteúdo do arquivo na modal
     */
    loadFileContent(file) {
        const extension = this.getFileExtension(file.name);
        const body = document.getElementById('mad-modal-body');
        const footer = document.getElementById('mad-modal-footer');
        
        if (this.isImage(extension)) {
            this.loadImageContent(file, body, footer);
        } else if (this.isPDF(extension)) {
            this.loadPDFContent(file, body, footer);
        } else {
            this.loadFileInfo(file, body, footer);
        }
    }
    
    /**
     * Carrega conteúdo de imagem
     */
    loadImageContent(file, body, footer) {
        const img = new Image();
        
        img.onload = () => {
            body.innerHTML = `<img src="${file.url}" alt="${file.name}" class="mad-modal__image">`;
            this.addFooterButtons(file, footer);
        };
        
        img.onerror = () => {
            this.loadFileInfo(file, body, footer, 'Erro ao carregar imagem');
        };
        
        img.src = file.url;
    }
    
    /**
     * Carrega conteúdo de PDF
     */
    loadPDFContent(file, body, footer) {
        body.innerHTML = `
            <iframe src="${file.url}" class="mad-modal__iframe" >
                <p>Seu navegador não suporta PDFs. 
                <a href="${file.url}" target="_blank">Clique aqui para baixar o arquivo</a></p>
            </iframe>
        `;
        
        this.addFooterButtons(file, footer);
    }
    
    /**
     * Carrega informações do arquivo (outros tipos)
     */
    loadFileInfo(file, body, footer, errorMessage = null) {
        const extension = this.getFileExtension(file.name);
        const iconClass = this.getFileIcon(extension);
        
        body.innerHTML = `
            <div class="mad-modal__file-info">
                <i class="${iconClass} mad-modal__file-icon"></i>
                <div class="mad-modal__file-name">${file.name}</div>
                ${errorMessage ? `<div style="color: #dc3545; margin-bottom: 15px;">${errorMessage}</div>` : ''}
                <div class="mad-modal__file-size">Clique em "Download" para baixar o arquivo</div>
            </div>
        `;
        
        this.addFooterButtons(file, footer, true);
    }
    
    /**
     * Adiciona botões no footer
     */
    addFooterButtons(file, footer, emphasizeDownload = false) {
        const buttons = [];
        
        if (file.config.modalOptions.showDownloadButton) {
            const downloadClass =  'mad-modal__btn--primary';
            buttons.push(`
                <a href="${file.url}" target="_blank" class="mad-modal__btn ${downloadClass}">
                    <i class="fas fa-download"></i>
                    Download
                </a>
            `);
        }
        
        buttons.push(`
            <button type="button" class="mad-modal__btn mad-modal__btn--secondary" onclick="window.MADThumbnailGallery.closeModal()">
                Fechar
            </button>
        `);
        
        footer.innerHTML = buttons.join('');
    }
    
    /**
     * Fecha a modal
     */
    closeModal() {
        if (!this.modal) return;
        
        this.modal.classList.remove('mad-modal--show');
        
        setTimeout(() => {
            this.modal.style.display = 'none';
            document.body.style.overflow = '';
        }, 300);
        
        this.currentFile = null;
    }
    
    /**
     * Manipula teclas
     */
    handleKeyDown(e) {
        if (e.key === 'Escape' && this.modal && this.modal.style.display !== 'none') {
            this.closeModal();
        }
    }
    
    /**
     * Manipula cliques fora da modal
     */
    handleOutsideClick(e) {
        if (e.target.classList.contains('mad-modal__backdrop')) {
            this.closeModal();
        }
    }
    
    /**
     * Utilitários
     */
    getFileExtension(fileName) {
        return fileName.split('.').pop().toLowerCase();
    }
    
    isImage(extension) {
        return this.imageExtensions.includes(extension);
    }
    
    isPDF(extension) {
        return this.pdfExtensions.includes(extension);
    }
    
    getFileIcon(extension) {
        const iconMap = {
            pdf: 'fas fa-file-pdf text-danger',
            doc: 'fas fa-file-word text-primary',
            docx: 'fas fa-file-word text-primary',
            xls: 'fas fa-file-excel text-success',
            xlsx: 'fas fa-file-excel text-success',
            ppt: 'fas fa-file-powerpoint text-warning',
            pptx: 'fas fa-file-powerpoint text-warning',
            zip: 'fas fa-file-archive text-dark',
            rar: 'fas fa-file-archive text-dark',
            txt: 'fas fa-file-alt text-secondary',
            default: 'fas fa-file text-muted'
        };
        
        return iconMap[extension] || iconMap.default;
    }
    
    /**
     * Verifica se é uma URL completa
     */
    isFullUrl(str) {
        if (!str) return false;
        return str.startsWith('http://') || 
               str.startsWith('https://') || 
               str.startsWith('//');
    }
    
    /**
     * Extrai nome de exibição de uma URL ou caminho
     */
    extractDisplayName(fileNameOrUrl) {
        if (!fileNameOrUrl) return 'Arquivo';
        
        // Se for URL completa, extrai o nome do arquivo da URL
        if (this.isFullUrl(fileNameOrUrl)) {
            try {
                const url = new URL(fileNameOrUrl.startsWith('//') ? 'https:' + fileNameOrUrl : fileNameOrUrl);
                const pathname = url.pathname;
                const fileName = pathname.split('/').pop();
                return decodeURIComponent(fileName) || 'Arquivo';
            } catch (e) {
                return 'Arquivo';
            }
        }
        
        // Se for caminho simples, retorna como está
        return fileNameOrUrl;
    }
    
    /**
     * Destrói a instância
     */
    destroy() {
        if (this.modal) {
            this.modal.remove();
        }
        
        document.removeEventListener('keydown', this.handleKeyDown);
        this.galleries.clear();
        this.isInitialized = false;
    }
}

// Cria instância global
window.MADThumbnailGallery = new MADThumbnailGallery();
