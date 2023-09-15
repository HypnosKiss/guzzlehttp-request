<?php
/**
 * Created by PhpStorm.
 * User: Sweeper
 * Time: 2023/2/7 16:59
 */

namespace Sweeper\GuzzleHttpRequest;

/**
 * 定义常用的 HTTP CODE
 * Created by PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/9/15 15:40
 * @Package \Sweeper\GuzzleHttpRequest\HttpCode
 */
class HttpCode
{

    /** @var int 请求成功。一般用于GET与POST请求 Request Successful, OK. */
    public const OK = 200;

    /** @var int 已创建。成功请求并创建了新的资源 */
    public const CREATED = 201;

    /** @var int 已接受。已经接受请求，但未处理完成 */
    public const ACCEPTED = 202;

    /** @var int 无内容。服务器成功处理，但未返回内容。在未更新网页的情况下，可确保浏览器继续显示当前文档 */
    public const NO_CONTENT = 204;

    /** @var int 重置内容。服务器处理成功，用户终端（例如：浏览器）应重置文档视图。可通过此返回码清除浏览器的表单域 */
    public const RESET_CONTENT = 205;

    /** @var int 查看其它地址。与301类似。使用GET和POST请求查看 */
    public const SEE_OTHER = 303;

    /** @var int 客户端请求的语法错误，服务器无法理解 */
    public const BAD_REQUEST = 400;

    /** @var int 请求要求用户的身份认证 */
    public const BAD_UNAUTHORIZED = 401;

    /** @var int 服务器理解请求客户端的请求，但是拒绝执行此请求 */
    public const FORBIDDEN = 403;

    /** @var int 服务器无法根据客户端的请求找到资源（网页）。通过此代码，网站设计人员可设置"您所请求的资源无法找到"的个性页面 */
    public const NOT_FOUND = 404;

    /** @var int 客户端请求中的方法被禁止 */
    public const METHOD_NOT_ALLOWED = 405;

    /** @var int 服务器等待客户端发送的请求时间过长，超时 */
    public const REQUEST_TIME_OUT = 408;

    /** @var int 请求的URI过长（URI通常为网址），服务器无法处理 */
    public const REQUEST_URI_TOO_LONG = 414;

    /** @var int 在一定的时间内用户发送了太多的请求，即超出了“频次限制”。 */
    public const TOO_MANY_REQUEST = 429;

    /** @var int 服务器内部错误，无法完成请求 */
    public const INTERNAL_SERVER_ERROR = 500;

    /** @var int 服务器不支持请求的功能，无法完成请求 */
    public const NOT_IMPLEMENTED = 501;

    /** @var int 作为网关或者代理工作的服务器尝试执行请求时，从远程服务器接收到了一个无效的响应 */
    public const BAD_GATEWAY = 502;

    /** @var int 由于超载或系统维护，服务器暂时的无法处理客户端的请求。延时的长度可包含在服务器的Retry-After头信息中 */
    public const SERVICE_UNAVAILABLE = 503;

    /** @var int    充当网关或代理的服务器，未及时从远端服务器获取请求 */
    public const GATEWAY_TIMEOUT = 504;

    /** @var int[] 成功 CODE 列表 */
    public const SUCCESS_CODE_LIST = [self::OK, self::CREATED, self::ACCEPTED, self::NO_CONTENT];

    /**
     * 断言 Http Code 成功
     * User: Sweeper
     * Time: 2023/8/10 18:52
     * @param             $code
     * @param bool        $throwException 抛出异常
     * @param string|null $message
     * @return bool
     */
    public static function assertSuccess($code, bool $throwException = true, string $message = null): bool
    {
        $isSuccess = in_array($code, static::SUCCESS_CODE_LIST, true);
        if (!$isSuccess && $throwException) {
            throw new \RuntimeException($message ?? "响应异常 Code[{$code}]", $code);
        }

        return $isSuccess;
    }

    //HTTP状态码类型归类
    public const TYPE_INFO         = 'information';  //信息(1xx系列)

    public const TYPE_SUCCESS      = 'success';      //成功(2xx系列)

    public const TYPE_REDIRECT     = 'redirect';     //跳转(3xx系列)

    public const TYPE_CLIENT_ERROR = 'client error'; //客户端错误(4xx系列)

