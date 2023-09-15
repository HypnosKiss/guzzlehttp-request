<?php

namespace Sweeper\GuzzleHttpRequest;

use BadMethodCallException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Sweeper\DesignPattern\Traits\MultiPattern;

/**
 * 请求摘要
 * Created by PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * Time: 2023/8/18 18:55
 * @Package \Sweeper\GuzzleHttpRequest\Request
 * @method $this get(string $url, array $params = [], array $options = [])
 * @method $this post(string $url, array $params = [], array $options = [])
 * @method $this put(string $url, array $params = [], array $options = [])
 * @method $this delete(string $url, array $params = [], array $options = [])
 * @method $this patch(string $url, array $params = [], array $options = [])
 * @mixin Client
 */
abstract class Request
{

    use MultiPattern;

    /** @var string GET：读取（Read）请求指定的页面信息，并返回实体主体。 */
    public const  METHOD_GET = 'GET';

    /** @var string POST：新建（Create）向指定资源提交数据进行处理请求（例如提交表单或者上传文件）。数据被包含在请求体中。POST 请求可能会导致新的资源的建立和/或已有资源的修改。 */
    public const  METHOD_POST = 'POST';

    /** @var string PUT：更新（Update）从客户端向服务器传送的数据取代指定的文档的内容。 */
    public const  METHOD_PUT = 'PUT';

    /** @var string 删除（Delete）请求服务器删除指定的页面。 */
    public const  METHOD_DELETE = 'DELETE';

    /** @var string 更新（Update），通常是部分更新，是对 PUT 方法的补充，用来对已知资源进行局部更新 。 */
    public const  METHOD_PATCH = 'PATCH';

    /** @var string CONNECT：HTTP/1.1 协议中预留给能够将连接改为管道方式的代理服务器。 */
    public const  METHOD_CONNECT = 'CONNECT ';

    /** @var string HEAD：类似于 GET 请求，只不过返回的响应中没有具体的内容，用于获取报头 */
    public const  METHOD_HEAD = 'HEAD';

    /** @var string OPTIONS：允许客户端查看服务器的性能。 */
    public const  METHOD_OPTIONS = 'OPTIONS';

    /** @var string TRACE：回显服务器收到的请求，主要用于测试或诊断。 */
    public const  METHOD_TRACE = 'TRACE';

    /** @var Client GuzzleHttp Client */
    private $client;

    /** @var int 默认连接超时时间 */
    private $connectTimeout = 10;

    /** @var int 默认超时时间 */
    private $timeout = 60;

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

    /**
     * 获取客户端
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 9:19
     * @param array $config
     * @return \GuzzleHttp\Client
     */
    public function getClient(array $config = []): Client
    {
        if ($this->client instanceof Client) {
            return $this->client;
        }

        return $this->client = $this->withClient($config);
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
     * 配置客户端
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 9:22
     * @param array $config
     * @return \GuzzleHttp\Client
     */
    public function withClient(array $config = []): Client
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
    public function withOptions(array $options = []): Client
    {
        return $this->getClient(array_replace_recursive([
            'headers'         => [
                'Content-Type' => 'application/json',
            ],
            'verify'          => false,
            'connect_timeout' => $this->getConnectTimeout(),
            'timeout'         => $this->getTimeout(),
        ], $options));
    }

    /**
     * 获取服务 URL 前缀 协议+域名：https://www.baidu.com
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 9:31
     * @return string
     */
    protected function getServerDomain(): string
    {
        return $this->getClientConfig()['base_uri'] ?? $this->getClientConfig()['domain'] ?? '';
    }

    /**
     * 获取服务请求的路径
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 9:31
     * @param string $path 资源路径
     * @return string
     */
    protected function getServerPath(string $path): string
    {
        return $path;
    }

    /**
     * 构建请求地址
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 9:31
     * @param string      $path
     * @param string|null $domain
     * @return string
     */
    public function buildRequestUri(string $path, string $domain = null): string
    {
        return trim($domain ?? $this->getServerDomain(), '/') . '/' . trim($this->getServerPath(trim($path, '/')), '/');
    }

    /**
     * 构建选项
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 9:31
     * @param array $options
     * @return array
     */
    protected function buildOptions(array $options): array
    {
        return array_replace_recursive($this->getClientConfig(), [
            'headers'         => [
                'Content-Type' => 'application/json',
            ],
            'verify'          => false,
            'connect_timeout' => $this->getConnectTimeout(),
            'timeout'         => $this->getTimeout(),
        ], $options);
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
        $method = "METHOD_" . strtoupper($name);
        if (defined(static::class . "::$method")) {
            return $this->doSyncRequest(constant(static::class . "::$method"), ...$arguments);
        }
        if (method_exists($this->getClient(), $name)) {
            return $this->getClient()->{$name}(...$arguments);
        }
        if (method_exists($this, $name)) {
            return $this->{$name}(...$arguments);
        }

        throw new BadMethodCallException('Call Undefined method');
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
     * 发送同步请求
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 9:41
     * @param string $method
     * @param string $url
     * @param array  $params
     * @param array  $options
     * @return \Sweeper\GuzzleHttpRequest\Response
     */
    private function doSyncRequest(string $method, string $url, array $params = [], array $options = []): Response
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
    public function sendSyncRequest(string $method, string $url, array $params = [], array $options = []): Response
    {
        return $this->doSyncRequest($method, $url, $params, $this->buildOptions($options));
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
     * 断言请求成功
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 13:06
     * @param int $code
     * @return bool
     */
    public function assertHttpSuccess(int $code): bool
    {
        return HttpCode::assertSuccess($code, false);
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
