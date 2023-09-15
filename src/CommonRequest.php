<?php

namespace Sweeper\GuzzleHttpRequest;

use Closure;
use Concat\Http\Middleware\Logger;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\MessageFormatterInterface;
use GuzzleHttp\Middleware;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * 公共请求
 * Created by PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/9/15 13:38
 * @Package \Sweeper\GuzzleHttpRequest\CommonRequest
 * @method v1()
 * @method v2()
 */
abstract class CommonRequest extends Request
{

    public const   VERSION_V1 = 'v1';

    public const   VERSION_V2 = 'v2';

    /**
     * 支持的版本列表
     * 子类需要支持更多的版本可以重写
     */
    public static $VERSION_MAP = [
        self::VERSION_V1 => self::VERSION_V1,
        self::VERSION_V2 => self::VERSION_V2,
    ];

    /** @var mixed|null 版本信息 */
    private $version;

    /**
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 13:51
     * @return string|null
     */
    public function getVersion(): ?string
    {
        return $this->version;
    }

    /**
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 13:51
     * @param string $version
     * @return $this
     */
    public function setVersion(string $version): self
    {
        $this->version = $version;

        return $this;
    }

    /**
     * 支持 $this->v1()->xxx()
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 13:53
     * @param string $name
     * @param array  $arguments
     * @return $this|\Sweeper\GuzzleHttpRequest\Response|self
     */
    public function __call(string $name, array $arguments)
    {
        if (isset(static::$VERSION_MAP[$name]) && static::$VERSION_MAP[$name]) {
            return $this->setVersion($name);
        }
        if (preg_match('/^v\d+$/', $name)) {//判断字符串格式为：v1、v2 ...
            return $this->setVersion($name);
        }

        return parent::__call($name, $arguments);// 调用父类
    }

    /**
     * 构建请求地址
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 13:41
     * @param string      $path
     * @param string|null $domain
     * @return string
     */
    public function getRequestUri(string $path = '', string $domain = null): string
    {
        return $this->buildRequestUri($path, $domain);
    }

    /**
     * 解析平台响应内容
     * User: Sweeper
     * Time: 2023/8/18 19:34
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return \Sweeper\GuzzleHttpRequest\Response
     */
    public function resolveResponse(ResponseInterface $response): Response
    {
        //返回结果解析
        $httpCode        = $response->getStatusCode();
        $responseContent = $response->getBody()->getContents() ?? [];
        $message         = $responseContent['message'] ?? $response->getReasonPhrase();
        if (!empty($httpCode) && $httpCode !== HttpCode::OK && $httpCode !== HttpCode::CREATED && $httpCode !== HttpCode::ACCEPTED && $httpCode !== HttpCode::NO_CONTENT) {
            $message = "接口请求口成功，返回错误：{$message}[Request failed with HTTP Code {$httpCode}.]";
        }

        return new Response($httpCode, $message, $responseContent);
    }

    /**
     * 获取处理程序堆栈
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 14:12
     * @param array $config
     * @return \GuzzleHttp\HandlerStack
     */
    public static function getHandlerStack(array $config = []): HandlerStack
    {
        return $config['handler'] ?? HandlerStack::create();
    }

    /**
     * 重试决策程序(返回一个匿名函数, 匿名函数若返回false 表示不重试，反之则表示继续重试)
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 14:12
     * @param int           $maxRetryTimes
     * @param callable|null $allowRetryFunc
     * @return \Closure
     */
    public static function retryDecider(int $maxRetryTimes = 1, callable $allowRetryFunc = null): Closure
    {
        return function($retries, RequestInterface $request, ResponseInterface $response = null, \Throwable $exception = null) use ($maxRetryTimes, $allowRetryFunc) {
            // 最允许重试次数内，继续重试，超过最大重试次数，不再重试
            if ($retries < $maxRetryTimes) {
                return true;
            }
            // 请求失败，继续重试
            if ($exception instanceof RequestException) {
                return true;
            }

            if ($response) {
                // 如果请求有响应，但是状态码大于等于500，继续重试(这里根据自己的业务而定)
                if ($response->getStatusCode() >= HttpCode::INTERNAL_SERVER_ERROR) {
                    return true;
                }
                // 自定义的函数
                if (is_callable($allowRetryFunc) && $allowRetryFunc($request, $response, $exception, $retries, $maxRetryTimes) === true) {
                    return true;
                }
            }

            return false;
        };
    }

