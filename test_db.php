<?php
$c = mysqli_connect('localhost', 'root', '', 'gm_hms');
if (!$c) { echo 'DB error'; exit; }
$r = mysqli_query($c, "SELECT COUNT(*) FROM ph_indent_requests WHERE status='ordered'");
echo mysqli_fetch_row($r)[0];
