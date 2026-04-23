<?php
    require_once '../DB/db_connect.php';
    include '../FUNCTION/mainFunc.inc.php';
    $connect = OpenCon();

    $user_id = $_SESSION['id'];

    $categoryQuery = "SELECT * FROM categories WHERE transactionType = 'expense'";
    $categoryResult = mysqli_query($connect, $categoryQuery);
    $categoryArray = array();
    while ($row = mysqli_fetch_assoc($categoryResult)) {
        $categoryArray[] = $row;
    }

    // Get current user's info
    $userQuery = "SELECT * FROM users WHERE user_id = '$user_id'";
    $userResult = mysqli_query($connect, $userQuery);
    $currentUser = mysqli_fetch_assoc($userResult);

    // Get other users
    $otherUsersQuery = "SELECT * FROM users WHERE user_id != '$user_id'";
    $otherUsersResult = mysqli_query($connect, $otherUsersQuery);
    $userArray = array();
    while ($row = mysqli_fetch_assoc($otherUsersResult)) {
        $userArray[] = $row;
    }
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="icon" href="../IMG/favicon.png">
        <title>Cash Compass</title>
        <link rel="stylesheet" href="../CSS/addSharedExpense.css">
        <link rel="stylesheet" href="../CSS/scrollBar.css">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    </head>

    <body>
        <section id="main">
            <span id="main-title">
                <a href="main.php" id="title-text"><i class="fa-solid fa-money-check"></i> &nbsp;Cash Compass</a>
                <a>ADD SHARE EXPENSE</a>
                <form action="../FUNCTION/logoutPost.php" method="post" id="logout-form">
                    <button id="logout-btn" type="submit">
                        <i class="fa-solid fa-right-from-bracket"></i> Logout
                    </button>
                </form>
            </span>
            <hr />    
        </section>
        <nav id="button-nav">
            <button id="back-btn" onclick="window.location.href='../FRONTEND/shareExpense.php'">
                <i class="fa-solid fa-circle-left"></i> &nbsp;Back
            </button>
        </nav>
        <section id="add-shared-expense-container">
            <form action="../FUNCTION/addSharedExpensePost.php" method="post" id="add-shared-expense-form">
                <div class="form-group">
                    <label for="share-amount">Shared Expense Amount</label>
                    <input type="number" name="share-amount" id="share-amount" class="form-item" placeholder="Enter Shared Expense Amount" required>
                    <p>Current Balance: <?php echo getBalance($totalIncome, $totalExpense); ?></p>
                </div>
                <div class="form-group">
                    <label for="shared-name">Shared Expense Name</label>
                    <input type="text" name="shared-name" id="shared-name" class="form-item" placeholder="Enter Shared Expense Name" required>
                </div>
                <div class="form-group">
                    <label for="share-description">Share Expense Description</label>
                    <input type="text" name="share-description" id="share-description" class="form-item" placeholder="Enter Shared Expense Description" required>
                </div>
                <div class="form-group">
                    <label for="share-category">Shared Expense Category</label>
                    <?php if (empty($categoryArray)) { echo "<b>No categories found!</b>"; } ?>
                    <select name="share-category" id="share-category" class="form-item" required>
                        <option value="" disabled selected>Select category</option>
                        <?php
                            foreach ($categoryArray as $category) {
                                echo "<option value='" . $category['category_id'] . "'>" . $category['categoryName'] . "</option>";
                            }
                        ?>
                    </select>
                </div>
                <p style="color: #5f2824; font-size: 0.9em; margin: -1.5em 0 -2.5em 0; padding: 0; text-align: center; width: 100%;">Note: You can add yourself as a recipient to track your own expenses.</p>
                <div class="form-group" id="recipients-group">
                    <label for="recipients[]">Recipients</label>
                    <div id="recipients-container" style="width: 70%;">
                        <div class="recipient-row" style="display: flex; align-items: center; gap: 0.5em; margin-bottom: 0.5em;">
                            <input type="email" name="recipients[]" class="form-item recipient-email-input" placeholder="Enter recipient email" required style="width: 70%;">
                            <span class="email-validation-message" style="color: red; font-size: 0.9em; display: none; margin-left: 0.5em;"></span>
                            <input type="number" name="percentages[]" class="form-item percentage-input" placeholder="%" min="1" max="100" required style="width: 24%;">
                            <button type="button" class="remove-recipient-btn" title="Remove recipient">
                                <i class="fa-solid fa-minus-circle"></i>
                            </button>
                        </div>
                    </div>
                    <button type="button" id="add-recipient-btn">
                        <i class="fa-solid fa-plus"></i> Add Recipient
                    </button>
                </div>
                <p id="percentage-warning" style="display: none;">Total percentage must be exactly 100%.</p>
                <p id="percentage-exceed-warning" style="display: none;">Total percentage cannot exceed 100%.</p>
                <p id="recipient-warning" style="display: none;">You must include at least one other recipient besides yourself.</p>
                <div class="form-group">
                    <button type="submit" name="submit" class="form-item" id="add-shared-expense-btn">Add Shared Expense</button>
                </div>
                <script>
                    // Get current user's email from PHP
                    const currentUserEmail = '<?php echo htmlspecialchars($currentUser['email']); ?>';
                    
                    function getTotalPercentage() {
                        const percentageInputs = document.querySelectorAll('input[name="percentages[]"]');
                        let total = 0;
                        percentageInputs.forEach(input => {
                            total += parseFloat(input.value) || 0;
                        });
                        return total;
                    }

                    function checkRecipientValidation() {
                        const recipientInputs = document.querySelectorAll('input[name="recipients[]"]');
                        const recipientWarning = document.getElementById('recipient-warning');
                        let hasCurrentUser = false;
                        let hasOtherUser = false;
                        
                        recipientInputs.forEach(input => {
                            const email = input.value.trim();
                            if (email === currentUserEmail) {
                                hasCurrentUser = true;
                            } else if (email && email !== currentUserEmail) {
                                hasOtherUser = true;
                            }
                        });
                        
                        // If user includes themselves but no other recipients, show warning
                        if (hasCurrentUser && !hasOtherUser) {
                            recipientWarning.style.display = 'block';
                            return false;
                        } else {
                            recipientWarning.style.display = 'none';
                            return true;
                        }
                    }

                    function updatePercentageWarnings() {
                        const total = getTotalPercentage();
                        const warning = document.getElementById('percentage-warning');
                        const exceedWarning = document.getElementById('percentage-exceed-warning');
                        if (total > 100) {
                            exceedWarning.style.display = 'block';
                            warning.style.display = 'none';
                        } else if (total < 100) {
                            warning.style.display = 'block';
                            exceedWarning.style.display = 'none';
                        } else {
                            warning.style.display = 'none';
                            exceedWarning.style.display = 'none';
                        }
                    }

                    // Add event listeners for percentage inputs and recipient inputs
                    document.getElementById('recipients-container').addEventListener('input', function(e) {
                        if (e.target.name === 'percentages[]') {
                            updatePercentageWarnings();
                        } else if (e.target.name === 'recipients[]') {
                            checkRecipientValidation();
                        }
                    });

                    // Validate form before submission
                    document.getElementById('add-shared-expense-form').addEventListener('submit', function(e) {
                        const total = getTotalPercentage();
                        const recipientValid = checkRecipientValidation();
                        
                        if (total !== 100 || !recipientValid) {
                            e.preventDefault();
                            if (total > 100) {
                                document.getElementById('percentage-exceed-warning').style.display = 'block';
                                document.getElementById('percentage-warning').style.display = 'none';
                            } else if (total < 100) {
                                document.getElementById('percentage-warning').style.display = 'block';
                                document.getElementById('percentage-exceed-warning').style.display = 'none';
                            }
                            
                            if (!recipientValid) {
                                document.getElementById('recipient-warning').style.display = 'block';
                            }
                        }
                    });

                    // Email validation AJAX
                    function validateEmailInput(input) {
                        const email = input.value.trim();
                        const messageSpan = input.parentElement.querySelector('.email-validation-message');
                        if (!email) {
                            messageSpan.style.display = 'none';
                            input.setCustomValidity('');
                            return;
                        }
                        fetch('../FUNCTION/checkInputFunc.inc.php?email=' + encodeURIComponent(email))
                            .then(response => response.json())
                            .then(data => {
                                if (data.exists) {
                                    messageSpan.style.display = 'none';
                                    input.setCustomValidity('');
                                } else {
                                    messageSpan.textContent = 'User not found';
                                    messageSpan.style.display = 'inline';
                                    input.setCustomValidity('User not found');
                                    // Show SweetAlert error
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Recipient Not Found',
                                        text: 'The email ' + email + ' does not exist in the system.',
                                        confirmButtonColor: '#294c4b'
                                    });
                                }
                            })
                            .catch(() => {
                                messageSpan.textContent = 'Error validating email';
                                messageSpan.style.display = 'inline';
                                input.setCustomValidity('Error validating email');
                            });
                    }

                    // Attach validation to all recipient email inputs
                    function attachEmailValidation() {
                        document.querySelectorAll('.recipient-email-input').forEach(input => {
                            input.addEventListener('blur', function() {
                                validateEmailInput(this);
                            });
                            input.addEventListener('input', function() {
                                this.setCustomValidity('');
                                this.parentElement.querySelector('.email-validation-message').style.display = 'none';
                            });
                        });
                    }

                    // Initial attach
                    attachEmailValidation();

                    // Check if add recipient button exists and attach event listener
                    const addRecipientBtn = document.getElementById('add-recipient-btn');
                    if (addRecipientBtn) {
                        console.log('Add recipient button found, attaching event listener'); // Debug log
                        addRecipientBtn.addEventListener('click', function() {
                            console.log('Add recipient button clicked'); // Debug log
                            const total = getTotalPercentage();
                            if (total >= 100) {
                                document.getElementById('percentage-exceed-warning').style.display = 'block';
                                return;
                            }
                            const container = document.getElementById('recipients-container');
                            const row = document.createElement('div');
                            row.className = 'recipient-row';
                            row.style.display = 'flex';
                            row.style.alignItems = 'center';
                            row.style.gap = '0.5em';
                            row.style.marginBottom = '0.5em';

                            // Create email input
                            const emailInput = document.createElement('input');
                            emailInput.type = 'email';
                            emailInput.name = 'recipients[]';
                            emailInput.className = 'form-item recipient-email-input';
                            emailInput.placeholder = 'Enter recipient email';
                            emailInput.required = true;
                            emailInput.style.width = '70%';
                            row.appendChild(emailInput);
                            
                            // Validation message span
                            const messageSpan = document.createElement('span');
                            messageSpan.className = 'email-validation-message';
                            messageSpan.style.color = '#5f2824';
                            messageSpan.style.fontSize = '0.9em';
                            messageSpan.style.display = 'none';
                            messageSpan.style.marginLeft = '0.5em';
                            row.appendChild(messageSpan);

                            // Add percentage input
                            const percentInput = document.createElement('input');
                            percentInput.type = "number";
                            percentInput.name = "percentages[]";
                            percentInput.className = "form-item percentage-input";
                            percentInput.placeholder = "%";
                            percentInput.min = "1";
                            percentInput.max = "100";
                            percentInput.required = true;
                            percentInput.style.width = "24%";
                            row.appendChild(percentInput);

                            // Add remove button
                            const removeBtn = document.createElement('button');
                            removeBtn.type = "button";
                            removeBtn.className = "remove-recipient-btn";
                            removeBtn.title = "Remove recipient";
                            removeBtn.innerHTML = '<i class="fa-solid fa-minus-circle"></i>';
                            row.appendChild(removeBtn);

                            container.appendChild(row);
                            console.log('New recipient row added'); // Debug log
                            updatePercentageWarnings();
                            checkRecipientValidation();
                            attachEmailValidation();
                        });
                    } else {
                        console.error('Add recipient button not found!'); // Debug log
                    }

                    // Remove recipient row
                    document.getElementById('recipients-container').addEventListener('click', function(e) {
                        if (e.target.closest('.remove-recipient-btn')) {
                            const row = e.target.closest('.recipient-row');
                            if (row) {
                                row.remove();
                                updatePercentageWarnings();
                                checkRecipientValidation();
                            }
                        }
                    });

                    // Prevent form submission if any recipient email is invalid
                    const addSharedExpenseForm = document.getElementById('add-shared-expense-form');
                    addSharedExpenseForm.addEventListener('submit', function(e) {
                        let valid = true;
                        document.querySelectorAll('.recipient-email-input').forEach(input => {
                            if (!input.checkValidity()) {
                                valid = false;
                            }
                        });
                        
                        // Check recipient validation (must have at least one other recipient if including self)
                        const recipientValid = checkRecipientValidation();
                        if (!recipientValid) {
                            valid = false;
                        }
                        
                        if (!valid) {
                            e.preventDefault();
                            if (!recipientValid) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Invalid Recipients',
                                    text: 'You must include at least one other recipient besides yourself.',
                                    confirmButtonColor: '#294c4b'
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Invalid Recipient(s)',
                                    text: 'Please ensure all recipient emails are valid and exist in the system.',
                                    confirmButtonColor: '#294c4b'
                                });
                            }
                        }
                    });
                </script>
            </form>
        </section>
    </body>
</html>