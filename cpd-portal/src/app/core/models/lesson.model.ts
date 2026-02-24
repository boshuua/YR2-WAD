export interface Lesson {
  id: number;
  course_id: number;
  title: string;
  content: string;
  order_index: number;
  created_at?: string;
  updated_at?: string;
}

export interface LessonProgress {
  lesson_id: number;
  course_id: number;
  completed: boolean;
  completed_at?: string;
}
