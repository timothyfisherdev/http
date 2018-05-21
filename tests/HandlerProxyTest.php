<?php

namespace Http\Test;

use Http\HandlerProxy;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Test the Http\HandlerProxy class.
 */
class HandlerProxyTest extends TestCase
{
	/**
	 * Test creating a proxy.
	 */
	public function testProxyHandle()
	{
		$requestHandler = $this->createMock(RequestHandlerInterface::class);
		$request = $this->createMock(ServerRequestInterface::class);
		$response = $this->createMock(ResponseInterface::class);

		$requestHandler->expects($this->once())->method('handle')->willReturn($response);

		$proxy = new HandlerProxy($requestHandler);
		$output = $proxy->handle($request);

		$this->assertSame($response, $output);
	}
}