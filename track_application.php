<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Track My Applications</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

  <img src="images/logo.png" alt="Portal Logo" class="site-logo" onerror="this.style.display='none'">

  <div class="nav-links">
    <a href="index.html">&larr; Back to Home</a>
  </div>

  <span class="page-kanji">確&#12288;認</span>
  <h1>Track / Review / Modify My Applications</h1>

<?php

  function statusBadge($status) {
      $map = array(
          'Pending'      => 'badge-pending',
          'Under Review' => 'badge-review',
          'Approved'     => 'badge-approved',
          'Rejected'     => 'badge-rejected',
          'Completed'    => 'badge-completed',
      );
      $cls = isset($map[$status]) ? $map[$status] : 'badge-pending';
      return "<span class='badge " . $cls . "'>" . htmlspecialchars($status) . "</span>";
  }

  @ $db = new mysqli('localhost', 'root', '', 'applicant_portal');

  if (mysqli_connect_errno()) {
      echo "<div class='alert alert-error'>Error: Could not connect to database. Please try again later.</div>";
      echo "</div></body></html>";
      exit;
  }

  // Handle UPDATE submission
  if (isset($_POST['update']) && isset($_POST['applicationid'])) {

      $applicationid = $_POST['applicationid'];
      $newtitle      = trim($_POST['newtitle']);
      $newdesc       = trim($_POST['newdescription']);

      if (!$newtitle) {
          echo "<div class='alert alert-error'>Title cannot be empty.</div>";
      } else {
          $stmt = $db->prepare("UPDATE application SET title = ?, description = ? WHERE applicationid = ? AND status = 'Pending'");
          $stmt->bind_param("ssi", $newtitle, $newdesc, $applicationid);

          if ($stmt->execute() && $db->affected_rows > 0) {
              echo "<div class='alert alert-success'>Application #" . htmlspecialchars($applicationid) . " updated successfully.</div>";
          } else {
              echo "<div class='alert alert-error'>Update failed. Only Pending applications can be modified.</div>";
          }
          $stmt->close();
      }
  }

  // Look up by Applicant ID
  $applicantid = isset($_POST['applicantid']) ? trim($_POST['applicantid'])
              : (isset($_GET['applicantid'])  ? trim($_GET['applicantid']) : '');

  if (!$applicantid) {
      echo <<<_END
      <form action="track_application.php" method="post">
        <p style="color: var(--ink-soft); margin-bottom: 8px;">Enter your Applicant ID to view your applications:</p>
        <input type="text" name="applicantid" placeholder="Applicant ID" style="max-width: 240px;">
        <br>
        <input type="submit" value="Find My Applications">
      </form>
_END;
      echo "</div></body></html>";
      $db->close();
      exit;
  }

  $stmt = $db->prepare("SELECT name FROM applicant WHERE applicantid = ?");
  $stmt->bind_param("i", $applicantid);
  $stmt->execute();
  $stmt->bind_result($custname);
  if (!$stmt->fetch()) {
      echo "<div class='alert alert-error'>No applicant found with that ID. <a href='track_application.php'>Try again</a>.</div>";
      $stmt->close();
      $db->close();
      echo "</div></body></html>";
      exit;
  }
  $stmt->close();

  echo "<p>Showing applications for: <strong style='color: var(--ink);'>" . htmlspecialchars($custname) . "</strong></p>";

  $stmt = $db->prepare("SELECT applicationid, title, description, status, submitted_date FROM application WHERE applicantid = ? ORDER BY submitted_date DESC");
  $stmt->bind_param("i", $applicantid);
  $stmt->execute();
  $result = $stmt->get_result();
  $num = $result->num_rows;

  echo "<p style='color: var(--ink-soft);'>Total applications: <strong>" . $num . "</strong></p>";

  if ($num === 0) {
      echo "<div class='alert alert-info'>No applications on record. <a href='submit_application.html'>Submit one now</a>.</div>";
      $stmt->close();
      $db->close();
      echo "</div></body></html>";
      exit;
  }

  while ($row = $result->fetch_assoc()) {
      $appid  = $row['applicationid'];
      $title  = htmlspecialchars($row['title']);
      $desc   = htmlspecialchars($row['description']);
      $status = $row['status'];
      $date   = $row['submitted_date'];

      echo "<div class='card'>";
      echo "<h3 style='margin-top:0;'>" . $title . " " . statusBadge($status) . "</h3>";
      echo "<p style='color: var(--ink-soft); font-size: 0.9rem;'>App ID: " . $appid . " &nbsp;|&nbsp; Submitted: " . $date . "</p>";
      echo "<p>" . $desc . "</p>";

      if ($status === 'Pending') {
          echo "<details style='margin-top: 12px;'>";
          echo "<summary style='cursor:pointer; color: var(--ink);'>Edit this application</summary>";
          echo "<form action='track_application.php' method='post' style='margin-top: 12px;'>";
          echo "<input type='hidden' name='applicantid' value='" . htmlspecialchars($applicantid) . "'>";
          echo "<input type='hidden' name='applicationid' value='" . $appid . "'>";
          echo "<input type='hidden' name='update' value='yes'>";
          echo "<label>Title</label>";
          echo "<input type='text' name='newtitle' value=\"" . $title . "\">";
          echo "<label>Description</label>";
          echo "<textarea name='newdescription' rows='4'>" . $desc . "</textarea>";
          echo "<input type='submit' value='Update Application'>";
          echo "</form>";
          echo "</details>";
      } else {
          echo "<p style='color: var(--ink-soft); font-style: italic; font-size: 0.9rem;'>This application can no longer be modified.</p>";
      }
      echo "</div>";
  }

  $stmt->close();
  $db->close();
?>

</div>

</body>
</html>
