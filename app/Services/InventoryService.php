<?php

namespace App\Services;

use App\Models\AdjustmentEntry;
use App\Models\Product;
use App\Models\ProductInventoryEntry;
use App\Models\ProductInventoryHolder;
use App\Models\ProductInventoryOutflow;
use App\Models\ProductInventoryPurchase;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SalesReturn;
use App\Models\StockTransfer;

class InventoryService
{
  public function getProfitMargin($costPrice, $salePrice)
  {
    return (($salePrice - $costPrice) / $salePrice) * 100;
  }

  public function getPurchaseDetailArray(
    $productId,
    $price,
    $quantity,
    $amount,
    $quantityBoxes,
    $unitsInBox,
    $quantityStrips,
    $unitsInStrip,
    $quantityUnits,
    $salePrice,
    $expiryDate,
    float $totalSalePrice,
    int $uomOfBoxes,
    float $boxSalePrice
  ) {

    return [
      'product_id' => $productId,
      'price' => $price,
      'quantity' => $quantity,
      'amount' => $amount,
      'quantity_boxes' => $quantityBoxes,
      'units_in_box' => $unitsInBox,
      'quantity_strips' => $quantityStrips,
      'units_in_strip' => $unitsInStrip,
      'quantity_units' => $quantityUnits,
      'sale_price' => $salePrice,
      'profit_margin' => (($salePrice - $price) / $salePrice) * 100,
      'expiry_date' => $expiryDate,
      'total_sale_price' => $totalSalePrice,
      'uom_of_boxes' => $uomOfBoxes,
      'box_sale_price' => $boxSalePrice
    ];
  }

  // , $saleId, $saleDate, $productId, $price, $requiredQuantity, $purchaseAmount
  public function moveInventory(int $referenceId, $date, int $fromLocationId, int $toLocationId, int $productId, float $purchasedPrice, int $transferredQuantity)
  {
    /* referenct type transfer should be added */
    // Get last purchase inventory entry where invetory available for particular product
    $lastPurchaseInventoryEntry = ProductInventoryEntry::where('reference_type', ProductInventoryOutflow::class)
      ->where('available_quantity', '>', 0)
      ->where('product_id', $productId)
      ->where('location_id', $fromLocationId)
      ->orderBy('date')
      ->first();

    if ($transferredQuantity > $lastPurchaseInventoryEntry->available_quantity) {
      $quantityForTransfer = $lastPurchaseInventoryEntry->available_quantity;

      $this->storeInventoryEntryOnPurchase(
        $date,
        $productId,
        $referenceId,
        StockTransfer::class,
        $purchasedPrice,
        $quantityForTransfer,
        $lastPurchaseInventoryEntry->expiry_date
      );

      $entryAmount = $purchasedPrice * $quantityForTransfer;
      // $soldAmount = $lastPurchaseInventoryEntry->sold_amount + $entryAmount;
      // $purchaseAmount += $lastPurchaseInventoryEntry->purchased_price * $soldQuantity;

      $lastPurchaseInventoryEntry->available_quantity = 0;
      $lastPurchaseInventoryEntry->transferred_quantity += $quantityForTransfer;
      $lastPurchaseInventoryEntry->save();

      // $this->updatePurchaseInventoryEntryOnSale($lastEntry, $lastEntry->quantity);      

      return $this->moveInventory(
        referenceId: $referenceId,
        date: $date,
        fromLocationId: $fromLocationId,
        toLocationId: $toLocationId,
        productId: $productId,
        purchasedPrice: $purchasedPrice,
        transferredQuantity: $transferredQuantity - $quantityForTransfer
      );
    }

    $this->storeInventoryEntryOnPurchase(
      $date,
      $productId,
      $referenceId,
      StockTransfer::class,
      $purchasedPrice,
      $transferredQuantity,
      $lastPurchaseInventoryEntry->expiry_date
    );

    $lastPurchaseInventoryEntry->available_quantity -= $transferredQuantity;
    $lastPurchaseInventoryEntry->transferred_quantity += $transferredQuantity;
    $lastPurchaseInventoryEntry->save();

    return;
  }

