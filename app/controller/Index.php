<?php

namespace app\controller;

use app\BaseController;
use app\model\Hotlist;
use think\facade\View;

class Index extends BaseController
{
    public function index()
    {
        $re = Hotlist::where('status', 1)->order('rank', 'asc')->select()->toArray();
        $hotBoardData = [];
        foreach ($re as $value) {
            $hotBoardData[$value['action']] = [
                'id' => $value['action'],
                'source' => $value['source'],
                'title' => $value['title'],
                'icon' => $value['icon'],
                'data' => json_decode($value['data']),
                'updated_at' => $value['updated_at']
            ];
        }
        $data['hotBoardData'] = $hotBoardData;
        View::assign('data', json_encode($data));
        return View::fetch('index/index');
    }

    public function hello($name = 'ThinkPHP6')
    {
        return 'hello,' . $name;
    }
}
