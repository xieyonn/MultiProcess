<?php

use MultiProcess\ProcessClone;
use MultiProcess\ProcessCloneParams;


require "./src/MultiProcessException.php";
require "./src/ProcessClone.php";
require "./src/ProcessCloneParams.php";


echo "ProcessClone 演示\n";
$call = function () {
    sleep(3);
    $pid = posix_getpid();
    echo "我是子进程{$pid}\n";
};
$p = new ProcessClone(2, $call);
$p->run(function () {
    echo "我是回调函数\ndone\n";
});

echo "\n\n";
echo "*************************************\n";
echo "ProcessCloneParams 演示\n";
$call = function ($begin, $end) {
    sleep(2);
    $pid = posix_getpid();
    echo "我是子进程{$pid} {$begin} ~ {$end}\n";
};

$params = [
    [
        'begin' => '2018-01-01',
        'end' => '2018-01-02',
    ],
    [
        'begin' => '2018-01-02',
        'end' => '2018-01-03',
    ],
    [
        'begin' => '2018-01-03',
        'end' => '2018-01-04',
    ],
    [
        'begin' => '2018-01-04',
        'end' => '2018-01-05',
    ],
    [
        'begin' => '2018-01-05',
        'end' => '2018-01-06',
    ],

];
$p = new ProcessCloneParams(3, $call, $params);
$p->run(function() {
    echo "我是回调函数\ndone\n";
});

echo "\n";
echo '进程继续执行...';
