<?php

namespace Sweeper\GuzzleHttpRequest;

/**
 * 后台返回标准封装
 * Created by PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/9/14 19:05
 * @Package \Sweeper\GuzzleHttpRequest\Response
 */
class Response
{

    use ResponseTrait;

    public const DEFAULT_HTML_ENCODING_OPTIONS = JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_AMP|JSON_HEX_QUOT;

    public const DEFAULT_ENCODING_OPTIONS      = JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE;

    /** @var int 成功 */
    public const CODE_SUCCESS = HttpCode::OK;

    /** @var int 后台失败 */
    public const CODE_COMMON_ERROR = HttpCode::INTERNAL_SERVER_ERROR;

    /** @var int 未知错误 */
    public const CODE_UNKNOWN_ERROR = HttpCode::SERVICE_UNAVAILABLE;

}
