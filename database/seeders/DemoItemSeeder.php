<?php

namespace Database\Seeders;

use App\Enums\ItemCondition;
use App\Enums\ItemStatus;
use App\Models\Collection;
use App\Models\Item;
use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Models\ItemImage;

class DemoItemSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAlbertoVault();
        $this->seedArtVault();
        $this->seedGamingVault();
    }

    private function seedAlbertoVault(): void
    {
        $collection = Collection::where('name', 'Designer Figures')->firstOrFail();

        $item = Item::create([
            'workspace_id' => $collection->workspace_id,
            'collection_id' => $collection->id,
            'name' => 'Tintin Moon Rocket Figure',
            'slug' => Str::slug('Tintin Moon Rocket Figure'),
            'description' => 'Detailed collectible figure from Tintin.',
            'status' => ItemStatus::STORED,
            'condition' => ItemCondition::GOOD,
            'purchase_price' => 195,
            'estimated_value' => 330,
            'location' => 'Storage box A3',
            'notes' => null,
        ]);

        $this->attachTags($item, ['Display Piece']);
        $this->attachImage($item, 'moonrocket.jpg');


        $item = Item::create([
            'workspace_id' => $collection->workspace_id,
            'collection_id' => $collection->id,
            'name' => 'KAWS Companion 2020 Figure',
            'slug' => Str::slug('KAWS Companion 2020 Figure'),
            'description' => 'High-quality KAWS Companion 2020 figure in perfect display condition.',
            'status' => ItemStatus::ACTIVE,
            'condition' => ItemCondition::MINT,
            'purchase_price' => 220,
            'estimated_value' => 980,
            'location' => 'Display cabinet',
            'notes' => 'Original packaging included.',
        ]);

        $this->attachTags($item, ['Rare']);
        $this->attachImage($item, 'kawscompanion.jpg');
        $this->attachImage($item, 'kawscompanion-2.jpg');
        $this->attachImage($item, 'kawscompanion-3.jpg');
    }

    private function seedArtVault(): void
    {
        $collection = Collection::where('name', 'Contemporary Paintings')->firstOrFail();

        $item = Item::create([
            'workspace_id' => $collection->workspace_id,
            'collection_id' => $collection->id,
            'name' => 'The Wreck of the Zephyr',
            'slug' => Str::slug('The Wreck of the Zephyr'),
            'description' => 'Chris Van Allsburg modern abstract composition using acrylic on canvas.',
            'status' => ItemStatus::ACTIVE,
            'condition' => ItemCondition::EXCELLENT,
            'purchase_price' => 6800,
            'estimated_value' => 11200,
            'location' => 'Living room wall',
            'notes' => 'Signed by artist.',
        ]);

        $this->attachTags($item, ['Acrylic', 'Modern', 'Signed']);
        $this->attachImage($item, 'wreckzephyr.jpg');

        $collection = Collection::where('name', 'Sculptures')->firstOrFail();

        $item = Item::create([
            'workspace_id' => $collection->workspace_id,
            'collection_id' => $collection->id,
            'name' => 'Marble Sculpture',
            'slug' => Str::slug('Marble Sculpture'),
            'description' => 'Handcrafted marble sculpture with classic influence.',
            'status' => ItemStatus::ACTIVE,
            'condition' => ItemCondition::GOOD,
            'purchase_price' => 1500,
            'estimated_value' => 2000,
            'location' => 'Studio',
            'notes' => null,
        ]);

        $this->attachTags($item, ['Marble', 'Classic']);
        $this->attachImage($item, 'llimonamarble.jpg');
    }

    private function seedGamingVault(): void
    {
        $collection = Collection::where('name', 'Retro Consoles')->firstOrFail();

        $item = Item::create([
            'workspace_id' => $collection->workspace_id,
            'collection_id' => $collection->id,
            'name' => 'Nintendo 64 Console',
            'slug' => Str::slug('Nintendo 64 Console'),
            'description' => 'Classic Nintendo 64 with original controller.',
            'status' => ItemStatus::ACTIVE,
            'condition' => ItemCondition::GRADED,
            'purchase_price' => 1100,
            'estimated_value' => 2500,
            'location' => 'Gaming shelf',
            'notes' => 'Graded and new unused.',
        ]);

        $this->attachTags($item, ['Nintendo', 'Retro']);
        $this->attachImage($item, 'nintendo64purple.jpg');

        $collection = Collection::where('name', 'Physical Games')->firstOrFail();

        $item = Item::create([
            'workspace_id' => $collection->workspace_id,
            'collection_id' => $collection->id,
            'name' => 'Doom II PC',
            'slug' => Str::slug('Doom II PC'),
            'description' => 'Legendary PC game with original box.',
            'status' => ItemStatus::ACTIVE,
            'condition' => ItemCondition::EXCELLENT,
            'purchase_price' => 160,
            'estimated_value' => 520,
            'location' => 'Game collection drawer',
            'notes' => null,
        ]);

        $this->attachTags($item, ['Nintendo', 'Boxed', 'Retro']);
        $this->attachImage($item, 'goku.jpg');
    }

    private function attachTags(Item $item, array $tagNames): void
    {
        $tags = Tag::whereIn('name', $tagNames)
            ->where('workspace_id', $item->workspace_id)
            ->pluck('id');

        $item->tags()->sync($tags);
    }

    private function attachImage(Item $item, string $fileName): void
    {
        $sourcePath = database_path("seeders/demo-images/{$fileName}");

        if (! file_exists($sourcePath)) {
            return;
        }

        $storagePath = "workspaces/{$item->workspace_id}/items/{$item->id}/{$fileName}";

        Storage::disk('public')->put(
            $storagePath,
            file_get_contents($sourcePath)
        );

        ItemImage::create([
            'item_id' => $item->id,
            'path' => $storagePath,
            'position' => 1,
            'alt_text' => $item->name,
        ]);
    }
}
