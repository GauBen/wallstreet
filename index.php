<?php

require 'login.php';

$dbh = require 'dbh.php';

[
    'last_prices' => $lastPrices,
    'curves' => $curves
] = require 'data.php';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wallstreet</title>
    <style>
        *,
        *::before,
        *::after {
            box-sizing: inherit;
        }

        :root {
            box-sizing: border-box;
            min-height: 100%;
            font-size: .5em;
        }

        body {
            margin: 0;
            font-size: 2rem;
            line-height: 1.5;
            background: #000;
        }

        .outer-frame {
            height: 100vh;
            padding: 1em;
        }

        .frame {
            height: 100%;
            padding: 1em;
            border: 6px double #b68a4e;
        }

        .full {
            height: 100%;
            overflow: hidden;
        }

        .full canvas {
            position: absolute;
        }
    </style>
</head>

<body>
    <div class=outer-frame>
        <div class=frame>
            <div class=full>
                <canvas id=board></canvas>
            </div>
        </div>
    </div>
    <script>
        const maximumPrice = 2.5
        const displayedTimeSpan = 120
        const textScale = 1 / 25
        const textWidthScale = 8
        const curveColors = ['#ff6', '#4f2', '#6af', '#41f', '#d03', '#83a', '#6f8'];

        let initialTime = new Date('<?= $lastPrices[0]['recorded_at'] ?>')
        let prices = JSON.parse('<?= json_encode($curves) ?>')
        let lastPrices = JSON.parse('<?= json_encode($lastPrices) ?>')

        const $board = document.querySelector('#board')
        /** @type CanvasRenderingContext2D  */
        const context = $board.getContext('2d')

        let width
        let height
        let textColumnWidth
        let fontSize

        const resizeHandler = () => {
            width = $board.width = $board.parentNode.offsetWidth
            height = $board.height = $board.parentNode.offsetHeight
            fontSize = height * textScale
            context.font = fontSize + 'px "Segoe UI", sans-serif'
            textColumnWidth = textWidthScale * height * textScale
        }

        const getPosY = (price) => height * (1 - price / maximumPrice);

        const getPos = (point) => {
            return {
                x: (width - textColumnWidth) * ((new Date(point.recorded_at) - initialTime) / (displayedTimeSpan * 1000) + 1),
                y: getPosY(point.price)
            }
        }

        const drawCurve = (curve, i) => {
            const {
                x,
                y
            } = getPos(curve[0])
            context.beginPath()
            context.moveTo(x, y)
            for (const point of curve.slice(1)) {
                const {
                    x,
                    y
                } = getPos(point)
                context.lineTo(x, y)
            }
            context.lineWidth = 5
            context.strokeStyle = curveColors[i]
            context.lineCap = 'round'
            context.lineJoin = 'round'
            context.stroke()
        }

        const drawPriceTags = (lastPrices) => {
            lastPrices.sort((a, b) => b.price - a.price)

            const tagBoxHeight = (lastPrices.length * 3 - 1) * fontSize
            const marginTop = height / 2 - tagBoxHeight / 2

            for (const i in lastPrices) {
                const beer = lastPrices[i]
                const priceTag = beer.price.toFixed(2) + ' €'
                context.fillStyle = curveColors[beer.beer_id - 1]
                const boxName = context.measureText(beer.name)
                const boxPrice = context.measureText(priceTag)
                context.fillText(
                    beer.name,
                    width - textColumnWidth / 2 - boxName.width / 2,
                    marginTop + (i * 3 + 1) * fontSize
                )
                context.fillText(
                    priceTag,
                    width - textColumnWidth / 2 - boxPrice.width / 2,
                    marginTop + (i * 3 + 2) * fontSize
                )
            }
        }

        const draw = () => {
            context.globalCompositeOperation = 'source-over';
            context.fillStyle = '#000'
            context.fillRect(0, 0, width, height)
            context.globalCompositeOperation = 'screen';
            for (const i in prices) {
                drawCurve(prices[i], i - 1)
            }
            drawPriceTags(lastPrices)
        }

        window.addEventListener('load', (e) => {
            resizeHandler()
            draw()
        })
        window.addEventListener('resize', (e) => {
            resizeHandler()
            draw()
        })

        setInterval(() => {
            fetch('compute.php').then(res => res.json()).then((res) => {
                initialTime = new Date(res.last_prices[0].recorded_at)
                lastPrices = res.last_prices
                prices = res.curves
                draw()
            })
        }, 2000)
    </script>
</body>

</html>
