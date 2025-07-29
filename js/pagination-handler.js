/**
 * PMV Pagination Handler
 * Handles pagination rendering and functionality
 * Version: 1.0
 */

jQuery(document).ready(function($) {
    console.log('PMV Pagination Handler: Initialized');
    
    // Global pagination state
    let currentPagination = null;
    let currentPage = 1;
    let totalPages = 1;
    
    // Function to render pagination links
    function renderPagination(pagination) {
        if (!pagination || pagination.total_pages <= 1) {
            $('.pmv-pagination').hide();
            return;
        }
        
        console.log('PMV Pagination: Rendering pagination', pagination);
        
        const $paginationContainer = $('.pmv-pagination');
        $paginationContainer.empty().show();
        
        const pageNumber = pagination.current_page;
        const totalPagesCount = pagination.total_pages;
        const totalItems = pagination.total_items;
        const perPage = pagination.per_page;
        
        // Calculate start and end items
        const startItem = (pageNumber - 1) * perPage + 1;
        const endItem = Math.min(pageNumber * perPage, totalItems);
        
        // Create pagination info
        const infoHtml = `
            <div class="pmv-pagination-info">
                Showing ${startItem}-${endItem} of ${totalItems} characters 
                (Page ${pageNumber} of ${totalPagesCount})
            </div>
        `;
        
        // Create pagination links
        let paginationHtml = '<div class="pmv-pagination-links">';
        
        // Previous button
        if (pageNumber > 1) {
            paginationHtml += `<a href="#" class="pmv-page-link" data-page="${pageNumber - 1}">&laquo; Previous</a>`;
        } else {
            paginationHtml += '<span class="pmv-page-disabled">&laquo; Previous</span>';
        }
        
        // Page numbers
        const maxVisiblePages = 7;
        let startPage = Math.max(1, pageNumber - Math.floor(maxVisiblePages / 2));
        let endPage = Math.min(totalPagesCount, startPage + maxVisiblePages - 1);
        
        // Adjust start page if we're near the end
        if (endPage - startPage < maxVisiblePages - 1) {
            startPage = Math.max(1, endPage - maxVisiblePages + 1);
        }
        
        // First page
        if (startPage > 1) {
            paginationHtml += `<a href="#" class="pmv-page-link" data-page="1">1</a>`;
            if (startPage > 2) {
                paginationHtml += '<span class="pmv-page-ellipsis">...</span>';
            }
        }
        
        // Page numbers
        for (let i = startPage; i <= endPage; i++) {
            if (i === pageNumber) {
                paginationHtml += `<span class="pmv-page-current">${i}</span>`;
            } else {
                paginationHtml += `<a href="#" class="pmv-page-link" data-page="${i}">${i}</a>`;
            }
        }
        
        // Last page
        if (endPage < totalPagesCount) {
            if (endPage < totalPagesCount - 1) {
                paginationHtml += '<span class="pmv-page-ellipsis">...</span>';
            }
            paginationHtml += `<a href="#" class="pmv-page-link" data-page="${totalPagesCount}">${totalPagesCount}</a>`;
        }
        
        // Next button
        if (pageNumber < totalPagesCount) {
            paginationHtml += `<a href="#" class="pmv-page-link" data-page="${pageNumber + 1}">Next &raquo;</a>`;
        } else {
            paginationHtml += '<span class="pmv-page-disabled">Next &raquo;</span>';
        }
        
        paginationHtml += '</div>';
        
        // Combine info and links
        $paginationContainer.html(infoHtml + paginationHtml);
        
        // Store current pagination data
        currentPagination = pagination;
        currentPage = pagination.current_page;
        totalPages = pagination.total_pages;
        
        console.log('PMV Pagination: Pagination rendered successfully');
    }
    
    // Handle pagination link clicks
    $(document).on('click', '.pmv-page-link', function(e) {
        e.preventDefault();
        
        const page = parseInt($(this).data('page'));
        if (page && page !== currentPage && page >= 1 && page <= totalPages) {
            console.log('PMV Pagination: Navigating to page', page);

            // NEW: Optimistically update UI to the requested page to avoid flicker
            if (currentPagination) {
                // Deep-clone the object and override the current_page
                const tempPagination = $.extend(true, {}, currentPagination, {
                    current_page: page
                });
                renderPagination(tempPagination);
                currentPage = page;
            }
            
            // Trigger page change event
            $(document).trigger('pmv_page_change', [page]);
            
            // Update URL without reloading
            const url = new URL(window.location);
            url.searchParams.set('pmv_page', page);
            window.history.pushState({}, '', url);
        }
    });
    
    // Listen for pagination updates from AJAX
    $(document).on('pmv_pagination_updated', function(event, pagination) {
        console.log('PMV Pagination: Received pagination update', pagination);
        renderPagination(pagination);
    });
    
    // Listen for cards loaded event
    $(document).on('pmv_cards_loaded', function(event, data) {
        console.log('PMV Pagination: Cards loaded event received', data);
        if (data && data.pagination) {
            renderPagination(data.pagination);
        }
    });
    
    // Handle browser back/forward buttons
    $(window).on('popstate', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const page = parseInt(urlParams.get('pmv_page')) || 1;
        
        if (page !== currentPage) {
            console.log('PMV Pagination: Browser navigation to page', page);
            $(document).trigger('pmv_page_change', [page]);
        }
    });
    
    // Initialize pagination on page load
    function initializePagination() {
        const urlParams = new URLSearchParams(window.location.search);
        const page = parseInt(urlParams.get('pmv_page')) || 1;
        
        if (page > 1) {
            console.log('PMV Pagination: Initializing with page', page);
            currentPage = page;
            // Trigger initial page change to load correct content
            $(document).trigger('pmv_page_change', [page]);
        }
    }

    // === NEW: Handle page changes by requesting new cards ===
    $(document).on('pmv_page_change', function(event, page) {
        console.log('PMV Pagination: Page change event handler invoked for page', page);
 
        function attemptLoad() {
            if (window.pmvGrid && typeof window.pmvGrid.loadCards === 'function') {
                const filters = (typeof window.pmvGrid.getCurrentFilters === 'function') ? window.pmvGrid.getCurrentFilters() : {};
                // FIX: Correct parameter order - page first, then filters
                window.pmvGrid.loadCards(page, filters);
            } else {
                // Retry shortly in case pmvGrid hasn't been initialised yet
                console.log('PMV Pagination: pmvGrid not available yet, retrying...');
                setTimeout(attemptLoad, 100);
            }
        }

        attemptLoad();
    });
    // === END NEW ===

    // Initialize on document ready
    initializePagination();
    
    // Expose pagination functions globally
    window.pmvPagination = {
        render: renderPagination,
        getCurrentPage: function() { return currentPage; },
        getTotalPages: function() { return totalPages; },
        getPagination: function() { return currentPagination; }
    };
    
    console.log('PMV Pagination Handler: All features loaded');
}); 