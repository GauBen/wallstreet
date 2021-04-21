<?php

$prices = $dbh->query(
    'SELECT beer_id, price, recorded_at
    FROM gat7by_wallstreet_prices
    ORDER BY recorded_at DESC
    LIMIT 4200'
)->fetchAll();

$lastPrices = $dbh->query(
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

$curves = [];

foreach ($prices as $price) {
    if (!isset($curves[$price['beer_id']])) {
        $curves[$price['beer_id']] = [];
    }
    $curves[$price['beer_id']][] = [
        'price' => round(floatval($price['price']), 2),
        'recorded_at' => $price['recorded_at']
    ];
}

foreach ($lastPrices as $i => $lastPrice) {
    $lastPrices[$i]['price'] = round(floatval($lastPrice['price']), 2);
}

return [
    'last_prices' => $lastPrices,
    'curves' => $curves
];
