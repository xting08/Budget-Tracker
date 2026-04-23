// Remove the import statements and use CDN instead
document.addEventListener('DOMContentLoaded', function() {
    const deleteForm = document.getElementById('delete-transaction-form');

    if (deleteForm) {
        deleteForm.addEventListener('submit', function(e) {
            e.preventDefault();
            Swal.fire({
                title: "Are you sure?",
                text: "You won't be able to revert this!",
                icon: "warning",
                showCancelButton: true,
                iconColor: "#E5B5B2",
                confirmButtonColor: "#5f2824",
                cancelButtonColor: "#4e606f",
                confirmButtonText: "Yes, delete it!"
            }).then((result) => {
                if (result.isConfirmed) {
                    // Submit the form after confirmation
                    deleteForm.submit();
                }
            });
        });
    }
});