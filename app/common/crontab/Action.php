<?php

namespace app\common\crontab;

use app\common\hotlist\Baidu;
use app\common\hotlist\Toutiao;
use app\common\hotlist\Uc;
use app\common\hotlist\Weibo;
use think\facade\Cache;
use think\facade\Db;

/**
 * 定时任务 - 执行动作
 */
class Action
{
  /**
   * 执行获取百度热搜榜任务
   *
   * @return array
   */
  public function getBaiduRealtime()
  {
    $result = Baidu::getRealtime();
    if ($result['code'] != 1) {
      $result = Baidu::getRealtime();
    }
    $action = __FUNCTION__;
    $latest_data = $result['data'];
    $cache_data = Cache::get($action);
    if ($cache_data && $cache_data == json_encode($latest_data)) {
      $res = [
        'code' => 1,
        'msg' => '数据一致'
      ];
      return $res;
    } else {
      // 刷新缓存
      Cache::set($action, json_encode($latest_data), 3600);
      // 记录到数据库
      Db::table('hotlist')
        ->where('action', $action)
        ->update([
          'data' => json_encode($latest_data, JSON_UNESCAPED_UNICODE),
          'updated_at' => date('Y-m-d H:i:s')
        ]);
      $res = [
        'code' => 1,
        'msg' => '执行成功'
      ];

      // 推送数据
      $push_data = [
        'action' => $action,
        'list' => $latest_data,
        'updated_at' => date('Y-m-d H:i:s')
      ];
      $client = stream_socket_client('tcp://127.0.0.1:15551', $errno, $errmsg, 1);
      fwrite($client, json_encode($push_data) . "\n");

      return $res;
    }
  }

  /**
   * 执行获取头条热榜任务
   *
   * @return array
   */
  public function getToutiaoHotBoard()
  {
    $result = Toutiao::getHotBoard();
    if ($result['code'] != 1) {
      $result = Toutiao::getHotBoard();
    }
    $action = __FUNCTION__;
    $latest_data = $result['data'];
    $cache_data = Cache::get($action);
    if ($cache_data && $cache_data == json_encode($latest_data)) {
      $res = [
        'code' => 1,
        'msg' => '数据一致'
      ];
      return $res;
    } else {
      // 刷新缓存
      Cache::set($action, json_encode($latest_data), 3600);
      // 记录到数据库
      Db::table('hotlist')
        ->where('action', $action)
        ->update([
          'data' => json_encode($latest_data, JSON_UNESCAPED_UNICODE),
          'updated_at' => date('Y-m-d H:i:s')
        ]);
      $res = [
        'code' => 1,
        'msg' => '执行成功'
      ];

      // 推送数据
      $push_data = [
        'action' => $action,
        'list' => $latest_data,
        'updated_at' => date('Y-m-d H:i:s')
      ];
      $client = stream_socket_client('tcp://127.0.0.1:15551', $errno, $errmsg, 1);
      fwrite($client, json_encode($push_data) . "\n");

      return $res;
    }
  }

  /**
   * 执行获取微博热搜榜的任务
   *
   * @return void
   */
  public function getWeiboHotSearch()
  {
    $result = Weibo::getHotSearch();
    if ($result['code'] != 1) {
      $result = Weibo::getHotSearch();
    }
    $action = __FUNCTION__;
    $latest_data = $result['data'];
    $cache_data = Cache::get($action);
    if ($cache_data && $cache_data == json_encode($latest_data)) {
      $res = [
        'code' => 1,
        'msg' => '数据一致'
      ];
      return $res;
    } else {
      // 刷新缓存
      Cache::set($action, json_encode($latest_data), 3600);
      // 记录到数据库
      Db::table('hotlist')
        ->where('action', $action)
        ->update([
          'data' => json_encode($latest_data, JSON_UNESCAPED_UNICODE),
          'updated_at' => date('Y-m-d H:i:s')
        ]);
      $res = [
        'code' => 1,
        'msg' => '执行成功'
      ];

      // 推送数据
      $push_data = [
        'action' => $action,
        'list' => $latest_data,
        'updated_at' => date('Y-m-d H:i:s')
      ];
      $client = stream_socket_client('tcp://127.0.0.1:15551', $errno, $errmsg, 1);
      fwrite($client, json_encode($push_data) . "\n");

      return $res;
    }
  }

  /**
   * 执行获取UC热榜的任务
   *
   * @return array
   */
  public function getUcHotRank()
  {
    $result = Uc::getHotRank();
    if ($result['code'] != 1) {
      $result = Uc::getHotRank();
    }
    $action = __FUNCTION__;
    $latest_data = $result['data'];
    $cache_data = Cache::get($action);
    if ($cache_data && $cache_data == json_encode($latest_data)) {
      $res = [
        'code' => 1,
        'msg' => '数据一致'
      ];
      return $res;
    } else {
      // 刷新缓存
      Cache::set($action, json_encode($latest_data), 3600);
      // 记录到数据库
      Db::table('hotlist')
        ->where('action', $action)
        ->update([
          'data' => json_encode($latest_data, JSON_UNESCAPED_UNICODE),
          'updated_at' => date('Y-m-d H:i:s')
        ]);
      $res = [
        'code' => 1,
        'msg' => '执行成功'
      ];

      // 推送数据
      $push_data = [
        'action' => $action,
        'list' => $latest_data,
        'updated_at' => date('Y-m-d H:i:s')
      ];
      $client = stream_socket_client('tcp://127.0.0.1:15551', $errno, $errmsg, 1);
      fwrite($client, json_encode($push_data) . "\n");

      return $res;
    }
  }
}
