<?php


namespace App\Models\App\Traits;


trait TaskValidationRules
{
    public function createdRules()
    {
        return [
            'title' => 'required|min:2|max:195',
            'stage_id' => 'required|exists:stages,id',
        ];
    }

    public function updatedRules()
    {
        return [
            'title' => 'required|min:2|max:195',
            'stage_id' => 'sometimes|required|exists:stages,id',
        ];
    }
}
