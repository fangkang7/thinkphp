<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 刘志淳 <chun@engineer.com>
// +----------------------------------------------------------------------

namespace think\console\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\App;
use think\facade\Config;
use think\facade\Env;
use think\facade\Log;


abstract class Make extends Command
{
    protected $type;

    abstract protected function getStub();

    protected function configure()
    {
        $this->addArgument('name', Argument::REQUIRED, "The name of the class");
    }

    protected function execute(Input $input, Output $output)
    {
        $name = trim($input->getArgument('name'));
        // 这个方法就是获取命名创建的类
        $classname = $this->getClassName($name);
        // 这个方法是命名创建的地址
        $pathname = $this->getPathName($classname);

        if (is_file($pathname)) {
            $output->writeln('<error>' . $this->type . ' already exists!</error>');
            return false;
        }

        if (!is_dir(dirname($pathname))) {
            mkdir(dirname($pathname), 0755, true);
        }

        file_put_contents($pathname, $this->buildClass($classname));

        $output->writeln('<info>' . $this->type . ' created successfully.</info>');

    }

    protected function buildClass($name)
    {
        $stub = file_get_contents($this->getStub());

        $namespace = trim(implode('\\', array_slice(explode('\\', $name), 0, -1)), '\\');

        $class = str_replace($namespace . '\\', '', $name);

        return str_replace(['{%className%}', '{%actionSuffix%}', '{%namespace%}', '{%app_namespace%}'], [
            $class,
            Config::get('action_suffix'),
            $namespace,
            App::getNamespace(),
        ], $stub);
    }

    protected function getPathName($name)
    {
        /*
        [ 2018-10-31T11:58:57+08:00 ][ info ] 参数$name>>>>app\model\kaka789
        [ 2018-10-31T11:58:57+08:00 ][ info ] appNamesapce>>>>app
        [ 2018-10-31T11:58:57+08:00 ][ info ] 第二个name>>>>>>model\kaka789
        [ 2018-10-31T11:58:57+08:00 ][ info ] $app_path>>>>>>D:\PHPTutorial\WWW\tp_shop\application\
         * */
        // Log::write('参数$name>>>>'.$name);
        $appNamespace = App::getNamespace();
        // Log::write('appNamesapce>>>>'.$appNamespace);
        // Log::write('第二个name>>>>>>'.$name);
        $app_path = Env::get('app_path');
        // Log::write('$app_path>>>>>>'.$app_path);
        // 判断如果以app开头的就执行
        if (strpos($name, $appNamespace . '\\') !== false) {
            Log::write('走的app');
            $name = str_replace(App::getNamespace() . '\\', '', $name);
            return $app_path . ltrim(str_replace('\\', '/', $name), '/') . '.php';
        }else{
            Log::write('走的data');
            // 当不是app开头的就将application置空
            return str_replace('application\\','',$app_path) . ltrim(str_replace('\\', '/', $name), '/') . '.php';
        }
    }

    protected function getClassName($name)//$name app\model\test
    {
        // 这是获取命名空间 app
        $appNamespace = App::getNamespace();
        //这里会做判断App 在命名空间出现的位置  如果有则直接return
        if (strpos($name, $appNamespace . '\\') !== false) {
            return $name;
        }
        // 在这一步我们的$name 还是App\modle\test
        if (Config::get('app_multi_module')) {
            if (strpos($name, '/')) {
                list($module, $name) = explode('/', $name, 2);
            } else {
                // 这里会做校验，判断模块是否存在，不存在的时候自定义一个common
                // 所以我们需要这里为空
                $module = '';
            }
        } else {
            $module = null;
        }
        //这里是对路径进行处理
        if (strpos($name, '/') !== false) {
            $name = str_replace('/', '\\', $name);
        }
        // 我们在这里就直接返回我们的命名空间即可
        return $name;
        // return $this->getNamespace($appNamespace, $module) . '\\' . $name;
    }
    // 这个方法是完善命名空间的
    protected function getNamespace($appNamespace, $module)
    {
        return $module ? ($appNamespace . '\\' . $module) : $appNamespace;


    }

}

