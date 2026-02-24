export interface UserCourse {
  id: number;
  course_id?: number;
  title: string;
  description?: string;
  category?: string;
  start_date?: string;
  end_date?: string;
  user_progress_status: 'not_started' | 'in_progress' | 'completed';
  score?: number;
  max_attendees?: number;
  enrolled_count?: number;
  last_accessed_lesson_id?: number;
  total_lessons?: number;
  completed_lessons?: number;
  assigned_start_date?: string;
  enrolled_at?: string;
  completion_date?: string;
  hours_completed?: number;
  passed?: boolean;
}

export interface TrainingSummary {
  total_courses: number;
  completed_courses: number;
  in_progress_courses: number;
  total_hours: number;
}

export interface Attachment {
  id: number;
  user_id: number;
  file_name: string;
  file_path: string;
  uploaded_at: string;
  file_type?: string;
  created_at?: string;
}

export interface ActivityLog {
  id: number;
  user_id?: number;
  user_email?: string;
  action: string;
  details?: string;
  created_at: string;
  ip_address?: string;
}

export interface UserDashboard {
  user: import('./user.model').User;
  active_courses: UserCourse[];
  completed_courses: UserCourse[];
  exam_history: UserCourse[];
  attachments: Attachment[];
  training_summary: TrainingSummary;
}

export interface AssignCoursePayload {
  user_id: number;
  course_id: number;
  start_date?: string;
}
