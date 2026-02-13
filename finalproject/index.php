<?php
# Read in conversion files with all of the conversion types
function load_conversions_from_xml(string $filePath): array {
    if (!file_exists($filePath)) return [];
    $xml = simplexml_load_file($filePath);
    $meta = [];
    foreach ($xml->conversion as $conv) {
        $id = (string)$conv['id'];
        $meta[$id] = [
            'label'       => (string)$conv->label,
            'description' => (string)$conv->description,
        ];
    }
    return $meta;
}

function parse_pipeline_ids(string $raw, array $conversionMeta): array {
    # Checks if the input string is empty or only whitespace, returns empty array
    if (trim($raw) === "") return [];

    # Splits string by commas, trims whitespace from each ID and removes any empty values
    $ids = array_filter(array_map('trim', explode(',', $raw)));

    # Keeps on IDs that exist as keys in the version metaday the reindexs the array
    return array_values(array_filter($ids, fn($id) => isset($conversionMeta[$id])));
}

# All of the conversions and the functions
# Reference: https://www.w3schools.com/php/php_ref_string.asp since I had to read this over and over again
function apply_single_conversion(string $id, string $text): string {
    switch ($id) {
        case 'hex':
            return bin2hex($text);

        case 'binary':
            if ($text === '') return '';
            $bytes = unpack('C*', $text);
            return implode(' ', array_map(
                fn($b) => str_pad(decbin($b), 8, '0', STR_PAD_LEFT),
                $bytes
            ));

        case 'base64':
            return base64_encode($text);

        case 'urlencode':
            return urlencode($text);

        case 'md5':
            return md5($text);

        case 'sha1':
            return sha1($text);

        case 'sha256':
            return hash('sha256', $text);

        case 'octet':
            if ($text === '') return '';
            $bytes = unpack('C*', $text);
            return implode(' ', $bytes);

        default:
            return $text;
    }
}

# Conversion logic for one input
function apply_pipeline(string $input, array $pipelineIds): string {
    $out = $input;
    foreach ($pipelineIds as $id) {
        $out = apply_single_conversion($id, $out);
    }
    return $out;
}

# Conversion with multiple stpes. 
function build_steps_summary(array $pipelineIds, array $conversionMeta): string {
    if (empty($pipelineIds)) return "Steps run: None";
    $labels = [];
    foreach ($pipelineIds as $id) {
        $labels[] = $conversionMeta[$id]['label'] ?? $id;
    }
    return "Steps run: " . implode(" → ", $labels);
}

# Logging the conversions
function log_conversion(string $logFile,
                        string $input,
                        string $output,
                        array $pipelineIds,
                        array $conversionMeta): void {
    if (empty($pipelineIds)) return; // nothing to log

    # Checks to make sure the log directory exist
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir,0755, true);
    }

    # If writes are not allowed
    if (!is_writable(dirname($logFile))) {  
        return;
    }

    $timestamp = date('Y-m-d H:i:s');
    $labels = [];
    foreach ($pipelineIds as $id) {
        $labels[] = $conversionMeta[$id]['label'] ?? $id;
    }
    $pipelineStr = implode(' -> ', $labels);

    // remove newlines so each log entry is a single line
    $in  = str_replace(["\r", "\n"], ' ', $input);
    $out = str_replace(["\r", "\n"], ' ', $output);

    $line = sprintf('[%s] Input: "%s" | Output: "%s" | Pipeline: %s',
        $timestamp,
        $in,
        $out,
        $pipelineStr
    );

    file_put_contents($logFile, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    # Note: File Write is disabled on Auburn Webhost
}

# Load the available conversions from XML file
# creates an array of the types of conversion and 
# what order to convert based on what order the user puts in  
$conversionMeta = load_conversions_from_xml('conversions.xml');

# Default setting of the pipeline
$pipelineIds   = []; # Ordered list of conversion
$inputText     = ''; # User input box
$outputText    = ''; # Output box
$stepsSummary  = 'Steps run: None';

# Checks if the Encode button was clicked
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawPipeline = $_POST['modes_order'] ?? '';
    $pipelineIds = parse_pipeline_ids($rawPipeline, $conversionMeta);

    $inputText  = $_POST['input_text'] ?? '';
    $outputText = apply_pipeline($inputText, $pipelineIds);
    $stepsSummary = build_steps_summary($pipelineIds, $conversionMeta);

    // write to logs file
    log_conversion('logs/conversion_logs.txt', $inputText, $outputText, $pipelineIds, $conversionMeta);
} 
else {
    $stepsSummary = build_steps_summary($pipelineIds, $conversionMeta);
}
# -------------------------------------------------------
?>
<!DOCTYPE html>
<head>
    <meta charset="UTF-8">
    <title>Enigma String Transformation</title>
    <link rel="icon" href="images/gear-856921_640.png" type="images/png">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <body class ="main-page">

