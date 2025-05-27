<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Models\ProductImage;

class MoveImagesToPrivate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:move-to-private';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Move product images from public storage to private storage';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to move images from public to private storage...');
        
        // Create private directory if it doesn't exist
        if (!Storage::exists('private/products')) {
            Storage::makeDirectory('private/products');
            $this->info('Created private/products directory');
        }
        
        // Get all product images
        $images = ProductImage::all();
        $count = 0;
        
        foreach ($images as $image) {
            $filename = $image->image_url;
            
            // Check if file exists in public storage
            if (Storage::exists("products/{$filename}")) {
                // Copy to private storage
                Storage::copy("products/{$filename}", "private/products/{$filename}");
                $count++;
                
                $this->line("Moved: {$filename}");
            } else {
                $this->warn("File not found in public storage: {$filename}");
            }
        }
        
        $this->info("Successfully moved {$count} out of {$images->count()} images to private storage");
        $this->info('You can now safely delete the public images if everything works correctly');
    }
} 