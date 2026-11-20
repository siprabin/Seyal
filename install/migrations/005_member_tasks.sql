-- Per-project switch: team members of the project may add tasks, assigned only to themselves.
-- Off by default; the project manager (or anyone who can manage the project) turns it on in the project settings.
ALTER TABLE projects ADD COLUMN member_tasks TINYINT(1) NOT NULL DEFAULT 0 AFTER client_visible;
