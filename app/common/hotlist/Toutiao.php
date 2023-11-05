<?php

namespace app\common\hotlist;

use app\common\Request;

/**
 * 头条热榜
 */
class Toutiao
{
  /**
   * 热榜
   *
   * @return array
   */
  public static function getHotBoard()
  {
    $options = [
      'url' => 'https://www.toutiao.com/hot-event/hot-board/?origin=toutiao_pc',
      'ua' => 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Mobile Safari/537.36',
      'referer' => 'https://www.toutiao.com/'
    ];
    $result = Request::singleCurl($options);
    try {
      if ($result['info']['http_code'] != 200 || empty($result['result'])) throw new \Exception('请求异常');
      $content = json_decode($result['result'], true);
      if (!isset($content['data']) || !is_array($content['data'])) throw new \Exception('内容异常');
      $data = [];
      $rank = 1;
      foreach ($content['data'] as $value) {
        switch ($value['Label']) {
          case 'new':
            $label = '新';
            break;
          case 'hot':
            $label = '热';
            break;
          case 'interpretation':
            $label = '解读';
            break;
          case 'refuteRumors':
            $label = '辟谣';
            break;
          default:
            $label = $value['LabelDesc'];
            break;
        }
        $data[] = array(
          'title' => $value['Title'],
          'url' => $value['Url'],
          'rank' => $rank++,
          'score' => $value['HotValue'],
          'label' => $label
        );
      }
      return ['code' => 1, 'msg' => '获取成功', 'data' => $data];
    } catch (\Exception $e) {
      return ['code' => -1, 'msg' => $e->getMessage()];
    }
  }
}
