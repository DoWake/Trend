<?php

namespace app\common\hotlist;

use app\common\Request;

/**
 * 微博
 */
class Weibo
{
  /**
   * 热搜榜
   *
   * @return array
   */
  public static function getHotSearch()
  {
    $options = [
      'url' => 'https://weibo.com/ajax/side/hotSearch',
      'ua' => 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.3',
      'referer' => 'https://weibo.com/'
    ];
    $result = Request::singleCurl($options);
    try {
      if ($result['info']['http_code'] != 200 || empty($result['result'])) throw new \Exception('请求异常');
      $content = json_decode($result['result'], true);
      if (!isset($content['data']['realtime']) || !is_array($content['data']['realtime'])) throw new \Exception('内容异常');
      $data = [];
      $rank = 1;
      foreach ($content['data']['realtime'] as $value) {
        if (isset($value['is_ad'])) continue;
        $data[] = [
          'title' => $value['word'],
          'url' => 'https://s.weibo.com/weibo?q=' . urlencode($value['word_scheme']),
          'rank' => $rank++,
          'score' => $value['num'],
          'label' => $value['label_name']
        ];
      }
      return ['code' => 1, 'msg' => '获取成功', 'data' => $data];
    } catch (\Exception $e) {
      return ['code' => -1, 'msg' => $e->getMessage()];
    }
  }
}
