<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Assignment;
use App\Models\Course;
use App\Models\Image;
use App\Models\Lesson;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ImageController extends Controller
{
    private const IMAGEABLE_TYPES = [
        'course' => Course::class,
        'lesson' => Lesson::class,
        'assignment' => Assignment::class,
    ];

    public function show(string $type, int $id): JsonResponse
    {
        $imageable = $this->findImageable($type, $id);

        $this->authorize('view', $imageable);

        $image = $imageable->image;

        abort_if($image === null, 404, 'Image not found.');

        return response()->json([
            'data' => $this->imageData($image),
        ]);
    }

    public function store(
        Request $request,
        string $type,
        int $id
    ): JsonResponse {
        $imageable = $this->findImageable($type, $id);

        $this->authorize('update', $imageable);

        if ($imageable->image()->exists()) {
            throw ValidationException::withMessages([
                'image' => ['This content already has an image. Use PUT to replace it.'],
            ]);
        }

        $validated = $request->validate([
            'image' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ]);

        $path = $validated['image']->store(
            "images/{$type}",
            'public'
        );

        $image = $imageable->image()->create([
            'path' => $path,
        ]);

        return response()->json([
            'data' => $this->imageData($image),
        ], 201);
    }

    public function update(
        Request $request,
        string $type,
        int $id
    ): JsonResponse {
        $imageable = $this->findImageable($type, $id);

        $this->authorize('update', $imageable);

        $validated = $request->validate([
            'image' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ]);

        $newPath = $validated['image']->store(
            "images/{$type}",
            'public'
        );

        $image = $imageable->image;

        if ($image === null) {
            $image = $imageable->image()->create([
                'path' => $newPath,
            ]);
        } else {
            $oldPath = $image->path;

            $image->update([
                'path' => $newPath,
            ]);

            Storage::disk('public')->delete($oldPath);
        }

        return response()->json([
            'data' => $this->imageData($image->fresh()),
        ]);
    }

    public function destroy(string $type, int $id): JsonResponse
    {
        $imageable = $this->findImageable($type, $id);

        $this->authorize('update', $imageable);

        $image = $imageable->image;

        abort_if($image === null, 404, 'Image not found.');

        Storage::disk('public')->delete($image->path);

        $image->delete();

        return response()->json([
            'message' => 'Image deleted successfully.',
        ]);
    }

    private function findImageable(string $type, int $id): Model
    {
        abort_unless(
            isset(self::IMAGEABLE_TYPES[$type]),
            404,
            'Unsupported imageable type.'
        );

        $modelClass = self::IMAGEABLE_TYPES[$type];

        return $modelClass::query()->findOrFail($id);
    }

    private function imageData(Image $image): array
    {
        return [
            'id' => $image->id,
            'imageable_type' => $image->imageable_type,
            'imageable_id' => $image->imageable_id,
            'path' => $image->path,
            'url' => Storage::disk('public')->url($image->path),
            'created_at' => $image->created_at,
            'updated_at' => $image->updated_at,
        ];
    }
}