  public function holdInventoryOnSale($saleId, $saleDate, $productId, $price, $requiredQuantity, $purchaseAmount)
  {
    $locationId = 1; //Will get from session    

    // Get last purchase inventory entry where invetory available for particular product
    $lastPurchaseInventoryEntry = ProductInventoryEntry::where('reference_type', ProductInventoryOutflow::class)
      ->where('available_quantity', '>', 0)
      ->where('product_id', $productId)
      ->where('location_id', $locationId)
      ->orderBy('date')
      ->first();

    if ($requiredQuantity > $lastPurchaseInventoryEntry->available_quantity) {
      $soldQuantity = $lastPurchaseInventoryEntry->available_quantity;
      $purchaseInventoryEntrySoldQuantity = $lastPurchaseInventoryEntry->sold_quantity + $soldQuantity;

      $this->storeInventoryHolderEntryOnSale($saleId, $productId, $price, $soldQuantity, $lastPurchaseInventoryEntry->id);

      $entryAmount = $price * $soldQuantity;
      $soldAmount = $lastPurchaseInventoryEntry->sold_amount + $entryAmount;
      $purchaseAmount += $lastPurchaseInventoryEntry->purchased_price * $soldQuantity;

      $lastPurchaseInventoryEntry->update([
        'available_quantity' => 0,
        'sold_quantity' => $purchaseInventoryEntrySoldQuantity,
        'sold_amount' => $soldAmount,
      ]);
      // $this->updatePurchaseInventoryEntryOnSale($lastEntry, $lastEntry->quantity);      

      return $this->holdInventoryOnSale($saleId, $saleDate, $productId, $price, $requiredQuantity - $soldQuantity, $purchaseAmount);
    }

    $this->storeInventoryHolderEntryOnSale($saleId, $productId, $price, $requiredQuantity, $lastPurchaseInventoryEntry->id);
    // $this->storeInventoryEntryOnSale($saleDate, $productId, $saleId, Sale::class, null, null, $requiredQuantity, $lastPurchaseInventoryEntry->id);

    $purchaseInventoryEntrySoldQuantity = $lastPurchaseInventoryEntry->sold_quantity + $requiredQuantity;
    $entryAmount = $price * $requiredQuantity;
    $soldAmount = $lastPurchaseInventoryEntry->sold_amount + $entryAmount;
    $purchaseAmount += $lastPurchaseInventoryEntry->purchased_price * $requiredQuantity;

    $lastPurchaseInventoryEntry->update([
      'available_quantity' => $lastPurchaseInventoryEntry->available_quantity - $requiredQuantity,
      'sold_quantity' => $purchaseInventoryEntrySoldQuantity,
      'sold_amount' => $soldAmount,
    ]);

    return $purchaseAmount;
  }

  public function reverseInventoryFromHolder($locationId, $saleId, $productId)
  {
    $inventoryHolders = ProductInventoryHolder::where('location_id', $locationId)
      ->where('sale_id', $saleId)
      ->where('product_id', $productId)
      ->get();
    foreach ($inventoryHolders as $inventoryHolder) {
      $productInventoryEntry = ProductInventoryEntry::find($inventoryHolder->product_inventory_entry_purchase_id);

      $productInventoryEntry->available_quantity = $productInventoryEntry->available_quantity + $inventoryHolder->sold_quantity;
      $productInventoryEntry->sold_quantity = $productInventoryEntry->sold_quantity - $inventoryHolder->sold_quantity;
      $soldAmount = $inventoryHolder->price * $inventoryHolder->sold_quantity;
      // $productInventoryEntry->sold_amount = $productInventoryEntry->sold_amount - $soldAmount;
      $productInventoryEntry->save();

      $inventoryHolder->forceDelete();
    }
    return;
  }

