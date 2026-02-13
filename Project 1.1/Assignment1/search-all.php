

<?php
/* ####### Show ALL films for the best-matching actor ##############*/

/* ########### Helper: escape output ######################### */

// I found out the hard way that " " and ' ' are escape character when I had issues with my password ;.;
function h($str) {
  return htmlspecialchars($str ?? "", ENT_QUOTES, "UTF-8");
}

/* ########### Read and sanitize input ######################## */
$first = trim($_GET["firstname"] ?? "");
$last  = trim($_GET["lastname"] ?? "");

/* ########### Page header ################################### */
include("top.html");

/* ########### Database connection ###########################
 * In a larger application, database credentials would be
 * stored in a separate configuration file.
 * Credentials are included here to match course examples.
 */
// References: https://www.w3schools.com/php/php_mysql_connect.asp
$dsn  = "mysql:host=mysqllab.auburn.edu;dbname=sxt0003db;charset=utf8mb4";
$user = "sxt0003";
$pass = "Jaim3Budapest?!";

try {
  $db = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
  ]);

  /* ########### Find best matching actor #################### */
  $actorSql = "
    SELECT id, first_name, last_name
    FROM actors
    WHERE last_name = :last
      AND first_name LIKE CONCAT(:first, '%')
    ORDER BY film_count DESC, id ASC
    LIMIT 1
  ";

  $stmt = $db->prepare($actorSql);
  $stmt->execute([
    ":first" => $first,
    ":last"  => $last
  ]);

  $actor = $stmt->fetch();

  if (!$actor) {
    $typedName = trim($first . " " . $last);
    echo "<h1>Results for " . h($typedName) . "</h1>";
    echo "<p class='msg'>Actor " . h($typedName) . " not found.</p>";
    include("bottom.html");
    exit;
  }

  $actorId = (int)$actor["id"];
  $displayName = $actor["first_name"] . " " . $actor["last_name"];

  echo "<h1>Results for " . h($displayName) . "</h1>";

  /* ########### Get all movies for actor #################### */
  // Used basic inner joins. 
  $moviesSql = "
    SELECT m.name AS title, m.year
    FROM movies m
    JOIN roles r ON r.movie_id = m.id
    WHERE r.actor_id = :actor_id
    ORDER BY m.year DESC, m.name ASC
  ";

  $stmt = $db->prepare($moviesSql);
  $stmt->execute([":actor_id" => $actorId]);
  $movies = $stmt->fetchAll();

  if (!$movies) {
    echo "<p class='msg'>No films found.</p>";
    include("bottom.html");
    exit;
  }

  /* ########### Output results table ######################## */
  echo "<table>";
  echo "<caption>All Films</caption>";
  echo "<thead>";
  echo "<tr><th>#</th><th>Title</th><th>Year</th></tr>";
  echo "</thead>";
  echo "<tbody>";

  $i = 1;
  foreach ($movies as $movie) {
    echo "<tr>";
    echo "<td>" . $i . "</td>";
    echo "<td>" . h($movie["title"]) . "</td>";
    echo "<td>" . h($movie["year"]) . "</td>";
    echo "</tr>";
    $i++;
  }

  echo "</tbody>";
  echo "</table>";

} catch (PDOException $e) {
  echo "<p class='msg error'>Database error.</p>";
}

/* ########### Page footer ################################## */
include("bottom.html");
