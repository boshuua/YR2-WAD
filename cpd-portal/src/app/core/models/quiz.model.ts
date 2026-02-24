export interface QuestionOption {
  id: number;
  option_text: string;
  is_correct: boolean;
}

export interface Question {
  id: number;
  course_id?: number;
  question_text: string;
  question_type: 'multiple_choice' | 'true_false';
  options: QuestionOption[];
}

export interface QuizResult {
  course_id: number;
  score: number;
  passed: boolean;
  submitted_at?: string;
}

export interface QuizSubmission {
  course_id: number;
  score: number;
}
