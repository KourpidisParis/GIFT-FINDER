$(document).ready(function() {
  // Initialize selected filters array from current state
  var selectedFilters = [{{ current_filter_ids|join(',') }}];
  
  // Filter option click
  $('.filter-option').on('click', function() {
    var filterId = $(this).data('filter-id');
    
    if ($(this).hasClass('active')) {
      // Remove filter if already active
      $(this).removeClass('active');
      $(this).find('.checkbox-square').html('');
      
      // Remove from selectedFilters array
      var index = selectedFilters.indexOf(filterId);
      if (index > -1) {
        selectedFilters.splice(index, 1);
      }
    } else {
      // Add filter if not active
      $(this).addClass('active');
      $(this).find('.checkbox-square').html('<i class="fa fa-check"></i>');
      
      // Add to selectedFilters array if not already there
      if (selectedFilters.indexOf(filterId) === -1) {
        selectedFilters.push(filterId);
      }
    }
    
    // Show loading indicator
    $('#gift-items').html('<div class="col-sm-12 text-center"><i class="fa fa-spinner fa-spin fa-3x"></i></div>');
    
    // Send AJAX request for page 1 of the filtered results
    loadFilteredGifts(selectedFilters, 1);
  });
  
  // Reset filter click
  $('.reset-filter').on('click', function() {
    // Remove active class from all filters
    $('.filter-option').removeClass('active');
    
    // Remove all checkmarks when reset is clicked
    $('.checkbox-square').html('');
    
    // Clear selected filters array
    selectedFilters = [];
    
    // Reload the page to show all gifts
    location.href = '{{ pageUrl }}';
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
