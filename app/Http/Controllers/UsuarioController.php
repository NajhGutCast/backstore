<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Models\Response;

use App\gLibraries\guid;
use App\gLibraries\gjson;
use App\gLibraries\gstatus;
use App\gLibraries\gvalidate;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class UsuarioController extends Controller
{

    public function listar(Request $request)
    {
        $response = new Response();
        try {
            [$estado, $mensaje, $session['rol']] = gValidate::obtener($request);
            if ($estado != 200) {
                throw new Exception($mensaje, $estado);
            }
            if (!gvalidate::verificar($session['rol']['permisos'], 'usuarios', 'listar')) {
                throw new Exception('No tienes permisos para listar los usuarios del sistema');
            }
            $usuariosJpa = Usuario::select([
                'usuarios.id',
                'usuarios.id_relativo',
                'usuarios.usuario',
                'personas.id AS persona.id',
                'personas.numerodocumento AS persona.dni',
                'personas.apellidos AS persona.apellidos',
                'usuarios.telefono AS persona.telefono',
                'usuarios.correo AS persona.correo',
                'usuarios.direccion AS persona.direccion',
                'usuarios.estado AS persona.estado',
                'roles.id AS rol.id',
                'roles.rol AS rol.rol',
                'roles.prioridad AS rol.prioridad',
                'roles.descripcion AS rol.descripcion',
                'roles.permisos AS rol.permisos',
                'roles.estado AS rol.estado',
                'usuarios.estado',
            ])
                ->leftjoin('roles', 'usuarios._rol', '=', 'roles.id')
                ->leftjoin('personas', 'usuarios._persona', '=', 'personas.id')
                ->where('roles.prioridad', '>=', $session['rol']['prioridad'])
                ->get();

            $usuarios = array();
            foreach ($usuariosJpa as $usuarioJpa) {
                $usuario = gJSON::restore($usuarioJpa->toArray());
                $usuario['rol']['permisos'] = gJSON::parse($usuario['rol']['permisos']);
                $usuarios[] = $usuario;
            }
            $response->setStatus(200);
            $response->setMessage('Operación correcta');
            $response->setData($usuarios);
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

    public function obtener($_usuario)
    {
        $response = new Response();
        try {
            $usuarioJpa = Usuario::select([
                'usuarios.id_relativo',
                'usuarios.usuario',
                'personas.nombres AS persona.nombres',
                'usuarios.estado',
                'roles.rol AS rol.rol',
            ])
                ->leftjoin('personas', 'usuarios._persona', '=', 'personas.id')
                ->leftjoin('roles', 'usuarios._rol', '=', 'roles.id')
                ->where('usuarios.usuario', $_usuario)
                ->first();

            if (!$usuarioJpa) {
                throw new Exception('Este usuario no existe');
            }

            if (!$usuarioJpa->estado) {
                throw new Exception('Este usuario se encuentra inactivo');
            }

            $usuario = gJSON::restore($usuarioJpa->toArray());

            $response->setStatus(200);
            $response->setMessage('Operación correcta');
            $response->setData($usuario);
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

    public function paginado(Request $request)
    {
        $response = new Response();
        try {

            [$estado, $mensaje, $sesion] = gValidate::obtener($request);
            if ($estado != 200) {
                throw new Exception($mensaje);
            }
            if (!gvalidate::verificar($sesion['rol']['permisos'], 'usuarios', 'read')) {
                throw new Exception('No tienes permisos para listar los usuarios del sistema');
            }

            $query = Usuario::select([
                'usuarios.id',
                'usuarios.is_relativo',
                'usuarios.usuario',
                'usuarios.password',
                'usuarios.dni',
                'usuarios.lastname',
                'usuarios.name',
                'usuarios.email',
                'usuarios.phone_prefix',
                'usuarios.phone_number',
                'roles.id AS role.id',
                'roles.role AS role.role',
                'roles.description AS role.description',
                'roles.permissions AS role.permissions',
                'usuarios.estado',
            ])
                ->leftjoin('roles', 'usuarios._role', '=', 'roles.id')
                ->where('roles.priority', '>=', $sesion['rol']['prioridad'])
                ->orderBy('usuarios.' . $request->order['column'], $request->order['dir']);

            if (!$request->all) {
                $query->whereNotNull('usuarios.estado');
            }

            $query->where(function ($q) use ($request) {
                $column = $request->search['column'];
                $type = $request->search['regex'] ? 'like' : '=';
                $value = $request->search['value'];
                $value = $type == 'like' ? DB::raw("'%{$value}%'") : $value;
                if ($column == 'usuarioname' || $column == '*') {
                    $q->where('usuarios.usuarioname', $type, $value);
                }
                if ($column == 'lastname' || $column == '*') {
                    $q->orWhere('usuarios.lastname', $type, $value);
                }
                if ($column == 'name' || $column == '*') {
                    $q->orWhere('usuarios.name', $type, $value);
                }
                if ($column == 'email' || $column == '*') {
                    $q->orWhere('usuarios.email', $type, $value);
                }
                if ($column == 'phone_number' || $column == '*') {
                    $q->orWhere('usuarios.phone_number', $type, $value);
                }
                if ($column == '_role' || $column == '*') {
                    $q->orWhere('roles.role', $type, $value);
                }
            });

            $iTotalDisplayRecords = $query->count();
            $usuariosJpa = $query
                ->skip($request->start)
                ->take($request->length)
                ->get();

            $usuarios = array();
            foreach ($usuariosJpa as $usuarioJpa) {
                $usuario = gJSON::restore($usuarioJpa->toArray());
                $usuario['role']['permissions'] = gJSON::parse($usuario['role']['permissions']);
                unset($usuario['password']);
                $usuarios[] = $usuario;
            }
            $response->setStatus(200);
            $response->setMessage('Operación correcta');
            $response->setDraw($request->draw);
            $response->setITotalDisplayRecords($iTotalDisplayRecords);
            $response->setITotalRecords(Usuario::count());
            $response->setData($usuarios);
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

    public function guardar(Request $request)
    {
        $response = new Response();
        try {

            if (
                !isset($request->usuarioname) ||
                !isset($request->password) ||
                !isset($request->_role) ||
                !isset($request->dni) ||
                !isset($request->name) ||
                !isset($request->lastname)
            ) {
                throw new Exception("Error: No deje campos vacíos");
            }

            [$status, $message, $role] = gValidate::obtener($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gvalidate::verificar($role->permissions, 'usuarios', 'create')) {
                throw new Exception('No tienes permisos para agregar usuarios en el sistema');
            }

            $usuarioValidation = Usuario::select(['usuarios.usuarioname'])->where('usuarioname', $request->usuarioname)->first();

            if ($usuarioValidation) {
                throw new Exception("Este usuario ya existe");
            }

            $usuarioJpa = new Usuario();

            if (
                isset($request->image_type) &&
                isset($request->image_mini) &&
                isset($request->image_full)
            ) {
                if (
                    $request->image_type &&
                    $request->image_mini &&
                    $request->image_full
                ) {
                    $usuarioJpa->image_type = $request->image_type;
                    $usuarioJpa->image_mini = base64_decode($request->image_mini);
                    $usuarioJpa->image_full = base64_decode($request->image_full);
                } else {
                    $usuarioJpa->image_type = null;
                    $usuarioJpa->image_mini = null;
                    $usuarioJpa->image_full = null;
                }
            }

            $usuarioJpa->relative_id = guid::short();
            $usuarioJpa->usuarioname = $request->usuarioname;
            $usuarioJpa->password = password_hash($request->password, PASSWORD_DEFAULT);
            $usuarioJpa->_role = $request->_role;
            $usuarioJpa->dni = $request->dni;
            $usuarioJpa->lastname = $request->lastname;
            $usuarioJpa->name = $request->name;
            if (
                isset($request->phone_prefix) &&
                isset($request->phone_number)
            ) {
                $usuarioJpa->phone_prefix = $request->phone_prefix;
                $usuarioJpa->phone_number = $request->phone_number;
            }
            if (isset($request->email)) {
                $usuarioJpa->email = $request->email;
            }

            $usuarioJpa->save();

            $response->setStatus(200);
            $response->setMessage('Usuario agregado correctamente');
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

    public function actualizar(Request $request)
    {
        $response = new Response();
        try {
            if (
                !isset($request->usuarioname) &&
                !isset($request->_role) &&
                !isset($request->dni) &&
                !isset($request->lastname) &&
                !isset($request->name)
            ) {
                throw new Exception("Error: No deje campos vacíos");
            }

            [$status, $message, $role] = gValidate::obtener($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gvalidate::verificar($role->permissions, 'usuarios', 'update')) {
                throw new Exception('No tienes permisos para modificar usuarios en el sistema');
            }

            $usuarioJpa = Usuario::find($request->id);
            if (!$usuarioJpa) {
                throw new Exception("Este usuario no existe");
            }

            if (
                isset($request->image_type) &&
                isset($request->image_mini) &&
                isset($request->image_full)
            ) {
                if (
                    $request->image_type != 'none' &&
                    $request->image_mini != 'none' &&
                    $request->image_full != 'none'
                ) {
                    $usuarioJpa->image_type = $request->image_type;
                    $usuarioJpa->image_mini = base64_decode($request->image_mini);
                    $usuarioJpa->image_full = base64_decode($request->image_full);
                } else {
                    $usuarioJpa->image_type = null;
                    $usuarioJpa->image_mini = null;
                    $usuarioJpa->image_full = null;
                }
            }

            $usuarioJpa->usuarioname = $request->usuarioname;

            if (isset($request->password) && $request->password) {
                $usuarioJpa->password = password_hash($request->password, PASSWORD_DEFAULT);
                $usuarioJpa->auth_token = null;
            }

            $usuarioJpa->_role = $request->_role;
            $usuarioJpa->dni = $request->dni;
            $usuarioJpa->lastname = $request->lastname;
            $usuarioJpa->name = $request->name;
            if (
                isset($request->phone_prefix) &&
                isset($request->phone_number)
            ) {
                $usuarioJpa->phone_prefix = $request->phone_prefix;
                $usuarioJpa->phone_number = $request->phone_number;
            }
            if (isset($request->email)) {
                $usuarioJpa->email = $request->email;
            }
            if (gValidate::verificar($role->permissions, 'views', 'change_status'))
                if (isset($request->status))
                    $usuarioJpa->status = $request->status;

            $usuarioJpa->save();

            $response->setStatus(200);
            $response->setMessage('Usuario actualizado correctamente');
            $response->setData($request->toArray());
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

    public function eliminar(Request $request)
    {
        $response = new Response();
        try {

            [$status, $message, $role] = gValidate::obtener($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gValidate::verificar($role->permissions, 'usuarios', 'delete_restore')) {
                throw new Exception('No tienes permisos para eliminar usuarios del sistema');
            }

            if (
                !isset($request->id)
            ) {
                throw new Exception("Error: Es necesario el ID para esta operación");
            }

            $usuarioJpa = Usuario::find($request->id);

            if (!$usuarioJpa) {
                throw new Exception("Este usuario no existe");
            }

            $usuarioJpa->status = null;
            $usuarioJpa->save();

            $response->setStatus(200);
            $response->setMessage('El usuario se ha eliminado correctamente');
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

    public function restablecer(Request $request)
    {
        $response = new Response();
        try {

            [$status, $message, $role] = gValidate::obtener($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gValidate::verificar($role->permissions, 'usuarios', 'delete_restore')) {
                throw new Exception('No tienes permisos para restaurar usuarios del sistema');
            }

            if (
                !isset($request->id)
            ) {
                throw new Exception("Error: Es necesario el ID para esta operación");
            }

            $usuarioJpa = Usuario::find($request->id);
            if (!$usuarioJpa) {
                throw new Exception("Este usuario no existe");
            }
            $usuarioJpa->status = "1";
            $usuarioJpa->save();

            $response->setStatus(200);
            $response->setMessage('El usuario ha sido restaurado correctamente');
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
