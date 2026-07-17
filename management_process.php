<?php require 'management_auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Process Management</title>
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
  <span class="page-kanji">進&#12288;捗</span>
  <h1>Process Management</h1>
  <p style="color: var(--ink-soft);">Move applications through the workflow: Pending &rarr; Under Review &rarr; Approved/Rejected &rarr; Completed.</p>

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
      echo "<div class='alert alert-error'>Error: Could not connect to database.</div>";
      echo "</div></body></html>";
      exit;
  }

  $allowed = array('Pending', 'Under Review', 'Approved', 'Rejected', 'Completed');

  if (isset($_POST['bulk_update']) && isset($_POST['appids']) && isset($_POST['newstatus'])) {

      $newstatus = $_POST['newstatus'];
      $appids    = $_POST['appids'];

      if (!in_array($newstatus, $allowed, true)) {
          echo "<div class='alert alert-error'>Invalid target status.</div>";
      } else {
          $updated = 0;
          $stmt = $db->prepare("UPDATE application SET status = ? WHERE applicationid = ?");
          $notif = $db->prepare(
              "INSERT INTO notification (applicantid, message)
               SELECT applicantid, ? FROM application WHERE applicationid = ?"
          );

          foreach ($appids as $aid) {
              $aid = (int)$aid;
              $stmt->bind_param("si", $newstatus, $aid);
              if ($stmt->execute() && $db->affected_rows > 0) {
                  $updated++;
                  $msg = "Your application #" . $aid . " status is now: " . $newstatus;
                  $notif->bind_param("si", $msg, $aid);
                  $notif->execute();
              }
          }
          $stmt->close();
          $notif->close();

          echo "<div class='alert alert-success'>" . $updated . " application(s) updated to '" . htmlspecialchars($newstatus) . "'. Applicants notified.</div>";
      }
  }

  echo "<h2>Workflow Overview</h2>";
  echo "<div class='stat-grid'>";
  $colors = array('', 'cyan', 'green', '', 'amber');
  $i = 0;
  foreach ($allowed as $s) {
      $stmt = $db->prepare("SELECT COUNT(*) AS c FROM application WHERE status = ?");
      $stmt->bind_param("s", $s);
      $stmt->execute();
      $stmt->bind_result($c);
      $stmt->fetch();
      $stmt->close();
      echo "<div class='stat-box " . ($colors[$i] ?? '') . "'>";
      echo "<div class='stat-label'>" . $s . "</div>";
      echo "<div class='stat-value'>" . $c . "</div>";
      echo "</div>";
      $i++;
  }
  echo "</div>";

  echo "<h2>Bulk Status Update</h2>";

  $sourceFilter = isset($_GET['source']) ? $_GET['source'] : 'Pending';
  if (!in_array($sourceFilter, $allowed, true)) {
      $sourceFilter = 'Pending';
  }

  echo "<div class='nav-links'>";
  echo "<span style='color: var(--ink-soft);'>Show applications with status:</span>";
  foreach ($allowed as $s) {
      $sel = ($s === $sourceFilter) ? 'style="color: var(--accent);"' : '';
      echo "<a href='management_process.php?source=" . urlencode($s) . "' " . $sel . ">" . $s . "</a>";
  }
  echo "</div>";

  $stmt = $db->prepare(
      "SELECT a.applicationid, a.title, a.submitted_date, c.name
       FROM application a JOIN applicant c ON a.applicantid = c.applicantid
       WHERE a.status = ? ORDER BY a.submitted_date ASC"
  );
  $stmt->bind_param("s", $sourceFilter);
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows === 0) {
      echo "<div class='alert alert-info'>No applications with status '" . htmlspecialchars($sourceFilter) . "'.</div>";
  } else {
      echo "<form action='management_process.php' method='post'>";
      echo "<input type='hidden' name='bulk_update' value='yes'>";
      echo "<table>";
      echo "<tr><th>Select</th><th>App ID</th><th>Title</th><th>Applicant</th><th>Submitted</th></tr>";
      while ($row = $result->fetch_assoc()) {
          echo "<tr>";
          echo "<td><input type='checkbox' name='appids[]' value='" . $row['applicationid'] . "'></td>";
          echo "<td>" . $row['applicationid'] . "</td>";
          echo "<td>" . htmlspecialchars($row['title']) . "</td>";
          echo "<td>" . htmlspecialchars($row['name']) . "</td>";
          echo "<td>" . $row['submitted_date'] . "</td>";
          echo "</tr>";
      }
      echo "</table>";

      echo "<label>Move selected to:</label>";
      echo "<select name='newstatus' style='max-width: 240px;'>";
      foreach ($allowed as $opt) {
          echo "<option value='" . $opt . "'>" . $opt . "</option>";
      }
      echo "</select>";
      echo "<input type='submit' value='Apply Bulk Update'>";
      echo "</form>";
  }
  $stmt->close();

  $db->close();
?>

</div>

</body>
</html>
