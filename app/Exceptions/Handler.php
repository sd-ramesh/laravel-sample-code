<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use App\Traits\SendResponseTrait;

class Handler extends ExceptionHandler
{
    use SendResponseTrait;
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
     *
     * @throws \Exception
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @throws \Exception
     */
    public function render($request, Exception $exception)
    {
        $rendered = parent::render($request, $exception);
        if ($request->is('api/*')) { //add Accept: application/json in request
            if($exception instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException){
                return $this->apiResponse('error', $rendered->getStatusCode(), 'Method is not allowed');
            }
            if ($exception instanceof \Illuminate\Contracts\Container\BindingResolutionException) {
                return $this->apiResponse('error', $rendered->getStatusCode(), 'Access not allowed');
            }
            if ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return $this->apiResponse('error', $rendered->getStatusCode(), 'Access not allowed');
            }
            if ($exception instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                return $this->apiResponse('error', $rendered->getStatusCode(), 'Try to access undefined api');
            }
         }
          else {
            // if ($this->isHttpException($exception)) {
            //     if ($exception->getStatusCode() == 404) {
            //         return response()->view('errors.' . '404');
            //     }
            // }
            // if($exception instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException){
            //     return $this->apiResponse('error', $rendered->getStatusCode(), 'Method is not allowed');
            // }
            // if($exception instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException){
            //     return $this->apiResponse('error', $rendered->getStatusCode(), 'Method is not allowed');
            // }
            // if ($exception instanceof \Illuminate\Contracts\Container\BindingResolutionException) {
            //     return $this->apiResponse('error', $rendered->getStatusCode(), 'Access not allowed');
            // }
            // if ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            //     return $this->apiResponse('error', $rendered->getStatusCode(), 'Access not allowed');
            // }
            // if ($exception instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            //     return $this->apiResponse('error', $rendered->getStatusCode(), 'Try to access undefined api');
            // }                                                                                       
         }
        return $rendered;
        // return parent::render($request, $exception);


    }
}