    public const TYPE_SERVER_ERROR = 'server error'; //服务端错误(5xx系列)

    //information
    public const STATUS_CONTINUE            = 100;

    public const STATUS_SWITCHING_PROTOCOLS = 101;

    //success
    public const STATUS_OK                            = 200;

    public const STATUS_CREATED                       = 201;

    public const STATUS_ACCEPTED                      = 202;

    public const STATUS_NON_AUTHORITATIVE_INFORMATION = 203;

    public const STATUS_NO_CONTENT                    = 204;

    public const STATUS_RESET_CONTENT                 = 205;

    public const STATUS_PARTIAL_CONTENT               = 206;

    //redirect
    public const STATUS_MULTIPLE_CHOICES   = 300;

    public const STATUS_MOVED_PERMANENTLY  = 301;

    public const STATUS_MOVED_TEMPORARILY  = 302;

    public const STATUS_SEE_OTHER          = 303;

    public const STATUS_NOT_MODIFIED       = 304;

    public const STATUS_USE_PROXY          = 305;

    public const STATUS_TEMPORARY_REDIRECT = 307;

    //client error
    public const STATUS_BAD_REQUEST                     = 400;

    public const STATUS_UNAUTHORIZED                    = 401;

    public const STATUS_PAYMENT_REQUIRED                = 402;

    public const STATUS_FORBIDDEN                       = 403;

    public const STATUS_NOT_FOUND                       = 404;

    public const STATUS_METHOD_NOT_ALLOWED              = 405;

    public const STATUS_NOT_ACCEPTABLE                  = 406;

    public const STATUS_PROXY_AUTHENTICATION_REQUIRED   = 407;

    public const STATUS_REQUEST_TIMEOUT                 = 408;

    public const STATUS_CONFLICT                        = 409;

    public const STATUS_GONE                            = 410;

    public const STATUS_LENGTH_REQUIRED                 = 411;

    public const STATUS_PRECONDITION_FAILED             = 412;

    public const STATUS_REQUEST_ENTITY_TOO_LARGE        = 413;

    public const STATUS_REQUEST_URI_TOO_LONG            = 414;

    public const STATUS_UNSUPPORTED_MEDIA_TYPE          = 415;

    public const STATUS_REQUESTED_RANGE_NOT_SATISFIABLE = 416;

    public const STATUS_EXPECTATION_FAILED              = 417;

    //server error
    public const STATUS_INTERNAL_SERVER_ERROR      = 500;

    public const STATUS_NOT_IMPLEMENTED            = 501;

    public const STATUS_BAD_GATEWAY                = 502;

    public const STATUS_SERVICE_UNAVAILABLE        = 503;

    public const STATUS_GATEWAY_TIMEOUT            = 504;

    public const STATUS_HTTP_VERSION_NOT_SUPPORTED = 505;

    public const STATUS_BANDWIDTH_LIMIT_EXCEEDED   = 509;

