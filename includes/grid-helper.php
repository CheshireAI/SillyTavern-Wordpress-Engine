<?php
/**
* SIMPLE GRID HELPER - Clean and Working
*/
if (!defined('ABSPATH')) {
    exit;
}

/**
* Add CSS for grid layout
*/
function pmv_add_simple_grid_css() {
    ?>
    <style>
    /* Filter Styles */
    .png-filters-container {
        padding: 20px;
        background: #2a2a2a;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .pmv-filters-wrapper {
        display: flex;
        gap: 15px;
        align-items: flex-end;
    }
    .pmv-filter-group {
        display: flex;
        flex-direction: column;
    }
    .pmv-filter-group label {
        font-size: 12px;
        color: #aaa;
        margin-bottom: 5px;
    }
    #pmv-search, .pmv-filter-select {
        padding: 8px;
        border-radius: 4px;
        border: 1px solid #404040;
        background: #1a1a1a;
        color: #f0f0f0;
    }
    .pmv-filter-button {
        padding: 8px 15px;
        border-radius: 4px;
        border: 1px solid var(--border-main);
        background: var(--button-bg);
        color: var(--text-main);
        cursor: pointer;
        transition: background 0.2s ease, border-color 0.2s ease;
        font-size: 14px;
        font-weight: 600;
    }
    
    .pmv-filter-button:hover {
        background: var(--button-hover);
        border-color: var(--button-hover);
    }

    /* Grid Layout */
    .png-cards {
        display: grid !important;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)) !important; /* REDUCED from 240px to allow more cards per row */
        gap: 15px !important;
        padding: 20px !important;
        background: #1a1a1a !important;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .png-cards { grid-template-columns: 1fr !important; }
    }
    
    /* Card Styles */
    .png-card {
        display: flex !important;
        opacity: 1 !important;
        transform: none !important;
        flex-direction: column !important;
        background: #2a2a2a !important;
        border: 1px solid #404040 !important;
        border-radius: 8px !important;
        overflow: hidden !important;
        color: #f0f0f0 !important;
        transition: transform 0.2s ease !important;
    }
    
    .png-card:hover {
        transform: translateY(-2px) !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3) !important;
    }
    
    .png-image-container {
        width: 100% !important;
        background: #1a1a1a !important;
        padding: 10px !important;
    }
    
    .png-image-container img {
        width: 100% !important;
        height: auto !important;
        display: block !important;
        background: transparent !important;
        object-fit: contain !important;
        object-position: center !important;
    }
    
    .png-card-info {
        padding: 15px !important;
    }
    
    .png-card-name {
        font-weight: bold !important;
        font-size: 16px !important;
        margin-bottom: 8px !important;
        color: #f0f0f0 !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        white-space: nowrap !important;
        max-width: 100% !important;
        width: 100% !important;
        display: block !important;
        box-sizing: border-box !important;
        word-wrap: normal !important;
        word-break: normal !important;
        hyphens: none !important;
    }
    
    .png-card-description {
        font-size: 14px !important;
        color: #b0b0b0 !important;
        margin-bottom: 10px !important;
        line-height: 1.4 !important;
        overflow-y: auto !important;
        max-height: 200px !important;
        text-overflow: clip !important;
        white-space: normal !important;
        word-wrap: break-word !important;
        padding: 8px !important;
        background: #1a1a1a !important;
        border-radius: 6px !important;
        border: 1px solid #404040 !important;
    }
    
    .png-card-tags {
        font-size: 12px !important;
        color: #888 !important;
        margin-bottom: 15px !important;
        overflow: visible !important;
        text-overflow: clip !important;
        white-space: normal !important;
        word-wrap: break-word !important;
    }
    
    .png-card-buttons {
        display: flex !important;
        flex-direction: row !important;
        justify-content: center !important;
        align-items: center !important;
        gap: 8px !important;
        padding: 10px 15px 15px 15px !important;
    }
    .png-card-buttons a,
    .png-card-buttons button {
        flex: 0 0 auto !important;
        width: auto !important;
        min-width: 110px !important;
    }
    
    .png-download-button,
    .png-chat-button {
        width: 100% !important;
        padding: 10px 12px !important;
        border: none !important;
        border-radius: 4px !important;
        cursor: pointer !important;
        text-decoration: none !important;
        text-align: center !important;
        font-size: 14px !important;
        font-weight: 500 !important;
        transition: background 0.2s ease !important;
        box-sizing: border-box !important;
    }
    
    .png-download-button {
        background: #006064 !important;
        color: white !important;
        border: 1px solid #006064 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        text-align: center !important;
        line-height: 1 !important;
    }
    
    .png-chat-button {
        background: var(--button-bg) !important;
        color: var(--text-main) !important;
        border: 1px solid var(--border-main) !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        text-align: center !important;
        line-height: 1 !important;
    }
    
    .png-download-button:hover {
        background: #004d52 !important;
    }
    
    .png-chat-button:hover {
        background: var(--button-hover) !important;
    }
    
    /* Loading overlays removed - no longer needed */
    
    /* Pagination */
    .pmv-pagination {
        text-align: center !important;
        margin: 20px 0 !important;
        padding: 16px 0 !important;
        background: #232323 !important;
        color: #e0e0e0 !important;
        border-radius: 8px !important;
        border: 1px solid #232629 !important;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1) !important;
    }
    
    .pmv-pagination-info {
        background: #232629 !important;
        color: #e0e0e0 !important;
        padding: 12px 16px !important;
        border-radius: 6px !important;
        margin-bottom: 12px !important;
        font-size: 14px !important;
        font-weight: 500 !important;
        border: 1px solid #232629 !important;
    }
    
    .pmv-page-link {
        display: inline-block !important;
        padding: 8px 16px !important;
        margin: 0 2px !important;
        text-decoration: none !important;
        border-radius: 6px !important;
        background: #23404e !important;
        color: #e0e0e0 !important;
        cursor: pointer !important;
        border: 1px solid #232629 !important;
        transition: all 0.2s ease !important;
        min-width: 40px !important;
        text-align: center !important;
        font-weight: 500 !important;
    }
    
    .pmv-page-link:hover {
        background: #2d5363 !important;
        color: #fff !important;
        border-color: #2d5363 !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 2px 4px rgba(45, 83, 99, 0.2) !important;
    }
    
    .pmv-page-current {
        display: inline-block !important;
        padding: 8px 16px !important;
        margin: 0 2px !important;
        border-radius: 6px !important;
        background: #2d5363 !important;
        color: #fff !important;
        border: 1px solid #2d5363 !important;
        font-weight: 600 !important;
        min-width: 40px !important;
        text-align: center !important;
        transition: all 0.2s ease !important;
        cursor: default !important;
        box-shadow: 0 2px 4px rgba(45, 83, 99, 0.2) !important;
    }
    
    .pmv-page-disabled {
        display: inline-block !important;
        padding: 8px 16px !important;
        margin: 0 2px !important;
        border-radius: 6px !important;
        background: #232323 !important;
        color: #aaa !important;
        border: 1px solid #232629 !important;
        font-weight: 500 !important;
        min-width: 40px !important;
        text-align: center !important;
        cursor: not-allowed !important;
        opacity: 0.6 !important;
        transition: all 0.2s ease !important;
    }

    /* ---- Card Layout Fix for Responsive Sizing ---- */
    .png-card-info {
        display: flex !important;
        flex-direction: column !important;
        flex: 1 1 auto !important;
        overflow: hidden !important; /* Prevent overflow from affecting card size */
        height: 70% !important; /* Use percentage instead of calc with pixels */
        max-height: 70% !important;
        padding: 2% !important;
        box-sizing: border-box !important;
    }
    .png-card-description {
        flex: 1 1 60% !important; /* Take 60% of available info space for description */
        overflow-y: auto !important; /* Allow scrolling for long descriptions */
        overflow-x: hidden !important;
        min-height: 50% !important; /* Minimum 50% of info area for description */
        max-height: 65% !important; /* Maximum 65% to leave space for tags */
    }
    .png-card-tags {
        flex: 0 0 25% !important; /* Fixed 25% of info area for tags */
        min-height: 20% !important; /* Minimum 20% for tags */
        max-height: 30% !important; /* Maximum 30% for tags */
        overflow-y: auto !important; /* Allow scrolling within tags area */
        overflow-x: hidden !important;
        white-space: normal !important; /* Allow text wrapping */
        word-wrap: break-word !important; /* Break long words if needed */
    }
    .png-card-buttons {
        margin-top: auto !important; /* Pin buttons to bottom of card */
        flex-direction: column !important; /* Stack buttons vertically */
        align-items: stretch !important;
        flex: 0 0 15% !important; /* Fixed 15% of info area for buttons */
        min-height: 10% !important; /* Minimum space for buttons */
        max-height: 20% !important; /* Maximum space for buttons */
    }
    .png-card-buttons .png-download-button,
    .png-card-buttons .png-chat-button {
        width: 100% !important; /* Make buttons fit card width */
        flex: 1 !important;
        min-height: 32px !important; /* Ensure readable button height */
    }
    </style>
    <?php
}
add_action('wp_head', 'pmv_add_simple_grid_css', 999);

