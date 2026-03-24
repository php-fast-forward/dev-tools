<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8') ?></title>
    <style>
        body {
            margin: 0;
            font-family: Inter, Arial, sans-serif;
            background: #0f172a;
            color: #e2e8f0;
        }
        main {
            max-width: 900px;
            margin: 0 auto;
            padding: 48px 24px;
        }
        h1 {
            font-size: 36px;
            margin-bottom: 24px;
        }
        ul {
            list-style: none;
            padding: 0;
            display: grid;
            gap: 16px;
        }
        a {
            display: block;
            padding: 18px 20px;
            border-radius: 14px;
            background: #1e293b;
            color: #93c5fd;
            text-decoration: none;
            border: 1px solid #334155;
        }
        a:hover {
            background: #334155;
            color: #dbeafe;
        }
    </style>
</head>
<body>
    <main>
        <h1><?= htmlspecialchars((string) $title, ENT_QUOTES, 'UTF-8') ?></h1>
        <ul>
            <?php foreach ($links as $name => $url): ?>
            <li><a href="<?= htmlspecialchars((string) $url, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8') ?></a></li>
            <?php endforeach; ?>
        </ul>
    </main>
</body>
</html>