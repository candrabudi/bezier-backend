<?php

namespace App\Exceptions;

use Doctrine\DBAL\Query\QueryException;
use Illuminate\Auth\Access\AuthorizationException;
use Throwable;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Illuminate\Auth\AuthenticationException;
class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */

    public function render($request, Throwable $exception)
    {
        if ($exception instanceof ModelNotFoundException) {
            
            $response = [
                'status' => false,
                'code' => 400,
                'message' => 'Data tidak ditemukan',
            ];
            return response()->json($response, 404);
        } else if ($exception instanceof AuthorizationException) {
            $response = [
                'status' => false,
                'code' => 403,
                'message' => 'You are not authorized to access this API',
            ];
            return response()->json($response, 403);
        }

    
        return parent::render($request, $exception);
    }
    public function handle($request, Closure $next)
    {
        if($request->header('Authorization')) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Not a valid API request.',
        ]);
    }
    protected function unauthenticated($request, AuthenticationException $exception) 
    {
        if($request->header('Authorization')) {
            if($exception){
                return response()->json([
                    'status' => "failed",
                    'code' => 403,
                    'message' => 'You are not authorized to access this API',
                ]);
            }
            return $next($request);
        }

        return response()->json([
            'status' => "failed",
            'code' => 403,
            'message' => 'You are not authorized to access this API',
        ]);
    }
}
