-- -----------------------------------------------------
-- mkombozi_hrmis Schema
-- -----------------------------------------------------
CREATE SCHEMA IF NOT EXISTS `mkombozi_hrmis` DEFAULT CHARACTER SET utf8mb4;
USE `mkombozi_hrmis`;

-- -----------------------------------------------------
-- Tables
-- -----------------------------------------------------

CREATE TABLE IF NOT EXISTS `appraisal_cycle` (
  `cycle_id` INT(11) NOT NULL AUTO_INCREMENT,
  `year` YEAR(4) NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`cycle_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `role` (
  `role_id` INT(11) NOT NULL AUTO_INCREMENT,
  `role_name` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`role_id`),
  UNIQUE KEY `role_name` (`role_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `department` (
  `dept_id` INT(11) NOT NULL AUTO_INCREMENT,
  `dept_name` VARCHAR(150) NOT NULL,
  PRIMARY KEY (`dept_id`),
  UNIQUE KEY `dept_name` (`dept_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `user` (
  `user_id` INT(11) NOT NULL AUTO_INCREMENT,
  `name` VARCHAR(150) NOT NULL,
  `email` VARCHAR(150) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role_id` INT(11) NOT NULL,
  `dept_id` INT(11) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `email` (`email`),
  KEY `role_id` (`role_id`),
  KEY `dept_id` (`dept_id`),
  CONSTRAINT `user_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `role` (`role_id`),
  CONSTRAINT `user_ibfk_2` FOREIGN KEY (`dept_id`) REFERENCES `department` (`dept_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `appraisal` (
  `appraisal_id` INT(11) NOT NULL AUTO_INCREMENT,
  `cycle_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `final_score` DECIMAL(5,2) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`appraisal_id`),
  KEY `cycle_id` (`cycle_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `appraisal_ibfk_1` FOREIGN KEY (`cycle_id`) REFERENCES `appraisal_cycle` (`cycle_id`),
  CONSTRAINT `appraisal_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `appraisal_detail` (
  `detail_id` INT(11) NOT NULL AUTO_INCREMENT,
  `appraisal_id` INT(11) NOT NULL,
  `criterion` VARCHAR(100) NOT NULL,
  `self_score` INT(11) DEFAULT NULL,
  `manager_score` INT(11) DEFAULT NULL,
  PRIMARY KEY (`detail_id`),
  KEY `appraisal_id` (`appraisal_id`),
  CONSTRAINT `appraisal_detail_ibfk_1` FOREIGN KEY (`appraisal_id`) REFERENCES `appraisal` (`appraisal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `audit_log` (
  `log_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `action` VARCHAR(255) NOT NULL,
  `timestamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`log_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `audit_log_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `yearly_agreement` (
  `agreement_id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `year` YEAR(4) NOT NULL,
  `status` ENUM('Pending','Approved','Rejected') DEFAULT 'Pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`agreement_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `yearly_agreement_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `goal` (
  `goal_id` INT(11) NOT NULL AUTO_INCREMENT,
  `agreement_id` INT(11) NOT NULL,
  `description` TEXT NOT NULL,
  PRIMARY KEY (`goal_id`),
  KEY `agreement_id` (`agreement_id`),
  CONSTRAINT `goal_ibfk_1` FOREIGN KEY (`agreement_id`) REFERENCES `yearly_agreement` (`agreement_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `task` (
  `task_id` INT(11) NOT NULL AUTO_INCREMENT,
  `goal_id` INT(11) DEFAULT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `due_date` DATE NOT NULL,
  `status` ENUM('Pending','In Progress','Completed','Overdue','Planned') DEFAULT 'Pending',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`task_id`),
  KEY `goal_id` (`goal_id`),
  CONSTRAINT `task_ibfk_1` FOREIGN KEY (`goal_id`) REFERENCES `goal` (`goal_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `feedback` (
  `feedback_id` INT(11) NOT NULL AUTO_INCREMENT,
  `task_id` INT(11) DEFAULT NULL,
  `from_user` INT(11) NOT NULL,
  `to_user` INT(11) NOT NULL,
  `comments` TEXT NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`feedback_id`),
  KEY `task_id` (`task_id`),
  KEY `from_user` (`from_user`),
  KEY `to_user` (`to_user`),
  CONSTRAINT `feedback_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `task` (`task_id`),
  CONSTRAINT `feedback_ibfk_2` FOREIGN KEY (`from_user`) REFERENCES `user` (`user_id`),
  CONSTRAINT `feedback_ibfk_3` FOREIGN KEY (`to_user`) REFERENCES `user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `task_assignment` (
  `assign_id` INT(11) NOT NULL AUTO_INCREMENT,
  `task_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `assigned_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`assign_id`),
  KEY `task_id` (`task_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `task_assignment_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `task` (`task_id`),
  CONSTRAINT `task_assignment_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `task_worklog` (
  `log_id` INT(11) NOT NULL AUTO_INCREMENT,
  `task_id` INT(11) NOT NULL,
  `user_id` INT(11) NOT NULL,
  `progress` VARCHAR(255) DEFAULT NULL,
  `update_date` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`log_id`),
  KEY `task_id` (`task_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `task_worklog_ibfk_1` FOREIGN KEY (`task_id`) REFERENCES `task` (`task_id`),
  CONSTRAINT `task_worklog_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `appraisal_evaluator` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `appraisal_id` INT(11) NOT NULL,
  `evaluator_id` INT(11) NOT NULL,
  `role` ENUM('Self','Supervisor','Peer','HR') NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
  PRIMARY KEY (`id`),
  KEY `appraisal_id` (`appraisal_id`),
  KEY `evaluator_id` (`evaluator_id`),
  CONSTRAINT `appraisal_evaluator_ibfk_1` FOREIGN KEY (`appraisal_id`) REFERENCES `appraisal` (`appraisal_id`) ON DELETE CASCADE,
  CONSTRAINT `appraisal_evaluator_ibfk_2` FOREIGN KEY (`evaluator_id`) REFERENCES `user` (`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
