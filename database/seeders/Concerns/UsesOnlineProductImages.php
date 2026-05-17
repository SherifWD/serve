<?php

namespace Database\Seeders\Concerns;

use App\Models\Product;

trait UsesOnlineProductImages
{
    /** @var array<string,string> */
    private array $productImageTags = [
        'Alexandria Mezze' => 'food,mezze',
        'Calamari Rings' => 'food,calamari',
        'Lentil Sea Soup' => 'food,soup',
        'Mediterranean Sea Bass' => 'food,fish',
        'Shrimp Sayadeya' => 'food,shrimp',
        'Seafood Pasta' => 'food,pasta',
        'Janova Kofta' => 'food,kofta',
        'Charcoal Chicken' => 'food,chicken',
        'Beef Tenderloin Plate' => 'food,beef',
        'Greek Coast Salad' => 'food,salad',
        'Chicken Fattoush' => 'food,salad,chicken',
        'Chocolate Fondant' => 'food,chocolate,dessert',
        'Milk Pudding' => 'food,pudding',
        'Orange Juice' => 'drink,orange,juice',
        'Janova Espresso' => 'coffee,espresso',
        'Flat White' => 'coffee,cup',
        'Spanish Latte' => 'coffee,latte',
        'Iced Latte' => 'coffee,iced',
        'English Breakfast' => 'tea,cup',
        'Green Tea' => 'tea,green',
        'Hibiscus Cooler' => 'drink,hibiscus',
        'Butter Croissant' => 'food,croissant',
        'Cinnamon Roll' => 'food,cinnamon',
        'Chocolate Muffin' => 'food,muffin',
        'Turkey Club' => 'sandwich,turkey',
        'Halloumi Panini' => 'sandwich,panini',
        'Tuna Melt' => 'sandwich,tuna',
        'San Sebastian Cheesecake' => 'food,cheesecake',
        'Tiramisu Cup' => 'food,tiramisu',
        'Lotus Cake Jar' => 'food,cake',
        'Halloumi Bites' => 'food,halloumi',
        'Lentil Soup' => 'food,soup',
        'Mezze Plate' => 'food,mezze',
        'Chicken Shawarma Plate' => 'chicken,shawarma',
        'Mushroom Pasta' => 'food,pasta',
        'Baked Kofta' => 'food,kofta',
        'Angus Burger' => 'food,burger',
        'Mixed Grill' => 'food,grill',
        'BBQ Chicken Skillet' => 'food,chicken',
        'Fresh Lemon Mint' => 'drink,lemonade,mint',
        'Sparkling Water' => 'drink,water',
        'Berry Mojito' => 'drink,mojito',
        'Om Ali' => 'food,pudding',
        'Brownie Skillet' => 'food,brownie',
        'Cheesecake Slice' => 'food,cheesecake',
    ];

    protected function productImageUrl(string $productName): string
    {
        $tags = $this->productImageTags[$productName] ?? $this->fallbackProductImageTags($productName);
        $lock = hexdec(substr(sha1($productName), 0, 6)) % 100000;

        return "https://loremflickr.com/900/650/{$tags}?lock={$lock}";
    }

    protected function syncSeededProductImages(): void
    {
        Product::query()
            ->whereIn('name', array_keys($this->productImageTags))
            ->get()
            ->each(function (Product $product): void {
                $product->forceFill([
                    'image' => $this->productImageUrl($product->name),
                ])->save();
            });
    }

    private function fallbackProductImageTags(string $productName): string
    {
        $slug = strtolower((string) preg_replace('/[^a-z0-9]+/i', ',', $productName));
        $slug = trim($slug, ',');

        return $slug !== '' ? "food,{$slug}" : 'food,restaurant';
    }
}
