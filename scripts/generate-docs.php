<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use League\CommonMark\GithubFlavoredMarkdownConverter;

$projectRoot = dirname(__DIR__);
$outputDir = $projectRoot . '/docs/guide';
$version = trim((string) file_get_contents($projectRoot . '/VERSION'));
$releaseNotesPath = $projectRoot . '/docs/release-notes/v' . $version . '.md';

$documents = [
    [
        'source' => $projectRoot . '/README.md',
        'output' => $outputDir . '/overview.html',
        'label' => 'Overview',
    ],
    [
        'source' => $projectRoot . '/INSTALLATION.md',
        'output' => $outputDir . '/installation-guide.html',
        'label' => 'Installation Guide',
    ],
    [
        'source' => $projectRoot . '/UPGRADE.md',
        'output' => $outputDir . '/upgrade-guide.html',
        'label' => 'Upgrade Guide',
    ],
    [
        'source' => resolveChangelogSource($projectRoot, $releaseNotesPath, $version),
        'output' => $outputDir . '/changelog.html',
        'label' => 'Changelog',
    ],
    [
        'source' => $projectRoot . '/docs/plugin-development.md',
        'output' => $outputDir . '/plugin-development.html',
        'label' => 'Plugin Development',
    ],
    [
        'source' => $projectRoot . '/CREDITS.md',
        'output' => $outputDir . '/credits.html',
        'label' => 'Credits',
    ],
];

$converter = new GithubFlavoredMarkdownConverter([
    'html_input' => 'strip',
    'allow_unsafe_links' => false,
]);

if (!is_dir($outputDir) && !mkdir($outputDir, 0777, true) && !is_dir($outputDir)) {
    throw new RuntimeException('Unable to create docs/guide output directory.');
}

foreach ($documents as $document) {
    $markdown = file_get_contents($document['source']);
    if ($markdown === false) {
        throw new RuntimeException('Unable to read ' . $document['source']);
    }

    preg_match('/^#\s+(.+)$/m', $markdown, $matches);
    $title = $matches[1] ?? $document['label'];
    $htmlBody = (string) $converter->convert($markdown);
    $navigation = buildNavigation($documents, basename($document['output']));
    $fullHtml = renderDocument($title, $navigation, $htmlBody);

    if (file_put_contents($document['output'], $fullHtml) === false) {
        throw new RuntimeException('Unable to write ' . $document['output']);
    }
}

$indexHtml = renderDocument(
    'Documentation',
    buildNavigation($documents, ''),
    '<h1>InsulaCRM Documentation</h1><p>Use the links in the sidebar to browse the documentation included with this release.</p>'
);

if (file_put_contents($outputDir . '/index.html', $indexHtml) === false) {
    throw new RuntimeException('Unable to write documentation index.');
}

function resolveChangelogSource(string $projectRoot, string $releaseNotesPath, string $version): string
{
    if (is_file($releaseNotesPath)) {
        return $releaseNotesPath;
    }

    $changelogPath = $projectRoot . '/CHANGELOG.md';
    $markdown = file_get_contents($changelogPath);
    if ($markdown === false) {
        throw new RuntimeException('Unable to read ' . $changelogPath);
    }

    $pattern = '/^##\s+' . preg_quote($version, '/') . '\b.*?(?=^##\s+\S|\z)/ms';
    if (preg_match($pattern, $markdown, $matches) === 1) {
        $content = "# Changelog\n\n" . trim($matches[0]) . "\n";
        $tempPath = $projectRoot . '/storage/framework/cache/data/changelog-' . $version . '.md';
        $dir = dirname($tempPath);
        if (!is_dir($dir) && !mkdir($dir, 0777, true) && !is_dir($dir)) {
            throw new RuntimeException('Unable to create changelog cache directory.');
        }
        if (file_put_contents($tempPath, $content) === false) {
            throw new RuntimeException('Unable to write temporary changelog file.');
        }
        return $tempPath;
    }

    return $changelogPath;
}

