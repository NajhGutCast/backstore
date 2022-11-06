<?php

namespace App\Http\Controllers;

use App\gLibraries\gjson;
use App\gLibraries\gvalidate;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Response;
use App\Models\Module;
use Exception;

class ModuleController extends Controller
{
    public function index(Request $request)
    {
        $response = new Response();
        try {

            [$status, $message, $role] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gValidate::check($role->permissions, 'modules', 'read')) {
                throw new Exception('No tienes permisos para listar los roles del sistema');
            }

            $modelsJpa = Module::select([
                'modules.id',
                'modules.module',
                'modules.description',
                'services.id as service.id',
                'services.service as service.service',
                'services.correlative as service.correlative',
                'services.repository as service.repository',
                'services.status as service.status',
                'modules.status'
            ])
            ->leftjoin('services', 'modules._service', '=', 'services.id')
                ->whereNotNull('modules.status')
                ->orderBy('modules.module', 'asc')
                ->get();

            $modules = array();
            foreach($modelsJpa as $modulJpa){
                $module = gJSON::restore($modulJpa->toArray());
                $modules[] = $module;
            }
            $response->setStatus(200);
            $response->setMessage('Operación correcta');
            $response->setData($modules);
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

    public function paginate(Request $request)
    {
        $response = new Response();
        try {
           
            [$status, $message, $role] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gValidate::check($role->permissions, 'modules', 'read')) {
                throw new Exception('No tienes permisos para listar los roles del sistema');
            }


            $query = Module::select([
                'modules.id',
                'modules.module',
                'modules.description',
                'services.id AS service.id',
                'services.service AS service.service',
                'services.correlative AS service.correlative',
                'services.repository AS service.repository',
                'services.status AS service.status',
                'modules.status'
            ])
            ->leftjoin('services', 'modules._service', '=', 'services.id');

            if($request->order['column'] == 'correlative'  || $request->order['column'] == 'repository'){
                $query  ->orderBy('services.'.$request->order['column'], $request->order['dir']);
            }else{
                $query  ->orderBy('modules.'.$request->order['column'], $request->order['dir']);
            }

            if (!$request->all) {
                $query->whereNotNull('modules.status');
            }

            $query->where(function ($q) use ($request) {
                $column = $request->search['column'];
                $type = $request->search['regex'] ? 'like' : '=';
                $value = $request->search['value'];
                $value = $type == 'like' ? DB::raw("'%{$value}%'") : $value;
                if ($column == 'module' || $column == '*') {
                    $q->where('module', $type, $value);
                }
                if ($column == 'correlative' || $column == '*') {
                    $q->orWhere('services.correlative', $type, $value);
                }
                if ($column == 'description' || $column == '*') {
                    $q->orWhere('description', $type, $value);
                }
                if ($column == 'repository' || $column == '*') {
                    $q->orWhere('services.repository', $type, $value);
                }
            });

            $iTotalDisplayRecords = $query->count();
            $modulesJpa = $query
                ->skip($request->start)
                ->take($request->length)
                ->get();

            $modules = array();
            foreach($modulesJpa as $modulJpa){
                $module = gJSON::restore($modulJpa->toArray());
                $modules[] = $module;
            }

            $response->setStatus(200);
            $response->setMessage('Operación correcta');
            $response->setDraw($request->draw);
            $response->setITotalDisplayRecords($iTotalDisplayRecords);
            $response->setITotalRecords(Module::count());
            $response->setData($modules);
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

    public function store(Request $request)
    {
        $response = new Response();
        try {

            if (
                !$request->module ||
                !$request->service
            ) {
                throw new Exception("Error: No deje campos vacíos");
            }
            
            [$status, $message, $role] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gValidate::check($role->permissions, 'modules', 'create')) {
                throw new Exception('No tienes permisos para listar los roles del sistema');
            }


            $description = $request->description ?? null;
            $repository = $request->repository ?? null;

            $moduleValidation = Module::select(['modules.module'])
                ->where('module', $request->module)
                ->first();
            if ($moduleValidation) {

                if ($moduleValidation->module == $request->module) {
                    throw new Exception("Escoja otro nombre para el módulo");
                }
            }

            $moduleJpa = new Module();
            $moduleJpa->module = $request->module;
            $moduleJpa->_service = $request->service;
            $moduleJpa->description = $description;
            $moduleJpa->save();

            $response->setStatus(200);
            $response->setMessage('El módulo se ha agregado correctamente');
            $response->setData($moduleJpa->toArray());
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

    public function update(Request $request)
    {
        $response = new Response();
        try {
            
            if (
                !$request->module ||
                !$request->service ||
                !$request->description ||
                !$request->status
            ) {
                throw new Exception("Error: No deje campos vacíos");
            }
           
            [$status, $message, $role] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gValidate::check($role->permissions, 'modules', 'update')) {
                throw new Exception('No tienes permisos para listar los roles del sistema');
            }

            $moduleValidation = Module::select('id')
                ->where('module', $request->module)
                ->where('id', '!=', $request->id)
                ->first();
            if ($moduleValidation) {
                throw new Exception("Escoja otro nombre para el módulo");
            }

            $moduleJpa = Module::find($request->id);
            if (!$moduleJpa) {
                throw new Exception("No se puede actualizar este registro");
            }
            $moduleJpa->module = $request->module;
            $moduleJpa->description = $request->description;
            $moduleJpa->_service = $request->service;
            $moduleJpa->status = $request->status;
           
            $moduleJpa->save();

            $response->setStatus(200);
            $response->setMessage('El módulo se a actualizado correctamente');
            $response->setData($moduleJpa->toArray());
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

    public function delete(Request $request)
    {
        $response = new Response();
        try {
            
            [$status, $message, $role] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gValidate::check($role->permissions, 'modules', 'delete_restore')) {
                throw new Exception('No tienes permisos para listar los roles del sistema');
            }

            if (
                !isset($request->id)
            ) {
                throw new Exception("Error: Es necesario el ID para esta operación");
            }

            $moduleJpa = Module::find($request->id);

            if (!$moduleJpa) {
                throw new Exception("Este registro no existe");
            }

            $moduleJpa->status = null;
            $moduleJpa->save();

            $response->setStatus(200);
            $response->setMessage('El módulo se ha eliminado correctamente');
            $response->setData($moduleJpa->toArray());
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

    public function restore(Request $request)
    {
        $response = new Response();
        try {
              
            [$status, $message, $role] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gValidate::check($role->permissions, 'modules', 'delete_restore')) {
                throw new Exception('No tienes permisos para listar los roles del sistema');
            }

            if (
                !isset($request->id)
            ) {
                throw new Exception("Error: Es necesario el ID para esta operación");
            }

            $moduleJpa = Module::find($request->id);
            if (!$moduleJpa) {
                throw new Exception("Este registro no existe");
            }
            $moduleJpa->status = "1";
            $moduleJpa->save();

            $response->setStatus(200);
            $response->setMessage('El modulo ha sido restaurado correctamente');
            $response->setData($moduleJpa->toArray());
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
