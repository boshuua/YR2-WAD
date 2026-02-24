import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router } from '@angular/router';
import { CourseService } from '../../core/services/course.service';
import { ToastService } from '../../core/services/toast.service';
import { Course } from '../../core/models/course.model';
import { Question } from '../../core/models/quiz.model';

@Component({
  selector: 'app-quiz',
  standalone: true,
  imports: [CommonModule],
  templateUrl: './quiz.html',
  styleUrls: ['./quiz.css'],
})
export class QuizComponent implements OnInit {
  course: Course | null = null;
  questions: Question[] = [];
  currentQuestionIndex = 0;
  userAnswers: Map<number, number> = new Map(); // questionId => selectedOptionId

  isLoading = true;
  isSubmitting = false;
  errorMessage = '';
  quizCompleted = false;
  score = 0;
  passed = false;
  Math = Math; // Expose Math to template

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private courseService: CourseService,
    private toastService: ToastService,
  ) {}

  ngOnInit(): void {
    this.route.paramMap.subscribe((params) => {
      const id = params.get('id');
      if (id) {
        this.loadQuiz(+id);
      } else {
        this.toastService.error('Course ID not found.');
        this.router.navigate(['/dashboard/my-courses']);
      }
    });
  }

  loadQuiz(courseId: number): void {
    this.isLoading = true;

    this.courseService.getCourseById(courseId).subscribe({
      next: (course) => {
        this.course = course;

        this.courseService.getCourseQuestions(courseId).subscribe({
          next: (questions) => {
            this.questions = questions;
            this.isLoading = false;

            if (this.questions.length === 0) {
              this.errorMessage = 'No questions available for this assessment.';
            }
          },
          error: () => {
            this.errorMessage = 'Failed to load questions.';
            this.isLoading = false;
          },
        });
      },
      error: () => {
        this.errorMessage = 'Failed to load assessment details.';
        this.isLoading = false;
      },
    });
  }

  get currentQuestion(): Question | null {
    return this.questions[this.currentQuestionIndex] || null;
  }

  get isLastQuestion(): boolean {
    return this.currentQuestionIndex === this.questions.length - 1;
  }

  get progressPercentage(): number {
    if (this.questions.length === 0) return 0;
    return Math.round(((this.currentQuestionIndex + 1) / this.questions.length) * 100);
  }

  selectAnswer(optionId: number): void {
    if (this.currentQuestion) {
      this.userAnswers.set(this.currentQuestion.id, optionId);
    }
  }

  isSelected(optionId: number): boolean {
    if (!this.currentQuestion) return false;
    return this.userAnswers.get(this.currentQuestion.id) === optionId;
  }

  nextQuestion(): void {
    if (this.currentQuestionIndex < this.questions.length - 1) {
      this.currentQuestionIndex++;
    }
  }

  previousQuestion(): void {
    if (this.currentQuestionIndex > 0) {
      this.currentQuestionIndex--;
    }
  }

  submitQuiz(): void {
    if (!this.course) return;

    const unanswered = this.questions.filter((q) => !this.userAnswers.has(q.id));
    if (unanswered.length > 0) {
      this.toastService.error(
        `Please answer all questions. ${unanswered.length} question(s) remaining.`,
      );
      return;
    }

    let correctAnswers = 0;
    this.questions.forEach((question) => {
      const selectedOptionId = this.userAnswers.get(question.id);
      const selectedOption = question.options.find((opt) => opt.id === selectedOptionId);

      if (selectedOption && selectedOption.is_correct) {
        correctAnswers++;
      }
    });

    this.score = Math.round((correctAnswers / this.questions.length) * 100);
    this.passed = this.score >= 80;

    this.isSubmitting = true;

    this.courseService.submitQuiz({ course_id: this.course.id, score: this.score }).subscribe({
      next: () => {
        this.quizCompleted = true;
        this.isSubmitting = false;

        if (this.passed) {
          this.toastService.success(`Congratulations! You passed with ${this.score}%`);
        } else {
          this.toastService.error(`You scored ${this.score}%. You need 80% to pass.`);
        }
      },
      error: (err) => {
        this.toastService.error('Failed to submit quiz.');
        console.error(err);
        this.isSubmitting = false;
      },
    });
  }

  retakeQuiz(): void {
    this.currentQuestionIndex = 0;
    this.userAnswers.clear();
    this.quizCompleted = false;
    this.score = 0;
    this.passed = false;
  }

  goBack(): void {
    this.router.navigate(['/dashboard/my-courses']);
  }
}
