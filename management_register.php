<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Management Account Setup</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container" style="max-width: 560px;">

  <div class="nav-links">
    <a href="index.html">&larr; Back to Home</a>
  </div>

  <span class="section-tag">Setup Utility</span>
  <span class="page-kanji">設&#12288;定</span>
  <h1>Management Account Setup</h1>
  <div class="alert alert-info" style="font-size: 0.9rem;">Use this page to create management accounts. In production, restrict or remove this file.</div>

<?php
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
      $username = trim($_POST['username']);
      $password = trim($_POST['password']);
      $fullname = trim($_POST['fullname']);

      if (!$username || !$password || !$fullname) {
          echo "<div class='alert alert-error'>All fields are required.</div>";
      } else {
          @ $db = new mysqli('localhost', 'root', '', 'applicant_portal');
          if (mysqli_connect_errno()) {
              echo "<div class='alert alert-error'>Could not connect to database.</div>";
          } else {
              $stmt = $db->prepare("SELECT managementid FROM management WHERE username = ?");
              $stmt->bind_param("s", $username);
              $stmt->execute();
              $stmt->store_result();
              if ($stmt->num_rows > 0) {
                  echo "<div class='alert alert-error'>Username already exists.</div>";
                  $stmt->close();
                  $db->close();
              } else {
                  $stmt->close();
                  $hashed = password_hash($password, PASSWORD_DEFAULT);
                  $stmt = $db->prepare("INSERT INTO management (username, password, fullname) VALUES (?, ?, ?)");
                  $stmt->bind_param("sss", $username, $hashed, $fullname);
                  if ($stmt->execute()) {
                      echo "<div class='alert alert-success'>Management account created. <a href='management_login.html'>Login here</a>.</div>";
                  } else {
                      echo "<div class='alert alert-error'>Failed to create account.</div>";
                  }
                  $stmt->close();
                  $db->close();
              }
          }
      }
  }
?>

<form action="management_register.php" method="post">
  <table>
    <tr><td>Full Name</td><td><input type="text" name="fullname" maxlength="100"></td></tr>
    <tr><td>Username</td><td><input type="text" name="username" maxlength="50"></td></tr>
    <tr><td>Password</td><td><input type="password" name="password" maxlength="100"></td></tr>
    <tr><td colspan="2"><input type="submit" value="Create Account"></td></tr>
  </table>
</form>

</div>

</body>
</html>
