-- Where people were when they started and closed their day (Settings → Daily work → Location).
-- *_loc_status: ok, denied, unavailable, timeout or unsupported; NULL when location capture was off.
ALTER TABLE work_days
  ADD COLUMN start_lat DECIMAL(9,6) NULL AFTER started_at,
  ADD COLUMN start_lng DECIMAL(9,6) NULL AFTER start_lat,
  ADD COLUMN start_accuracy INT UNSIGNED NULL AFTER start_lng,
  ADD COLUMN start_loc_status VARCHAR(12) NULL AFTER start_accuracy,
  ADD COLUMN close_lat DECIMAL(9,6) NULL AFTER closed_at,
  ADD COLUMN close_lng DECIMAL(9,6) NULL AFTER close_lat,
  ADD COLUMN close_accuracy INT UNSIGNED NULL AFTER close_lng,
  ADD COLUMN close_loc_status VARCHAR(12) NULL AFTER close_accuracy;
