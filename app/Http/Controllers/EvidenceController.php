<?php

namespace App\Http\Controllers;

use App\Models\EvidenceFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EvidenceController extends Controller
{
    public function download(EvidenceFile $evidence): StreamedResponse
    {
        Gate::authorize('view', $evidence);

        $disk = Storage::disk('local');

        abort_unless($disk->exists($evidence->path), 404, 'The evidence file could not be found.');

        return $disk->download($evidence->path, $evidence->original_name, [
            'Content-Type' => $evidence->mime_type ?: 'application/octet-stream',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }
}
