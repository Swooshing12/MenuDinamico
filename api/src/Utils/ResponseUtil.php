<?php
// En api/src/Utils/ResponseUtil.php, agregar estos métodos:

namespace App\Utils;

use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Response;

class ResponseUtil
{
    // ✅ MÉTODOS EXISTENTES (mantener igual)
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

    // ✅ NUEVOS MÉTODOS QUE FALTAN
    
    /**
     * Respuesta para conflictos de recursos (409)
     * Usado cuando ya existe un recurso con los mismos datos únicos
     */
    public static function conflict($message = 'Conflicto: El recurso ya existe', $details = null): ResponseInterface
    {
        return self::error($message, 409, $details);
    }
    
    /**
     * Respuesta para recursos no procesables (422)
     * Usado cuando la sintaxis es correcta pero los datos no son válidos
     */
    public static function unprocessableEntity($message = 'Datos no procesables', $validationErrors = null): ResponseInterface
    {
        return self::error($message, 422, $validationErrors);
    }
    
    /**
     * Respuesta para acceso prohibido (403)
     * Usado cuando el usuario no tiene permisos suficientes
     */
    public static function forbidden($message = 'Acceso prohibido'): ResponseInterface
    {
        return self::error($message, 403);
    }
    
    /**
     * Respuesta para demasiadas peticiones (429)
     * Usado para rate limiting
     */
    public static function tooManyRequests($message = 'Demasiadas peticiones', $retryAfter = null): ResponseInterface
    {
        $response = new Response();
        
        $responseData = [
            'success' => false,
            'message' => $message,
            'code' => 429,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        if ($retryAfter) {
            $responseData['retry_after'] = $retryAfter;
        }
        
        $response->getBody()->write(json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        $httpResponse = $response->withStatus(429)->withHeader('Content-Type', 'application/json; charset=utf-8');
        
        if ($retryAfter) {
            $httpResponse = $httpResponse->withHeader('Retry-After', $retryAfter);
        }
        
        return $httpResponse;
    }
    
    /**
     * Respuesta exitosa para recursos creados (201)
     */
    public static function created($data = null, $message = 'Recurso creado exitosamente', $location = null): ResponseInterface
    {
        $response = new Response();
        
        $responseData = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s'),
            'code' => 201
        ];
        
        $response->getBody()->write(json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        $httpResponse = $response->withStatus(201)->withHeader('Content-Type', 'application/json; charset=utf-8');
        
        if ($location) {
            $httpResponse = $httpResponse->withHeader('Location', $location);
        }
        
        return $httpResponse;
    }
    
    /**
     * Respuesta exitosa sin contenido (204)
     * Usado para eliminaciones exitosas
     */
    public static function noContent(): ResponseInterface
    {
        $response = new Response();
        return $response->withStatus(204);
    }
}
?>