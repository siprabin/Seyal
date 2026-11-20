-- Phase 3: task ratings, weekly reviews, appraisals, client portal visibility.

-- One rating per task, given by the assignee's direct manager when the task is done.
-- weightage / on_time / completed_on are snapshots so later edits to the task don't change history.
CREATE TABLE task_ratings (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  task_id INT UNSIGNED NOT NULL UNIQUE,
  user_id INT UNSIGNED NOT NULL,
  rated_by INT UNSIGNED NULL,
  quality TINYINT UNSIGNED NOT NULL,
  weightage TINYINT UNSIGNED NOT NULL,
  on_time TINYINT(1) NOT NULL DEFAULT 1,
  days_late INT NOT NULL DEFAULT 0,
  completed_on DATE NOT NULL,
  comment TEXT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  KEY idx_tr_user (user_id, completed_on),
  CONSTRAINT fk_tr_task FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
  CONSTRAINT fk_tr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_tr_rater FOREIGN KEY (rated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Manager's weekly review of a direct report (week_start = Monday).
CREATE TABLE weekly_reviews (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  reviewer_id INT UNSIGNED NULL,
  week_start DATE NOT NULL,
  rating TINYINT UNSIGNED NOT NULL,
  went_well TEXT NULL,
  to_improve TEXT NULL,
  next_focus TEXT NULL,
  shared TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  UNIQUE KEY uq_review_week (user_id, week_start),
  CONSTRAINT fk_wr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_wr_reviewer FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- status: draft -> finalised (metrics frozen) -> published (visible to the employee)
CREATE TABLE appraisals (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  reviewer_id INT UNSIGNED NULL,
  title VARCHAR(160) NOT NULL,
  period_from DATE NOT NULL,
  period_to DATE NOT NULL,
  status VARCHAR(12) NOT NULL DEFAULT 'draft',
  overall_rating TINYINT UNSIGNED NULL,
  strengths TEXT NULL,
  improvements TEXT NULL,
  goals TEXT NULL,
  manager_comments TEXT NULL,
  metrics_json TEXT NULL,
  score DECIMAL(5,1) NULL,
  finalised_at DATETIME NULL,
  finalised_by INT UNSIGNED NULL,
  published_at DATETIME NULL,
  published_by INT UNSIGNED NULL,
  employee_comment TEXT NULL,
  acknowledged_at DATETIME NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NULL,
  KEY idx_ap_user (user_id, status),
  CONSTRAINT fk_ap_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_ap_reviewer FOREIGN KEY (reviewer_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Projects can be hidden from the client portal.
ALTER TABLE projects ADD COLUMN client_visible TINYINT(1) NOT NULL DEFAULT 1 AFTER client_id;

-- Reports: managers and above by default. (Publishing appraisals stays Super Admin only by default.)
INSERT IGNORE INTO role_permissions (role_id, perm) SELECT id, 'reports.view' FROM roles WHERE slug IN ('senior_mgmt', 'dept_head', 'manager');
