export type CourseStatus = 'draft' | 'published' | 'archived';
export type CourseType = 'active' | 'template' | 'locked' | 'library' | 'upcoming';

export interface Course {
  id: number;
  title: string;
  description: string;
  required_hours: number;
  duration_minutes?: number;
  start_date: string;
  end_date: string;
  status: CourseStatus;
  category?: string;
  created_at?: string;
  updated_at?: string;
  is_template?: boolean;
  is_locked?: boolean;
  max_attendees?: number;
  spaces_booked?: number; // Calculated field
  enrolled_count?: number; // Calculated field
  duration?: number; // alias for duration_minutes in some API responses
}

export interface CourseSchedulePayload {
  template_id: number;
  start_date: string;
  end_date?: string;
  title?: string;
  user_ids?: number[];
}

export interface EnrollCoursePayload {
  course_id: number;
}
