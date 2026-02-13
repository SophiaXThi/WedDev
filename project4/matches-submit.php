<?php
/*
  matches-submit.php
  Sophia Xuan Thi
  CPSC 5200 — Project 4: Matches submit (PHP)
  Description:
    Show matches for a returning NerdLuv user
    Shows all that is available match wise ie:
        process the given name, 
        reads the singles database, 
        finds compatible matches,
        displays them 
*/

include("top.html");

// If no name was passed, send them back to the form. Had to add this because I accidentally made a profile with no name
// Fun times
if (!isset($_GET["name"]) || trim($_GET["name"]) === "") {
    header("Location: matches.php");
    exit();
}

// Did you know invisible characters exist? Had that happen when I was parsing data once. 10 invisible spaces. NEVER AGAIN. 
$name = trim($_GET["name"]);

// Read all records from singles.txt
$lines = file("singles.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

// Find the current user's record
$currentUser = null;

foreach ($lines as $line) {
    $parts = explode(",", $line);

    // Skip malformed rows or rows with only commas
    if (count($parts) < 7 || trim($parts[0]) === "") {
        continue;
    }

    if (trim($parts[0]) === $name) {
        $currentUser = [
            "name"        => trim($parts[0]),
            "gender"      => trim($parts[1]),
            "age"         => (int) trim($parts[2]),
            "personality" => strtoupper(trim($parts[3])),
            "os"          => trim($parts[4]),
            "min_age"     => (int) trim($parts[5]),
            "max_age"     => (int) trim($parts[6]),
        ];
        break;
    }
}
?>

<h1>Matches for <?= htmlspecialchars($name) ?></h1>

<?php if ($currentUser === null): ?>

    <p>No such user was found.</p>

<?php else: ?>

    <?php
    // Loop again to find matches
    foreach ($lines as $line) {
        $parts = explode(",", $line);

        // Skip malformed / empty records
        if (count($parts) < 7 || trim($parts[0]) === "") {
            continue;
        }

        $personName = trim($parts[0]);

        // Skip the user themself because they aren't Narcissius 
        if ($personName === $currentUser["name"]) {
            continue;
        }

        $person = [
            "name"        => $personName,
            "gender"      => trim($parts[1]),
            "age"         => (int) trim($parts[2]),
            "personality" => strtoupper(trim($parts[3])),
            "os"          => trim($parts[4]),
            "min_age"     => (int) trim($parts[5]),
            "max_age"     => (int) trim($parts[6]),
        ];

        // 1) Opposite gender
        $genderMatch = ($person["gender"] !== $currentUser["gender"]);

        // 2) Mutual age compatibility
        $ageMatch =
            ($person["age"] >= $currentUser["min_age"] &&
             $person["age"] <= $currentUser["max_age"]) &&
            ($currentUser["age"] >= $person["min_age"] &&
             $currentUser["age"] <= $person["max_age"]);

        // 3) Same favorite OS
        $osMatch = ($person["os"] === $currentUser["os"]);

        // 4) At least one matching personality letter in same position
        $hasCommonPersonality = false;
        $p1 = $currentUser["personality"];
        $p2 = $person["personality"];
        $len = min(strlen($p1), strlen($p2));

        for ($i = 0; $i < $len; $i++) {
            if ($p1[$i] === $p2[$i]) {
                $hasCommonPersonality = true;
                break;
            }
        }

        // is a match on gender, age, operating system, and common personality. 
        $isMatch = $genderMatch && $ageMatch && $osMatch && $hasCommonPersonality;

        if (!$isMatch) {
            continue;
        }
        ?>

        <div class="match">
            <p>
                <img src="images/user.jpg" alt="user" />
                <?= htmlspecialchars($person["name"]) ?>
            </p>
            <ul>
                <li><strong>gender:</strong> <?= htmlspecialchars($person["gender"]) ?></li>
                <li><strong>age:</strong> <?= htmlspecialchars((string)$person["age"]) ?></li>
                <li><strong>type:</strong> <?= htmlspecialchars($person["personality"]) ?></li>
                <li><strong>OS:</strong> <?= htmlspecialchars($person["os"]) ?></li>
            </ul>
        </div>

    <?php } // end foreach ?>

<?php endif; ?>

<?php include("bottom.html"); ?>

