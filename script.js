jQuery(document).ready(function ($) {
    console.log('PNG Metadata Viewer: Enhanced Script with Message Editing, Auto-save, and Centered Responsive Masonry loaded');
    
    // ========= GLOBAL VARIABLES =========
    window.selectedTags = { filter1: [], filter2: [], filter3: [] };
    let masonryInitialized = false;
    let imagesLoaded = 0;
    let totalImages = 0;
    
    // ========= IMPROVED RESPONSIVE MASONRY SYSTEM =========
    // Updated responsive breakpoint detection with more granular control
    function getCurrentBreakpoint() {
        const width = window.innerWidth;
        if (width <= 399) return 'xs';    // Very small mobile
        if (width <= 499) return 'sm';    // Small mobile  
        if (width <= 699) return 'md';    // Large mobile
        if (width <= 899) return 'lg';    // Small tablet
        if (width <= 1199) return 'xl';   // Medium screens
        if (width <= 1599) return 'xxl';  // Wide screens
        if (width <= 1999) return 'xxxl'; // Very wide screens
        return 'ultra';                   // Ultra-wide screens
    }
    
    // Track current breakpoint to detect changes
    let currentBreakpoint = getCurrentBreakpoint();
    
    // ========= ENHANCED CENTERED RESPONSIVE MASONRY INITIALIZATION =========
    function initMasonry() {
        const $grid = $('.png-cards');
        if (!$grid.length) {
            $('.spinner-overlay').fadeOut();
            console.error('No grid element found for Masonry');
            return;
        }

        console.log('Initializing Centered Responsive Masonry gallery');
        console.log('Current breakpoint:', getCurrentBreakpoint());
        
        totalImages = $('.png-image-container img').length;
        imagesLoaded = 0;

        // Remove any existing negative margins that break centering
        $grid.css({
            'margin-left': '0',
            'margin-right': '0',
            'width': '100%',
            'max-width': '100%'
        });

        // Set initial opacity and prepare cards with responsive heights
        $('.png-card').each(function() {
            const breakpoint = getCurrentBreakpoint();
            let minHeight = '320px';
            
            // More granular height control
            switch(breakpoint) {
                case 'xs': minHeight = '200px'; break;
                case 'sm': minHeight = '220px'; break;
                case 'md': minHeight = '260px'; break;
                case 'lg': minHeight = '280px'; break;
                case 'xl': minHeight = '300px'; break;
                case 'xxl': minHeight = '320px'; break;
                case 'xxxl': minHeight = '340px'; break;
                default: minHeight = '350px';
            }
            
            $(this).css({
                'opacity': '0',
                'height': 'auto',
                'transition': 'opacity 0.5s ease',
                'min-height': minHeight,
                'box-sizing': 'border-box'
            });
        });

        // Handle responsive image container heights
        $('.png-image-container img').each(function() {
            const $img = $(this);
            const $card = $img.closest('.png-card');
            const breakpoint = getCurrentBreakpoint();

            // Set responsive image container height with more granular control
            let containerHeight = '180px';
            switch(breakpoint) {
                case 'xs': containerHeight = '130px'; break;
                case 'sm': containerHeight = '140px'; break;
                case 'md': containerHeight = '150px'; break;
                case 'lg': containerHeight = '160px'; break;
                case 'xl': containerHeight = '170px'; break;
                case 'xxl': containerHeight = '180px'; break;
                case 'xxxl': containerHeight = '190px'; break;
                default: containerHeight = '200px';
            }

            $img.closest('.png-image-container').css({
                'height': containerHeight,
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

        // Destroy existing masonry instance if it exists
        if ($grid.data('masonry')) {
            console.log('Destroying existing masonry instance');
            $grid.masonry('destroy');
        }

        // Initialize masonry with improved centering settings
        const breakpoint = getCurrentBreakpoint();
        $grid.masonry({
            itemSelector: '.png-card',
            columnWidth: '.grid-sizer',
            percentPosition: true,
            transitionDuration: 0,
            stagger: 0,
            initLayout: false,
            // Consistent gutter - use 0 and rely on padding
            gutter: 0,
            // Ensure proper centering
            horizontalOrder: false,
            fitWidth: false // Let it use full container width
        });

        if (imagesLoaded === totalImages && totalImages > 0) {
            finalizeMasonryLayout($grid);
        }

        // Safety timeout
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

        // Responsive image sizing
        if (img.naturalWidth > img.naturalHeight) {
            $img.css({'width': '100%', 'height': 'auto', 'max-height': '100%'});
        } else {
            $img.css({'width': 'auto', 'height': '100%', 'max-width': '100%'});
        }

        if (++imagesLoaded === totalImages) {
            finalizeMasonryLayout($('.png-cards'));
        }
    }

    function finalizeMasonryLayout($grid) {
        if (masonryInitialized) return;

        masonryInitialized = true;
        console.log('All images loaded, finalizing centered responsive masonry layout');
        console.log('Final breakpoint:', getCurrentBreakpoint());

        $('.spinner-overlay').fadeOut();

        // Ensure grid is properly centered before layout
        $grid.css({
            'margin': '0 auto',
            'width': '100%'
        });

        $grid.masonry('layout');

        // Staggered reveal with responsive timing
        const breakpoint = getCurrentBreakpoint();
        let staggerDelay = 30;
        
        switch(breakpoint) {
            case 'xs': 
            case 'sm': staggerDelay = 15; break;
            case 'md': staggerDelay = 20; break;
            case 'lg': staggerDelay = 25; break;
            default: staggerDelay = 30;
        }
        
        $('.png-card').each(function(index) {
            const $card = $(this);
            setTimeout(() => $card.css('opacity', 1), index * staggerDelay);
        });

        // Enable transitions after initial layout
        setTimeout(() => {
            $grid.masonry('option', {
                transitionDuration: '0.3s',
                stagger: staggerDelay
            }).masonry('layout');
        }, 500);
    }

    // ========= ENHANCED RESPONSIVE RESIZE HANDLING =========
    let resizeTimeout;
    function handleResponsiveResize() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            const newBreakpoint = getCurrentBreakpoint();
            
            // Only reinitialize if breakpoint actually changed
            if (newBreakpoint !== currentBreakpoint) {
                console.log(`Breakpoint changed from ${currentBreakpoint} to ${newBreakpoint}`);
                currentBreakpoint = newBreakpoint;
                
                // Update image container heights for new breakpoint
                $('.png-image-container').each(function() {
                    let containerHeight = '180px';
                    switch(newBreakpoint) {
                        case 'xs': containerHeight = '130px'; break;
                        case 'sm': containerHeight = '140px'; break;
                        case 'md': containerHeight = '150px'; break;
                        case 'lg': containerHeight = '160px'; break;
                        case 'xl': containerHeight = '170px'; break;
                        case 'xxl': containerHeight = '180px'; break;
                        case 'xxxl': containerHeight = '190px'; break;
                        default: containerHeight = '200px';
                    }
                    $(this).css('height', containerHeight);
                });

                // Update card min-heights
                $('.png-card').each(function() {
                    let minHeight = '320px';
                    switch(newBreakpoint) {
                        case 'xs': minHeight = '200px'; break;
                        case 'sm': minHeight = '220px'; break;
                        case 'md': minHeight = '260px'; break;
                        case 'lg': minHeight = '280px'; break;
                        case 'xl': minHeight = '300px'; break;
                        case 'xxl': minHeight = '320px'; break;
                        case 'xxxl': minHeight = '340px'; break;
                        default: minHeight = '350px';
                    }
                    $(this).css('min-height', minHeight);
                });

                // Reinitialize masonry
                masonryInitialized = false;
                initMasonry();
            } else {
                // Just re-layout if same breakpoint
                const $grid = $('.png-cards');
                if ($grid.data('masonry')) {
                    // Ensure centering is maintained
                    $grid.css({
                        'margin': '0 auto',
                        'width': '100%'
                    });
                    $grid.masonry('layout');
                }
            }
        }, 250); // Debounce resize events
    }

    // Enhanced window resize handler
    $(window).on('resize orientationchange', handleResponsiveResize);

    // ========= CHARACTER MODAL SYSTEM WITH MESSAGE EDITING SUPPORT =========
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

            // Initialize conversation manager with message editing support
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
        console.log('Building character modal HTML with message editing support for:', character.name);

        const name = character.name || 'Unnamed Character';
        const description = character.description || '';
        const personality = character.personality || '';
        const scenario = character.scenario || '';
        const firstMes = character.first_mes || '';
        const mesExample = character.mes_example || '';
        const creator = character.creator || '';
        const version = character.character_version || '';
        const systemPrompt = character.system_prompt || '';
        const postHistory = character.post_history_instructions || '';
        const creatorNotes = character.creator_notes || '';
        const tags = character.tags || [];
        const alternateGreetings = character.alternate_greetings || [];
        const characterBook = character.character_book || null;

        // Safely encode metadata for chat button
        let escapedMetadata;
        try {
            escapedMetadata = encodeURIComponent(typeof rawMetadata === 'string' ? rawMetadata : JSON.stringify(rawMetadata));
        } catch (e) {
            console.error('Error encoding metadata:', e);
            escapedMetadata = '';
        }

        // Build sections
        let sections = [];

        if (description) {
            sections.push(`<div class="character-section"><h3>Description</h3><div class="character-field">${escapeHtml(description)}</div></div>`);
        }

        if (personality) {
            sections.push(`<div class="character-section"><h3>Personality</h3><div class="character-field">${escapeHtml(personality)}</div></div>`);
        }

        if (scenario) {
            sections.push(`<div class="character-section"><h3>Scenario</h3><div class="character-field">${escapeHtml(scenario)}</div></div>`);
        }

        if (firstMes) {
            sections.push(`<div class="character-section"><h3>First Message</h3><div class="character-field">${escapeHtml(firstMes)}</div></div>`);
        }

        if (alternateGreetings.length > 0) {
            let greetings = alternateGreetings.map((greeting, index) =>
                `<div class="greeting-item"><strong>Greeting ${index + 1}:</strong><div>${escapeHtml(greeting)}</div></div>`
            ).join('');
            sections.push(`<div class="character-section"><h3>Alternate Greetings</h3><div class="character-field">${greetings}</div></div>`);
        }

        if (mesExample) {
            sections.push(`<div class="character-section"><h3>Example Messages</h3><div class="character-field example-messages">${escapeHtml(mesExample)}</div></div>`);
        }

        if (systemPrompt) {
            sections.push(`<div class="character-section"><h3>System Prompt</h3><div class="character-field system-prompt">${escapeHtml(systemPrompt)}</div></div>`);
        }

        if (postHistory) {
            sections.push(`<div class="character-section"><h3>Post-History Instructions</h3><div class="character-field">${escapeHtml(postHistory)}</div></div>`);
        }

        if (characterBook && characterBook.entries && characterBook.entries.length > 0) {
            let entries = characterBook.entries.map((entry, index) => {
                const keys = entry.keys || [];
                const content = entry.content || '';
                const constant = entry.constant ? ' (Always Active)' : '';
                return `<div class="lorebook-entry"><div class="entry-header"><strong>Entry ${index + 1}:</strong> ${keys.map(key => ` <span class="key-tag">${escapeHtml(key)}</span>`).join('')}${constant}</div><div class="entry-content">${escapeHtml(content)}</div></div>`;
            }).join('');
            sections.push(`<div class="character-section"><h3>Character Book (${characterBook.entries.length} entries)</h3><div class="character-field">${entries}</div></div>`);
        }

        if (tags.length > 0) {
            let tagHtml = tags.map(tag => `<span class="tag-item">${escapeHtml(tag)}</span>`).join('');
            sections.push(`<div class="character-section"><h3>Tags</h3><div class="character-field"><div class="tags-container">${tagHtml}</div></div></div>`);
        }

        if (creatorNotes) {
            sections.push(`<div class="character-section"><h3>Creator Notes</h3><div class="character-field">${escapeHtml(creatorNotes)}</div></div>`);
        }

        // Tech info section
        sections.push(`<div class="character-section"><h3>Technical Information</h3><div class="character-field"><div class="tech-info"><div class="tech-row"><strong>Character Version:</strong> ${version || 'Not specified'}</div><div class="tech-row"><strong>Creator:</strong> ${creator || 'Unknown'}</div><div class="tech-row"><strong>Has Character Book:</strong> ${characterBook && characterBook.entries && characterBook.entries.length > 0 ? 'Yes (' + characterBook.entries.length + ' entries)' : 'No'}</div><div class="tech-row"><strong>Alternate Greetings:</strong> ${alternateGreetings.length}</div><div class="tech-row"><strong>Tags:</strong> ${tags.length}</div></div></div></div>`);

        // Build modal HTML with enhanced footer for message editing features and auto-save
        const html = `
            <div class="character-modal-wrapper">
                <div class="character-details">
                    <div class="character-header">
                        <h2>${escapeHtml(name)}</h2>
                        ${creator ? `<div class="character-creator">Created by: ${escapeHtml(creator)}</div>` : ''}
                        ${version ? `<div class="character-version">Version: ${escapeHtml(version)}</div>` : ''}
                    </div>
                    <div class="character-image">
                        <img src="${fileUrl}" alt="${escapeHtml(name)}">
                    </div>
                    <div id="character-info">
                        ${sections.join('')}
                    </div>
                    <div class="character-footer">
                        <div class="footer-left">
                            <a href="${fileUrl}" class="png-download-button" download>Download</a>
                        </div>
                        <div class="footer-center">
                            <button class="png-chat-button" data-metadata="${escapedMetadata}">
                                ${window.pmv_chat_button_text || 'Chat'}
                            </button>
                        </div>
                        <div class="footer-right">
                            <div class="conversation-controls">
                                <button id="new-conversation" class="conversation-btn" title="Start a new conversation">
                                    🔄 New Chat
                                </button>
                                <button id="save-conversation" class="conversation-btn" title="Save current conversation">
                                    💾 Save
                                </button>
                                <button id="export-conversation" class="conversation-btn" data-format="json" title="Export conversation">
                                    📤 Export
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;

        return html;
    }

    function setupTabSwitching() {
        // No tabs needed - simple layout
        console.log('Character modal loaded - simple layout with message editing support');
    }

    function showErrorModal(errorMessage, fileUrl) {
        $('#modal-content').html(
            `<div class="character-modal-wrapper">
                <div class="character-details">
                    <div class="character-header">
                        <h2>Error Loading Character</h2>
                    </div>
                    <div class="character-section">
                        <p>${escapeHtml(errorMessage)}</p>
                        <p>Please check the browser console for more details.</p>
                        ${fileUrl ? `
                        <div class="character-actions">
                            <a href="${fileUrl}" class="png-download-button" download>Download Character</a>
                        </div>` : ''}
                    </div>
                </div>
            </div>`
        );
        $('#png-modal').fadeIn();
    }

    // Helper function for HTML escaping
    function escapeHtml(str) {
        if (typeof str !== 'string') return '';
        return str.replace(/[&<>"']/g, function(match) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#39;'
            }[match];
        });
    }

    // ========= RESPONSIVE TAG FILTERING SYSTEM =========
    function initTagFilters() {
        const $filterWrap = $('.filter-wrap');
        if (!$filterWrap.length) {
            console.log('No filter wrap element found, skipping tag filters');
            return;
        }

        // Collect all tags from PNG cards
        const allTags = new Set();
        $('.png-card').each(function() {
            const tagsStr = $(this).attr('data-tags');
            if (tagsStr) {
                try {
                    const tags = JSON.parse(tagsStr);
                    if (Array.isArray(tags)) {
                        tags.forEach(tag => allTags.add(tag.trim()));
                    }
                } catch (e) {
                    console.warn('Failed to parse tags:', tagsStr);
                }
            }
        });

        if (allTags.size === 0) {
            console.log('No tags found in PNG cards');
            return;
        }

        const sortedTags = Array.from(allTags).sort();
        console.log('Found tags:', sortedTags);

        // Build filter HTML
        const filterHtml = `
            <div class="tag-filters">
                <h3>Filter by Tags</h3>
                <div class="filter-row">
                    <label for="tag-filter-1">Include:</label>
                    <select id="tag-filter-1" class="tag-filter" data-filter="filter1" multiple>
                        ${sortedTags.map(tag => `<option value="${escapeHtml(tag)}">${escapeHtml(tag)}</option>`).join('')}
                    </select>
                </div>
                <div class="filter-row">
                    <label for="tag-filter-2">Also Include:</label>
                    <select id="tag-filter-2" class="tag-filter" data-filter="filter2" multiple>
                        ${sortedTags.map(tag => `<option value="${escapeHtml(tag)}">${escapeHtml(tag)}</option>`).join('')}
                    </select>
                </div>
                <div class="filter-row">
                    <label for="tag-filter-3">Exclude:</label>
                    <select id="tag-filter-3" class="tag-filter" data-filter="filter3" multiple>
                        ${sortedTags.map(tag => `<option value="${escapeHtml(tag)}">${escapeHtml(tag)}</option>`).join('')}
                    </select>
                </div>
                <div class="filter-actions">
                    <button id="apply-filters" class="filter-btn">Apply Filters</button>
                    <button id="clear-filters" class="filter-btn">Clear All</button>
                </div>
                <div class="filter-info">
                    <span id="visible-count">${$('.png-card').length}</span> of ${$('.png-card').length} characters shown
                </div>
            </div>
        `;

        $filterWrap.html(filterHtml);

        // Initialize multi-select if Select2 is available
        if ($.fn.select2) {
            $('.tag-filter').each(function() {
                $(this).select2({
                    placeholder: 'Select tags...',
                    allowClear: true,
                    width: '100%'
                });
            });
        }

        // Filter event handlers
        $('#apply-filters').on('click', applyTagFilters);
        $('#clear-filters').on('click', clearTagFilters);

        $('.tag-filter').on('change', function() {
            const filterId = $(this).data('filter');
            window.selectedTags[filterId] = $(this).val() || [];
            console.log('Tag selection changed:', filterId, window.selectedTags[filterId]);
        });

        console.log('Tag filters initialized');
    }

    function applyTagFilters() {
        console.log('Applying responsive tag filters:', window.selectedTags);

        const include1 = window.selectedTags.filter1 || [];
        const include2 = window.selectedTags.filter2 || [];
        const exclude = window.selectedTags.filter3 || [];

        let visibleCount = 0;

        $('.png-card').each(function() {
            const $card = $(this);
            const tagsStr = $card.attr('data-tags');
            let cardTags = [];

            if (tagsStr) {
                try {
                    cardTags = JSON.parse(tagsStr) || [];
                } catch (e) {
                    console.warn('Failed to parse card tags:', tagsStr);
                }
            }

            let shouldShow = true;

            // Check include filters (AND logic within each group)
            if (include1.length > 0) {
                const hasAll1 = include1.every(tag => cardTags.includes(tag));
                if (!hasAll1) shouldShow = false;
            }

            if (include2.length > 0 && shouldShow) {
                const hasAll2 = include2.every(tag => cardTags.includes(tag));
                if (!hasAll2) shouldShow = false;
            }

            // Check exclude filter
            if (exclude.length > 0 && shouldShow) {
                const hasExcluded = exclude.some(tag => cardTags.includes(tag));
                if (hasExcluded) shouldShow = false;
            }

            if (shouldShow) {
                $card.show();
                visibleCount++;
            } else {
                $card.hide();
            }
        });

        // Re-layout masonry after filtering with responsive considerations
        if (masonryInitialized && $('.png-cards').data('masonry')) {
            setTimeout(() => {
                $('.png-cards').masonry('layout');
            }, 100);
        }

        // Update count
        $('#visible-count').text(visibleCount);
        console.log(`Responsive filter applied: ${visibleCount} cards visible`);
    }

    function clearTagFilters() {
        console.log('Clearing all tag filters');

        // Reset selections
        $('.tag-filter').val(null).trigger('change');
        window.selectedTags = { filter1: [], filter2: [], filter3: [] };

        // Show all cards
        $('.png-card').show();

        // Re-layout masonry
        if (masonryInitialized && $('.png-cards').data('masonry')) {
            setTimeout(() => {
                $('.png-cards').masonry('layout');
            }, 100);
        }

        // Update count
        $('#visible-count').text($('.png-card').length);
        console.log('All filters cleared');
    }

    // ========= RESPONSIVE SEARCH FUNCTIONALITY =========
    function initSearch() {
        const $searchWrap = $('.search-wrap');
        if (!$searchWrap.length) {
            console.log('No search wrap element found, skipping search');
            return;
        }

        const searchHtml = `
            <div class="search-container">
                <input type="text" id="character-search" placeholder="Search characters by name, creator, or description..." />
                <button id="clear-search" class="search-clear-btn">×</button>
            </div>
        `;

        $searchWrap.html(searchHtml);

        // Search functionality
        let searchTimeout;
        $('#character-search').on('input', function() {
            clearTimeout(searchTimeout);
            const query = $(this).val().toLowerCase().trim();

            searchTimeout = setTimeout(() => {
                performSearch(query);
            }, 300);
        });

        $('#clear-search').on('click', function() {
            $('#character-search').val('');
            performSearch('');
        });

        console.log('Search functionality initialized');
    }

    function performSearch(query) {
        console.log('Performing responsive search:', query);

        let visibleCount = 0;

        $('.png-card').each(function() {
            const $card = $(this);
            const name = ($card.find('.character-name').text() || '').toLowerCase();
            const creator = ($card.attr('data-creator') || '').toLowerCase();
            const description = ($card.attr('data-description') || '').toLowerCase();

            const matches = !query ||
                           name.includes(query) ||
                           creator.includes(query) ||
                           description.includes(query);

            if (matches) {
                $card.show();
                visibleCount++;
            } else {
                $card.hide();
            }
        });

        // Re-layout masonry after search with responsive considerations
        if (masonryInitialized && $('.png-cards').data('masonry')) {
            setTimeout(() => {
                $('.png-cards').masonry('layout');
            }, 100);
        }

        // Update filter count if it exists
        if ($('#visible-count').length) {
            $('#visible-count').text(visibleCount);
        }

        console.log(`Responsive search completed: ${visibleCount} cards visible`);
    }

    // ========= CONVERSATION MANAGEMENT INTEGRATION WITH MESSAGE EDITING AND AUTO-SAVE =========
    $(document)
        .on('click', '.close-modal, #png-modal', function(e) {
            if (e.target === this || $(e.target).hasClass('close-modal')) {
                // Check for unsaved edits before closing
                if (window.PMV_MobileChat && window.PMV_MobileChat.isEditing && window.PMV_MobileChat.isEditing()) {
                    const confirmClose = confirm('You have unsaved message edits. Are you sure you want to close?');
                    if (!confirmClose) {
                        return;
                    }
                }

                // Check for unsaved conversation changes
                if (window.PMV_MobileChat && window.PMV_MobileChat.hasUnsavedChanges && window.PMV_MobileChat.hasUnsavedChanges()) {
                    const confirmClose = confirm('You have unsaved conversation changes. Are you sure you want to close?');
                    if (!confirmClose) {
                        return;
                    }
                }

                $('#png-modal').fadeOut().find('.png-modal-content').removeClass('chat-mode');
                if (window.PMV_Chat) window.PMV_Chat.resetChatState?.();
                if (window.PMV_ConversationManager) window.PMV_ConversationManager.reset?.();
            }
        })
        .on('click', '#new-conversation', function(e) {
            e.preventDefault();
            console.log('New conversation button clicked from modal');

            // Check for unsaved changes
            let hasUnsavedChanges = false;
            if (window.PMV_MobileChat && window.PMV_MobileChat.isEditing && window.PMV_MobileChat.isEditing()) {
                hasUnsavedChanges = true;
            }
            if (window.PMV_MobileChat && window.PMV_MobileChat.hasUnsavedChanges && window.PMV_MobileChat.hasUnsavedChanges()) {
                hasUnsavedChanges = true;
            }

            if (hasUnsavedChanges) {
                const confirmNew = confirm('You have unsaved changes. Starting a new conversation will lose these changes. Continue?');
                if (!confirmNew) {
                    return;
                }
            }

            if (window.PMV_ConversationManager && window.PMV_ConversationManager.startNewConversation) {
                window.PMV_ConversationManager.startNewConversation();
            } else {
                console.log('Starting new conversation by clearing current chat');
                $('#chat-history').empty();
                
                // Add initial message if in chat context
                if ($('#chat-history').length > 0) {
                    const $modal = $('#png-modal').find('.png-modal-content');
                    const characterData = $modal.data('characterData');
                    if (characterData) {
                        const character = window.PMV_Chat ?
                            window.PMV_Chat.extractCharacterInfo(characterData) :
                            (characterData.data || characterData);
                        const firstMessage = character.first_mes || `Hello, I am ${character.name}. How can I help you today?`;
                        
                        if (window.PMV_MobileChat && window.PMV_MobileChat.addEditableMessage) {
                            window.PMV_MobileChat.addEditableMessage(
                                firstMessage,
                                'bot',
                                character.name || 'AI'
                            );
                        } else {
                            $('#chat-history').append(`
                                <div class="chat-message bot">
                                    <strong>${escapeHtml(character.name || 'AI')}:</strong>
                                    <span class="chat-message-content-wrapper">${escapeHtml(firstMessage)}</span>
                                </div>
                            `);
                        }
                    }
                    $modal.data('isNewConversation', true);
                    
                    // Clear modification state
                    if (window.PMV_MobileChat && window.PMV_MobileChat.clearModified) {
                        window.PMV_MobileChat.clearModified();
                    }
                }
            }
        })
        .on('click', '#save-conversation', function(e) {
            e.preventDefault();
            console.log('Save conversation button clicked from modal');
            
            const $btn = $(this);
            const originalText = $btn.text();
            
            // Disable button during save
            $btn.prop('disabled', true).text('Saving...');

            // Enhanced save logic with better integration
            if (window.PMV_ConversationManager && window.PMV_ConversationManager.saveCurrentConversation) {
                try {
                    // Use the conversation manager's save method which prompts for title
                    window.PMV_ConversationManager.saveCurrentConversation();
                    
                    // Add success feedback
                    setTimeout(() => {
                        $btn.text('✓ Saved').prop('disabled', false);
                        setTimeout(() => {
                            $btn.text(originalText);
                        }, 2000);
                    }, 1000);
                } catch (error) {
                    console.error('Error saving conversation:', error);
                    $btn.text('✗ Error').prop('disabled', false);
                    setTimeout(() => {
                        $btn.text(originalText);
                    }, 2000);
                }
            } else if (window.PMV_MobileChat && window.PMV_MobileChat.performAutoSave) {
                // Fallback to auto-save method
                console.log('Using auto-save as fallback for manual save');
                try {
                    window.PMV_MobileChat.performAutoSave();
                    
                    // Add success feedback
                    setTimeout(() => {
                        $btn.text('✓ Saved').prop('disabled', false);
                        setTimeout(() => {
                            $btn.text(originalText);
                        }, 2000);
                    }, 1000);
                } catch (error) {
                    console.error('Error with auto-save:', error);
                    $btn.text('✗ Error').prop('disabled', false);
                    setTimeout(() => {
                        $btn.text(originalText);
                    }, 2000);
                }
            } else {
                console.warn('No save method available');
                alert('Conversation manager not available');
                $btn.prop('disabled', false).text(originalText);
            }
        })
        .on('click', '#export-conversation', function(e) {
            e.preventDefault();
            console.log('Export conversation button clicked from modal');
            
            const format = $(this).data('format') || 'json';
            const $btn = $(this);
            const originalText = $btn.text();
            
            // Disable button during export
            $btn.prop('disabled', true).text('Exporting...');

            if (window.PMV_ConversationManager && window.PMV_ConversationManager.exportConversation) {
                try {
                    window.PMV_ConversationManager.exportConversation(format);
                    
                    // Add success feedback
                    setTimeout(() => {
                        $btn.text('✓ Exported').prop('disabled', false);
                        setTimeout(() => {
                            $btn.text(originalText);
                        }, 2000);
                    }, 500);
                } catch (error) {
                    console.error('Error exporting conversation:', error);
                    $btn.text('✗ Error').prop('disabled', false);
                    setTimeout(() => {
                        $btn.text(originalText);
                    }, 2000);
                }
            } else {
                // Enhanced fallback export functionality
                console.log('Using fallback export method');
                try {
                    const messages = window.PMV_Chat ? window.PMV_Chat.collectConversationHistory() : [];
                    const $modal = $('#png-modal').find('.png-modal-content');
                    const characterData = $modal.data('characterData');
                    const character = window.PMV_Chat ? window.PMV_Chat.extractCharacterInfo(characterData || {}) : {};
                    
                    const exportData = {
                        character: character.name || 'Unknown',
                        character_data: character,
                        timestamp: new Date().toISOString(),
                        messages: messages,
                        format: format,
                        exported_by: 'PNG Metadata Viewer',
                        version: '1.0'
                    };

                    const dataStr = JSON.stringify(exportData, null, 2);
                    const dataBlob = new Blob([dataStr], {type: 'application/json'});
                    const url = URL.createObjectURL(dataBlob);
                    const downloadLink = document.createElement('a');
                    downloadLink.href = url;
                    downloadLink.download = `conversation_${(character.name || 'unknown').replace(/[^a-z0-9]/gi, '_')}_${new Date().toISOString().split('T')[0]}.json`;
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                    document.body.removeChild(downloadLink);
                    URL.revokeObjectURL(url);

                    // Add success feedback
                    setTimeout(() => {
                        $btn.text('✓ Exported').prop('disabled', false);
                        setTimeout(() => {
                            $btn.text(originalText);
                        }, 2000);
                    }, 500);
                } catch (error) {
                    console.error('Error with fallback export:', error);
                    alert('Failed to export conversation: ' + error.message);
                    $btn.text('✗ Error').prop('disabled', false);
                    setTimeout(() => {
                        $btn.text(originalText);
                    }, 2000);
                }
            }
        });

    // ========= ADDITIONAL UTILITY FUNCTIONS =========
    // Lazy loading for images
    function initLazyLoading() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src;
                        img.classList.remove('lazy');
                        imageObserver.unobserve(img);
                    }
                });
            });

            document.querySelectorAll('img[data-src]').forEach(img => {
                imageObserver.observe(img);
            });
        }
    }

    // Performance monitoring for large galleries
    function monitorPerformance() {
        if (performance && performance.mark) {
            performance.mark('pmv-init-start');
            $(window).on('load', function() {
                performance.mark('pmv-init-end');
                performance.measure('pmv-init-duration', 'pmv-init-start', 'pmv-init-end');
                const measures = performance.getEntriesByType('measure');
                const initMeasure = measures.find(m => m.name === 'pmv-init-duration');
                if (initMeasure) {
                    console.log(`PMV initialization took ${initMeasure.duration.toFixed(2)}ms`);
                }
            });
        }
    }

    // Error boundary for JavaScript errors
    function setupErrorHandling() {
        window.addEventListener('error', function(event) {
            if (event.error && event.error.stack &&
                (event.error.stack.includes('pmv') ||
                 event.error.stack.includes('masonry') ||
                 event.error.stack.includes('message editing') ||
                 event.error.stack.includes('auto-save'))) {
                console.error('PMV Error caught:', {
                    message: event.error.message,
                    filename: event.filename,
                    lineno: event.lineno,
                    stack: event.error.stack
                });
            }
        });

        // Handle unhandled promise rejections
        window.addEventListener('unhandledrejection', function(event) {
            if (event.reason && event.reason.toString().includes('PMV')) {
                console.error('PMV Promise rejection:', event.reason);
                event.preventDefault();
            }
        });
    }

    // Accessibility improvements
    function setupAccessibility() {
        // Add ARIA labels for dynamically created elements
        $(document).on('DOMNodeInserted', '.png-card', function() {
            const $card = $(this);
            const characterName = $card.find('.character-name').text() || 'Character';
            $card.attr('aria-label', `Character card: ${characterName}`);
        });

        // Keyboard navigation for cards
        $(document).on('keydown', '.png-card', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                $(this).find('img').click();
            }
        });

        // High contrast mode detection
        if (window.matchMedia && window.matchMedia('(prefers-contrast: high)').matches) {
            $('body').addClass('high-contrast-mode');
        }

        // Reduced motion preference
        if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            $('body').addClass('reduced-motion-mode');
        }
    }

    // Dark mode support
    function setupDarkMode() {
        // Detect system dark mode preference
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            $('body').addClass('dark-mode');
        }

        // Listen for changes in color scheme preference
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
                if (e.matches) {
                    $('body').addClass('dark-mode');
                } else {
                    $('body').removeClass('dark-mode');
                }

                // Reapply masonry layout if needed
                if (masonryInitialized && $('.png-cards').data('masonry')) {
                    setTimeout(() => {
                        $('.png-cards').masonry('layout');
                    }, 100);
                }
            });
        }
    }

    // Enhanced auto-save integration monitoring
    function setupAutoSaveMonitoring() {
        // Monitor for auto-save events
        $(document).on('message:added', function(e, data) {
            console.log('Message added event detected in script.js, auto-save should be triggered');
            // Ensure auto-save is triggered if mobile chat system is available
            if (window.PMV_MobileChat && window.PMV_MobileChat.triggerAutoSave) {
                // Debounced auto-save after message addition
                setTimeout(() => {
                    window.PMV_MobileChat.triggerAutoSave(3000);
                }, 500);
            }
        });

        $(document).on('message:edited', function(e, $message) {
            console.log('Message edited event detected in script.js');
            // Trigger auto-save after message editing
            if (window.PMV_MobileChat && window.PMV_MobileChat.triggerAutoSave) {
                window.PMV_MobileChat.triggerAutoSave(1000);
            }
        });

        $(document).on('message:deleted', function(e, $message) {
            console.log('Message deleted event detected in script.js');
            // Trigger auto-save after message deletion
            if (window.PMV_MobileChat && window.PMV_MobileChat.triggerAutoSave) {
                window.PMV_MobileChat.triggerAutoSave(1000);
            }
        });

        $(document).on('message:regenerated', function(e, $message, newContent) {
            console.log('Message regenerated event detected in script.js');
            // Trigger auto-save after message regeneration
            if (window.PMV_MobileChat && window.PMV_MobileChat.triggerAutoSave) {
                window.PMV_MobileChat.triggerAutoSave(2000);
            }
        });

        console.log('Auto-save monitoring setup complete');
    }

    // Export functionality for debugging with responsive info
    window.PMV_Debug = {
        getMasonryStatus: () => ({
            initialized: masonryInitialized,
            imagesLoaded: imagesLoaded,
            totalImages: totalImages,
            currentBreakpoint: currentBreakpoint
        }),
        getSelectedTags: () => window.selectedTags,
        resetMasonry: () => {
            masonryInitialized = false;
            initMasonry();
        },
        reapplyFilters: applyTagFilters,
        clearAllFilters: clearTagFilters,
        performSearch: performSearch,
        getCurrentBreakpoint: getCurrentBreakpoint,
        forceResponsiveResize: handleResponsiveResize,
        getImageInfo: () => ({
            totalImages: totalImages,
            imagesLoaded: imagesLoaded,
            masonryInitialized: masonryInitialized,
            currentBreakpoint: currentBreakpoint
        }),
        triggerAutoSave: () => {
            if (window.PMV_MobileChat && window.PMV_MobileChat.triggerAutoSave) {
                window.PMV_MobileChat.triggerAutoSave(0);
                return 'Auto-save triggered';
            }
            return 'Auto-save not available';
        },
        checkAutoSaveStatus: () => {
            if (window.PMV_MobileChat) {
                return {
                    hasUnsavedChanges: window.PMV_MobileChat.hasUnsavedChanges ? window.PMV_MobileChat.hasUnsavedChanges() : false,
                    isEditing: window.PMV_MobileChat.isEditing ? window.PMV_MobileChat.isEditing() : false,
                    autoSaveEnabled: window.PMV_MobileChat.enableAutoSave ? true : false
                };
            }
            return 'Mobile chat system not available';
        },
        // New centering debug functions
        checkCentering: () => {
            const wrapper = $('.png-cards-loading-wrapper');
            const grid = $('.png-cards');
            const cards = $('.png-card');
            
            console.log('Container centering debug:', {
                wrapperOffset: wrapper.offset(),
                wrapperWidth: wrapper.width(),
                gridOffset: grid.offset(),
                gridWidth: grid.width(),
                cardCount: cards.length,
                viewport: $(window).width(),
                currentBreakpoint: getCurrentBreakpoint()
            });
            
            // Highlight container boundaries
            wrapper.css('border', '2px solid red');
            grid.css('border', '2px solid blue');
            
            setTimeout(() => {
                wrapper.css('border', '');
                grid.css('border', '');
            }, 3000);
        },
        forceCenterGrid: () => {
            const $grid = $('.png-cards');
            $grid.css({
                'margin': '0 auto',
                'width': '100%',
                'margin-left': '0',
                'margin-right': '0'
            });
            if ($grid.data('masonry')) {
                $grid.masonry('layout');
            }
            console.log('Forced grid centering applied');
        }
    };

    // ========= INITIALIZATION =========
    $(function() {
        console.log('Initializing PNG Metadata Viewer with Message Editing, Auto-save, and Centered Responsive Masonry support...');
        console.log('Initial breakpoint:', getCurrentBreakpoint());

        // Setup error handling first
        setupErrorHandling();

        // Start performance monitoring
        monitorPerformance();

        // Initialize accessibility features
        setupAccessibility();

        // Setup dark mode
        setupDarkMode();

        // Setup auto-save monitoring
        setupAutoSaveMonitoring();

        // Initialize core functionality with responsive support
        setTimeout(() => {
            initMasonry();
            initLazyLoading();
            setTimeout(() => {
                initTagFilters();
                initSearch();
            }, 1000);
        }, 100);

        // Initialize message editing system if available
        if (window.PMV_MobileChat && window.PMV_MobileChat.init) {
            console.log('Initializing message editing system...');
            setTimeout(() => {
                window.PMV_MobileChat.init();
            }, 200);
        }

        console.log('PNG Metadata Viewer initialization with Message Editing, Auto-save, and Centered Responsive Masonry complete');
    });

    // Global error handler for message editing and auto-save
    window.addEventListener('error', function(event) {
        if (event.error && event.error.message &&
            (event.error.message.includes('message editing') ||
             event.error.message.includes('PMV_MobileChat') ||
             event.error.message.includes('auto-save') ||
             event.error.message.includes('conversation') ||
             event.error.message.includes('masonry'))) {
            console.error('PMV System error:', event.error);
        }
    });

    // Periodic auto-save check (every 30 seconds)
    setInterval(function() {
        if (window.PMV_MobileChat &&
            window.PMV_MobileChat.hasUnsavedChanges &&
            window.PMV_MobileChat.hasUnsavedChanges() &&
            !window.PMV_MobileChat.isEditing()) {
            console.log('Periodic auto-save check: triggering save for unsaved changes');
            if (window.PMV_MobileChat.triggerAutoSave) {
                window.PMV_MobileChat.triggerAutoSave(1000);
            }
        }
    }, 30000); // 30 seconds

    // Auto-save on page visibility change (when user switches tabs/windows)
    document.addEventListener('visibilitychange', function() {
        if (document.hidden &&
            window.PMV_MobileChat &&
            window.PMV_MobileChat.hasUnsavedChanges &&
            window.PMV_MobileChat.hasUnsavedChanges()) {
            console.log('Page hidden with unsaved changes, triggering auto-save');
            if (window.PMV_MobileChat.performAutoSave) {
                window.PMV_MobileChat.performAutoSave();
            }
        }
    });

    // Expose some functions globally for external access
    window.PMV_Gallery = {
        initMasonry: initMasonry,
        applyTagFilters: applyTagFilters,
        clearTagFilters: clearTagFilters,
        performSearch: performSearch,
        openCharacterModal: openCharacterModal,
        escapeHtml: escapeHtml,
        getCurrentBreakpoint: getCurrentBreakpoint,
        handleResponsiveResize: handleResponsiveResize,
        triggerAutoSave: () => {
            if (window.PMV_MobileChat && window.PMV_MobileChat.triggerAutoSave) {
                window.PMV_MobileChat.triggerAutoSave();
                return true;
            }
            return false;
        }
    };

    console.log('PNG Metadata Viewer Enhanced Script with Message Editing, Auto-save, and Centered Responsive Masonry loaded and ready');
});
