<?php

declare(strict_types=1);

namespace app\library\cache\driver;

use think\cache\Driver;

class Redis extends Driver
{
  protected $handler;
  protected $options = [
    'host'       => '127.0.0.1',
    'port'       => 6379,
    'password'   => '',
    'select'     => 0,
    'timeout'    => 0,
    'expire'     => 0,
    'persistent' => false,
    'break_reconnect' => false,
    'prefix'     => '',
    'tag_prefix' => 'tag:',
    'serialize'  => [],
  ];
  /**
   * 架构函数
   * @access public
   * @param array $options 缓存参数
   */
  public function __construct(array $options = [])
  {
    if (!empty($options)) {
      $this->options = array_merge($this->options, $options);
    }

    if (extension_loaded('redis')) {
      $this->handler = new \Redis;

      if ($this->options['persistent']) {
        $this->handler->pconnect($this->options['host'], (int) $this->options['port'], (int) $this->options['timeout'], 'persistent_id_' . $this->options['select']);
      } else {
        $this->handler->connect($this->options['host'], (int) $this->options['port'], (int) $this->options['timeout']);
      }

      if ('' != $this->options['password']) {
        $this->handler->auth($this->options['password']);
      }
    } elseif (class_exists('\Predis\Client')) {
      $params = [];
      foreach ($this->options as $key => $val) {
        if (in_array($key, ['aggregate', 'cluster', 'connections', 'exceptions', 'prefix', 'profile', 'replication', 'parameters'])) {
          $params[$key] = $val;
          unset($this->options[$key]);
        }
      }

      if ('' == $this->options['password']) {
        unset($this->options['password']);
      }

      $this->handler = new \Predis\Client($this->options, $params);

      $this->options['prefix'] = '';
    } else {
      throw new \BadFunctionCallException('not support: redis');
    }

    if (0 != $this->options['select']) {
      $this->handler->select((int) $this->options['select']);
    }
  }

  /**
   * 创建Redis实例
   *
   * @return void
   */
  private function setHandler()
  {
    if (extension_loaded('redis')) {
      $this->handler = new \Redis;

      if ($this->options['persistent']) {
        $this->handler->pconnect($this->options['host'], (int) $this->options['port'], (int) $this->options['timeout'], 'persistent_id_' . $this->options['select']);
      } else {
        $this->handler->connect($this->options['host'], (int) $this->options['port'], (int) $this->options['timeout']);
      }

      if ('' != $this->options['password']) {
        $this->handler->auth($this->options['password']);
      }
    } elseif (class_exists('\Predis\Client')) {
      $params = [];
      foreach ($this->options as $key => $val) {
        if (in_array($key, ['aggregate', 'cluster', 'connections', 'exceptions', 'prefix', 'profile', 'replication', 'parameters'])) {
          $params[$key] = $val;
          unset($this->options[$key]);
        }
      }

      if ('' == $this->options['password']) {
        unset($this->options['password']);
      }

      $this->handler = new \Predis\Client($this->options, $params);

      $this->options['prefix'] = '';
    } else {
      throw new \BadFunctionCallException('not support: redis');
    }

    if (0 != $this->options['select']) {
      $this->handler->select((int) $this->options['select']);
    }
  }

  /**
   * 判断缓存
   * @access public
   * @param string $name 缓存变量名
   * @return bool
   */
  public function has($name): bool
  {
    try {
      return $this->handler->exists($this->getCacheKey($name)) ? true : false;
    } catch (\Throwable $th) {
      $this->setHandler();
      return $this->handler->exists($this->getCacheKey($name)) ? true : false;
    }
  }

  /**
   * 读取缓存
   * @access public
   * @param string $name    缓存变量名
   * @param mixed  $default 默认值
   * @return mixed
   */
  public function get($name, $default = null)
  {
    try {
      $this->readTimes++;
      $key   = $this->getCacheKey($name);
      $value = $this->handler->get($key);
      if (false === $value || is_null($value)) {
        return $default;
      }
      return $this->unserialize($value);
    } catch (\Throwable $th) {
      $this->setHandler();
      $value = $this->handler->get($key);
      if (false === $value || is_null($value)) {
        return $default;
      }
      return $this->unserialize($value);
    }
  }

