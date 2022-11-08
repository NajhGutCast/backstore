<?php

namespace App\Http\Controllers;

use App\gLibraries\gvalidate;
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
                throw new Exception("Error: No deje campos vacíos");
            }

            $usuarioJpa = Usuario::select([
                "usuarios.perfil_$medida AS perfil",
                'usuarios.perfil_tipo AS tipo'

            ])
                ->where('id_relativo', $id_relativo)
                ->first();

            if (!$usuarioJpa) {
                throw new Exception('No se encontraron datos');
            }
            if (!$usuarioJpa->perfil) {
                throw new Exception('No existe imagen');
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
            $response->setStatus(400);
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
            [$status, $message, $role, $userid] = gValidate::obtener($request);
            if ($status != 200) {
                throw new Exception($message);
            }

            if (
                !isset($request->username) &&
                !isset($request->password)
            ) {
                throw new Exception("Error: No deje campos vacíos");
            }

            if (strlen($request->username) < 4) {
                throw new Exception('El nombre de usuario debe contener entre 4 y 16 caracteres');
            }

            if (!ctype_alnum($request->username)) {
                throw new Exception('El nombre de usuario debe contener solo letras y números');
            }

            $userJpa = User::find($userid);
            if (!$userJpa) {
                throw new Exception("Este usuario no existe");
            }

            if (!password_verify($request->password, $userJpa->password)) {
                throw new Exception('Error: Contraseña incorrecta');
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
                    $userJpa->image_type = $request->image_type;
                    $userJpa->image_mini = base64_decode($request->image_mini);
                    $userJpa->image_full = base64_decode($request->image_full);
                } else {
                    $userJpa->image_type = null;
                    $userJpa->image_mini = null;
                    $userJpa->image_full = null;
                }
            }

            if ($request->username != $userJpa->username) {
                $userJpa->username = $request->username;
                $userJpa->auth_token = null;
            }

            $userJpa->save();

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

    public function password(Request $request)
    {
        $response = new Response();
        try {
            [$status, $message, $role, $userid] = gValidate::obtener($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (
                !isset($request->password_new) &&
                !isset($request->password_confirm) &&
                !isset($request->password)
            ) {
                throw new Exception("Error: No deje campos vacíos");
            }

            if ($request->password_new != $request->password_confirm) {
                throw new Exception('Las contraseñas deben ser iguales');
            }

            if (strlen($request->password_new) < 4) {
                throw new Exception('La contraseña debe contener 4 caracteres como mínimo. Recuerda: Si quieres tener una cuenta segura, debes crear una contraseña segura');
            }

            $userJpa = User::find($userid);
            if (!$userJpa) {
                throw new Exception("Este usuario no existe");
            }

            if (!password_verify($request->password, $userJpa->password)) {
                throw new Exception('Error: Contraseña de confirmación incorrecta');
            }

            if (password_verify($request->password_new, $userJpa->password)) {
                throw new Exception('La contraseña nueva debe ser diferente a la anterior');
            }

            $userJpa->password = password_hash($request->password_new, PASSWORD_DEFAULT);
            $userJpa->auth_token = null;

            $userJpa->save();

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

    public function personal(Request $request)
    {
        $response = new Response();
        try {
            [$status, $message, $role, $userid] = gValidate::obtener($request);
            if ($status != 200) {
                throw new Exception($message);
            }

            if (
                !isset($request->lastname) &&
                !isset($request->name)
            ) {
                throw new Exception("Error: No deje campos vacíos");
            }

            $userJpa = User::find($userid);
            if (!$userJpa) {
                throw new Exception("Este usuario no existe");
            }

            if (!password_verify($request->password, $userJpa->password)) {
                throw new Exception('Error: Contraseña de confirmación incorrecta');
            }

            $userJpa->lastname = $request->lastname;
            $userJpa->name = $request->name;
            if (
                isset($request->phone_prefix) &&
                isset($request->phone_number)
            ) {
                $userJpa->phone_prefix = $request->phone_prefix;
                $userJpa->phone_number = $request->phone_number;
            }
            if (isset($request->email)) {
                $userJpa->email = $request->email;
            }

            $userJpa->save();

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
