function uploadFiles(event, url) {
    event.preventDefault();
    var formData = new FormData(event.target);
    var progressBar = $('.progress-bar');
    
    $.ajax({
        url: url,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        xhr: function() {
            var xhr = new window.XMLHttpRequest();
            xhr.upload.addEventListener("progress", updateProgress, false);
            return xhr;
        },
        success: handleUploadSuccess,
        error: handleUploadError
    });
}

function handleUploadError(xhr, status, error) {
    console.error('Upload error:', error);
    alert('An error occurred during upload: ' + error);
}

function handleUploadSuccess(response) {
    try {
        const result = JSON.parse(response);
        if (result.success) {
            alert(result.message);
            window.location.href = 'logbook.php';
        } else {
            alert('Error: ' + result.message);
        }
    } catch (e) {
        console.error('Parse error:', e);
        alert('Error processing response');
    }
}