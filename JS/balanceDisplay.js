function checkBalance() {
    const balanceElement = document.getElementById("balance-amount");
    const balance = parseFloat(balanceElement.textContent);

    if (balance < 0) {
        balanceElement.style.color = "#5f2824";  // Using the red color from your color scheme
    } else {
        balanceElement.style.color = "#232640";  // Using the original color
    }
}

// Run the check when the page loads
document.addEventListener('DOMContentLoaded', checkBalance);