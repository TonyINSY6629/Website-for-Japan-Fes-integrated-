<?php require 'management_auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Review Applications</title>
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
  <span class="page-kanji">審&#12288;査</span>
  <h1>Review / Modify Applications</h1>

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

  if (isset($_POST['update']) && isset($_POST['applicationid'])) {

      $applicationid = $_POST['applicationid'];
      $newtitle      = trim($_POST['newtitle']);
      $newdesc       = trim($_POST['newdescription']);
      $newstatus     = trim($_POST['newstatus']);

      $allowed = array('Pending', 'Under Review', 'Approved', 'Rejected', 'Completed');
      if (!in_array($newstatus, $allowed, true)) {
          echo "<div class='alert alert-error'>Invalid status value.</div>";
      } elseif (!$newtitle) {
          echo "<div class='alert alert-error'>Title cannot be empty.</div>";
      } else {
          $stmt = $db->prepare(
              "UPDATE application SET title = ?, description = ?, status = ? WHERE applicationid = ?"
          );
          $stmt->bind_param("sssi", $newtitle, $newdesc, $newstatus, $applicationid);

          if ($stmt->execute()) {
              echo "<div class='alert alert-success'>Application #" . htmlspecialchars($applicationid) . " updated. New status: " . htmlspecialchars($newstatus) . "</div>";

              $stmt2 = $db->prepare("SELECT applicantid FROM application WHERE applicationid = ?");
              $stmt2->bind_param("i", $applicationid);
              $stmt2->execute();
              $stmt2->bind_result($cid);
              if ($stmt2->fetch()) {
                  $stmt2->close();
                  $msg = "Your application '" . $newtitle . "' status is now: " . $newstatus;
                  $stmt3 = $db->prepare("INSERT INTO notification (applicantid, message) VALUES (?, ?)");
                  $stmt3->bind_param("is", $cid, $msg);
                  $stmt3->execute();
                  $stmt3->close();
              } else {
                  $stmt2->close();
              }
          } else {
              echo "<div class='alert alert-error'>Update failed.</div>";
          }
          $stmt->close();
      }
  }

  $filter = isset($_GET['status']) ? trim($_GET['status']) : '';
  $allowedFilters = array('Pending', 'Under Review', 'Approved', 'Rejected', 'Completed');

  echo "<div class='nav-links'>";
  $allCls = $filter ? '' : 'style="color: var(--accent);"';
  echo "<a href='management_review.php' " . $allCls . ">All</a>";
  foreach ($allowedFilters as $f) {
      $sel = ($f === $filter) ? 'style="color: var(--accent);"' : '';
      echo "<a href='management_review.php?status=" . urlencode($f) . "' " . $sel . ">" . $f . "</a>";
  }
  echo "</div>";

  if ($filter && in_array($filter, $allowedFilters, true)) {
      $stmt = $db->prepare(
          "SELECT a.applicationid, a.title, a.description, a.status, a.submitted_date, c.name, c.applicantid
           FROM application a JOIN applicant c ON a.applicantid = c.applicantid
           WHERE a.status = ? ORDER BY a.submitted_date DESC"
      );
      $stmt->bind_param("s", $filter);
  } else {
      $stmt = $db->prepare(
          "SELECT a.applicationid, a.title, a.description, a.status, a.submitted_date, c.name, c.applicantid
           FROM application a JOIN applicant c ON a.applicantid = c.applicantid
           ORDER BY a.submitted_date DESC"
      );
  }

  $stmt->execute();
  $result = $stmt->get_result();
  $num = $result->num_rows;

  echo "<p style='color: var(--ink-soft);'>Applications found: <strong>" . $num . "</strong></p>";

  if ($num === 0) {
      echo "<div class='alert alert-info'>No applications match.</div>";
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
      $cust   = htmlspecialchars($row['name']);
      $cid    = $row['applicantid'];

      echo "<div class='card'>";
      echo "<h3 style='margin-top: 0;'>" . $title . " " . statusBadge($status) . "</h3>";
      echo "<p style='color: var(--ink-soft); font-size: 0.9rem;'>App ID: " . $appid . " &nbsp;|&nbsp; Applicant: " . $cust . " (ID: " . $cid . ") &nbsp;|&nbsp; Submitted: " . $date . "</p>";
      echo "<p>" . $desc . "</p>";

      echo "<details style='margin-top: 12px;'>";
      echo "<summary style='cursor:pointer; color: var(--ink);'>Edit / change status</summary>";
      echo "<form action='management_review.php' method='post' style='margin-top: 12px;'>";
      echo "<input type='hidden' name='applicationid' value='" . $appid . "'>";
      echo "<input type='hidden' name='update' value='yes'>";
      echo "<label>Title</label>";
      echo "<input type='text' name='newtitle' value=\"" . $title . "\">";
      echo "<label>Description</label>";
      echo "<textarea name='newdescription' rows='3'>" . $desc . "</textarea>";
      echo "<label>Status</label>";
      echo "<select name='newstatus'>";
      foreach ($allowedFilters as $opt) {
          $sel = ($opt === $status) ? "selected" : "";
          echo "<option value='" . $opt . "' " . $sel . ">" . $opt . "</option>";
      }
      echo "</select>";
      echo "<input type='submit' value='Update Application'>";
      echo "</form>";
      echo "</details>";
      echo "</div>";
  }

  $stmt->close();
  $db->close();
?>

</div>

</body>
</html>
