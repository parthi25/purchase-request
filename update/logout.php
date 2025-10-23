<?php
session_start();
session_destroy();
?>
<!DOCTYPE html>
<html>

<head>
    <script>
    // clear client-side filters
    localStorage.removeItem('filter');
    localStorage.removeItem('viewMode');
    localStorage.removeItem('prDashboardFilters');
    // now redirect
    window.location.replace("../index.php");
    </script>
    <noscript>
        <meta http-equiv="refresh" content="0;url=../index.php">
    </noscript>
</head>

<body></body>

</html>