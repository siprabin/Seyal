-- Time is now entered as hours + minutes. Store hours with 4 decimals so minutes are kept exactly
-- (5 min = 0.0833 h) and many small entries still add up to the right total.
ALTER TABLE work_logs MODIFY hours DECIMAL(7,4) NOT NULL DEFAULT 0;
ALTER TABLE tasks MODIFY estimated_hours DECIMAL(9,4) NULL;
