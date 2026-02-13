<?php
/* #######Show films where the actor appears WITH Kevin Bacon ######################## */

/* ########### Helper: escape output ######################### */
// I found out the hard way that " " and ' ' are escape character when I had issues with my password ;.;
function h($str) {
  return htmlspecialchars($str ?? "", ENT_QUOTES, "UTF-8");
}

/* ########### Render movies table ########################### */
function renderMoviesTable($caption, $movies) {
  if (!$movies) {
    echo "<p class='msg'>No films found.</p>";
    return;
  }

  echo "<table>";
  echo "<caption>" . h($caption) . "</caption>";
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
  // Tested the queries in MySQL first to make sure things were working
  // Also, I have to use SQL statements at work and there is a 128 character limit if you use Databricks
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

  /* ########### Movies shared with Kevin Bacon ############## */
  // Used basic inner joins
  // Aka fetching the bacon. Bringing home the bacon. 
  $moviesSql = "
    SELECT DISTINCT m.name AS title, m.year
    FROM movies m
    JOIN roles r1 ON r1.movie_id = m.id
    JOIN roles r2 ON r2.movie_id = m.id
    JOIN actors kb ON kb.id = r2.actor_id
    WHERE r1.actor_id = :actor_id
      AND kb.first_name = 'Kevin'
      AND kb.last_name  = 'Bacon'
      AND r2.actor_id <> r1.actor_id
    ORDER BY m.year DESC, m.name ASC
  ";

  $stmt = $db->prepare($moviesSql);
  $stmt->execute([":actor_id" => $actorId]);
  $movies = $stmt->fetchAll();

  /* ########### Output results ############################## */
  renderMoviesTable("Movies with Kevin Bacon", $movies);

} catch (PDOException $e) {
  // Made sure the error message was in red so I knew it was working
  echo "<p class='msg error'>Database error.</p>";
}

/* ########### Page footer ################################## */
include("bottom.html");

