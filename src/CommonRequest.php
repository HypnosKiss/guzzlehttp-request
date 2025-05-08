<?php

namespace Sweeper\GuzzleHttpRequest;

use Closure;
use Concat\Http\Middleware\Logger;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\MessageFormatterInterface;
use GuzzleHttp\Middleware;
use GuzzleHttp\Promise\PromiseInterface;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\TransferStats;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * 公共请求
 * Created by PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/9/15 13:38
 * @Package \Sweeper\GuzzleHttpRequest\CommonRequest
 * @method self v1()
 * @method self v2()
 * @method self v[\d]()
 */
class CommonRequest extends Request
{

    /** @var mixed|null 版本信息 */
    private $version;

    /** @var int 成功 CODE */
    protected $successCode = 0;

    /** @var array 请求统计信息 */
    protected $statsInfo = [];

    public function getStatsInfo(): array
    {
        return $this->statsInfo;
    }

    public function setStatsInfo(array $statsInfo): self
    {
        $this->statsInfo = $statsInfo;

        return $this;
    }

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

    public function getSuccessCode(): int
    {
        return $this->successCode;
    }

    public function setSuccessCode(int $successCode): self
    {
        $this->successCode = $successCode;

        return $this;
    }

    /**
     * 断言逻辑成功
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 15:52
     * @param int $logicCode
     * @return bool
     */
    public function assertLogicSuccess(int $logicCode): bool
    {
        return $logicCode === $this->getSuccessCode();
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
        if (preg_match('/^v\d+$/', $name)) {//判断字符串格式为：v1、v2 ...
            return $this->setVersion($name);
        }

        return parent::__call($name, $arguments);// 调用父类
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
        $responseContent = json_decode(static::getResponseContents($response), true) ?: [];
        $logicCode       = $responseContent['code'] ?? -1;
        $message         = $responseContent['message'] ?? $responseContent['msg'] ?? $response->getReasonPhrase();
        $errors          = $responseContent['errors'] ?? [];
        if (!$this->assertHttpSuccess($httpCode)) {
            $message = "{$message}[Request failed with HTTP Code {$httpCode}.]";

            return Response::error($message, $responseContent);
        }
        if (!$this->assertLogicSuccess($logicCode)) {
            $message = "{$message}[Request succeeded with HTTP Code {$httpCode}.][with Logic Code {$logicCode}]";

            return Response::error($message, $responseContent);
        }
        if ($errors) {
            $message = is_string($errors) ? $errors : implode(', ', $errors);
            $message = "{$message}[Request succeeded with HTTP Code {$httpCode}.][with Logic Code {$logicCode}]";

            return Response::error($message, $responseContent);
        }

        return new Response(HttpCode::OK, $message, $responseContent);
    }

    /**
     * 获取处理程序堆栈
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 14:12
     * @param array         $config
     * @param callable|null $handler
     * @return \GuzzleHttp\HandlerStack
     */
    protected function getHandler(array $config = [], callable $handler = null): HandlerStack
    {
        // 创建 Handler
        if (isset($config['handler']) && $config['handler'] instanceof HandlerStack) {
            $handlerStack = $config['handler'];
        } else {
            $handlerStack = HandlerStack::create($handler);
        }

        return $handlerStack;
    }

