<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OSRS Potion Profit Calculator</title>
    <script>
        // Function to format the input values with commas
        function formatInput(event) {
            var value = event.target.value.replace(/,/g, '');
            if (!isNaN(value) && value !== '') {
                var formattedValue = Number(value).toLocaleString('en');
                event.target.value = formattedValue;
            }
        }

        // Function to clear all input fields
        function clearInputs() {
            document.getElementById('price1').value = '';
            document.getElementById('price2').value = '';
            document.getElementById('quantity').value = '';
            document.getElementById('sellPrice').value = '';
            document.getElementById('productQuantity').value = '';
            document.getElementById('productQuantityContainer').style.display = 'none';
            document.getElementById('separateQuantity').checked = false;
            document.getElementById('profit').innerText = 'Profit: ';
        }

        // Function to toggle the separate quantity input
        function toggleProductQuantity() {
            const productQuantityContainer = document.getElementById('productQuantityContainer');
            productQuantityContainer.style.display = document.getElementById('separateQuantity').checked ? 'block' : 'none';
        }
    </script>
</head>
<body>
    <h1>OSRS Potion Profit Calculator</h1>
    <form method="post">
        <button type="button" onclick="clearInputs()">Clear All</button>
        <br><br>
        <label for="price1">Price 1:</label>
        <input type="text" id="price1" name="price1" oninput="formatInput(event)"><br><br>
        
        <label for="price2">Price 2:</label>
        <input type="text" id="price2" name="price2" oninput="formatInput(event)"><br><br>
        
        <label for="quantity">Quantity:</label>
        <input type="text" id="quantity" name="quantity" oninput="formatInput(event)"><br><br>
        
        <label for="sellPrice">Sell Price per Product:</label>
        <input type="text" id="sellPrice" name="sellPrice" oninput="formatInput(event)">
        <input type="checkbox" id="separateQuantity" name="separateQuantity" onclick="toggleProductQuantity()">
        <label for="separateQuantity">Use separate quantity for product</label><br><br>
        
        <div id="productQuantityContainer" style="display: none;">
            <label for="productQuantity">Product Quantity:</label>
            <input type="text" id="productQuantity" name="productQuantity" oninput="formatInput(event)"><br><br>
        </div>
        
        <button type="submit">Calculate Profit</button>
    </form>

    <?php
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $price1 = isset($_POST['price1']) ? str_replace(',', '', $_POST['price1']) : 0;
        $price2 = isset($_POST['price2']) ? str_replace(',', '', $_POST['price2']) : 0;
        $quantity = isset($_POST['quantity']) ? str_replace(',', '', $_POST['quantity']) : 0;
        $sellPrice = isset($_POST['sellPrice']) ? str_replace(',', '', $_POST['sellPrice']) : 0;

        $price1 = (float)$price1;
        $price2 = (float)$price2;
        $quantity = (float)$quantity;
        $sellPrice = (float)$sellPrice;

        // Check if the separate quantity checkbox is used
        if (isset($_POST['separateQuantity'])) {
            $productQuantity = isset($_POST['productQuantity']) ? str_replace(',', '', $_POST['productQuantity']) : 0;
            $productQuantity = (float)$productQuantity;
        } else {
            $productQuantity = $quantity;
        }

        if ($price1 > 0 && $price2 > 0 && $quantity > 0 && $sellPrice > 0 && $productQuantity > 0) {
            $totalCost = ($price1 + $price2) * $quantity;
            $productSell = ($sellPrice * $productQuantity) * 0.99; // Sell price with 1% deduction
            $profit = $productSell - $totalCost;
            echo "<h2 id='profit'>Profit: " . number_format($profit, 2) . "</h2>";
        } else {
            echo "<h2 id='profit'>Please enter valid numbers in all fields.</h2>";
        }
    }
    ?>
</body>
</html>
