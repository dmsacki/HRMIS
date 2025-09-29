USE `mkombozi_hrmis`;

-- Roles
INSERT IGNORE INTO role (role_name) VALUES
('Admin'),('HR Manager'),('Employee'),('Supervisor');

-- Departments
INSERT IGNORE INTO department (dept_name) VALUES
('Human Resources'),('Finance'),('IT'),('Operations');

-- Users (passwords hashed)
INSERT INTO user (name, email, password, role_id, dept_id) VALUES
('Anne HR','anne.hr@mkombozi.com',SHA2('password123',256),2,1),
('John Finance','john.finance@mkombozi.com',SHA2('password123',256),3,2),
('Mussa IT','mussa.it@mkombozi.com',SHA2('password123',256),3,3),
('Diana Ops','diana.ops@mkombozi.com',SHA2('password123',256),4,4);

-- Appraisal Cycle
INSERT INTO appraisal_cycle (year, start_date, end_date) VALUES
(2025, '2025-01-01', '2025-12-31');

-- Yearly Agreements
INSERT INTO yearly_agreement (user_id, title, year, status) VALUES
(2, 'Finance Performance Agreement', 2025, 'Approved'),
(3, 'IT Development Agreement', 2025, 'Pending'),
(4, 'Operations Supervision Agreement', 2025, 'Approved');

-- Goals
INSERT INTO goal (agreement_id, description) VALUES
(1, 'Improve monthly financial reporting accuracy'),
(2, 'Develop new IT system for HR management'),
(3, 'Ensure timely completion of operations projects'),
(3, 'Mentor team members for improved performance');

-- Tasks
INSERT INTO task (goal_id, title, description, due_date, status) VALUES
(1, 'Prepare January financial report', 'Complete and submit monthly finance report', '2025-01-31', 'Completed'),
(2, 'Develop HRMS prototype', 'Build initial version of HR management system', '2025-03-15', 'In Progress'),
(2, 'Test HRMS prototype', 'Perform user testing on HR system', '2025-04-01', 'Pending'),
(3, 'Oversee Operations Department Q1 Projects', 'Monitor and guide project execution for Q1', '2025-03-31', 'In Progress'),
(4, 'Conduct Mentorship Sessions', 'Organize monthly mentorship meetings with staff', '2025-06-30', 'Planned');

-- Task Assignments
INSERT INTO task_assignment (task_id, user_id) VALUES
(1, 2), -- John Finance
(2, 3), -- Mussa IT
(3, 3), -- Mussa IT
(4, 4), -- Diana Ops
(5, 4); -- Diana Ops

-- Task Worklogs
INSERT INTO task_worklog (task_id, user_id, progress) VALUES
(2, 3, 'Database schema created'),
(2, 3, 'Initial UI completed'),
(3, 3, 'Test plan drafted'),
(4, 4, 'Reviewed project reports and provided feedback to staff'),
(5, 4, 'Scheduled first mentorship session for February');

-- Appraisals
INSERT INTO appraisal (cycle_id, user_id, final_score) VALUES
(1, 2, 85.50), -- John Finance
(1, 3, 78.00), -- Mussa IT
(1, 4, 90.00); -- Diana Ops

-- Appraisal Details
INSERT INTO appraisal_detail (appraisal_id, criterion, self_score, manager_score) VALUES
(1, 'Accuracy', 90, 85),
(1, 'Timeliness', 80, 85),
(2, 'Technical Skills', 75, 80),
(2, 'Collaboration', 70, 75),
(3, 'Leadership', 88, 92),
(3, 'Mentorship', 85, 90),
(3, 'Team Oversight', 90, 88);

-- Feedback
INSERT INTO feedback (task_id, from_user, to_user, comments) VALUES
(1, 4, 2, 'Great job completing the finance report on time!'),
(2, 4, 3, 'Good progress on the HRMS prototype. Keep it up.');

-- Appraisal Evaluators
INSERT INTO appraisal_evaluator (appraisal_id, evaluator_id, role) VALUES
(1, 4, 'Supervisor'),
(2, 4, 'Supervisor');
