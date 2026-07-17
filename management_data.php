<?php require 'management_auth.php'; ?>
<?php

  // Handle EXPORT before any HTML output
  if (isset($_GET['export'])) {

      @ $db = new mysqli('localhost', 'root', '', 'applicant_portal');
      if (mysqli_connect_errno()) {
          die("Error: Could not connect to database.");
      }

      $type = $_GET['export'];

      if ($type === 'applications') {
          header('Content-Type: text/csv');
          header('Content-Disposition: attachment; filename="applications_' . date('Ymd_His') . '.csv"');
          $out = fopen('php://output', 'w');
          fputcsv($out, array('applicationid', 'applicantid', 'applicant_name', 'title', 'description', 'status', 'submitted_date'));
          $result = $db->query(
              "SELECT a.applicationid, a.applicantid, c.name, a.title, a.description, a.status, a.submitted_date
               FROM application a JOIN applicant c ON a.applicantid = c.applicantid
               ORDER BY a.applicationid"
          );
          while ($row = $result->fetch_assoc()) {
              fputcsv($out, $row);
          }
          fclose($out);
          $db->close();
          exit;

      } elseif ($type === 'applicants') {
          header('Content-Type: text/csv');
          header('Content-Disposition: attachment; filename="applicants_' . date('Ymd_His') . '.csv"');
          $out = fopen('php://output', 'w');
          fputcsv($out, array('applicantid', 'name', 'email', 'address', 'city'));
          $result = $db->query("SELECT applicantid, name, email, address, city FROM applicant ORDER BY applicantid");
          while ($row = $result->fetch_assoc()) {
              fputcsv($out, $row);
          }
          fclose($out);
          $db->close();
          exit;

      } elseif ($type === 'notifications') {
          header('Content-Type: text/csv');
          header('Content-Disposition: attachment; filename="notifications_' . date('Ymd_His') . '.csv"');
          $out = fopen('php://output', 'w');
          fputcsv($out, array('notificationid', 'applicantid', 'message', 'sent_date', 'is_read'));
          $result = $db->query("SELECT notificationid, applicantid, message, sent_date, is_read FROM notification ORDER BY notificationid");
          while ($row = $result->fetch_assoc()) {
              fputcsv($out, $row);
          }
          fclose($out);
          $db->close();
          exit;
      }
      $db->close();
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Import / Export Data</title>
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
  <span class="page-kanji">資&#12288;料</span>
  <h1>Import / Export Data</h1>

  <h2>Export</h2>
  <div class="menu-grid">
    <a href="management_data.php?export=applicants" class="menu-card">
      <h3>Applicants CSV</h3>
      <p>Download all applicant accounts.</p>
    </a>
    <a href="management_data.php?export=applications" class="menu-card">
      <h3>Applications CSV</h3>
      <p>Download all submitted applications.</p>
    </a>
    <a href="management_data.php?export=notifications" class="menu-card">
      <h3>Notifications CSV</h3>
      <p>Download notification history.</p>
    </a>
  </div>

  <h2>Import Applications</h2>
  <p style="color: var(--ink-soft); font-size: 0.9rem;">
    CSV columns required: <code style="color: var(--ink);">applicantid, title, description, status, submitted_date</code>.
    Header row required. Status defaults to "Pending" if blank; date defaults to today.
  </p>

  <form action="management_data.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="import" value="applications">
    <label>Select CSV file</label>
    <input type="file" name="csvfile" accept=".csv">
    <input type="submit" value="Import CSV">
  </form>

<?php

  if (isset($_POST['import']) && $_POST['import'] === 'applications'
      && isset($_FILES['csvfile']) && $_FILES['csvfile']['error'] === UPLOAD_ERR_OK) {

      @ $db = new mysqli('localhost', 'root', '', 'applicant_portal');
      if (mysqli_connect_errno()) {
          echo "<div class='alert alert-error'>Could not connect to database.</div>";
          exit;
      }

      $allowed = array('Pending', 'Under Review', 'Approved', 'Rejected', 'Completed');
      $tmpPath = $_FILES['csvfile']['tmp_name'];
      $fh = fopen($tmpPath, 'r');

      if (!$fh) {
          echo "<div class='alert alert-error'>Could not open uploaded file.</div>";
          $db->close();
          exit;
      }

      $header = fgetcsv($fh);
      $imported = 0;
      $skipped  = 0;
      $errors   = array();

      $stmt = $db->prepare(
          "INSERT INTO application (applicantid, title, description, status, submitted_date)
           VALUES (?, ?, ?, ?, ?)"
      );
      $verify = $db->prepare("SELECT applicantid FROM applicant WHERE applicantid = ?");

      $line = 1;
      while (($row = fgetcsv($fh)) !== false) {
          $line++;
          if (count($row) < 2) { $skipped++; continue; }

          $cid    = (int)$row[0];
          $title  = isset($row[1]) ? trim($row[1]) : '';
          $desc   = isset($row[2]) ? trim($row[2]) : '';
          $status = isset($row[3]) ? trim($row[3]) : '';
          $date   = isset($row[4]) ? trim($row[4]) : '';

          if ($title === '') {
              $skipped++;
              $errors[] = "Line " . $line . ": missing title";
              continue;
          }

          $verify->bind_param("i", $cid);
          $verify->execute();
          $verify->store_result();
          if ($verify->num_rows === 0) {
              $skipped++;
              $errors[] = "Line " . $line . ": applicant id " . $cid . " not found";
              $verify->free_result();
              continue;
          }
          $verify->free_result();

          if ($status === '' || !in_array($status, $allowed, true)) {
              $status = 'Pending';
          }
          if ($date === '') {
              $date = date('Y-m-d');
          }

          $stmt->bind_param("issss", $cid, $title, $desc, $status, $date);
          if ($stmt->execute()) {
              $imported++;
          } else {
              $skipped++;
              $errors[] = "Line " . $line . ": insert failed";
          }
      }

      fclose($fh);
      $stmt->close();
      $verify->close();
      $db->close();

      echo "<div class='alert alert-success'>Import complete. Imported: " . $imported . " | Skipped: " . $skipped . "</div>";
      if (!empty($errors)) {
          echo "<div class='card'><h3>Issues</h3><ul style='color: var(--ink-soft); padding-left: 20px;'>";
          foreach ($errors as $e) {
              echo "<li>" . htmlspecialchars($e) . "</li>";
          }
          echo "</ul></div>";
      }
  }
?>

</div>

</body>
</html>
