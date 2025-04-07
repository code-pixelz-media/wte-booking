jQuery(document).ready(function($) {
    // Only initialize on frontend
    if (typeof booking_ajax !== 'undefined' && !booking_ajax.is_editor) {
        initBookingCalendars();
    }

    // Reinitialize when Elementor editor is refreshed
    $(window).on('elementor/frontend/init', function() {
        if (!booking_ajax.is_editor) {
            initBookingCalendars();
        }
    });
    
    $('#wte__custom-booking-continue').on('click', function(){
        
            
    });
    


    function initBookingCalendars() {
        $('.booking-calendar-container').each(function() {
            const container = $(this);
            const widgetId = container.attr('id');
            const defaultDate = container.data('default-date');
            const bookingSummaryDate = $('.wte__custom-booking-date');
            const calendar = flatpickr(`#${widgetId}`, {
                inline: true,
                static: true,
                enableTime: false,
                altFormat: "F j, Y",
                dateFormat: "Y-m-d",
                minDate: "today",  
                maxDate: new Date().fp_incr(365),  
                defaultDate: defaultDate || null,
                onChange: function(selectedDates, dateStr, instance) {
                    $('#selected-booking-date').val(dateStr);
                    if (selectedDates[0]) {
                        const formattedDate = instance.formatDate(selectedDates[0], "M d, Y");
                        bookingSummaryDate.text(formattedDate);
                    }
                }
            });
            
            container.on('click', '.flatpickr-day', function() {
                const selectedDate = calendar.selectedDates[0];
                if (selectedDate) {
                    const dateStr = calendar.formatDate(selectedDate, 'Y-m-d');
                    loadPackages(dateStr);
                }
            });
            
            if (calendar.selectedDates.length > 0) {
                const initialDate = calendar.selectedDates[0];
                const dateStr = calendar.formatDate(initialDate, 'Y-m-d');
                $('#selected-booking-date').val(dateStr);
                bookingSummaryDate.text(calendar.formatDate(initialDate, "M d, Y"));
                loadPackages(dateStr);
            }
        });
    }

    function loadPackages(date) {
        $.ajax({
            url: booking_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_available_packages',
                date: date,
                nonce: booking_ajax.nonce
            },
            beforeSend: function() {
                $('#packages-list').html('<p>Loading packages...</p>');
            },
            success: function(response) {
                if (response.success) {
                    $('#packages-list').html(response.data);
                } else {
                    $('#packages-list').html('<p>No packages available for this date.</p>');
                }
            },
            error: function() {
                $('#packages-list').html('<p>Error loading packages.</p>');
            }
        });
    }
});