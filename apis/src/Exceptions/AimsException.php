<?php
namespace Crm\Apis\Exceptions;

use Crm\Apis\Traits\ApiResponse;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Throwable;

class AimsException extends Exception
{
    use ApiResponse;

    protected $previousException;
    private $data;
    protected $message;

    public function __construct(
        string $message=null,
        mixed $data=null,
        Throwable $exception =null
    ) {
        $this->data = $data;
        $this->message = $message;
        $this->previousException = $exception;
        parent::__construct($this->message);
    }

    public function render($exception)
    {
        if(isset($this->previousException)){
            $exception = $this->previousException;
        };
        $response = $this->handleException($exception);
        return $response;

    }

    public function handleException(Throwable $e)
    {
        if ($e instanceof ModelNotFoundException) {
            return $this->errorResponse('Entry for '.str_replace('App', '', $e->getModel()).' not found',$this->message, Response::HTTP_NOT_FOUND);
        }
        elseif($e instanceof MethodNotAllowedException){//405
            return $this->errorResponse('The specified method for the request is invalid',$this->message, Response::HTTP_METHOD_NOT_ALLOWED);
        }
        elseif($e instanceof UnauthorizedHttpException){//401
            return $this->errorResponse('You are not authorized to access the resource',$this->message, Response::HTTP_NOT_FOUND);
        }
        elseif($e instanceof NotFoundHttpException){//404
            return $this->errorResponse('The specified URL cannot be found',$this->message, Response::HTTP_NOT_FOUND);
        }
        elseif($e instanceof HttpException){
            return $this->errorResponse($e->getMessage(), $e->getStatusCode());
        }
        elseif($e instanceof ValidationException){
            return $this->errorResponse($this->data,$this->message, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // if(config('debug')){
        //     return throw new Exception($e);
        // }
        
        return $this->errorResponse($this->data,$this->message, Response::HTTP_INTERNAL_SERVER_ERROR);
    }

}