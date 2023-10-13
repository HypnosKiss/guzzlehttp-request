<?php
/**
 * Created by PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/9/15 16:08
 */

use Sweeper\GuzzleHttpRequest\ServiceRequest;

require_once dirname(__DIR__) . '/vendor/autoload.php';

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
function withRequestParams($platform, $accountId, array $params = [], array $requestParams = []): array
{
    $requestParams         = array_replace_recursive($requestParams, [
        'platform'   => $platform,
        'account_id' => $accountId,
        'params'     => json_encode($params),
        'partner_id' => 390627,
        'timestamp'  => time(),
    ]);
    $requestParams['sign'] = ServiceRequest::generateSign($requestParams, 'QKU5pHqmxXnSRkoh8yZvzwu7rEeaNYBMLIiW9f41JAcsVg3ODjlbt0G2TdPCF6');

    return $requestParams;
}

$order_id      = '5271164584257';
$url           = "http://middleware.tenflyer.com/v1/shopify/order/get_order_detail";
$requestParams = withRequestParams('shopify', 5001728, ['order_id' => $order_id]);
// $params        = ServiceRequest::withBody(json_encode($requestParams));
// $params        = ServiceRequest::withJson($requestParams);
$params = ServiceRequest::withFormParams($requestParams);
$rs     = ServiceRequest::instance()->post($url, $params, ServiceRequest::withRetry(ServiceRequest::withLog(ServiceRequest::withTap(ServiceRequest::withDelay(ServiceRequest::withDebug())))))->getSuccessResponse(200);
var_dump($rs);
var_dump(ServiceRequest::instance()->v2()->getVersion());
var_dump(ServiceRequest::getLoggerMiddleware());