  public function reverseInventoryOnSalesReturn(int $locationId, int $salesReturnId, int $productId, int $salesReturnDetailQuantity)
  {
    // Get sales return entries from inventory
    $salesReturnInventoryEntries = ProductInventoryEntry::where('reference_type', SalesReturn::class)
      ->where('reference_id', $salesReturnId)
      ->where('product_id', $productId)
      ->where('location_id', $locationId)
      ->orderBy('date', 'desc')
      ->orderBy('id', 'desc')
      ->get();

    foreach ($salesReturnInventoryEntries as $i => $salesReturnInventoryEntry) {
      $saleInventoryEntry = ProductInventoryEntry::find($salesReturnInventoryEntry->product_inventory_entry_purchase_id);

      $purchaseInventoryEntry = ProductInventoryEntry::find($saleInventoryEntry->product_inventory_entry_purchase_id);
      $purchaseInventoryEntry->available_quantity = $purchaseInventoryEntry->available_quantity - $salesReturnInventoryEntry->returned_quantity;
      $purchaseInventoryEntry->returned_quantity = $purchaseInventoryEntry->returned_quantity - $salesReturnInventoryEntry->returned_quantity;
      $purchaseInventoryEntry->save();

      $salesReturnInventoryEntry->returned_quantity = 0;
      $salesReturnInventoryEntry->save();
    }

    return;
  }

  public function storeInventoryEntryOnAdjustment($date, $productId, $referenceType, $referenceId, $adjustedQuantity, $lastPurchaseInventoryEntryId)
  {
    $locationId = 1; //Will get from session
    return ProductInventoryEntry::create([
      'date' => $date,
      'product_id' => $productId,
      'location_id' => $locationId,
      'reference_id' => $referenceId,
      'reference_type' => $referenceType,
      'adjusted_quantity' => $adjustedQuantity,
      'product_inventory_entry_purchase_id' => $lastPurchaseInventoryEntryId
    ]);
  }

  public function storeInventoryEntryOnPurchase($date, $productId, $referenceId, $referenceType, $purchasedPrice, $initialQuantity, $expiryDate)
  {
    $locationId = 1; //Will get from session
    $productInventoryEntry = ProductInventoryEntry::create([
      'date' => $date,
      'product_id' => $productId,
      'location_id' => $locationId,
      'reference_id' => $referenceId,
      'reference_type' => $referenceType,
      'purchased_price' => $purchasedPrice,
      'initial_quantity' => $initialQuantity,
      'available_quantity' => $initialQuantity,
      'sold_quantity' => 0,
      'transferred_quantity' => 0,
      'adjusted_quantity' => 0,
      'expiry_date' => $expiryDate,
      'purchased_amount' => $initialQuantity * $purchasedPrice,
      'sold_amount' => 0,
    ]);
    return $productInventoryEntry;
  }

  public function storeInventoryEntryOnSale($date, $productId, $referenceId, $referenceType, $initialQuantity, $availableQuantity, $soldQuantity, $purchaseId)
  {
    $locationId = 1; //Will get from session
    return ProductInventoryEntry::create([
      'date' => $date,
      'product_id' => $productId,
      'location_id' => $locationId,
      'reference_id' => $referenceId,
      'reference_type' => $referenceType,
      'initial_quantity' => $initialQuantity,
      'available_quantity' => $availableQuantity,
      'sold_quantity' => $soldQuantity,
      'product_inventory_entry_purchase_id' => $purchaseId
    ]);
  }

  public function storeInventoryEntryOnSalesReturn($date, $productId, $referenceId, $referenceType, $returnedQuantity, $saleId)
  {
    $locationId = 1; //Will get from session
    return ProductInventoryEntry::create([
      'date' => $date,
      'product_id' => $productId,
      'location_id' => $locationId,
      'reference_id' => $referenceId,
      'reference_type' => $referenceType,
      'returned_quantity' => $returnedQuantity,
      'product_inventory_entry_purchase_id' => $saleId
    ]);
  }

  public function storeInventoryHolderEntryOnSale($saleId, $productId, $price, $soldQuantity, $purchaseEntryId)
  {
    $locationId = 1; //Will get from session
    return ProductInventoryHolder::create([
      'product_id' => $productId,
      'location_id' => $locationId,
      'sale_id' => $saleId,
      'price' => $price,
      'sold_quantity' => $soldQuantity,
      'product_inventory_entry_purchase_id' => $purchaseEntryId
    ]);
  }

  public function storeInventoryPurchase($productId, $referenceId, $purchasedPrice, $purchasedQuantity, $expiryDate)
  {
    return ProductInventoryPurchase::create([
      'product_id' => $productId,
      'reference_type' => Purchase::class,
      'reference_id' => $referenceId,
      'purchased_price' => $purchasedPrice,
      'purchased_quantity' => $purchasedQuantity,
      'available_quantity' => $purchasedQuantity,
      'expiry_date' => $expiryDate,
    ]);
  }

