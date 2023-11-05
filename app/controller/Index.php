<?php

namespace app\controller;

use app\BaseController;
use app\model\Hotlist;
use think\facade\View;

class Index extends BaseController
{
    public function index()
    {
        $hotlist = Hotlist::where('status', 1)->select();
        $data = [];
        foreach ($hotlist as $value) {
            $data[$value['action']] = [
                'list' => json_decode($value['data'], true),
                'updated_at' => $value['updated_at']
            ];
        }
        return View::fetch('index/index', $data);
    }

    public function hello($name = 'ThinkPHP6')
    {
        return 'hello,' . $name;
    }
}
