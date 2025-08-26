<?php

namespace App\Services\Admin\SuperAdmin\Newsletter;

use App\Models\Newsletter;
use App\Traits\AdminLogging;
use Illuminate\Http\Request;

class UpdateNewsletterService
{
    use AdminLogging;

    public function execute(Request $request, $id)
    {
        $newsletter = Newsletter::findOrFail($id);
        
        $request->validate([
            'email' => 'required|email|unique:newsletter,email,' . $id,
            'is_active' => 'boolean'
        ]);

        $oldValues = $newsletter->toArray();
        $newsletter->update($request->only(['email', 'is_active']));

        // Log the update
        $this->logUpdate(
            'newsletter',
            "Newsletter actualizado: {$newsletter->email}",
            $oldValues,
            $newsletter->fresh()->toArray()
        );

        return $newsletter;
    }
}
