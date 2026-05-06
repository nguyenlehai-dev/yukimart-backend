<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tăng tốc query JSONB phổ biến trên shop_entries:
 * - GIN index toàn bộ data → speed up @> / whereJsonContains.
 * - Expression B-tree index trên (entity, (data->>'user_id')) → query
 *   "đơn của user X" / "customer của user X" chuyển từ seq scan sang index scan.
 * - Trigram GIN trên search_text để LIKE/ILIKE %q% nhanh hơn (nếu pg_trgm có).
 *
 * Tác động: tạo index một lần khi migrate, không khoá table lâu vì shop_entries
 * mới ~40k row.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE INDEX IF NOT EXISTS shop_entries_data_gin ON shop_entries USING gin (data jsonb_path_ops)');
        DB::statement("CREATE INDEX IF NOT EXISTS shop_entries_entity_user_id ON shop_entries (entity, ((data->>'user_id')))");
        DB::statement("CREATE INDEX IF NOT EXISTS shop_entries_entity_status ON shop_entries (entity, ((data->>'status')))");

        // pg_trgm tăng tốc search_text ILIKE — extension có thể không bật trên DB
        // không có quyền; bỏ qua nếu lỗi để migration không khoá deploy.
        try {
            DB::statement('CREATE EXTENSION IF NOT EXISTS pg_trgm');
            DB::statement('CREATE INDEX IF NOT EXISTS shop_entries_search_text_trgm ON shop_entries USING gin (search_text gin_trgm_ops)');
        } catch (\Throwable $e) {
            // pg_trgm chưa cài / không đủ quyền → skip, fallback ILIKE seq scan.
        }
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS shop_entries_data_gin');
        DB::statement('DROP INDEX IF EXISTS shop_entries_entity_user_id');
        DB::statement('DROP INDEX IF EXISTS shop_entries_entity_status');
        DB::statement('DROP INDEX IF EXISTS shop_entries_search_text_trgm');
    }
};
