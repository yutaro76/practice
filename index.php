<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>PHP Practice</title>
</head>

<body>
    <?php
    require_once 'calc.php';
    require __DIR__ . '/vendor/autoload.php';
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();

    $dsn = strval($_ENV['DBNAME'] . $_ENV['HOST']);
    $user = $_ENV['USER'];
    $password = $_ENV['PASSWORD'];
    $pdo = new PDO($dsn, $user, $password);
    ?>
    <form method="POST" action="<?php print($_SERVER['PHP_SELF']) ?>">
        <input type="text" name="formula"><br>
        <input type="submit" name="btn" value="計算">
    </form>
    <?php if ($_SERVER["REQUEST_METHOD"] != "POST") { ?>
    <?php } else {
        $formula = htmlspecialchars($_POST["formula"]);
        $pattern = "/[^0-9+\-\/\*\.\(\)\s]/";
        // check if the formula matches the condition
        if (preg_match($pattern, $formula)) {
            $allowedCharacters = "0123456789+-*/.() ";
            for ($i = 0; $i < strlen($formula); $i++) {
                $currentChar = mb_substr($formula, $i, 1, 'UTF-8');
                if (strpos($allowedCharacters, $currentChar) === false) {
                    $index = $i + 1;
                    echo "$index 文字目'$currentChar'が「整数、+, -, /, *」ではありません";
                    break;
                }
            }
        } else {
            $formulaNew = str_replace(' ', '', $formula);
            $formulaAnswer = 0;
            $operator = "/[+\-\/\*]/";
            $currentNum = "";
            $formulaArr = [];

            // put numbers and operands separately into array 
            $characters = str_split($formulaNew);

            foreach ($characters as $char) {
                if (in_array($char, ["(", ")"]) && (!($currentNum == null))) {
                    $formulaArr[] = $currentNum;
                    $formulaArr[] = $char;
                    $currentNum = "";
                } elseif (in_array($char, ["(", ")"])) {
                    $formulaArr[] = $char;
                } elseif (in_array($char, ["+", "-", "*", "/"])) {
                    if ($currentNum) {
                        $formulaArr[] = $currentNum;
                    }
                    $formulaArr[] = $char;
                    $currentNum = "";
                } else {
                    $currentNum .= $char;
                }
            }

            // put the last number into array
            if (!($currentNum == null)) {
                array_push($formulaArr, $currentNum);
            }

            // extract formula inside () 
            if (in_array('(', $formulaArr) && in_array(')', $formulaArr)) {
                // if there is one ()
                if (array_count_values($formulaArr)['('] == 1 && array_count_values($formulaArr)[')'] == 1) {
                    $openPar = array_search('(', $formulaArr);
                    $closePar = array_search(')', $formulaArr);
                    $formulaInPar = [];
                    for ($i = $openPar + 1; $i < $closePar; $i++) {
                        $formulaInPar[] = $formulaArr[$i];
                    }

                    // calculate inside ()
                    if ((in_array('*', $formulaInPar)) || (in_array('/', $formulaInPar))) {
                        if (!(multipleAndDivide($formulaInPar)[1] == null)) {
                            $formulaInParAns = multipleAndDivide($formulaInPar)[0];
                            $answerErr = multipleAndDivide($formulaInPar)[1];
                            $formulaArr = '';
                        } else {
                            $formulaInParAns = multipleAndDivide($formulaInPar)[0];
                            if ((in_array('+', $formulaInParAns)) || (in_array('-', $formulaInParAns))) {
                                $formulaInParAns = plusAndMinus($formulaInParAns);
                            }
                        }
                    } else {
                        $formulaInParAns = plusAndMinus($formulaInPar);
                    }

                    if (is_array($formulaInParAns)) {
                        $formulaInParAns = $formulaInParAns[0];
                    }

                    // replace ( ) and inside formula with the answer 
                    if (!($formulaArr == null)) {
                        for ($i = $openPar + 1; $i <= $closePar; $i++) {
                            unset($formulaArr[$i]);
                        }
                        $formulaArr[array_search('(', $formulaArr)] = (string)$formulaInParAns;
                        $formulaArr = array_merge($formulaArr);
                    }
                } else {
                    // if there are multiple ()
                    // count the number of ()
                    $elementCounts = array_count_values($formulaArr);
                    $openParNum = $elementCounts['('];

                    // repeat as many as ()
                    $s = 0;
                    while ($s < $openParNum) {
                        // get the keys of ( and )
                        $openPar = '(';
                        $closePar = ')';
                        $openKeys = array_keys($formulaArr, $openPar);
                        $closeKeys = array_keys($formulaArr, $closePar);
                        $openKeysLast = end($openKeys);
                        $closeKeysFirst = array_values($closeKeys)[0];

                        // calculate from inner (): get ( from the last and get ) from the first
                        $formulaInPar = [];
                        for ($i = $openKeysLast + 1; $i < $closeKeysFirst; $i++) {
                            $formulaInPar[] = $formulaArr[$i];
                        }

                        if ((in_array('*', $formulaInPar)) || (in_array('/', $formulaInPar))) {
                            if (!(multipleAndDivide($formulaInPar)[1] == null)) {
                                $formulaInParAns = multipleAndDivide($formulaInPar)[0];
                                $answerErr = multipleAndDivide($formulaInPar)[1];
                                $formulaArr = '';
                            } else {
                                $formulaInParAns = multipleAndDivide($formulaInPar)[0];
                                if ((in_array('+', $formulaInParAns)) || (in_array('-', $formulaInParAns))) {
                                    $formulaInParAns = plusAndMinus($formulaInParAns);
                                }
                            }
                        } else {
                            $formulaInParAns = plusAndMinus($formulaInPar);
                        }

                        if (is_array($formulaInParAns)) {
                            $formulaInParAns = $formulaInParAns[0];
                        }

                        // replace original formula with the answer
                        if (!($formulaArr == null)) {
                            for ($i = $openKeysLast + 1; $i <= $closeKeysFirst; $i++) {
                                unset($formulaArr[$i]);
                            }
                            $formulaArr = array_merge($formulaArr);
                            $openKeysLast = end($openKeys);
                            $formulaArr[$openKeysLast] = (string)$formulaInParAns;
                            $formulaArr = array_merge($formulaArr);
                        }
                        $s++;
                    }
                }
            } elseif (in_array('(', $formulaArr) || in_array(')', $formulaArr)) {
                $answerErr = "()の入力が不十分です";
            }

            // get answer of the whole formula 
            if (!($formulaArr == null) && ($answerErr == null)) {
                if ((in_array('*', $formulaArr)) || (in_array('/', $formulaArr))) {
                    if (!(multipleAndDivide($formulaArr)[1]) == null) {
                        $answerErr = multipleAndDivide($formulaArr)[1];
                        $answer = '';
                    } else {
                        $answer = multipleAndDivide($formulaArr)[0];
                        if ((in_array('+', $answer)) || (in_array('-', $answer))) {
                            $answer = plusAndMinus($answer);
                        }
                    }
                } elseif (!($formulaArr == null)) {
                    $answer = plusAndMinus($formulaArr);
                }
            }

            // replace $answer if divided by 0 
            if ($answerErr) {
                $answer = $answerErr;
            }

            // if $answer is array, extract the answer
            if (is_array($answer)) {
                $answer = $answer[0];
            }

            // insert data
            try {
                $sql = "INSERT INTO calc (formula, answer) values (:formula, :answer)";
                $statement = $pdo->prepare($sql);
                $params = array("formula" => $formula, "answer" => $answer);
                $statement->execute($params);
                echo $answer;
            } catch (PDOException $e) {
                echo "DB接続エラー:" . $e->getMessage();
            }
        }
    }
    ?>

    <?php
    try {
        // fetch data
        $statement2 = $pdo->prepare("SELECT * FROM calc");
        $statement2->execute();
        $result = $statement2->fetchAll();

        $statement = null;
        $statement2 = null;
        $pdo = null;
    } catch (PDOException $e) {
        echo "DB接続エラー:" . $e->getMessage();
    }

    ?>

    <table border="1">
        <tr>
            <th>date</th>
            <th>answer</th>
        </tr>
        <?php for ($i = 0; $i < count($result); $i++) { ?>
            <tr>
                <td><?php echo mb_substr($result[$i]["created"], 0, 10); ?></td>
                <td><?php echo $result[$i]["formula"]; ?> = <?php echo $result[$i]["answer"]; ?></td>
            </tr>
        <?php } ?>
    </table>


</body>

</html>