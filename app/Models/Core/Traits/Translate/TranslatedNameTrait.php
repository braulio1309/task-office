<?php


namespace App\Models\Core\Traits\Translate;


trait TranslatedNameTrait
{
    public function getTranslatedNameAttribute()
    {
        $key = "default.{$this->attributes['name']}";
        $translated = trans($key);

        if (empty($translated) || $translated === $key) {
            return ucwords(str_replace('_', ' ', $this->attributes['name']));
        }

        return $translated;
    }

}
