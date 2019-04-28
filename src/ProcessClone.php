<?php

/**
 * @brief 多进程处理同一个任务
 * 
 * 规定创建的进程数，父进程同时创建多个子进程同时执行一个任务。
 * 父进程负责回收子进程资源，可以选择父进程以阻塞或非阻塞方式执行，在非阻塞方式下，父进程会输出心跳信息。
 *
 * @author xieyong <qxieyongp@163.com>
 */

namespace MultiProcess;

class ProcessClone
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
     * 已运行结束的子进程总数
     * @var integer
     */
    private $finish_num = 0;
    /**
     * 配置
     * @var array
     */
    private $option = [
        'heartbeat' => true, // 主进程是否显示心跳
    ];
    /**
     * 回调函数
     * @var callable
     */
    private $call_back;

    public function __construct($max_process_num, $task, $option = [])
    {
        if (!is_numeric($max_process_num) || $max_process_num <= 0) {
            throw new MultiProcessException('INVALIED_PROCESS_NUM', ['p' => $max_process_num]);
        }
        $this->max_process_num = $max_process_num;

        if (!is_callable($task)) {
            throw new MultiProcessException('INVALIED_CALLABLE', ['p' => $task]);
        }
        $this->task = $task;

        if (!empty($option)) {
            $this->option = array_merge($this->option, $option);
        }
    }

    public function run($call_back = null)
    {
        if ($call_back !== null && !is_callable($call_back)) {
            throw new MultiProcessException('INVALIED_CALLBACK', ['p' => $call_back]);
        }
        $this->call_back = $call_back;
        $this->log("当前进程ID", posix_getpid(), "将要fork", $this->max_process_num, "个子进程...");

        if ($this->max_process_num === 1) {
            call_user_func($this->task);
            exit(0);
        }

        for ($i = 0; $i < $this->max_process_num; $i++) {
            $pid = pcntl_fork();

            if ($pid == -1) {
                $this->log('fork 失败');
                // 等待一会儿后再尝试
                usleep(100000);
            }

            if ($pid == 0) {
                // 子进程
                // 随机等待，避免同时刻运行。
                $rand = rand(1, 5);
                usleep($rand * 200000);
                try {
                    call_user_func($this->task);
                    exit(0); // 子进程执行完后退出
                } catch (\Exception $e) {
                    throw new MultiProcessException('EXCEPTION_CATCH', [], $e);
                    exit(1);
                }
            }

            if ($pid > 0) {
                // 父进程
                if (++$this->current_process_num < $this->max_process_num) {
                    // 继续fork
                    continue;
                }

                if ($this->option['heartbeat'] === true) {
                    $this->noBlock();
                } else {
                    $this->block();
                }
            }
        }
    }

    /**
     * 非阻塞等待
     * @return void
     */
    private function noBlock()
    {
        $status;
        $time = 1;
        while (true) {
            $rtv = pcntl_wait($status, WNOHANG);

            if ($rtv === 0) {
                $time++;
                if ($time === 100) {
                    $this->log('当前子进程数', $this->current_process_num, 
                        '已完成任务数', $this->finish_num, '等待子进程退出...'
                    );
                    $time = 1;
                }
            }

            if ($rtv === -1) {
                $this->log('等待子进程退出异常');
            }

            if ($rtv > 0) {
                $this->log("进程", $rtv, "退出", "退出status:", $status);
                $this->finish_num++;
            }

            if ($this->finish_num === $this->max_process_num) {
                $this->log("所有进程执行完毕");

                if ($this->call_back !== null) {
                    $this->log('执行回调');
                    call_user_func($this->call_back);
                }
                break;
            }

            usleep(10000);
        }
    }

    /**
     * 阻塞等待
     * @return void
     */
    private function block()
    {
        $status;
        while (true) {
            $rtv = pcntl_wait($status, WUNTRACED);
            $this->finish_num++;

            if ($rtv == -1) {
                $this->log('等待子进程退出异常');
            }

            if ($rtv > 0) {
                $this->log("进程", $rtv, "退出", "退出status:", $status);
            }

            if ($this->finish_num == $this->max_process_num) {
                $this->log("所有进程执行完毕");

                if ($this->call_back !== null) {
                    call_user_func($this->call_back);
                }
                break;
            }
        }
    }

    protected function log(...$msg)
    {
        echo "[" . date('Y-m-d H:i:s') . "] " . implode(" ", $msg) . "\n";
    }
}
