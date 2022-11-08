<?php

namespace App\gLibraries;

use App\Models\Usuario;
use App\gLibraries\gjson;
use Illuminate\Http\Request;
use Exception;

class gValidate
{
    public static function obtener(Request $request): array
    {
        $estado = 200;
        $mensaje = 'Operación correcta';
        $session = new Usuario();
        try {
            if (
                $request->header('SoDe-Auth-User') == null ||
                $request->header('SoDe-Auth-Token') == null
            ) {
                throw new Exception('Error: Debes enviar los encabezados de autenticación', 401);
            }

            $sessionJpa = Usuario::select([
                'usuarios.id AS id',
                'usuarios.estado AS estado',
                'roles.id AS rol.id',
                'roles.prioridad AS rol.prioridad',
                'roles.permisos AS rol.permisos',
                'roles.estado AS role.estado'
            ])
                ->where('usuarios.token', $request->header('SoDe-Auth-Token'))
                ->where('usuarios.usuario', $request->header('SoDe-Auth-User'))
                ->leftjoin('roles', 'usuarios._rol', '=', 'roles.id')
                ->first();

            if (!$sessionJpa) {
                throw new Exception('La sesión ha expirado o has iniciado sesión en otro dispositivo', 403);
            }

            $session = gJSON::restore($sessionJpa->toArray());
            if (!$session['estado']) {
                throw new Exception('No tienes permisos para acceder. Su usuario se encuentra inactivo', 403);
            }
            if (!$session['rol']['estado']) {
                throw new Exception('No tienes permisos para acceder. Su rol se encuentra inactivo', 403);
            }

            $session['rol']['permisos'] = gJSON::parse($session['rol']['permisos']);
        } catch (\Throwable $th) {
            $status = $th->getCode() < 100 ? 400 : $th->getCode();
            $message = $th->getMessage();
            $session = null;
        }

        return [$status, $message, $session];
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
