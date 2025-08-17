<?php
namespace App\Utils;

use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Response;

class ResponseUtil
{
    // Punto 9: Parámetros bien estructurados en JSON
    public static function success($data = null, $message = 'Operación exitosa', $code = 200): ResponseInterface
    {
        $response = new Response();
        
        $responseData = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s'),
            'code' => $code
        ];
        
        $response->getBody()->write(json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $response->withStatus($code)->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
    
    // Punto 10: Mensajes de error claros y útiles
    public static function error($message = 'Error interno del servidor', $code = 500, $details = null): ResponseInterface
    {
        $response = new Response();
        
        $responseData = [
            'success' => false,
            'message' => $message,
            'code' => $code,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if ($details) {
            $responseData['details'] = $details;
        }
        
        $response->getBody()->write(json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $response->withStatus($code)->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
    
    public static function notFound($message = 'Recurso no encontrado'): ResponseInterface
    {
        return self::error($message, 404);
    }
    
    public static function badRequest($message = 'Solicitud incorrecta', $validationErrors = null): ResponseInterface
    {
        return self::error($message, 400, $validationErrors);
    }
    
    public static function unauthorized($message = 'Credenciales incorrectas'): ResponseInterface
    {
        return self::error($message, 401);
    }
}
?>