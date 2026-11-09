<?php

namespace App\Models;

/**
 * Alias for GRNItem.
 *
 * `app/Models/GRNItem.php` (hand-written, used by GRNService, the
 * GoodsReceivedNote relation, GRNItemFactory and GRNTest) and this
 * auto-generated `GrnItem.php` were two divergent models over the same
 * `grn_items` table — different $fillable, casts and relations, and only one of
 * them tenant-scoped. They also differ only by letter case, so on a
 * case-insensitive filesystem Composer resolves both FQCNs to the same file and
 * one of the two classes simply does not exist (fatal error).
 *
 * This file is now a pure alias so there is a single source of truth. It is kept
 * only because App\Models\QualityCheck imports GrnItem. tools/generate-models.py
 * skips `grn_items` so a regeneration cannot resurrect the duplicate.
 *
 * @deprecated use GRNItem
 */
class GrnItem extends GRNItem
{
}
