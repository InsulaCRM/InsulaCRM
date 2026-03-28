<?php

/**
 * Shared-hosting/front-controller fallback.
 *
 * This lets the project root act as the web entrypoint when the host cannot
 * point the document root directly at public/.
 *
 * Requests for static files are served from public/ so subfolder deployments
 * stay stable across redeploys without relying on fragile rewrite chains.
 */
if (PHP_SAPI !== 'cli') {
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

    if ($scriptName !== '' && $scriptName !== '/' && str_starts_with($requestPath, $scriptName)) {
        $requestPath = substr($requestPath, strlen($scriptName));
    } else {
        $scriptDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        if ($scriptDir !== '' && $scriptDir !== '.' && str_starts_with($requestPath, $scriptDir . '/')) {
            $requestPath = substr($requestPath, strlen($scriptDir));
        }
    }

    $requestPath = '/' . ltrim($requestPath, '/');

    if (! str_starts_with($requestPath, '/public/')) {
        $publicFile = realpath(__DIR__ . '/public' . $requestPath);
        $publicRoot = realpath(__DIR__ . '/public');

        if (
            $publicFile !== false
            && $publicRoot !== false
            && str_starts_with($publicFile, $publicRoot . DIRECTORY_SEPARATOR)
            && is_file($publicFile)
        ) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = $finfo ? finfo_file($finfo, $publicFile) : false;

            if ($finfo) {
                finfo_close($finfo);
            }

            if ($mimeType) {
                header('Content-Type: ' . $mimeType);
            }

            header('Content-Length: ' . (string) filesize($publicFile));
            readfile($publicFile);

            return;
        }
    }
}

require __DIR__ . '/public/index.php';
