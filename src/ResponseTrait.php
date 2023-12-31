<?php

namespace Sweeper\GuzzleHttpRequest;

/**
 * 后台返回标准封装
 * Created by PhpStorm.
 * Author: Sweeper <wili.lixiang@gmail.com>
 * DateTime: 2023/9/19 9:19
 * @Package \Sweeper\GuzzleHttpRequest\ResponseTrait
 */
trait ResponseTrait
{

    /** @var int|mixed 结果状态代码 */
    public $code;

    /** @var mixed|string 消息 */
    public $message;

    /** @var mixed|string uuid 请求的唯一ID */
    public $uuid;

    /** @var mixed|null 内容（格式未定，可能是数组，也可能是其他类型） */
    public $response;

    /**
     * User: Sweeper
     * Time: 2022/11/2 10:27
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * User: Sweeper
     * Time: 2022/11/2 10:27
     * @param int $code
     * @return $this;
     */
    public function setCode(int $code): self
    {
        $this->code = $code;

        return $this;
    }

    /**
     * User: Sweeper
     * Time: 2022/11/2 10:27
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * User: Sweeper
     * Time: 2022/11/2 10:27
     * @param string $message
     * @return $this
     */
    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    /**
     * User: Sweeper
     * Time: 2022/11/2 10:27
     * @return string
     */
    public function getUuid(): string
    {
        return $this->uuid;
    }

    /**
     * User: Sweeper
     * Time: 2022/11/2 10:27
     * @param string $uuid
     * @return $this
     */
    public function setUuid(string $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * User: Sweeper
     * Time: 2022/11/2 10:27
     * @return mixed|null
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * User: Sweeper
     * Time: 2022/11/2 10:27
     * @param $response
     * @return $this
     */
    public function setResponse($response): self
    {
        $this->response = $response;

        return $this;
    }

    /**
     * Response constructor.
     * @param int    $code
     * @param string $message
     * @param array  $response
     * @param string $uuid
     */
    public function __construct(int $code = HttpCode::INTERNAL_SERVER_ERROR, string $message = '', array $response = [], string $uuid = '')
    {
        $this->setCode($code)->setMessage($message)->setResponse($response)->setUuid($uuid ?: microtime(true));
    }

    /**
     * 返回成功结果
     * User: Sweeper
     * Time: 2022/11/2 10:24
     * @param string $message
     * @param array  $response
     * @param string $uuid
     * @return Response
     */
    public static function success(string $message = '', array $response = [], string $uuid = ''): self
    {
        return new static(HttpCode::OK, $message, $response, $uuid);
    }

    /**
     * 返回失败结果
     * User: Sweeper
     * Time: 2022/11/2 10:23
     * @param string $message
     * @param array  $response
     * @param string $uuid
     * @return Response
     */
    public static function error(string $message = '', array $response = [], string $uuid = ''): self
    {
        return new static(HttpCode::INTERNAL_SERVER_ERROR, $message, $response, $uuid);
    }

    /**
     * 返回数组结构
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 12:52
     * @return array
     */
    public function toArray(): array
    {
        return ['code' => $this->getCode(), 'message' => $this->getMessage(), 'response' => $this->getResponse(), 'uuid' => $this->getUuid()];
    }

    /**
     * 返回字符串结构
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 12:52
     * @return string
     */
    public function toString(): string
    {
        return json_encode($this->toArray(), static::DEFAULT_ENCODING_OPTIONS);
    }

    /**
     * 响应结果
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 12:52
     * @return string
     */
    public function toResult(): string
    {
        return $this->toString();
    }

    /**
     * 输出JSON
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 12:52
     * @return void
     */
    public function toJSON(): void
    {
        //声明header为json
        header('Content-type:application/json');

        echo $this->toString();

        die(0);
    }

    /**
     * 判断是否成功
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 12:55
     * @param int $code
     * @return bool
     */
    public function isSuccess(int $code = HttpCode::OK): bool
    {
        return $this->getCode() === $code;
    }

    /**
     * 断言成功
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 12:55
     * @param int $code
     * @return $this
     */
    public function assertSuccess(int $code = HttpCode::OK): self
    {
        if (!$this->isSuccess($code)) {
            $responseContents = $this->getResponse()['response'] ?? '';
            throw new \RuntimeException($this->getMessage() . ($responseContents ? " ({$responseContents})" : ''), $this->getCode());
        }

        return $this;
    }

    /**
     * 获取请求成功的响应
     * Author: Sweeper <wili.lixiang@gmail.com>
     * DateTime: 2023/9/15 12:55
     * @param int $code
     * @return mixed|null
     */
    public function getSuccessResponse(int $code = HttpCode::OK)
    {
        return $this->assertSuccess($code)->getResponse();
    }

}
