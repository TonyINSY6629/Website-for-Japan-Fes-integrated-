<?php
  session_start();

  if (!isset($_SESSION['managementid'])) {
      header("Location: management_login.html");
      exit;
  }
?>
