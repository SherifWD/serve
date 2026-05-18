<?php

namespace Database\Seeders\Concerns;

use App\Models\Product;

trait UsesOnlineProductImages
{
    /** @var array<string,string> */
    private array $productImageFiles = [
        'Alexandria Mezze' => 'Turkish_meze_plate.jpg',
        'Mezze Plate' => 'Turkish_meze_plate.jpg',
        'Bruschetta Trio' => '2014_Bruschetta_The_Larder_Chiang_Mai.jpg',
        'Thai Chicken Satay' => 'Thai_chicken_satay.jpg',
        'Truffle Fries' => 'Truffle_Fries_(Unsplash).jpg',
        'Halloumi Bites' => 'Grilled_Halloumi.jpg',
        'Calamari Rings' => 'Fried_calamari_ring.jpg',
        'Lentil Sea Soup' => 'Red_lentil_soup.jpg',
        'Lentil Soup' => 'Red_lentil_soup.jpg',

        'Mediterranean Sea Bass' => 'GOC_Wheathampstead_Xmas_2013_061_Grilled_sea_bass_(11476849023).jpg',
        'Shrimp Sayadeya' => 'Shrimp_rice.jpg',
        'Seafood Pasta' => 'Seafood_pasta.jpg',
        'Seafood Linguine' => 'Seafood_pasta.jpg',
        'Mushroom Pasta' => 'Linguine.jpg',
        'Truffle Mushroom Risotto' => 'Risotto_with_speck_and_goat_cheese_(6101067436).jpg',
        'Penne Arrabbiata' => 'Penne_Arrabbiata.jpg',

        'Janova Kofta' => 'Kuskuso_kofta.jpg',
        'Baked Kofta' => 'Kuskuso_kofta.jpg',
        'Charcoal Chicken' => 'Korean_Spicy_Charcoal-grilled_Chicken.jpg',
        'Korean BBQ Chicken' => 'Korean_Spicy_Charcoal-grilled_Chicken.jpg',
        'BBQ Chicken Skillet' => 'BBQ_Chicken.jpg',
        'Chicken Shawarma Plate' => 'Chicken_Shwarama_plate.jpeg',
        'Butter Chicken Bowl' => 'Chicken_makhani.jpg',
        'Angus Burger' => 'RedDot_Burger.jpg',
        'Mixed Grill' => 'Chinese_Style_Mixed_Grill-1.jpg',
        'Beef Tenderloin Plate' => 'Beef_tenderloin_-_Guillaume_at_Bennelong.jpg',

        'Greek Coast Salad' => 'Greece_Food_Horiatiki.JPG',
        'Caesar Chicken Salad' => 'Caesar_salad_(2).jpg',
        'Quinoa Avocado Bowl' => 'Avocado_Bowl_Green.jpg',
        'Chicken Fattoush' => 'Fattoush.JPG',

        'Chocolate Fondant' => 'Chocolate_Fondant.jpg',
        'Milk Pudding' => 'Milk_pudding_from_Yee_Shun_Milk_Company,_Hong_Kong_-_20111215.jpg',
        'San Sebastian Cheesecake' => 'San_Sebastian_Cheesecake.jpg',
        'Tiramisu Cup' => 'Tiramisu_-_Raffaele_Diomede.jpg',
        'Lotus Cake Jar' => 'Lotus_biskof_cheesecake.jpg',
        'Om Ali' => 'Om_Ali_Dessert.jpg',
        'Brownie Skillet' => 'Chocolate_brownie_2.jpg',
        'Cheesecake Slice' => 'Plain_cheesecake_slice.jpg',

        'Fresh Lemon Mint' => 'Cold_water_with_lemon_and_mint.jpg',
        'Orange Juice' => 'Orange_juice.jpg',
        'Janova Espresso' => 'Tazzina_di_caffè_a_Ventimiglia.jpg',
        'Matcha Latte' => 'Matcha_Tea_Latte_(6293795173).jpg',
        'Sparkling Water' => 'Glass_sparkling_lemonade.jpg',
        'Berry Mojito' => 'Mojito_made_with_rum,_lime,_sugar,_mint,_club_soda,_served_in_a_tall_glass_-_Evan_Swigart.jpg',

        'Flat White' => 'Charlecote_Park_flat_white_coffee_Warwickshire_England.jpg',
        'Spanish Latte' => 'Latte-heart.jpg',
        'Iced Latte' => 'Iced_coffee_.jpg',
        'English Breakfast' => 'Mug_of_English_breakfast_tea.jpg',
        'Green Tea' => 'Small_cup_of_green_tea.jpg',
        'Hibiscus Cooler' => 'Hibiscus_High_Tea_from_Mountain_Rose_Herbs.jpg',
        'Butter Croissant' => 'Croissant.jpg',
        'Cinnamon Roll' => 'Cinnamon_roll_in_Stockholm.jpg',
        'Chocolate Muffin' => 'Chocolate_muffin_with_chocolate_chips.JPG',
        'Turkey Club' => 'Club_sandwich.png',
        'Halloumi Panini' => 'Panini_sandwich_in_a_restaurant.JPG',
        'Tuna Melt' => 'Tuna_melt_with_cheddar,_mustard_or_guacamole,_and_black_pepper_-_Massachusets.jpg',
    ];

    protected function productImageUrl(string $productName): string
    {
        $file = $this->productImageFiles[$productName] ?? $this->fallbackProductImageFile();

        return 'https://commons.wikimedia.org/wiki/Special:FilePath/'.rawurlencode($file).'?width=900';
    }

    protected function syncSeededProductImages(): void
    {
        Product::query()
            ->whereIn('name', array_keys($this->productImageFiles))
            ->get()
            ->each(function (Product $product): void {
                $product->forceFill([
                    'image' => $this->productImageUrl($product->name),
                ])->save();
            });
    }

    private function fallbackProductImageFile(): string
    {
        return '2014_Bruschetta_The_Larder_Chiang_Mai.jpg';
    }
}
