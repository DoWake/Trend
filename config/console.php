<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => [
        'worker2' => 'app\command\Worker',
        'CrontabTask' => 'app\command\CrontabTask',
        'CrontabTimer' => 'app\command\CrontabTimer',
        'PushWeb' => 'app\command\PushWeb'
    ],
];
