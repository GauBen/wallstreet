<?php

require 'login.php';

function standard_deviation(array $a): float
{
    $n = count($a);
    $mean = array_sum($a) / $n;
    $carry = 0.0;
    foreach ($a as $val) {
        $d = floatval($val) - $mean;
        $carry += $d * $d;
    }
    return sqrt($carry / $n);
}

function dump_json()
{
    global $dbh;
    echo json_encode(require 'data.php');
    exit;
}

$fp = fopen('lockfile.txt', 'r');
if (!flock($fp, LOCK_EX)) {
    exit;
}

/** @var PDO */
$dbh = require 'dbh.php';

/** @var array<string, mixed> */
$settings = require 'settings.php';

$lastUpdateQuery = $dbh->prepare(
    'SELECT MAX(recorded_at) AS last_update, MAX(recorded_at) < NOW() - INTERVAL ? SECOND AS should_update
    FROM gat7by_wallstreet_prices'
);
$lastUpdateQuery->execute([$settings['update_interval']]);

['last_update' => $lastUpdate, 'should_update' => $shouldUpdate]
    = $lastUpdateQuery->fetch();
$shouldUpdate = isset($forceCompute) || boolval($shouldUpdate);

if (!$shouldUpdate) {
    dump_json();
    exit;
}
try {
    $prices = $dbh->query(
        'SELECT name, beer_id, price, recorded_at, price_min, price_max
        FROM gat7by_wallstreet_beers b
        LEFT JOIN gat7by_wallstreet_prices p ON p.beer_id = b.id
        WHERE (beer_id, recorded_at) in (
            SELECT beer_id, MAX(recorded_at)
            FROM gat7by_wallstreet_prices
            GROUP BY beer_id
        )
        ORDER BY beer_id'
    )->fetchAll();
    $orders = $dbh->query(
        'SELECT MIN(b.id) AS beer_id, COUNT(s.id) AS count
        FROM gat7by_wallstreet_beers b
        LEFT JOIN (
            SELECT b.id, o.ordered_at
            FROM gat7by_wallstreet_beers b
            LEFT JOIN gat7by_wallstreet_orders o ON o.beer_id = b.id
            WHERE ordered_at > (
                SELECT MAX(recorded_at)
                FROM gat7by_wallstreet_prices
            )
        ) s ON s.id = b.id
        GROUP BY b.id'
    )->fetchAll();
} catch (PDOException $e) {
    var_dump($e);
    exit;
}
$count = array_reduce(array_column($orders, 'count'), function ($a, $b) {
    return intval($a) + intval($b);
}, 0);

$beers = [];

foreach ($prices as $price) {
    $beers[$price['beer_id']] = [
        'price' => floatval($price['price']),
        'delta' => 0.0,
        'price_min' => floatval($price['price_min']),
        'price_max' => floatval($price['price_max']),
        'recorded_at' => $price['recorded_at']
    ];
}

if ($count > 0) {

    $mean = $count / count($beers);
    $deviation = standard_deviation(array_column($orders, 'count'));

    if (abs($deviation) > 1e-3) {

        foreach ($orders as $order) {
            $c = intval($order['count']);
            $beers[$order['beer_id']]['count'] = $c;
            $beers[$order['beer_id']]['delta'] = $settings['price_volatility'] * ($c - $mean) / $deviation;
        }
    }
}

$query = $dbh->prepare(
    'INSERT INTO `gat7by_wallstreet_prices` (`beer_id`, `price`, `recorded_at`) VALUES (?, ?, NOW())'
);

foreach ($beers as $id => $details) {
    $price = min(max($details['price'] + $details['delta'], $details['price_min']), $details['price_max']);
    $price = ceil($price * 20) / 20;
    $query->execute([$id, $price]);
}

isset($forceCompute) || dump_json();

flock($fp, LOCK_UN);
fclose($fp);
