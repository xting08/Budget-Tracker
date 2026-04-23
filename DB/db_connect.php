<?php
if (!function_exists('OpenCon')) {
    function OpenCon() {
        $dbhost = "localhost";
        $dbuser = "root";
        $dbpass = "";
        $dbname = "fyp_budget_tracker";

        $connect = mysqli_connect($dbhost, $dbuser, $dbpass, $dbname);

        if(!$connect) {
            die("Connection Failed: " . mysqli_connect_error());
        }
        return $connect;
    }
}

if (!function_exists('CloseCon')) {
    function CloseCon($connect) {
        $connect -> close();
    }
}
?>