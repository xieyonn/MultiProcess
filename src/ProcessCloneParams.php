<?php

/**
 * @brief 多进程处理同一个任务，可以传入参数，根据传入的参数数量决定执行次数
 * 
 * 规定进程数上限，父进程创建多个子进程完成任务。
 * 任务可以接受参数
 * 任务的数量根据传入参数的数量决定
 * 父进程以非阻塞运行，每5秒输出心跳
 *
 * @author xieyong <qxieyongp@163.com>
 */

namespace MultiProcess;

class ProcessCloneParams
{
    /**
     * 要执行的任务
     * @var callable
     */
    private $task;
    /**
     * 子进程数
     * @var integer
     */
    private $max_process_num = 1;
    /**
     * 当前子进程总数
     * @var integer
     */
    private $current_process_num = 0;
    /**
     * 已触发的任务总数
     * @var integer
     */
    private $task_called_num = 0;
    /**
     * 已完成任务数量
     * @var integer
     */
    private $finish_num = 0;
    /**
     * 回调函数
     * @var callable
     */
    private $call_back;
    /**
     * 参数，同时决定任务执行次数
     * @var array
     */
    private $params = [];
    /**
     * 任务数量
     * @var integer
     */
    private $task_num = 0;

    public function __construct($max_process_num, $task, $params)
    {
        if (! is_numeric($max_process_num) || $max_process_num <= 0) {
            throw new MultiProcessException('INVALIED_PROCESS_NUM', ['num' => $max_process_num]);
        }
        $this->max_process_num = $max_process_num;

        if (! is_callable($task)) {
            throw new MultiProcessException('INVALIED_CALLABLE', ['c' => $task]);
        }
        $this->task = $task;

        if (! is_array($params)) {
            throw new MultiProcessException('PARAMS_NEED_INDEXED_ARRAY', ['p' => $params]);
        }

        $this->task_num = count($params);
        if ($this->task_num === 0) {
            throw new MultiProcessException('PARAMS_EMPTY');
        }

        $this->params = array_values($params);
    }

    public function run($call_back = null)
    {
        if ($call_back !== null && !is_callable($call_back)) {
            throw new MultiProcessException('INVALIED_CALLBACK', ['p' => $call_back]);
        }
        $this->call_back = $call_back;
        $this->log("当前进程:", posix_getpid(), "任务总数:", $this->task_num, "最大进程数", $this->max_process_num);

        while (true) {
            if ($this->finish_num === $this->task_num) {
                $this->log('所有任务运行完毕');

                if ($call_back !== false) {
                    $this->log('执行回调...');
                    call_user_func($call_back);
                }
                break;
            }

            // fork失败后自动退出
            if ($this->max_process_num <= 0) {
                $this->log('子进程上限归0, 退出执行...');
                exit(1);
            }

            if ($this->current_process_num === $this->max_process_num 
                || $this->task_called_num === $this->task_num
            ) {
                // 1.当前进程数已经达到上限
                // 2.所有任务都已触发
                // 3.由于fork失败自动减少上限
                // 父进程停止fork，等待子进程退出

                $heart = 1;
                while(true) {
                    // WNOHANG 非阻塞调用让父进程保持心跳
                    $rtv = pcntl_wait($status, WNOHANG);

                    if ($rtv == 0 ) {
                        usleep(100000);
                        $heart++;
                        if ($heart === 50) {
                            // 每5s输出一次心跳
                            $this->log("当前子进程数", $this->current_process_num, 
                                "已完成任务数", $this->finish_num, '等待子进程退出...'
                            );
                            $heart = 1;
                        } 
                        continue;
                    }

                    if ($rtv == -1) {
                        $this->log('等待子进程退出异常');
                    }

                    if ($rtv > 0) {
                        $this->log("进程", $rtv, "退出");
                    }

                    // 释放一个子进程名额
                    $this->current_process_num--;
                    $this->finish_num++;
                    break;
                }
            } else {
                $pid = pcntl_fork();
                $this->current_process_num++;
                
                $param = array_shift($this->params);
                // 记录触发任务数
                $this->task_called_num++;

                if ($pid == -1) {
                    $this->log('fork 失败');
                    $this->max_process_num--; // 失败则减少进程上限防止卡在这里

                    // 把参数塞回去
                    $this->task_called_num--;
                    array_unshift($this->params, $param);
                }

                if ($pid == 0) {
                    // 子进程
                    $rand = rand(1, 5);
                    usleep($rand * 100000); // 随机等待，避免同时刻运行
                    
                    $this->log('子进程', posix_getpid(), '开始运行');
                    try {
                        call_user_func_array($this->task, $param);
                        exit(0);
                    } catch (\Exception $e) {
                        throw new MultiProcessException('EXCEPTION_CATCH', [], $e);
                        exit(1);
                    }
                }
            }
        }
    }

    protected function log(...$msg)
    {
        echo "[" . date('Y-m-d H:i:s') . "] " . implode(" ", $msg) . "\n";
    }
}
