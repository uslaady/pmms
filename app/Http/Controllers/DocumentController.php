<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    public const TYPES = [
        'BOQ', 'Contract Agreement', 'Drawing', 'Certificate',
        'Payment Certificate', 'Variation Order', 'Inspection Report', 'Letter', 'Other',
    ];

    public function store(Request $request, Project $project)
    {
        abort_unless($request->user()->hasPermission('documents.upload'), 403);

        $request->validate(
            [
                'type' => 'required|in:'.implode(',', self::TYPES),
                'title' => 'required|string|max:255',
                'file' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,jpg,png|max:1024',
            ],
            [
                'file.required' => 'No file selected — choose a document before clicking Upload.',
                'file.mimes' => 'Unsupported file type — accepted: PDF, DOC/DOCX, XLS/XLSX, JPG, PNG.',
                'file.max' => sprintf(
                    'The selected document is %.2f MB. Documents must not exceed 1 MB. Please reduce the file size before uploading.',
                    ($request->file('file')?->getSize() ?? 0) / 1048576,
                ),
            ],
        );

        $path = $request->file('file')->store("projects/{$project->id}/documents", 'public');

        $project->documents()->create([
            'type' => $request->input('type'),
            'title' => $request->input('title'),
            'path' => $path,
            'uploaded_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Document uploaded.');
    }

    /**
     * Remove a document (to replace one: delete it, then upload the new version).
     */
    public function destroy(Request $request, ProjectDocument $document)
    {
        abort_unless($request->user()->hasPermission('documents.upload'), 403);

        Storage::disk('public')->delete($document->path);
        $document->delete();

        return back()->with('status', 'Document removed.');
    }

    /**
     * Download keeps the real file extension so the file opens directly
     * in the right application on the user's computer.
     */
    public function download(Request $request, ProjectDocument $document)
    {
        abort_unless($request->user()->hasPermission('projects.view'), 403);

        $ext = pathinfo($document->path, PATHINFO_EXTENSION);
        $name = $document->title;
        if ($ext && ! str_ends_with(strtolower($name), '.'.strtolower($ext))) {
            $name .= '.'.$ext;
        }

        return Storage::disk('public')->download($document->path, $name);
    }
}
