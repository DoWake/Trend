<?php

namespace app\common\crontab;

use Workerman\Connection\TcpConnection;
use Workerman\Worker;

/**
 * 定时任务 - 处理进程
 */
class Task
{
  public static function run()
  {
    $task_worker = new Worker('Text://0.0.0.0:15550');
    $task_worker->name = 'CrontabTask';
    $task_worker->count = 10;
    $task_worker->onMessage = function (TcpConnection $connection, $task_data) {
      $current_time = date('Y-m-d H:i:s');
      $task_data = json_decode($task_data, true);
      $action = new Action;
      try {
        if (empty($task_data['action']) || !method_exists($action, $task_data['action'])) {
          $task_result = [
            'code' => 0,
            'msg' => '操作未知'
          ];
        } else {
          $func = $task_data['action'];
          $action_result = $action->$func();
          if ($action_result['code'] == 1) {
            $task_result = [
              'code' => 1,
              'msg' => '执行成功'
            ];
          } else {
            $task_result = [
              'code' => -1,
              'msg' => $current_time . '【' . $action_result['msg'] . '】'
            ];
          }
        }
      } catch (\Throwable $th) {
        $task_result = [
          'code' => -1,
          'msg' => $current_time . '【' . $th->getMessage() . '】'
        ];
      } finally {
        $connection->send(json_encode($task_result));
      }
    };
    // 检测启动模式
    if (!defined('GLOBAL_START')) {
      Worker::runAll();
    }
  }
}
