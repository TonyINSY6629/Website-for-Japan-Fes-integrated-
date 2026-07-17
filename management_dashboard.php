<?php require 'management_auth.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Management Dashboard</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">

  <img src="images/logo.png" alt="Portal Logo" class="site-logo" onerror="this.style.display='none'">

  <div class="user-bar">
    <div class="user-info">Logged in as: <strong><?php echo htmlspecialchars($_SESSION['fullname']); ?></strong></div>
    <a href="management_logout.php">Logout</a>
  </div>

  <span class="section-tag">Admin Area</span>
  <span class="page-kanji">管&#12288;理</span>
  <h1>Management Dashboard</h1>

  <div class="nav-links">
    <a href="management_review.php">Review Applications</a>
    <a href="management_notify.php">Send Notifications</a>
    <a href="management_process.php">Process Management</a>
    <a href="management_data.php">Import / Export Data</a>
  </div>

<?php

  @ $db = new mysqli('localhost', 'root', '', 'applicant_portal');

  if (mysqli_connect_errno()) {
      echo "<div class='alert alert-error'>Error: Could not connect to database.</div>";
      echo "</div></body></html>";
      exit;
  }

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

  $totalapplicants = $db->query("SELECT COUNT(*) AS c FROM applicant")->fetch_assoc()['c'];
  $totalApps      = $db->query("SELECT COUNT(*) AS c FROM application")->fetch_assoc()['c'];
  $unreadNotifs   = $db->query("SELECT COUNT(*) AS c FROM notification WHERE is_read = 0")->fetch_assoc()['c'];
  $pendingApps    = $db->query("SELECT COUNT(*) AS c FROM application WHERE status = 'Pending'")->fetch_assoc()['c'];
?>

  <div class="stat-grid">
    <div class="stat-box">
      <div class="stat-label">Applicants</div>
      <div class="stat-value"><?php echo $totalapplicants; ?></div>
    </div>
    <div class="stat-box cyan">
      <div class="stat-label">Total Applications</div>
      <div class="stat-value"><?php echo $totalApps; ?></div>
    </div>
    <div class="stat-box amber">
      <div class="stat-label">Pending Review</div>
      <div class="stat-value"><?php echo $pendingApps; ?></div>
    </div>
    <div class="stat-box green">
      <div class="stat-label">Unread Notifications</div>
      <div class="stat-value"><?php echo $unreadNotifs; ?></div>
    </div>
  </div>

  <h2>Applications by Status</h2>
<?php
  $result = $db->query("SELECT status, COUNT(*) AS c FROM application GROUP BY status");
  if ($result->num_rows === 0) {
      echo "<p style='color: var(--ink-soft);'>No applications submitted yet.</p>";
  } else {
      echo "<table><tr><th>Status</th><th>Count</th></tr>";
      while ($row = $result->fetch_assoc()) {
          echo "<tr><td>" . statusBadge($row['status']) . "</td><td>" . $row['c'] . "</td></tr>";
      }
      echo "</table>";
  }
?>

  <h2>10 Most Recent Applications</h2>
<?php
  $stmt = $db->prepare(
      "SELECT a.applicationid, a.title, a.status, a.submitted_date, c.name
       FROM application a JOIN applicant c ON a.applicantid = c.applicantid
       ORDER BY a.submitted_date DESC, a.applicationid DESC LIMIT 10"
  );
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows === 0) {
      echo "<p style='color: var(--ink-soft);'>No applications on record.</p>";
  } else {
      echo "<table>";
      echo "<tr><th>ID</th><th>Applicant</th><th>Title</th><th>Status</th><th>Submitted</th></tr>";
      while ($row = $result->fetch_assoc()) {
          echo "<tr>";
          echo "<td>" . $row['applicationid'] . "</td>";
          echo "<td>" . htmlspecialchars($row['name']) . "</td>";
          echo "<td>" . htmlspecialchars($row['title']) . "</td>";
          echo "<td>" . statusBadge($row['status']) . "</td>";
          echo "<td>" . $row['submitted_date'] . "</td>";
          echo "</tr>";
      }
      echo "</table>";
  }
  $stmt->close();
?>

  <h2>Top 5 Applicants by Submission Count</h2>
<?php
  $result = $db->query(
      "SELECT c.applicantid, c.name, COUNT(a.applicationid) AS total
       FROM applicant c LEFT JOIN application a ON c.applicantid = a.applicantid
       GROUP BY c.applicantid, c.name
       ORDER BY total DESC LIMIT 5"
  );

  echo "<table>";
  echo "<tr><th>Applicant ID</th><th>Name</th><th>Applications</th></tr>";
  while ($row = $result->fetch_assoc()) {
      echo "<tr>";
      echo "<td>" . $row['applicantid'] . "</td>";
      echo "<td>" . htmlspecialchars($row['name']) . "</td>";
      echo "<td>" . $row['total'] . "</td>";
      echo "</tr>";
  }
  echo "</table>";

  $db->close();
?>

</div>

</body>
</html>
