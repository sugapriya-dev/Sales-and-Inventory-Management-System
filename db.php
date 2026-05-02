<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);


$conn=mysqli_connect("localhost","root","root","supermarket");

if (!$conn)
    {
        die("Databased Connection Failed".mysqli_connect_error());
    }
 
//echo"Database connected successfully";
 

?>
