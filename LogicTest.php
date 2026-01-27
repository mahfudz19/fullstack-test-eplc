<?php

class Test
{
  public function mergeSortArray($a, $b)
  {
    $result = array();

    foreach ($a as $val) {
      $result[] = $val;
    }
    foreach ($b as $val) {
      $result[] = $val;
    }

    $count = 0;
    foreach ($result as $item) {
      $count++;
    }

    for ($i = 0; $i < $count; $i++) {
      for ($j = 0; $j < $count - $i - 1; $j++) {
        if ($result[$j] > $result[$j + 1]) {

          $temp = $result[$j];
          $result[$j] = $result[$j + 1];
          $result[$j + 1] = $temp;
        }
      }
    }

    return $result;
  }

  public function getMissingData($arr)
  {
    $missing = array();
    $count = 0;
    foreach ($arr as $item) {
      $count++;
    }

    if ($count < 2) return $missing;

    $currentDiff = $arr[1] - $arr[0];

    for ($i = 0; $i < $count - 1; $i++) {
      $currentVal = $arr[$i];
      $nextValInArray = $arr[$i + 1];

      $expectedNextVal = $currentVal + $currentDiff;

      while ($expectedNextVal < $nextValInArray) {
        $missing[] = $expectedNextVal;
        $currentDiff++;
        $expectedNextVal = $expectedNextVal + $currentDiff;
      }

      $currentDiff++;
    }

    return $missing;
  }

  public function insertMissingData($arr, $missingData)
  {
    foreach ($missingData as $val) {
      $arr[] = $val;
    }

    $count = 0;
    foreach ($arr as $item) {
      $count++;
    }

    for ($i = 0; $i < $count; $i++) {
      for ($j = 0; $j < $count - $i - 1; $j++) {
        if ($arr[$j] > $arr[$j + 1]) {
          $temp = $arr[$j];
          $arr[$j] = $arr[$j + 1];
          $arr[$j + 1] = $temp;
        }
      }
    }

    return $arr;
  }

  public function main()
  {
    $a = array(11, 36, 65, 135, 98);
    $b = array();
    $b[0] = 81;
    $b[1] = 23;
    $b[2] = 50;
    $b[3] = 155;

    echo "--- Initial Data ---\n";
    echo "Array A: " . implode(", ", $a) . "\n";
    echo "Array B: " . implode(", ", $b) . "\n\n";

    $c = $this->mergeSortArray($a, $b);
    echo "--- 1. Merge & Sort Result ---\n";
    echo implode(", ", $c) . "\n\n";

    $i = $this->getMissingData($c);
    echo "--- 2. Missing Data Found ---\n";
    if (empty($i)) {
      echo "None\n\n";
    } else {
      echo implode(", ", $i) . "\n\n";
    }

    $d = $this->insertMissingData($c, $i);
    echo "--- 3. Final Result (Complete Pattern) ---\n";
    echo implode(", ", $d) . "\n";
  }
}

$t = new Test();
$t->main();
