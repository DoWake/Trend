<?php

/**
 * PHP网络请求类
 * 
 * 对cURL提供的功能进行简单封装
 * 支持单个请求、并发请求。
 * 
 * @author DoWake
 * @version 1.0
 */

namespace app\common;

class Request
{
  /**
   * 默认通用cURL配置选项
   */
  private static $defaultOptions = [
    CURLOPT_SSL_VERIFYHOST => 0, //不验证SSL。
    CURLOPT_SSL_VERIFYPEER => 0, //不验证SSL。
    CURLOPT_RETURNTRANSFER => true, //以字符串返回。
    CURLOPT_ENCODING => 'gzip', //HTTP请求头中"Accept-Encoding: "的值。
    CURLOPT_CONNECTTIMEOUT => 5 //在尝试连接时等待的秒数。
  ];
  /**
   * 配置选项别名
   * 可根据个人需求自行添加
   */
  private static $alias = [
    'url' => CURLOPT_URL, //需要获取的URL地址
    'post' => CURLOPT_POST, //发送POST请求
    'fields' => CURLOPT_POSTFIELDS, //POST请求内容
    'postfields' => CURLOPT_POSTFIELDS, //POST请求内容
    'httpheader' => CURLOPT_HTTPHEADER, //HTTP请求头
    'header' => CURLOPT_HEADER, //是否输出响应头
    'ua' => CURLOPT_USERAGENT, //设置"User-Agent: "头的字符串
    'useragent' => CURLOPT_USERAGENT, //设置"User-Agent: "头的字符串
    'cookie' => CURLOPT_COOKIE, //设置"Cookie: "头的字符串
    'referer' => CURLOPT_REFERER, //设置"Referer: "头的字符串
    'location' => CURLOPT_FOLLOWLOCATION //跟随重定向跳转
  ];

  /**
   * 添加配置选项别名
   * @param array $alias 配置选项数组：键是别名，值是原名
   * @return boolean true|false 添加是否成功
   */
  public static function addAlias($alias)
  {
    if (is_array($alias)) {
      self::$alias = $alias + self::$alias;
      return true;
    } else {
      return false;
    }
  }
  /**
   * 格式化CURL配置选项
   * @param array $options 要格式化的配置选项
   * @return array 已格式化的配置选项 
   */
  private static function formatOptions($options)
  {
    if (is_array($options)) {
      $options2 = [];
      foreach ($options as $key => $value) {
        if (is_string($key) && isset(self::$alias[$key])) {
          $options2[self::$alias[$key]] = $value;
        } else if (is_int($key)) {
          $options2[$key] = $value;
        }
      }
      return $options2 + self::$defaultOptions; //覆盖默认配置
    } else {
      return self::$defaultOptions;
    }
  }
  /**
   * 执行单个cURL请求
   * @param array $options 配置选项
   * @return array 执行结果关联数组：result结果字符串、info执行信息、error失败提示
   */
  public static function singleCurl($options)
  {
    $options = self::formatOptions($options);
    $ch = curl_init();
    curl_setopt_array($ch, $options);
    $result = curl_exec($ch);
    $info = curl_getinfo($ch);
    $error = curl_error($ch);
    curl_close($ch);
    return array('result' => $result, 'info' => $info, 'error' => $error);
  }
  /**
   * 执行并发cURL请求
   * @param array $optionList 配置选项数组
   * @return array 执行结果关联数组列表：result结果字符串、info执行信息、error失败提示
   */
  public static function multiCurl($optionsArr)
  {
    $mh = curl_multi_init();
    $handles = [];
    foreach ($optionsArr as $key => $options) {
      $options = self::formatOptions($options);
      $ch = curl_init();
      curl_setopt_array($ch, $options);
      curl_multi_add_handle($mh, $ch);
      $handles[$key] = $ch;
    }
    $active = null;
    do {
      curl_multi_exec($mh, $active);
    } while ($active);
    $resultList = [];
    foreach ($handles as $key => $ch) {
      $result = curl_multi_getcontent($ch);
      $info = curl_getinfo($ch);
      $error = curl_error($ch);
      $resultList[$key] = array('result' => $result, 'info' => $info, 'error' => $error);
      curl_multi_remove_handle($mh, $ch);
    }
    curl_multi_close($mh);
    return $resultList;
  }
}