    /**
     * 返回一个匿名函数，该匿名函数返回下次重试的时间（毫秒）
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 14:16
     * @param int $intervalMillisecond
     * @return \Closure
     */
    public static function retryDelay(int $intervalMillisecond = 1000): Closure
    {
        return function($numberOfRetries) use ($intervalMillisecond) {
            return $intervalMillisecond * $numberOfRetries;
        };
    }

    /**
     * 添加请求头
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 14:17
     * @param $header
     * @param $value
     * @return \Closure
     */
    public static function addRequestHeader($header, $value): Closure
    {
        return function(callable $handler) use ($header, $value) {
            return function(RequestInterface $request, array $options) use ($handler, $header, $value) {
                $request = $request->withHeader($header, $value);

                return $handler($request, $options);
            };
        };
    }

    /**
     * 添加响应头
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 14:17
     * @param $header
     * @param $value
     * @return \Closure
     */
    public static function addResponseHeader($header, $value): Closure
    {
        return function(callable $handler) use ($header, $value) {
            return function(RequestInterface $request, array $options) use ($handler, $header, $value) {
                return $handler($request, $options)->then(
                    function(ResponseInterface $response) use ($header, $value) {
                        return $response->withHeader($header, $value);
                    }
                );
            };
        };
    }

    /**
     * 使用重试
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 14:17
     * @param array         $config
     * @param int           $maxRetryTimes
     * @param callable|null $allowRetryFunc
     * @param int           $intervalMillisecond
     * @param \Closure|null $retryDecider
     * @param \Closure|null $retryDelay
     * @return array
     * @see \GuzzleHttp\Middleware::retry()
     */
    public static function withRetry(array $config = [], int $maxRetryTimes = 1, callable $allowRetryFunc = null, int $intervalMillisecond = 3000, Closure $retryDecider = null, Closure $retryDelay = null): array
    {
        // 创建 Handler
        $handlerStack = static::getHandlerStack($config);
        // 创建重试中间件，指定决策者为 $this->retryDecider(),指定重试延迟为 $this->retryDelay()
        $handlerStack->push(Middleware::retry($retryDecider ?? static::retryDecider($maxRetryTimes, $allowRetryFunc), $retryDelay ?? static::retryDelay($intervalMillisecond)));
        $config['handler'] = $handlerStack;

        return $config;
    }

    /**
     * 添加请求之前、请求之后处理
     * User: Sweeper
     * Time: 2023/3/10 15:40
     * @param array        $config
     * @param Closure|null $before
     * @param Closure|null $after
     * @return array
     * {@see \GuzzleHttp\Middleware::tap()}
     */
    public static function withTap(array $config = [], Closure $before = null, Closure $after = null): array
    {
        // 创建 Handler
        $handlerStack = static::getHandlerStack($config);
        $before       = $before && is_callable($before) ? $before : function(RequestInterface $request, array $options) {
            echo '>>> ', date('Y-m-d H:i:s'), ' Before sending the request', PHP_EOL;
        };
        $after        = $after && is_callable($after) ? $after : function(RequestInterface $request, array $options, PromiseInterface $response) {
            echo '>>> ', date('Y-m-d H:i:s'), ' After receiving the response', PHP_EOL;
        };
        // 在发送请求之前和之后调用回调的中间件
        $handlerStack->push(Middleware::tap($before, $after));
        $config['handler'] = $handlerStack;

        return $config;
    }

    /**
     * 使用 DEBUG 模式
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 14:21
     * @param array $config
     * @return array
     * {@see \GuzzleHttp\RequestOptions::DEBUG}
     */
    public static function withDebug(array $config = []): array
    {
        // 创建 Handler
        $config['handler'] = static::getHandlerStack($config);
        $config['debug']   = true;

        return $config;
    }

