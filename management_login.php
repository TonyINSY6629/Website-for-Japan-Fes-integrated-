<?php
  session_start();

  $username = isset($_POST['username']) ? trim($_POST['username']) : '';
  $password = isset($_POST['password']) ? trim($_POST['password']) : '';

  if (!$username || !$password) {
      $error = "missing";
  } else {
      @ $db = new mysqli('localhost', 'root', '', 'applicant_portal');

      if (mysqli_connect_errno()) {
          $error = "db";
      } else {
          $stmt = $db->prepare("SELECT managementid, password, fullname FROM management WHERE username = ?");
          $stmt->bind_param("s", $username);
          $stmt->execute();
          $stmt->bind_result($mid, $hashed, $fullname);

          if ($stmt->fetch() && password_verify($password, $hashed)) {
              $stmt->close();
              $db->close();

              $_SESSION['managementid'] = $mid;
              $_SESSION['fullname']     = $fullname;

              header("Location: management_dashboard.php");
              exit;
          }

          $stmt->close();
          $db->close();
          $error = "auth";
      }
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Failed</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container" style="max-width: 480px;">

  <div class="nav-links">
    <a href="index.html">&larr; Back to Home</a>
  </div>

  <span class="page-kanji">認&#12288;証</span>
  <h1>Login Failed</h1>

<?php
  if ($error === "missing") {
      echo "<div class='alert alert-error'>Please enter both username and password.</div>";
  } elseif ($error === "db") {
      echo "<div class='alert alert-error'>Could not connect to database.</div>";
  } else {
      echo "<div class='alert alert-error'>Invalid username or password.</div>";
  }
?>

  <p><a href="management_login.html">&rarr; Try again</a></p>

</div>

</body>
</html>
