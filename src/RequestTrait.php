<?php

namespace Sweeper\GuzzleHttpRequest;

use Closure;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\HandlerStack;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Request Trait
 * Created by PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/9/19 8:56
 * @Package \Sweeper\GuzzleHttpRequest\RequestTrait
 * @method Response get(string $url, array $params = [], array $options = []) GET 请求
 * @method Response post(string $url, array $params = [], array $options = [])
 * @method Response put(string $url, array $params = [], array $options = [])
 * @method Response delete(string $url, array $params = [], array $options = [])
 * @method Response patch(string $url, array $params = [], array $options = [])
 * @method Response connect(string $url, array $params = [], array $options = [])
 * @method Response head(string $url, array $params = [], array $options = [])
 * @method Response options(string $url, array $params = [], array $options = [])
 * @method Response trace(string $url, array $params = [], array $options = [])
 * @method static self
 * @mixin Client
 */
trait RequestTrait
{

    /** @var Client GuzzleHttp Client */
    private $client;

    /** @var array 客户端配置 */
    private $clientConfig = [
        'handler'         => null,
        'base_uri'        => null,
        'proxy'           => null,
        'allow_redirects' => [
            'max'             => 5,
            'protocols'       => ['http', 'https'],
            'strict'          => false,
            'referer'         => false,
            'track_redirects' => false,
        ],
        'http_errors'     => true,
        'decode_content'  => true,
        'verify'          => false,
        'cookies'         => false,
        'idn_conversion'  => false,
        'connect_timeout' => 10,
        'timeout'         => 60,
        'headers'         => [
            'Content-Type' => 'application/json',
        ],
    ];

    /** @var HandlerStack GuzzleHttp HandlerStack */
    private $handlerStack;

    /** @var int 默认连接超时时间 */
    private $connectTimeout = 10;

    /** @var int 默认超时时间 */
    private $timeout = 60;

    /**
     * 获取客户端
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 9:19
     * @param array $config
     * @return \GuzzleHttp\Client
     */
    public function getClient(array $config = []): Client
    {
        if (!($this->client instanceof Client)) {
            $this->client = $this->withClient($config);
        }

        return $this->client;
    }

    /**
     * 设置客户端
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 9:19
     * @param \GuzzleHttp\Client $client
     * @return $this
     */
    public function setClient(Client $client): self
    {
        $this->client = $client;

        return $this;
    }

    /**
     * 获取客户端配置
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 9:20
     * @return array
     */
    public function getClientConfig(): array
    {
        return array_replace($this->getConfig('config') ?: [], $this->clientConfig);
    }

    /**
     * 设置客户端配置
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 9:21
     * @param array $clientConfig
     * @return $this
     */
    public function setClientConfig(array $clientConfig): self
    {
        $this->clientConfig = $clientConfig;

        return $this;
    }

    /**
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/19 11:59
     * @param callable|null $handler
     * @return \GuzzleHttp\HandlerStack
     */
    public function getHandlerStack(callable $handler = null): HandlerStack
    {
        if (!$this->handlerStack) {
            $this->handlerStack = HandlerStack::create($handler);
        }

        return $this->handlerStack;
    }

    /**
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/19 11:56
     * @param callable $handlerStack
     * @return $this
     */
    public function setHandlerStack(callable $handlerStack): self
    {
        $this->handlerStack = $handlerStack;

        return $this;
    }

    /**
     * 获取连接超时时间
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 9:19
     * @return int
     */
    public function getConnectTimeout(): int
    {
        return $this->connectTimeout;
    }

    /**
     * 设置连接超时时间
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 9:20
     * @param int $connectTimeout
     * @return $this
     */
    public function setConnectTimeout(int $connectTimeout): self
    {
        $this->connectTimeout = $connectTimeout;

        return $this;
    }

    /**
     * 获取超时时间
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 9:20
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * 设置超时时间
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 9:20
     * @param int $timeout
     * @return $this
     */
    public function setTimeout(int $timeout): self
    {
        $this->timeout = $timeout;

        return $this;
    }

    /**
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 9:40
     * @param string $name
     * @param array  $arguments
     * @return \Sweeper\GuzzleHttpRequest\Response
     * @mixin Client
     */
    public function __call(string $name, array $arguments)
    {
        $method = strtoupper($name);
        if (defined(static::class . "::$method")) {
            return $this->doSyncRequest(constant(static::class . "::$method"), ...$arguments);
        }
        // 优先调用自己方法
        if (method_exists($this, $name)) {
            return $this->{$name}(...$arguments);
        }
        if (method_exists($this->getClient(), $name)) {
            return $this->getClient()->{$name}(...$arguments);
        }
        // 调用父类
        if (is_callable([$this, $name])) {
            return parent::__call($name, $arguments);
        }

        throw new \BadMethodCallException('Method no exists:' . $name);
    }

