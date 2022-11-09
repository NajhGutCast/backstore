<?php

namespace App\gLibraries;

use App\Models\Usuario;
use App\gLibraries\gJSON;
use App\gLibraries\gStatus;
use Illuminate\Http\Request;
use Exception;

class gValidate
{
    public static function obtener(Request $request): array
    {
        $estado = 200;
        $mensaje = 'Operación correcta';
        $sesion = new Usuario();
        try {
            if (
                $request->header('SoDe-Auth-User') == null ||
                $request->header('SoDe-Auth-Token') == null
            ) {
                throw new Exception('Error: Debes enviar los encabezados de autenticación', 401);
            }

            $sesionJpa = Usuario::select([
                'usuarios.id AS id',
                'usuarios.estado AS estado',
                'roles.id AS rol.id',
                'roles.prioridad AS rol.prioridad',
                'roles.permisos AS rol.permisos',
                'roles.estado AS rol.estado'
            ])
                ->where('usuarios.token', $request->header('SoDe-Auth-Token'))
                ->where('usuarios.usuario', $request->header('SoDe-Auth-User'))
                ->leftjoin('roles', 'usuarios._rol', '=', 'roles.id')
                ->first();

            if (!$sesionJpa) {
                throw new Exception('La sesión ha expirado o has iniciado sesión en otro dispositivo', 403);
            }

            $sesion = gJSON::restore($sesionJpa->toArray());
            if (!$sesion['estado']) {
                throw new Exception('No tienes permisos para acceder. Su usuario se encuentra inactivo', 403);
            }
            if (!$sesion['rol']['estado']) {
                throw new Exception('No tienes permisos para acceder. Su rol se encuentra inactivo', 403);
            }

            $sesion['rol']['permisos'] = gJSON::parse($sesion['rol']['permisos']);
        } catch (\Throwable $th) {
            $estado = gStatus::get($th->getCode());
            $mensaje = $th->getMessage();
            $sesion = null;
        }

        return [$estado, $mensaje, $sesion];
    }

    public static function verificar(array $permisos, String $vista, String $permiso): bool
    {
        $permisos = gJSON::flatten($permisos);
        if (
            isset($permisos["esRoot"]) ||
            isset($permisos["esAdmin"]) ||
            isset($permisos["$vista.todo"]) ||
            isset($permisos["$vista.$permiso"])
        ) {
            return true;
        }
        return false;
    }

    public static function limpiarPermisos(array $permisos, array $antes, array $nuevo): array
    {
        $ok = true;
        $mensage = 'Operación correcta';

        $despues = array();
        try {
            $antes = gJSON::flatten($antes);
            $nuevo = gJSON::flatten($nuevo);

            foreach ($nuevo as $clave => $valor) {
                [$vista, $permiso] = explode('.', $clave);
                if (gValidate::verificar($permisos, $vista, $permiso) || $antes[$clave]) {
                    $despues[$clave] = true;
                }
            }
        } catch (\Throwable $th) {
            $ok = false;
            $mensage = $th->getMessage();
        }

        return [$ok, $mensage, gJSON::restore($despues)];
    }
}
