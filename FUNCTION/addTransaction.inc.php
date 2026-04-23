<?php
    require_once '../DB/db_connect.php';
    $connect = OpenCon();

    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $user_id = $_SESSION['id'];

    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        if (!empty($_FILES['receipt']['name'])) {
            require_once '../FUNCTION/addTransactionByOCR.inc.php';
            $filePath = $_FILES['receipt']['tmp_name'];
            $input = addTransactionByOCR($filePath);

            if (!empty($input) && isset($input['gemini_extracted'])) {
                // Try to extract the JSON from the gemini_extracted string
                $geminiExtracted = $input['gemini_extracted'];

                // Remove any code block markers and trim
                $geminiExtracted = preg_replace('/^```json\s*/', '', $geminiExtracted);
                $geminiExtracted = preg_replace('/^```/', '', $geminiExtracted);
                $geminiExtracted = preg_replace('/```$/', '', $geminiExtracted);
                $geminiExtracted = trim($geminiExtracted);

                // Try to decode JSON
                $data = json_decode($geminiExtracted, true);

                if (is_array($data)) {
                    $formattedArray = array();
                    $formattedArray2 = array(); // Initialize the array
                    
                    // Validate data structure
                    if (!is_array($data)) {
                        throw new Exception('Invalid data format received');
                    }
                    
                    // Extract TransactionDetails
                    if (isset($data['transaction']) && is_array($data['transaction']) && isset($data['items']) && is_array($data['items'])) {
                        $transactionDetails = $data['transaction'][0]; // Get first transaction
                        
                        // Validate transaction details
                        if (!isset($transactionDetails['Transaction Type']) || 
                            !isset($transactionDetails['Transaction Amount']) || 
                            !isset($transactionDetails['Transaction Name']) || 
                            !isset($transactionDetails['Transaction Date']) || 
                            !isset($transactionDetails['Transaction Description']) || 
                            !isset($transactionDetails['Used OCR'])) {
                            throw new Exception('Missing required transaction details');
                        }
                        
                        $formattedArray = array(
                            $transactionDetails['Transaction Type'],
                            $transactionDetails['Transaction Amount'],
                            $transactionDetails['Transaction Name'],
                            $transactionDetails['Transaction Date'],
                            $transactionDetails['Transaction Description'],
                            $transactionDetails['Used OCR']
                        );

                        $itemArray = $data['items'];
                        
                        // Debug output
                        // echo "<pre>Item Array: ";
                        // print_r($itemArray);
                        // echo "</pre>";
                        
                        foreach ($itemArray as $item) {
                            // Check if the required keys exist and are not empty
                            if (isset($item['Item Name']) && isset($item['Item Price']) && 
                                !empty($item['Item Name']) && !empty($item['Item Price'])) {
                                $formattedArray2[] = array(
                                    $item['Item Name'],
                                    $item['Item Price']
                                );
                            }
                        }

                        if (!empty($_FILES['receipt']['name'])) {
                            $fileName = basename($_FILES['receipt']['name']);
                            $fileExt = pathinfo($fileName, PATHINFO_EXTENSION);
                            $fileType = strtolower($fileExt);
                            $allowTypes = array('jpg', 'jpeg', 'png', 'gif');
                
                            if (in_array($fileType, $allowTypes)) {
                                $image = $_FILES['receipt']['tmp_name'];
                                $imgContent = addslashes(file_get_contents($image));
                
                                $receiptSql = "INSERT INTO transaction_receipt (receipt_image, user_id) VALUES ('$imgContent', '$user_id')";
                                $receiptResult = mysqli_query($connect, $receiptSql);

                                // Print $formattedArray for checking
                                // echo "<pre>formattedArray: ";
                                // print_r($formattedArray);
                                // echo "<br>";
                                // echo "<pre>formattedArray2: ";
                                // print_r($formattedArray2);
                                // echo "<br>";
                                // echo "</pre>";
                            } 
                        }
                    } else {
                        throw new Exception('Invalid transaction data format');
                    }
                } else {
                    echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
                    echo "<script>
                        document.addEventListener('DOMContentLoaded', function() {
                            Swal.fire({
                                title: 'Invalid!',
                                text: 'Failed to extract transaction data.',
                                icon: 'error',
                                confirmButtonColor: '#5f2824',
                                confirmButtonText: 'Try Again',
                                didClose: () => {
                                    window.history.back();
                                }
                            }); 
                        });
                    </script>";
                    exit();
                }
            } else {
                echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
                echo "<script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            title: 'Invalid!',
                            text: 'Failed to extract transaction data.',
                            icon: 'error',
                            confirmButtonColor: '#5f2824',
                            confirmButtonText: 'Try Again',
                            didClose: () => {
                                window.history.back();
                            }
                        }) ;
                    });
                </script>";
                exit();
            }
        } else {
            // No file uploaded: skip OCR logic, allow manual transaction entry or do nothing
            // You can add your manual transaction logic here if needed
        }
    }

    $categorySql = "SELECT * FROM categories WHERE user_id = '$user_id' OR user_id = 0";
    echo "<!-- Debug: SQL Query: " . $categorySql . " -->";
    $categoryResult = mysqli_query($connect, $categorySql);
    
    if (!$categoryResult) {
        $categoryRows = array();
    } else {
        $categoryRows = mysqli_fetch_all($categoryResult, MYSQLI_ASSOC);
    }

    function getExpenseCategory($categoryRows) {
        if (empty($categoryRows)) {
            echo "<option value='' disabled selected>No categories available</option>";
            return;
        }
        
        echo "<option value='' disabled selected>Select Category</option>";
        foreach ($categoryRows as $row) {
            $type = strtolower($row['transactionType']);
            echo "<option value='" . htmlspecialchars($row['category_id']) . "' transaction-type='" . htmlspecialchars($type) . "'>" . htmlspecialchars($row['categoryName']) . "</option>";
        }
    }

?>