<?php
/**
* AJAX-POWERED MASONRY HELPER - Fixed centering and chat button
*/
if (!defined('ABSPATH')) {
    exit;
}

/**
* Enqueue scripts
*/
function pmv_enqueue_masonry_scripts() {
    if (is_single() || is_page() || is_home() || is_archive()) {
        wp_enqueue_script('jquery');
        wp_enqueue_script('masonry');
        wp_enqueue_script('jquery-masonry', array('masonry', 'jquery'), false, true);
        
        wp_enqueue_script(
            'imagesloaded-js',
            'https://cdnjs.cloudflare.com/ajax/libs/jquery.imagesloaded/4.1.4/imagesloaded.pkgd.min.js',
            array('jquery'),
            '4.1.4',
            true
        );
        
        // Add AJAX nonce
        wp_localize_script('jquery', 'pmv_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('pmv_ajax_nonce')
        ));
    }
}
add_action('wp_enqueue_scripts', 'pmv_enqueue_masonry_scripts', 5);

/**
* FIXED CSS for proper centering
*/
function pmv_add_working_css() {
    if (is_single() || is_page() || is_home() || is_archive()) {
        ?>
        <style id="pmv-ajax-css">
        /* LOADING OVERLAY */
        .pmv-loading-overlay {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d2d2d 100%) !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            z-index: 99999 !important;
            opacity: 1 !important;
            visibility: visible !important;
            transition: opacity 0.5s ease !important;
        }
        
        .pmv-loading-overlay.fadeout {
            opacity: 0 !important;
            visibility: hidden !important;
            pointer-events: none !important;
        }
        
        /* LOADING ANIMATION */
        .pmv-loading-content {
            text-align: center;
            color: #f0f0f0;
        }
        
        .pmv-spinner {
            position: relative;
            width: 80px;
            height: 80px;
            margin: 0 auto 30px;
        }
        
        .pmv-spinner-ring {
            position: absolute;
            width: 100%;
            height: 100%;
            border: 3px solid transparent;
            border-top: 3px solid #4CAF50;
            border-radius: 50%;
            animation: pmv-spin 1.5s linear infinite;
        }
        
        .pmv-spinner-ring:nth-child(2) {
            width: 90%;
            height: 90%;
            top: 5%;
            left: 5%;
            border-top-color: #2196F3;
            animation-duration: 2s;
            animation-direction: reverse;
        }
        
        .pmv-spinner-ring:nth-child(3) {
            width: 80%;
            height: 80%;
            top: 10%;
            left: 10%;
            border-top-color: #FF9800;
            animation-duration: 1s;
        }
        
        @keyframes pmv-spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .pmv-loading-text {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #f0f0f0;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        }
        
        .pmv-loading-subtext {
            font-size: 16px;
            color: #b0b0b0;
            opacity: 0.8;
            animation: pmv-pulse 2s ease-in-out infinite;
        }
        
        @keyframes pmv-pulse {
            0%, 100% { opacity: 0.6; }
            50% { opacity: 1; }
        }
        
        /* CRITICAL: MASONRY WRAPPER FOR CENTERING */
        .pmv-masonry-wrapper {
            width: 100% !important;
            text-align: center !important; /* THIS CENTERS THE INLINE-BLOCK CONTAINER */
            padding: 20px 0 !important;
            min-height: 200px !important;
            position: relative !important;
            clear: both !important;
        }
        
        /* MASONRY CONTAINER - MUST BE INLINE-BLOCK FOR CENTERING */
        .png-cards {
            /* INITIALLY HIDDEN */
            opacity: 0 !important;
            visibility: hidden !important;
            transition: opacity 0.8s ease !important;
            /* CRITICAL FOR CENTERING WITH FITWIDTH */
            display: inline-block !important; /* INLINE-BLOCK + parent text-align:center = centered */
            position: relative !important;
            margin: 0 auto !important;
            text-align: left !important; /* Reset text align for content */
        }
        
        /* Show container when loaded */
        .png-cards-loading.loaded .png-cards {
            opacity: 1 !important;
            visibility: visible !important;
        }
        
        /* CARDS - MASONRY ITEMS */
        .png-card {
            /* INITIALLY HIDDEN */
            opacity: 0 !important;
            visibility: hidden !important;
            transform: translateY(30px) scale(0.9) !important;
            transition: all 0.6s ease !important;
            /* MASONRY ITEM STYLES */
            width: 300px !important;
            margin-bottom: 20px !important;
            float: left !important; /* Required for masonry */
            display: block !important;
            box-sizing: border-box !important;
        }
        
        /* Show cards when loaded */
        .png-cards-loading.loaded .png-card {
            opacity: 1 !important;
            visibility: visible !important;
            transform: translateY(0) scale(1) !important;
        }
        
        /* Staggered animation delays */
        .png-cards-loading.loaded .png-card:nth-child(1) { transition-delay: 0.1s !important; }
        .png-cards-loading.loaded .png-card:nth-child(2) { transition-delay: 0.15s !important; }
        .png-cards-loading.loaded .png-card:nth-child(3) { transition-delay: 0.2s !important; }
        .png-cards-loading.loaded .png-card:nth-child(4) { transition-delay: 0.25s !important; }
        .png-cards-loading.loaded .png-card:nth-child(5) { transition-delay: 0.3s !important; }
        .png-cards-loading.loaded .png-card:nth-child(6) { transition-delay: 0.35s !important; }
        .png-cards-loading.loaded .png-card:nth-child(7) { transition-delay: 0.4s !important; }
        .png-cards-loading.loaded .png-card:nth-child(8) { transition-delay: 0.45s !important; }
        .png-cards-loading.loaded .png-card:nth-child(n+9) { transition-delay: 0.5s !important; }
        
        /* RESPONSIVE WIDTHS */
        @media (max-width: 1400px) {
            .png-card { width: 280px !important; }
        }
        
        @media (max-width: 1200px) {
            .png-card { width: 250px !important; }
        }
        
        @media (max-width: 1024px) {
            .png-card { width: 220px !important; }
        }
        
        @media (max-width: 768px) {
            .pmv-masonry-wrapper {
                padding: 10px !important;
            }
            
            .png-cards {
                display: block !important;
                width: 100% !important;
            }
            
            .png-card {
                width: calc(100% - 30px) !important;
                max-width: 400px !important;
                margin: 0 auto 20px auto !important;
                float: none !important;
                display: block !important;
                position: relative !important;
                left: auto !important;
                top: auto !important;
            }
        }
        
        /* Grid sizer for masonry */
        .grid-sizer {
            width: 300px;
            height: 0;
            visibility: hidden;
        }
        
        @media (max-width: 1400px) { .grid-sizer { width: 280px; } }
        @media (max-width: 1200px) { .grid-sizer { width: 250px; } }
        @media (max-width: 1024px) { .grid-sizer { width: 220px; } }
        @media (max-width: 768px) { .grid-sizer { width: 100%; } }
        
        /* AJAX LOADING INDICATOR */
        .pmv-ajax-loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        
        .pmv-ajax-loading .pmv-spinner {
            width: 40px;
            height: 40px;
            margin: 0 auto 20px;
        }
        
        /* ERROR STATE */
        .pmv-error {
            text-align: center;
            padding: 20px;
            color: #d63638;
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
            margin: 20px 0;
        }
        
        /* PAGINATION */
        .pmv-pagination {
            text-align: center !important;
            margin: 20px 0 !important;
            padding: 10px !important;
            clear: both !important;
        }
        
        .pmv-pagination.loading {
            opacity: 0.5;
            pointer-events: none;
        }
        
        .pmv-page-link {
            display: inline-block !important;
            padding: 8px 12px !important;
            margin: 0 4px !important;
            text-decoration: none !important;
            border: 1px solid #ddd !important;
            color: #333 !important;
            background: #fff !important;
            border-radius: 3px !important;
            cursor: pointer !important;
        }
        
        .pmv-page-link:hover {
            background: #f5f5f5 !important;
            text-decoration: none !important;
        }
        
        .pmv-page-current {
            background: #0073aa !important;
            color: #fff !important;
            border-color: #0073aa !important;
        }
        
        .pmv-ellipsis {
            padding: 8px 4px !important;
            color: #666 !important;
        }
        </style>
        <?php
    }
}
add_action('wp_head', 'pmv_add_working_css', 999);

