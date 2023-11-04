<?php

declare(strict_types=1);

namespace app\command;

use app\common\crontab\Task;
use app\common\crontab\Timer;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use Workerman\Worker as WorkermanWorker;

class Worker extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('convert')
            ->addArgument('action', Argument::OPTIONAL, "start|stop|restart|reload|status|connections", 'start')
            ->addOption('mode', 'm', Option::VALUE_OPTIONAL, 'Run the workerman server in daemon mode.')
            ->setDescription('the workerman command');
    }

    protected function execute(Input $input, Output $output)
    {
        // 指令输出
        $output->writeln('convert start');

        $action = $input->getArgument('action');
        $mode = $input->getOption('mode');


        // 重新构造命令行参数,以便兼容workerman的命令
        global $argv;

        $argv = [];

        array_unshift($argv, 'think', $action);
        if ($mode == 'd') {
            $argv[] = '-d';
        } else if ($mode == 'g') {
            $argv[] = '-g';
        }

        // 检测运行环境是否是Windows
        if (strpos(strtolower(PHP_OS), 'win') === 0) {
            exit("start.php not support windows, please use start_for_win.bat\n");
        }

        // 检查pcntl扩展
        if (!extension_loaded('pcntl')) {
            exit("Please install pcntl extension. See http://doc3.workerman.net/appendices/install-extension.html\n");
        }

        // 检测posix扩展
        if (!extension_loaded('posix')) {
            exit("Please install posix extension. See http://doc3.workerman.net/appendices/install-extension.html\n");
        }

        // 定义全局启动模式
        define('GLOBAL_START', 1);

        // 运行定时任务 - 任务处理进程
        Task::run();

        // 运行定时任务 - 定时器进程
        Timer::run();

        // 启动全部进程
        WorkermanWorker::runAll();
    }
}
