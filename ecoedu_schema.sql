-- ===========================================
-- GreenQuest Platform - MySQL Schema (3NF)
-- ===========================================

CREATE DATABASE IF NOT EXISTS ecoedu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ecoedu;

-- --- Lookup Tables ---
CREATE TABLE roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(32) UNIQUE NOT NULL
);

CREATE TABLE eco_point_reasons (
    reason_id INT AUTO_INCREMENT PRIMARY KEY,
    reason_key VARCHAR(32) UNIQUE NOT NULL,
    description TEXT
);

CREATE TABLE content_types (
    content_type_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(32) UNIQUE NOT NULL
);

CREATE TABLE verification_types (
    verification_type_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(32) UNIQUE NOT NULL
);

-- --- Core Tables ---
CREATE TABLE schools (
    school_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    contact_email VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE users (
    user_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    school_id INT NULL,
    join_date DATE DEFAULT CURRENT_DATE,
    profile_pic TEXT,
    is_active TINYINT(1) DEFAULT 1,
    FOREIGN KEY (role_id) REFERENCES roles(role_id),
    FOREIGN KEY (school_id) REFERENCES schools(school_id)
);

CREATE TABLE lessons (
    lesson_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    summary TEXT,
    difficulty TINYINT DEFAULT 1,
    created_by BIGINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

CREATE TABLE lesson_contents (
    content_id INT AUTO_INCREMENT PRIMARY KEY,
    lesson_id INT NOT NULL,
    content_type_id INT NOT NULL,
    title VARCHAR(255),
    body TEXT,
    external_url TEXT,
    sequence_num INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (lesson_id, sequence_num),
    FOREIGN KEY (lesson_id) REFERENCES lessons(lesson_id) ON DELETE CASCADE,
    FOREIGN KEY (content_type_id) REFERENCES content_types(content_type_id)
);

CREATE TABLE quizzes (
    quiz_id INT AUTO_INCREMENT PRIMARY KEY,
    lesson_id INT NOT NULL,
    title VARCHAR(255),
    total_marks INT DEFAULT 0,
    time_limit_minutes INT,
    FOREIGN KEY (lesson_id) REFERENCES lessons(lesson_id) ON DELETE CASCADE
);

CREATE TABLE quiz_questions (
    question_id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    question_text TEXT NOT NULL,
    marks INT DEFAULT 1,
    sequence_num INT DEFAULT 0,
    UNIQUE (quiz_id, sequence_num),
    FOREIGN KEY (quiz_id) REFERENCES quizzes(quiz_id) ON DELETE CASCADE
);

CREATE TABLE quiz_options (
    option_id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    option_text TEXT,
    is_correct TINYINT(1) DEFAULT 0,
    FOREIGN KEY (question_id) REFERENCES quiz_questions(question_id) ON DELETE CASCADE
);

CREATE TABLE quiz_attempts (
    attempt_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    user_id BIGINT NOT NULL,
    score INT,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    submitted_at TIMESTAMP NULL,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(quiz_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE TABLE quiz_answers (
    answer_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    attempt_id BIGINT NOT NULL,
    question_id INT NOT NULL,
    selected_option_id INT,
    is_marked_correct TINYINT(1),
    marks_awarded INT DEFAULT 0,
    FOREIGN KEY (attempt_id) REFERENCES quiz_attempts(attempt_id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES quiz_questions(question_id),
    FOREIGN KEY (selected_option_id) REFERENCES quiz_options(option_id)
);

CREATE TABLE challenges (
    challenge_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    start_date DATE,
    end_date DATE,
    eco_points INT DEFAULT 0,
    verification_type_id INT,
    created_by BIGINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (verification_type_id) REFERENCES verification_types(verification_type_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id)
);

CREATE TABLE user_challenges (
    user_challenge_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    challenge_id INT NOT NULL,
    status ENUM('pending','completed','verified','rejected') DEFAULT 'pending',
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    completed_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (challenge_id) REFERENCES challenges(challenge_id) ON DELETE CASCADE
);

CREATE TABLE challenge_proofs (
    proof_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_challenge_id BIGINT NOT NULL,
    proof_url TEXT,
    metadata JSON,
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verifier_id BIGINT,
    verified_at TIMESTAMP NULL,
    verdict ENUM('approved','rejected','pending') DEFAULT 'pending',
    FOREIGN KEY (user_challenge_id) REFERENCES user_challenges(user_challenge_id) ON DELETE CASCADE,
    FOREIGN KEY (verifier_id) REFERENCES users(user_id)
);

CREATE TABLE eco_points (
    points_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    points INT NOT NULL,
    reason_id INT,
    related_entity_type VARCHAR(64),
    related_entity_id BIGINT,
    awarded_by BIGINT,
    awarded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    note TEXT,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (reason_id) REFERENCES eco_point_reasons(reason_id),
    FOREIGN KEY (awarded_by) REFERENCES users(user_id)
);

CREATE TABLE badges (
    badge_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(128) UNIQUE NOT NULL,
    description TEXT,
    criteria JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE user_badges (
    user_badge_id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT NOT NULL,
    badge_id INT NOT NULL,
    awarded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (user_id, badge_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (badge_id) REFERENCES badges(badge_id) ON DELETE CASCADE
);

-- --- Seed Data ---
INSERT INTO roles (role_name) VALUES
('student'),('teacher'),('admin'),('ngo');

INSERT INTO eco_point_reasons (reason_key, description) VALUES
('challenge','Points for completing challenges'),
('quiz','Points for quiz scores'),
('bonus','Manual bonus points');

INSERT INTO content_types (name) VALUES
('article'),('video'),('interactive');

INSERT INTO verification_types (name) VALUES
('photo'),('geo'),('teacher_approval');

-- Add sample data for testing
INSERT INTO schools (name, city, state, contact_email) VALUES
('Green Valley High School', 'Greenville', 'California', 'info@greenvalleyhs.edu'),
('Eco Warriors Academy', 'Portland', 'Oregon', 'contact@ecowarriors.edu');

-- Create admin user with password 'admin123'
INSERT INTO users (name, email, password_hash, role_id, school_id) VALUES
('Admin', 'admin@greenquest.com', '$2y$10$8tPbCf5Lm2hPE0TvOxlnIehNwMQrWa1CfP1/FJxbQZJn.EBjq5q/W', 3, NULL);

-- Create sample badges
INSERT INTO badges (name, description, criteria) VALUES
('Eco Starter', 'Awarded for joining the platform', '{"points_required": 0}'),
('Green Thumb', 'Complete 3 planting challenges', '{"challenges_completed": 3, "challenge_type": "planting"}'),
('Quiz Master', 'Score 90% or higher on 5 quizzes', '{"quiz_count": 5, "min_score_percent": 90}'),
('Eco Warrior', 'Earn 1000 eco-points', '{"points_required": 1000}');