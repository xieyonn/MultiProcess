<?php

namespace MultiProcess;

use Exception;

class MultiProcessException extends Exception
{
    protected $code_map = [
        'PARAMS_NEED_INDEXED_ARRAY' => ['code' => 9001, 'msg' => '需要传入索引数组 {p}'],
        'PARAMS_EMPTY' => ['code' => 9002, 'msg' => '参数为空'],
        'EXCEPTION_CATCH' => ['code' => 9003, 'msg' => '捕获到子进程执行异常'],
        'INVALIED_CALLABLE' => ['code' => 9004, 'msg' => '任务必须是可调用闭包类型 {p}'],
        'INVALIED_PROCESS_NUM' => ['code' => 9005, 'msg' => '最大进程数参数错误 {p}'],
        'INVALIED_CALLBACK' => ['code' => 9006, 'msg' => '回调函数必须是可调用闭包 {p}'],
    ];
    
    public function __construct($id, $params = [], $previous = null)
    {
        $map = $this->code_map[$id];

        $msg = $map['msg'];
        if (! empty($params)) {
            foreach ($params as $key => $val) {
                $msg = str_replace('{' . $key . '}', var_export($val, true), $msg);
            }
        }
        
        parent::__construct($msg, $map['code'], $previous);
    }
}
