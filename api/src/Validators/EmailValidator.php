<?php
namespace App\Validators;

class EmailValidator
{
    /**
     * Validar formato de email
     */
    public static function validate($email)
    {
        $errores = [];
        
        // Verificar que no esté vacío
        if (empty($email)) {
            $errores[] = 'El email no puede estar vacío';
            return $errores;
        }
        
        // Limpiar espacios
        $email = trim($email);
        
        // Verificar formato básico
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El formato del email no es válido';
        }
        
        // Verificar longitud
        if (strlen($email) > 100) {
            $errores[] = 'El email no puede tener más de 100 caracteres';
        }
        
        // Verificar que contenga @ y .
        if (!str_contains($email, '@') || !str_contains($email, '.')) {
            $errores[] = 'El email debe contener @ y un dominio válido';
        }
        
        // Verificar que no tenga espacios
        if (str_contains($email, ' ')) {
            $errores[] = 'El email no puede contener espacios';
        }
        
        // Verificar dominio básico
        $partes = explode('@', $email);
        if (count($partes) === 2) {
            $dominio = $partes[1];
            if (strlen($dominio) < 3) {
                $errores[] = 'El dominio del email es muy corto';
            }
            
            // Verificar que el dominio tenga al menos un punto
            if (!str_contains($dominio, '.')) {
                $errores[] = 'El dominio debe contener al menos un punto';
            }
        }
        
        return $errores;
    }
    
    /**
     * Validar múltiples emails separados por coma
     */
    public static function validateMultiple($emails)
    {
        $errores = [];
        
        if (empty($emails)) {
            $errores[] = 'No se proporcionaron emails';
            return $errores;
        }
        
        $listaEmails = explode(',', $emails);
        
        foreach ($listaEmails as $index => $email) {
            $email = trim($email);
            $erroresEmail = self::validate($email);
            
            if (!empty($erroresEmail)) {
                $errores["email_" . ($index + 1)] = $erroresEmail;
            }
        }
        
        return $errores;
    }
    
    /**
     * Verificar si un email es de un dominio específico
     */
    public static function isDomain($email, $domain)
    {
        if (empty($email) || empty($domain)) {
            return false;
        }
        
        $partes = explode('@', $email);
        
        if (count($partes) !== 2) {
            return false;
        }
        
        return strtolower($partes[1]) === strtolower($domain);
    }
    
    /**
     * Extraer el dominio de un email
     */
    public static function getDomain($email)
    {
        if (empty($email)) {
            return null;
        }
        
        $partes = explode('@', $email);
        
        if (count($partes) !== 2) {
            return null;
        }
        
        return strtolower($partes[1]);
    }
    
    /**
     * Limpiar y normalizar email
     */
    public static function normalize($email)
    {
        if (empty($email)) {
            return '';
        }
        
        return strtolower(trim($email));
    }
}
?>