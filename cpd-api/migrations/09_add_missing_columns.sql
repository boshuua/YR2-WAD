-- Add score column to user_course_progress if it doesn't exist
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'user_course_progress' AND column_name = 'score') THEN
        ALTER TABLE user_course_progress ADD COLUMN score INTEGER DEFAULT 0;
    END IF;
END $$;

-- Add category column to courses if it doesn't exist
DO $$
BEGIN
    IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'courses' AND column_name = 'category') THEN
        ALTER TABLE courses ADD COLUMN category VARCHAR(50) DEFAULT 'Training';
    END IF;
END $$;

-- Update existing assessments to have the correct category
UPDATE courses SET category = 'Assessment' WHERE title LIKE '%Assessment%';
