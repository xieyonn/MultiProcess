# PHP 多进程模型

## 安装
```
composer require "xieyong1023/MultiProcess"
```

## 示例
`ProcessClone`示例

规定子进程数量，主进程同时创建多个子进程执行同一个任务

可以传入回调函数，全部任务执行完后执行

```php
use MultiProcess\ProcessClone;

$mum = 2; // 子进程数量
$call = function () { // 任务以闭包的形式传入
    sleep(5);
    echo "hello";
};
$p = new ProcessClone($num, $call);

$callback = function() {
    echo 'done';
};
// 指定callback，任务执行完后执行
$p->run($callback);

...
// 主进程继续执行...
```

`ProcessCloneParams`示例

规定子进程数量`上限`，接受`数组`形式的任务参数，每个参数会传入给任务。
任务执行次数取决于参数数组大小。

可以传入回调函数，全部任务执行完后执行

```php
use MultiProcess\ProcessClone;

$num = 3;
$call = function ($begin, $end) {
    sleep(5);
    echo "{$begin} ~ {$end}\n";
};

// 任务参数(索引数组) key => name 分别代码传给任务闭包的参数名、参数值
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
$p = new ProcessCloneParams($num, $call, $params);
$p->run(function() {
    echo 'done';
});

...
// 主进程继续执行...
```

## 示例脚本
执行example.php文件
```bash
cd '当前目录'
php example.php
```
