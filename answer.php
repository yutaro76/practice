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
    </form> 

    <?php 
        $formula = htmlspecialchars($_POST["formula"]);
        $pattern = "/[^0-9+\-\/\*\s]/";
        
        // check if POST matches the condition
        if (preg_match($pattern, $formula)){
            $allowedCharacters = "0123456789+-*/ ";
            for ($i = 0; $i < strlen($formula); $i++) {
                $currentChar = mb_substr($formula, $i, 1, 'UTF-8');
                if (strpos($allowedCharacters, $currentChar) === false) {
                    $index = $i + 1;
                    echo "$index 文字目'$currentChar'が「整数、+, -, /, *」ではありません";
                    break;
                }
            }
        } else {
            try {
                $answer = calculateFormula($formula);
    
                // insert data
                $sql = "INSERT INTO calc (formula, answer) values (:formula, :answer)";
                $statement = $pdo->prepare($sql);
                $params = array("formula" => $formula, "answer" => $answer);
                $statement->execute($params);
                echo $answer;
    
                // fetch data
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
        function calculateFormula($formula){
            $formulaAnswer = 0;
            $operator = "/[+\-\/\*]/";
            
            for($i=0; $i<strlen($formula); $i++){
                if(preg_match($operator, $formula[$i])){
                        switch ($formula[$i]) {
                        case '+':
                            $formulaAnswer = $formula[$i-1] + $formula[$i+1];
                            break;
                        case '-':
                            $formulaAnswer = $formula[$i-1] - $formula[$i+1];
                            break;
                        case '*':
                            $formulaAnswer = $formula[$i-1] * $formula[$i+1];
                            break;
                        case '/':
                            $formulaAnswer = $formula[$i-1] / $formula[$i+1];
                            break;
                    }
                }
            }  
            return $formulaAnswer;
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

