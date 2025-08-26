<?php

namespace App\Services\Admin\SuperAdmin\Newsletter;

use App\Models\Newsletter;
use App\Traits\AdminLogging;

class DeleteNewsletterService
{
    use AdminLogging;

    public function execute($id)
    {
        $newsletter = Newsletter::findOrFail($id);
        $newsletterData = $newsletter->toArray();
        
        $newsletter->delete();

        // Log the deletion
        $this->logDelete(
            'newsletter',
            "Newsletter eliminado: {$newsletterData['email']}",
            $newsletterData
        );

        return $newsletterData;
    }
}