    /**
     * 支持静态调用方法(默认对象)
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 14:05
     * @param string $name
     * @param array  $arguments
     * @return mixed
     */
    public static function __callStatic(string $name, array $arguments)
    {
        return static::instance()->{$name}(...$arguments);
    }

    /**
     * 配置客户端
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 9:22
     * @param array $config
     * @return \GuzzleHttp\Client
     */
    protected function withClient(array $config = []): Client
    {
        return new Client(array_replace($this->getClientConfig(), $config));
    }

    /**
     * 使用配置选项调用
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 15:17
     * @param array $options
     * @return \GuzzleHttp\Client
     */
    protected function withOptions(array $options = []): Client
    {
        return $this->getClient($this->buildOptions($options));
    }

    /**
     * 构建选项
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 9:31
     * @param array         $options
     * @param callable|null $handler
     * @return array
     */
    protected function buildOptions(array $options, callable $handler = null): array
    {
        return array_replace_recursive($this->getClientConfig(), [
            'handler'         => $this->getHandlerStack($handler),
            'base_uri'        => null,
            'proxy'           => null,
            'allow_redirects' => [
                'max'             => 5,
                'protocols'       => ['http', 'https'],
                'strict'          => false,
                'referer'         => false,
                'track_redirects' => false,
            ],
            'http_errors'     => true,
            'decode_content'  => true,
            'verify'          => false,
            'cookies'         => false,
            'idn_conversion'  => false,
            'connect_timeout' => $this->getConnectTimeout(),
            'timeout'         => $this->getTimeout(),
            'headers'         => [
                'Content-Type' => 'application/json',
            ],
        ], $options);
    }

    /**
     * 发送同步请求
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 9:41
     * @param string $method
     * @param string $url
     * @param array  $params
     * @param array  $options
     * @return \Sweeper\GuzzleHttpRequest\Response
     */
    protected function doSyncRequest(string $method, string $url, array $params = [], array $options = []): Response
    {
        try {
            $response = $this->getClient()->request($method, $url, array_replace($params, $options));

            if (!$this->assertHttpSuccess($code = $response->getStatusCode())) {
                return Response::error("Response Error[{$code}]:" . json_encode($response, JSON_UNESCAPED_UNICODE));
            }

            if (is_null($response) || !is_object($response)) {
                return Response::error('Format Error:' . json_encode($response, JSON_UNESCAPED_UNICODE));
            }

            /** 解析结果 */
            return $this->resolveResponse($response);
        } catch (RequestException $e) {
            return Response::error($e->getMessage(), [
                'request'  => $e->getRequest()->getBody()->getContents(),// 请求失败这里一般是拿不到数据的
                'response' => $e->getResponse() ? $e->getResponse()->getBody()->getContents() : null,
            ]);
        } catch (\Throwable $e) {
            return Response::error($e->getMessage());
        }
    }

    /**
     * 发送同步请求
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 13:04
     * @param string $method
     * @param string $url
     * @param array  $params
     * @param array  $options
     * @return \Sweeper\GuzzleHttpRequest\Response
     */
    protected function sendSyncRequest(string $method, string $url, array $params = [], array $options = []): Response
    {
        return $this->doSyncRequest($method, $url, $params, $this->buildOptions($options));
    }

    /**
     * 断言请求成功
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 13:06
     * @param int $code
     * @return bool
     */
    protected function assertHttpSuccess(int $code): bool
    {
        return HttpCode::assertSuccess($code, false);
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
     * 查询字符串参数
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 16:40
     * @param $data
     * @return array
     */
    public static function withQuery($data): array
    {
        return ['query' => $data];
    }

    /**
     * 上传原始数据
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 16:41
     * @param $data
     * @return array
     */
    public static function withBody($data): array
    {
        return ['body' => $data];
    }

    /**
     * 上传JSON数据
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 16:41
     * @param $data
     * @return array
     */
    public static function withJson($data): array
    {
        return ['json' => $data];// application/json
    }

    /**
     * 发送表单字段
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 16:41
     * @param $data
     * @return array
     */
    public static function withFormParams($data): array
    {
        return ['form_params' => $data];// application/x-www-form-urlencoded
    }

    /**
     * 发送表单字段
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 16:41
     * @param $data
     * @return array
     */
    public static function withMultipart($data): array
    {
        return ['multipart' => $data];// multipart/form-data
    }

    /**
     * 第三方接口返回内容解析
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 13:01
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return \Sweeper\GuzzleHttpRequest\Response
     */
    abstract public function resolveResponse(ResponseInterface $response): Response;

}