    /**
     * 使用延迟（发送前延迟的时间量（以毫秒为单位）。）
     * delay: (int) The amount of time to delay before sending in milliseconds.
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 14:22
     * @param array $config
     * @param int   $delay
     * @return array
     * {@see \GuzzleHttp\RequestOptions::DELAY}
     */
    public static function withDelay(array $config = [], int $delay = 0): array
    {
        // 创建 Handler
        $config['handler'] = static::getHandlerStack($config);
        $config['delay']   = (float)$delay;

        return $config;
    }

    /**
     * 使用请求选项
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 14:22
     * @param array  $config
     * @param string $optionKey
     * @param null   $optionValue
     * @return array
     * {@see \GuzzleHttp\RequestOptions}
     */
    public static function withRequestOptions(array $config = [], string $optionKey = '', $optionValue = null): array
    {
        // 创建 Handler
        $config['handler'] = static::getHandlerStack($config);
        $const             = strtoupper($optionKey);
        if (defined(RequestOptions::class . "::$const")) {
            $config[$optionKey] = $optionValue;
        }

        return $config;
    }

    /**
     * 使用日志
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 14:22
     * @param array                                      $config
     * @param \Psr\Log\LoggerInterface|null              $logger
     * @param \GuzzleHttp\MessageFormatterInterface|null $formatter
     * @param string                                     $logLevel
     * @return array
     * {@see \GuzzleHttp\Middleware::log()}
     */
    public static function withLog(array $config = [], LoggerInterface $logger = null, MessageFormatterInterface $formatter = null, string $logLevel = 'info'): array
    {
        // 创建 Handler
        $handlerStack = static::getHandlerStack($config);
        // 日志中间件
        $middlewareLog = $logger ? Middleware::log($logger, $formatter ?? new MessageFormatter()) : static::getLoggerMiddleware();
        // 创建日志中间件
        $handlerStack->push($middlewareLog);
        $config['handler'] = $handlerStack;

        return $config;
    }

    /**
     * 日志中间件
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 15:03
     * @return \Concat\Http\Middleware\Logger
     */
    public static function getLoggerMiddleware(): Logger
    {
        $logger    = function($level, $message, array $context) {
            echo date('Y-m-d H:i:s') . " [$level] " . (is_string($message) ? $message : json_encode((array)$message, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)), PHP_EOL;
        };
        $formatter = function($request, $response, $reason) {
            /**
             * @var \GuzzleHttp\Psr7\Request  $request
             * @var \GuzzleHttp\Psr7\Response $response
             */
            $requestBody = $request->getBody();
            $requestBody->rewind();

            //请求头
            $requestHeaders = [];
            foreach ($request->getHeaders() as $k => $vs) {
                foreach ($vs as $v) {
                    $requestHeaders[] = "$k: $v";
                }
            }

            //响应头
            $responseHeaders = [];
            foreach ($response->getHeaders() as $k => $vs) {
                foreach ($vs as $v) {
                    $responseHeaders[] = "$k: $v";
                }
            }

            $uri  = $request->getUri();
            $path = $uri->getPath();

            if ($query = $uri->getQuery()) {
                $path .= '?' . $query;
            }

            return sprintf(
                "Request %s\n%s %s HTTP/%s\r\n%s\r\n\r\n%s\r\n--------------------\r\nHTTP/%s %s %s\r\n%s\r\n\r\n%s",
                $uri,
                $request->getMethod(),
                $path,
                $request->getProtocolVersion(),
                implode("\r\n", $requestHeaders),
                $requestBody->getContents(),
                $response->getProtocolVersion(),
                $response->getStatusCode(),
                $response->getReasonPhrase(),
                implode("\r\n", $responseHeaders),
                $response->getBody()->getContents()
            );
        };

        return new Logger($logger, $formatter);
    }

    /**
     * 生成签名信息
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 15:33
     * @param array  $params
     * @param string $secretKey
     * @return string
     */
    protected static function generateSign(array $params, string $secretKey): string
    {
        ksort($params);
        $stringToBeSigned = '';
        foreach ($params as $k => $v) {
            $stringToBeSigned .= "$k$v";
        }

        return strtoupper(hash_hmac('sha256', $stringToBeSigned, $secretKey));
    }

}
