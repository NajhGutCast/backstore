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
            // Obtienemos validación
            [$estado, $mensaje, $sesion] = gValidate::obtener($request);
            if ($estado != 200) {
                throw new Exception($mensaje, $estado);
            }

            // Verificamos el permiso
            if (!gvalidate::verificar($sesion['rol']['permisos'], 'usuarios', 'listar')) {
                throw new Exception('No tienes permisos para listar los usuarios del sistema');
            }

            // Establecemos las columnas a obtener
            $filasJpa = Usuario::select([
                'usuarios.id',
                'usuarios.id_relativo',
                'usuarios.usuario',
                'personas.id AS persona.id',
                'personas.numerodocumento AS persona.dni',
                'personas.apellidos AS persona.apellidos',
                'personas.nombres AS persona.nombres',
                'personas.telefono AS persona.telefono',
                'personas.correo AS persona.correo',
                'personas.direccion AS persona.direccion',
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
                ->where('roles.prioridad', '>=', $sesion['rol']['prioridad'])
                ->get();

            $filas = array();
            foreach ($filasJpa as $filaJpa) {
                $fila = gJSON::restore($filaJpa->toArray());
                $fila['rol']['permisos'] = gJSON::parse($fila['rol']['permisos']);
                $filas[] = $fila;
            }
            $response->setStatus(200);
            $response->setMessage('Operación correcta');
            $response->setData($filas);
        } catch (\Throwable $th) {
            // $response->setStatus(gStatus::get($th->getCode()));
            $response->setStatus(400);
            $response->setMessage($th->getMessage());
        } finally {
            return response(
                $response->toArray(),
                $response->getStatus()
            );
        }
    }

    public function obtener($usuario)
    {
        $response = new Response();
        try {
            $filaJpa = Usuario::select([
                'usuarios.id_relativo',
                'usuarios.usuario',
                'personas.nombres AS persona.nombres',
                'usuarios.estado',
                'roles.rol AS rol.rol',
            ])
                ->leftjoin('personas', 'usuarios._persona', '=', 'personas.id')
                ->leftjoin('roles', 'usuarios._rol', '=', 'roles.id')
                ->where('usuarios.usuario', $usuario)
                ->first();

            if (!$filaJpa) {
                throw new Exception('Este usuario no existe');
            }

            if (!$filaJpa->estado) {
                throw new Exception('Este usuario se encuentra inactivo');
            }

            $fila = gJSON::restore($filaJpa->toArray());

            $response->setStatus(200);
            $response->setMessage('Operación correcta');
            $response->setData($fila);
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
                throw new Exception($mensaje, $estado);
            }
            if (!gvalidate::verificar($sesion['rol']['permisos'], 'usuarios', 'listar')) {
                throw new Exception('No tienes permisos para listar los usuarios del sistema');
            }

            $sql = Usuario::select([
                'usuarios.id',
                'usuarios.id_relativo',
                'usuarios.usuario',
                'personas.id AS persona.id',
                'personas.numerodocumento AS persona.dni',
                'personas.apellidos AS persona.apellidos',
                'personas.nombres AS persona.nombres',
                'personas.telefono AS persona.telefono',
                'personas.correo AS persona.correo',
                'personas.direccion AS persona.direccion',
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
                ->where('roles.prioridad', '>=', $sesion['rol']['prioridad'])
                ->orderBy('usuarios.' . $request->order['column'], $request->order['dir']);

            if (!$request->all) {
                $sql->whereNotNull('usuarios.estado');
            }

            $sql->where(function ($q) use ($request) {
                $column = $request->search['column'];
                $type = $request->search['regex'] ? 'like' : '=';
                $value = $request->search['value'];
                $value = $type == 'like' ? DB::raw("'%{$value}%'") : $value;
                if ($column == 'usuario' || $column == '*') {
                    $q->where('usuarios.usuario', $type, $value);
                }
                if ($column == 'apellidos' || $column == '*') {
                    $q->orWhere('personas.apellidos', $type, $value);
                }
                if ($column == 'nombres' || $column == '*') {
                    $q->orWhere('personas.nombres', $type, $value);
                }
                if ($column == 'correo' || $column == '*') {
                    $q->orWhere('personas.correo', $type, $value);
                }
                if ($column == 'telefono' || $column == '*') {
                    $q->orWhere('personas.telefono', $type, $value);
                }
                if ($column == '_rol' || $column == '*') {
                    $q->orWhere('roles.rol', $type, $value);
                }
            });

            $iTotalDisplayRecords = $sql->count();
            $usuariosJpa = $sql
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
