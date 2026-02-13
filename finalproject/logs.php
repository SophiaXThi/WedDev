<?php
# txt file to store the logs
$logFile = 'logs/conversion_logs.txt';

/* Clear logs if requested */
if (isset($_POST['clear_logs'])) {
    file_put_contents($logFile, '');
}

/* CSV Download code */
if (isset($_POST['download_csv'])) {

    if (!file_exists($logFile)) {
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=\"conversion_logs.csv\"");
        $out = fopen("php://output", "w");
        fputcsv($out, ["Timestamp", "Input", "Output", "Pipeline"], ",", '"', "\\");
        fclose($out);
        exit;
    }

    $logLines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=\"conversion_logs.csv\"");

    $out = fopen("php://output", "w");

    // 4 columns with full signature 
    fputcsv($out, ["Timestamp", "Input", "Output", "Pipeline"], ",", '"', "\\");

    // Matches the format written by index.php:
    // [YYYY-MM-DD HH:MM:SS] Input: "..." | Output: "..." | Pipeline: ...
    $pattern = '/^\[(.*?)\]\s+Input:\s+"(.*?)"\s+\|\s+Output:\s+"(.*?)"\s+\|\s+Pipeline:\s+(.*)$/';

    foreach ($logLines as $line) {
        if (preg_match($pattern, $line, $m)) {
            $row = [$m[1], $m[2], $m[3], $m[4]];
        } else {
            // Fallback: whole line in first column
            $row = [$line, "", "", ""];
        }
        fputcsv($out, $row, ",", '"', "\\");
    }

    fclose($out);
    exit;
}

/* Page render: read log file if it exists */
$logLines = [];
if (file_exists($logFile)) {
    $logLines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
}
?>

<!DOCTYPE html>
<head>
    <meta charset="UTF-8">
    <title>Conversion Logs</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="images/gear-856921_640.png" type="images/png">
</head>
<body class="logs-page">

<nav class="top-nav">
    <a href="index.php" class="nav-tab">Main</a>
    <a href="logs.php" class="nav-tab active">Conversion logs</a>
    <a href="guide.php" class="nav-tab">Transformation guide</a>
</nav>

<!-- Vintage background just for logs -->
<div class="vintage-background">
    <div class="page-shell logs-shell">

        <div class="paper-box">
            <h1 class="paper-title">Conversion Logs</h1>

            <p class="paper-intro">
                Logged transformations from this system.
            </p>

            <div class="paper-lines">
    <?php if (empty($logLines)): ?>
        <div class="paper-line">No logs yet.</div>
    <?php else: ?>
        <?php
        # I really need to get better at understanding REGEX. It took me way too long to figure this out
        $pattern = '/^\[(.*?)\]\s+Input:\s+"(.*?)"\s+\|\s+Output:\s+"(.*?)"\s+\|\s+Pipeline:\s+(.*)$/';
        ?>

        <?php foreach ($logLines as $line): ?>
            <?php
            $entry = null;
            if (preg_match($pattern, $line, $m)) {
                $entry = [
                    'timestamp' => $m[1],
                    'input'     => $m[2],
                    'output'    => $m[3],
                    'pipeline'  => $m[4],
                ];
            }
            ?>
            <div class="log-entry">
                <?php if ($entry): ?>
                    <div class="log-header">
                        <span class="log-time">
                            <?php echo htmlspecialchars($entry['timestamp']); ?>
                        </span>
                        <span class="log-pipeline">
                            <?php echo htmlspecialchars($entry['pipeline']); ?>
                        </span>
                    </div>

                    <div class="log-section">
                        <div class="log-label">Input</div>
                        <pre class="log-text">
                            <?php echo htmlspecialchars($entry['input']); ?></pre>
                    </div>

                    <div class="log-section">
                        <div class="log-label">
                            Output (<?php echo strlen($entry['output']); ?> chars)
                        </div>
                        <pre class="log-text log-output">
                            <?php echo htmlspecialchars($entry['output']); ?></pre>
                    </div>

                <?php else: ?>
                    <!-- Fallback if a line doesn't match the pattern -->
                    <pre class="log-text">
                        <?php echo htmlspecialchars($line); ?></pre>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <div class="download-container-bottom">
            <!-- Download CSV button -->
            <form method="post" style="display:inline-block; margin-right:8px;">
                <button type="submit" name="download_csv" class="btn-primary">
                    Download as CSV
                </button>
            </form>

            <!-- Clear logs button -->
            <form method="post" style="display:inline-block;">
                <button type="submit" name="clear_logs" class="btn-primary">
                    Clear Logs
                </button>
            </form>
        </div>

    <?php endif; ?>
</div>

            </div>
        </div>

    </div>
</div>

</body>
</html>


