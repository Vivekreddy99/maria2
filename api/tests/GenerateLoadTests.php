<?php

require_once('bootstrap.php');

$is_test = isset($_SERVER) && isset($_SERVER['APP_ENV']) && ($_SERVER['APP_ENV'] == 'dev' || $_SERVER['APP_ENV'] == 'test');

$is_shell = isset($_SERVER) && isset($_SERVER['TERM_PROGRAM']) && $_SERVER['TERM_PROGRAM'] == 'Apple_Terminal';

// Allow if running locally via shell.
if ($is_test && $is_shell) {

  // Number of users to test with 50 transactions each.
  $num=100;

  for($i=1;$i<$num;$i++) {

    // Create directory if not present.
    if (!file_exists("Load/" . $i)) {
        mkdir("Load/" . $i, 0744);
    }

    // Customize unit test file.
    $txt = file_get_contents("template.inc");
    $txt = str_replace("111", "111" . $i, $txt);
    $txt = "<?php" . PHP_EOL . PHP_EOL . "namespace App\Test\Load\d" . $i . ";" . PHP_EOL . PHP_EOL . $txt;
    file_put_contents("Load/" . $i . "/TransactionResourceTest.php", $txt);
  }
}
