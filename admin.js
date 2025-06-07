// Enhanced admin.js with bulk selection functionality
jQuery(document).ready(function($) {
    
    // Match accordion functionality
    $('.mvp-match-header').on('click', function(e) {
        // Don't trigger accordion if clicking on form elements
        if ($(e.target).closest('.mvp-rating-toggle, .mvp-switch, input, label').length) {
            return;
        }
        
        var header = $(this);
        var content = header.next('.mvp-match-content');
        var expandBtn = header.find('.mvp-expand-btn');
        var isActive = header.hasClass('active');
        
        // Close all other accordions
        $('.mvp-match-header').removeClass('active');
        $('.mvp-match-content').slideUp(300);
        $('.mvp-expand-btn').removeClass('expanded');
        
        if (!isActive) {
            header.addClass('active');
            content.slideDown(300);
            expandBtn.addClass('expanded');
        }
    });
    
    // Prevent accordion from closing when clicking expand button
    $('.mvp-expand-btn').on('click', function(e) {
        e.stopPropagation();
        var header = $(this).closest('.mvp-match-header');
        header.click();
    });
    
    // Initialize bulk selection functionality
    initializeBulkSelection();
    
    function initializeBulkSelection() {
        // Handle individual checkbox changes
        $(document).on('change', '.mvp-player-checkbox', function() {
            var matchContent = $(this).closest('.mvp-match-content');
            var matchId = matchContent.find('input[name="match_id"]').val();
            
            updatePlayerItemStyle($(this));
            updateSelectionCounts(matchContent, matchId);
            updateMoveButtonStates(matchContent, matchId);
            updateSelectAllStates(matchContent, matchId);
        });
        
        // Handle "Select All" checkboxes
        $(document).on('change', '.mvp-select-all-selected', function() {
            var matchId = $(this).data('match-id');
            var isChecked = $(this).is(':checked');
            var matchContent = $(this).closest('.mvp-match-content');
            
            var checkboxes = matchContent.find('#selected-players-' + matchId + ' .mvp-player-checkbox');
            checkboxes.prop('checked', isChecked);
            
            checkboxes.each(function() {
                updatePlayerItemStyle($(this));
            });
            
            updateSelectionCounts(matchContent, matchId);
            updateMoveButtonStates(matchContent, matchId);
            
            showBulkActionFeedback(isChecked ? 'All selected players checked' : 'All selected players unchecked');
        });
        
        $(document).on('change', '.mvp-select-all-available', function() {
            var matchId = $(this).data('match-id');
            var isChecked = $(this).is(':checked');
            var matchContent = $(this).closest('.mvp-match-content');
            
            var checkboxes = matchContent.find('#available-players-' + matchId + ' .mvp-player-checkbox');
            checkboxes.prop('checked', isChecked);
            
            checkboxes.each(function() {
                updatePlayerItemStyle($(this));
            });
            
            updateSelectionCounts(matchContent, matchId);
            updateMoveButtonStates(matchContent, matchId);
            
            showBulkActionFeedback(isChecked ? 'All available players checked' : 'All available players unchecked');
        });
        
        // Handle bulk move buttons
        $(document).on('click', '.mvp-bulk-move-single', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var button = $(this);
            var action = button.data('action');
            var matchId = button.data('match-id');
            var matchContent = button.closest('.mvp-match-content');
            
            if (action === 'move-to-available') {
                moveSelectedPlayers(matchContent, matchId, 'selected', 'available');
            } else if (action === 'move-to-selected') {
                moveSelectedPlayers(matchContent, matchId, 'available', 'selected');
            }
        });
        
        $(document).on('click', '.mvp-bulk-move-all', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var button = $(this);
            var action = button.data('action');
            var matchId = button.data('match-id');
            var matchContent = button.closest('.mvp-match-content');
            
            if (action === 'move-all-to-available') {
                moveAllPlayers(matchContent, matchId, 'selected', 'available');
            } else if (action === 'move-all-to-selected') {
                moveAllPlayers(matchContent, matchId, 'available', 'selected');
            }
        });
    }
    
    function updatePlayerItemStyle(checkbox) {
        var playerItem = checkbox.closest('.mvp-player-item');
        if (checkbox.is(':checked')) {
            playerItem.addClass('selected');
        } else {
            playerItem.removeClass('selected');
        }
    }
    
    function updateSelectionCounts(matchContent, matchId) {
        var selectedCount = matchContent.find('#selected-players-' + matchId + ' .mvp-player-checkbox:checked').length;
        var availableCount = matchContent.find('#available-players-' + matchId + ' .mvp-player-checkbox:checked').length;
        
        var selectedCountEl = matchContent.find('.mvp-selected-count[data-match-id="' + matchId + '"]');
        var availableCountEl = matchContent.find('.mvp-available-count[data-match-id="' + matchId + '"]');
        
        selectedCountEl.text('(' + selectedCount + ' selected)');
        availableCountEl.text('(' + availableCount + ' selected)');
        
        // Add updated animation
        selectedCountEl.addClass('updated');
        availableCountEl.addClass('updated');
        
        setTimeout(function() {
            selectedCountEl.removeClass('updated');
            availableCountEl.removeClass('updated');
        }, 1000);
    }
    
    function updateMoveButtonStates(matchContent, matchId) {
        var selectedCount = matchContent.find('#selected-players-' + matchId + ' .mvp-player-checkbox:checked').length;
        var availableCount = matchContent.find('#available-players-' + matchId + ' .mvp-player-checkbox:checked').length;
        
        var moveToAvailableBtn = matchContent.find('[data-action="move-to-available"]');
        var moveToSelectedBtn = matchContent.find('[data-action="move-to-selected"]');
        
        // Single move buttons
        moveToAvailableBtn.prop('disabled', selectedCount === 0);
        moveToSelectedBtn.prop('disabled', availableCount === 0);
        
        // Add pulse animation for enabled buttons with selections
        if (selectedCount > 0) {
            moveToAvailableBtn.addClass('has-selection');
        } else {
            moveToAvailableBtn.removeClass('has-selection');
        }
        
        if (availableCount > 0) {
            moveToSelectedBtn.addClass('has-selection');
        } else {
            moveToSelectedBtn.removeClass('has-selection');
        }
        
        // Bulk move all buttons - always enabled if there are players
        var totalSelectedPlayers = matchContent.find('#selected-players-' + matchId + ' .mvp-player-item').length;
        var totalAvailablePlayers = matchContent.find('#available-players-' + matchId + ' .mvp-player-item').length;
        
        matchContent.find('[data-action="move-all-to-available"]').prop('disabled', totalSelectedPlayers === 0);
        matchContent.find('[data-action="move-all-to-selected"]').prop('disabled', totalAvailablePlayers === 0);
    }
    
    function updateSelectAllStates(matchContent, matchId) {
        var selectedContainer = matchContent.find('#selected-players-' + matchId);
        var availableContainer = matchContent.find('#available-players-' + matchId);
        
        var selectedCheckboxes = selectedContainer.find('.mvp-player-checkbox');
        var availableCheckboxes = availableContainer.find('.mvp-player-checkbox');
        
        var selectedChecked = selectedCheckboxes.filter(':checked').length;
        var availableChecked = availableCheckboxes.filter(':checked').length;
        
        var selectAllSelected = matchContent.find('.mvp-select-all-selected[data-match-id="' + matchId + '"]');
        var selectAllAvailable = matchContent.find('.mvp-select-all-available[data-match-id="' + matchId + '"]');
        
        // Update select all checkboxes
        selectAllSelected.prop('checked', selectedCheckboxes.length > 0 && selectedChecked === selectedCheckboxes.length);
        selectAllAvailable.prop('checked', availableCheckboxes.length > 0 && availableChecked === availableCheckboxes.length);
        
        // Set indeterminate state for partial selections
        selectAllSelected.prop('indeterminate', selectedChecked > 0 && selectedChecked < selectedCheckboxes.length);
        selectAllAvailable.prop('indeterminate', availableChecked > 0 && availableChecked < availableCheckboxes.length);
    }
    
    function moveSelectedPlayers(matchContent, matchId, fromList, toList) {
        var fromContainer = matchContent.find('#' + fromList + '-players-' + matchId);
        var toContainer = matchContent.find('#' + toList + '-players-' + matchId);
        
        var selectedPlayers = fromContainer.find('.mvp-player-checkbox:checked');
        
        if (selectedPlayers.length === 0) {
            showBulkActionFeedback('No players selected');
            return;
        }
        
        // Add loading state
        matchContent.find('.mvp-player-lists').addClass('loading');
        
        selectedPlayers.each(function() {
            var checkbox = $(this);
            var playerItem = checkbox.closest('.mvp-player-item');
            var playerId = playerItem.data('player-id');
            
            // Clone and modify item
            var newItem = playerItem.clone();
            
            if (toList === 'selected') {
                newItem.find('input[name="available_player_ids[]"]').attr('name', 'selected_player_ids[]');
                newItem.append('<input type="hidden" name="selected_players[]" value="' + playerId + '">');
            } else {
                newItem.find('input[name="selected_player_ids[]"]').attr('name', 'available_player_ids[]');
                newItem.find('input[name="selected_players[]"]').remove();
            }
            
            newItem.find('.mvp-player-checkbox').prop('checked', false);
            newItem.removeClass('selected');
            
            // Add to destination with animation
            newItem.hide();
            toContainer.append(newItem);
            newItem.slideDown(200);
            
            // Remove from source with animation
            playerItem.slideUp(200, function() {
                playerItem.remove();
                updateAfterMove(matchContent, matchId, selectedPlayers.length, fromList, toList);
            });
        });
    }
    
    function moveAllPlayers(matchContent, matchId, fromList, toList) {
        var fromContainer = matchContent.find('#' + fromList + '-players-' + matchId);
        var toContainer = matchContent.find('#' + toList + '-players-' + matchId);
        
        var allPlayers = fromContainer.find('.mvp-player-item');
        
        if (allPlayers.length === 0) {
            showBulkActionFeedback('No players to move');
            return;
        }
        
        // Confirm bulk action
        var confirmMessage = 'Move all ' + allPlayers.length + ' players from ' + fromList + ' to ' + toList + '?';
        if (!confirm(confirmMessage)) {
            return;
        }
        
        // Add loading state
        matchContent.find('.mvp-player-lists').addClass('loading');
        
        allPlayers.each(function(index) {
            var playerItem = $(this);
            var playerId = playerItem.data('player-id');
            
            // Clone and modify item
            var newItem = playerItem.clone();
            
            if (toList === 'selected') {
                newItem.find('input').attr('name', 'selected_player_ids[]');
                newItem.append('<input type="hidden" name="selected_players[]" value="' + playerId + '">');
            } else {
                newItem.find('input').attr('name', 'available_player_ids[]');
                newItem.find('input[name="selected_players[]"]').remove();
            }
            
            newItem.find('.mvp-player-checkbox').prop('checked', false);
            newItem.removeClass('selected');
            
            // Stagger animations
            setTimeout(function() {
                newItem.hide();
                toContainer.append(newItem);
                newItem.fadeIn(150);
                
                playerItem.fadeOut(150, function() {
                    playerItem.remove();
                    
                    // Update after last item
                    if (index === allPlayers.length - 1) {
                        updateAfterMove(matchContent, matchId, allPlayers.length, fromList, toList);
                    }
                });
            }, index * 50);
        });
    }
    
    function updateAfterMove(matchContent, matchId, movedCount, fromList, toList) {
        // Remove loading state
        matchContent.find('.mvp-player-lists').removeClass('loading');
        
        // Update all states
        updateSelectionCounts(matchContent, matchId);
        updateMoveButtonStates(matchContent, matchId);
        updateSelectAllStates(matchContent, matchId);
        
        // Show feedback
        var message = 'Moved ' + movedCount + ' player' + (movedCount !== 1 ? 's' : '') + ' from ' + fromList + ' to ' + toList;
        showBulkActionFeedback(message);
    }
    
    function showBulkActionFeedback(message) {
        var feedback = $('<div class="mvp-bulk-action-feedback">' + message + '</div>');
        $('body').append(feedback);
        
        setTimeout(function() {
            feedback.remove();
        }, 2000);
    }
    
    // Initialize states on page load
    $('.mvp-match-content').each(function() {
        var matchContent = $(this);
        var matchId = matchContent.find('input[name="match_id"]').val();
        
        if (matchId) {
            updateSelectionCounts(matchContent, matchId);
            updateMoveButtonStates(matchContent, matchId);
            updateSelectAllStates(matchContent, matchId);
        }
    });
    
    // Enhanced team selection for matches
    var clubName = '<?php echo esc_js(get_option("mvp_club_name", "")); ?>';
    
    // Quick fill buttons for team inputs
    if (clubName) {
        $('input[name="home_team"], input[name="away_team"]').each(function() {
            var input = $(this);
            var quickFillBtn = $('<button type="button" class="button button-small mvp-quick-fill" title="Use ' + clubName + '">' + clubName + '</button>');
            quickFillBtn.on('click', function(e) {
                e.preventDefault();
                input.val(clubName);
                input.focus();
            });
            input.after(quickFillBtn);
        });
    }
    
    // Auto-suggest opponent when club is selected
    $('input[name="home_team"]').on('input change', function() {
        var homeTeam = $(this).val();
        var awayInput = $('input[name="away_team"]');
        
        if (homeTeam === clubName && awayInput.val() === '') {
            awayInput.attr('placeholder', 'Enter opponent team');
        } else if (homeTeam !== clubName && awayInput.val() === '') {
            awayInput.val(clubName);
        }
    });
    
    $('input[name="away_team"]').on('input change', function() {
        var awayTeam = $(this).val();
        var homeInput = $('input[name="home_team"]');
        
        if (awayTeam === clubName && homeInput.val() === '') {
            homeInput.attr('placeholder', 'Enter opponent team');
        } else if (awayTeam !== clubName && homeInput.val() === '') {
            homeInput.val(clubName);
        }
    });
    
    // Move buttons functionality for players page
    $('#move-to-inactive').on('click', function() {
        var selectedPlayers = $('#active-players-form input[name="player_ids[]"]:checked');
        if (selectedPlayers.length === 0) {
            alert('Please select players to move to inactive.');
            return;
        }
        
        var form = $('<form method="post" style="display: none;"></form>');
        form.append('<input type="hidden" name="move_action" value="deactivate">');
        selectedPlayers.each(function() {
            form.append('<input type="hidden" name="player_ids[]" value="' + $(this).val() + '">');
        });
        form.append('<input type="hidden" name="move_players" value="1">');
        $('body').append(form);
        form.submit();
    });
    
    $('#move-to-active').on('click', function() {
        var selectedPlayers = $('#inactive-players-form input[name="player_ids[]"]:checked');
        if (selectedPlayers.length === 0) {
            alert('Please select players to move to active.');
            return;
        }
        
        var form = $('<form method="post" style="display: none;"></form>');
        form.append('<input type="hidden" name="move_action" value="activate">');
        selectedPlayers.each(function() {
            form.append('<input type="hidden" name="player_ids[]" value="' + $(this).val() + '">');
        });
        form.append('<input type="hidden" name="move_players" value="1">');
        $('body').append(form);
        form.submit();
    });
    
    // Update button states for players page
    function updateMoveButtons() {
        var activeSelected = $('#active-players-form input[name="player_ids[]"]:checked').length;
        var inactiveSelected = $('#inactive-players-form input[name="player_ids[]"]:checked').length;
        
        $('#move-to-inactive').prop('disabled', activeSelected === 0);
        $('#move-to-active').prop('disabled', inactiveSelected === 0);
    }
    
    $(document).on('change', '#active-players-form input[name="player_ids[]"], #inactive-players-form input[name="player_ids[]"]', updateMoveButtons);
    updateMoveButtons();
    
    // Select All functionality for players page
    $('.select-all').on('click', function() {
        var form = $(this).closest('form');
        var checkboxes = form.find('input[type="checkbox"][name="player_ids[]"]');
        var allChecked = checkboxes.length === checkboxes.filter(':checked').length;
        
        checkboxes.prop('checked', !allChecked);
        $(this).text(allChecked ? 'Select All' : 'Deselect All');
    });
    
    // Show/Hide matches functionality
    $('#show-all-matches').on('click', function() {
        $('.mvp-match-row[style*="display: none"]').slideDown('fast');
        $(this).parent().fadeOut();
    });
    
    // Copy code functionality
    $('.copy-code').on('click', function() {
        var code = $(this).data('code');
        var button = $(this);
        
        if (navigator.clipboard) {
            navigator.clipboard.writeText(code).then(function() {
                var originalText = button.text();
                button.text('Copied!');
                setTimeout(function() {
                    button.text(originalText);
                }, 2000);
            });
        }
    });
    
    // Rating system preview
    $('input[name="rating_system"]').on('change', function() {
        $('.rating-preview').remove();
        
        var preview = $('<div class="rating-preview"><h4>Preview:</h4></div>');
        
        if ($(this).val() === 'stars') {
            preview.append('<div class="stars-preview">★★★★☆ <span style="font-size: 14px; color: #666;">(4.0/5.0)</span></div>');
        } else {
            preview.append('<div class="number-preview">8.5 <span style="font-size: 14px; color: #666;">/10</span></div>');
        }
        
        $(this).closest('fieldset').after(preview);
    });
    
    $('input[name="rating_system"]:checked').trigger('change');
    
    // Vote duration settings
    $('input[name="vote_duration"]').on('change', function() {
        var daysInput = $('input[name="vote_duration_days"]');
        if ($(this).val() === 'days') {
            daysInput.prop('disabled', false).focus();
        } else {
            daysInput.prop('disabled', true);
        }
    });
    
    if ($('input[name="vote_duration"]:checked').val() !== 'days') {
        $('input[name="vote_duration_days"]').prop('disabled', true);
    }
    
    // Form validation
    $('.mvp-add-player-form').on('submit', function(e) {
        var firstName = $('input[name="first_name"]').val().trim();
        var lastName = $('input[name="last_name"]').val().trim();
        
        if (!firstName || !lastName) {
            e.preventDefault();
            alert('First name and last name are required!');
            return false;
        }
    });
    
    $('.mvp-add-match-form').on('submit', function(e) {
        var matchDate = $('input[name="match_date"]').val();
        var homeTeam = $('input[name="home_team"]').val().trim();
        var awayTeam = $('input[name="away_team"]').val().trim();
        
        if (!matchDate || !homeTeam || !awayTeam) {
            e.preventDefault();
            alert('Date, home team, and away team are required!');
            return false;
        }
    });
});