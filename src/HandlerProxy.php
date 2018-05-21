<?php

namespace Http;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * A proxy used to limit the scope of the HttpKernel.
 *
 * This class is used to avoid passing the entire HttpKernel
 * and all of its available methods/data to each middleware.
 *
 * Instead, we limit access to only the handle method for when
 * the middleware needs to bail out.
 *
 * This class also needs to be an instance of RequestHandlerInterface
 * since it will be passed to the MiddlewareInterface::process() 
 * method, which requires a RequestHandlerInterface.
 */
class HandlerProxy implements RequestHandlerInterface
{
	/**
	 * The request handler.
	 * 
	 * @var ReqestHandlerInterface
	 */
	private $resource;

	/**
	 * Accept the request handler that this proxy is for.
	 * 
	 * @param RequestHandlerInterface $resource The request handler.
	 */
	public function __construct(RequestHandlerInterface $resource)
	{
		$this->resource = $resource;
	}

	/**
	 * Call the resources handle method.
	 *
	 * This method limits the scope of the request handler resource
	 * that was passed in.
	 * 
	 * @param  ServerRequestInterface $request The request handler.
	 * 
	 * @return ResponseInterface
	 */
	public function handle(ServerRequestInterface $request) : ResponseInterface
	{
		return $this->resource->handle($request);
	}
}