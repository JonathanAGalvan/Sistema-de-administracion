<?php
function conectar(){
    $host="srv737.hstgr.io";
    $user="u102381755_sistema";
    $pass="Os5aVGSRL+";
    $bd="u102381755_sistemaInve";

    $con=mysqli_connect($host,$user,$pass);

    mysqli_select_db($con,$bd);

    return $con;
}
?> 