  public function updateInventoryOnPurchaseReturn($locationId, $purchaseId, $productId, $purchaseReturnedQuantity, $purchaseReturnedAmount)
  {
    $productInventoryPurchase = ProductInventoryPurchase::where('reference_type', Purchase::class)
      ->where('reference_id', $purchaseId)
      // ->where('location_id', $locationId)
      ->where('product_id', $productId)
      ->first();
    $productInventoryOutflowId = $productInventoryPurchase->productInventoryOutflowDetail->product_inventory_outflow_id;

    $productInventoryEntry = ProductInventoryEntry::where('reference_type', ProductInventoryOutflow::class)
      ->where('reference_id', $productInventoryOutflowId)
      ->where('product_id', $productId)
      ->where('location_id', $locationId)
      ->first();

    $productInventoryEntry->available_quantity -= $purchaseReturnedQuantity;
    $productInventoryEntry->purchased_amount -= $purchaseReturnedAmount;
    $productInventoryEntry->purchase_returned_quantity += $purchaseReturnedQuantity;
    $productInventoryEntry->save();

    return;
  }

  public function updateInventoryFromHolder($locationId, $saleId, $saleDate, $productId)
  {
    $inventoryHolders = ProductInventoryHolder::where('location_id', $locationId)
      ->where('sale_id', $saleId)
      ->where('product_id', $productId)
      ->get();
    foreach ($inventoryHolders as $inventoryHolder) {
      $this->storeInventoryEntryOnSale($saleDate, $productId, $saleId, Sale::class, null, null, $inventoryHolder->sold_quantity, $inventoryHolder->product_inventory_entry_purchase_id);
      $inventoryHolder->forceDelete();
    }
    return;
  }

  public function updateInventoryOnSale($saleId, $saleDate, $productId, $price, $requiredQuantity, $purchaseAmount)
  {
    $locationId = 1; //Will get from session

    // Get last purchase inventory entry where invetory available for particular product
    $lastPurchaseInventoryEntry = ProductInventoryEntry::where('reference_type', ProductInventoryOutflow::class)
      ->where('available_quantity', '>', 0)
      ->where('product_id', $productId)
      ->where('location_id', $locationId)
      ->orderBy('date')
      ->first();

    if ($requiredQuantity > $lastPurchaseInventoryEntry->available_quantity) {
      $soldQuantity = $lastPurchaseInventoryEntry->available_quantity;
      $purchaseInventoryEntrySoldQuantity = $lastPurchaseInventoryEntry->sold_quantity + $soldQuantity;
      $inventoryEntryAmount = $price * $soldQuantity;
      $soldAmount = $lastPurchaseInventoryEntry->sold_amount + $inventoryEntryAmount;
      $purchaseAmount += $lastPurchaseInventoryEntry->purchased_price * $soldQuantity;

      $this->storeInventoryEntryOnSale($saleDate, $productId, $saleId, Sale::class, null, null, $soldQuantity, $lastPurchaseInventoryEntry->id);

      $lastPurchaseInventoryEntry->update([
        'available_quantity' => 0,
        'sold_quantity' => $purchaseInventoryEntrySoldQuantity,
        'sold_amount' => $soldAmount,
      ]);
      // $this->updatePurchaseInventoryEntryOnSale($lastEntry, $lastEntry->quantity);

      return $this->updateInventoryOnSale($saleId, $saleDate, $productId, $price, $requiredQuantity - $soldQuantity, $purchaseAmount);
    }


    $this->storeInventoryEntryOnSale($saleDate, $productId, $saleId, Sale::class, null, null, $requiredQuantity, $lastPurchaseInventoryEntry->id);

    $purchaseInventoryEntrySoldQuantity = $lastPurchaseInventoryEntry->sold_quantity + $requiredQuantity;
    $inventoryEntryAmount = $price * $requiredQuantity;
    $soldAmount = $lastPurchaseInventoryEntry->sold_amount + $inventoryEntryAmount;
    $purchaseAmount += $lastPurchaseInventoryEntry->purchased_price * $requiredQuantity;

    $lastPurchaseInventoryEntry->update([
      'available_quantity' => $lastPurchaseInventoryEntry->available_quantity - $requiredQuantity,
      'sold_quantity' => $purchaseInventoryEntrySoldQuantity,
      'sold_amount' => $soldAmount,
    ]);

    return $purchaseAmount;
  }

