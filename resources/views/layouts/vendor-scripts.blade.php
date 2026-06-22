
<script src="{{ URL::asset('build/js/bootstrap.bundle.min.js') }}"></script>
<!--plugins-->
<script src="{{ URL::asset('build/js/jquery.min.js') }}"></script>
<!--plugins-->
<script src="{{ URL::asset('build/plugins/perfect-scrollbar/js/perfect-scrollbar.js') }}"></script>
<script src="{{ URL::asset('build/plugins/metismenu/metisMenu.min.js') }}"></script>
<script src="{{ URL::asset('build/plugins/simplebar/js/simplebar.min.js') }}"></script>
<script src="{{ URL::asset('build/js/main.js') }}"></script>
<!--SweetAlert2-->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
// Toast de notificación global
function showToast(icon, title, timer) {
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: icon,
        title: title,
        showConfirmButton: false,
        timer: timer || 3000,
        timerProgressBar: true
    });
}

// Confirmación de eliminación global
function confirmDelete(formId, message) {
    Swal.fire({
        title: '¿Estás seguro?',
        text: message || 'Esta acción no se puede revertir',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Sí, eliminar',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById(formId).submit();
        }
    });
    return false;
}
</script>