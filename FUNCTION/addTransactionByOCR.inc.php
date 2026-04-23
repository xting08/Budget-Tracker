<?php
function addTransactionByOCR($filePath) {
    if ($_SERVER["REQUEST_METHOD"] === "POST" && !empty($filePath)) {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "http://localhost:5000/upload-receipt-gemini",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => [
                "receipt" => new CURLFile($filePath)
            ]
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        $result = json_decode($response, true);
        return $result; 
    } else {
        echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        Swal.fire({
                            position: 'center',
                            icon: 'warning',
                            title: 'Please Upload a Receipt!',
                            showConfirmButton: false,
                            timer: 1000,
                            timerProgressBar: false,
                            didClose: () => {
                                window.history.back();
                            }
                        });
                    });
                </script>";
    }
}
?>