/**
* AJAX MASONRY SYSTEM - Fixed centering and chat button
*/
function pmv_add_working_masonry() {
    if (is_single() || is_page() || is_home() || is_archive()) {
        ?>
        <script>
        jQuery(document).ready(function($) {
            console.log('AJAX MASONRY: Starting system...');
            
            // Global state
            let masonryInstance = null;
            let isLoaded = false;
            let currentPage = 1;
            let totalPages = 1;
            let isLoading = false;
            let $container = $('.png-cards');
            let $loadingContainer = $('.png-cards-loading');
            let $loadingOverlay = $('#pmv-loading-overlay');
            
            // Get initial parameters
            const urlParams = new URLSearchParams(window.location.search);
            const initialFilters = {
                search: urlParams.get('pmv_search') || '',
                filter1: urlParams.get('pmv_filter1') || '',
                filter2: urlParams.get('pmv_filter2') || '',
                filter3: urlParams.get('pmv_filter3') || '',
                filter4: urlParams.get('pmv_filter4') || '',
                page: parseInt(urlParams.get('pmv_page')) || 1
            };
            
            function getResponsiveWidth() {
                const width = $(window).width();
                if (width <= 768) return '100%';
                if (width <= 1024) return 220;
                if (width <= 1200) return 250;
                if (width <= 1400) return 280;
                return 300;
            }
            
            function showLoading() {
                $('#pmv-loading-overlay').removeClass('fadeout');
                $('.pmv-ajax-loading').show();
            }
            
            function hideLoading() {
                $('#pmv-loading-overlay').addClass('fadeout');
                $('.pmv-ajax-loading').hide();
            }
            
            function showError(message) {
                const $container = $('.png-cards');
                $container.html('<div class="pmv-error">Error: ' + message + '</div>');
                hideLoading();
            }
            
            function loadCards(filters = {}, page = 1) {
                if (isLoading) return;
                
                console.log('AJAX MASONRY: Loading cards...', filters, page);
                isLoading = true;
                showLoading();
                
                // Destroy existing masonry
                if (masonryInstance) {
                    $container.masonry('destroy');
                    masonryInstance = null;
                }
                
                // Clear existing cards
                $container.empty().append('<div class="grid-sizer"></div>');
                $loadingContainer.removeClass('loaded');
                
                // Get gallery data
                const $galleryContainer = $('.pmv-gallery-container');
                const folder = $galleryContainer.data('folder') || '';
                const category = $galleryContainer.data('category') || '';
                const cardsPerPage = $galleryContainer.data('cards-per-page') || 12;
                
                $.ajax({
                    url: pmv_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'pmv_get_character_cards',
                        nonce: pmv_ajax.nonce,
                        page: page,
                        per_page: cardsPerPage,
                        search: filters.search || '',
                        filter1: filters.filter1 || '',
                        filter2: filters.filter2 || '',
                        filter3: filters.filter3 || '',
                        filter4: filters.filter4 || '',
                        folder: folder,
                        category: category
                    },
                    success: function(response) {
                        console.log('AJAX MASONRY: Cards loaded successfully');
                        
                        if (response.success && response.data.cards) {
                            renderCards(response.data.cards);
                            updatePagination(response.data.pagination);
                            currentPage = page;
                            totalPages = response.data.pagination.total_pages;
                            
                            // Dispatch event for pagination info update
                            $(document).trigger('pmv_cards_loaded', [response.data]);
                            
                            // Initialize masonry after cards are rendered
                            setTimeout(initMasonry, 100);
                        } else {
                            const errorMsg = response.data ? response.data.message : 'Failed to load cards';
                            showError(errorMsg);
                            $(document).trigger('pmv_load_error', [errorMsg]);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('AJAX MASONRY: Load error', error);
                        const errorMsg = 'Network error: ' + error;
                        showError(errorMsg);
                        $(document).trigger('pmv_load_error', [errorMsg]);
                    },
                    complete: function() {
                        isLoading = false;
                    }
                });
            }
            
            // FIXED: Properly escape JSON for HTML attributes
            function renderCards(cards) {
                const $container = $('.png-cards');
                const chatButtonText = 'Chat'; // You can get this from options if needed
                
                cards.forEach(function(card) {
                    // FIXED: Properly escape JSON for HTML attribute
                    const metadataJson = JSON.stringify(card.metadata);
                    // Only escape quotes for HTML attribute - don't use .text().html()
                    const metadataString = metadataJson.replace(/"/g, '&quot;');
                    
                    const tagsString = Array.isArray(card.tags) ? card.tags.join(', ') : '';
                    
                    // Build card HTML
                    let cardHtml = `
                        <div class="png-card" data-tags="${tagsString}" data-file-url="${card.file_url}">
                            <div class="png-image-container">
                                <img src="${card.file_url}" alt="${card.name}" loading="eager">
                            </div>
                            <div class="png-card-info">
                                <div class="png-card-name">${card.name}</div>
                                ${card.description ? `<div class="png-card-description">${card.description}</div>` : ''}
                                ${tagsString ? `<div class="png-card-tags">${tagsString}</div>` : ''}
                            </div>
                            <div class="png-card-buttons">
                                <a href="${card.file_url}" class="png-download-button" download>Download</a>
                                <button class="png-chat-button" data-metadata="${metadataString}">${chatButtonText}</button>
                            </div>
                        </div>
                    `;
                    $container.append(cardHtml);
                });
                
                console.log('AJAX MASONRY: Cards rendered, total:', cards.length);
            }
            
            function initMasonry() {
                const $container = $('.png-cards');
                
                if ($container.length === 0 || $('.png-card').length === 0) {
                    console.log('AJAX MASONRY: No container or cards for masonry');
                    completeLoading();
                    return;
                }
                
                console.log('AJAX MASONRY: Initializing masonry with', $('.png-card').length, 'cards');
                
                // Check if mobile
                if ($(window).width() <= 768) {
                    console.log('AJAX MASONRY: Mobile detected, using simple layout');
                    $('.png-card').css({
                        'width': 'calc(100% - 30px)',
                        'max-width': '400px',
                        'margin': '0 auto 20px auto',
                        'float': 'none',
                        'position': 'relative',
                        'display': 'block'
                    });
                    completeLoading();
                    return;
                }
                
                // Wait for images to load
                $container.imagesLoaded(function() {
                    console.log('AJAX MASONRY: All images loaded, creating masonry...');
                    
                    try {
                        // Get responsive settings
                        const columnWidth = getResponsiveWidth();
                        const gutter = 15;
                        
                        // Initialize masonry with fitWidth for centering
                        $container.masonry({
                            itemSelector: '.png-card',
                            columnWidth: columnWidth,
                            gutter: gutter,
                            fitWidth: true, // CRITICAL FOR CENTERING
                            percentPosition: false,
                            horizontalOrder: true,
                            transitionDuration: '0.3s',
                            isResizeBound: true
                        });
                        
                        masonryInstance = $container.data('masonry');
                        console.log('AJAX MASONRY: ✅ Masonry created and centered');
                        
                        // Force layout after a brief delay
                        setTimeout(function() {
                            if (masonryInstance) {
                                $container.masonry('layout');
                                console.log('AJAX MASONRY: Layout complete');
                            }
                            completeLoading();
                        }, 100);
                        
                    } catch (error) {
                        console.error('AJAX MASONRY: Masonry initialization failed:', error);
                        completeLoading();
                    }
                });
            }
            
            function completeLoading() {
                console.log('AJAX MASONRY: Completing loading...');
                isLoaded = true;
                
                // Show everything
                $loadingContainer.addClass('loaded');
                hideLoading();
                
                console.log('AJAX MASONRY: ✅ Loading complete');
            }
            
            function updatePagination(pagination) {
                const $pagination = $('.pmv-pagination');
                
                if (pagination.total_pages <= 1) {
                    $pagination.hide();
                    return;
                }
                
                $pagination.show();
                let paginationHtml = '';
                
                // Previous button
                if (pagination.has_prev) {
                    paginationHtml += `<a href="#" class="pmv-page-link pmv-prev" data-page="${pagination.current_page - 1}">‹ Previous</a>`;
                }
                
                // Page numbers
                const startPage = Math.max(1, pagination.current_page - 2);
                const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);
                
                if (startPage > 1) {
                    paginationHtml += `<a href="#" class="pmv-page-link" data-page="1">1</a>`;
                    if (startPage > 2) {
                        paginationHtml += `<span class="pmv-ellipsis">…</span>`;
                    }
                }
                
                for (let i = startPage; i <= endPage; i++) {
                    if (i === pagination.current_page) {
                        paginationHtml += `<span class="pmv-page-link pmv-page-current">${i}</span>`;
                    } else {
                        paginationHtml += `<a href="#" class="pmv-page-link" data-page="${i}">${i}</a>`;
                    }
                }
                
                if (endPage < pagination.total_pages) {
                    if (endPage < pagination.total_pages - 1) {
                        paginationHtml += `<span class="pmv-ellipsis">…</span>`;
                    }
                    paginationHtml += `<a href="#" class="pmv-page-link" data-page="${pagination.total_pages}">${pagination.total_pages}</a>`;
                }
                
                // Next button
                if (pagination.has_next) {
                    paginationHtml += `<a href="#" class="pmv-page-link pmv-next" data-page="${pagination.current_page + 1}">Next ›</a>`;
                }
                
                $pagination.html(paginationHtml);
                
                // Update URL without reload
                const url = new URL(window.location);
                if (pagination.current_page > 1) {
                    url.searchParams.set('pmv_page', pagination.current_page);
                } else {
                    url.searchParams.delete('pmv_page');
                }
                window.history.replaceState(null, '', url);
            }
            
            // CHAT BUTTON HANDLER - FIXED
            $(document).on('click', '.png-chat-button', function(e) {
                e.preventDefault();
                console.log('Chat button clicked');
                
                const $button = $(this);
                const metadataString = $button.attr('data-metadata');
                
                if (!metadataString) {
                    console.error('No metadata found on button');
                    alert('Error: Character data not available');
                    return;
                }
                
                try {
                    // Parse the metadata directly (it's already been escaped properly)
                    const metadata = JSON.parse(metadataString);
                    console.log('Parsed metadata:', metadata);
                    
                    // Trigger custom event for chat initialization
                    $(document).trigger('pmv_start_chat', [metadata]);

                    } catch (error) {
                    console.error('Failed to parse metadata:', error, metadataString);
                    alert('Error: Invalid character data');
                }
            });
            
            // Handle pagination clicks
            $(document).on('click', '.pmv-page-link', function(e) {
                e.preventDefault();
                const page = $(this).data('page');
                if (page && page !== currentPage) {
                    const filters = getCurrentFilters();
                    loadCards(filters, page);
                }
            });
            
            // Get current filters
            function getCurrentFilters() {
                return {
                    search: $('#pmv-search').val() || '',
                    filter1: $('#pmv-filter1').val() || '',
                    filter2: $('#pmv-filter2').val() || '',
                    filter3: $('#pmv-filter3').val() || '',
                    filter4: $('#pmv-filter4').val() || ''
                };
            }
            
            // Apply filters
            $(document).on('click', '#pmv-apply-filters', function(e) {
                e.preventDefault();
                const filters = getCurrentFilters();
                loadCards(filters, 1);
            });
            
            // Clear filters
            $(document).on('click', '#pmv-clear-filters', function(e) {
                e.preventDefault();
                $('#pmv-search').val('');
                $('.pmv-filter-select').val('');
                loadCards({}, 1);
            });
            
            // Enter key in search
            $(document).on('keypress', '#pmv-search', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    const filters = getCurrentFilters();
                    loadCards(filters, 1);
                }
            });
            
            // Handle resize
            let resizeTimer;
            $(window).on('resize', function() {
                clearTimeout(resizeTimer);
                resizeTimer = setTimeout(function() {
                    if (isLoaded && masonryInstance) {
                        console.log('AJAX MASONRY: Handling resize...');
                        const windowWidth = $(window).width();
                        
                        if (windowWidth <= 768) {
                            // Mobile: destroy masonry
                            if (masonryInstance) {
                                $container.masonry('destroy');
                                masonryInstance = null;
                            }
                            $('.png-card').css({
                                'width': 'calc(100% - 30px)',
                                'max-width': '400px',
                                'margin': '0 auto 20px auto',
                                'float': 'none',
                                'position': 'relative'
                            });
                        } else {
                            // Desktop: reinitialize
                            initMasonry();
                        }
                    }
                }, 250);
            });
            
            // Global API
            window.pmvMasonry = {
                loadCards: loadCards,
                getCurrentFilters: getCurrentFilters,
                layout: function() {
                    if (masonryInstance && isLoaded) {
                        $container.masonry('layout');
                    }
                },
                reinitialize: function() {
                    isLoaded = false;
                    $loadingContainer.removeClass('loaded');
                    $loadingOverlay.removeClass('fadeout');
                    setTimeout(function() {
                        loadCards(getCurrentFilters(), currentPage);
                    }, 100);
                }
            };
            
            // Initial load
            console.log('AJAX MASONRY: Starting initial load...');
            setTimeout(function() {
                loadCards(initialFilters, initialFilters.page);
            }, 200);
            
            console.log('AJAX MASONRY: System ready');
        });
        </script>
        <?php
    }
}
add_action('wp_footer', 'pmv_add_working_masonry', 999);
?>
