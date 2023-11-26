<?php 
$dsn = 'mysql:dbname=cake;host=localhost';
$user = 'okuda';
$pass = '';
$pdo = new PDO($dsn, $user, $password);
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Practice</title>
</head>
<body>
    <a href="index.php">戻る</a>
    <form method="POST" action="<?php print($SERVER['PHP_SELF']) ?>">
        <input type="text" name="formula"><br>
        <input type="submit" name="btn" value="計算" disabled>
    </form> 

    <?php 
        try {
            $formula = htmlspecialchars($_POST['formula']);
            $result = sprintf('$answer=%s;', $formula);
            eval($result);
            $sql = "INSERT INTO calc (formula, answer) values (:formula, :answer)";
            $statement = $pdo->prepare($sql);
            $params = array('formula' => $formula, 'answer' => $answer);
            $statement->execute($params);
            echo $answer;
            
        } catch (PDOException $e){
            echo 'DB接続エラー:'. $e->getMessage();
        }


        //     $pstmt = $pdo->prepare('SELECT * FROM calc');
        //     $pstmt->execute();
        //     $result = $pstmt -> fetchAll();

        //     $pstmt = null;
        //     $pdo = null;

        // } catch (PDOException $e) {
        //     print "エラー!: " . $e->getMessage() . "<br/gt;";
        //     die();
        // }

    ?>


<!-- 
    <?php
    $formula = $_POST['formula'];
    echo($formula);
    ?> -->

    
    
</body>
</html>

