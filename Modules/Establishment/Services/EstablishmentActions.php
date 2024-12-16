<?php


namespace Modules\Establishment\Services;

use Illuminate\Support\Facades\File;
use Modules\Establishment\Models\Establishment;

class EstablishmentActions
{
    public function __construct(protected $request)
    {
    }

    public function storeImage($image, $oldImage = null)
    {
        $oldPath = public_path('storage/tenant' . tenancy()->tenant->id . '/' . $oldImage);

        if (File::exists($oldPath)) {
            File::delete($oldPath);
        }

        $logoName = 'est_logos/' . time() . '.' . $image->extension();
        $image->storeAs('', $logoName, 'public');
        return $logoName;
    }

    public function store()
    {
        $logoName = $this->request->has('logo') ? $this->storeImage($this->request['logo']) : null;

        Establishment::create($this->request->merge([
            'logo' => $logoName,
        ])->all());
    }

    public function update($establishment)
    {
        $logoName = $this->request->has('logo')
            ? $this->storeImage($this->request['logo'], $establishment->logo)
            : null;

        $data = $this->request;

        if ($logoName) {
            $data = $data->merge([
                'logo' => $logoName,
            ]);
        } elseif (!$this->request->get('logo_old')) {
            $data = $data->merge(['logo' => null]);
        }
        return $establishment->update($data->toArray());
    }

}