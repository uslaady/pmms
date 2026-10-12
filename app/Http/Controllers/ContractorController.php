<?php

namespace App\Http\Controllers;

use App\Models\Contractor;
use App\Models\ContractorDocument;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Contractor workflow:
 *  1. Director Planning registers contractors (one by one or bulk CSV) and uploads
 *     their registration documents. New contractors are Pending.
 *  2. The Permanent Secretary checks the details/documents and makes them Active.
 *  3. Only Active contractors can be assigned to approved projects (by Director Planning).
 *
 * A Deactivated / Suspended / Blacklisted contractor is LOCKED: its details and
 * documents can no longer be changed, except by the Super Admin, Commissioner or
 * Permanent Secretary, who may also delete it. Its existing projects keep working
 * and keep showing its name; it simply cannot receive new projects.
 */
class ContractorController extends Controller
{
    private const CSV_COLUMNS = ['name', 'rc_number', 'tax_id', 'contact_person', 'email', 'phone', 'address', 'director'];

    public function index(Request $request)
    {
        $contractors = Contractor::withCount('projects')
            ->when($request->filled('q'), fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', '%'.$request->string('q').'%')
                ->orWhere('rc_number', 'like', '%'.$request->string('q').'%')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderBy('name')->paginate($this->perPage($request))->withQueryString();

        return view('contractors.index', compact('contractors'));
    }

    public function show(Contractor $contractor)
    {
        $contractor->load(['documents.uploader', 'projects' => fn ($q) => $q->orderBy('project_code')]);

        return view('contractors.show', compact('contractor'));
    }

    public function store(Request $request)
    {
        abort_unless($request->user()->hasPermission('contractors.register'), 403);

        $data = $this->validateContractor($request);
        $data['status'] = 'pending';

        $contractor = Contractor::create($data);
        app(NotificationService::class)->contractorsSubmitted(1, $request->user());

        return redirect()->route('contractors.show', $contractor)
            ->with('status', "{$contractor->name} registered. Upload its registration documents below; the Permanent Secretary will then review and activate it.");
    }

    public function update(Request $request, Contractor $contractor)
    {
        abort_unless($contractor->canBeEditedBy($request->user()), 403, $contractor->isFrozen()
            ? "{$contractor->name} is {$contractor->status} and locked. Only the Super Admin, Commissioner or Permanent Secretary can edit it."
            : 'You do not have permission to edit contractors.');

        $contractor->update($this->validateContractor($request, $contractor));

        return back()->with('status', "{$contractor->name} updated.");
    }

    /**
     * Permanent Secretary (or Admin / Commissioner): Pending -> Active, or lock it
     * as Deactivated / Suspended / Blacklisted, or reactivate it.
     */
    public function setStatus(Request $request, Contractor $contractor)
    {
        abort_unless($request->user()->hasPermission('contractors.status'), 403);

        $data = $request->validate(['status' => 'required|in:'.implode(',', Contractor::STATUSES)]);
        $wasActive = $contractor->status === 'active';

        $contractor->update($data + ['rejection_reason' => null]);

        if (! $wasActive && $data['status'] === 'active') {
            app(NotificationService::class)->contractorActivated($contractor);
        }

        return back()->with('status', "{$contractor->name} is now {$data['status']}.");
    }

    /** Perm Sec declines a Pending contractor (with a reason). Planning corrects it and resubmits it. */
    public function reject(Request $request, Contractor $contractor)
    {
        abort_unless($request->user()->hasPermission('contractors.status'), 403);
        abort_unless($contractor->status === 'pending', 422, 'Only Pending contractors can be rejected.');

        $data = $request->validate(['rejection_reason' => 'required|string|max:2000'], [
            'rejection_reason.required' => 'Please give the reason for rejecting this contractor, so it can be corrected.',
        ]);

        $contractor->update(['status' => 'rejected', 'rejection_reason' => $data['rejection_reason']]);
        app(NotificationService::class)->contractorRejected($contractor);

        return back()->with('status', "{$contractor->name} rejected. The Director Planning has been notified.");
    }

    public function resubmit(Request $request, Contractor $contractor)
    {
        abort_unless($request->user()->hasPermission('contractors.edit'), 403);
        abort_unless($contractor->isRejected(), 422, 'Only rejected contractors can be resubmitted.');

        $contractor->update(['status' => 'pending']);
        app(NotificationService::class)->contractorsSubmitted(1, $request->user());

        return back()->with('status', "{$contractor->name} resubmitted to the Permanent Secretary for approval.");
    }

    /**
     * Only a LOCKED (or rejected) contractor can be deleted, and only by the executives. It is a
     * soft delete: its name stays on every project it worked on.
     */
    public function destroy(Request $request, Contractor $contractor)
    {
        abort_unless($contractor->canBeDeletedBy($request->user()), 403,
            'Only a Rejected, Deactivated, Suspended or Blacklisted contractor can be deleted, and only by the Super Admin, Commissioner or Permanent Secretary.');

        $name = $contractor->name;
        $contractor->delete();

        return redirect()->route('contractors.index')->with('status', "{$name} deleted. Its name remains on its existing projects.");
    }

    /* ---------------------------- Documents ---------------------------- */

    public function storeDocument(Request $request, Contractor $contractor)
    {
        abort_unless($request->user()->hasPermission('contractors.documents'), 403);
        abort_unless($contractor->canBeEditedBy($request->user()), 403,
            "{$contractor->name} is {$contractor->status} and locked: documents can no longer be added.");

        $request->validate([
            'type' => ['required', Rule::in(array_keys(ContractorDocument::TYPES))],
            'title' => 'required|string|max:255',
            'file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ], [
            'file.required' => 'No file selected — choose a document before clicking Upload.',
            'file.mimes' => 'Unsupported file type — accepted: PDF, DOC/DOCX, JPG, PNG.',
            'file.max' => 'Documents must not exceed 5 MB.',
        ]);

        $file = $request->file('file');
        // Registration documents are private: stored outside the public folder, downloadable only after sign-in.
        $path = $file->store("contractors/{$contractor->id}", 'local');

        $contractor->documents()->create([
            'type' => $request->input('type'),
            'title' => $request->input('title'),
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'uploaded_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Document uploaded.');
    }

    public function downloadDocument(ContractorDocument $contractorDocument)
    {
        abort_unless(Storage::disk('local')->exists($contractorDocument->path), 404, 'The file is missing from storage.');

        $ext = pathinfo($contractorDocument->path, PATHINFO_EXTENSION);
        $name = $contractorDocument->title;
        if ($ext && ! str_ends_with(strtolower($name), '.'.strtolower($ext))) {
            $name .= '.'.$ext;
        }

        return Storage::disk('local')->download($contractorDocument->path, $name);
    }

    public function destroyDocument(Request $request, ContractorDocument $contractorDocument)
    {
        $contractor = $contractorDocument->contractor;
        abort_unless($request->user()->hasPermission('contractors.documents'), 403);
        abort_unless($contractor->canBeEditedBy($request->user()), 403,
            "{$contractor->name} is {$contractor->status} and locked: documents can no longer be removed.");

        Storage::disk('local')->delete($contractorDocument->path);
        $contractorDocument->delete();

        return back()->with('status', 'Document removed.');
    }

    /* ----------------------------- Bulk CSV ----------------------------- */

    public function downloadTemplate(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, self::CSV_COLUMNS);
            fputcsv($out, ['Example Builders Ltd', 'RC123456', 'TIN-0001234', 'Musa Ali', 'info@example.com', '08030000000', '12 Example Road, Maiduguri', 'Aisha Bello']);
            fclose($out);
        }, 'contractor-upload-template.csv', ['Content-Type' => 'text/csv']);
    }

    /** Valid rows are registered as Pending; invalid rows are skipped and reported. */
    public function bulkStore(Request $request)
    {
        abort_unless($request->user()->hasPermission('contractors.register'), 403);
        $request->validate(['file' => 'required|file|mimes:csv,txt|max:5120']);

        $handle = fopen($request->file('file')->getRealPath(), 'r');
        $header = fgetcsv($handle);
        if (! $header) {
            return back()->withErrors(['file' => 'The uploaded file is empty.']);
        }
        // trim also removes the byte-order mark Excel adds to "CSV UTF-8" files
        $header = array_map(fn ($h) => strtolower(trim((string) $h, " \t\n\r\0\x0B\xEF\xBB\xBF")), $header);
        if (! in_array('name', $header, true)) {
            fclose($handle);
            return back()->withErrors(['file' => 'Template column "name" is missing. Download the template and keep the header row unchanged.']);
        }

        $existing = Contractor::withTrashed()->pluck('name')->map(fn ($n) => strtolower(trim($n)))->flip();
        $seen = [];
        $created = 0;
        $errors = [];
        $rowNum = 1;

        while (($cells = fgetcsv($handle)) !== false) {
            $rowNum++;
            if (count(array_filter($cells, fn ($c) => trim((string) $c) !== '')) === 0) {
                continue;
            }
            $row = [];
            foreach ($header as $i => $col) {
                if (in_array($col, self::CSV_COLUMNS, true)) {
                    $row[$col] = isset($cells[$i]) ? trim((string) $cells[$i]) : '';
                }
            }

            $rowErrors = [];
            $key = strtolower($row['name'] ?? '');
            if ($key === '') {
                $rowErrors[] = 'name is required';
            } elseif (isset($existing[$key])) {
                $rowErrors[] = "a contractor named '{$row['name']}' is already registered";
            } elseif (isset($seen[$key])) {
                $rowErrors[] = "duplicate name within this file (first used on row {$seen[$key]})";
            }
            if (($row['email'] ?? '') !== '' && ! filter_var($row['email'], FILTER_VALIDATE_EMAIL)) {
                $rowErrors[] = "email '{$row['email']}' is not a valid email address";
            }
            foreach (['name' => 255, 'rc_number' => 50, 'tax_id' => 50, 'contact_person' => 255, 'phone' => 30, 'address' => 255, 'director' => 255] as $col => $max) {
                if (mb_strlen($row[$col] ?? '') > $max) {
                    $rowErrors[] = "{$col} is longer than {$max} characters";
                }
            }

            if ($rowErrors) {
                $errors[] = ['row' => $rowNum, 'code' => $row['name'] ?: '—', 'reasons' => $rowErrors];
                continue;
            }

            Contractor::create(array_map(fn ($v) => $v === '' ? null : $v, $row) + ['status' => 'pending']);
            $seen[$key] = $rowNum;
            $created++;
        }
        fclose($handle);

        if ($created > 0) {
            app(NotificationService::class)->contractorsSubmitted($created, $request->user());
        }

        return redirect()->route('contractors.index')->with('bulk_results', ['created' => $created, 'errors' => $errors]);
    }

    private function validateContractor(Request $request, ?Contractor $contractor = null): array
    {
        return $request->validate([
            // Names must be unique: bulk uploads and searches match contractors by name
            'name' => ['required', 'string', 'max:255', Rule::unique('contractors', 'name')->ignore($contractor?->id)],
            'rc_number' => 'nullable|string|max:50',
            'tax_id' => 'nullable|string|max:50',
            'contact_person' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:30',
            'director' => 'nullable|string|max:255',
        ], [
            'name.unique' => 'A contractor with this name is already registered.',
        ]);
    }
}
