-- Guided tour: shown automatically on a person's first login; "?" → Take the tour replays it any time.
ALTER TABLE users ADD COLUMN tour_seen_at DATETIME NULL AFTER last_login_at;
-- People who have already used Seyal are not interrupted; they can still start the tour from the "?" button.
UPDATE users SET tour_seen_at = NOW() WHERE last_login_at IS NOT NULL;
