<?php
$content = file_get_contents('.htaccess');
echo "Length: " . strlen($content) . "\n";
echo "Encoding attempt: " . mb_detect_encoding($content, 'UTF-8, UTF-16LE, UTF-16BE, ISO-8859-1') . "\n";
echo "Hex dump of first 50 bytes: " . bin2hex(substr($content, 0, 50)) . "\n";
echo "Content:\n";
echo $content;
