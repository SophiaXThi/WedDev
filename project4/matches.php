<?php
/*
  matches.php
  Sophia Xuan Thi
  CPSC 5200 — Project 4: Matches submit (PHP)
  Description:
    Shows the returning user form to view matchesm 
*/

?>

<?php include("top.html"); ?>

// Gets the user's name 
<form action="matches-submit.php" method="get">
    <fieldset>
        <legend>Returning User:</legend>

        <strong class="column">Name:</strong>
        <input type="text" name="name" size="16" maxlength="16" required />
        <br />

        <input type="submit" value="View My Matches" />
    </fieldset>
</form>

<?php include("bottom.html"); ?>

