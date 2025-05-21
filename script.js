jQuery(document).ready(function ($) {
    console.log('PNG Metadata Viewer: Enhanced Script loaded');
    // ========= GLOBAL VARIABLES =========
    window.selectedTags = { filter1: [], filter2: [], filter3: [] };
    let masonryInitialized = false;
    let imagesLoaded = 0;
    let totalImages = 0;

    // ========= MASONRY INITIALIZATION =========
    function initMasonry() {
        const $grid = $('.png-cards');
        if (!$grid.length) {
            $('.spinner-overlay').fadeOut();
            console.error('No grid element found for Masonry');
            return;
        }
        
        console.log('Initializing Masonry gallery');
        totalImages = $('.png-image-container img').length;
        imagesLoaded = 0;
        
        $('.png-card').each(function() {
            $(this).css({ 'opacity': '0', 'height': 'auto', 'transition': 'opacity 0.5s ease', 'min-height': '300px' });
        });

        $('.png-image-container img').each(function() {
            const $img = $(this);
            const $card = $img.closest('.png-card');
            $img.closest('.png-image-container').css({
                'height': '180px',
                'display': 'flex',
                'align-items': 'center',
                'justify-content': 'center',
                'overflow': 'hidden'
            });

            if (this.complete) {
                handleImageLoaded($img, $card);
            } else {
                $img.on('load', function() {
                    handleImageLoaded($img, $card);
                }).on('error', function() {
                    console.log('Image failed to load:', $img.attr('src'));
                    $card.find('.png-image-container').css('background-color', '#f8f8f8');
                    handleImageLoaded($img, $card);
                });
            }
        });

        $grid.masonry({
            itemSelector: '.png-card',
            columnWidth: '.grid-sizer',
            percentPosition: true,
            transitionDuration: 0,
            stagger: 0,
            initLayout: false
        });

        if (imagesLoaded === totalImages && totalImages > 0) {
            finalizeMasonryLayout($grid);
        }

        setTimeout(function() {
            if (!masonryInitialized && totalImages > 0) {
                console.log('Safety timeout triggered for masonry initialization');
                finalizeMasonryLayout($grid);
            }
        }, 5000);
    }

    function handleImageLoaded($img, $card) {
        const img = $img[0];
        const container = $img.closest('.png-image-container');
        $img.css({'width': '', 'height': ''});
        
        img.naturalWidth > img.naturalHeight ? 
            $img.css({'width': '100%', 'height': 'auto', 'max-height': '100%'}) :
            $img.css({'width': 'auto', 'height': '100%', 'max-width': '100%'});

        if (++imagesLoaded === totalImages) {
            finalizeMasonryLayout($('.png-cards'));
        }
    }

    function finalizeMasonryLayout($grid) {
        if (masonryInitialized) return;
        masonryInitialized = true;
        console.log('All images loaded, finalizing masonry layout');
        
        $('.spinner-overlay').fadeOut();
        $grid.masonry('layout');

        $('.png-card').each(function(index) {
            const $card = $(this);
            setTimeout(() => $card.css('opacity', 1), index * 30);
        });

        setTimeout(() => {
            $grid.masonry('option', {
                transitionDuration: '0.3s',
                stagger: 30
            }).masonry('layout');
        }, 500);
    }

    // ========= CHARACTER MODAL SYSTEM ========= [1][2][5]
    function openCharacterModal(card) {
        try {
            const $card = $(card);
            const metadataStr = $card.attr('data-metadata');
            const fileUrl = $card.data('file-url');
            
            if (!metadataStr) throw new Error('No metadata found on this card');
            
            const characterData = window.PMV_Chat.parseCharacterData(metadataStr);
            const character = window.PMV_Chat.extractCharacterInfo(characterData);
            const modalHtml = buildCharacterModalHtml(character, fileUrl, metadataStr);
            
            $('#modal-content').html(modalHtml);
            $('#png-modal').fadeIn();
            
            // Initialize conversation manager [5]
            if (window.PMV_ConversationManager) {
                const botId = 'bot_' + (character.name || 'character').replace(/[^a-z0-9]/gi, '_').toLowerCase();
                window.PMV_ConversationManager.init(characterData, botId);
            }
            
            setupTabSwitching();
        } catch (error) {
            console.error('Error opening character modal:', error);
            showErrorModal(error.message, $card.data('file-url'));
        }
    }

    function buildCharacterModalHtml(character, fileUrl, rawMetadata) {
        console.log('Building character modal HTML for:', character.name);
        
        // Existing character field extraction remains the same...
        
        // MODIFIED FOOTER SECTION [1][5]
        return `
            <div class="character-details">
                <!-- Existing header and tabs remain unchanged -->
                
                <div class="character-footer">
                    <a href="${fileUrl}" class="png-download-button" download>Download</a>
                    <div class="conversation-controls">
                        <button id="new-conversation" class="conversation-btn">New Chat</button>
                        <button id="save-conversation" class="conversation-btn">Save Chat</button>
                    </div>
                </div>
            </div>
        `;
    }

    // ========= CONVERSATION MANAGEMENT INTEGRATION [5] =========
    $(document)
        .on('click', '.close-modal, #png-modal', function(e) {
            if (e.target === this || $(e.target).hasClass('close-modal')) {
                $('#png-modal').fadeOut().find('.png-modal-content').removeClass('chat-mode');
                if (window.PMV_Chat) window.PMV_Chat.resetChatState?.();
                if (window.PMV_ConversationManager) window.PMV_ConversationManager.reset?.();
            }
        });

    // Existing tag filtering and other functions remain unchanged...
    
    // ========= INITIALIZATION =========
    $(function() {
        setTimeout(() => {
            initMasonry();
            setTimeout(initTagFilters, 1000);
        }, 100);
    });
});
