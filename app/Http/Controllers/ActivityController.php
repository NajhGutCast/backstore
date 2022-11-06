<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\gLibraries\gjson;
use App\gLibraries\gtrace;
use App\Models\Response;
use App\gLibraries\gvalidate;
use App\Models\Activity;
use Carbon\Carbon;
use Exception;

class ActivityController extends Controller
{
    public function getpending(Request $request){
        $response = new Response();
        try {
            [$status, $message, $role] = gValidate::get($request);
            if ($status != 200) {
                throw new Exception($message);
            }
            if (!gvalidate::check($role->permissions, 'activitiespending', 'read')) {
                throw new Exception('No tienes permisos para listar los usuarios del sistema');
            }

            $activitiesJpa = Activity::select([
                'activities.id',

                'modules.id AS module.id',
                'modules.module AS module.module',
                'modules.description AS module.description',

                'services.id AS module.service.id',
                'services.service AS module.service.service',
                'services.correlative AS module.service.correlative',
                'services.repository AS module.service.repository',
                'services.status AS module.service.status',

                'modules.status AS module.status',

                'activities.activity',
                'activities.observation',
                'activities.priority',

                'environments.id AS environment.id',
                'environments.environment AS environment.environment',
                'environments.domain AS environment.domain',
                'environments.description AS environment.description',
                'environments.status AS environment.status',

                'activities.relative_hours',
                'activities.accepted_hours',

                'invoices.id AS invoice.id',
                'invoices.relative_id AS invoice.relative_id',
                'invoices.issue_date AS invoice.issue_date',

                'users.id AS user.id',
                'users.relative_id AS user.relative_id',
                'users.username As user.username',
                'users.dni AS user.dni',
                'users.name AS user.name',
                'users.lastname AS user.lastname',
                'users.email AS user.email',
                'users.phone_prefix AS user.phone_prefix',
                'users.phone_number As user.phone_number',
                
                'roles.id AS user.role.id',
                'roles.role AS user.role.role',
                'roles.priority AS user.role.priority',
                'roles.description AS user.role.description',
                'roles.permissions AS user.role.permissions',
                'roles.status AS user.role.status',

                'activities.creation_date',
                'activities.update_date',
                'activities.observation_date',
                'activities.status',
            ])
            ->leftjoin('modules', 'activities._module', '=', 'modules.id')
            ->leftjoin('services', 'modules._service', '=', 'services.id')
            ->leftjoin('environments', 'activities._environment', '=', 'environments.id')
            ->leftjoin('invoices', 'activities._invoice', '=', 'invoices.id')
            ->leftjoin('users', 'activities._user', '=', 'users.id')
            ->leftjoin('roles', 'users._role', '=', 'roles.id')
            ->where('activities.status', '=', 'PENDIENTE')
            ->get()
            ;

            $activities = array();

            foreach($activitiesJpa as $activityJpa){
                $activity = gJSON::restore($activityJpa->toArray());
                $activity['user']['role']['permissions'] = gJSON::parse($activity['user']['role']['permissions']);
                $activities[] = $activity;
            }

            $response->setStatus(200);
            $response->setMessage("Operacion Correcta");
            $response->setData($activities);

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

    public function store(Request $request){
        $response = new Response();
       
        try {
            $date = new Carbon();
            // if (
            //     !isset($request->module) ||
            //     !isset($request->activity) ||
            //     !isset($request->obserbation) ||
            //     !isset($request->priority) ||
            //     !isset($request->environment) ||
            //     !isset($request->status)

            // ) {
            //     throw new Exception("Error: No deje campos vacíos");
            // }

            // [$status, $message, $role] = gValidate::get($request);
            // if ($status != 200) {
            //     throw new Exception($message);
            // }
            // if (!gvalidate::check($role->permissions, 'users', 'create')) {
            //     throw new Exception('No tienes permisos para agregar usuarios en el sistema');
            // }
            $response ->setStatus(200);
            $response->setMessage("Actividad agregada correctamente");
            $response->setData([gTrace::getDate('mysql')]);
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
