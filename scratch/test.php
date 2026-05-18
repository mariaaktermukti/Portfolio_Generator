<?php
$url = "https://www.hackerrank.com/certificates/a5e01c905fa3";
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 3,
        'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36\r\n"
    ]
]);
$html = @file_get_contents($url, false, $context);
if ($html) {
    if (preg_match('/<meta[^>]+(?:property|name)=[\"\'\s]*(?:og:image|twitter:image)[\"\'\s]*content=[\"\'\s]*([^\"\'\s>]+)[\"\'\s]*[^>]*>/i', $html, $matches)) {
        echo "Found OG Image: " . $matches[1] . "\n";
    } else {
        echo "No OG image found. HTML length: " . strlen($html) . "\n";
        file_put_contents('test_out.html', $html);
    }
} else {
    echo "Failed to fetch.\n";
}
