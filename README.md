# PSR-15/PSR-7 HTTP Library
[![Build Status](https://travis-ci.org/timothyfisherdev/routing.svg?branch=master)](https://travis-ci.org/timothyfisherdev/routing) [![Coverage Status](https://coveralls.io/repos/github/timothyfisherdev/routing/badge.svg?branch=master)](https://coveralls.io/github/timothyfisherdev/routing?branch=master)

This is a very simple PHP library that models the request/response lifecycle using PSR-7 message objects, and PSR-15 middleware interfaces.

**This was mainly created for learning and educational purposes, and for getting more comfortable with PHPUnit, Travis, and Coveralls.**


# Overview
The purpose of this library is to create a broad outline of the request/response pattern in modern web development. The request/response are modeled through PSR-7 objects: `Psr\Http\Message\ServerRequestInterface` and `Psr\Http\Message\ResponseInterface`.

PSR-15 provides two new interfaces that utilize these objects:

 - `Psr\Http\Server\RequestHandlerInterface`
 - `Psr\Http\Server\MiddlewareInterface`

The `RequestHandlerInterface` is used to define an object that will handle a PSR-7 request. Typically, this is done through some sort of "kernel" object that accepts an initial request, and sends the request through a "queue" or stack of `MiddlewareInterface` objects.

There are two common approaches to how middleware can interpret the request and produce a response: `Single Pass` and `Double Pass`.

According to the [PSR-15 Meta Document](https://www.php-fig.org/psr/psr-15/meta/#5-middleware-approaches), the `Single Pass` approach was chosen due to problems with the unreliability of the `Double Pass` approach. Since middleware objects in the stack are independent and not conscious of each other, passing an empty response object in addition to the request object through the queue causes the response to be ambiguous since the currently acting middleware doesn't know the current state of the response object.

>In this form, middleware has no access to a response until one is generated by the request handler. Middleware can then modify the response before returning it ([PSR-15 Meta](https://www.php-fig.org/psr/psr-15/meta/#52-single-pass-lambda)).

Instead, with the `Single Pass` approach, only the request object is passed to each middleware. The middleware can then decide whether to create a response or pass the request to the next middleware. If the middleware decides to create a response, it can delegate its creation to a factory, but that is outside the scope of this library, and is currently in the works as a separate PSR: [PSR-17](https://www.php-fig.org/psr/#draft).

If the middleware decides to relay the request to the next middleware, it sends it back to the kernel which advances its internal pointer in the queue to grab the next middleware to invoke. If all of the middleware in the queue have been invoked, the request handler should have a way to fallback to a default handler in case no middlewares can create a response.

As stated in the [PSR-15 Meta Docs](https://www.php-fig.org/psr/psr-15/#13-generating-responses):
>It is RECOMMENDED that any middleware or request handler that generates a response will either compose a prototype of a PSR-7 `ResponseInterface` or a factory capable of generating a `ResponseInterface` instance in order to prevent dependence on a specific HTTP message implementation.

As such, the `Http\HttpKernel` object accepts a "fallback handler" which is an instance of `Psr\Http\Server\RequestHandlerInterface` to call when all middleware objects have been exhausted. Each middleware interface is also passed an `Http\HandlerProxy` proxy object that exposes access to the `HttpKernel::handle()` method. If a middleware determines that it cannot delegate to its factory to create a response, the kernel handle method is called again, which directs the request to the next middleware, or executes the fallback handler. This is described in just a few lines in the `Http\HttpKernel` class:

```php
public function handle(ServerRequestInterface $request) : ResponseInterface
{
    // if we have processed all middleware, invoke fallback handler
    if ($this->queue->processed()) {
        return $this->fallbackHandler->handle($request);
    }

    // if not, invoke the next middleware
    return $this->queue->process($request, $this->proxy);
}
```

This is also described in detail [here](https://www.php-fig.org/psr/psr-15/meta/#queue-based-request-handler).
