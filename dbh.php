<?php

$dsn = ['mysql:host=127.0.0.1;dbname=tests', 'root', ''];

$dbh = new PDO(...$dsn);
$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$dbh->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
$dbh->exec('SET NAMES "UTF8"');

return $dbh;
