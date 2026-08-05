<?php
$file = 'd:/xampp/htdocs/GM_HMS/laboratory_view/kanban.php';
$content = file_get_contents($file);

// Split at <?php require_once 'includes/lab_foot.php'; ?>
$parts = explode("<?php require_once 'includes/lab_foot.php'; ?>", $content);

if (count($parts) == 2) {
    // Everything after lab_foot is in $parts[1]. We want to move it to before lab_foot.
    $new_content = $parts[0] . $parts[1] . "\n<?php require_once 'includes/lab_foot.php'; ?>\n";
    file_put_contents($file, $new_content);
    echo "Fixed!";
} else {
    echo "Could not split";
}
