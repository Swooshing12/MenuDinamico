<?php
namespace App\Validators;

class PasswordValidator
{
    public static function validate($password): array
    {
        $errors = [];
        
        // Verificar que no esté vacía
        if (empty($password)) {
            $errors[] = 'La contraseña es requerida';
            return $errors;
        }
        
        // Verificar longitud mínima
        if (strlen($password) < 8) {
            $errors[] = 'La contraseña debe tener al menos 8 caracteres';
        }
        
        // Verificar longitud máxima
        if (strlen($password) > 50) {
            $errors[] = 'La contraseña no puede tener más de 50 caracteres';
        }
        
        // Verificar que contenga al menos una letra minúscula
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos una letra minúscula';
        }
        
        // Verificar que contenga al menos una letra mayúscula
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos una letra mayúscula';
        }
        
        // Verificar que contenga al menos un número
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'La contraseña debe contener al menos un número';
        }
        
        // Verificar que no contenga espacios
        if (preg_match('/\s/', $password)) {
            $errors[] = 'La contraseña no puede contener espacios';
        }
        
        // Verificar que no sea una contraseña común
        $passwordsComunes = [
            '12345678', '123456789', 'password', 'qwerty123', 
            'abc12345', '11111111', '22222222', '33333333',
            'password123', 'admin123', 'user1234', 'test1234'
        ];
        
        if (in_array(strtolower($password), $passwordsComunes)) {
            $errors[] = 'La contraseña es demasiado común, por favor elija una más segura';
        }
        
        return $errors;
    }
    
    public static function isValid($password): bool
    {
        return empty(self::validate($password));
    }
    
    // Método para evaluar la fortaleza de la contraseña
    public static function evaluarFortaleza($password): array
    {
        $fortaleza = 0;
        $detalles = [];
        
        if (strlen($password) >= 8) {
            $fortaleza += 1;
            $detalles[] = 'Longitud adecuada';
        }
        
        if (preg_match('/[a-z]/', $password)) {
            $fortaleza += 1;
            $detalles[] = 'Contiene minúsculas';
        }
        
        if (preg_match('/[A-Z]/', $password)) {
            $fortaleza += 1;
            $detalles[] = 'Contiene mayúsculas';
        }
        
        if (preg_match('/[0-9]/', $password)) {
            $fortaleza += 1;
            $detalles[] = 'Contiene números';
        }
        
        if (preg_match('/[^a-zA-Z0-9]/', $password)) {
            $fortaleza += 1;
            $detalles[] = 'Contiene caracteres especiales';
        }
        
        $nivel = 'Muy débil';
        if ($fortaleza >= 4) $nivel = 'Fuerte';
        elseif ($fortaleza >= 3) $nivel = 'Moderada';
        elseif ($fortaleza >= 2) $nivel = 'Débil';
        
        return [
            'puntuacion' => $fortaleza,
            'nivel' => $nivel,
            'detalles' => $detalles
        ];
    }
}
?>