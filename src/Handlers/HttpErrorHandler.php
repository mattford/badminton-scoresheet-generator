<?php
namespace Mattford\WsmOpenScoresheet\Handlers;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpException;
use Slim\Views\Twig;
use Throwable;

class HttpErrorHandler
{
    public function __construct(private $app) {}

    public function handle(
        ServerRequestInterface $request,
        Throwable $exception,
        bool $displayErrorDetails
    ): ResponseInterface {
        $statusCode = 500;
        $description = 'An internal error has occurred while processing your request.';

        if ($exception instanceof HttpException) {
            $statusCode = $exception->getCode();
            $description = $exception->getMessage();
        } elseif ($displayErrorDetails) {
            $description = $exception->getMessage();
        }
        error_log($exception->getMessage());
        $response = $this->app->getResponseFactory()->createResponse();

        if ($request->getHeader('Accept') === 'application/json') {
            $error = [
                'statusCode' => $statusCode,
                'error' => [
                    'description' => $description,
                ],
            ];

            $payload = json_encode($error, JSON_PRETTY_PRINT);
            $response->getBody()->write($payload);
            return $response;
        }

        $view = Twig::fromRequest($request);
        return $view->render($response, 'error.twig', [
            'title' => 'Error ' . $statusCode,
            'code' => $statusCode,
            'description' => $description,
        ]);
    }
}