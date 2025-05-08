<?php

namespace Modules\Product\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Product\Models\TreeBuilder;
use Modules\Product\Models\CustomMenu;
use Modules\Product\Models\CustomMenuTime;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use Modules\Product\Enums\Mode;
use Modules\Product\Models\CustomMenuItem;
use Modules\Product\Models\CustomMenuTimeDetail;

class CustomMenuController extends Controller
{
    public function index()
    {
        // Pass the posts to the view
        return view('product::customMenu.index');
    }

    // public function getCustomMenus()
    // {
    //     $TreeBuilder = new TreeBuilder();
    //     $customMenues = CustomMenu::all();

    //     $customMenues->transform(function ($menu) {
    //         if (!empty($menu->mode)) {
    //             $modeArray = json_decode($menu->mode, true);
    //             $menu->mode = is_array($modeArray) ? implode(',', $modeArray) : $menu->mode;
    //         }
    //         return $menu;
    //     });

    //     $tree = $TreeBuilder->buildTree($customMenues, null, 'customMenu', null, null, null);
    //     return response()->json($tree);
    // }




    public function getCustomMenus()
    {
        $TreeBuilder = new TreeBuilder();
        $customMenues = CustomMenu::all();

        // تحميل ملف الترجمة JSON
        $translationFilePath = resource_path('components/lang/ar.json');
        $translations = File::exists($translationFilePath)
            ? json_decode(File::get($translationFilePath), true)
            : [];

        // تحويل mode من القيم الرقمية إلى الترجمة
        $customMenues->transform(function ($menu) use ($translations) {
            if (!empty($menu->mode)) {
                $modeArray = json_decode($menu->mode, true);

                if (is_array($modeArray)) {
                    $translatedModes = array_map(
                        fn($value) =>
                        $translations[Mode::tryFrom($value)?->name] ?? Mode::tryFrom($value)?->name,
                        $modeArray
                    );

                    $menu->mode = implode(', ', $translatedModes);
                }
            }
            return $menu;
        });

        $tree = $TreeBuilder->buildTree($customMenues, null, 'customMenu', null, null, null);
        return response()->json($tree);
    }


    public function create()
    {
        $custommenu  = new CustomMenu();
        return view('product::customMenu.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_ar' => 'required|string|max:255',
            'name_en' => 'required|string',
            'application_type' => 'required|numeric',
            'mode' => 'nullable',
            'station_id' => 'nullable',
            'active' => 'nullable|boolean',
            'id' => 'nullable|numeric',
            'method' => 'nullable|string'
        ]);
        $validated['mode'] = json_encode($validated['mode']);
        $validated['station_id'] = json_encode($validated['station_id']);


        if (isset($validated['method']) && ($validated['method'] == "delete")) {
            $customMenu = CustomMenu::find($validated['id']);
            $customMenu->delete();
            return response()->json(["message" => "Done"]);
        }

        if (!isset($validated['id'])) {
            // try {
            $cMenu = CustomMenu::where('name_ar', $validated['name_ar'])->first();
            if ($cMenu != null)
                return response()->json(["message" => "NAME_AR_EXIST"]);
            $cMenu = CustomMenu::where('name_en', $validated['name_en'])->first();
            if ($cMenu != null)
                return response()->json(["message" => "NAME_EN_EXIST"]);
            DB::transaction(function () use ($validated, $request) {
                $customMenu = CustomMenu::create($validated);
                if (isset($request["dates"])) {
                    $customMenuDate = $request["dates"];
                    $customMenuDate['custommenu_id'] = $customMenu->id;
                    $result = CustomMenuTime::create($customMenuDate);
                    foreach ($customMenuDate['times'] as $timed) {
                        $dated['custommenu_time_id'] = $result->id;
                        $dated['day_no'] = $timed['day_no'];
                        $dated['from_time'] = $timed['from_time'];
                        $dated['to_time'] = $timed['to_time'];
                        $dated['active'] = false;
                        $result1 = CustomMenuTimeDetail::create($dated);
                    }
                }
                if (isset($request['products'])) {
                    foreach ($request['products'] as $newProduct) {
                        if (isset($newProduct)) {
                            $prod = new CustomMenuItem();
                            $prod->product_id = $newProduct['product_id'];
                            $prod->custommenu_id = $customMenu->id;
                            $prod = $prod->save();
                        }
                    }
                }
            });
            // } catch (QueryException $e) {
            //     return response()->json(["message" => "ERROR_SAVING"]);
            // }
        } else {
            $modifier = CustomMenu::where([
                ['id', '!=', $validated['id']],
                ['name_ar', '=', $validated['name_ar']]
            ])->first();
            if ($modifier != null)
                return response()->json(["message" => "NAME_AR_EXIST"]);
            $modifier = CustomMenu::where([
                ['id', '!=', $validated['id']],
                ['name_en', '=', $validated['name_en']]
            ])->first();
            if ($modifier != null)
                return response()->json(["message" => "NAME_EN_EXIST"]);
            $customMenu = CustomMenu::find($validated['id']);
            $customMenu->name_ar = $validated['name_ar'];
            $customMenu->name_en = $validated['name_en'];
            $customMenu->application_type = $validated['application_type'];
            $customMenu->mode = $validated['mode'];
            $customMenu->station_id = $validated['station_id'];
            $customMenu->active = $validated['active'];
            //try {
            DB::transaction(function () use ($customMenu, $request) {
                $customMenu->save();
                if (isset($request['dates'])) {
                    $newDated = $request['dates'];
                    $dated = CustomMenuTime::find($newDated['id']);
                    $dated['from_date'] = $newDated['from_date'];
                    $dated['to_date'] = $newDated['to_date'];
                    $dated->save();
                    if (isset($newDated['times'])) {
                        foreach ($newDated['times'] as $newTime) {
                            $timed = CustomMenuTimeDetail::find($newTime['id']);
                            $timed['from_time'] = $newTime['from_time'];
                            $timed['to_time'] = $newTime['to_time'];
                            $timed['active'] = $newTime['active'];
                            $timed->save();
                        }
                    }
                }
                if (isset($request['products'])) {
                    CustomMenuItem::where('custommenu_id', '=', $customMenu->id)->delete();
                    foreach ($request['products'] as $newProduct) {
                        if (isset($newProduct)) {
                            $prod = new CustomMenuItem();
                            $prod->product_id = $newProduct['product_id'];
                            $prod->custommenu_id = $customMenu->id;
                            $prod = $prod->save();
                        }
                    }
                }
            });
            //} catch (QueryException $e) {
            //   return response()->json(["message" => "ERROR_SAVING"]);
            //}
        }
        return response()->json(["message" => "Done"]);
    }

    public function show($id)
    {
        $item = CustomMenu::find($id);
        if ($item) {
            return response()->json($item);
        }
        return response()->json(['error' => 'Item not found'], 404);
    }

    public function edit($id)
    {
        $custommenu  = CustomMenu::find($id);
        $custommenu->dates = $custommenu->dates;
        foreach ($custommenu->dates as $d) {
            $d->times = $d->times;
        }
        $custommenu->products = $custommenu->products;
        return view('product::customMenu.edit', compact('custommenu'));
    }

    public function update(Request $request, $id)
    {
        // Logic to update a specific post
    }

    public function destroy($id)
    {
        // Logic to delete a specific post
    }
}
