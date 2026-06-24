# Title

Avoid filesort/temp table in CMS block store fallback load

# Body

`Mage_Cms_Model_Resource_Block::_getLoadSelect()` on current `main` still builds a store fallback query of this shape:

```sql
SELECT cms_block.*, cbs.store_id
FROM cms_block
INNER JOIN cms_block_store AS cbs
    ON cms_block.block_id = cbs.block_id
WHERE cms_block.identifier = ?
  AND cms_block.is_active = 1
  AND cbs.store_id IN (0, ?)
ORDER BY cbs.store_id DESC
LIMIT 1
```

On MariaDB this can produce a `filesort` with a `temporary_table` in `EXPLAIN FORMAT=JSON`, even though the final result set is only choosing between the concrete store and admin store fallback.

The relevant code on `main` is currently in `app/code/core/Mage/Cms/Model/Resource/Block.php`:

```php
$select->join(
    ['cbs' => $this->getTable('cms/block_store')],
    $this->getMainTable() . '.block_id = cbs.block_id',
    ['store_id'],
)->where('is_active = ?', 1)
->where('cbs.store_id in (?) ', $stores)
->order('store_id DESC')
->limit(1);
```

The existing `cms_block_store` primary key is already `(block_id, store_id)`, so an extra index does not appear to address the real issue here. The likely improvement is to avoid the fallback sort entirely:

1. Probe the requested store with `cbs.store_id = ?`
2. If no row is found, probe `ADMIN_STORE_ID`

That preserves behavior while turning this into at most two indexed point lookups and avoids the `IN (...) ORDER BY ... DESC LIMIT 1` plan shape.

I have a local Composer patch implementing that as an override of `load()` plus a helper for the single-store probe, and it keeps `_getLoadSelect()` unchanged for callers that build the select directly.

If helpful, I can open a follow-up PR with that change.
