// assets/frontend.js
jQuery(document).ready(function($) {
    
    // Star Rating System
    initializeStarRating();
    
    // Number Rating System
    initializeNumberRating();
    
    // Individual Rating Submission
    $('.mvp-submit-rating').on('click', function() {
        var button = $(this);
        var playerId = button.data('player-id');
        var playerItem = button.closest('.mvp-player-rating-item');
        var rating = getRatingValue(playerItem);
        
        if (rating === 0) {
            showMessage('Please select a rating first.', 'error');
            return;
        }
        
        submitRating(playerId, rating, button);
    });
    
    // Bulk Rating Submission
    $('#mvp-submit-all-ratings').on('click', function() {
        var button = $(this);
        var ratings = [];
        var hasErrors = false;
        
        $('.mvp-player-rating-item').each(function() {
            var playerId = $(this).data('player-id');
            var rating = getRatingValue($(this));
            
            if (rating > 0) {
                ratings.push({
                    player_id: playerId,
                    rating: rating
                });
            }
        });
        
        if (ratings.length === 0) {
            showMessage('Please rate at least one player.', 'error');
            return;
        }
        
        button.prop('disabled', true).text('Submitting...');
        
        // Submit all ratings
        var submitted = 0;
        var failed = 0;
        var total = ratings.length;
        
        ratings.forEach(function(ratingData) {
            submitRating(ratingData.player_id, ratingData.rating, null, function(success) {
                submitted++;
                if (!success) failed++;
                
                // Update progress
                var progress = Math.round((submitted / total) * 100);
                button.text('Submitting... (' + progress + '%)');
                
                if (submitted === total) {
                    button.prop('disabled', false).text('Submit All Ratings');
                    if (failed === 0) {
                        showMessage('All ratings submitted successfully! 🎉', 'success');
                        updateBulkSubmitButton();
                    } else {
                        showMessage(failed + ' rating(s) failed to submit. Please try again.', 'error');
                    }
                }
            });
        });
    });
    
    // Initialize star rating functionality
    function initializeStarRating() {
        // Handle star clicks
        $(document).on('click', '.mvp-star, .mvp-star-half', function() {
            var rating = parseFloat($(this).data('value'));
            var container = $(this).closest('.mvp-star-rating');
            var playerId = $(this).closest('.mvp-player-rating-item').data('player-id');
            
            container.data('rating', rating);
            updateStarDisplay(container, rating);
            
            // Auto-submit if enabled
            if (container.hasClass('auto-submit')) {
                var button = container.closest('.mvp-player-rating-item').find('.mvp-submit-rating');
                if (button.length) {
                    setTimeout(function() {
                        button.click();
                    }, 500);
                }
            }
        });
        
        // Handle star hover effects
        $(document).on('mouseenter', '.mvp-star, .mvp-star-half', function() {
            var rating = parseFloat($(this).data('value'));
            var container = $(this).closest('.mvp-star-rating');
            updateStarDisplay(container, rating, true);
        });
        
        $(document).on('mouseleave', '.mvp-star-rating', function() {
            var container = $(this);
            var currentRating = container.data('rating') || 0;
            updateStarDisplay(container, currentRating);
        });
        
        // Initialize existing star displays
        $('.mvp-star-rating').each(function() {
            var rating = parseFloat($(this).data('rating'));
            if (rating > 0) {
                updateStarDisplay($(this), rating);
            }
        });
    }
    
    function updateStarDisplay(container, rating, isHover = false) {
        container.find('.mvp-star, .mvp-star-half').removeClass('active hover');
        
        var fullStars = Math.floor(rating);
        var hasHalf = (rating % 1) >= 0.5;
        
        // Highlight full stars
        for (var i = 1; i <= fullStars; i++) {
            var star = container.find('.mvp-star[data-value="' + i + '"]');
            star.addClass(isHover ? 'hover' : 'active');
        }
        
        // Highlight half star if needed
        if (hasHalf && fullStars < 5) {
            var halfValue = fullStars + 0.5;
            var halfStar = container.find('.mvp-star-half[data-value="' + halfValue + '"]');
            halfStar.addClass(isHover ? 'hover' : 'active');
        }
        
        // Add visual feedback
        if (!isHover) {
            container.addClass('rated');
            setTimeout(function() {
                container.removeClass('rated');
            }, 300);
        }
    }
    
    // Initialize number rating functionality
    function initializeNumberRating() {
        $(document).on('input change', '.mvp-number-rating input', function() {
            var input = $(this);
            var value = parseFloat(input.val());
            var min = parseFloat(input.attr('min'));
            var max = parseFloat(input.attr('max'));
            
            // Validate range
            if (value < min) {
                input.val(min);
                showMessage('Minimum rating is ' + min, 'error');
            } else if (value > max) {
                input.val(max);
                showMessage('Maximum rating is ' + max, 'error');
            }
            
            // Visual feedback
            if (value > 0) {
                input.addClass('has-rating');
            } else {
                input.removeClass('has-rating');
            }
        });
        
        // Auto-submit on Enter key
        $(document).on('keypress', '.mvp-number-rating input', function(e) {
            if (e.which === 13) { // Enter key
                var button = $(this).closest('.mvp-player-rating-item').find('.mvp-submit-rating');
                if (button.length) {
                    button.click();
                }
            }
        });
    }
    
    function getRatingValue(playerItem) {
        var rating = 0;
        
        if (playerItem.find('.mvp-star-rating').length > 0) {
            rating = playerItem.find('.mvp-star-rating').data('rating') || 0;
        } else if (playerItem.find('.mvp-number-rating input').length > 0) {
            rating = parseFloat(playerItem.find('.mvp-number-rating input').val()) || 0;
        }
        
        return rating;
    }
    
    function submitRating(playerId, rating, button, callback) {
        var matchId = $('.mvp-rating-form').data('match-id');
        var originalButtonText = '';
        
        if (button) {
            originalButtonText = button.text();
            button.prop('disabled', true).text('Submitting...');
            button.closest('.mvp-player-rating-item').addClass('mvp-loading');
        }
        
        $.ajax({
            url: mvp_ajax.url,
            type: 'POST',
            data