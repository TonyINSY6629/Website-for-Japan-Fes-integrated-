<?php require 'management_auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Send Notifications</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

  <img src="images/logo.png" alt="Portal Logo" class="site-logo" onerror="this.style.display='none'">

  <div class="user-bar">
    <div class="user-info"><a href="management_dashboard.php">&larr; Dashboard</a></div>
    <a href="management_logout.php">Logout</a>
  </div>

  <span class="section-tag">Admin Area</span>
  <span class="page-kanji">連&#12288;絡</span>
  <h1>Send Notifications &amp; Communications</h1>

<?php

  @ $db = new mysqli('localhost', 'root', '', 'applicant_portal');

  if (mysqli_connect_errno()) {
      echo "<div class='alert alert-error'>Error: Could not connect to database.</div>";
      echo "</div></body></html>";
      exit;
  }

  if (isset($_POST['send'])) {

      $target  = isset($_POST['target']) ? $_POST['target'] : '';
      $message = trim($_POST['message']);

      if (!$message) {
          echo "<div class='alert alert-error'>Message cannot be empty.</div>";
      } elseif ($target === 'all') {
          $result = $db->query("SELECT applicantid FROM applicant");
          $count = 0;
          $stmt = $db->prepare("INSERT INTO notification (applicantid, message) VALUES (?, ?)");
          while ($row = $result->fetch_assoc()) {
              $cid = $row['applicantid'];
              $stmt->bind_param("is", $cid, $message);
              $stmt->execute();
              $count++;
          }
          $stmt->close();
          echo "<div class='alert alert-success'>Broadcast sent to " . $count . " applicant(s).</div>";

      } elseif ($target === 'one') {
          $cid = trim($_POST['applicantid']);
          if (!$cid) {
              echo "<div class='alert alert-error'>Applicant ID required.</div>";
          } else {
              $stmt = $db->prepare("SELECT name FROM applicant WHERE applicantid = ?");
              $stmt->bind_param("i", $cid);
              $stmt->execute();
              $stmt->bind_result($cname);
              if ($stmt->fetch()) {
                  $stmt->close();
                  $stmt2 = $db->prepare("INSERT INTO notification (applicantid, message) VALUES (?, ?)");
                  $stmt2->bind_param("is", $cid, $message);
                  if ($stmt2->execute()) {
                      echo "<div class='alert alert-success'>Notification sent to " . htmlspecialchars($cname) . ".</div>";
                  } else {
                      echo "<div class='alert alert-error'>Failed to send notification.</div>";
                  }
                  $stmt2->close();
              } else {
                  echo "<div class='alert alert-error'>No applicant found with ID " . htmlspecialchars($cid) . ".</div>";
                  $stmt->close();
              }
          }
      }
  }
?>

<h2>Compose New Notification</h2>

<form action="management_notify.php" method="post">
  <input type="hidden" name="send" value="yes">
  <p style="margin-bottom: 8px;">
    <label><input type="radio" name="target" value="one" checked> Send to one applicant (ID:
    <input type="text" name="applicantid" style="width: 100px; display: inline-block; margin-left: 6px;">)</label>
  </p>
  <p style="margin-bottom: 12px;">
    <label><input type="radio" name="target" value="all"> Broadcast to ALL applicants</label>
  </p>
  <label>Message</label>
  <textarea name="message" rows="5" placeholder="Type your message..."></textarea>
  <input type="submit" value="Send Notification">
</form>

<h2>Recent Notifications Sent</h2>

<?php
  $result = $db->query(
      "SELECT n.notificationid, n.message, n.sent_date, n.is_read, c.name, c.applicantid
       FROM notification n JOIN applicant c ON n.applicantid = c.applicantid
       ORDER BY n.sent_date DESC LIMIT 20"
  );

  if ($result->num_rows === 0) {
      echo "<p style='color: var(--ink-soft);'>No notifications sent yet.</p>";
  } else {
      echo "<table>";
      echo "<tr><th>ID</th><th>Applicant</th><th>Message</th><th>Sent</th><th>Read</th></tr>";
      while ($row = $result->fetch_assoc()) {
          $readBadge = $row['is_read']
              ? "<span class='badge badge-read'>Yes</span>"
              : "<span class='badge badge-unread'>No</span>";
          echo "<tr>";
          echo "<td>" . $row['notificationid'] . "</td>";
          echo "<td>" . htmlspecialchars($row['name']) . " (" . $row['applicantid'] . ")</td>";
          echo "<td>" . htmlspecialchars($row['message']) . "</td>";
          echo "<td>" . $row['sent_date'] . "</td>";
          echo "<td>" . $readBadge . "</td>";
          echo "</tr>";
      }
      echo "</table>";
  }

  $db->close();
?>

</div>

</body>
</html>
