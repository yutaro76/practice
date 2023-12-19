<?php

function multipleAndDivide($formulaArr)
{
    // calculate * and / first
    if (in_array('*', $formulaArr) || in_array('/', $formulaArr)) {
        $array1 = array_keys($formulaArr, '/');
        $array2 = array_keys($formulaArr, '*');
        $keys = array_merge($array1, $array2);
        sort($keys);

        $answerErr = '';
        $counter = 0;
        $repKeys = count($keys);

        for ($counter = 0; $counter < $repKeys; $counter++) {
            if ($formulaArr[$keys[0]] === "*") {
                $mulAnswer = $formulaArr[$keys[0] - 1] * $formulaArr[$keys[0] + 1];
                unset($formulaArr[$keys[0] - 1]);
                unset($formulaArr[$keys[0] + 1]);
                $formulaArr[$keys[0]] = (string)$mulAnswer;
                $formulaArr = array_values($formulaArr);
            } else {
                if ($formulaArr[$keys[0] + 1] == '0') {
                    $formulaArr = '';
                    $answerErr = "0で割ることはできません";
                    break;
                }
                $divAnswer = $formulaArr[$keys[0] - 1] / $formulaArr[$keys[0] + 1];
                $divAnswer = round($divAnswer, 1);
                unset($formulaArr[$keys[0] - 1]);
                unset($formulaArr[$keys[0] + 1]);
                $formulaArr[$keys[0]] = (string)$divAnswer;
                $formulaArr = array_values($formulaArr);
            }
            if (in_array('*', $formulaArr) || in_array('/', $formulaArr)) {
                $array1 = array_keys($formulaArr, '/');
                $array2 = array_keys($formulaArr, '*');
                $keys = array_merge($array1, $array2);
                sort($keys);
            }
        }
    }
    return array($formulaArr, $answerErr);
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