    /**
     * 重试决策程序(返回一个匿名函数, 匿名函数若返回false 表示不重试，反之则表示继续重试)
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 14:12
     * @param int           $maxRetryTimes
     * @param callable|null $allowRetryFunc
     * @return \Closure
     */
    protected function retryDecider(int $maxRetryTimes = 1, callable $allowRetryFunc = null): Closure
    {
        return function($retries, RequestInterface $request, ResponseInterface $response = null, \Throwable $exception = null) use ($maxRetryTimes, $allowRetryFunc) {
            // 最允许重试次数内，继续重试，超过最大重试次数，不再重试
            if ($retries >= $maxRetryTimes) {
                return false;
            }
            // 请求失败，继续重试
            if ($exception instanceof ConnectException) {
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
    protected function retryDelay(int $intervalMillisecond = 1000): Closure
    {
        return function($numberOfRetries) use ($intervalMillisecond) {
            return $intervalMillisecond * $numberOfRetries;
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
    protected function withRetry(array $config = [], int $maxRetryTimes = 1, callable $allowRetryFunc = null, int $intervalMillisecond = 3000, Closure $retryDecider = null, Closure $retryDelay = null): array
    {
        // 创建 Handler
        $handlerStack = $this->getHandler($config);
        // 创建重试中间件，指定决策者为 $this->retryDecider(),指定重试延迟为 $this->retryDelay()
        $handlerStack->push(Middleware::retry($retryDecider ?? $this->retryDecider($maxRetryTimes, $allowRetryFunc), $retryDelay ?? $this->retryDelay($intervalMillisecond)));
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
    protected function withTap(array $config = [], Closure $before = null, Closure $after = null): array
    {
        // 创建 Handler
        $handlerStack = $this->getHandler($config);
        $before       = $before && is_callable($before) ? $before : function(RequestInterface $request, array $options) {
            if (PHP_SAPI === 'cli') {
                echo '>>> ', date('Y-m-d H:i:s'), ' Before sending the request', PHP_EOL;
            }
        };
        $after        = $after && is_callable($after) ? $after : function(RequestInterface $request, array $options, PromiseInterface $response) {
            if (PHP_SAPI === 'cli') {
                echo '>>> ', date('Y-m-d H:i:s'), ' After receiving the response', PHP_EOL;
            }
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
    protected function withDebug(array $config = []): array
    {
        // 创建 Handler
        $config['handler'] = $this->getHandler($config);
        $config['debug']   = PHP_SAPI === 'cli';

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
    protected function withDelay(array $config = [], int $delay = 0): array
    {
        // 创建 Handler
        $config['handler'] = $this->getHandler($config);
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
    protected function withRequestOptions(array $config = [], string $optionKey = '', $optionValue = null): array
    {
        // 创建 Handler
        $config['handler'] = $this->getHandler($config);
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
    protected function withLog(array $config = [], LoggerInterface $logger = null, MessageFormatterInterface $formatter = null, string $logLevel = 'info'): array
    {
        // 创建 Handler
        $handlerStack = $this->getHandler($config);
        // 日志中间件
        $middlewareLog = $logger ? Middleware::log($logger, $formatter ?? new MessageFormatter(), $logLevel) : $this->getLoggerMiddleware($logger, $formatter, $logLevel);
        // 创建日志中间件
        $handlerStack->push($middlewareLog);
        $config['handler'] = $handlerStack;

        return $config;
    }

    /**
     * 使用头信息
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 15:59
     * @param array $config
     * @return array
     */
    protected function withHeader(array &$config = []): array
    {
        // 创建 Handler
        $handlerStack = $this->getHandler($config);
        // Add a middleware with a name
        $handlerStack->push(Middleware::mapRequest(function(RequestInterface $request) {
            return $request->withHeader('X-Foo', 'Bar');
        }), 'add_foo');

        // Add a middleware before a named middleware (unshift before).
        $handlerStack->before('add_foo', Middleware::mapRequest(function(RequestInterface $request) {
            return $request->withHeader('X-Baz', 'Qux');
        }), 'add_baz');

        // Add a middleware after a named middleware (pushed after).
        $handlerStack->after('add_baz', Middleware::mapRequest(function(RequestInterface $request) {
            return $request->withHeader('X-Lorem', 'Ipsum');
        }));

        // 附带头信息
        $handlerStack->push(Middleware::mapRequest(function(RequestInterface $request) {
            return $request->withHeader('X-mapRequest', 'mapRequest');
        }));

        $handlerStack->push(Middleware::mapResponse(function(ResponseInterface $response) {
            return $response->withHeader('X-mapResponse', 'mapResponse');
        }));

        $handlerStack->push(static::addRequestHeader('X-addRequestHeader', 'addRequestHeader'));
        $handlerStack->push(static::addResponseHeader('X-addResponseHeader', 'addResponseHeader'));
        $config['handler'] = $handlerStack;

        return $config;
    }

    /**
     * 日志中间件
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 15:03
     * @param LoggerInterface|callable $logger
     * @param string|callable          $formatter Constant or callable that accepts a Response.
     * @param string                   $logLevel  Sets the log level to use, which can be either a string or a callable  that accepts a response (which could be null). A log level could also  be null, which indicates that the default log level should be used.
     * @return \Concat\Http\Middleware\Logger
     */
    protected function getLoggerMiddleware($logger = null, $formatter = null, string $logLevel = ''): Logger
    {
        $logger    = $logger ?? static function($level, $message, array $context) {// $context = compact('request', 'response', 'reason');
            if (PHP_SAPI === 'cli') {
                echo date('Y-m-d H:i:s') . " [$level] " . (json_encode(compact('message', 'context'), JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)), PHP_EOL;
            }
        };
        $formatter = $formatter ?? static function($request, $response, $reason) {
            /**
             * @var \GuzzleHttp\Psr7\Request  $request
             * @var \GuzzleHttp\Psr7\Response $response
             */
            // Make sure that the content of the body is available again.
            $request && $request->getBody() && $request->getBody()->rewind();
            $response && $response->getBody() && $response->getBody()->rewind();

            //请求头
            $requestHeaders = [];
            foreach ($request->getHeaders() as $k => $vs) {
                foreach ($vs as $v) {
                    $requestHeaders[] = "$k: $v";
                }
            }

            //响应头
            $responseHeaders = [];
            if ($response) {
                foreach ($response->getHeaders() as $k => $vs) {
                    foreach ($vs as $v) {
                        $responseHeaders[] = "$k: $v";
                    }
                }
            }

            $uri                     = $request->getUri();
            $method                  = $request->getMethod();
            $path                    = $uri->getPath();
            $requestProtocolVersion  = $request->getProtocolVersion();
            $requestContents         = $request->getBody()->getContents();
            $responseProtocolVersion = $response ? $response->getProtocolVersion() : null;
            $responseStatusCode      = $response ? $response->getStatusCode() : null;
            $responseReasonPhrase    = $response ? $response->getReasonPhrase() : null;
            $responseContents        = $response ? $response->getBody()->getContents() : null;

            if ($query = $uri->getQuery()) {
                $path .= '?' . $query;
            }

            // Make sure that the content of the body is available again.
            $request && $request->getBody() && $request->getBody()->rewind();
            $response && $response->getBody() && $response->getBody()->rewind();

            return sprintf(
                "Request %s\n%s %s HTTP/%s\r\n%s\r\n\r\n%s\r\n--------------------\r\nHTTP/%s %s %s\r\n%s\r\n\r\n%s",
                $uri,
                $method,
                $path,
                $requestProtocolVersion,
                implode("\r\n", $requestHeaders),
                $requestContents,
                $responseProtocolVersion,
                $responseStatusCode,
                $responseReasonPhrase,
                implode("\r\n", $responseHeaders),
                $responseContents
            );
        };

        $loggerMiddleware = new Logger($logger, $formatter);
        $loggerMiddleware->setLogLevel($logLevel);

        return $loggerMiddleware;
    }

    /**
     * 生成签名信息
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 15:33
     * @param array  $params
     * @param string $secretKey
     * @return string
     */
    protected function generateSign(array $params, string $secretKey): string
    {
        ksort($params);
        $stringToBeSigned = '';
        foreach ($params as $k => $v) {
            $stringToBeSigned .= "$k$v";
        }

        return strtoupper(hash_hmac('sha256', $stringToBeSigned, $secretKey));
    }

    /**
     * 使用请求参数
     * User: Sweeper
     * Time: 2023/3/10 14:11
     * @param array $params
     * @param array $requestParams
     * @param null  $appKey
     * @param null  $secretKey
     * @return array
     */
    protected function withRequestParams(array $params = [], array &$requestParams = [], $appKey = null, $secretKey = null): array
    {
        $requestParams         = array_replace_recursive($requestParams, [
            'params'     => json_encode($params, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),
            'partner_id' => $appKey,
            'timestamp'  => time(),
        ]);
        $requestParams['sign'] = $this->generateSign($requestParams, $secretKey);

        return $requestParams;
    }

    /**
     * 使用指定选项
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/23 17:22
     * @param array         $options
     * @param callable|null $handler
     * @param bool          $registerLog
     * @param callable|null $logMiddleware
     * @return array
     */
    protected function addOptions(array $options = [], callable $handler = null, bool $registerLog = false, callable $logMiddleware = null): array
    {
        $handlerStack = $this->getHandler($options, $handler);

        // 附带请求头信息
        $handlerStack->push(Middleware::mapRequest(function(RequestInterface $request) {
            return $request->withHeader('X-Middleware-Request-Time', microtime(true));
        }), 'Middleware::mapRequest');

        // 附带响应头信息
        $handlerStack->push(Middleware::mapResponse(function(ResponseInterface $response) {
            // Make sure that the content of the body is available again.
            // $contents = $response->getBody()->getContents() ?? '';
            $response->getBody()->rewind();

            return $response->withHeader('X-Middleware-Response-Time', microtime(true));
        }), 'Middleware::mapResponse');

        // 在发送请求之前和之后调用回调的中间件
        $handlerStack->push(Middleware::tap(function(RequestInterface $request, array $options) {
            if (PHP_SAPI === 'cli') {
                echo '>>> ', date('Y-m-d H:i:s'), ' Before sending the request', PHP_EOL;
            }
        }, function(RequestInterface $request, array $options, PromiseInterface $response) {
            if (PHP_SAPI === 'cli') {
                echo '>>> ', date('Y-m-d H:i:s'), ' After receiving the response', PHP_EOL;
            }
        }), 'Middleware::tap');

        // 创建日志中间件
        // 先入后出，执行后必须重置响应内容，否则会导致获取不到响应内容
        if ($registerLog) {
            $handlerStack->push($logMiddleware ?? $this->getLoggerMiddleware(), 'Middleware::log');
        }

        $options['handler'] = $handlerStack;
        $options['debug']   = PHP_SAPI === 'cli';

        return $options;
    }

    /**
     * 统计信息中间件
     * @param array         $config
     * @param callable|null $handler
     * @return array
     */
    protected function withStats(array $config = [], callable $handler = null): array
    {
        // 创建 Handler
        $handlerStack = $this->getHandler($config, $handler);

        /**
         * on_stats：（可调用）允许您访问请求的传输统计信息，并访问与客户端关联的处理程序的较低级别传输详细信息。
         * on_stats 是在处理程序完成发送请求时调用的可调用对象。调用回调时，会显示有关请求、收到的响应或遇到的错误的传输统计信息。数据中包括发送请求所花费的总时间。
         */
        if (empty($config[RequestOptions::ON_STATS])) {
            $config[RequestOptions::ON_STATS] = function(TransferStats $stats) {
                /** @var RequestInterface $request */
                $request = $stats->getRequest();
                /** @var ResponseInterface|null $response */
                $response     = $stats->getResponse();
                $transferTime = $stats->getTransferTime();
                $statsInfo    = $stats->getHandlerStats();

                // Make sure that the content of the body is available again.
                $request && $request->getBody() && $request->getBody()->rewind();
                $response && $response->getBody() && $response->getBody()->rewind();

                // 请求信息
                $requestInfo = [
                    'url'     => (string)$request->getUri(),
                    'method'  => $request->getMethod(),
                    'path'    => $request->getUri()->getQuery() ? $request->getUri()->getPath() . '?' . $request->getUri()->getQuery() : $request->getUri()->getPath(),
                    'headers' => $request->getHeaders(),
                    'body'    => (string)$request->getBody(),
                ];

                // 响应信息
                $responseInfo = null;
                if ($response) {
                    $responseInfo = [
                        'statusCode' => $response->getStatusCode(),
                        'headers'    => $response->getHeaders(),
                        'reason'     => $response->getReasonPhrase(),
                        'body'       => (string)$response->getBody(),
                    ];
                }

                // 格式化后的信息
                // $formatted = $this->formatter($request, $response);

                // Make sure that the content of the body is available again.
                $request && $request->getBody() && $request->getBody()->rewind();
                $response && $response->getBody() && $response->getBody()->rewind();

                $this->setStatsInfo(compact('requestInfo', 'responseInfo', 'transferTime', 'statsInfo'));

                if (PHP_SAPI === 'cli') {
                    echo ' stats >>> ' . json_encode(compact('requestInfo', 'responseInfo', 'transferTime', 'statsInfo')), PHP_EOL;
                }
            };
        }
        $config['handler'] = $handlerStack;

        return $config;
    }

    /**
     * 格式化请求信息、响应信息
     * Author: Sweeper <wili.lixiang@gmail.com>
     * Time: 2024/10/21 11:38:06
     * @param \GuzzleHttp\Psr7\Request       $request
     * @param \GuzzleHttp\Psr7\Response|null $response
     * @param mixed                          $reason
     * @return array
     */
    protected function formatter($request, $response, $reason = null): array
    {
        /**
         * @var \GuzzleHttp\Psr7\Request  $request
         * @var \GuzzleHttp\Psr7\Response $response
         */
        // Make sure that the content of the body is available again.
        $request && $request->getBody() && $request->getBody()->rewind();
        $response && $response->getBody() && $response->getBody()->rewind();

        //请求头
        $requestHeaders = [];
        foreach ($request->getHeaders() as $k => $vs) {
            foreach ($vs as $v) {
                $requestHeaders[] = "$k: $v";
            }
        }

        //响应头
        $responseHeaders = [];
        if ($response) {
            foreach ($response->getHeaders() as $k => $vs) {
                foreach ($vs as $v) {
                    $responseHeaders[] = "$k: $v";
                }
            }
        }

        $uri                     = $request->getUri();
        $method                  = $request->getMethod();
        $path                    = $uri->getPath();
        $requestProtocolVersion  = $request->getProtocolVersion();
        $requestContents         = (string)$request->getBody();
        $responseProtocolVersion = $response ? $response->getProtocolVersion() : null;
        $responseStatusCode      = $response ? $response->getStatusCode() : null;
        $responseReasonPhrase    = $response ? $response->getReasonPhrase() : null;
        $responseContents        = $response ? (string)$response->getBody() : null;

        // Make sure that the content of the body is available again.
        $request && $request->getBody() && $request->getBody()->rewind();
        $response && $response->getBody() && $response->getBody()->rewind();

        if ($query = $uri->getQuery()) {
            $path .= '?' . $query;
        }
        $uri = (string)$uri;

        return compact('uri', 'method', 'path', 'requestProtocolVersion', 'requestHeaders', 'requestContents', 'responseProtocolVersion', 'responseHeaders', 'responseStatusCode', 'responseReasonPhrase', 'responseContents');
    }

}
