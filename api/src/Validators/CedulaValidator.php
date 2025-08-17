<?php
namespace App\Validators;

class CedulaValidator
{
    public static function validate($cedula): array
    {
        $errors = [];
        
        // Verificar que no esté vacía
        if (empty($cedula)) {
            $errors[] = 'La cédula es requerida';
            return $errors;
        }
        
        // Convertir a string y limpiar
        $cedula = (string) $cedula;
        $cedulaLimpia = preg_replace('/[^0-9]/', '', $cedula);
        
        // Verificar que solo contenga números
        if ($cedula !== $cedulaLimpia) {
            $errors[] = 'La cédula solo debe contener números';
        }
        
        // Verificar longitud exacta
        if (strlen($cedulaLimpia) !== 10) {
            $errors[] = 'La cédula debe tener exactamente 10 dígitos';
            return $errors;
        }
        
        // Validar que no sean todos números iguales
        if (preg_match('/^(\d)\1{9}$/', $cedulaLimpia)) {
            $errors[] = 'La cédula no puede tener todos los dígitos iguales';
        }
        
        // Validar algoritmo de cédula ecuatoriana
        if (!self::validarAlgoritmoCedulaEcuatoriana($cedulaLimpia)) {
            $errors[] = 'La cédula no es válida según el algoritmo de verificación ecuatoriano';
        }
        
        return $errors;
    }
    
    public static function isValid($cedula): bool
    {
        return empty(self::validate($cedula));
    }
    
    private static function validarAlgoritmoCedulaEcuatoriana($cedula): bool
    {
        $digitos = str_split($cedula);
        $region = intval(substr($cedula, 0, 2));
        
        // Verificar región válida (01-24 para Ecuador)
        if ($region < 1 || $region > 24) {
            return false;
        }
        
        // Algoritmo de validación para cédula ecuatoriana
        $suma = 0;
        $coeficientes = [2, 1, 2, 1, 2, 1, 2, 1, 2];
        
        for ($i = 0; $i < 9; $i++) {
            $digito = intval($digitos[$i]) * $coeficientes[$i];
            
            if ($digito >= 10) {
                $digito = $digito - 9;
            }
            
            $suma += $digito;
        }
        
        $digitoVerificador = intval($digitos[9]);
        $residuo = $suma % 10;
        $resultado = $residuo === 0 ? 0 : 10 - $residuo;
        
        return $resultado === $digitoVerificador;
    }
    
    // Método auxiliar para limpiar y formatear cédula
    public static function limpiarCedula($cedula): string
    {
        return preg_replace('/[^0-9]/', '', (string) $cedula);
    }
    
    // Método auxiliar para formatear cédula con guiones
    public static function formatearCedula($cedula): string
    {
        $cedulaLimpia = self::limpiarCedula($cedula);
        if (strlen($cedulaLimpia) === 10) {
            return substr($cedulaLimpia, 0, 2) . '-' . substr($cedulaLimpia, 2, 4) . '-' . substr($cedulaLimpia, 6, 4);
        }
        return $cedulaLimpia;
    }
}
?>