    /** @var string[] 基本状态码文字说明映射 */
    public const STATUS_MESSAGE = [
        self::STATUS_CONTINUE                        => 'Continue',
        self::STATUS_SWITCHING_PROTOCOLS             => 'Switching Protocols',
        self::STATUS_OK                              => 'OK',
        self::STATUS_CREATED                         => 'Created',
        self::STATUS_ACCEPTED                        => 'Accepted',
        self::STATUS_NON_AUTHORITATIVE_INFORMATION   => 'Non-Authoritative Information',
        self::STATUS_NO_CONTENT                      => 'No Content',
        self::STATUS_RESET_CONTENT                   => 'Reset Content',
        self::STATUS_PARTIAL_CONTENT                 => 'Partial Content',
        self::STATUS_MULTIPLE_CHOICES                => 'Multiple Choices',
        self::STATUS_MOVED_PERMANENTLY               => 'Moved Permanently',
        self::STATUS_MOVED_TEMPORARILY               => 'Moved Temporarily ',
        self::STATUS_SEE_OTHER                       => 'See Other',
        self::STATUS_NOT_MODIFIED                    => 'Not Modified',
        self::STATUS_USE_PROXY                       => 'Use Proxy',
        self::STATUS_TEMPORARY_REDIRECT              => 'Temporary Redirect',
        self::STATUS_BAD_REQUEST                     => 'Bad Request',
        self::STATUS_UNAUTHORIZED                    => 'Unauthorized',
        self::STATUS_PAYMENT_REQUIRED                => 'Payment Required',
        self::STATUS_FORBIDDEN                       => 'Forbidden',
        self::STATUS_NOT_FOUND                       => 'Not Found',
        self::STATUS_METHOD_NOT_ALLOWED              => 'Method Not Allowed',
        self::STATUS_NOT_ACCEPTABLE                  => 'Not Acceptable',
        self::STATUS_PROXY_AUTHENTICATION_REQUIRED   => 'Proxy Authentication Required',
        self::STATUS_REQUEST_TIMEOUT                 => 'Request Timeout',
        self::STATUS_CONFLICT                        => 'Conflict',
        self::STATUS_GONE                            => 'Gone',
        self::STATUS_LENGTH_REQUIRED                 => 'Length Required',
        self::STATUS_PRECONDITION_FAILED             => 'Precondition Failed',
        self::STATUS_REQUEST_ENTITY_TOO_LARGE        => 'Request Entity Too Large',
        self::STATUS_REQUEST_URI_TOO_LONG            => 'Request-URI Too Long',
        self::STATUS_UNSUPPORTED_MEDIA_TYPE          => 'Unsupported Media Type',
        self::STATUS_REQUESTED_RANGE_NOT_SATISFIABLE => 'Requested Range Not Satisfiable',
        self::STATUS_EXPECTATION_FAILED              => 'Expectation Failed',
        self::STATUS_INTERNAL_SERVER_ERROR           => 'Internal Server Error',
        self::STATUS_NOT_IMPLEMENTED                 => 'Not Implemented',
        self::STATUS_BAD_GATEWAY                     => 'Bad Gateway',
        self::STATUS_SERVICE_UNAVAILABLE             => 'Service Unavailable',
        self::STATUS_GATEWAY_TIMEOUT                 => 'Gateway Timeout',
        self::STATUS_HTTP_VERSION_NOT_SUPPORTED      => 'HTTP Version Not Supported',
        self::STATUS_BANDWIDTH_LIMIT_EXCEEDED        => 'Bandwidth Limit Exceeded',
    ];

    /**
     * 输出HTTP状态
     * @param $code
     * @return bool
     */
    public static function sendHttpStatus($code): bool
    {
        $messages = static::STATUS_MESSAGE;
        $message  = $messages[$code];
        if ($message && !headers_sent()) {
            $sapi_type = PHP_SAPI;
            if (strpos($sapi_type, 'cgi') === 0) {//CGI 模式
                header("Status: $code $message");
            } else { //FastCGI模式
                header("{$_SERVER['SERVER_PROTOCOL']} $code $message");
            }

            return true;
        }

        return false;
    }

    /**
     * 发送HTTP字符集
     * @param $charset
     * @return bool
     */
    public static function sendCharset($charset): bool
    {
        if (!headers_sent()) {
            header('Content-Type:text/html; charset=' . $charset);

            return true;
        }

        return false;
    }

    /**
     * 获取状态码归类
     * @param $status_code
     * @return string|null
     */
    public static function getStatusType($status_code): ?string
    {
        $status_code  = (string)$status_code;
        $type_map     = [
            '1' => static::TYPE_INFO,
            '2' => static::TYPE_SUCCESS,
            '3' => static::TYPE_REDIRECT,
            '4' => static::TYPE_CLIENT_ERROR,
            '5' => static::TYPE_SERVER_ERROR,
        ];
        $first_letter = $status_code[0];
        if ($type_map[$first_letter]) {
            return $type_map[$first_letter];
        }

        return null;
    }

    /**
     * http跳转
     * @param     $url
     * @param int $status 状态码，可选redirect相关状态码
     */
    public static function redirect($url, int $status = self::STATUS_MOVED_TEMPORARILY): void
    {
        static::sendHttpStatus($status);
        header('Location:' . $url);
    }

    /**
     * 输出下载文件Header
     * @param $file_name
     */
    public static function headerDownloadFile($file_name): void
    {
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Type: application/force-download');
        header('Content-Type: application/octet-stream');
        header('Content-Type: application/download');
        header('Content-Disposition: attachment;filename=' . $file_name);
        header('Content-Transfer-Encoding: binary');
    }

}
