<?php

namespace App\Services;

use App\DataTransferObjects\NormalizedPurchaseData;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductWebhookSyncService
{
    public function resolve(NormalizedPurchaseData $data): Product
    {
        return $this->sync(
            productCodes: $data->productCodesForLookup(),
            primaryCode: $data->productCode,
            title: $this->resolveTitleFromPayload($data->rawPayload, $data->productCode),
            amount: $data->amount,
        );
    }

    /**
     * @param  list<string>  $productCodes
     */
    public function syncFromHotmartPayload(array $payload, array $productCodes): Product
    {
        $primaryCode = $productCodes[0] ?? '';

        return $this->sync(
            productCodes: $productCodes,
            primaryCode: $primaryCode,
            title: $this->resolveTitleFromPayload($payload, $primaryCode),
            amount: $this->resolveAmountFromHotmartPayload($payload),
        );
    }

    /**
     * @param  list<string>  $productCodes
     */
    private function sync(
        array $productCodes,
        string $primaryCode,
        string $title,
        ?float $amount,
    ): Product {
        $existing = Product::query()
            ->whereIn('product_code', $productCodes)
            ->first();

        if ($existing) {
            $this->syncTitleIfChanged($existing, $title);

            return $existing->fresh();
        }

        $product = Product::query()->create([
            'product_code' => $primaryCode,
            'title' => $title,
            'slug' => $this->uniqueSlug($title, $primaryCode),
            'description' => 'Produto sincronizado automaticamente via webhook Hotmart.',
            'price' => $amount ?? 0,
            'checkout_url' => null,
            'plan_id' => null,
            'grants_access' => false,
            'is_active' => true,
            'sort_order' => 999,
        ]);

        Log::info('Produto Hotmart cadastrado automaticamente.', [
            'product_id' => $product->id,
            'product_code' => $primaryCode,
            'title' => $title,
        ]);

        return $product;
    }

    private function syncTitleIfChanged(Product $product, string $title): void
    {
        if (! filled($title) || $product->title === $title) {
            return;
        }

        $product->update([
            'title' => $title,
            'slug' => $this->uniqueSlug($title, $product->product_code, $product->id),
        ]);

        Log::info('Produto Hotmart atualizado automaticamente.', [
            'product_id' => $product->id,
            'product_code' => $product->product_code,
            'title' => $title,
        ]);
    }

    private function resolveTitleFromPayload(array $payload, string $fallbackCode): string
    {
        $name = trim((string) data_get($payload, 'data.product.name', ''));

        if (filled($name)) {
            return $name;
        }

        return 'Hotmart — '.$fallbackCode;
    }

    private function resolveAmountFromHotmartPayload(array $payload): ?float
    {
        $value = data_get($payload, 'data.purchase.full_price.value');

        if ($value === null) {
            $value = data_get($payload, 'data.purchase.price.value');
        }

        if ($value === null) {
            return null;
        }

        return round((float) $value, 2);
    }

    private function uniqueSlug(string $title, string $productCode, ?int $ignoreId = null): string
    {
        $base = Str::slug($title) ?: 'produto-hotmart';
        $slug = $base;

        if (Product::query()->where('slug', $slug)->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$productCode;
        }

        if (Product::query()->where('slug', $slug)->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.Str::lower(Str::random(6));
        }

        return $slug;
    }
}
