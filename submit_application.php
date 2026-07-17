<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Application Submission Result</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

  <img src="images/logo.png" alt="Portal Logo" class="site-logo" onerror="this.style.display='none'">

  <div class="nav-links">
    <a href="index.html">&larr; Back to Home</a>
  </div>

  <span class="page-kanji">申&#12288;込</span>
  <h1>Application Submission Result</h1>

<?php

  $applicantid  = trim($_POST['applicantid']);
  $title       = trim($_POST['title']);
  $description = trim($_POST['description']);

  if (!$applicantid || !$title) {
      echo "<div class='alert alert-error'>You have not entered all required details (Applicant ID and Title). Please <a href='submit_application.html'>go back</a> and try again.</div>";
      echo "</div></body></html>";
      exit;
  }

  @ $db = new mysqli('localhost', 'root', '', 'applicant_portal');

  if (mysqli_connect_errno()) {
      echo "<div class='alert alert-error'>Error: Could not connect to database. Please try again later.</div>";
      echo "</div></body></html>";
      exit;
  }

  $stmt = $db->prepare("SELECT name FROM applicant WHERE applicantid = ?");
  $stmt->bind_param("i", $applicantid);
  $stmt->execute();
  $stmt->bind_result($name);

  if (!$stmt->fetch()) {
      echo "<div class='alert alert-error'>Applicant ID not found. Please <a href='submit_application.html'>go back</a> and enter a valid ID.</div>";
      $stmt->close();
      $db->close();
      echo "</div></body></html>";
      exit;
  }
  $stmt->close();

  $today = date("Y-m-d");
  $status = "Pending";

  $stmt = $db->prepare("INSERT INTO application (applicantid, title, description, status, submitted_date) VALUES (?, ?, ?, ?, ?)");
  $stmt->bind_param("issss", $applicantid, $title, $description, $status, $today);

  if ($stmt->execute()) {
      $newid = $db->insert_id;
      echo "<div class='alert alert-success'>Application submitted successfully!</div>";
      echo "<div class='card'>";
      echo "<p><strong style='color: var(--ink);'>Application ID:</strong> " . $newid . "</p>";
      echo "<p><strong style='color: var(--ink);'>Title:</strong> " . htmlspecialchars($title) . "</p>";
      echo "<p><strong style='color: var(--ink);'>Status:</strong> <span class='badge badge-pending'>Pending</span></p>";
      echo "<p><strong style='color: var(--ink);'>Submitted:</strong> " . $today . "</p>";
      echo "</div>";
      echo "<p><a href='track_application.php'>&rarr; Track your applications</a></p>";
  } else {
      echo "<div class='alert alert-error'>An error occurred. Your application could not be submitted.</div>";
  }

  $stmt->close();
  $db->close();

?>

</div>

</body>
</html>