  public function updateInventoryOnSalesReturn($saleId, $salesReturnId, $salesReturnDate, $productId, $salePrice, $returnedQuantity)
  {
    $remainingReturnedQuantity = $returnedQuantity;

    $locationId = 1; //Will get from session

    // Get sale entry from inventory
    $saleInventoryEntries = ProductInventoryEntry::where('reference_type', Sale::class)
      ->where('reference_id', $saleId)
      ->where('product_id', $productId)
      ->where('location_id', $locationId)
      ->orderBy('date', 'desc')
      ->orderBy('id', 'desc')
      ->get();

    foreach ($saleInventoryEntries as $i => $saleInventoryEntry) {
      // dd($saleInventoryEntry, ProductInventoryOutflow::class, $saleInventoryEntry->product_inventory_entry_purchase_id, $productId, $locationId);
      $purchaseInventoryEntry = ProductInventoryEntry::where('reference_type', ProductInventoryOutflow::class)
        ->where('id', $saleInventoryEntry->product_inventory_entry_purchase_id)
        ->where('product_id', $productId)
        ->where('location_id', $locationId)
        ->first();
      // dd($purchaseInventoryEntry);

      if ($remainingReturnedQuantity > $saleInventoryEntry->sold_quantity) {
        $soldAmount = $salePrice * $saleInventoryEntry->sold_quantity;
        $entryReturnedQuantity = $saleInventoryEntry->sold_quantity;
        $purchaseInventoryEntry->available_quantity = $purchaseInventoryEntry->available_quantity + $entryReturnedQuantity;
        $purchaseInventoryEntry->returned_quantity = $purchaseInventoryEntry->returned_quantity + $entryReturnedQuantity;
        // $purchaseInventoryEntry->sold_amount -= $soldAmount;
        $purchaseInventoryEntry->save();

        $this->storeInventoryEntryOnSalesReturn($salesReturnDate, $productId, $salesReturnId, SalesReturn::class, $entryReturnedQuantity, $saleInventoryEntry->id);

        $remainingReturnedQuantity = $remainingReturnedQuantity - $entryReturnedQuantity;
      } else if ($remainingReturnedQuantity != 0) {
        $soldAmount = $salePrice * $remainingReturnedQuantity;
        $purchaseInventoryEntry->available_quantity = $purchaseInventoryEntry->available_quantity + $remainingReturnedQuantity;
        $purchaseInventoryEntry->returned_quantity = $purchaseInventoryEntry->returned_quantity + $remainingReturnedQuantity;
        // $purchaseInventoryEntry->sold_amount -= $soldAmount;
        $purchaseInventoryEntry->save();
        // $purchaseInventoryEntry->save();
        $this->storeInventoryEntryOnSalesReturn($salesReturnDate, $productId, $salesReturnId, SalesReturn::class, $remainingReturnedQuantity, $saleInventoryEntry->id);

        $remainingReturnedQuantity = $remainingReturnedQuantity - $remainingReturnedQuantity;
      }
    }

    return;
  }

