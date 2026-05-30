<?php
$file = 'c:\xampp\htdocs\Portfolio_generator\dashboard\projects.php';
$content = file_get_contents($file);
$content = preg_replace('/\\$_POST\[\'([a-zA-Z0-9_]+)\'\]\s*\?\?\s*\'\'/', 'isset($_POST[\'\\1\']) ? $_POST[\'\\1\'] : \'\'', $content);
$content = preg_replace('/\\$edit_data\[\'([a-zA-Z0-9_]+)\'\]\s*\?\?\s*\'\'/', 'isset($edit_data[\'\\1\']) ? $edit_data[\'\\1\'] : \'\'', $content);
file_put_contents($file, $content);
echo "Fixed projects.php";
