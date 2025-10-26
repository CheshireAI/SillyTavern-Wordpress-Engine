/**
 * PNG Metadata Viewer - Voting System Frontend
 * Handles upvotes/downvotes for character cards
 * Version: 1.0
 */

(function($) {
    'use strict';
    
    // Voting system object
    const PMVVoting = {
        
        init: function() {
            console.log('PMV Voting: Initializing...');
            console.log('PMV Voting: pmv_voting object available:', typeof pmv_voting !== 'undefined');
            if (typeof pmv_voting !== 'undefined') {
                console.log('PMV Voting: pmv_voting.is_logged_in:', pmv_voting.is_logged_in);
                console.log('PMV Voting: pmv_voting.ajax_url:', pmv_voting.ajax_url);
                console.log('PMV Voting: pmv_voting.nonce:', pmv_voting.nonce);
            }
            this.bindEvents();
            this.initializeVoteButtons();
            console.log('PMV Voting: Initialization complete');
        },
        
        bindEvents: function() {
            // Handle upvote clicks
            $(document).on('click', '.pmv-upvote', function(e) {
                e.preventDefault();
                e.stopPropagation();
                PMVVoting.handleVote(e);
            });
            
            // Handle downvote clicks
            $(document).on('click', '.pmv-downvote', function(e) {
                e.preventDefault();
                e.stopPropagation();
                PMVVoting.handleVote(e);
            });
            
            // Handle vote button hovers for tooltips
            $(document).on('mouseenter', '.pmv-vote-btn', function() {
                PMVVoting.showVoteTooltip($(this));
            }).on('mouseleave', '.pmv-vote-btn', function() {
                PMVVoting.hideVoteTooltip();
            });
        },
        
        initializeVoteButtons: function() {
            console.log('PMV Voting: Initializing vote buttons...');
            
            // Add vote buttons to existing character cards
            const existingCards = $('.png-card');
            console.log('PMV Voting: Found ' + existingCards.length + ' existing character cards');
            
            existingCards.each(function() {
                PMVVoting.addVoteButtons($(this));
            });
            
            // Add vote buttons to newly loaded cards (for AJAX pagination)
            $(document).on('pmv_cards_loaded', function() {
                console.log('PMV Voting: Cards loaded event triggered');
                $('.png-card').each(function() {
                    if (!$(this).find('.pmv-vote-container').length) {
                        PMVVoting.addVoteButtons($(this));
                    }
                });
            });
            
            console.log('PMV Voting: Vote button initialization complete');
        },
        
        addVoteButtons: function($card) {
            console.log('PMV Voting: Adding vote buttons to card:', $card);
            console.log('PMV Voting: Card HTML:', $card.html().substring(0, 200) + '...');

            // Don't add if already exists
            if ($card.find('.pmv-vote-container').length) {
                console.log('PMV Voting: Vote container already exists, skipping');
                return;
            }

            const characterFilename = PMVVoting.getCharacterFilename($card);
            console.log('PMV Voting: Character filename:', characterFilename);

            if (!characterFilename) {
                console.log('PMV Voting: No character filename found, skipping');
                return;
            }

            // Create streamlined vote container
            const $voteContainer = $(`
                <div class="pmv-vote-container">
                    <button class="pmv-vote-btn pmv-upvote" data-character="${characterFilename}" data-vote="upvote" title="Upvote">
                        <span class="pmv-vote-icon">▲</span>
                        <span class="pmv-vote-count">0</span>
                    </button>
                    <span class="pmv-vote-score">0</span>
                    <button class="pmv-vote-btn pmv-downvote" data-character="${characterFilename}" data-vote="downvote" title="Downvote">
                        <span class="pmv-vote-icon">▼</span>
                        <span class="pmv-vote-count">0</span>
                    </button>
                </div>
            `);

            // Add login prompt for non-logged-in users
            if (!pmv_voting.is_logged_in) {
                $voteContainer.append(`
                    <div class="pmv-login-prompt">
                        <a href="${pmv_voting.login_url}">Login to vote</a>
                    </div>
                `);
                $voteContainer.find('.pmv-vote-btn').addClass('pmv-disabled');
            }

            // Insert after the image container
            $card.find('.png-image-container').after($voteContainer);
            console.log('PMV Voting: Vote container added to card');

            // Load initial vote stats
            this.loadVoteStats(characterFilename, $voteContainer);
        },
        
        getCharacterFilename: function($card) {
            // First try to get from data-file-url attribute (most reliable)
            const fileUrl = $card.data('file-url');
            if (fileUrl) {
                const filename = fileUrl.split('/').pop(); // Get filename from URL
                console.log('PMV Voting: Got filename from data-file-url:', filename);
                return filename;
            }
            
            // Fallback: try to get filename from image src
            const $img = $card.find('img');
            if ($img.length) {
                const src = $img.attr('src');
                if (src) {
                    const filename = src.split('/').pop(); // Get filename from URL
                    console.log('PMV Voting: Got filename from img src:', filename);
                    return filename;
                }
            }
            
            // Last fallback: try other data attributes
            const fallback = $card.data('character-filename') || $card.data('metadata');
            console.log('PMV Voting: Using fallback filename:', fallback);
            return fallback;
        },
        
        handleVote: function(e) {
            e.preventDefault();
            
            console.log('PMV Voting: handleVote called with event:', e);
            
            const $btn = $(e.currentTarget);
            const characterFilename = $btn.data('character');
            const voteType = $btn.data('vote');
            
            console.log('PMV Voting: Button element:', $btn);
            console.log('PMV Voting: Character filename from data:', characterFilename);
            console.log('PMV Voting: Vote type from data:', voteType);
            
            if (!pmv_voting.is_logged_in) {
                console.log('PMV Voting: User not logged in, showing login prompt');
                this.showLoginPrompt();
                return;
            }
            
            console.log('PMV Voting: User is logged in, proceeding with vote');
            console.log('PMV Voting: Handling vote:', voteType, 'for character:', characterFilename);
            
            // Disable button during request
            $btn.prop('disabled', true);
            
            $.ajax({
                url: pmv_voting.ajax_url,
                type: 'POST',
                data: {
                    action: 'pmv_vote_character',
                    character_filename: characterFilename,
                    vote_type: voteType,
                    nonce: pmv_voting.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.showVoteMessage('Vote recorded successfully!', 'success');
                        // Reload vote stats for this card
                        const $voteContainer = $btn.closest('.pmv-vote-container');
                        this.loadVoteStats(characterFilename, $voteContainer);
                    } else {
                        this.showVoteMessage(response.data || 'Error recording vote', 'error');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('PMV Voting: AJAX error:', error);
                    this.showVoteMessage('Error recording vote', 'error');
                },
                complete: () => {
                    $btn.prop('disabled', false);
                }
            });
        },
        
        loadVoteStats: function(characterFilename, $voteContainer) {
            console.log('PMV Voting: Loading vote stats for:', characterFilename);
            console.log('PMV Voting: User logged in:', pmv_voting.is_logged_in);
            console.log('PMV Voting: AJAX URL:', pmv_voting.ajax_url);
            
            // Prepare data - only include nonce for logged-in users
            const ajaxData = {
                action: 'pmv_get_vote_stats',
                character_filename: characterFilename
            };
            
            // Only add nonce if user is logged in
            if (pmv_voting.is_logged_in && pmv_voting.nonce) {
                ajaxData.nonce = pmv_voting.nonce;
                console.log('PMV Voting: Including nonce for logged-in user');
            } else {
                console.log('PMV Voting: No nonce for non-logged-in user');
            }
            
            console.log('PMV Voting: AJAX data:', ajaxData);
            
            $.ajax({
                url: pmv_voting.ajax_url,
                type: 'POST',
                data: ajaxData,
                success: (response) => {
                    console.log('PMV Voting: Vote stats response:', response);
                    if (response.success) {
                        this.updateVoteDisplay($voteContainer, response.data.vote_stats, response.data.user_vote);
                    } else {
                        console.error('PMV Voting: Response not successful:', response);
                    }
                },
                error: (xhr, status, error) => {
                    console.error('PMV Voting: Failed to load vote stats:', error);
                    console.error('PMV Voting: Status:', status);
                    console.error('PMV Voting: Response:', xhr.responseText);
                    console.error('PMV Voting: XHR:', xhr);
                }
            });
        },
        
        updateVoteDisplay: function($voteContainer, voteStats, userVote) {
            // Update vote counts
            $voteContainer.find('.pmv-upvote .pmv-vote-count').text(voteStats.upvotes);
            $voteContainer.find('.pmv-downvote .pmv-vote-count').text(voteStats.downvotes);
            
            // Update score
            $voteContainer.find('.pmv-vote-score').text(voteStats.score);
            
            // Update button states based on user's vote
            $voteContainer.find('.pmv-vote-btn').removeClass('pmv-voted-up pmv-voted-down');
            
            if (userVote) {
                if (userVote.vote_type === 'upvote') {
                    $voteContainer.find('.pmv-upvote').addClass('pmv-voted-up');
                } else if (userVote.vote_type === 'downvote') {
                    $voteContainer.find('.pmv-downvote').addClass('pmv-voted-down');
                }
            }
            
            // Update score color based on positive/negative
            const $scoreElement = $voteContainer.find('.pmv-vote-score');
            $scoreElement.removeClass('pmv-score-positive pmv-score-negative pmv-score-neutral');
            
            if (voteStats.score > 0) {
                $scoreElement.addClass('pmv-score-positive');
            } else if (voteStats.score < 0) {
                $scoreElement.addClass('pmv-score-negative');
            } else {
                $scoreElement.addClass('pmv-score-neutral');
            }
        },
        
        showLoginPrompt: function() {
            const message = pmv_voting.strings.login_required;
            const loginUrl = pmv_voting.login_url;
            
            // Create a simple modal or use existing modal system
            if (typeof showPMVModal === 'function') {
                showPMVModal(`
                    <div class="pmv-login-prompt">
                        <h3>Login Required</h3>
                        <p>${message}</p>
                        <div class="pmv-login-actions">
                            <a href="${loginUrl}" class="pmv-button pmv-button-primary">Login</a>
                            <button class="pmv-button pmv-button-secondary" onclick="closePMVModal()">Cancel</button>
                        </div>
                    </div>
                `);
            } else {
                // Fallback: show alert and redirect
                if (confirm(message + '\n\nWould you like to go to the login page?')) {
                    window.location.href = loginUrl;
                }
            }
        },
        
        showVoteMessage: function(message, type) {
            // Create a temporary notification
            const $notification = $(`
                <div class="pmv-vote-notification pmv-notification-${type}">
                    ${message}
                </div>
            `);
            
            $('body').append($notification);
            
            // Show notification
            $notification.fadeIn(300);
            
            // Auto-hide after 3 seconds
            setTimeout(function() {
                $notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        },
        
        showVoteTooltip: function($button) {
            const tooltipText = $button.attr('title');
            if (!tooltipText) return;
            
            // Create tooltip
            const $tooltip = $(`
                <div class="pmv-vote-tooltip">
                    ${tooltipText}
                </div>
            `);
            
            $('body').append($tooltip);
            
            // Position tooltip
            const buttonOffset = $button.offset();
            const tooltipWidth = $tooltip.outerWidth();
            
            $tooltip.css({
                position: 'absolute',
                top: buttonOffset.top - 30,
                left: buttonOffset.left + ($button.outerWidth() / 2) - (tooltipWidth / 2),
                zIndex: 10000
            });
            
            $tooltip.fadeIn(200);
        },
        
        hideVoteTooltip: function() {
            $('.pmv-vote-tooltip').fadeOut(200, function() {
                $(this).remove();
            });
        }
        
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        console.log('PMV Voting: Document ready, initializing voting system...');
        PMVVoting.init();
    });
    
    // Make available globally
    window.PMVVoting = PMVVoting;
    
})(jQuery); 