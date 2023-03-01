<?php
namespace Crm\Apis\Traits;

use Symfony\Component\HttpFoundation\Response;

trait ApiResponse{
    public function successResponse($data,$msg=null,$statusCode=200)
    {
        return response()->json([
            'status' => 'AIMS001',
            'message' => $msg,
            'data' =>$data
        ],$statusCode);
    }

    public function errorResponse($data,$msg=null,$statusCode=Response::HTTP_BAD_REQUEST)
    {
        return response()->json([
            'status' => 'AIMS002',
            'message' => $msg,
            'data' => $data
        ],$statusCode);
        
    }
    
}