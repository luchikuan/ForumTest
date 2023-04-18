<?php
    exec('whoami', $output);
    $username = isset($output[0]) ? $output[0] : 'unknown';
    echo $username;
?>
