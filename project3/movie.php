<?php
/*
  movie.php
  Sophia Xuan Thi
  CPSC 5200 — Project 3: Movie Review Part II (PHP)
  Description:
    Generates a Rancid Tomatoes-style movie review page for any film folder
    using ?film=<foldername>. Reads info.txt, overview.txt, overview.png,
    and all review*.txt files dynamically. Uses PHP expression blocks (no echo/print).
*/

/* all of the PHP code 
   Can't say I like the syntax of PHP as it looks so weird to me
   Logic:
   Get the name of the movie, 
   Get the ratings and if it is over 60% then fresh else rotten, 
   Break everything up into blocks
   Thank god for W3 school and Google
*/

/* According to the documentation, _GET is a superglobal variable and creates an associative array */
$movie = $_GET["film"]; 

// movie info from various files. Good thing they have a consistent naming convention 
$infoLines = file("$movie/info.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
// Breaks the data into parts
list($title, $year, $ratingStr) = $infoLines;
$rating = (int) trim($ratingStr);
// if statement
$bigIcon = ($rating >= 60) ? "images/freshbig.png" : "images/rottenbig.png";

// overview.txt → [term, value] pairs (seems similar to Python's dictionary)
$overviewItems = [];
foreach (file("$movie/overview.txt", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
// apparently there is a difference between != and !== that tripped me up
  if (strpos($line, ":") !== false) {
    list($t, $d) = explode(":", $line, 2);
    $overviewItems[] = [trim($t), trim($d)];
  }
}

// reviews stuff 
$reviewFiles = glob("$movie/review*.txt");
natsort($reviewFiles);
$reviewFiles = array_values($reviewFiles);

$reviews = [];
foreach ($reviewFiles as $rf) {
  $lines  = file($rf, FILE_IGNORE_NEW_LINES);
  $text   = isset($lines[0]) ? trim($lines[0]) : "";
  $flag   = isset($lines[1]) ? strtoupper(trim($lines[1])) : "ROTTEN";
  $critic = isset($lines[2]) ? trim($lines[2]) : "";
  $pub    = isset($lines[3]) ? trim($lines[3]) : "";
  
  // If statement to check the flag. 
  $icon   = ($flag === "FRESH") ? "images/fresh.gif" : "images/rotten.gif";
  $reviews[] = compact("text","flag","critic","pub","icon");
}

// Divide the number of reviews into 2 columns
$total = count($reviews);
$leftCount = (int) ceil($total / 2);
$leftReviews  = array_slice($reviews, 0, $leftCount);
$rightReviews = array_slice($reviews, $leftCount);
?>
<!DOCTYPE html>
  <head>
    <title>Rancid Tomatoes</title>
    <link rel="stylesheet" href="movie.css" />
    <link rel="icon" href="images/rotten.gif" type="image/gif" />
  </head>
  <body>
    <div id="banner">
      <img src="images/banner.png" alt="Rancid Tomatoes" />
    </div>

    <!-- The big heading should be the movie title + year -->
    <h1><?= htmlspecialchars($title) ?> (<?= htmlspecialchars($year) ?>)</h1>

    <div id="content">
      <!-- LEFT: rating + reviews -->
      <div id="left">
        <!-- percent -->
        <div id="rating">
          <img src="<?= $bigIcon ?>" alt="Rating" />
          <span class="percent"><?= htmlspecialchars($rating) ?>%</span>
        </div>

        <div class="reviews">
          <div class="column">
            <?php foreach ($leftReviews as $r): ?>
              <p class="review">
                <img src="<?= $r['icon'] ?>" alt="<?= htmlspecialchars($r['flag']) ?>" class="icon" />
                <q><?= htmlspecialchars($r['text']) ?></q>
              </p>
              <p class="critic">
                <img src="images/critic.gif" alt="Critic" class="critic-icon" />
                <?= htmlspecialchars($r['critic']) ?><br />
                <em><?= htmlspecialchars($r['pub']) ?></em>
              </p>
            <?php endforeach; ?>
          </div>

          <div class="column">
            <?php foreach ($rightReviews as $r): ?>
              <p class="review">
                <img src="<?= $r['icon'] ?>" alt="<?= htmlspecialchars($r['flag']) ?>" class="icon" />
                <q><?= htmlspecialchars($r['text']) ?></q>
              </p>
              <p class="critic">
                <img src="images/critic.gif" alt="Critic" class="critic-icon" />
                <?= htmlspecialchars($r['critic']) ?><br />
                <em><?= htmlspecialchars($r['pub']) ?></em>
              </p>
            <?php endforeach; ?>
          </div>

          <div class="clearfix"></div>
        </div>
      </div>

      <!-- RIGHT:  -->
      <div id="overview">
        <img src="<?= $movie ?>/overview.png" alt="Poster" class="poster" />
        <dl>
          <?php foreach ($overviewItems as $pair): ?>
            <dt><?= htmlspecialchars($pair[0]) ?></dt>
            <dd><?= htmlspecialchars($pair[1]) ?></dd>
          <?php endforeach; ?>
        </dl>
      </div>

      <!-- Footer for how many reviews there are. It's easier to have this to make it more dynamic -->
      <div id="bottom-bar">(1–<?= $total ?>) of <?= $total ?></div>
    </div>
  </body>
</html>