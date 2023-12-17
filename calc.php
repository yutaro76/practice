<?php

function multipleAndDivide($formulaArr)
{
    // calculate * and / first
    if (in_array('*', $formulaArr) || in_array('/', $formulaArr)) {
        $array1 = array_keys($formulaArr, '/');
        $array2 = array_keys($formulaArr, '*');
        $keys = array_merge($array1, $array2);
        sort($keys);

        // check if divided by 0
        foreach ($keys as $key) {
            if (($formulaArr[$key] === "/") && ($formulaArr[$key + 1] === "0")) {
                $answerErr = "0で割ることはできません";
            }
        }

        // if not divided by 0 
        if (!($answerErr)) {
            // calculate * and / parts first
            if (count($keys) > 0) {
                $counter = 0;
                if ($keys >= 2) {
                    while ($counter <= count($keys)) {
                        if ($formulaArr[$keys[0]] === "*") {
                            $mulAnswer = $formulaArr[$keys[0] - 1] * $formulaArr[$keys[0] + 1];
                            unset($formulaArr[$keys[0] - 1]);
                            unset($formulaArr[$keys[0] + 1]);
                            $formulaArr[$keys[0]] = (string)$mulAnswer;
                            $formulaArr = array_values($formulaArr);
                        } else {
                            $divAnswer = $formulaArr[$keys[0] - 1] / $formulaArr[$keys[0] + 1];
                            $divAnswer = round($divAnswer, 1);
                            unset($formulaArr[$keys[0] - 1]);
                            unset($formulaArr[$keys[0] + 1]);
                            $formulaArr[$keys[0]] = (string)$divAnswer;
                            $formulaArr = array_values($formulaArr);
                        }
                        $array1 = array_keys($formulaArr, '/');
                        $array2 = array_keys($formulaArr, '*');
                        $keys = array_merge($array1, $array2);
                        sort($keys);
                        $counter++;
                    }
                }
                while ($counter <= count($keys)) {
                    if ($formulaArr[$keys[0]] === "*") {
                        $mulAnswer = $formulaArr[$keys[0] - 1] * $formulaArr[$keys[0] + 1];
                        unset($formulaArr[$keys[0] - 1]);
                        unset($formulaArr[$keys[0] + 1]);
                        $formulaArr[$keys[0]] = (string)$mulAnswer;
                        $formulaArr = array_values($formulaArr);
                    } else {
                        $divAnswer = $formulaArr[$keys[0] - 1] / $formulaArr[$keys[0] + 1];
                        $divAnswer = round($divAnswer, 1);
                        unset($formulaArr[$keys[0] - 1]);
                        unset($formulaArr[$keys[0] + 1]);
                        $formulaArr[$keys[0]] = (string)$divAnswer;
                        $formulaArr = array_values($formulaArr);
                    }
                    $array1 = array_keys($formulaArr, '/');
                    $array2 = array_keys($formulaArr, '*');
                    $keys = array_merge($array1, $array2);
                    sort($keys);
                    $counter++;
                }
            }
        }
    }

    return $formulaArr;
}

function plusAndMinus($formulaArr)
{
    if (strpos($formulaArr[0], '.')) {
        $answer = (float)($formulaArr[0]);
    } else {
        $answer = (int)($formulaArr[0]);
    }

    // calculate the combination of operand and number
    for ($i = 1; $i < count($formulaArr); $i += 2) {
        $operator = $formulaArr[$i];
        if (strpos($formulaArr[$i + 1], '.')) {
            $number = (float)($formulaArr[$i + 1]);
        } else {
            $number = (int)($formulaArr[$i + 1]);
        }

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
