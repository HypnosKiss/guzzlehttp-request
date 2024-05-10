<?php
/**
 * Created by Sweeper PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/11/24 18:24
 */

use Concat\Http\Middleware\Logger;
use PHPUnit\Framework\TestCase;
use Sweeper\GuzzleHttpRequest\CommonRequest;
use Sweeper\GuzzleHttpRequest\Request;
use Sweeper\GuzzleHttpRequest\ServiceRequest;

require_once dirname(__DIR__) . '/vendor/autoload.php';

class ClientRequestTest extends TestCase
{

    /**
     * 使用请求参数
     * User: Sweeper
     * Time: 2023/3/10 14:11
     * @param       $platform
     * @param       $accountId
     * @param array $params
     * @param array $requestParams
     * @return array
     */
    public function withRequestParams($platform, $accountId, array $params = [], array $requestParams = []): array
    {
        $requestParams         = array_replace_recursive($requestParams, [
            'platform'   => $platform,
            'account_id' => $accountId,
            'params'     => json_encode($params, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),
            'partner_id' => 390627,
            'timestamp'  => time(),
        ]);
        $requestParams['sign'] = CommonRequest::generateSign($requestParams, 'QKU5pHqmxXnSRkoh8yZvzwu7rEeaNYBMLIiW9f41JAcsVg3ODjlbt0G2TdPCF6');

        return $requestParams;
    }

    /**
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/27 18:44
     * @return void
     */
    public function testRequest(): void
    {
        $requestParams = $this->withRequestParams('tiktok', 36675, ['orderIds' => ['577954482659428384']]);
        $params        = ServiceRequest::withFormParams($requestParams);
        $options       = ServiceRequest::withRetry(ServiceRequest::withLog(ServiceRequest::withTap(ServiceRequest::withDelay(ServiceRequest::withDebug()))));
        $rs            = ServiceRequest::post('http://middleware.tenflyer.com/v1/tiktok/order/get_order_detail', $params, $options);
        var_dump($rs);
        $this->assertInstanceOf(Request::class, ServiceRequest::instance());
    }

    /**
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/11/27 18:44
     * @return void
     */
    public function testServiceRequest(): void
    {
        $requestParams = $this->withRequestParams('tiktok', 36675, ['orderIds' => ['577954482659428384']]);
        $params        = ServiceRequest::withFormParams($requestParams);
        $options       = ServiceRequest::withRetry(ServiceRequest::withLog(ServiceRequest::withTap(ServiceRequest::withDelay(ServiceRequest::withDebug()))));
        $rs            = ServiceRequest::instance()->post('http://middleware.tenflyer.com/v1/tiktok/order/get_order_detail', $params, $options);
        var_dump($rs);
        $this->assertInstanceOf(Request::class, ServiceRequest::instance());
    }

    public function testClientDoRequest(): void
    {
        $requestParams = $this->withRequestParams('tiktok', 36675, ['orderIds' => ['577954482659428384']]);
        $params        = ServiceRequest::withFormParams($requestParams);
        $options       = ServiceRequest::withRetry(ServiceRequest::withLog(ServiceRequest::withTap(ServiceRequest::withDelay(ServiceRequest::withDebug()))));
        $rs            = ServiceRequest::instance()
                                       ->setMethod(ServiceRequest::POST)
                                       ->setUri('http://middleware.tenflyer.com/v1/tiktok/order/get_order_detail')
                                       ->setParams($params)
                                       ->setOptions($options)
                                       ->do();

        var_dump($rs);

        $this->assertInstanceOf(Request::class, ServiceRequest::instance());
    }

    public function testClientRequestVersion(): void
    {
        $this->assertEquals('v2', ServiceRequest::instance()->v2()->getVersion());
    }

    public function testClientRequestLoggerMiddleware(): void
    {
        $this->assertInstanceOf(Logger::class, ServiceRequest::getLoggerMiddleware());
    }

}
