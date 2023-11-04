<?php

namespace app\common\crontab;

use think\facade\Db;
use Workerman\Connection\AsyncTcpConnection;
use Workerman\Lib\Timer as WorkermanTimer;
use Workerman\Worker;

/**
 * 定时任务 - 计时器
 */
class Timer
{
  public static function run()
  {
    $timer_worker = new Worker();
    $timer_worker->name = 'CrontabTimer';
    $timer_worker->count = 1;
    $timer_worker->onWorkerStart = function (Worker $timer_worker) {
      // 定时器执行时间间隔。
      $time_interval = 1;
      WorkermanTimer::add($time_interval, function () {
        $timestamp = time();
        $list = Db::table('crontab')->field('id,title,interval,action')->where('status', 1)->select()->toArray();
        foreach ($list as $item) {
          if ($item['interval'] > 0 && $timestamp % $item['interval'] == 0) {
            // 建立TCP异步连接
            $task_connection = new AsyncTcpConnection('Text://127.0.0.1:15550');
            $item['time'] = date('Y-m-d H:i:s');
            $task_data = json_encode($item);
            $task_connection->send($task_data);
            $task_connection->onMessage = function (AsyncTcpConnection $task_connection, $task_result) use ($item) {
              // 有数据返回，执行次数 + 1
              Db::table('crontab')->where('id', $item['id'])->inc('times')->update();
              $task_result = json_decode($task_result, true);
              // 结果异常，记录日志
              if ($task_result['code'] != 1) {
                $data = [
                  'title' => $item['title'],
                  'content' => $task_result['msg'],
                  'add_time' => date('Y-m-d H:i:s')
                ];
                Db::table('crontab_log')->insert($data);
              }
              // 关闭连接
              $task_connection->close();
            };
            // 连接
            $task_connection->connect();
          }
        }
        echo date('Y-m-d H:i:s') . PHP_EOL;
      });
    };
    // 检测启动模式
    if (!defined('GLOBAL_START')) {
      Worker::runAll();
    }
  }
}
