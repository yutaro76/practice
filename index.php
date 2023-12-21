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

            // if there is/are () 
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
                    // if the answer is array, extract the answer
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
                } else { // if there are multiple ()
                    $openPar = '(';
                    $closePar = ')';
                    $openKeys = array_keys($formulaArr, $openPar);
                    $closeKeys = array_keys($formulaArr, $closePar);
                    $openKeysLast = end($openKeys);
                    $closeKeysFirst = array_values($closeKeys)[0];
                    $separateOperator = [];
                    $separateOperatorCalc = [];
                    $answerErr = '';
                    if ($openKeysLast > $closeKeysFirst) {
                        // if the last '(' is bigger than first ')' ex (((((1 + 2) + 3) + 4) + 5) + 6) + (10 * 2)
                        // check the combination of " ) operator ( "
                        for ($i = 0; $i < count($formulaArr) - 2; $i++) {
                            if (
                                $formulaArr[$i] == ')' && $formulaArr[$i + 1] == '+' && $formulaArr[$i + 2] == '('
                                ||
                                $formulaArr[$i] == ')' && $formulaArr[$i + 1] == '-' && $formulaArr[$i + 2] == '('
                                ||
                                $formulaArr[$i] == ')' && $formulaArr[$i + 1] == '*' && $formulaArr[$i + 2] == '('
                                ||
                                $formulaArr[$i] == ')' && $formulaArr[$i + 1] == '/' && $formulaArr[$i + 2] == '('
                            ) {
                                // put the key of separating operator into array
                                $separateOperator[] = $i + 1;
                                $separateOperatorCalc[] = $formulaArr[$i + 1];

                                // extract formula of the first half
                                $formulaInParFirst = [];
                                for ($i = 1; $i < $separateOperator[0] - 1; $i++) {
                                    $formulaInParFirst[] = $formulaArr[$i];
                                }
                                // extract formula of the second half
                                $formulaInParSecond = [];
                                for ($i = $separateOperator[0] + 2; $i < array_key_last($formulaArr); $i++) {
                                    $formulaInParSecond[] = $formulaArr[$i];
                                }

                                // calculate the first half
                                $elementCounts = array_count_values($formulaInParFirst);
                                // if there is/are ()
                                if (isset($elementCounts['('])) {
                                    $openParNumFirst = $elementCounts['('];
                                    // repeat as many as ()
                                    $s = 0;
                                    while ($s < $openParNumFirst) {
                                        $openPar = '(';
                                        $closePar = ')';
                                        $openKeys = array_keys($formulaInParFirst, $openPar);
                                        $closeKeys = array_keys($formulaInParFirst, $closePar);
                                        $openKeysLast = end($openKeys);
                                        $closeKeysFirst = array_values($closeKeys)[0];

                                        // put formula inside () into []
                                        $formulaInParFirstInside = [];
                                        for ($k = $openKeysLast + 1; $k < $closeKeysFirst; $k++) {
                                            $formulaInParFirstInside[] = $formulaInParFirst[$k];
                                        }

                                        // calculate inside the formula
                                        if ((in_array('*', $formulaInParFirstInside)) || (in_array('/', $formulaInParFirstInside))) {
                                            if (!(multipleAndDivide($formulaInParFirstInside)[1] == null)) {
                                                $formulaInParFirstInsideAns = multipleAndDivide($formulaInParFirstInside)[0];
                                                $answerErr = multipleAndDivide($formulaInParFirstInside)[1];
                                                $formulaArr = '';
                                            } else {
                                                $formulaInParFirstInsideAns = multipleAndDivide($formulaInParFirstInside)[0];
                                                if ((in_array('+', $formulaInParFirstInsideAns)) || (in_array('-', $formulaInParFirstInsideAns))) {
                                                    $formulaInParFirstInsideAns = plusAndMinus($formulaInParFirstInsideAns);
                                                }
                                            }
                                        } else {
                                            $formulaInParFirstInsideAns = plusAndMinus($formulaInParFirstInside);
                                        }

                                        // if the answer is array, extract the answer
                                        if (is_array($formulaInParFirstInsideAns)) {
                                            $formulaInParFirstInsideAns = $formulaInParFirstInsideAns[0];
                                        }

                                        // replace ( ) and inside formula with the answer 
                                        if (!($formulaInParFirst == null)) {
                                            for ($k = $openKeysLast + 1; $k <= $closeKeysFirst; $k++) {
                                                unset($formulaInParFirst[$k]);
                                            }
                                            $formulaInParFirst = array_merge($formulaInParFirst);
                                            $openKeysLast = end($openKeys);
                                            $formulaInParFirst[$openKeysLast] = (string)$formulaInParFirstInsideAns;
                                            $formulaInParFirst = array_merge($formulaInParFirst);
                                        }
                                        $s++;
                                    }
                                }

                                // calculate the formula that has no ()
                                if (!($formulaInParFirst == null) && ($answerErr == null)) {
                                    if ((in_array('*', $formulaInParFirst)) || (in_array('/', $formulaInParFirst))) {
                                        if (!(multipleAndDivide($formulaInParFirst)[1]) == null) {
                                            $answerErr = multipleAndDivide($formulaInParFirst)[1];
                                            $answerFirst = '';
                                        } else {
                                            $answerFirst = multipleAndDivide($formulaInParFirst)[0];
                                            if ((in_array('+', $answerFirst)) || (in_array('-', $answerFirst))) {
                                                $answerFirst = plusAndMinus($answerFirst);
                                            }
                                        }
                                    } elseif (!($formulaInParFirst == null)) {
                                        $answerFirst = plusAndMinus($formulaInParFirst);
                                    }
                                    $answerFirst = (array)$answerFirst;
                                }

                                // calculate the first half
                                $elementCounts = array_count_values($formulaInParSecond);
                                if ($elementCounts['('] !== null) {
                                    $openParNumSecond = $elementCounts['('];
                                    // repeat as many as ()
                                    $m = 0;
                                    while ($m < $openParNumSecond) {
                                        $openPar = '(';
                                        $closePar = ')';
                                        $openKeys = array_keys($formulaInParSecond, $openPar);
                                        $closeKeys = array_keys($formulaInParSecond, $closePar);
                                        $openKeysLast = end($openKeys);
                                        $closeKeysFirst = array_values($closeKeys)[0];
                                        $answerErr = '';

                                        // put formula inside () into []
                                        $formulaInParSecondInside = [];
                                        for ($j = $openKeysLast + 1; $j < $closeKeysFirst; $j++) {
                                            $formulaInParSecondInside[] = $formulaInParSecond[$j];
                                        }

                                        // calculate inside the formula
                                        if ((in_array('*', $formulaInParSecondInside)) || (in_array('/', $formulaInParSecondInside))) {
                                            if (!(multipleAndDivide($formulaInParSecondInside)[1] == null)) {
                                                $formulaInParSecondInsideAns = multipleAndDivide($formulaInParSecondInside)[0];
                                                $answerErr = multipleAndDivide($formulaInParSecondInside)[1];
                                                $formulaArr = '';
                                            } else {
                                                $formulaInParSecondInsideAns = multipleAndDivide($formulaInParSecondInside)[0];
                                                if ((in_array('+', $formulaInParSecondInsideAns)) || (in_array('-', $formulaInParSecondInsideAns))) {
                                                    $formulaInParSecondInsideAns = plusAndMinus($formulaInParSecondInsideAns);
                                                }
                                            }
                                        } else {
                                            $formulaInParSecondInsideAns = plusAndMinus($formulaInParSecondInside);
                                        }

                                        // if the answer is array, extract the answer
                                        if (is_array($formulaInParSecondInsideAns)) {
                                            $formulaInParSecondInsideAns = $formulaInParSecondInsideAns[0];
                                        }

                                        // replace ( ) and inside formula with the answer 
                                        if (!($formulaInParSecond == null)) {
                                            for ($j = $openKeysLast + 1; $j <= $closeKeysFirst; $j++) {
                                                unset($formulaInParSecond[$j]);
                                            }
                                            $formulaInParSecond = array_merge($formulaInParSecond);
                                            $openKeysLast = end($openKeys);
                                            $formulaInParSecond[$openKeysLast] = (string)$formulaInParSecondInsideAns;
                                            $formulaInParSecond = array_merge($formulaInParSecond);
                                        }
                                        $m++;
                                    }
                                }

                                // calculate the formula that has no ()
                                if (!($formulaInParSecond == null) && ($answerErr == null)) {
                                    if ((in_array('*', $formulaInParSecond)) || (in_array('/', $formulaInParSecond))) {
                                        if (!(multipleAndDivide($formulaInParSecond)[1]) == null) {
                                            $answerErr = multipleAndDivide($formulaInParSecond)[1];
                                            $answerSecond = '';
                                        } else {
                                            $answerSecond = multipleAndDivide($formulaInParSecond)[0];
                                            if ((in_array('+', $answerSecond)) || (in_array('-', $answerSecond))) {
                                                $answerSecond = plusAndMinus($answerSecond);
                                            }
                                        }
                                    } elseif (!($formulaInParSecond == null)) {
                                        $answerSecond = plusAndMinus($formulaInParSecond);
                                    }
                                    $answerSecond = (array)$answerSecond;
                                }
                            }
                        }
                        //combine first half and second half using the separating operator
                        $formulaArr = array_merge($answerFirst, $separateOperatorCalc, $answerSecond);
                    } else {
                        // if the last '(' is smaller than first ')' ex ((2 * 4) + 5) * 2
                        // count the number of ()
                        $elementCounts = array_count_values($formulaArr);
                        $openParNum = $elementCounts['('];

                        // repeat as many as ()
                        $t = 0;
                        while ($t < $openParNum) {
                            // prepare several variables
                            $openPar = '(';
                            $closePar = ')';
                            $openKeys = array_keys($formulaArr, $openPar);
                            $closeKeys = array_keys($formulaArr, $closePar);
                            $openKeysLast = end($openKeys);
                            $closeKeysFirst = array_values($closeKeys)[0];

                            // put formula inside () into []
                            $formulaInPar = [];
                            for ($i = $openKeysLast + 1; $i < $closeKeysFirst; $i++) {
                                $formulaInPar[] = $formulaArr[$i];
                            }

                            // calculate inside the formula
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

                            // if the answer is array, extract the answer
                            if (is_array($formulaInParAns)) {
                                $formulaInParAns = $formulaInParAns[0];
                            }

                            // replace ( ) and inside formula with the answer 
                            if (isset($formulaArr)) {
                                for ($i = $openKeysLast + 1; $i <= $closeKeysFirst; $i++) {
                                    unset($formulaArr[$i]);
                                }
                                $formulaArr = array_merge($formulaArr);
                                $openKeysLast = end($openKeys);
                                $formulaArr[$openKeysLast] = (string)$formulaInParAns;
                                $formulaArr = array_merge($formulaArr);
                            }
                            $t++;
                        }
                    }
                }
            } elseif (in_array('(', $formulaArr) || in_array(')', $formulaArr)) {
                $answerErr = "()の入力が不十分です";
            }

            // get the answer of the whole formula 
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