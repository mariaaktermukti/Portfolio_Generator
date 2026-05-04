<?php
session_start();
session_unset();    // সমস্ত session variable মুছে ফেলা
session_destroy();  // session ধ্বংস করা
header('Location: login.php');
exit;
?>