  public function updateInventoryOnSalesReturnUpdate(int $locationId, int $saleId, int $salesReturnId, $salesReturnDate, int $productId, float $salePrice, int $returnedQuantity)
  {
    $remainingReturnedQuantity = $returnedQuantity;

    // Get sale entry from inventory
    $saleInventoryEntries = ProductInventoryEntry::where('reference_type', Sale::class)
      ->where('reference_id', $saleId)
      ->where('product_id', $productId)
      ->where('location_id', $locationId)
      ->orderBy('date', 'desc')
      ->orderBy('id', 'desc')
      ->get();

    foreach ($saleInventoryEntries as $i => $saleInventoryEntry) {
      // dd($saleInventoryEntry, ProductInventoryOutflow::class, $saleInventoryEntry->product_inventory_entry_purchase_id, $productId, $locationId);
      $purchaseInventoryEntry = ProductInventoryEntry::find($saleInventoryEntry->product_inventory_entry_purchase_id);
      // dd($purchaseInventoryEntry);

      if ($remainingReturnedQuantity > $saleInventoryEntry->sold_quantity) {
        // $soldAmount = $salePrice * $saleInventoryEntry->sold_quantity;
        $entryReturnedQuantity = $saleInventoryEntry->sold_quantity;

        $purchaseInventoryEntry->available_quantity = $purchaseInventoryEntry->available_quantity + $entryReturnedQuantity;
        $purchaseInventoryEntry->returned_quantity = $purchaseInventoryEntry->returned_quantity + $entryReturnedQuantity;
        // $purchaseInventoryEntry->sold_amount -= $soldAmount;
        $purchaseInventoryEntry->save();

        $salesReturnInventoryEntry = ProductInventoryEntry::query()
          ->where('reference_type', SalesReturn::class)
          ->where('reference_id', $salesReturnId)
          ->where('product_id', $productId)
          ->where('location_id', $locationId)
          ->where('product_inventory_entry_purchase_id', $saleInventoryEntry->id)
          ->first();

        if($salesReturnInventoryEntry) {
          $salesReturnInventoryEntry->returned_quantity = $entryReturnedQuantity;
          $salesReturnInventoryEntry->save();
        } else {
          $this->storeInventoryEntryOnSalesReturn($salesReturnDate, $productId, $salesReturnId, SalesReturn::class, $entryReturnedQuantity, $saleInventoryEntry->id);
        }        

        $remainingReturnedQuantity = $remainingReturnedQuantity - $entryReturnedQuantity;
      } else if ($remainingReturnedQuantity != 0) {
        // $soldAmount = $salePrice * $remainingReturnedQuantity;

        $purchaseInventoryEntry->available_quantity = $purchaseInventoryEntry->available_quantity + $remainingReturnedQuantity;
        $purchaseInventoryEntry->returned_quantity = $purchaseInventoryEntry->returned_quantity + $remainingReturnedQuantity;
        // $purchaseInventoryEntry->sold_amount -= $soldAmount;
        $purchaseInventoryEntry->save();

        $salesReturnInventoryEntry = ProductInventoryEntry::query()
          ->where('reference_type', SalesReturn::class)
          ->where('reference_id', $salesReturnId)
          ->where('product_id', $productId)
          ->where('location_id', $locationId)
          ->where('product_inventory_entry_purchase_id', $saleInventoryEntry->id)
          ->first();
        
        if($salesReturnInventoryEntry) {
          $salesReturnInventoryEntry->returned_quantity = $remainingReturnedQuantity;
          $salesReturnInventoryEntry->save();
        } else {
          $this->storeInventoryEntryOnSalesReturn($salesReturnDate, $productId, $salesReturnId, SalesReturn::class, $remainingReturnedQuantity, $saleInventoryEntry->id);
        }

        $remainingReturnedQuantity = $remainingReturnedQuantity - $remainingReturnedQuantity;
      }
    }

    return;
  }

  public function updateProductQuantityOnAdjustment(Product $product, $adjustedQuantity)
  {
    $product->quantity = $product->quantity + $adjustedQuantity;
    $product->save();
    return;
  }

  public function updateProductPriceQuantityOnPurchase(Product $product, $purchasePrice, $salePrice, $initialQuantity, float $boxSalePrice)
  {
    $oldSalePrice = $product->default_selling_price;
    $product->default_purchase_price = $purchasePrice;
    $product->default_selling_price = $salePrice;
    $product->default_selling_price_old = $oldSalePrice;
    $product->quantity = $product->quantity + $initialQuantity;
    $product->default_box_sale_price = $boxSalePrice;
    $product->update();
    return;
  }

  public function updateProductQuantityOnPurchaseReturn($productId, $purchaseReturnedQuantity)
  {
    $product = Product::find($productId);
    $product->quantity -= $purchaseReturnedQuantity;
    $product->save();
    return;
  }

  public function updateProductQuantityOnSale(Product $product, $soldQuantity)
  {
    $product->quantity -= $soldQuantity;
    $product->save();
    return;
  }

  public function updateProductQuantityOnSalesReturn(Product $product, $returnedQuantity)
  {
    $product->quantity += $returnedQuantity;
    $product->save();
    return;
  }

