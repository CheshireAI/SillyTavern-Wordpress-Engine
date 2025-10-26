jQuery(document).ready(function($) {
    
    // Handle file upload UI
    $('input[name="pmv_png_upload[]"]').on('change', function() {
        var fileNames = [];
        if (this.files && this.files.length > 0) {
            for (var i = 0; i < this.files.length; i++) {
                fileNames.push(this.files[i].name);
            }
            $(this).next('.description').html('Selected files: ' + fileNames.join(', '));
        }
    });

    // Show upload feedback
    if (window.location.search.indexOf('pmv_upload=success') > -1) {
        alert('Files uploaded successfully!');
        window.history.replaceState(null, '', window.location.pathname + window.location.search.replace(/[\?&]pmv_upload=success/, ''));
    }
    if (window.location.search.indexOf('pmv_delete=success') > -1) {
        alert('File deleted successfully!');
        window.history.replaceState(null, '', window.location.pathname + window.location.search.replace(/[\?&]pmv_delete=success/, ''));
    }

    // Initialize tab switching
    // Note: We're relying on the server-side tab handling here
    // and will not try to change tab contents with JavaScript
    
    // Handle file upload preview
    $('input[type="file"]').on('change', function() {
        var files = $(this)[0].files;
        var fileList = '';
        
        if (files.length > 0) {
            fileList = '<strong>Selected files:</strong><ul class="file-preview">';
            for (var i = 0; i < files.length; i++) {
                fileList += '<li>' + files[i].name + ' (' + formatFileSize(files[i].size) + ')</li>';
            }
            fileList += '</ul>';
            
            $(this).next('.description').html(fileList);
        }
    });
    
    // Helper function to format file size
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
});
