<?php

namespace Sweeper\GuzzleHttpRequest;

use GuzzleHttp\Client;
use RuntimeException;

/**
 * 服务请求
 * Created by PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/9/15 14:11
 * @Package \Sweeper\GuzzleHttpRequest\ServiceRequest
 */
class ServiceRequest extends CommonRequest
{

    /** @var int API 成功 Code */
    public const CODE_API_SUCCESS = 0;

    /** @var int API 失败 Code */
    public const CODE_API_FAILURE = 1;

    /** @var int 成功 Code */
    public const CODE_SUCCESS = 0;

    /** @var int 失败 Code */
    public const CODE_FAILURE = 1;

    /** @var string 密钥 */
    protected $secretKey = '';

    /** @var int 成功 CODE */
    protected $successCode = 200;

    public function getSecretKey(): string
    {
        return $this->secretKey ?: $this->getConfig('secretKey');
    }

    public function setSecretKey(string $secretKey): self
    {
        $this->secretKey = $secretKey;

        return $this;
    }

    /**
     * 发起请求
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/23 18:13
     * @param string|null $baseUrl
     * @param string      $extraUrl
     * @param array       $signParams
     * @param array       $params ['platform' => $platform, 'account_id' => $accountId, 'params' => json_encode($params, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE)]
     * @param array       $arrayMergeParams
     * @param array       $options
     * @param string      $method
     * @return array|mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function doRequest(string $baseUrl, string $extraUrl, array $signParams = [], array $params = [], array $arrayMergeParams = [], array $options = [], string $method = 'POST')
    {
        $client      = new Client(['base_uri' => $baseUrl]);
        $requestInfo = $this->sign($signParams, array_merge([
            'platform'   => $params['platform'] ?? '',
            'account_id' => $params['account_id'] ?? 0,
            'params'     => json_encode($params, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),
        ], $arrayMergeParams));// 签名
        $body        = array_replace(static::withJson($requestInfo), ['connect_timeout' => 30, 'timeout' => 120], $options);
        $response    = $client->request($method ?: 'POST', $extraUrl, $body);
        if (!is_object($response)) {
            throw new RuntimeException('网络响应超时，请重试！');
        }
        $contents = static::getResponseContents($response);
        if (empty($contents)) {
            throw new RuntimeException("请求响应异常,StatusCode：{$response->getStatusCode()}，Contents:" . $contents, $response->getStatusCode());
        }

        return json_decode($contents, true) ?: [];
    }

    /**
     * @param $signParams
     * @param $params
     * @return array
     */
    private function sign($signParams, $params): array
    {
        $params         = array_merge($signParams, $params);
        $sign           = $this->generateSign($params, $this->getSecretKey());
        $params['sign'] = $sign;

        return $params;
    }

}