  public function updateInventoryOnNegativeAdjustment($adjustmentEntryId, $adjustmentEntryDate, $productId, $adjustedQuantity)
  {
    $absoluteAdjustedQuantity = abs($adjustedQuantity);
    $locationId = 1; //Will get from session

    // Get first purchase inventory entry where inventory available for particular product
    $lastPurchaseInventoryEntry = ProductInventoryEntry::where('reference_type', ProductInventoryOutflow::class)
      ->where('available_quantity', '>', 0)
      ->where('product_id', $productId)
      ->where('location_id', $locationId)
      ->orderBy('date')
      ->orderBy('id')
      ->first();

    if ($absoluteAdjustedQuantity > $lastPurchaseInventoryEntry->available_quantity) {
      $entryAdjustedQuantity = $lastPurchaseInventoryEntry->available_quantity;
      $remainingAdjustedQuantity = $entryAdjustedQuantity + $adjustedQuantity;

      $this->storeInventoryEntryOnAdjustment($adjustmentEntryDate, $productId, AdjustmentEntry::class, $adjustmentEntryId, ($entryAdjustedQuantity * -1), $lastPurchaseInventoryEntry->id);

      $lastPurchaseInventoryEntry->update([
        'available_quantity' => 0,
        'adjusted_quantity' => $lastPurchaseInventoryEntry->adjusted_quantity - $entryAdjustedQuantity,
      ]);

      return $this->updateInventoryOnNegativeAdjustment($adjustmentEntryId, $adjustmentEntryDate, $productId, $remainingAdjustedQuantity);
    }

    $this->storeInventoryEntryOnAdjustment($adjustmentEntryDate, $productId, AdjustmentEntry::class, $adjustmentEntryId, $adjustedQuantity, $lastPurchaseInventoryEntry->id);

    $purchaseInventoryEntryAdjustedQuantity = $lastPurchaseInventoryEntry->adjusted_quantity + $adjustedQuantity;

    $lastPurchaseInventoryEntry->update([
      'available_quantity' => $lastPurchaseInventoryEntry->available_quantity - $absoluteAdjustedQuantity,
      'adjusted_quantity' => $purchaseInventoryEntryAdjustedQuantity,
    ]);

    return;
  }

  public function updateInventoryOnPositiveAdjustment($adjustmentEntryId, $adjustmentEntryDate, $productId, $adjustedQuantity)
  {
    $locationId = 1; //Will get from session

    // Get last purchase inventory entry to increase available quantity
    $lastPurchaseInventoryEntry = ProductInventoryEntry::where('reference_type', ProductInventoryOutflow::class)
      ->where('product_id', $productId)
      ->where('location_id', $locationId)
      ->orderBy('date', 'desc')
      ->orderBy('id', 'desc')
      ->first();

    // if ($remainingAdjustedQuantity > $lastPurchaseInventoryEntry->available_quantity) {
    //   $entryAdjustedQuantity = $lastPurchaseInventoryEntry->available_quantity;
    //   $remainingAdjustedQuantity = $remainingAdjustedQuantity - $entryAdjustedQuantity;

    //   $this->storeInventoryEntryOnAdjustment($adjustmentEntryDate, $productId, AdjustmentEntry::class, $adjustmentEntryId, ($entryAdjustedQuantity * -1), $lastPurchaseInventoryEntry->id);

    //   $lastPurchaseInventoryEntry->update([
    //     'available_quantity' => 0,
    //     'adjusted_quantity' => $lastPurchaseInventoryEntry->adjusted_quantity - $remainingAdjustedQuantity,
    //   ]);

    //   return $this->updateInventoryOnPositiveAdjustment($adjustmentEntryId, $adjustmentEntryDate, $productId, $remainingAdjustedQuantity);
    // }

    $this->storeInventoryEntryOnAdjustment($adjustmentEntryDate, $productId, AdjustmentEntry::class, $adjustmentEntryId, $adjustedQuantity, $lastPurchaseInventoryEntry->id);

    $purchaseInventoryEntryAdjustedQuantity = $lastPurchaseInventoryEntry->adjusted_quantity + $adjustedQuantity;

    $lastPurchaseInventoryEntry->update([
      'available_quantity' => $lastPurchaseInventoryEntry->available_quantity + $adjustedQuantity,
      'adjusted_quantity' => $purchaseInventoryEntryAdjustedQuantity,
    ]);

    return;
  }
}
