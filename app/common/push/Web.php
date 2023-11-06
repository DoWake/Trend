<?php

namespace app\common\push;

use think\facade\Db;
use Workerman\Connection\TcpConnection;
use Workerman\Worker;

/**
 * 数据推送 - Web网页推送
 */
class Web
{
  public static function run()
  {
    $web_worker = new Worker('websocket://0.0.0.0:15560');
    $web_worker->name = 'PushWeb';
    $web_worker->count = 1;
    $web_worker->onWorkerStart = function (Worker $web_worker) {
      // 创建内部通知进程
      $notice_worker = new Worker('Text://0.0.0.0:15551');
      $notice_worker->onMessage = function (TcpConnection $connection, $data) use ($web_worker) {
        $data = json_decode($data, true);
        // 组装数据
        $result = [
          'code' => 1100,
          'msg' => '推送信息',
          'data' => $data
        ];
        $json_result = json_encode($result);
        // 将新的数据推送给所有的客户端
        foreach ($web_worker->connections as $conn) {
          $conn->send($json_result);
        }
      };
      $notice_worker->listen();
    };
    // 建立WebSocket连接时
    $web_worker->onWebSocketConnect = function (TcpConnection $connection, $request) {
      $result = [
        'code' => 200,
        'msg' => '连接成功',
      ];
      $connection->send(json_encode($result));
    };
    // 处理客户端发来的信息
    $web_worker->onMessage = function (TcpConnection $connection, $data) {
      try {
        // 解析客户端请求
        $data = json_decode($data, true);
        switch ($data['act']) {
          case 'getBoardList':
            // 获取榜单列表
            $list = Db::table('hotlist')->field('id,action,title')->where('status', 1)->select()->toArray();
            $result = [
              'code' => 1001,
              'msg' => '获取成功',
              'data' => $list
            ];
            break;
          case 'getBoardData':
            // 获取榜单数据
            $info = Db::table('hotlist')->field('data,updated_at')->where('status', 1)->where('action', $data['action'])->find();
            $result = [
              'code' => 1002,
              'msg' => '获取成功',
              'data' => [
                'action' => $data['action'],
                'list' => json_decode($info['data'], true),
                'updated_at' => $info['updated_at']
              ]
            ];
            break;
          default:
            $result = [
              'code' => 1000,
              'msg' => '操作未知'
            ];
            break;
        }
      } catch (\Throwable $th) {
        $result = ['code' => 0, 'msg' => $th->getMessage()];
      } finally {
        $connection->send(json_encode($result));
      }
    };
    // 检测启动模式
    if (!defined('GLOBAL_START')) {
      Worker::runAll();
    }
  }
}
