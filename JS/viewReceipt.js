document.addEventListener('DOMContentLoaded', function() {
    const receiptImageDetails = document.getElementById('receipt-image-details');
    const modalDetails = document.getElementById('receipt-image-modal-details');
    const modalImgDetails = document.getElementById('receipt-image-modal-image-details');
    const closeBtnDetails = document.getElementById('receipt-image-modal-close-details');

    if (receiptImageDetails && modalDetails && modalImgDetails && closeBtnDetails) {
        receiptImageDetails.addEventListener('click', function() {
            if (receiptImageDetails.src && receiptImageDetails.src !== window.location.href) {
                modalDetails.style.display = "block";
                modalImgDetails.src = receiptImageDetails.src;
            }
        });

        closeBtnDetails.addEventListener('click', function() {
            modalDetails.style.display = "none";
        });

        modalDetails.addEventListener('click', function(e) {
            if (e.target === modalDetails) {
                modalDetails.style.display = "none";
            }
        });

        // Optional: Close modal on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === "Escape" && modalDetails.style.display === "block") {
                modalDetails.style.display = "none";
            }
        });
    }
});