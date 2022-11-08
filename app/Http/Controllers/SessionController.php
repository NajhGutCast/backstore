<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Models\Response;
use App\gLibraries\guid;
use App\gLibraries\gjson;
use App\gLibraries\gValidate;
use Illuminate\Http\Request;
use Exception;


class SessionController extends Controller
{
    public function login(Request $request)
    {
        $response = new Response();
        try {

            if (
                !isset($request->clave) ||
                !isset($request->usuario)
            ) {
                throw new Exception("Error: No deje campos vacíos");
            }

            $sessionJpa = Usuario::select([
                'usuarios.id',
                'usuarios.id_relativo',
                'usuarios.usuario',
                'usuarios.clave',
                'usuarios.token',
                'usuarios.estado',

                'personas.id AS persona.id',
                'personas.numerodocumento AS persona.dni',
                'personas.apellidos AS persona.apellido',
                'personas.nombres AS persona.nombre',
                'personas.telefono AS persona.telefono',
                'personas.correo AS persona.correo',
                'personas.direccion AS persona.direccion',
                'personas.estado AS persona.estado',

                'roles.id AS rol.id',
                'roles.rol AS rol.rol',
                'roles.prioridad AS rol.prioridad',
                'roles.descripcion AS rol.descripcion',
                'roles.permisos AS rol.permisos',
                'roles.estado AS rol.estado',
            ])
                ->leftjoin('personas', 'usuarios._persona', '=', 'personas.id')
                ->leftjoin('roles', 'usuarios._rol', '=', 'roles.id')
                ->where('usuarios.usuario', $request->usuario)
                ->first();

            if (!$sessionJpa) {
                throw new Exception('El usuario solicitado no existe', 404);
            }
            if (!$sessionJpa->status) {
                throw new Exception('Este usuario se encuentra inactivo', 403);
            }
            if (!password_verify($request->clave, $sessionJpa->clave)) {
                throw new Exception('La contraseña enviada es incorrecta', 400);
            }

            $sessionJpa->token = guid::long();
            $sessionJpa->save();

            $session = gJSON::restore($sessionJpa->toArray());
            unset($session['id']);
            unset($session['password']);
            $session['role']['permissions'] = gJSON::parse($session['role']['permissions']);

            $response->setStatus(200);
            $response->setMessage('Operación correcta');
            $response->setData($session);
        } catch (\Throwable $th) {
            $response->setStatus($th->getCode() < 100 ? 400 : $th->getCode());
            $response->setMessage($th->getMessage());
        } finally {
            return response(
                $response->toArray(),
                $response->getStatus()
            );
        }
    }

    public function logout(Request $request)
    {
        $response = new Response();
        try {
            if (
                !isset($request->relative_id)
            ) {
                throw new Exception("Error: no deje campos vaciós");
            }
            [$status, $message, $session] = gValidate::obtener($request);
            if ($status != 200) {
                throw new Exception($message, $status);
            }

            $sessionJpa = Usuario::select([
                'usuarios.token'
            ])
                ->where('usuarios.id', $session['id'])
                ->first();

            if (!$sessionJpa) {
                throw new Exception('No se pudo cerrar la sessión. No existe sesión');
            }

            $sessionJpa->token = null;
            $sessionJpa->save();

            $response->setStatus(200);
            $response->setMessage('La sesión se cerró correctamente');
            $response->setData([]);
        } catch (\Throwable $th) {
            $response->setStatus($th->getCode() < 100 ? 400 : $th->getCode());
            $response->setMessage($th->getMessage());
        } finally {
            return response(
                $response->toArray(),
                $response->getStatus()
            );
        }
    }

    public function verify(Request $request)
    {
        $response = new Response();
        try {

            [$status, $message] = gValidate::obtener($request);

            if ($status != 200) {
                throw new Exception($message, $status);
            }

            $sessionJpa = Usuario::select([
                'usuarios.id',
                'usuarios.id_relativo',
                'usuarios.usuario',
                'usuarios.clave',
                'usuarios.token',
                'usuarios.estado',

                'personas.id AS persona.id',
                'personas.numerodocumento AS persona.dni',
                'personas.apellidos AS persona.apellido',
                'personas.nombres AS persona.nombre',
                'personas.telefono AS persona.telefono',
                'personas.correo AS persona.correo',
                'personas.direccion AS persona.direccion',
                'personas.estado AS persona.estado',

                'roles.id AS rol.id',
                'roles.rol AS rol.rol',
                'roles.prioridad AS rol.prioridad',
                'roles.descripcion AS rol.descripcion',
                'roles.permisos AS rol.permisos',
                'roles.estado AS rol.estado',
            ])
                ->leftjoin('personas', 'usuarios._persona', '=', 'personas.id')
                ->leftjoin('roles', 'users._rol', '=', 'roles.id')
                ->where('usuarios.token', $request->header('sode-auth-token'))
                ->where('usuarios.usuario', $request->header('sode-auth-user'))
                ->first();

            if (!$sessionJpa) {
                throw new Exception('No tienes una sesión activa', 403);
            }

            if (!$sessionJpa->status) {
                throw new Exception('Este usuario se encuentra inactivo', 403);
            }

            $session = gJSON::restore($sessionJpa->toArray());
            unset($session['id']);
            unset($session['password']);
            $session['role']['permissions'] = gJSON::parse($session['role']['permissions']);

            $response->setStatus(200);
            $response->setMessage('Operación correcta');
            $response->setData($session);
        } catch (\Throwable $th) {
            $response->setStatus($th->getCode() < 100 ? 400 : $th->getCode());
            $response->setMessage($th->getMessage());
        } finally {
            return response(
                $response->toArray(),
                $response->getStatus()
            );
        }
    }
}
