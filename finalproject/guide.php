<?php
# Same logic as from the index to pull in the stuff. Just using more of it
function load_conversions_from_xml(string $filePath): array {
    if (!file_exists($filePath)) return [];

    $xml = simplexml_load_file($filePath);
    $meta = [];

    foreach ($xml->conversion as $conv) {
        $id = (string)$conv['id'];

        $info = [
            'label'       => (string)$conv->label,
            'description' => (string)$conv->description,

            # The differences in what guide.php reads vs index.php
            'details'     => (string)$conv->details,
            'references'  => []
        ];

        # logic to find if there are any entries in the XML
        if (isset($conv->reference)) {
            foreach ($conv->reference as $ref) {
                $info['references'][] = [
                    'href'  => (string)$ref['href'],
                    'label' => trim((string)$ref) ?: (string)$ref['href'],
                ];
            }
        }
        $meta[$id] = $info;
    }
    return $meta;
}

$conversionMeta = load_conversions_from_xml('conversions.xml');
?>
<!DOCTYPE html>
<head>
    <meta charset="UTF-8">
    <title>Transformation Guide</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="images/gear-856921_640.png" type="images/png">
</head>
<body class="guide-page">

<nav class="top-nav">
    <a href="index.php" class="nav-tab">Main</a>
    <a href="logs.php" class="nav-tab">Conversion logs</a>
    <a href="guide.php" class="nav-tab active">Transformation guide</a>
</nav>

<div class="vintage-background">
    <div class="page-shell logs-shell">

        <!-- Use the same paper box but with a guide-specific class -->
        <div class="paper-box guide-box">
            <h1 class="paper-title">Transformation Guide</h1>

            <p class="paper-intro">
                Overview of each transformation mode, what it does, and where to learn more.
            </p>

            <div class="guide-list">
                <?php if (empty($conversionMeta)): ?>
                    <p>No transformation modes found. Check conversions.xml.</p>
                <?php else: ?>
                    <?php foreach ($conversionMeta as $id => $info): ?>
                        <div class="guide-entry">
                            <div class="guide-mode">
                                <?php echo htmlspecialchars($info['label']); ?>
                            </div>

                            <div class="guide-description">
                                <?php echo nl2br(htmlspecialchars($info['description'])); ?>
                            </div>

                            <?php if (!empty(trim($info['details']))): ?>
                                <div class="guide-details">
                                    <?php echo nl2br(htmlspecialchars(trim($info['details']))); ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($info['references'])): ?>
                                <div class="guide-refs">
                                    <span class="guide-refs-label">Reference links:</span>
                                    <ul>
                                        <?php foreach ($info['references'] as $ref): ?>
                                            <li>
                                                <a href="<?php echo htmlspecialchars($ref['href']); ?>" target="_blank">
                                                    <?php echo htmlspecialchars($ref['label']); ?>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php else: ?>
                                <div class="guide-refs">
                                    <span class="guide-refs-label">Reference links:</span>
                                    <span class="guide-refs-none">None provided.</span>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

</body>
</html>


