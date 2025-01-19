<?php

namespace Modules\Inventory\Notifications;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\ProductInventory;
use Modules\Inventory\Notifications\NotificationService;
use Stancl\Tenancy\Facades\Tenancy;
class CheckLowStockReminder extends Command
{
    protected $signature = 'inventory:ten-minute-reminder';
    protected $description = 'Send inventory reminders to admins every 10 minutes';

    public function handle()
    {
        $tenants = Tenant::all();
        
        foreach ($tenants as $tenant) {
            // Switch to the current tenant's database
            tenancy()->initialize($tenant);
            // $tenant = tenancy()->tenant;
            // $tenantId = $tenant->id;
            $productInventories = ProductInventory::with(['product', 'unit', 'modifier'])
                                    ->join(DB::raw('(SELECT product_id, SUM(qty) as total_qty FROM product_inventories GROUP BY product_id) as total'), 'inventory_product_inventories.product_id', '=', 'total.product_id')
                                    ->get();
            foreach ($productInventories as $productInventory) {
                $this->info("Reminder sent for {$productInventory->product}");
                if($productInventory->threshold > $productInventory->total_qty)
                    NotificationService::sendInventoryAlert($productInventory?->product, $productInventory->total_qty?? 0.0, $productInventory->threshold);
                    $this->info("Reminder sent for {$productInventory->product->name_ar}");
                }
            }
        }
    }