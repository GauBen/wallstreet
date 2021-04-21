<?php

require 'login.php';

$dbh = require 'dbh.php';

$beers = $dbh->query(
    'SELECT id, name, price_min, price_max
    FROM gat7by_wallstreet_beers
    ORDER BY id'
)->fetchAll();

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

if (isset($_POST['beers'])) {
    $query = $dbh->prepare(
        'UPDATE gat7by_wallstreet_beers SET price_min=?, price_max=? WHERE id=?'
    );
    foreach ($_POST['beers'] as $id => ['price_min' => $price_min, 'price_max' => $price_max]) {
        $query->execute([$price_min, $price_max, $id]);
    }
    header('Location: admin.php');
    exit;
}

if (isset($_POST['prices'])) {
    $query = $dbh->prepare(
        'INSERT INTO `gat7by_wallstreet_prices` (`beer_id`, `price`, `recorded_at`) VALUES (?, ?, NOW())'
    );
    foreach ($_POST['prices'] as $id => $price) {
        $query->execute([$id, $price]);
    }
    header('Location: admin.php');
    exit;
}

if (isset($_GET['maj'])) {
    $forceCompute = true;
    require 'compute.php';
    header('Location: admin.php');
    exit;
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin</title>
    <style>
        body {
            color: #eee;
            background: #000;
        }

        a {
            color: #88f;
        }
    </style>
</head>

<body>
    <form method=post>
        <h1>Mettre à jour les prix</h1>
        <strong>Mettez les bornes à jour avant !</strong>
        <?php foreach ($prices as $price) { ?>
            <p><?= htmlspecialchars($price['name']) ?> : prix <input type=text value="<?= $price['price'] ?>" name="prices[<?= $price['beer_id'] ?>]" data-focused=no></p>
        <?php } ?>
        <strong>Mettez les bornes à jour avant !</strong>
        <p><button type="submit">Sauvegarder</button></p>
    </form>
    <form method=post>
        <h1>Mettre à jour les bornes des prix</h1>
        <?php foreach ($beers as $beer) { ?>
            <p><?= htmlspecialchars($beer['name']) ?> : prix min <input type=text value="<?= $beer['price_min'] ?>" name="beers[<?= $beer['id'] ?>][price_min]" data-focused=no>, prix max <input type=text value="<?= $beer['price_max'] ?>" name="beers[<?= $beer['id'] ?>][price_max]" data-focused=no></p>
        <?php } ?>
        <p><button type="submit">Sauvegarder</button></p>
    </form>
    <h2><a href="?maj">Forcer le calcul des prix</a></h2>
    <script>
        document.querySelectorAll('input').forEach($el => {
            $el.addEventListener('focus', (e) => {
                $el.dataset.focused = 'yes'
            })
        })
        setInterval(() => {
            fetch('compute.php').then(res => res.json()).then(res => {
                for (const beer of res.last_prices) {
                    const $priceMin = document.querySelector('[name="beers[' + beer.beer_id + '][price_min]"]')
                    if ($priceMin.dataset.focused === 'no') {
                        $priceMin.value = beer.price_min
                    }
                    const $priceMax = document.querySelector('[name="beers[' + beer.beer_id + '][price_max]"]')
                    if ($priceMax.dataset.focused === 'no') {
                        $priceMax.value = beer.price_max
                    }
                    const $price = document.querySelector('[name="prices[' + beer.beer_id + ']"]')
                    if ($price.dataset.focused === 'no') {
                        $price.value = beer.price
                    }
                }
            })
        }, 2000)
    </script>
</body>

</html>
