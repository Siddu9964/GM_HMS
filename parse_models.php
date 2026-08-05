<?php
$json = json_decode(file_get_contents('d:\xampp\htdocs\GM_HMS\models.txt'), true);
if (isset($json['models'])) {
    foreach ($json['models'] as $m) {
        if (in_array('generateContent', $m['supportedGenerationMethods'] ?? [])) {
            echo $m['name'] . "\n";
        }
    }
}
