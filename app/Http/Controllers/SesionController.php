<?php

namespace App\Http\Controllers;

use App\gLibraries\guid;
use App\gLibraries\gjson;
use App\gLibraries\gValidate;
use App\gLibraries\gstatus;

use App\Models\Usuario;
use App\Models\Response;
use Illuminate\Http\Request;
use Exception;


class SesionController extends Controller
{
    public function ingresar(Request $request)
    {
        $response = new Response();
        try {

            if (
                !isset($request->clave) ||
                !isset($request->usuario)
            ) {
                throw new Exception("Error: No deje campos vacíos");
            }

            $sesionJpa = Usuario::select([
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

            if (!$sesionJpa) {
                throw new Exception('El usuario solicitado no existe', 404);
            }
            if (!$sesionJpa->estado) {
                throw new Exception('Este usuario se encuentra inactivo', 403);
            }
            if (!password_verify($request->clave, $sesionJpa->clave)) {
                throw new Exception('La contraseña enviada es incorrecta', 400);
            }

            $sesionJpa->token = guid::long();
            $sesionJpa->save();

            $sesion = gJSON::restore($sesionJpa->toArray());
            unset($sesion['id']);
            unset($sesion['clave']);
            $sesion['rol']['permisos'] = gJSON::parse($sesion['rol']['permisos']);

            $response->setStatus(200);
            $response->setMessage('Se ha iniciado sesión satisfactoriamente');
            $response->setData($sesion);
        } catch (\Throwable $th) {
            $response->setStatus(gStatus::get($th->getCode()));
            $response->setMessage($th->getMessage());
        } finally {
            return response(
                $response->toArray(),
                $response->getStatus()
            );
        }
    }

    public function cerrar(Request $request)
    {
        $response = new Response();
        try {
            if (
                !isset($request->relative_id)
            ) {
                throw new Exception("Error: no deje campos vaciós");
            }
            [$estado, $mensaje, $sesion] = gValidate::obtener($request);
            if ($estado != 200) {
                throw new Exception($mensaje, $estado);
            }

            $sesionJpa = Usuario::select([
                'usuarios.token'
            ])
                ->where('usuarios.id', $sesion['id'])
                ->first();

            if (!$sesionJpa) {
                throw new Exception('No se pudo cerrar la sessión. No existe sesión');
            }

            $sesionJpa->token = null;
            $sesionJpa->save();

            $response->setStatus(200);
            $response->setMessage('La sesión se cerró correctamente');
            $response->setData([]);
        } catch (\Throwable $th) {
            $response->setStatus(gStatus::get($th->getCode()));
            $response->setMessage($th->getMessage());
        } finally {
            return response(
                $response->toArray(),
                $response->getStatus()
            );
        }
    }

    public function verificar(Request $request)
    {
        $response = new Response();
        try {

            [$estado, $mensaje] = gValidate::obtener($request);

            if ($estado != 200) {
                throw new Exception($mensaje, $estado);
            }

            $sesionJpa = Usuario::select([
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

            if (!$sesionJpa) {
                throw new Exception('No tienes una sesión activa', 403);
            }

            if (!$sesionJpa->status) {
                throw new Exception('Este usuario se encuentra inactivo', 403);
            }

            $sesion = gJSON::restore($sesionJpa->toArray());
            unset($sesion['id']);
            unset($sesion['clave']);
            $sesion['rol']['permisos'] = gJSON::parse($sesion['rol']['permisos']);

            $response->setStatus(200);
            $response->setMessage('Operación correcta');
            $response->setData($sesion);
        } catch (\Throwable $th) {
            $response->setStatus(gStatus::get($th->getCode()));
            $response->setMessage($th->getMessage());
        } finally {
            return response(
                $response->toArray(),
                $response->getStatus()
            );
        }
    }
}
