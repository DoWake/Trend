<?php

namespace app\common\crontab;

use app\common\hotlist\Baidu;
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
      return $res;
    }
  }
}
