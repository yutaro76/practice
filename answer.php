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
        // check if the formula matches the condition
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
                $formulaNew = deleteSpace($formula);
                $answer = calculateFormula($formulaNew);

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

        function calculateFormula($formulaNew){
            $formulaAnswer = 0;
            $operator = "/[+\-\/\*]/";
            $currentNum = "";
            $formulaArr = [];

            // put numbers and operands separately into array
            $characters = str_split($formulaNew);
            foreach($characters as $char) {
                if (in_array($char, ["+","-","*","/"])){
                    $formulaArr[] = $currentNum;
                    $formulaArr[] = $char;
                    $currentNum = "";
                } else {
                    $currentNum .= $char;
                }
            }

            // put the last number into array
            $formulaArr[] = $currentNum;
            // calculate * and / first
            $array1 = array_keys($formulaArr, '/');
            $array2 = array_keys($formulaArr, '*');
            $keys = array_merge($array1, $array2);
            sort($keys);

            foreach($keys as $key){
                if(($formulaArr[$key] === "/") && ($formulaArr[$key+1] === "0")){
                    return $answer = "0で割ることはできません";
                }
            }

            if(count($keys) > 0){
                $counter = 0;
                while($counter <= count($keys)){
                    if($formulaArr[$keys[0]] === "*") {
                        $mulAnswer = $formulaArr[$keys[0]-1] * $formulaArr[$keys[0]+1];
                        unset($formulaArr[$keys[0]-1]);
                        unset($formulaArr[$keys[0]+1]);
                        $formulaArr[$keys[0]] = (string)$mulAnswer;
                        $formulaArr = array_values($formulaArr);
                    } else {
                        $divAnswer = $formulaArr[$keys[0]-1] / $formulaArr[$keys[0]+1];
                        unset($formulaArr[$keys[0]-1]);
                        unset($formulaArr[$keys[0]+1]);
                        $formulaArr[$keys[0]] = (string)$divAnswer;
                        $formulaArr = array_values($formulaArr);
                    }
                    $array1 = array_keys($formulaArr, '/');
                    $array2 = array_keys($formulaArr, '*');
                    $keys = array_merge($array1, $array2);
                    sort($keys);
                    $counter ++;
                }
            }
            
            // calculate the array
            // put the first number into variable
            $answer = (int)$formulaArr[0];

            // calculate the combination of operand and number
            for ($i = 1; $i < count($formulaArr); $i += 2) {
                $operator = $formulaArr[$i];
                $number = (int)$formulaArr[$i + 1];
       
                switch ($operator) {
                    case "+":
                        $answer += $number;
                        break;
                    case "-":
                        $answer -= $number;
                        break;
                }
            }
       
            return $answer;
        }

        // delete space in the formula
        function deleteSpace($formula) {
            $cleanedFormula = str_replace(' ','',$formula);
            return $cleanedFormula;
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
