<?php

namespace Sweeper\GuzzleHttpRequest;

use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * 服务请求
 * Created by PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/9/15 14:11
 * @Package \Sweeper\GuzzleHttpRequest\ServiceRequest
 */
class ServiceRequest extends CommonRequest
{

    protected $successCode = 0;

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
            'params'     => json_encode($params),
            'partner_id' => $appKey,
            'timestamp'  => time(),
        ]);
        $requestParams['sign'] = $this->generateSign($requestParams, $secretKey);

        return $requestParams;
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
     * 解析响应数据
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 15:46
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return \Sweeper\GuzzleHttpRequest\Response
     */
    public function resolveResponse(ResponseInterface $response): Response
    {
        //返回结果解析
        $httpCode        = $response->getStatusCode();
        $responseContent = json_decode($response->getBody()->getContents() ?? '', true);
        $logicCode       = $responseContent['code'] ?? -1;
        $message         = $responseContent['message'] ?? $responseContent['msg'] ?? $response->getReasonPhrase();
        $errors          = $responseContent['errors'] ?? [];
        if (!$this->assertHttpSuccess($httpCode)) {
            $message = "接口请求口成功，返回错误：{$message}[Request failed with HTTP Code {$httpCode}.]";

            return Response::error($message, $responseContent);
        }
        if (!$this->assertLogicSuccess($logicCode)) {
            $message = "接口请求口成功，返回错误：{$message}[Request succeeded with HTTP Code {$httpCode}.][with Logic Code {$logicCode}]";

            return Response::error($message, $responseContent);
        }
        if ($errors) {
            $message = implode(', ', $errors);
            $message = "接口请求口成功，返回错误：{$message}[Request succeeded with HTTP Code {$httpCode}.]";

            return Response::error($message, $responseContent);
        }

        return new Response(HttpCode::OK, $message, $responseContent);
    }

}
