<?php

namespace Http\Test;

use Http\Queue;
use Http\HandlerProxy;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Test the HTTP queue class.
 */
class QueueTest extends TestCase
{
	/**
	 * Test when not checking if the queue is processed.
	 * 
	 * @expectedException \LogicException
	 */
	public function testProcessNonExistantMiddlewareIndex()
	{
		$queue = new Queue();
		$fallbackHandler = $this->createMock(RequestHandlerInterface::class);
		$handler = new class($queue, $fallbackHandler) implements RequestHandlerInterface
		{
			public function __construct(Queue $queue, RequestHandlerInterface $fallbackHandler)
			{
				$this->queue = $queue;
				$this->fallbackHandler = $fallbackHandler;
				$this->proxy = new HandlerProxy($this);
			}

			public function handle(ServerRequestInterface $request) : ResponseInterface
			{
				return $this->queue->process($request, $this->proxy);
			}
		};

		$request = $this->createMock(ServerRequestInterface::class);
		$output = $handler->handle($request);
	}

	/**
	 * Test prepending middleware to the start of the queue.
	 */
	public function testPrepend()
	{
		$queue = new Queue();

		$firstMiddleware = $this->createMock(MiddlewareInterface::class);
		$secondMiddleware = $this->createMock(MiddlewareInterface::class);
		$middlewareArray = [$firstMiddleware, $secondMiddleware];

		$queue->append($secondMiddleware);
		$queue->prepend($firstMiddleware);

		$this->assertAttributeSame($middlewareArray, 'middlewares', $queue);
	}
}