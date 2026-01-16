<?php
ini_set('memory_limit', '256M');
$rootDir = __DIR__;
$files = [];

$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($rootDir, RecursiveDirectoryIterator::SKIP_DOTS)
);

foreach ($iterator as $file) {
    if ($file->isFile()) {
        $path = $file->getPathname();
        $relativePath = substr($path, strlen($rootDir) + 1);
        $relativePath = str_replace('\\', '/', $relativePath);

        // Filtros
        if (strpos($relativePath, '.git') === 0)
            continue;
        if (strpos($relativePath, 'uploads/') === 0)
            continue;
        if (strpos($relativePath, 'prepare_push.php') !== false)
            continue;
        if (strpos($relativePath, 'payload') !== false)
            continue; // ignore existing payloads

        $content = file_get_contents($path);

        if (!mb_detect_encoding($content, 'UTF-8', true)) {
            $content = mb_convert_encoding($content, 'UTF-8', 'ISO-8859-1');
        }

        // Skip massive files if necessary, but we want most code.
        // Let's skip only truly huge files > 1MB
        if (strlen($content) > 1000000)
            continue;

        $files[] = [
            'path' => $relativePath,
            'content' => $content
        ];
    }
}

// Chunking by size
$maxChunkSize = 30000; // 30KB limit to be safe for LLM output tokens (approx 7-8k tokens)
$chunks = [];
$currentChunk = [];
$currentChunkSize = 0;

foreach ($files as $file) {
    $fileSize = strlen($file['content']);

    // If a single file is massive (larger than chunk limit), it needs its own chunk
    if ($fileSize > $maxChunkSize) {
        // If current chunk has items, push it first
        if (!empty($currentChunk)) {
            $chunks[] = $currentChunk;
            $currentChunk = [];
            $currentChunkSize = 0;
        }
        // Push the large file as its own chunk
        // Note: If it's REALLY big (e.g. > 100KB), it might still fail the tool call limit.
        // But for now, let's assume files < 1MB can be handled if isolated or we might successfully push them if they aren't drastically over the limit.
        // Actually, for files > maxChunkSize, we might face issues if we don't handle them.
        // Let's just create a chunk for it. 
        $chunks[] = [$file];
        continue;
    }

    if (($currentChunkSize + $fileSize) > $maxChunkSize) {
        $chunks[] = $currentChunk;
        $currentChunk = [];
        $currentChunkSize = 0;
    }

    $currentChunk[] = $file;
    $currentChunkSize += $fileSize;
}

if (!empty($currentChunk)) {
    $chunks[] = $currentChunk;
}

foreach ($chunks as $index => $chunk) {
    file_put_contents("payload_chunk_" . ($index + 1) . ".json", json_encode($chunk));
    echo "Chunk " . ($index + 1) . " generated with " . count($chunk) . " files.\n";
}
?>