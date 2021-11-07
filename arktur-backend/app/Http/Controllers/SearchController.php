<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Module;
use App\Rules\Search;
use Illuminate\Http\Request;

class SearchController extends Controller {
    private function semesterToString($semester) {
        if ($semester % 10 == 0) {
            return "SoSe " . (int)($semester / 10);
        } else {
            return "WiSe " . (int)($semester / 10);
        };
    }

    private function group_by($key, $data) {
        $result = array();

        foreach ($data as $model) {
            $semester = $this->semesterToString($model[$key]);
            if (!array_key_exists($semester, $result)) {
                $result[$semester] = array($model);
            } else {
                array_push($result[$semester], $model);
            }
        }

        return $result;
    }

    private function inModuleArray($array, $module) {
        foreach($array as $elem) {
            if($module['modulecode'] == $elem['modulecode']) {
                return true;
            }
        }
        return false;
    }

    public function getEvent(Request $request) {
        $request->validate([
            'vnr' => ['required', 'integer'],
            'semester' => ['required', 'integer']
        ]);

        $event = Event::select('id', 'active', 'rotation', 'semester', 'sws', 'targets', 'title', 'type', 'vnr')
            ->where('vnr', $request->vnr)
            ->where('semester', $request->semester)
            ->get()
            ->first();
        
        if(!$event) {
            return response("Event not found", 404);
        }

        $people = Event::find($event->id)
            ->users()
            ->select('displayname', 'forename', 'surname', 'uid')
            ->get();

        $modules = Event::find($event->id)
            ->modules()
            ->select('modulecode')
            ->get();

        $response = [
            'content' => $event,
            'people' => $people,
            'modules' => $modules
        ];

        return response($response, 200);
    }

    public function searchEvent(Request $request) {
        $request->validate([
            'value' => [new Search],
            'limit' => ['integer']
        ]);

        $events = Event::select('active', 'semester', 'title', 'vnr')
            ->where('title', 'LIKE', '%' . $request->value . '%')
            ->orWhere('vnr', 'LIKE', '%' . $request->value . '%')
            ->limit($request->limit)
            ->orderBy('semester', 'desc')
            ->get();

        $response = $this->group_by("semester", $events);

        return response($response, 200);
    }

    public function getModule(Request $request) {
        $request->validate([
            'modulecode' => ['required', 'regex:/^[\w\d\-]*$/'],
        ]);

        $module = Module::select('id', 'modulecode', 'title_de', 'title_en', 'active', 'rotation', 'composition', 'ects', 'presence_time', 'workload', 'type', 'prior_knowledge', 'content', 'required_creditpoints', 'requirement_exam', 'requirement_admission', 'additional_info', 'literature')
            ->where('modulecode', $request->modulecode)
            ->get()
            ->first();

        $people = Module::find($module->id)
            ->users()
            ->select('displayname', 'forename', 'surname', 'uid')
            ->get();

        $events = Module::find($module->id)
            ->events()
            ->select('vnr', 'semester')
            ->orderBy('semester', 'desc')
            ->get();

        $events = $this->group_by("semester", $events);

        $response = [
            'content' => $module,
            'people' => $people,
            'events' => $events
        ];

        return response($response, 200);
    }

    public function searchModule(Request $request) {
        $request->validate([
            'value' => [new Search],
            'limit' => ['integer']
        ]);

        $modules = Module::select('active', 'title_de', 'title_en', 'modulecode')
            ->where('title_de', 'LIKE', '%' . $request->value . '%')
            ->orWhere('title_en', 'LIKE', '%' . $request->value . '%')
            ->orWhere('modulecode', 'LIKE', '%' . $request->value . '%')
            ->limit($request->limit)
            ->get();

        $response = $modules;

        return response($response, 200);
    }

    public function getNewEntries() {
        $changed_events = Event::select('*')
            ->where('changed', true)
            ->get();

        $response = [];
        $changed_modules = [];

        foreach ($changed_events as &$event) {
            $modules = Event::find($event->id)
            ->modules()
            ->get()
            ->toArray();

            foreach($modules as &$module) {
                if(!$this->inModuleArray($changed_modules, $module)) {
                    array_push($changed_modules, $module);
                }
            }
        }

        foreach ($changed_modules as &$module) {
            $events = Module::find($module['id'])
                ->events()
                ->get();

            $pnrs = [];
            $pnrObj = [];
            foreach ($events as &$event) {
                $pnr = $event->pivot->pnr;
                if (!in_array($pnr, $pnrs)) {
                    array_push($pnrs, $pnr);
                    $item['pnr'] = $pnr;
                    $item['events'] = Module::find($module['id'])
                        ->events()
                        ->where('pivot_pnr', $pnr)
                        ->where('events.changed', true)
                        ->get();
                    array_push($pnrObj, $item);
                }
            }

            $item = [];
            $item['module'] = $module['modulecode'];
            $item['pnr'] = $pnrObj;

            array_push($response, $item);
        }

        return response($response, 200);
    }
}
