<?php

use App\Models\AudioTrack;
use App\Models\Material;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Setting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const REMOVED_SLUGS = ['basico', 'premium', 'vitalicio'];

    public function up(): void
    {
        $completo = Plan::query()->where('slug', 'completo')->first();

        if (! $completo) {
            return;
        }

        $removedIds = Plan::query()
            ->whereIn('slug', self::REMOVED_SLUGS)
            ->pluck('id');

        if ($removedIds->isNotEmpty()) {
            Material::query()
                ->whereIn('plan_id', $removedIds)
                ->update(['plan_id' => $completo->id]);

            AudioTrack::query()
                ->whereIn('required_plan_id', $removedIds)
                ->update(['required_plan_id' => $completo->id]);

            Product::query()
                ->whereIn('plan_id', $removedIds)
                ->update(['plan_id' => $completo->id]);

            Purchase::query()
                ->whereIn('plan_id', $removedIds)
                ->update(['plan_id' => $completo->id]);

            $userIds = DB::table('user_plans')
                ->whereIn('plan_id', $removedIds)
                ->distinct()
                ->pluck('user_id');

            foreach ($userIds as $userId) {
                DB::table('user_plans')->updateOrInsert(
                    [
                        'user_id' => $userId,
                        'plan_id' => $completo->id,
                    ],
                    [
                        'granted_at' => now(),
                        'expires_at' => null,
                        'granted_by' => 'migration:consolidate_plans',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }

            DB::table('user_plans')
                ->whereIn('plan_id', $removedIds)
                ->delete();

            Plan::query()
                ->whereIn('id', $removedIds)
                ->delete();
        }

        $completo->update([
            'name' => 'Plan Completo',
            'description' => 'Acceso completo a toda la biblioteca bíblica digital.',
            'level' => 1,
            'is_admin' => false,
            'sort_order' => 1,
        ]);

        Plan::query()
            ->where('slug', 'admin')
            ->update([
                'sort_order' => 99,
            ]);

        Setting::query()
            ->whereIn('key', ['checkout_basico_url', 'checkout_premium_url'])
            ->delete();
    }

    public function down(): void
    {
        //
    }
};
