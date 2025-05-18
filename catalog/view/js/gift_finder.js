$(document).ready(function() {
  // Filter option click
  $('.filter-option').on('click', function() {
    var filterId = $(this).data('filter-id');
    
    // Add active class to clicked filter and remove from others
    $('.filter-option').removeClass('active');
    $(this).addClass('active');
    
    // Show loading indicator
    $('#gift-items').html('<div class="col-sm-12 text-center"><i class="fa fa-spinner fa-spin fa-3x"></i></div>');
    
    // Send AJAX request for page 1 of the filtered results
    loadFilteredGifts(filterId, 1);
  });
  
  // Reset filter click
  $('.reset-filter').on('click', function() {
    // Remove active class from all filters
    $('.filter-option').removeClass('active');
    
    // Reload the page to show all gifts
    //location.href = 'index.php?route=extension/module/gift_finder';
    location.href   = '{{ pageUrl }}';
  });
  
  // Function to handle AJAX pagination clicks
  $(document).on('click', '#pagination-container a', function(e) {
    e.preventDefault();
    
    // Get the active filter if any
    var filterId = $('.filter-option.active').data('filter-id') || 0;
    
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
    loadFilteredGifts(filterId, page);
    
    // Scroll to top of product area
    $('html, body').animate({
      scrollTop: $('#gift-finder').offset().top - 100
    }, 500);
  });
  

  // Function to load filtered gifts with pagination
  function loadFilteredGifts(filterId, page) {
    $.ajax({
      url: 'index.php?route=extension/module/gift_finder/filter',
      type: 'post',
      data: {
        filter_id: filterId,
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
          
          // Update URL with the filter and page, preserving the route
          if (window.history && window.history.pushState) {
            var newUrl = '{{ pageUrl }}';
            
            if (json.filter_id > 0) {
              newUrl += '&filter_id=' + json.filter_id;
            }
            
            if (json.page > 1) {
              newUrl += '&page=' + json.page;
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