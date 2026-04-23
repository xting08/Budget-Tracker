document.addEventListener('DOMContentLoaded', function() {
    // Clear localStorage when user clicks the "Add Transaction" button
    const addTransactionBtn = document.getElementById('add-transaction-btn');
    const backBtn = document.getElementById('back-btn');

    if (addTransactionBtn) {
        addTransactionBtn.addEventListener('click', function() {
            localStorage.removeItem('receiptImage');
        });
    }
    if (backBtn) {
        backBtn.addEventListener('click', function() {
            localStorage.removeItem('receiptImage');
        });
    }
    
    const receiptInput = document.getElementById('receipt-input');
    const receiptImage = document.getElementById('receipt-image');
    const receiptText = document.getElementById('receipt-image-text');
    const modal = document.getElementById('receipt-image-modal');
    const modalImg = document.getElementById('receipt-image-modal-image');
    const closeBtn = document.getElementById('receipt-image-modal-close');

    // Check if there's a saved receipt in localStorage
    const savedReceipt = localStorage.getItem('receiptImage');
    if (savedReceipt) {
        receiptImage.src = savedReceipt;
        receiptImage.style.display = 'block';
        receiptText.style.display = 'block';
    }

    // Handle file input change
    receiptInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const imageData = e.target.result;
                // Save to localStorage
                localStorage.setItem('receiptImage', imageData);
                // Display the image
                receiptImage.src = imageData;
                receiptImage.style.display = 'block';
                receiptText.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }
    });

    // Modal functionality
    if (receiptImage && modal && modalImg) {
        receiptImage.addEventListener('click', function() {
            modal.style.display = "block";
            modalImg.src = this.src;
        });

        // Close modal when clicking the close button
        if (closeBtn) {
            closeBtn.addEventListener('click', function() {
                modal.style.display = "none";
            });
        }

        // Close modal when clicking outside the image
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                modal.style.display = "none";
            }
        });

        // Close modal when pressing ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === "Escape" && modal.style.display === "block") {
                modal.style.display = "none";
            }
        });
    }

    // Add event listener for transaction type change
    const transactionTypeSelect = document.getElementById('transaction-type');
    if (transactionTypeSelect) {
        transactionTypeSelect.addEventListener('change', filterCategory);
        // Call filterCategory initially to set up the correct state
        filterCategory();
    }
});

// Move filterCategory function outside and make it globally accessible
function filterCategory() {
    const transactionTypeSelect = document.getElementById('transaction-type');
    const categorySelect = document.getElementById('transaction-category');

    if (!transactionTypeSelect || !categorySelect) {
        console.error('Required select elements not found');
        return;
    }

    const selectedType = transactionTypeSelect.value.toLowerCase();
    // Reset to first option
    categorySelect.selectedIndex = 0;

    // Show/hide options based on transaction type
    for (let i = 0; i < categorySelect.options.length; i++) {
        const option = categorySelect.options[i];
        const optionType = option.getAttribute('transaction-type');
        console.log('Option:', option.text, 'Type:', optionType);
        
        if (optionType === selectedType || option.value === "") {
            option.style.display = 'block';
        } else {
            option.style.display = 'none';
        }
    }
}