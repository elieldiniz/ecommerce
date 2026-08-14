---
paths:
  - 'tests/**'
---

# Tests

## HolderTypeFactory only yields 2 slugs — avoid independent double-creation
`HolderTypeFactory` picks name from only `['Pessoa Física', 'Pessoa Jurídica']` and slugs it, so `holder_types.slug` (unique) collides ~50% of the time if you call `HolderType::factory()->create()` a second time independently in the same test. `ProductFactory`'s `holder_type_id` already falls back to creating one via `HolderType::inRandomOrder()->value('id') ?? HolderType::factory()->create()->id` when none exists — so if a test also needs an explicit `HolderType`, create it first and pass its `id` into `Product::factory()->create(['holder_type_id' => $holderType->id, ...])` rather than creating a second `HolderType` after a `Product` factory call.
