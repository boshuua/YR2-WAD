-- 05_simplify_training_system.sql
-- Removes quiz/assessment functionality to simplify training flow
-- SAFE TO RUN: Drops only unused tables and columns

-- ============================================
-- STEP 1: Drop quiz-related tables
-- ============================================
-- These tables are no longer needed since we're removing assessments
DROP TABLE IF EXISTS question_options CASCADE;
DROP TABLE IF EXISTS questions CASCADE;

-- ============================================
-- STEP 2: Clean up user_course_progress
-- ============================================
-- Remove score column (no more assessments/grading)
ALTER TABLE user_course_progress DROP COLUMN IF EXISTS score;

-- ============================================
-- STEP 3: Verify final structure
-- ============================================
-- Run this to confirm cleanup:
-- SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name;

-- Expected remaining tables:
-- - courses (templates and scheduled instances)
-- - lessons (training content)
-- - users (user accounts)
-- - user_course_progress (enrollment tracking)
-- - activity_log (audit trail)
-- - attachments (if exists)
