<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notifications & Communications</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

  <img src="images/logo.png" alt="Portal Logo" class="site-logo" onerror="this.style.display='none'">

  <div class="nav-links">
    <a href="index.html">&larr; Back to Home</a>
  </div>

  <span class="page-kanji">通&#12288;知</span>
  <h1>Notifications &amp; Communications</h1>

<?php

  @ $db = new mysqli('localhost', 'root', '', 'applicant_portal');

  if (mysqli_connect_errno()) {
      echo "<div class='alert alert-error'>Error: Could not connect to database. Please try again later.</div>";
      echo "</div></body></html>";
      exit;
  }

  if (isset($_POST['mark_read']) && isset($_POST['notificationid'])) {
      $nid  = $_POST['notificationid'];
      $stmt = $db->prepare("UPDATE notification SET is_read = 1 WHERE notificationid = ?");
      $stmt->bind_param("i", $nid);
      $stmt->execute();
      $stmt->close();
  }

  $applicantid = isset($_POST['applicantid']) ? trim($_POST['applicantid'])
              : (isset($_GET['applicantid'])  ? trim($_GET['applicantid']) : '');

  if (!$applicantid) {
      echo <<<_END
      <form action="notifications.php" method="post">
        <p style="color: var(--ink-soft); margin-bottom: 8px;">Enter your Applicant ID to view your notifications:</p>
        <input type="text" name="applicantid" placeholder="Applicant ID" style="max-width: 240px;">
        <br>
        <input type="submit" value="View My Notifications">
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
      echo "<div class='alert alert-error'>No applicant found with that ID. <a href='notifications.php'>Try again</a>.</div>";
      $stmt->close();
      $db->close();
      echo "</div></body></html>";
      exit;
  }
  $stmt->close();

  echo "<p>Notifications for: <strong style='color: var(--ink);'>" . htmlspecialchars($custname) . "</strong></p>";

  $stmt = $db->prepare("SELECT notificationid, message, sent_date, is_read FROM notification WHERE applicantid = ? ORDER BY sent_date DESC");
  $stmt->bind_param("i", $applicantid);
  $stmt->execute();
  $result = $stmt->get_result();
  $num = $result->num_rows;

  echo "<p style='color: var(--ink-soft);'>Total notifications: <strong>" . $num . "</strong></p>";

  if ($num === 0) {
      echo "<div class='alert alert-info'>You have no notifications at this time.</div>";
      $stmt->close();
      $db->close();
      echo "</div></body></html>";
      exit;
  }

  while ($row = $result->fetch_assoc()) {
      $nid     = $row['notificationid'];
      $msg     = htmlspecialchars($row['message']);
      $date    = $row['sent_date'];
      $is_read = $row['is_read'];
      $badge   = $is_read ? "<span class='badge badge-read'>Read</span>" : "<span class='badge badge-unread'>Unread</span>";

      echo "<div class='card'>";
      echo "<p style='color: var(--ink-soft); font-size: 0.85rem; margin-bottom: 8px;'>" . $date . " &nbsp; " . $badge . "</p>";
      echo "<p>" . $msg . "</p>";

      if (!$is_read) {
          echo "<form action='notifications.php' method='post' style='margin-top: 12px; padding: 0; background: transparent; border: none;'>";
          echo "<input type='hidden' name='applicantid' value='" . htmlspecialchars($applicantid) . "'>";
          echo "<input type='hidden' name='notificationid' value='" . $nid . "'>";
          echo "<input type='hidden' name='mark_read' value='yes'>";
          echo "<input type='submit' value='Mark as Read'>";
          echo "</form>";
      }

      echo "</div>";
  }

  $stmt->close();
  $db->close();
?>

</div>

</body>
</html>
