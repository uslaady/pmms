<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectImage;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProjectImageController extends Controller
{
    /**
     * Field upload of geo-tagged photos (Module 8).
     * Images over 1 MB are automatically resized/re-encoded to roughly
     * 300 KB before storage, so field engineers can upload straight from
     * phone cameras without worrying about file size.
     */
    public function store(Request $request, Project $project)
    {
        abort_unless($request->user()->hasPermission('images.upload'), 403);

        $request->validate(
            [
                'image' => 'required|image|mimes:jpg,jpeg,png,gif,webp|max:20480',
                'stage' => 'required|in:before,during,after',
                'caption' => 'nullable|string|max:255',
                'gps_lat' => 'nullable|numeric|between:-90,90',
                'gps_lng' => 'nullable|numeric|between:-180,180',
                'milestone_id' => 'nullable|exists:project_milestones,id',
            ],
            [
                'image.required' => 'No image selected — choose a photo before clicking Upload.',
                'image.image' => 'The selected file is not an image. Accepted formats: JPG, PNG, GIF, WebP.',
                'image.mimes' => 'Unsupported image format — use JPG, PNG, GIF, or WebP.',
                'image.max' => sprintf(
                    'The selected image is %.2f MB — the maximum the server accepts is 20 MB. Images over 1 MB are compressed automatically, so please choose a photo under 20 MB.',
                    ($request->file('image')?->getSize() ?? 0) / 1048576,
                ),
            ],
        );

        $path = $this->storeCompressed($request->file('image'), "projects/{$project->id}/images");

        $project->images()->create([
            'path' => $path,
            'stage' => $request->input('stage'),
            'caption' => $request->input('caption'),
            'gps_lat' => $request->input('gps_lat'),
            'gps_lng' => $request->input('gps_lng'),
            'milestone_id' => $request->input('milestone_id'),
            'uploaded_by' => $request->user()->id,
        ]);

        return back()->with('status', 'Photo uploaded.');
    }

    /**
     * Remove a photo (to replace one: delete it, then upload the new photo).
     */
    public function destroy(Request $request, ProjectImage $image)
    {
        abort_unless($request->user()->hasPermission('images.upload'), 403);

        Storage::disk('public')->delete($image->path);
        $image->delete();

        return back()->with('status', 'Photo removed.');
    }

    /**
     * Files <= 1 MB are stored as-is. Larger files are downscaled to a max
     * dimension of 1600px and re-encoded as JPEG, stepping quality down
     * until the result is ~300 KB (or quality floor is reached).
     * Falls back to storing the original if GD is unavailable.
     */
    private function storeCompressed(UploadedFile $file, string $dir): string
    {
        if ($file->getSize() <= 1024 * 1024 || ! function_exists('imagecreatefromstring')) {
            return $file->store($dir, 'public');
        }

        try {
            $img = imagecreatefromstring(file_get_contents($file->getRealPath()));
            if ($img === false) {
                return $file->store($dir, 'public');
            }

            $w = imagesx($img);
            $h = imagesy($img);
            $maxDim = 1600;
            if (max($w, $h) > $maxDim) {
                $scale = $maxDim / max($w, $h);
                $resized = imagescale($img, (int) round($w * $scale), (int) round($h * $scale));
                if ($resized !== false) {
                    imagedestroy($img);
                    $img = $resized;
                }
            }

            $targetBytes = 300 * 1024;
            $quality = 85;
            do {
                ob_start();
                imagejpeg($img, null, $quality);
                $data = ob_get_clean();
                $quality -= 10;
            } while (strlen($data) > $targetBytes && $quality >= 30);

            imagedestroy($img);

            $path = $dir.'/'.uniqid('img_').'.jpg';
            Storage::disk('public')->put($path, $data);

            return $path;
        } catch (\Throwable) {
            return $file->store($dir, 'public');
        }
    }
}