  /**
   * 写入缓存
   * @access public
   * @param string            $name   缓存变量名
   * @param mixed             $value  存储数据
   * @param integer|\DateTime $expire 有效时间（秒）
   * @return bool
   */
  public function set($name, $value, $expire = null): bool
  {
    try {
      $this->writeTimes++;
      if (is_null($expire)) {
        $expire = $this->options['expire'];
      }
      $key    = $this->getCacheKey($name);
      $expire = $this->getExpireTime($expire);
      $value  = $this->serialize($value);
      if ($expire) {
        $this->handler->setex($key, $expire, $value);
      } else {
        $this->handler->set($key, $value);
      }
      return true;
    } catch (\Throwable $th) {
      $this->setHandler();
      if ($expire) {
        $this->handler->setex($key, $expire, $value);
      } else {
        $this->handler->set($key, $value);
      }
      return true;
    }
  }

  /**
   * 自增缓存（针对数值缓存）
   * @access public
   * @param string $name 缓存变量名
   * @param int    $step 步长
   * @return false|int
   */
  public function inc(string $name, int $step = 1)
  {
    try {
      $this->writeTimes++;
      $key = $this->getCacheKey($name);
      return $this->handler->incrby($key, $step);
    } catch (\Throwable $th) {
      $this->setHandler();
      return $this->handler->incrby($key, $step);
    }
  }

  /**
   * 自减缓存（针对数值缓存）
   * @access public
   * @param string $name 缓存变量名
   * @param int    $step 步长
   * @return false|int
   */
  public function dec(string $name, int $step = 1)
  {
    try {
      $this->writeTimes++;
      $key = $this->getCacheKey($name);
      return $this->handler->decrby($key, $step);
    } catch (\Throwable $th) {
      $this->setHandler();
      return $this->handler->decrby($key, $step);
    }
  }

  /**
   * 删除缓存
   * @access public
   * @param string $name 缓存变量名
   * @return bool
   */
  public function delete($name): bool
  {
    try {
      $this->writeTimes++;
      $key    = $this->getCacheKey($name);
      $result = $this->handler->del($key);
      return $result > 0;
    } catch (\Throwable $th) {
      $this->setHandler();
      $result = $this->handler->del($key);
      return $result > 0;
    }
  }

  /**
   * 清除缓存
   * @access public
   * @return bool
   */
  public function clear(): bool
  {
    try {
      $this->writeTimes++;
      $this->handler->flushDB();
      return true;
    } catch (\Throwable $th) {
      $this->setHandler();
      $this->handler->flushDB();
      return true;
    }
  }

  /**
   * 删除缓存标签
   * @access public
   * @param array $keys 缓存标识列表
   * @return void
   */
  public function clearTag(array $keys): void
  {
    try {
      // 指定标签清除
      $this->handler->del($keys);
    } catch (\Throwable $th) {
      $this->setHandler();
      $this->handler->del($keys);
    }
  }

  /**
   * 追加TagSet数据
   * @access public
   * @param string $name  缓存标识
   * @param mixed  $value 数据
   * @return void
   */
  public function append(string $name, $value): void
  {
    try {
      $key = $this->getCacheKey($name);
      $this->handler->sAdd($key, $value);
    } catch (\Throwable $th) {
      $this->setHandler();
      $this->handler->sAdd($key, $value);
    }
  }

  /**
   * 获取标签包含的缓存标识
   * @access public
   * @param string $tag 缓存标签
   * @return array
   */
  public function getTagItems(string $tag): array
  {
    try {
      $name = $this->getTagKey($tag);
      $key  = $this->getCacheKey($name);
      return $this->handler->sMembers($key);
    } catch (\Throwable $th) {
      $this->setHandler();
      return $this->handler->sMembers($key);
    }
  }
}
