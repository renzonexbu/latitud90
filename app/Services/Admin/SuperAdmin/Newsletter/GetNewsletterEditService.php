<?php

namespace App\Services\Admin\SuperAdmin\Newsletter;

use App\Models\Newsletter;

class GetNewsletterEditService
{
    public function execute($id)
    {
        $newsletter = Newsletter::findOrFail($id);
        
        return [
            'newsletter' => $newsletter
        ];
    }
}
