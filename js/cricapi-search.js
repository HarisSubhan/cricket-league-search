jQuery(document).ready(function($) {
    $('#cricapi-search-btn').on('click', function() {
        let query = $('#cricapi-search-input').val().trim();
        if (!query) return;

        $('#cricapi-search-results').html('<p>Loading...</p>');

        $.post(cricapi_ajax.ajax_url, {
            action: 'cricapi_search',
            nonce: cricapi_ajax.nonce,
            query: query
        }, function(response) {
            console.log('API Response:', response);

            if (response.success && Array.isArray(response.data)) {
                let html = '<ul>';
                response.data.forEach(item => {
                    html += `<li>
                        <strong>${item.name}</strong><br>
                        Start: ${item.startDate}<br>
                        End: ${item.endDate}
                    </li>`;
                });
                html += '</ul>';
                $('#cricapi-search-results').html(html);
            } else {
                $('#cricapi-search-results').html('<p>No series found or unexpected response format.</p>');
            }
        });
    });
});
