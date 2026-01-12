$(document).ready(function() {
    
    // Confirmation dialogs
    $('.delete-confirm').on('click', function(e) {
        if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
            e.preventDefault();
            return false;
        }
    });
    
    // Auto-hide alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
    
    // Form validation
    $('form[data-validate="true"]').on('submit', function(e) {
        var valid = true;
        $(this).find('[required]').each(function() {
            if ($(this).val() === '') {
                $(this).addClass('error');
                valid = false;
            } else {
                $(this).removeClass('error');
            }
        });
        
        if (!valid) {
            e.preventDefault();
            alert('Please fill in all required fields.');
            return false;
        }
    });
    
    // Real-time search
    $('.search-input').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('.searchable').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });
    
    // Date picker fallback for older browsers
    if (!Modernizr.inputtypes.date) {
        $('input[type="date"]').datepicker({
            dateFormat: 'yy-mm-dd'
        });
    }
    
    // Toggle sidebar on mobile
    $('.sidebar-toggle').on('click', function() {
        $('.sidebar').toggleClass('active');
    });
    
    // Tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    // Ajax form submission handler
    $('.ajax-form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        var url = form.attr('action');
        var method = form.attr('method');
        
        $.ajax({
            url: url,
            method: method,
            data: form.serialize(),
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    if (response.redirect) {
                        window.location.href = response.redirect;
                    } else if (response.reload) {
                        location.reload();
                    } else {
                        showAlert('success', response.message);
                    }
                } else {
                    showAlert('danger', response.message);
                }
            },
            error: function() {
                showAlert('danger', 'An error occurred. Please try again.');
            }
        });
    });
    
    // Show alert function
    function showAlert(type, message) {
        var alertHtml = '<div class="alert alert-' + type + ' alert-dismissible" role="alert">' +
                       '<button type="button" class="close" data-dismiss="alert">&times;</button>' +
                       message + '</div>';
        $('.alert-container').html(alertHtml);
        
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
    }
    
    // Character counter
    $('.char-counter').each(function() {
        var maxLength = $(this).attr('maxlength');
        var currentLength = $(this).val().length;
        var counterId = $(this).attr('id') + '-counter';
        
        if ($('#' + counterId).length === 0) {
            $(this).after('<small id="' + counterId + '" class="text-muted">' + currentLength + '/' + maxLength + '</small>');
        }
        
        $(this).on('keyup', function() {
            var length = $(this).val().length;
            $('#' + counterId).text(length + '/' + maxLength);
        });
    });
    
    // Print functionality
    $('.btn-print').on('click', function() {
        window.print();
    });
    
    // Export to CSV
    $('.btn-export-csv').on('click', function() {
        var table = $(this).data('table');
        exportTableToCSV(table);
    });
    
    function exportTableToCSV(tableId) {
        var csv = [];
        var rows = document.querySelectorAll('#' + tableId + ' tr');
        
        for (var i = 0; i < rows.length; i++) {
            var row = [], cols = rows[i].querySelectorAll('td, th');
            
            for (var j = 0; j < cols.length; j++) {
                row.push(cols[j].innerText);
            }
            
            csv.push(row.join(','));
        }
        
        downloadCSV(csv.join('\n'), 'export.csv');
    }
    
    function downloadCSV(csv, filename) {
        var csvFile;
        var downloadLink;
        
        csvFile = new Blob([csv], {type: 'text/csv'});
        downloadLink = document.createElement('a');
        downloadLink.download = filename;
        downloadLink.href = window.URL.createObjectURL(csvFile);
        downloadLink.style.display = 'none';
        document.body.appendChild(downloadLink);
        downloadLink.click();
    }
});
