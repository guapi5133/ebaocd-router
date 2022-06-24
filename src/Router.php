<?php

namespace eBaocd\Router;

use eBaocd\Common\xFun;

class Router
{
    /**
     * display
     */
    public static function display()
    {
        spl_autoload_register(array('\eBaocd\Router\Router', 'xLoader'));
        set_error_handler([__CLASS__, 'error']);//注册错误处理方式,严重错误发生或使用 trigger_error() 时的异常
        set_exception_handler([__CLASS__, 'exception']);//注册异常处理方式,用于没有用 try/catch 块来捕获的异常
        register_shutdown_function([__CLASS__, 'shutdown']);//注册关闭函数,脚本执行完成或者 exit() 后被调用

        xFun::urlParse($controller, $action);
        if (!$action || $action == '') $action = 'index';

        $controller = NAME_SPACE . '\\' . APP_NAME . '\\Controller\\' . ucfirst($controller);//首字母大写，大驼峰
        if (!method_exists($controller, $action)) xFun::output('not found');

        $obj = new $controller();
        call_user_func_array([$obj, $action], []);
    }

    /**
     * 自动加载
     * @param $clsname
     * @return bool|mixed
     */
    public static function xLoader($clsname)
    {
        if (class_exists($clsname, FALSE) || interface_exists($clsname, FALSE))
        {
            return TRUE;
        }

        if (strpos($clsname, 'Controller') !== FALSE || strpos($clsname, 'Model') !== FALSE || strpos($clsname, 'Rule') !== FALSE || strpos($clsname, 'Config') !== FALSE)
        {
            $str        = str_ireplace(NAME_SPACE, '', $clsname);
            $str_arr    = explode('\\', $str);
            $str_arr[0] = rtrim(APPS_DIR, DIRECTORY_SEPARATOR);
            if (strpos($clsname, 'Model') !== FALSE || strpos($clsname, 'Rule') !== FALSE || strpos($clsname, 'Config') !== FALSE)
            {
                $str_arr[0] = dirname($str_arr[0]);
            }
            $file = implode(DIRECTORY_SEPARATOR, $str_arr);
            $file .= '.php';
            if (file_exists($file))
            {
                return require_once $file;
            }
        }
    }

    /**
     * 注册错误处理方式
     * @param $type
     * @param $message
     * @param $file
     * @param $line
     */
    public static function error($type, $message, $file, $line)
    {
        $error = [
            'type'    => $type,
            'message' => $message,
            'file'    => $file,
            'line'    => $line
        ];
        self::handler($error);
    }

    /**
     * 注册异常处理方式
     * @param $e
     */
    public static function exception($e)
    {
        $error = [
            'type'    => $e->getCode(),
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'trace'   => $e->getTraceAsString()
        ];
        self::handler($error);
    }

    /**
     * 注册关闭函数
     */
    public static function shutdown()
    {
        $error = error_get_last();
        if (!is_null($error)) self::handler($error);
        exit;
    }

    /**
     * 错误处理
     * @param array $error
     */
    public static function handler(array $error)
    {
        ob_get_contents() && ob_end_clean();

        $error = array_merge(['datetime' => date('Y-m-d H:i:s')], $error);
        xFun::write_log(var_export($error, true), 'error_log');
        xFun::output(103);
    }
}