<!-- Top navigation -->
<nav class="top-nav">
    <a href="index.php" class="nav-tab active">Main</a>
    <a href="logs.php" class="nav-tab">Conversion logs</a>
    <a href="guide.php" class="nav-tab">Transformation guide</a>
</nav>

<div class="page-shell">
    <header class="page-header">
        <h1>String Transformation Pipeline</h1>
        <p>Drag modes into the pipeline, order them, then encode your text.</p>
    </header>

    <hr class="main-divider">

    <form method="post" id="pipelineForm">
        <input type="hidden" name="modes_order" id="modesOrder"
               value="<?php echo htmlspecialchars(implode(',', $pipelineIds)); ?>">

        <div class="main-grid">
            <!-- LEFT: transformation modes -->
            <section class="panel modes-panel">
                <h2>Transformation Modes</h2>
                <div class="mode-list">
                    <?php foreach ($conversionMeta as $id => $meta): ?>
                        <div class="mode-item"
                             draggable="true"
                             ondragstart="drag(event)"
                             data-id="<?php echo htmlspecialchars($id, ENT_QUOTES); ?>">
                            <?php echo htmlspecialchars($meta['label']); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- CENTER: pipeline area -->
            <section class="panel pipeline-panel">
                <h2>Pipeline Area</h2>
                <div id="pipelineArea"
                     class="pipeline-drop-zone"
                     ondrop="pipelineDrop(event)"
                     ondragover="allowDrop(event)">
                    <?php if (empty($pipelineIds)): ?>
                        <span id="pipelinePlaceholder" class="pipeline-placeholder-text">
                            Drag modes from the left into this area.<br>
                            Drag items up or down to change the order.
                        </span>
                    <?php else: ?>
                        <?php foreach ($pipelineIds as $index => $id): ?>
                            <div class="pipeline-step"
                                 data-id="<?php echo htmlspecialchars($id, ENT_QUOTES); ?>"
                                 draggable="true"
                                 ondragstart="dragStep(event)">
                                <strong>Step <?php echo $index + 1; ?>:</strong>
                                <?php echo htmlspecialchars($conversionMeta[$id]['label']); ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <!-- RIGHT: input / controls / output -->
            <div class="right-column">
                <section class="panel input-panel">
                    <h2>Input Window</h2>
                    <textarea name="input_text" id="input_text" rows="5"><?php
                        echo htmlspecialchars($inputText);
                    ?></textarea>
                </section>

            <!-- All of the buttons. -->
                <section class="panel controls-panel">
                    <h2>Controls</h2>
                    <div class="controls-buttons">
                        <button type="submit" class="btn-primary">Encode</button>
                        <button type="button" class="btn-secondary" onclick="removeLastStep()">Remove</button>
                        <button type="button" class="btn-secondary" onclick="stopEncoding()">Stop</button>
                        <button type="button" class="btn-secondary" onclick="clearAll()">Clear</button>
                    </div>
                    <p class="controls-steps" id="stepsSummary">
                        <?php echo htmlspecialchars($stepsSummary); ?>
                    </p>
                </section>

                <section class="panel output-panel">
                    <h2>Output Window</h2>
                    <pre class="output-window" id="outputWindow"><?php
                        echo htmlspecialchars($outputText);
                    ?></pre>
                </section>
            </div>
        </div>
    </form>
</div>


<script>
// Javascript functions for the drag and drop feature for the left panel and the pipeline 

// Logic I need: Drag from left, drop into pipeline, transfer data in the pipeline and update the data in the pipeline
// References: https://www.w3schools.com/html/html5_draganddrop.asp and https://www.w3schools.com/js/js_functions.asp
// Reference: https://developer.mozilla.org/en-US/docs/Web/API/HTML_Drag_and_Drop_API    