function buildNavigation(array $documents, string $activeFile): string
{
    $items = [];

    $items[] = sprintf(
        '<li><a href="%s"%s>%s</a></li>',
        htmlspecialchars('index.html', ENT_QUOTES, 'UTF-8'),
        $activeFile === '' ? ' class="active"' : '',
        htmlspecialchars('Documentation Home', ENT_QUOTES, 'UTF-8')
    );

    foreach ($documents as $document) {
        $fileName = basename($document['output']);
        $items[] = sprintf(
            '<li><a href="%s"%s>%s</a></li>',
            htmlspecialchars($fileName, ENT_QUOTES, 'UTF-8'),
            $activeFile === $fileName ? ' class="active"' : '',
            htmlspecialchars($document['label'], ENT_QUOTES, 'UTF-8')
        );
    }

    return '<ul>' . implode('', $items) . '</ul>';
}

function renderDocument(string $title, string $navigation, string $htmlBody): string
{
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

    return <<<HTML
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{$safeTitle} | InsulaCRM</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f4f1ea;
            --surface: #ffffff;
            --border: #d6cec0;
            --text: #1f1e1a;
            --muted: #6c675d;
            --accent: #0f5b6d;
            --accent-soft: #dff1f5;
            --code-bg: #f7f7f8;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: Georgia, "Times New Roman", serif;
            background: linear-gradient(180deg, #f8f5ef 0%, var(--bg) 100%);
            color: var(--text);
            line-height: 1.65;
        }
        .layout {
            display: grid;
            grid-template-columns: 280px minmax(0, 1fr);
            min-height: 100vh;
        }
        .sidebar {
            background: #12343b;
            color: #f4f4f1;
            padding: 32px 24px;
            position: sticky;
            top: 0;
            align-self: start;
            min-height: 100vh;
        }
        .sidebar h1 {
            font-size: 1.25rem;
            margin: 0 0 8px;
            font-family: "Segoe UI", sans-serif;
        }
        .sidebar p {
            margin: 0 0 24px;
            color: #c9dcde;
            font-size: 0.95rem;
            font-family: "Segoe UI", sans-serif;
        }
        .sidebar ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: grid;
            gap: 10px;
        }
        .sidebar a {
            display: block;
            text-decoration: none;
            color: #eef7f8;
            padding: 10px 12px;
            border-radius: 10px;
            font-family: "Segoe UI", sans-serif;
            transition: background-color 0.15s ease;
        }
        .sidebar a:hover,
        .sidebar a.active {
            background: rgba(255,255,255,0.12);
        }
        .content {
            padding: 40px 48px 56px;
        }
        .article {
            max-width: 980px;
            margin: 0 auto;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 40px 44px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.06);
        }
        h1, h2, h3, h4 {
            line-height: 1.2;
            color: #132026;
        }
        h1 { font-size: 2.2rem; margin-top: 0; }
        h2 {
            font-size: 1.5rem;
            margin-top: 2.4rem;
            padding-top: 0.4rem;
            border-top: 1px solid #ece4d6;
        }
        h3 { font-size: 1.18rem; margin-top: 1.6rem; }
        p, li { font-size: 1rem; }
        a { color: var(--accent); }
        code {
            font-family: Consolas, Monaco, monospace;
            background: var(--code-bg);
            padding: 0.12rem 0.35rem;
            border-radius: 4px;
            font-size: 0.94em;
        }
        pre {
            background: #0f1720;
            color: #f8fafc;
            padding: 16px;
            border-radius: 12px;
            overflow-x: auto;
            font-size: 0.92rem;
        }
        pre code {
            background: transparent;
            padding: 0;
            color: inherit;
        }
        blockquote {
            margin: 1.5rem 0;
            padding: 0.8rem 1rem;
            border-left: 4px solid var(--accent);
            background: var(--accent-soft);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 1.5rem 0;
            font-size: 0.96rem;
        }
        th, td {
            border: 1px solid var(--border);
            padding: 10px 12px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #f5efe2;
            font-family: "Segoe UI", sans-serif;
        }
        hr {
            border: 0;
            border-top: 1px solid var(--border);
            margin: 2rem 0;
        }
        @media (max-width: 960px) {
            .layout { grid-template-columns: 1fr; }
            .sidebar {
                position: static;
                min-height: auto;
            }
            .content {
                padding: 20px;
            }
            .article {
                padding: 24px 20px;
                border-radius: 16px;
            }
        }
    </style>
</head>
<body>
    <div class="layout">
        <aside class="sidebar">
            <h1>InsulaCRM Docs</h1>
            <p>Documentation included with this release.</p>
            {$navigation}
        </aside>
        <main class="content">
            <article class="article markdown-body">
                {$htmlBody}
            </article>
        </main>
    </div>
</body>
</html>
HTML;
}
