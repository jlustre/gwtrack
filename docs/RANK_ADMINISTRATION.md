# Rank Administration

Rank administrators can open **Admin → Ranks** to manage the promotion ladder and the advancement requirements associated with each target rank.

## Promotion ladder

- Rank rows are ordered by sort value. Use the up/down controls to exchange a rank with its neighbor, or enter an unused sort value while editing.
- Track, income metric and threshold, NBA/leg requirements, compensation rates, field-leader status, description, and active status are editable.
- Rank codes are permanent identifiers used by member assignments and application rules; they cannot be edited after creation.
- The configured entry rank must remain active. Other ranks can be retired and reactivated.
- Retiring a rank does not change current member assignments. Inactive ranks are excluded from future active-rank progression and rank assignment, except that an existing inactive assignment remains selectable when editing that same member.

## Advancement requirements

- Select a rank to edit the requirements members must complete to advance into it.
- Requirements support category, description, required/optional status, active status, and sort order.
- Retiring a requirement hides it from active advancement trackers. Its member progress records remain in the database; reactivating the requirement makes the requirement visible again with its history.
- Requirement identifiers remain stable when the display title changes, so subsequent seeding does not duplicate or overwrite administrator edits.

## Seed data

`RankSeeder` and `RankRequirementSeeder` create missing catalog defaults. Once a rank or requirement exists in the database, seeder runs leave its administrator-managed values intact. The database is the source of truth for runtime rank administration.
