/* PNG Metadata Viewer - Optimized Script with Pagination Support */
jQuery(document).ready(function ($) {
    console.log('PNG Metadata Viewer: Optimized Script Loading...');
    
    // Configuration
    const config = {
        cardsPerPage: 12,
        enableLazyLoading: true,
        masonryEnabled: true
    };
    
    // Get cards per page from settings or use default
    if (typeof pmv_settings !== 'undefined' && pmv_settings.cards_per_page) {
        config.cardsPerPage = parseInt(pmv_settings.cards_per_page);
    }
    
    // Initialize the gallery
    function initializeGallery() {
        console.log('Initializing gallery...');
        
        const $cards = $('.png-card');
        const $grid = $('.png-cards');
        const $spinner = $('.spinner-overlay');
        
        // Hide spinner since we're using server-side pagination
        $spinner.hide();
        
        if ($cards.length === 0) {
            console.log('No cards found');
            return;
        }
        
        console.log(`Found ${$cards.length} cards`);
        
        // Show cards immediately since they're already loaded
        $cards.css({
            'opacity': '1',
            'visibility': 'visible',
            'display': 'block'
        });
        
        // Initialize masonry if enabled and available
        if (config.masonryEnabled && typeof $.fn.masonry !== 'undefined') {
            initializeMasonry();
        } else {
            console.log('Using CSS grid fallback');
            $grid.addClass('css-grid-fallback');
        }
        
        // Setup lazy loading for images if enabled
        if (config.enableLazyLoading) {
            setupLazyLoading();
        }
        
        // Setup modal functionality
        setupModalHandlers();
        
        console.log('Gallery initialization complete');
    }
    
    // Initialize Masonry layout
    function initializeMasonry() {
        const $grid = $('.png-cards');
        
        if (!$grid.length) {
            console.error('No grid element found');
            return;
        }
        
        try {
            // Wait for images to load
            $grid.imagesLoaded(function() {
                console.log('Images loaded, initializing masonry');
                
                $grid.masonry({
                    itemSelector: '.png-card',
                    columnWidth: '.grid-sizer',
                    percentPosition: true,
                    gutter: 15,
                    fitWidth: false
                });
                
                console.log('Masonry initialized');
            });
        } catch (error) {
            console.error('Masonry initialization failed:', error);
            $grid.addClass('css-grid-fallback');
        }
    }
    
    // Setup lazy loading for images
    function setupLazyLoading() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src || img.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });
            
            document.querySelectorAll('img[loading="lazy"]').forEach(img => {
                imageObserver.observe(img);
            });
        }
    }
    
    // Setup modal functionality
    function setupModalHandlers() {
        // Open modal when clicking character image
        $(document).on('click', '.png-image-container img', function(e) {
            e.preventDefault();
            openCharacterModal($(this).closest('.png-card'));
        });
        
        // Close modal
        $(document).on('click', '.close-modal, #png-modal', function(e) {
            if (e.target === this || $(e.target).hasClass('close-modal')) {
                $('#png-modal').fadeOut();
            }
        });
        
        // Chat button functionality
        $(document).on('click', '.png-chat-button', function(e) {
            e.preventDefault();
            const metadata = $(this).attr('data-metadata') || 
                           $(this).closest('.png-card').attr('data-metadata');
            
            if (metadata && window.startChat) {
                window.startChat(metadata);
            } else {
                console.error('Chat functionality not available or no metadata found');
            }
        });
    }
    
    // Open character modal
    function openCharacterModal($card) {
        try {
            const metadataStr = $card.attr('data-metadata');
            const fileUrl = $card.data('file-url');
            
            if (!metadataStr) {
                throw new Error('No metadata found for this character');
            }
            
            const characterData = JSON.parse(metadataStr);
            const character = characterData.data || characterData;
            
            const modalContent = buildModalContent(character, fileUrl, metadataStr);
            $('#modal-content').html(modalContent);
            $('#png-modal').fadeIn();
            
        } catch (error) {
            console.error('Error opening modal:', error);
            alert('Error loading character details: ' + error.message);
        }
    }
    
    // Build modal content
    function buildModalContent(character, fileUrl, metadataStr) {
        const name = character.name || 'Unknown Character';
        const description = character.description || '';
        const personality = character.personality || '';
        const creator = character.creator || '';
        const tags = character.tags || [];
        
        // Escape metadata for chat button
        let escapedMetadata = '';
        try {
            escapedMetadata = encodeURIComponent(metadataStr);
        } catch (e) {
            console.error('Error encoding metadata:', e);
        }
        
        const chatButtonText = (window.pmv_chat_button_text || 'Chat');
        
        return `
            <div class="character-modal-wrapper">
                <div class="character-details">
                    <div class="character-header">
                        <h2>${escapeHtml(name)}</h2>
                        ${creator ? `<div class="character-creator">Created by: ${escapeHtml(creator)}</div>` : ''}
                    </div>
                    
                    <div class="character-image">
                        <img src="${fileUrl}" alt="${escapeHtml(name)}">
                    </div>
                    
                    <div class="character-info">
                        ${description ? `
                            <div class="character-section">
                                <h3>Description</h3>
                                <div class="character-field">${escapeHtml(description)}</div>
                            </div>
                        ` : ''}
                        
                        ${personality ? `
                            <div class="character-section">
                                <h3>Personality</h3>
                                <div class="character-field">${escapeHtml(personality)}</div>
                            </div>
                        ` : ''}
                        
                        ${tags.length > 0 ? `
                            <div class="character-section">
                                <h3>Tags</h3>
                                <div class="character-field">
                                    <div class="tags-container">
                                        ${tags.map(tag => `<span class="tag-item">${escapeHtml(tag)}</span>`).join('')}
                                    </div>
                                </div>
                            </div>
                        ` : ''}
                    </div>
                    
                    <div class="character-footer">
                        <div class="footer-buttons">
                            <a href="${fileUrl}" class="png-download-button" download>Download</a>
                            <button class="png-chat-button" data-metadata="${escapedMetadata}">
                                ${chatButtonText}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }
    
    // AJAX pagination handler (if you want to implement AJAX pagination instead of URL-based)
    function loadPage(page, append = false) {
        if (typeof pmv_ajax_object === 'undefined') {
            console.error('AJAX object not available');
            return;
        }
        
        const $loading = $('.pmv-loading-indicator');
        $loading.show();
        
        $.ajax({
            url: pmv_ajax_object.ajax_url,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'pmv_get_character_cards',
                page: page,
                per_page: config.cardsPerPage,
                nonce: pmv_ajax_object.nonce
            },
            success: function(response) {
                if (response.success) {
                    renderCards(response.data.cards, append);
                    updatePagination(response.data.pagination);
                } else {
                    console.error('AJAX error:', response.data);
                    showError('Failed to load characters: ' + (response.data.message || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX request failed:', error);
                showError('Connection error. Please try again.');
            },
            complete: function() {
                $loading.hide();
            }
        });
    }
    
    // Render cards (for AJAX pagination)
    function renderCards(cards, append = false) {
        const $grid = $('.png-cards');
        
        if (!append) {
            $grid.find('.png-card').remove();
        }
        
        cards.forEach(card => {
            const cardHtml = createCardHtml(card);
            $grid.append(cardHtml);
        });
        
        // Re-initialize masonry
        if (config.masonryEnabled && $grid.data('masonry')) {
            $grid.masonry('reloadItems').masonry('layout');
        }
    }
    
    // Create HTML for a single card
    function createCardHtml(card) {
        const metadataStr = escapeHtml(JSON.stringify(card.metadata));
        const tagsStr = Array.isArray(card.tags) ? card.tags.join(', ') : '';
        
        return `
            <div class="png-card" data-tags="${escapeHtml(tagsStr)}" 
                 data-metadata="${metadataStr}" data-file-url="${escapeHtml(card.file_url)}">
                <div class="png-image-container">
                    <img src="${escapeHtml(card.file_url)}" alt="${escapeHtml(card.name)}" loading="lazy">
                </div>
                <div class="png-card-info">
                    <div class="png-card-name">${escapeHtml(card.name)}</div>
                    ${card.description ? `<div class="png-card-description">${escapeHtml(card.description)}</div>` : ''}
                    ${tagsStr ? `<div class="png-card-tags">${escapeHtml(tagsStr)}</div>` : ''}
                </div>
                <div class="png-card-buttons">
                    <a href="${escapeHtml(card.file_url)}" class="png-download-button" download>Download</a>
                    <button class="png-chat-button" data-metadata="${metadataStr}">
                        ${window.pmv_chat_button_text || 'Chat'}
                    </button>
                </div>
            </div>
        `;
    }
    
    // Update pagination controls
    function updatePagination(pagination) {
        // Implementation depends on your pagination HTML structure
        // This is a placeholder for AJAX pagination
        console.log('Pagination:', pagination);
    }
    
    // Show error message
    function showError(message) {
        const $container = $('.png-cards-loading-wrapper');
        $container.prepend(`
            <div class="pmv-error-message" style="background: #fee; border: 1px solid #fcc; padding: 15px; margin: 10px 0; border-radius: 5px; color: #c33;">
                <strong>Error:</strong> ${escapeHtml(message)}
                <button onclick="$(this).parent().remove()" style="float: right; background: none; border: none; font-size: 18px; cursor: pointer;">&times;</button>
            </div>
        `);
    }
    
    // HTML escape function
    function escapeHtml(str) {
        if (typeof str !== 'string') return '';
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }
    
    // Add CSS for fallback grid
    function addFallbackCSS() {
        if ($('#pmv-fallback-css').length) return;
        
        $('head').append(`
            <style id="pmv-fallback-css">
            .css-grid-fallback {
                display: grid !important;
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)) !important;
                gap: 15px !important;
                width: 100% !important;
            }
            
            .css-grid-fallback .grid-sizer {
                display: none !important;
            }
            
            .css-grid-fallback .png-card {
                margin: 0 !important;
                width: auto !important;
                position: relative !important;
                left: auto !important;
                top: auto !important;
            }
            
            .pmv-loading-indicator {
                text-align: center;
                padding: 20px;
                font-style: italic;
                color: #666;
            }
            
            .character-modal-wrapper {
                max-width: 800px;
                margin: 0 auto;
                padding: 20px;
            }
            
            .character-header {
                text-align: center;
                margin-bottom: 20px;
            }
            
            .character-image {
                float: left;
                margin: 0 20px 20px 0;
                max-width: 200px;
            }
            
            .character-image img {
                width: 100%;
                border-radius: 8px;
            }
            
            .character-section {
                margin-bottom: 20px;
                clear: both;
            }
            
            .character-section h3 {
                margin-bottom: 10px;
                color: #333;
            }
            
            .tags-container {
                display: flex;
                flex-wrap: wrap;
                gap: 5px;
            }
            
            .tag-item {
                background: #e1e1e1;
                padding: 2px 8px;
                border-radius: 12px;
                font-size: 12px;
                color: #555;
            }
            
            .footer-buttons {
                text-align: center;
                margin-top: 20px;
                clear: both;
            }
            
            .footer-buttons > * {
                margin: 0 10px;
            }
            
            @media (max-width: 768px) {
                .css-grid-fallback {
                    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)) !important;
                }
                
                .character-image {
                    float: none;
                    margin: 0 auto 20px auto;
                    display: block;
                    text-align: center;
                }
            }
            </style>
        `);
    }
    
    // Initialize everything
    addFallbackCSS();
    initializeGallery();
    
    // Expose functions globally for debugging
    window.pmvGallery = {
        loadPage: loadPage,
        reinitialize: initializeGallery,
        config: config
    };
    
    console.log('PNG Metadata Viewer optimized script loaded');
    console.log('Debug: Run pmvGallery.reinitialize() to reload gallery');
});
