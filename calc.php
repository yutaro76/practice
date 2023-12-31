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

function calcInsideFormula($calcInside)
{
    if ((in_array('*', $calcInside)) || (in_array('/', $calcInside))) {
        if (!(multipleAndDivide($calcInside)[1] == null)) {
            $calcInsideAns = multipleAndDivide($calcInside)[0];
            $answerErr = multipleAndDivide($calcInside)[1];
            $formulaArr = '';
        } else {
            $calcInsideAns = multipleAndDivide($calcInside)[0];
            if ((in_array('+', $calcInsideAns)) || (in_array('-', $calcInsideAns))) {
                $calcInsideAns = plusAndMinus($calcInsideAns);
            }
        }
    } else {
        $calcInsideAns = plusAndMinus($calcInside);
    }

    // if the answer is array, extract the answer
    if (is_array($calcInsideAns)) {
        $calcInsideAns = $calcInsideAns[0];
    }

    return $calcInsideAns;
}

function calcInsideFormulaWithPar($calcInsideWithPar)
{
    $elementCounts = array_count_values($calcInsideWithPar);
    $openParNumFirst = $elementCounts['('];

    // repeat as many as ()
    $s = 0;
    while ($s < $openParNumFirst) {
        $openPar = '(';
        $closePar = ')';
        $openKeys = array_keys($calcInsideWithPar, $openPar);
        $closeKeys = array_keys($calcInsideWithPar, $closePar);
        $openKeysLast = end($openKeys);
        $closeKeysFirst = array_values($closeKeys)[0];

        // put formula inside () into []
        $calcInsideWithParInside = [];
        for ($k = $openKeysLast + 1; $k < $closeKeysFirst; $k++) {
            $calcInsideWithParInside[] = $calcInsideWithPar[$k];
        }

        $calcInsideWithParInsideAns = calcInsideFormula($calcInsideWithParInside);
        if (!($calcInsideWithPar == null)) {
            for ($k = $openKeysLast + 1; $k <= $closeKeysFirst; $k++) {
                unset($calcInsideWithPar[$k]);
            }
            $calcInsideWithPar = array_merge($calcInsideWithPar);
            $openKeysLast = end($openKeys);
            $calcInsideWithPar[$openKeysLast] = (string)$calcInsideWithParInsideAns;
            $calcInsideWithPar = array_merge($calcInsideWithPar);
        }

        $s++;
    }

    return $calcInsideWithPar;
}

function findParsPosision($array, $subarray)
{
    $string = implode('', $array);
    $position = strpos($string, implode('', $subarray));
    return $position !== false ? $position : -1;
}

function calcMultiParsAndAperators($formulaArr)
{
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
        }
    }
    // if the formula can be separeted into two
    if (count($separateOperator) == 1) {
        // extract first formula and calculate
        $formulaInParFirst = [];
        for ($i = 1; $i < $separateOperator[0] - 1; $i++) {
            $formulaInParFirst[] = $formulaArr[$i];
        }
        $elementCounts = array_count_values($formulaInParFirst);
        if (isset($elementCounts['('])) {
            $firstAnswer[] = calcInsideFormula(calcInsideFormulaWithPar($formulaInParFirst));
        } else {
            $firstAnswer[] = calcInsideFormula($formulaInParFirst);
        }

        // extract last formula and calculate
        $formulaInParLast = [];
        for ($i = $separateOperator[0] + 2; $i < array_key_last($formulaArr); $i++) {
            $formulaInParLast[] = $formulaArr[$i];
        }
        $elementCounts = array_count_values($formulaInParLast);
        if (isset($elementCounts['('])) {
            $lastAnswer[] = calcInsideFormula(calcInsideFormulaWithPar($formulaInParLast));
        } else {
            $lastAnswer[] = calcInsideFormula($formulaInParLast);
        }

        // combine first half and second half using the separating operator
        $formulaArr = array_merge($firstAnswer, $separateOperatorCalc, $lastAnswer);
    } else {

        // if the formula can be separated into more than three
        $j = 0;
        $formulaInParBetweenOuter = [];
        $formulaInParBetween = [];
        while ($j <= count($separateOperator)) {
            switch ($j) {
                    // extract first formula and calculate
                case $j == $separateOperator[0]:
                    $formulaInParFirst = [];
                    for ($i = 1; $i < $separateOperator[0] - 1; $i++) {
                        $formulaInParFirst[] = $formulaArr[$i];
                    }
                    $elementCounts = array_count_values($formulaInParFirst);
                    if (isset($elementCounts['('])) {
                        $firstAnswer[] = calcInsideFormula(calcInsideFormulaWithPar($formulaInParFirst));
                    } else {
                        $firstAnswer[] = calcInsideFormula($formulaInParFirst);
                    }
                    break;

                    // extract last formula and calculate
                case $j == array_key_last($separateOperator):
                    $formulaInParLast = [];
                    for ($i = $separateOperator[array_key_last($separateOperator)] + 2; $i < array_key_last($formulaArr); $i++) {
                        $formulaInParLast[] = $formulaArr[$i];
                    }
                    if (isset($elementCounts['('])) {
                        $lastAnswer[] = calcInsideFormula(calcInsideFormulaWithPar($formulaInParLast));
                    } else {
                        $lastAnswer[] = calcInsideFormula($formulaInParLast);
                    }
                    break;

                    // extract other formulas 
                default:
                    if (count($formulaInParBetweenOuter) == 0) {
                        for ($k = 0; $k < count($separateOperator) - 1; $k++) {
                            for ($l = $separateOperator[$k] + 2; $l < $separateOperator[$k + 1] - 1; $l++) {
                                $formulaInParBetween[] = $formulaArr[$l];
                            }
                            $formulaInParBetweenOuter[] = $formulaInParBetween;
                            $formulaInParBetween = [];
                        }
                    }
            }
            $j++;
        }
        // calculate other formulas
        for ($m = 0; $m < count($formulaInParBetweenOuter); $m++) {
            $formulaInParBetweenOuterAns[] = (string)calcInsideFormula(($formulaInParBetweenOuter[$m]));
        }

        // merge first and last formula
        array_unshift($formulaInParBetweenOuterAns, (string)$firstAnswer[0]);
        $formulaInParBetweenOuterAns[] = (string)$lastAnswer[0];

        // merge numbers and operators
        $multiParFormula = [];
        for ($n = 0; $n < count($formulaInParBetweenOuterAns); $n++) {
            if ($n < count($formulaInParBetweenOuterAns)) {
                $multiParFormula[] = $formulaInParBetweenOuterAns[$n];
            }
            if ($n < count($separateOperator)) {
                $multiParFormula[] = $formulaArr[$separateOperator[$n]];
            }
        }
        $formulaArr = $multiParFormula;
    }

    return $formulaArr;
}
