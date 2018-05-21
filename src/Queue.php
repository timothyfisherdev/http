<?php

namespace Http;

use LogicException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Models the "queue" or "stack" of middleware instances.
 *
 * This object is used by a request handler to push the PSR-7
 * request through an application.
 *
 * The queue itself is also an instance of MiddlewareInterface,
 * allowing queue's to be nested.
 */
class Queue implements MiddlewareInterface
{
	/**
	 * Ordered middleware.
	 * 
	 * @var array
	 */
	protected $middlewares = [];

	/**
	 * Internal middleware pointer.
	 *
	 * Starts at "-1" meaning "not started".
	 * 
	 * @var integer
	 */
	private $current = -1;

	/**
	 * Create the HTTP queue.
	 *
	 * Can append middlewares on instantiation.
	 * 
	 * @param array $middlewares Middleware interfaces.
	 */
	public function __construct(array $middlewares = [])
	{
		foreach ($middlewares as $middleware) {
			$this->append($middleware);
		}
	}

	/**
	 * Start processing the queue.
	 *
	 * Invokes the next middleware using the internal pointer.
	 * 
	 * @param  ServerRequestInterface  $request The PSR-7 request.
	 * @param  RequestHandlerInterface $handler The request handler (proxy)
	 *
	 * @throws \LogicException When middleware index does not exist. This means you have not
	 * checked whether the queue is processed or not.
	 * 
	 * @return ResponseInterface
	 */
	public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
	{
		$this->current++;

		if (!isset($this->middlewares[$this->current])) {
			throw new LogicException(sprintf(
				'Requested middleware in position [%d] does not exist. Check if the queue is processed first',
				$this->current
			));
		}

		return $this->middlewares[$this->current]->process($request, $handler);
	}

	/**
	 * Checks if all middleware have been invoked.
	 * 
	 * @return bool
	 */
	public function processed() : bool
	{
		return $this->current + 1 >= count($this->middlewares);
	}

	/**
	 * Append middleware to end of queue.
	 * 
	 * @param  MiddlewareInterface $middleware Middleware.
	 */
	public function append(MiddlewareInterface $middleware)
	{
		$this->middlewares[] = $middleware;
	}

	/**
	 * Prepend middleware to beginning of queue.
	 * 
	 * @param  MiddlewareInterface $middleware Middleware.
	 */
	public function prepend(MiddlewareInterface $middleware)
	{
		array_unshift($this->middlewares, $middleware);
	}
}