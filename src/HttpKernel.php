<?php

namespace Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * This class models the HTTP request/response lifecycle.
 *
 * A "kernel" is the heart or center piece of a process.
 * This class dictates the entire HTTP cycle of accepting
 * a request and emitting a response.
 *
 * This class boils entire complex systems and frameworks
 * down into one simple process at its surface.
 *
 * The kernel accepts a queue of middleware that must be called
 * in order. It also accepts a fallback handler that can be used
 * once all middleware have been exhausted.
 *
 * Per PSR-15, middleware have the ability to delegate to a factory
 * to create a response, or pass the request on to the next middleware.
 *
 * In the case that the middleware passes the request, it calls $handler->handle(),
 * which routes to this classes handle method. The kernel checks if the entire 
 * queue has been processed, and if it hasn't, invokes the next middleware. 
 *
 * However, if it has, it will finally call the fallback handler.
 */
class HttpKernel implements RequestHandlerInterface
{
	/**
	 * The HTTP queue.
	 * 
	 * @var Queue
	 */
	protected $queue;

	/**
	 * Handler to be called once all middleware have been invoked.
	 * 
	 * @var RequestHandlerInterface
	 */
	protected $fallbackHandler;

	/**
	 * A proxy for this class.
	 * 
	 * @var self
	 */
	protected $proxy;

	/**
	 * Build the kernel.
	 *
	 * We accept the HTTP queue, and a request handler that will be invoked
	 * if all middleware in the queue have been invoked, and no response was
	 * created.
	 * 
	 * @param Queue                   $queue           The HTTP queue.
	 * @param RequestHandlerInterface $fallbackHandler Called once all middleware invoked.
	 */
	public function __construct(Queue $queue, RequestHandlerInterface $fallbackHandler)
	{
		$this->queue = $queue;
		$this->fallbackHandler = $fallbackHandler;
		$this->proxy = new HandlerProxy($this);
	}

	/**
	 * Handle a request.
	 *
	 * This method is called at the beginning of the HTTP lifecycle,
	 * and each time a middleware passes the request to the next 
	 * middleware.
	 *
	 * First we check to see if all middleware have been exhausted.
	 *
	 * If so, we use the fallback handler to create a response.
	 *
	 * If not, we invoke the next middleware.
	 * 
	 * @param  ServerRequestInterface $request The server request.
	 * 
	 * @return ResponseInterface
	 */
	public function handle(ServerRequestInterface $request) : ResponseInterface
	{
		if ($this->queue->processed()) {
			return $this->fallbackHandler->handle($request);
		}

		return $this->queue->process($request, $this->proxy);
	}
}