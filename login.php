<?php

if (isset($_SERVER['PHP_AUTH_USER']) && !in_array($_SERVER['PHP_AUTH_USER'], ['benaimg'])) {
    echo '403';
    exit;
}
