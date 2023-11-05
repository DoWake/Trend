<?php

namespace app\common\hotlist;

use app\common\Request;

/**
 * 百度热搜
 */
class Baidu
{
  /**
   * 热搜榜
   *
   * @return array
   */
  public static function getRealtime()
  {
    $options = [
      'url' => 'https://top.baidu.com/api/board?platform=wise&tab=realtime&tag=%7B%7D',
      'ua' => 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/110.0.0.0 Safari/537.3',
      'referer' => 'https://top.baidu.com/board?tab=realtime'
    ];
    $result = Request::singleCurl($options);
    try {
      if ($result['info']['http_code'] != 200 || empty($result['result'])) throw new \Exception('请求异常');
      $content = json_decode($result['result'], true);
      if (!isset($content['data']['cards'][0]['content']) || !is_array($content['data']['cards'][0]['content'])) throw new \Exception('内容异常');
      $data = [];
      $rank = 1;
      foreach ($content['data']['cards'][0]['content'] as $value) {
        if ($value['hotTag'] == 3) {
          $tag = '热';
        } else if ($value['hotTag'] == 1) {
          $tag = '新';
        } else {
          $tag = '';
        }
        $data[] = array(
          'title' => $value['word'],
          'url' => $value['url'],
          'cover' => $value['img'],
          'summary' => $value['desc'],
          'rank' => $rank++,
          'score' => $value['hotScore'],
          'label' => $tag
        );
      }
      return ['code' => 1, 'msg' => '获取成功', 'data' => $data];
    } catch (\Exception $e) {
      return ['code' => -1, 'msg' => $e->getMessage()];
    }
  }
}
