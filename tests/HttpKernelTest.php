<?php

namespace Http\Test;

use Http\Queue;
use Http\HttpKernel;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Test the HttpKernel class.
 */
class HttpKernelTest extends TestCase
{
	/**
	 * Test handling the request when there is no middleware added.
	 */
	public function testHandleNoMiddleware()
	{
		$request = $this->createMock(ServerRequestInterface::class);
		$response = $this->createMock(ResponseInterface::class);
		$fallbackHandler = $this->createMock(RequestHandlerInterface::class);

		$fallbackHandler->expects($this->once())->method('handle')->willReturn($response);

		$queue = new Queue();
		$handler = new HttpKernel($queue, $fallbackHandler);

		$output = $handler->handle($request);

		$this->assertSame($output, $response);
	}

	/**
	 * Test handling the request when there is middleware added and the queues are nested.
	 */
	public function testHandleWithMiddlewareAndNestedQueues()
	{
		$request = $this->createMock(ServerRequestInterface::class);
		$response = $this->createMock(ResponseInterface::class);
		$fallbackHandler = $this->createMock(RequestHandlerInterface::class);

		$counter = -1;

		$middleware1 = $this->createMock(MiddlewareInterface::class);
		$closure1 = function($request, $handler) use (&$counter) {
			$counter++;
			$this->counter = $counter;
			return $handler->handle($request, $handler);
		};
		$closure1 = $closure1->bindTo($middleware1, get_class($middleware1));

		$middleware2 = $this->createMock(MiddlewareInterface::class);
		$closure2 = function($request, $handler) use (&$counter, $response) {
			$counter++;
			$this->counter = $counter;
			return $response;
		};
		$closure2 = $closure2->bindTo($middleware2, get_class($middleware2));
		
		$middleware1->method('process')->will($this->returnCallback($closure1));
		$middleware2->method('process')->will($this->returnCallback($closure2));

		$nestedQueue = new Queue([$middleware2]);
		$queueFinal = new Queue([$middleware1, $nestedQueue]);
		$handler = new HttpKernel($queueFinal, $fallbackHandler);

		$output = $handler->handle($request, $fallbackHandler);

		$this->assertEquals(0, $middleware1->counter);
		$this->assertEquals(1, $middleware2->counter);
		$this->assertSame($response, $output);
	}
}