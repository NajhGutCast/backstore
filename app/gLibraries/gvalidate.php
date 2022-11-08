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

    public static function check(array $permissions, String $view, String $permission): bool
    {
        $permissions = gJSON::flatten($permissions);
        if (
            isset($permissions["isRoot"]) ||
            isset($permissions["isAdmin"]) ||
            isset($permissions["$view.all"]) ||
            isset($permissions["$view.$permission"])
        ) {
            return true;
        }
        return false;
    }

    public static function cleanPermissions(array $permissions, array $before, array $toset): array
    {
        $ok = true;
        $message = 'Operación correcta';

        $after = array();
        try {
            $before = gJSON::flatten($before);
            $toset = gJSON::flatten($toset);

            foreach ($toset as $key => $value) {
                [$view, $permission] = explode('.', $key);
                if (gValidate::check($permissions, $view, $permission) || $before[$key]) {
                    $after[$key] = true;
                }
            }
        } catch (\Throwable $th) {
            $ok = false;
            $message = $th->getMessage();
        }

        return [$ok, $message, gJSON::restore($after)];
    }
}
