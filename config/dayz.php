<?php

$uploadSize = strtoupper((string) env('MAX_UPLOAD_SIZE', '100M'));
$value = (int) $uploadSize;
$multiplier = match (substr($uploadSize, -1)) {
    'G' => 1024 * 1024,
    'M' => 1024,
    'K' => 1,
    default => 1,
};

$editorSize = strtoupper((string) env('MAX_EDITOR_SIZE', '10M'));
$editorValue = (int) $editorSize;
$editorMultiplier = match (substr($editorSize, -1)) {
    'G' => 1024 * 1024,
    'M' => 1024,
    'K' => 1,
    default => 1,
};

return [
    'max_upload_size' => $value * $multiplier,
    'max_editor_size' => $editorValue * $editorMultiplier,
];
