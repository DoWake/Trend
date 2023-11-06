<?php

namespace app\controller;

use app\BaseController;
use app\model\Hotlist;
use think\facade\View;

class Index extends BaseController
{
    public function index()
    {
        $data = Hotlist::where('status', 1)->order('rank', 'asc')->select()->toArray();
        View::assign('data', $data);
        return View::fetch('index/index');
    }

    public function hello($name = 'ThinkPHP6')
    {
        return 'hello,' . $name;
    }
}
