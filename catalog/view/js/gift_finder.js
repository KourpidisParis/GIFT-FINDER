$(document).ready(function() {
  // Initialize selected filters array from current state
  var selectedFilters = [{{ current_filter_ids|join(',') }}];
  var filterGroupsData = {{ filter_groups|json_encode|raw }};
  
  // Initialize custom multiselect and selected filters display
  initializeMultiselects();
  updateSelectedFiltersDisplay();
  
  // Custom multiselect functionality
  $(document).on('click', '.multiselect-display', function() {
    $('.custom-multiselect').not($(this).parent()).removeClass('open');
    $(this).parent().toggleClass('open');
  });
  
  // Handle checkbox changes in custom multiselect
  $(document).on('change', '.custom-multiselect input[type="checkbox"]', function() {
    var filterId = parseInt($(this).val());
    var groupId = $(this).closest('.custom-multiselect').data('filter-group-id');
    
    if ($(this).is(':checked')) {
      // Add filter if not already there
      if (selectedFilters.indexOf(filterId) === -1) {
        selectedFilters.push(filterId);
      }
    } else {
      // Remove filter
      var index = selectedFilters.indexOf(filterId);
      if (index > -1) {
        selectedFilters.splice(index, 1);
      }
    }
    
    // Update the display text for this multiselect
    updateMultiselectDisplay(groupId);
    
    // Update the selected filters display
    updateSelectedFiltersDisplay();
    
    // Show loading indicator
    $('#gift-items').html('<div class="col-sm-12 text-center"><i class="fa fa-spinner fa-spin fa-3x"></i></div>');
    
    // Send AJAX request for page 1 of the filtered results
    loadFilteredGifts(selectedFilters, 1);
  });
  
  // Close multiselect when clicking outside
  $(document).on('click', function(e) {
    if (!$(e.target).closest('.custom-multiselect').length) {
      $('.custom-multiselect').removeClass('open');
    }
  });
  
  // Reset all filters click
  $('.reset-all-filters').on('click', function() {
    // Uncheck all checkboxes
    $('.custom-multiselect input[type="checkbox"]').prop('checked', false);
    
    // Clear selected filters array
    selectedFilters = [];
    
    // Update all multiselect displays
    $('.custom-multiselect').each(function() {
      var groupId = $(this).data('filter-group-id');
      updateMultiselectDisplay(groupId);
    });
    
    // Update display
    updateSelectedFiltersDisplay();
    
    // Reload the page to show all gifts
    location.href = '{{ pageUrl }}';
  });
  
  // Remove individual filter tag click
  $(document).on('click', '.filter-tag .remove-filter', function() {
    var filterId = parseInt($(this).data('filter-id'));
    var filterGroupId = getFilterGroupId(filterId);
    
    // Remove from selected filters
    var index = selectedFilters.indexOf(filterId);
    if (index > -1) {
      selectedFilters.splice(index, 1);
    }
    
    // Uncheck the corresponding checkbox
    $('.custom-multiselect[data-filter-group-id="' + filterGroupId + '"] input[value="' + filterId + '"]').prop('checked', false);
    
    // Update multiselect display
    updateMultiselectDisplay(filterGroupId);
    
    // Update display
    updateSelectedFiltersDisplay();
    
    // Show loading indicator
    $('#gift-items').html('<div class="col-sm-12 text-center"><i class="fa fa-spinner fa-spin fa-3x"></i></div>');
    
    // Send AJAX request for page 1 of the filtered results
    loadFilteredGifts(selectedFilters, 1);
  });
  
  // Function to handle AJAX pagination clicks
  $(document).on('click', '#pagination-container a', function(e) {
    e.preventDefault();
    
    // Extract page number from the link
    var pageMatch = $(this).attr('href').match(/page=(\d+)/);
    var page = 1; // Default to page 1
    
    if (pageMatch && pageMatch[1]) {
      page = parseInt(pageMatch[1]);
    } else {
      // Check if this is the first page link (might not have page parameter)
      if ($(this).attr('href').indexOf('page=') === -1) {
        page = 1;
      }
    }
    
    // Load the new page
    loadFilteredGifts(selectedFilters, page);
    
    // Scroll to top of product area
    $('html, body').animate({
      scrollTop: $('#gift-finder').offset().top - 100
    }, 500);
  });
  
  // Initialize multiselect displays
  function initializeMultiselects() {
    $('.custom-multiselect').each(function() {
      var groupId = $(this).data('filter-group-id');
      updateMultiselectDisplay(groupId);
    });
  }
  
  // Update multiselect display text
  function updateMultiselectDisplay(groupId) {
    var multiselect = $('.custom-multiselect[data-filter-group-id="' + groupId + '"]');
    var checkedBoxes = multiselect.find('input[type="checkbox"]:checked');
    var placeholder = multiselect.find('.placeholder');
    var groupName = getGroupNameById(groupId);
    
    if (checkedBoxes.length === 0) {
      placeholder.text('Επιλογή ' + groupName);
      multiselect.removeClass('has-selections');
    } else if (checkedBoxes.length === 1) {
      var filterName = checkedBoxes.first().siblings('.option-text').text();
      placeholder.text(filterName);
      multiselect.addClass('has-selections');
    } else {
      placeholder.text(checkedBoxes.length + ' selected');
      multiselect.addClass('has-selections');
    }
  }
  
  // Helper function to get group name by ID
  function getGroupNameById(groupId) {
    var groupName = '';
    filterGroupsData.forEach(function(group) {
      if (group.filter_group_id == groupId) {
        groupName = group.name;
      }
    });
    return groupName;
  }
  
  // Helper function to get filter group ID for a filter ID
  function getFilterGroupId(filterId) {
    var groupId = null;
    filterGroupsData.forEach(function(group) {
      group.filter.forEach(function(filter) {
        if (parseInt(filter.filter_id) === filterId) {
          groupId = group.filter_group_id;
        }
      });
    });
    return groupId;
  }
  
  // Helper function to get filter name by ID
  function getFilterName(filterId) {
    var filterName = '';
    filterGroupsData.forEach(function(group) {
      group.filter.forEach(function(filter) {
        if (parseInt(filter.filter_id) === filterId) {
          filterName = filter.name;
        }
      });
    });
    return filterName;
  }
  
  // Helper function to get filter group name by filter ID
  function getFilterGroupName(filterId) {
    var groupName = '';
    filterGroupsData.forEach(function(group) {
      group.filter.forEach(function(filter) {
        if (parseInt(filter.filter_id) === filterId) {
          groupName = group.name;
        }
      });
    });
    return groupName;
  }
  
  // Function to update selected filters display
  function updateSelectedFiltersDisplay() {
    var tagsContainer = $('#selected-filters-tags');
    tagsContainer.empty();
    
    if (selectedFilters.length > 0) {
      selectedFilters.forEach(function(filterId) {
        var filterName = getFilterName(filterId);
        var groupName = getFilterGroupName(filterId);
        
        if (filterName) {
          var tag = $('<span class="filter-tag">' + 
                     '<span class="group-name">' + groupName + ':</span> ' + 
                     '<span class="filter-name">' + filterName + '</span> ' + 
                     '<span class="remove-filter" data-filter-id="' + filterId + '">×</span>' + 
                     '</span>');
          tagsContainer.append(tag);
        }
      });
      $('.selected-filters').show();
    } else {
      $('.selected-filters').hide();
    }
  }

  // Function to load filtered gifts with pagination
  function loadFilteredGifts(filterIds, page) {
    $.ajax({
      url: 'index.php?route=extension/module/gift_finder/filter',
      type: 'post',
      data: {
        filter_ids: filterIds.length > 0 ? filterIds.join(',') : '',
        page: page
      },
      dataType: 'json',
      success: function(json) {
        if (json.success) {
          // Update product items
          $('#gift-items').html(json.product_html);
          
          // Update the pagination - only shows if there are multiple pages
          $('#pagination-container').html(json.pagination_html);
          $('#results-count').html(json.results_text);
          
          // Update URL with multiple filters and page, preserving the route
          if (window.history && window.history.pushState) {
            var newUrl = '{{ pageUrl }}';
            var params = [];
            
            if (json.filter_ids && json.filter_ids.length > 0) {
              params.push('filter_id=' + json.filter_ids.join(','));
            }
            
            if (json.page > 1) {
              params.push('page=' + json.page);
            }
            
            if (params.length > 0) {
              newUrl += (newUrl.indexOf('?') === -1 ? '?' : '&') + params.join('&');
            }
            
            window.history.pushState({ path: newUrl }, '', newUrl);
          }
        } else {
          $('#gift-items').html('<div class="col-sm-12 text-center">' + json.error + '</div>');
        }
      },
      error: function(xhr, ajaxOptions, thrownError) {
        console.error(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
        $('#gift-items').html('<div class="col-sm-12 text-center">An error occurred. Please try again.</div>');
      }
    });
  }

});
