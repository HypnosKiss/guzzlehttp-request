<?php

namespace Sweeper\GuzzleHttpRequest;

use Sweeper\DesignPattern\Traits\MultiPattern;

/**
 * 请求摘要
 * Created by PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * Time: 2023/8/18 18:55
 * @Package \Sweeper\GuzzleHttpRequest\Request
 */
abstract class Request
{

    use MultiPattern, RequestTrait;

    /** @var string GET：读取（Read）请求指定的页面信息，并返回实体主体。 */
    public const  GET = 'GET';

    /** @var string POST：新建（Create）向指定资源提交数据进行处理请求（例如提交表单或者上传文件）。数据被包含在请求体中。POST 请求可能会导致新的资源的建立和/或已有资源的修改。 */
    public const  POST = 'POST';

    /** @var string PUT：更新（Update）从客户端向服务器传送的数据取代指定的文档的内容。 */
    public const  PUT = 'PUT';

    /** @var string 删除（Delete）请求服务器删除指定的页面。 */
    public const  DELETE = 'DELETE';

    /** @var string 更新（Update），通常是部分更新，是对 PUT 方法的补充，用来对已知资源进行局部更新 。 */
    public const  PATCH = 'PATCH';

    /** @var string CONNECT：HTTP/1.1 协议中预留给能够将连接改为管道方式的代理服务器。 */
    public const  CONNECT = 'CONNECT ';

    /** @var string HEAD：类似于 GET 请求，只不过返回的响应中没有具体的内容，用于获取报头 */
    public const  HEAD = 'HEAD';

    /** @var string OPTIONS：允许客户端查看服务器的性能。 */
    public const  OPTIONS = 'OPTIONS';

    /** @var string TRACE：回显服务器收到的请求，主要用于测试或诊断。 */
    public const  TRACE = 'TRACE';

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
    protected function buildRequestUri(string $path, string $domain = null): string
    {
        return trim($domain ?? $this->getServerDomain(), '/') . '/' . trim($this->getServerPath(trim($path, '/')), '/');
    }

}
