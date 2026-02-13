<?php include("top.html"); 
/*
  signup.php
  Sophia Xuan Thi
  CPSC 5200 — Project 4: Find Love Signup (PHP)
  Description:
    Creates a signup window for a new user to find love. 
    Is it better than Hinge/Tinder/Ok Cupid? Who knows?
*/
?>


<h1>New User Signup</h1>

<form action="signup-submit.php" method="post">
    <fieldset>
        <legend>New User Signup:</legend>

        <!-- Name -->
        <strong class="column">Name:</strong>
        <input type="text" name="name" size="16" maxlength="16" required />
        <br />

        <!-- Gender -->
        <strong class="column">Gender:</strong>
        <label>
            <input type="radio" name="gender" value="M" checked />
            Male
        </label>
        <label>
            <input type="radio" name="gender" value="F" />
            Female
        </label>
        <br />

        <!-- Age -->
        <strong class="column">Age:</strong>
        <input type="text" name="age" size="6" maxlength="2" required />
        <br />

        <!-- Personality -->
        <strong class="column">Personality type:</strong>
        <input type="text" name="personality" size="6" maxlength="4" required />
        <a href="https://www.humanmetrics.com/personality" target="_blank">
            (Don't know your type?)
        </a>
        <br />

        <!-- OS -->
        <strong class="column">Favorite OS:</strong>
        <select name="os">
            <option value="Windows">Windows</option>
            <option value="Mac OS X">Mac OS X</option>
            <option value="Linux">Linux</option>
        </select>
        <br />

        <!-- Seeking Age -->
        <strong class="column">Seeking age:</strong>
        <input type="text" name="min_age" size="6" maxlength="2" placeholder="min" required />
        to
        <input type="text" name="max_age" size="6" maxlength="2" placeholder="max" required />
        <br />

        <!-- submit -->
        <input type="submit" value="Sign Up" />
    </fieldset>
</form>

<?php include("bottom.html"); ?>

