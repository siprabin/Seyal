-- Phase 2: Start Day, daily work log with AI usage, Close Day, reminders, digests, notifications.

CREATE TABLE ai_tools (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  sort_order INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO ai_tools (name, sort_order, created_at) VALUES
 ('ChatGPT', 1, NOW()), ('Gemini', 2, NOW()), ('Claude', 3, NOW());

-- One row per person per date.
-- status: open (started), closed, auto_closed (forgot to close), missed (never started on a working day), leave
CREATE TABLE work_days (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  work_date DATE NOT NULL,
  status VARCHAR(12) NOT NULL DEFAULT 'open',
  started_at DATETIME NULL,
  closed_at DATETIME NULL,
  closed_by INT UNSIGNED NULL,
  note TEXT NULL,
  flagged TINYINT(1) NOT NULL DEFAULT 0,
  flag_reason VARCHAR(255) NULL,
  reopened_count INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  UNIQUE KEY uq_work_day (user_id, work_date),
  KEY idx_wd_date (work_date, status),
  CONSTRAINT fk_wd_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_wd_closer FOREIGN KEY (closed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE work_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  work_day_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  task_id INT UNSIGNED NULL,
  description TEXT NULL,
  hours DECIMAL(5,2) NOT NULL DEFAULT 0,
  ai_used TINYINT(1) NOT NULL DEFAULT 0,
  ai_minutes INT UNSIGNED NOT NULL DEFAULT 0,
  ai_purpose VARCHAR(30) NULL,
  time_saved_minutes INT UNSIGNED NOT NULL DEFAULT 0,
  output_quality TINYINT UNSIGNED NULL,
  blockers TEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  KEY idx_wl_day (work_day_id),
  KEY idx_wl_task (task_id),
  KEY idx_wl_user (user_id),
  CONSTRAINT fk_wl_day FOREIGN KEY (work_day_id) REFERENCES work_days(id) ON DELETE CASCADE,
  CONSTRAINT fk_wl_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_wl_task FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE work_log_ai_tools (
  work_log_id INT UNSIGNED NOT NULL,
  ai_tool_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (work_log_id, ai_tool_id),
  CONSTRAINT fk_wlat_log FOREIGN KEY (work_log_id) REFERENCES work_logs(id) ON DELETE CASCADE,
  CONSTRAINT fk_wlat_tool FOREIGN KEY (ai_tool_id) REFERENCES ai_tools(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Every email / Teams post the app sends (also used to avoid sending the same reminder twice).
CREATE TABLE notification_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  kind VARCHAR(30) NOT NULL,
  channel VARCHAR(10) NOT NULL,
  user_id INT UNSIGNED NULL,
  ref_date DATE NULL,
  ref_id INT UNSIGNED NULL,
  recipient VARCHAR(255) NULL,
  status VARCHAR(10) NOT NULL,
  error TEXT NULL,
  created_at DATETIME NOT NULL,
  KEY idx_nl_dedupe (kind, user_id, ref_date),
  KEY idx_nl_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- New permissions for existing roles.
INSERT IGNORE INTO role_permissions (role_id, perm) SELECT id, 'team.view_all' FROM roles WHERE slug = 'senior_mgmt';
INSERT IGNORE INTO role_permissions (role_id, perm) SELECT id, 'team.view_department' FROM roles WHERE slug = 'dept_head';
