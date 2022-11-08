<?php

namespace App\Http\Controllers;

use App\gLibraries\gStatus;
use App\gLibraries\gValidate;
use App\Models\Usuario;
use App\Models\Response;
use Illuminate\Http\Request;
use Exception;

class PerfilController extends Controller
{

    public function perfil($id_relativo, $medida)
    {
        $response = new Response();
        $perfil = null;
        $tipo = null;
        try {
            if ($medida != 'full') {
                $medida = 'mini';
            }
            if (
                !isset($id_relativo)
            ) {
                throw new Exception("Error: No deje campos vacíos", 401);
            }

            $usuarioJpa = Usuario::select([
                "usuarios.perfil_$medida AS perfil",
                'usuarios.perfil_tipo AS tipo'

            ])
                ->where('id_relativo', $id_relativo)
                ->first();

            if (!$usuarioJpa) {
                throw new Exception('No se encontraron datos', 404);
            }
            if (!$usuarioJpa->perfil) {
                throw new Exception('No existe imagen', 400);
            }
            $perfil = $usuarioJpa->perfil;
            $tipo = $usuarioJpa->tipo;
            $response->setStatus(200);
        } catch (\Throwable $th) {
            $ruta = '../storage/images/user_not_found.svg';
            $fp = fopen($ruta, 'r');
            $datos_image = fread($fp, filesize($ruta));
            $datos_image = addslashes($datos_image);
            fclose($fp);
            $perfil = stripslashes($datos_image);
            $tipo = 'image/svg+xml';
            $response->setStatus(gStatus::get($th->getCode()));
        } finally {
            return response(
                $perfil,
                $response->getStatus()
            )->header('Content-Type', $tipo);
        }
    }

    public function cuenta(Request $request)
    {
        $response = new Response();
        try {
            [$estado, $mensaje, $sesion] = gValidate::obtener($request);
            if ($estado != 200) {
                throw new Exception($mensaje, $estado);
            }

            if (
                !isset($request->usuario) &&
                !isset($request->clave)
            ) {
                throw new Exception("Los campos usuario y contraseña son obligatorios", 401);
            }

            if (strlen($request->usuario) < 4) {
                throw new Exception('El nombre de usuario debe contener entre 4 y 16 caracteres', 400);
            }

            if (!ctype_alnum($request->usuario)) {
                throw new Exception('El nombre de usuario debe contener solo letras y números', 400);
            }

            $usuarioJpa = Usuario::find($sesion['id']);

            if (!$usuarioJpa) {
                throw new Exception("El usuario que intentas modificar no existe", 404);
            }

            if (!$usuarioJpa->estado) {
                throw new Exception('El usuario que intentas modificar no se encuentra activo', 400);
            }

            if (!password_verify($request->clave, $usuarioJpa->clave)) {
                throw new Exception('Contraseña incorrecta. Ingrese una contraseña válida', 400);
            }

            if (
                isset($request->perfil_tipo) &&
                isset($request->perfil_mini) &&
                isset($request->perfil_full)
            ) {
                if (
                    $request->perfil_tipo != 'none' &&
                    $request->perfil_mini != 'none' &&
                    $request->perfil_full != 'none'
                ) {
                    $usuarioJpa->perfil_tipo = $request->perfil_tipo;
                    $usuarioJpa->perfil_mini = base64_decode($request->perfil_mini);
                    $usuarioJpa->perfil_full = base64_decode($request->perfil_full);
                } else {
                    $usuarioJpa->perfil_tipo = null;
                    $usuarioJpa->perfil_mini = null;
                    $usuarioJpa->perfil_full = null;
                }
            }

            if ($request->usuario != $usuarioJpa->usuario) {
                $usuarioJpa->usuario = $request->usuario;
                $usuarioJpa->token = null;
            }

            $usuarioJpa->save();

            $response->setStatus(200);
            $response->setMessage('El usuario ha sido actualizado correctamente');
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

    public function clave(Request $request)
    {
        $response = new Response();
        try {
            [$estado, $mensaje, $sesion] = gValidate::obtener($request);
            if ($estado != 200) {
                throw new Exception($mensaje, $estado);
            }
            if (
                !isset($request->clave_nueva) &&
                !isset($request->clave_confirmacion) &&
                !isset($request->clave)
            ) {
                throw new Exception("Envía todos los datos necesarios");
            }

            if ($request->clave_nueva != $request->clave_confirmacion) {
                throw new Exception('Las contraseñas deben ser iguales');
            }

            if (strlen($request->clave_nueva) < 4) {
                throw new Exception('La contraseña debe contener 4 caracteres como mínimo. Recuerda: Si quieres tener una cuenta segura, debes crear una contraseña segura');
            }

            $usuarioJpa = Usuario::find($sesion['id']);

            if (!$usuarioJpa) {
                throw new Exception("El usuario que intentas modificar no existe", 404);
            }

            if (!$usuarioJpa->estado) {
                throw new Exception('El usuario que intentas modificar no se encuentra activo', 400);
            }

            if (!password_verify($request->clave, $usuarioJpa->clave)) {
                throw new Exception('Contraseña incorrecta. Ingrese una contraseña válida', 400);
            }

            if (password_verify($request->clave_nueva, $usuarioJpa->clave)) {
                throw new Exception('La contraseña nueva debe ser diferente a la anterior');
            }

            $usuarioJpa->clave = password_hash($request->clave_nueva, PASSWORD_DEFAULT);
            $usuarioJpa->token = null;

            $usuarioJpa->save();

            $response->setStatus(200);
            $response->setMessage('La contraseña ha sido actualizada correctamente');
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

    public function personal(Request $request)
    {
        $response = new Response();
        try {
            [$estado, $mensaje, $sesion] = gValidate::obtener($request);
            if ($estado != 200) {
                throw new Exception($mensaje, $estado);
            }

            if (
                !isset($request->apellidos) &&
                !isset($request->nombres)
            ) {
                throw new Exception('Los apellidos y nombres son obligatorios', 401);
            }

            $usuarioJpa = Usuario::find($sesion['id']);

            if (!$usuarioJpa) {
                throw new Exception("El usuario que intentas modificar no existe", 404);
            }

            if (!$usuarioJpa->estado) {
                throw new Exception('El usuario que intentas modificar no se encuentra activo', 400);
            }

            if (!password_verify($request->clave, $usuarioJpa->clave)) {
                throw new Exception('Contraseña incorrecta. Ingrese una contraseña válida', 400);
            }

            $usuarioJpa->apellidos = $request->apellidos;
            $usuarioJpa->nombres = $request->nombres;
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
            $response->setMessage('Usuario actualizado correctamente');
            $response->setData($request->toArray());
        } catch (\Throwable $th) {
            $response->setStatus(400);
            $response->setMessage($th->getMessage());
        } finally {
            return response(
                $response->toArray(),
                $response->getStatus()
            );
        }
    }
}
