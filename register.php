<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Registration Result</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

  <img src="images/logo.png" alt="Portal Logo" class="site-logo" onerror="this.style.display='none'">

  <div class="nav-links">
    <a href="index.html">&larr; Back to Home</a>
  </div>

  <span class="page-kanji">登&#12288;録</span>
  <h1>Registration Result</h1>

<?php

  $name     = trim($_POST['name']);
  $email    = trim($_POST['email']);
  $password = trim($_POST['password']);
  $address  = trim($_POST['address']);
  $city     = trim($_POST['city']);

  if (!$name || !$email || !$password) {
      echo "<div class='alert alert-error'>You have not entered all required details (Name, Email, Password). Please <a href='register.html'>go back</a> and try again.</div>";
      echo "</div></body></html>";
      exit;
  }

  $db = new mysqli('localhost', 'root', '', 'applicant_portal');

  if (mysqli_connect_errno()) {
      echo "<div class='alert alert-error'>Error: Could not connect to database. Please try again later.</div>";
      echo "</div></body></html>";
      exit;
  }

  $stmt = $db->prepare("SELECT applicantid FROM applicant WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $stmt->store_result();

  if ($stmt->num_rows > 0) {
      echo "<div class='alert alert-error'>An account with that email already exists. Please <a href='register.html'>go back</a> and use a different email.</div>";
      $stmt->close();
      $db->close();
      echo "</div></body></html>";
      exit;
  }
  $stmt->close();

  $hashed = password_hash($password, PASSWORD_DEFAULT);

  $stmt = $db->prepare("INSERT INTO applicant (name, email, password, address, city) VALUES (?, ?, ?, ?, ?)");
  $stmt->bind_param("sssss", $name, $email, $hashed, $address, $city);

  if ($stmt->execute()) {
      echo "<div class='alert alert-success'>Registration successful! Welcome, " . htmlspecialchars($name) . ".</div>";
      echo "<div class='card'>";
      echo "<p>Your Applicant ID is: <strong style='color: var(--ink); font-size: 1.3rem;'>" . $db->insert_id . "</strong></p>";
      echo "<p style='color: var(--ink-soft); font-size: 0.9rem;'>Save this ID. You'll need it to submit applications, track status, and view notifications.</p>";
      echo "</div>";
      echo "<p><a href='submit_application.html'>&rarr; Submit your first application</a></p>";
  } else {
      echo "<div class='alert alert-error'>An error occurred. Your registration could not be completed.</div>";
  }

  $stmt->close();
  $db->close();

?>

</div>

</body>
</html>
