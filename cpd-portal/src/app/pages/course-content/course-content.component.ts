import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router } from '@angular/router';
import { AuthService } from '../../service/auth.service';
import { ToastService } from '../../service/toast.service';

@Component({
  selector: 'app-course-content',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './course-content.component.html',
  styleUrls: ['./course-content.component.css']
})
export class CourseContentComponent implements OnInit {
  course: any | null = null;
  questions: any[] = [];
  isLoading = true;
  errorMessage = '';
  isEnrolled = false;
  isCheckingEnrollment = true;

  // Quiz state
  userAnswers: Map<number, number[]> = new Map(); // Array to support multiple answers if needed
  quizSubmitted = false;
  quizScore: number | null = null;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private authService: AuthService,
    private toastService: ToastService
  ) {}

  ngOnInit(): void {
    this.route.paramMap.subscribe(params => {
      const id = params.get('id');
      if (id) {
        this.checkEnrollment(+id);
      } else {
        this.toastService.error('Course ID not found.');
        this.router.navigate(['/dashboard']);
      }
    });
  }

  checkEnrollment(courseId: number): void {
    this.isCheckingEnrollment = true;
    this.authService.getUserCourses().subscribe({
      next: (courses: any[]) => {
        const course = courses.find(c => c.id === courseId);

        if (!course || course.user_progress_status === null || course.user_progress_status === undefined) {
          this.isEnrolled = false;
          this.toastService.error('You must enroll in this course before accessing the quiz.');
          this.router.navigate(['/dashboard/my-courses']);
          this.isCheckingEnrollment = false;
          return;
        }

        if (course.user_progress_status === 'completed') {
          this.toastService.info('You have already completed this course.');
          this.router.navigate(['/dashboard/my-courses']);
          this.isCheckingEnrollment = false;
          return;
        }

        this.isEnrolled = true;
        this.loadCourse(courseId);
        this.loadQuestions(courseId);
        this.isCheckingEnrollment = false;
      },
      error: () => {
        this.toastService.error('Failed to verify enrollment status.');
        this.router.navigate(['/dashboard/my-courses']);
        this.isCheckingEnrollment = false;
      }
    });
  }

  loadCourse(courseId: number): void {
    this.isLoading = true;
    this.errorMessage = '';
    this.authService.getCourseById(courseId).subscribe({
      next: (data) => {
        this.course = data;
        this.isLoading = false;
      },
      error: (err) => {
        this.errorMessage = 'Failed to load course content.';
        this.toastService.error(this.errorMessage);
        this.isLoading = false;
      }
    });
  }

  loadQuestions(courseId: number): void {
    this.authService.getCourseQuestions(courseId).subscribe({
      next: (data: any) => {
        this.questions = Array.isArray(data) ? data : [];
      },
      error: (err) => {
        console.error('Failed to load questions:', err);
        this.questions = [];
      }
    });
  }

  resetQuiz(): void {
    this.userAnswers.clear();
    this.quizSubmitted = false;
    this.quizScore = null;
  }

  toggleAnswer(questionId: number, optionId: number, isSingleAnswer: boolean = false): void {
    if (this.quizSubmitted) return;

    if (isSingleAnswer) {
      // For single answer questions (true/false, single choice), replace the answer
      this.userAnswers.set(questionId, [optionId]);
    } else {
      // For multiple answer questions, toggle the selection
      const currentAnswers = this.userAnswers.get(questionId) || [];
      const index = currentAnswers.indexOf(optionId);

      if (index > -1) {
        currentAnswers.splice(index, 1);
      } else {
        currentAnswers.push(optionId);
      }

      this.userAnswers.set(questionId, currentAnswers);
    }
  }

  isTrueFalseQuestion(question: any): boolean {
    return question.question_type === 'true_false';
  }

  isOptionSelected(questionId: number, optionId: number): boolean {
    const answers = this.userAnswers.get(questionId) || [];
    return answers.includes(optionId);
  }

  submitQuiz(): void {
    // Check if all questions are answered
    const unansweredQuestions = this.questions.filter(q => {
      const answers = this.userAnswers.get(q.id);
      return !answers || answers.length === 0;
    });

    if (unansweredQuestions.length > 0) {
      this.toastService.error('Please answer all questions before submitting.');
      return;
    }

    // Calculate score
    let correctAnswers = 0;
    this.questions.forEach(question => {
      const userAnswerIds = this.userAnswers.get(question.id) || [];
      const correctOptionIds = question.options
        .filter((opt: any) => opt.is_correct)
        .map((opt: any) => opt.id);

      // Check if user's answers match correct answers exactly
      const isCorrect =
        userAnswerIds.length === correctOptionIds.length &&
        userAnswerIds.every(id => correctOptionIds.includes(id));

      if (isCorrect) {
        correctAnswers++;
      }
    });

    this.quizScore = Math.round((correctAnswers / this.questions.length) * 100);
    this.quizSubmitted = true;

    // Submit score to backend
    if (this.course) {
      this.authService.submitQuiz(this.course.id, this.quizScore).subscribe({
        next: (response) => {
          if (response.passed) {
            this.toastService.success(response.message);
          } else {
            this.toastService.info(response.message);
          }
        },
        error: (err) => {
          this.toastService.error('Failed to save quiz score: ' + (err.error?.message || err.message));
        }
      });
    }
  }

  goBack(): void {
    this.router.navigate(['/dashboard/my-courses']);
  }
}
