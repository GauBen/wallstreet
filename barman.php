<?php

require 'login.php';

$dbh = require 'dbh.php';

$beers = $dbh->query(
    'SELECT name, beer_id, price, recorded_at, price_min, price_max
    FROM gat7by_wallstreet_prices p
    RIGHT JOIN gat7by_wallstreet_beers b ON p.beer_id = b.id
    WHERE (beer_id, recorded_at) in (
        SELECT beer_id, MAX(recorded_at)
        FROM gat7by_wallstreet_prices
        GROUP BY beer_id
    )
    ORDER BY beer_id'
)->fetchAll();

foreach ($beers as $i => $beer) {
    $beers[$i]['price'] = round($beer['price'], 2);
}

if (isset($_GET['beer_id'])) {
    foreach ($beers as $beer) {
        if ($beer['beer_id'] === $_GET['beer_id']) {
            $query = $dbh->prepare(
                'INSERT INTO gat7by_wallstreet_orders (beer_id, price, ordered_at)
                VALUES (?, ?, NOW())'
            );
            $query->execute([$beer['beer_id'], $beer['price']]);
            header('Location: barman.php');
            exit;
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Barman</title>
    <style>
        body {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            justify-content: stretch;
            height: 100vh;
            margin: 0;
            background: #002f35;
            color: #b68a4e;
            font-family: 'Segoe UI', sans-serif;
        }

        .beer {
            position: relative;
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0.25em;
            font-size: 2em;
            border: 3px double currentColor;
        }

        .beer:active {
            background: #b68a4e;
            color: #002f35;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        a::before {
            content: "";
            position: absolute;
            inset: 0;
        }
    </style>
</head>

<body>
    <?php foreach ($beers as $beer) { ?>
        <div class=beer>
            <a href="?beer_id=<?= $beer['beer_id'] ?>"><span class=name><?= htmlspecialchars($beer['name']) ?></span> <span class=price data-beer-price=<?= $beer['beer_id'] ?>><?= number_format($beer['price'], 2, '.', '') ?> €</span></a>
        </div>
    <?php } ?>
    <script>
        setInterval(() => {
            fetch('compute.php').then(res => res.json()).then(res => {
                for (const beer of res.last_prices) {
                    document.querySelector('*[data-beer-price="' + beer.beer_id + '"]').innerText = beer.price.toFixed(2) + ' €'
                }
            })
        }, 2000)
    </script>
</body>

</html>