// labels for each conversion (from PHP)
const conversionLabels = <?php
echo json_encode(
    array_map(fn($m) => $m['label'], $conversionMeta),
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
?>;

function allowDrop(ev) {
    ev.preventDefault();
}

// Drag from LEFT palette 
function drag(ev) {
    const id = ev.target.getAttribute("data-id");
    if (!id) return;
    ev.dataTransfer.setData("text/plain", "mode:" + id);
}

// Drag an existing STEP in pipeline
function dragStep(ev) {
    const steps = Array.from(document.querySelectorAll("#pipelineArea .pipeline-step"));
    const index = steps.indexOf(ev.target);
    if (index === -1) return;
    ev.dataTransfer.setData("text/plain", "step:" + index);
}

// Unified drop handler for pipeline area
function pipelineDrop(ev) {
    ev.preventDefault();

    const data = ev.dataTransfer.getData("text/plain");
    if (!data) return;

    const dropZone = document.getElementById("pipelineArea");
    const steps = Array.from(dropZone.querySelectorAll(".pipeline-step"));
    const targetStep = ev.target.closest(".pipeline-step");
    let insertBeforeIndex = targetStep ? steps.indexOf(targetStep) : steps.length;

    // remove placeholder if present
    const placeholder = document.getElementById("pipelinePlaceholder");
    if (placeholder) placeholder.remove();

    if (data.startsWith("mode:")) {
        // new mode from left panel
        const id = data.substring(5);
        const label = conversionLabels[id] || id;

        const stepDiv = document.createElement("div");
        stepDiv.className = "pipeline-step";
        stepDiv.setAttribute("data-id", id);
        stepDiv.setAttribute("draggable", "true");
        stepDiv.setAttribute("ondragstart", "dragStep(event)");
        stepDiv.innerHTML = "<strong></strong> " + label;

        const beforeNode = steps[insertBeforeIndex] || null;
        dropZone.insertBefore(stepDiv, beforeNode);

    } else if (data.startsWith("step:")) {
        // reorder existing step
        const fromIndex = parseInt(data.substring(5), 10);
        if (isNaN(fromIndex)) return;

        const currentSteps = Array.from(dropZone.querySelectorAll(".pipeline-step"));
        if (fromIndex < 0 || fromIndex >= currentSteps.length) return;

        const moving = currentSteps[fromIndex];

        if (targetStep && moving === targetStep) return;

        if (insertBeforeIndex > fromIndex) {
            insertBeforeIndex--;
        }

        const beforeNode = currentSteps[insertBeforeIndex] || null;
        dropZone.insertBefore(moving, beforeNode);
    }

    updateStepNumbers();
    updateModesOrder();
    updateStepsSummary();
}

function updateStepNumbers() {
    const steps = document.querySelectorAll("#pipelineArea .pipeline-step");
    steps.forEach((step, i) => {
        const id = step.getAttribute("data-id");
        const label = conversionLabels[id] || id;
        step.innerHTML = "<strong>Step " + (i + 1) + ":</strong> " + label;
    });
}

function updateModesOrder() {
    const ids = Array.from(
        document.querySelectorAll("#pipelineArea .pipeline-step")
    ).map(step => step.getAttribute("data-id"));
    document.getElementById("modesOrder").value = ids.join(",");
}

function updateStepsSummary() {
    const steps = Array.from(document.querySelectorAll("#pipelineArea .pipeline-step"));
    const summaryElem = document.getElementById("stepsSummary");
    if (steps.length === 0) {
        summaryElem.textContent = "Steps run: None";
        return;
    }
    const labels = steps.map(step => {
        const id = step.getAttribute("data-id");
        return conversionLabels[id] || id;
    });
    summaryElem.textContent = "Steps run: " + labels.join(" → ");
}

function clearAll() {
    document.getElementById("input_text").value = "";
    document.getElementById("outputWindow").textContent = "";

    const dropZone = document.getElementById("pipelineArea");
    dropZone.innerHTML =
        '<span id="pipelinePlaceholder" class="pipeline-placeholder-text">Drag modes from the left into this area.<br>Drag items up or down to change the order.</span>';

    updateModesOrder();
    updateStepsSummary();
}

// Removes the last element from the list
function removeLastStep() {
    const dropZone = document.getElementById("pipelineArea");
    const steps = dropZone.querySelectorAll(".pipeline-step");
    if (steps.length === 0) return;

    const last = steps[steps.length - 1];
    dropZone.removeChild(last);

    if (dropZone.querySelectorAll(".pipeline-step").length === 0) {
        const placeholder = document.createElement("span");
        placeholder.id = "pipelinePlaceholder";
        placeholder.className = "pipeline-placeholder-text";
        placeholder.innerHTML = "Drag modes from the left into this area.<br>Drag items up or down to change the order.";
        dropZone.appendChild(placeholder);
    }

    updateStepNumbers();
    updateModesOrder();
    updateStepsSummary();
}

function stopEncoding() {
    alert("Stop requested.");
}

document.addEventListener("DOMContentLoaded", function () {
    updateStepNumbers();
    updateModesOrder();
    updateStepsSummary();
});
</script>

</body>
</html>









