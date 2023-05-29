<?php
namespace Crm\Apis\Traits;

use Symfony\Component\HttpFoundation\Response;

trait ApiResponse{
    public function successResponse($data,$msg=null, $aimstatus="AIMS001", $statusCode=200)
    {
        return response()->json([
            'status' => $aimstatus,
            'message' => $msg,
            'data' =>$data
        ],$statusCode);
    }

    public function errorResponse($data,$msg="Error",$aimstatus="AIMS002",$statusCode=Response::HTTP_BAD_REQUEST)
    {
        return response()->json([
            'status' => $aimstatus,
            'message' => $msg,
            'data' =>$data
        ],$statusCode);
        
    }
    
}