<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Practice</title>
</head>
<body>
    <form method="POST" action="<?php print($SERVER['PHP_SELF']) ?>">
        <input type="text" name="formula"><br>
        <input type="submit" name="btn" value="計算">
    </form> 
    <?php
    $formula = $_POST['formula'];
    echo($formula);
    ?>
    
</body>
</html>