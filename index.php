<?php 
$dsn = 'mysql:dbname=cake;host=localhost';
$user = 'okuda';
$pass = '';
$pdo = new PDO($dsn, $user, $password);

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Practice</title>
</head>
<body>
    <form method="POST" action="<?php print($SERVER['PHP_SELF']) ?>">
        <?php if (isset($_POST['formula'])) { ?>
            <input type="text" name="formula" value="<?php echo($_POST['formula']); ?>"><br>
        <?php } else {?>
            <input type="text" name="formula"><br>
        <?php } ?>
        <input type="submit" name="btn" value="計算">
    </form> 
    <?php
    $formula = $_POST['formula'];
    echo($formula);
    ?>

    <?php 
        try {
            

            $pstmt = $pdo->prepare('SELECT * FROM calc');
            $pstmt->execute();
            $result = $pstmt -> fetchAll();
            echo '<pre>'; var_dump($result);

            $pstmt = null;
            $pdo = null;

        } catch (PDOException $e) {
            print "エラー!: " . $e->getMessage() . "<br/gt;";
            die();
        }

    ?>
    
</body>
</html>

<!-- PDO -->
<!-- https://engineer-milione.com/programming/php-pdo.html -->