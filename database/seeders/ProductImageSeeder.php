<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ProductImageSeeder extends Seeder
{
    private const PALETTES = [
        ['#e0f2fe', '#075985'],
        ['#fef3c7', '#92400e'],
        ['#fce7f3', '#9d174d'],
        ['#dcfce7', '#166534'],
        ['#ede9fe', '#5b21b6'],
    ];

    public function run(): void
    {
        foreach (DemoCatalog::products() as $index => $productData) {
            $product = Product::where('name', $productData['name'])->first();

            if (! $product) {
                throw new RuntimeException(
                    "Run ProductSeeder before ProductImageSeeder ({$productData['name']})."
                );
            }

            $filename = 'demo-product-'.$product->id.'.svg';
            [$background, $accent] = self::PALETTES[$index % count(self::PALETTES)];
            $label = htmlspecialchars(
                Str::limit($product->name, 38, ''),
                ENT_QUOTES | ENT_XML1,
                'UTF-8'
            );
            $illustration = match ($productData['category']) {
                'Shoes' => <<<SVG
                    <path d="M366 390c46-9 72-70 105-126l61 29c20 51 65 83 127 96 18 4 29 17 29 34v24H360v-25c0-15 2-27 6-32z" fill="{$accent}"/>
                    <path d="M391 414h296" stroke="#ffffff" stroke-width="12" stroke-linecap="round" opacity=".8"/>
                    SVG,
                'Accessories' => str_contains($product->name, 'Watch')
                    ? <<<SVG
                        <rect x="430" y="188" width="100" height="330" rx="40" fill="{$accent}" opacity=".88"/>
                        <circle cx="480" cy="354" r="96" fill="#ffffff" stroke="{$accent}" stroke-width="20"/>
                        <circle cx="480" cy="354" r="7" fill="{$accent}"/>
                        <path d="M480 354v-54m0 54 42 24" stroke="{$accent}" stroke-width="10" stroke-linecap="round"/>
                        SVG
                    : <<<SVG
                        <rect x="303" y="315" width="354" height="88" rx="20" fill="{$accent}"/>
                        <rect x="446" y="291" width="106" height="136" rx="18" fill="none" stroke="#d6b25e" stroke-width="18"/>
                        <path d="M498 310v96" stroke="#d6b25e" stroke-width="12"/>
                        SVG,
                default => <<<SVG
                    <path d="M407 243h146l58 62-47 44-29-27v112H425V322l-29 27-47-44z" fill="{$accent}" opacity=".86"/>
                    <path d="M447 244c2-34 64-34 66 0" fill="none" stroke="#ffffff" stroke-width="14" opacity=".8"/>
                    SVG,
            };
            $svg = <<<SVG
                <svg xmlns="http://www.w3.org/2000/svg" width="960" height="720" viewBox="0 0 960 720">
                  <defs>
                    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
                      <stop offset="0" stop-color="{$background}"/>
                      <stop offset="1" stop-color="#ffffff"/>
                    </linearGradient>
                  </defs>
                  <rect width="960" height="720" fill="url(#bg)"/>
                  <circle cx="740" cy="170" r="155" fill="{$accent}" opacity=".08"/>
                  <circle cx="230" cy="570" r="190" fill="{$accent}" opacity=".06"/>
                  <rect x="312" y="155" width="336" height="336" rx="42" fill="#ffffff" opacity=".76"/>
                  {$illustration}
                  <text x="480" y="560" text-anchor="middle" fill="{$accent}" font-family="Arial, sans-serif" font-size="30" font-weight="700">3Z SHOP</text>
                  <text x="480" y="607" text-anchor="middle" fill="#334155" font-family="Arial, sans-serif" font-size="24">{$label}</text>
                </svg>
                SVG;

            if (! Storage::put("products/{$filename}", $svg)) {
                throw new RuntimeException("Unable to write demo image: {$filename}.");
            }

            ProductImage::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'image_url' => $filename,
                ]
            );
        }
    }
}
