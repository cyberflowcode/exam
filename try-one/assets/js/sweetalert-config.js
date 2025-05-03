// SweetAlert2 Configuration
const Toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
    didOpen: (toast) => {
        toast.addEventListener('mouseenter', Swal.stopTimer)
        toast.addEventListener('mouseleave', Swal.resumeTimer)
    }
});

// Success Alert
function showSuccess(message) {
    Toast.fire({
        icon: 'success',
        title: message
    });
}

// Error Alert
function showError(message) {
    Toast.fire({
        icon: 'error',
        title: message
    });
}

// Confirmation Dialog
function showConfirm(title, text, confirmButtonText = 'Yes', cancelButtonText = 'No') {
    return Swal.fire({
        title: title,
        text: text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#2a2185',
        cancelButtonColor: '#6c757d',
        confirmButtonText: confirmButtonText,
        cancelButtonText: cancelButtonText
    });
}

// Loading Alert
function showLoading(message = 'Please wait...') {
    Swal.fire({
        title: message,
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
}

// Close Loading Alert
function closeLoading() {
    Swal.close();
}