<?php 
require __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$dsn = strval($_ENV['DBNAME'].$_ENV['HOST']);
$user = $_ENV['USER'];
$password = $_ENV['PASSWORD'];
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
    <form method="POST">
        <input type="text" name="formula"><br>
        <input type="submit" name="btn" value="計算" disabled>
    </form> 

    <?php 
        $formula = htmlspecialchars($_POST["formula"]);
        $pattern = "/[^0-9+\-\/\*\s]/";
        if (preg_match($pattern, $formula)){
            echo "error";
        } else {
            try {
                $result = sprintf('$answer=%s;', $formula);
                eval($result);
    
                $sql = "INSERT INTO calc (formula, answer) values (:formula, :answer)";
                $statement = $pdo->prepare($sql);
                $params = array("formula" => $formula, "answer" => $answer);
                $statement->execute($params);
                echo $answer;
    
                $statement2 = $pdo->prepare("SELECT * FROM calc");
                $statement2->execute();
                $result = $statement2 -> fetchAll();
                
                $statement = null;
                $statement2 = null;
                $pdo = null;
            } catch (PDOException $e){
                echo "DB接続エラー:". $e->getMessage();
            }
        }
    ?>

    <table border = "1">
        <tr>
            <th>date</th>
            <th>answer</th>
        </tr>
        <?php for($i = 0; $i < count($result); $i++) { ?>
            <tr>
                <td><?php echo mb_substr($result[$i]["created"], 0, 10); ?></td>
                <td><?php echo $result[$i]["formula"]; ?> = <?php echo $result[$i]["answer"]; ?></td>
            </tr>
        <?php } ?>
    </table>
</body>
</html>