/**
* Add JavaScript for grid functionality
*/
function pmv_add_simple_grid_js() {
    ?>
    <script>
    jQuery(document).ready(function($) {
        console.log('SIMPLE GRID: Starting...');
        
        var $container = $('.png-cards');
        if ($container.length === 0) {
            console.error('SIMPLE GRID: No container found');
            return;
        }
        
        var $gallery = $('.pmv-gallery-container');
        var folder = $gallery.data('folder') || '';
        var category = $gallery.data('category') || '';
        var cardsPerPage = $gallery.data('cards-per-page') || 12;

        function getCurrentFilters() {
            return {
                search: $('#pmv-search').val() || '',
                filter1: $('#pmv-filter1').val() || '',
                filter2: $('#pmv-filter2').val() || '',
                filter3: $('#pmv-filter3').val() || '',
                filter4: $('#pmv-filter4').val() || '',
                sort_by: $('#pmv-sort').val() || ''
            };
        }
        
        function loadCards(page, filters) {
            console.log('SIMPLE GRID: Loading page', page);
            
            $container.empty().html('<div style="text-align: center; padding: 40px; color: #666;">Loading...</div>');
            
            var ajaxData = {
                action: 'pmv_get_character_cards',
                nonce: pmv_ajax.nonce,
                page: page,
                per_page: cardsPerPage,
                folder: folder,
                category: category
            };

            var currentFilters = filters || getCurrentFilters();
            $.extend(ajaxData, currentFilters);

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: ajaxData,
                success: function(response) {
                    console.log('SIMPLE GRID: AJAX success', response);
                    
                    if (response.success && response.data && response.data.cards) {
                        $container.empty();
                        renderCards(response.data.cards);
                        renderPagination(response.data.pagination);
                    } else {
                        console.error('SIMPLE GRID: No cards in response');
                        $container.html('<div style="text-align: center; padding: 40px; color: #666;">No characters found</div>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('SIMPLE GRID: AJAX error', error);
                    $container.html('<div style="text-align: center; padding: 40px; color: #d63638;">Error loading characters</div>');
                }
            });
        }

        // Expose to window
        window.pmvGrid = {
            loadCards: loadCards,
            getCurrentFilters: getCurrentFilters
        };
        
        // Initial Load
        loadCards(1);

        // Filter Handlers
        $('#pmv-apply-filters').on('click', function() {
            loadCards(1, getCurrentFilters());
        });

        $('#pmv-clear-filters').on('click', function() {
            $('#pmv-search').val('');
            $('.pmv-filter-select').val('');
            $('#pmv-sort').val('');
            loadCards(1, {});
        });
        
        function renderCards(cards) {
            console.log('SIMPLE GRID: Rendering', cards.length, 'cards');
            
            cards.forEach(function(card) {
                var cardHtml = `
                    <div class="png-card" data-metadata='${encodeURIComponent(JSON.stringify(card.metadata)).replace(/'/g, "%27")}' data-file-url="${card.file_url}">
                        <div class="png-image-container">
                            <img src="${card.file_url}" alt="${card.name}">
                        </div>
                        <div class="png-card-info">
                            <div class="png-card-name">${card.name}</div>
                            <div class="png-card-description" style="overflow-y: auto !important; overflow-x: hidden !important; max-height: 200px !important; white-space: normal !important; word-wrap: break-word !important; text-overflow: clip !important; display: block !important; height: 200px !important; min-height: 200px !important;">${card.description || 'No description available'}</div>
                            <div class="png-card-tags">${card.tags && card.tags.length > 0 ? card.tags.join(', ') : 'No tags'}</div>
                        </div>
                        <div class="png-card-buttons">
                            <a href="${card.file_url}" class="png-download-button" download>Download</a>
                            <button class="png-chat-button" data-metadata='${encodeURIComponent(JSON.stringify(card.metadata)).replace(/'/g, "%27")}'>Chat</button>
                        </div>
                        <!-- Voting buttons will be added by voting-system.js -->
                    </div>
                `;
                $container.append(cardHtml);
            });
            
            // Trigger event for voting system to initialize
            $(document).trigger('pmv_cards_loaded');
            
            // CRITICAL FIX: Ensure description scrolling works
            setTimeout(function() {
                $('.png-card-description').each(function() {
                    this.style.setProperty('overflow-y', 'auto', 'important');
                    this.style.setProperty('overflow-x', 'hidden', 'important');
                    this.style.setProperty('max-height', '200px', 'important');
                    this.style.setProperty('white-space', 'normal', 'important');
                    this.style.setProperty('word-wrap', 'break-word', 'important');
                    this.style.setProperty('text-overflow', 'clip', 'important');
                    this.style.setProperty('display', 'block', 'important');
                    this.style.setProperty('height', '200px', 'important');
                    this.style.setProperty('min-height', '200px', 'important');
                });
            }, 100);
        }
        
        function renderPagination(pagination) {
            var $pagination = $('.pmv-pagination');
            
            if (pagination.total_pages <= 1) {
                $pagination.hide();
                return;
            }
            
            $pagination.show();
            
            // Calculate start and end items
            var startItem = (pagination.current_page - 1) * pagination.per_page + 1;
            var endItem = Math.min(pagination.current_page * pagination.per_page, pagination.total_items);
            
            // Create pagination info
            var infoHtml = '<div class="pmv-pagination-info">' +
                'Showing ' + startItem + '-' + endItem + ' of ' + pagination.total_items + ' characters ' +
                '(Page ' + pagination.current_page + ' of ' + pagination.total_pages + ')' +
                '</div>';
            
            var html = '';
            
            // Previous
            if (pagination.has_prev) {
                html += '<a href="#" class="pmv-page-link" data-page="' + (pagination.current_page - 1) + '">&laquo; Previous</a>';
            } else {
                html += '<span class="pmv-page-disabled">&laquo; Previous</span>';
            }
            
            // Page numbers
            for (var i = 1; i <= pagination.total_pages; i++) {
                if (i === pagination.current_page) {
                    html += '<span class="pmv-page-current">' + i + '</span>';
                } else {
                    html += '<a href="#" class="pmv-page-link" data-page="' + i + '">' + i + '</a>';
                }
            }
            
            // Next
            if (pagination.has_next) {
                html += '<a href="#" class="pmv-page-link" data-page="' + (pagination.current_page + 1) + '">Next &raquo;</a>';
            } else {
                html += '<span class="pmv-page-disabled">Next &raquo;</span>';
            }
            
            $pagination.html(infoHtml + html);
            
            // Trigger pagination updated event for the main handler
            $(document).trigger('pmv_pagination_updated', [pagination]);
        }
        
        // Remove duplicate pagination handler - handled by pagination-handler.js
        // Note: Pagination clicks are now handled by the dedicated pagination-handler.js file
        
        // Chat button click
        $(document).on('click', '.png-chat-button', function(e) {
            e.preventDefault();
            var metadata = $(this).data('metadata');
            var fileUrl = $(this).closest('.png-card').data('file-url') || $(this).closest('.png-card').find('img').attr('src') || '';
            console.log('SIMPLE GRID: Chat clicked', metadata, 'fileUrl:', fileUrl);
            
            // Trigger chat event with metadata and fileUrl
            $(document).trigger('pmv_start_chat', [metadata, fileUrl]);
        });
        
        console.log('SIMPLE GRID: Ready');
        
        // CRITICAL FIX: Function to ensure description scrolling works
        function fixDescriptionScrolling() {
            $('.png-card-description').each(function() {
                this.style.setProperty('overflow-y', 'auto', 'important');
                this.style.setProperty('overflow-x', 'hidden', 'important');
                this.style.setProperty('max-height', '200px', 'important');
                this.style.setProperty('white-space', 'normal', 'important');
                this.style.setProperty('word-wrap', 'break-word', 'important');
                this.style.setProperty('text-overflow', 'clip', 'important');
                this.style.setProperty('display', 'block', 'important');
                this.style.setProperty('height', '200px', 'important');
                this.style.setProperty('min-height', '200px', 'important');
            });
        }
        
        // Run fix on page load
        $(document).ready(function() {
            setTimeout(fixDescriptionScrolling, 500);
        });
        
        // Run fix whenever cards are loaded
        $(document).on('pmv_cards_loaded', function() {
            setTimeout(fixDescriptionScrolling, 100);
        });
        
        // Run fix on pagination
        $(document).on('pmv_pagination_updated', function() {
            setTimeout(fixDescriptionScrolling, 100);
        });
    });
    </script>
    <?php
}
add_action('wp_footer', 'pmv_add_simple_grid_js', 999);

/**
* Add AJAX nonce
*/
function pmv_add_ajax_nonce() {
    ?>
    <script>
    var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    var pmv_ajax = {
        nonce: '<?php echo wp_create_nonce('pmv_ajax_nonce'); ?>'
    };
    </script>
    <?php
}
add_action('wp_head', 'pmv_add_ajax_nonce', 1);
?> 