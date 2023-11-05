<?php

namespace app\common\hotlist;

use app\common\Request;

/**
 * UC浏览器
 */
class Uc
{
  /**
   * UC热榜
   *
   * @return array
   */
  public static function getHotRank()
  {
    $options = [
      'url' => 'https://iflow-api.uc.cn/hot_rank/list?page=0&size=50',
      'ua' => 'Mozilla/5.0 (Linux; Android 6.0; Nexus 5 Build/MRA58N) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/80.0.3987.87 Mobile Safari/537.36',
      'referer' => 'https://pages.uc.cn/r/uc-hotcomment/UcHotcommentNewPageHotSearchRank?uc_biz_str=S:custom%7CC:full_screen'
    ];
    $result = Request::singleCurl($options);
    try {
      if ($result['info']['http_code'] != 200 || empty($result['result'])) throw new \Exception('请求异常');
      $content = json_decode($result['result'], true);
      if (!isset($content['data']['rank_list']) || !is_array($content['data']['rank_list'])) throw new \Exception('内容异常');
      $data = [];
      $rank = 1;
      foreach ($content['data']['rank_list'] as $value) {
        if ($value['is_ad'] == 1 || $value['is_govtop'] == 1) continue;
        if ($value['is_hot'] == 1) {
          $tag = '热';
        } else if ($value['is_new'] == 1) {
          $tag = '新';
        } else {
          $tag = '';
        }
        $data[] = array(
          'title' => $value['title'],
          'url' => $value['url'],
          'cover' => $value['news_image'],
          'summary' => $value['news_summary'],
          'rank' => $rank++,
          'score' => $value['hot'],
          'label' => $tag
        );
      }
      return ['code' => 1, 'msg' => '获取成功', 'data' => $data];
    } catch (\Exception $e) {
      return ['code' => -1, 'msg' => $e->getMessage()];
    }
  }
}
