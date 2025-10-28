import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router } from '@angular/router';
import { AuthService } from '../../service/auth.service';
import { ToastService } from '../../service/toast.service';
import { FormsModule } from '@angular/forms';
import { HttpErrorResponse } from '@angular/common/http';

@Component({
  selector: 'app-course-player',
  standalone: true,
  imports: [CommonModule, FormsModule],
  templateUrl: './course-player.component.html',
  styleUrls: ['./course-player.component.css']
})
export class CoursePlayerComponent implements OnInit {
  courseId: number | null = null;
  course: any | null = null;
  currentLessonIndex: number = 0;
  currentQuestionIndex: number = 0;
  selectedOptionId: number | null = null;
  isLoading = true;
  errorMessage = '';

  constructor(
    private authService: AuthService,
    private toastService: ToastService,
    private router: Router,
    private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.route.paramMap.subscribe(params => {
      const id = params.get('id');
      if (id) {
        this.courseId = +id;
        this.loadCourseContent();
      } else {
        this.toastService.error('Course ID not found in route.');
        this.router.navigate(['/dashboard']); // Redirect to user dashboard
      }
    });
  }

  loadCourseContent(): void {
    if (this.courseId === null) return;

    this.isLoading = true;
    this.errorMessage = '';
    this.authService.getCourseContentForUser(this.courseId).subscribe({
      next: (data: any) => {
        this.course = data;
        this.isLoading = false;
        // Initialize progress or find last saved progress
        this.initializeProgress();
      },
      error: (err: HttpErrorResponse) => {
        console.error('Failed to load course content', err);
        this.errorMessage = 'Error loading course content: ' + (err.error?.message || err.message);
        this.toastService.error(this.errorMessage);
        this.isLoading = false;
      }
    });
  }

  initializeProgress(): void {
    // Logic to find user's last progress
    // For now, start from the beginning
    this.currentLessonIndex = 0;
    this.currentQuestionIndex = 0;
    this.selectedOptionId = null;
  }

  get currentLesson(): any {
    return this.course?.lessons[this.currentLessonIndex];
  }

  get currentQuestion(): any {
    return this.currentLesson?.questions[this.currentQuestionIndex];
  }

  nextLesson(): void {
    if (this.course && this.currentLessonIndex < this.course.lessons.length - 1) {
      this.currentLessonIndex++;
      this.currentQuestionIndex = 0;
      this.selectedOptionId = null;
      this.updateLessonProgress('in_progress');
    } else if (this.course && this.currentLessonIndex === this.course.lessons.length - 1) {
      // Last lesson, check if all questions are answered and mark course completed
      this.markCourseCompleted();
    }
  }

  prevLesson(): void {
    if (this.currentLessonIndex > 0) {
      this.currentLessonIndex--;
      this.currentQuestionIndex = 0;
      this.selectedOptionId = null;
      this.updateLessonProgress('in_progress');
    }
  }

  selectOption(optionId: number): void {
    this.selectedOptionId = optionId;
  }

  submitAnswer(): void {
    if (this.courseId === null || this.currentQuestion === null || this.selectedOptionId === null) {
      this.toastService.info('Please select an option before submitting.');
      return;
    }

    const answerData = {
      question_id: this.currentQuestion.id,
      selected_option_id: this.selectedOptionId
    };

    this.authService.submitAnswer(answerData).subscribe({
      next: (response: any) => {
        this.toastService.success(response.message || 'Answer submitted!');
        // Update the local question state with the user's answer
        if (this.currentQuestion) {
          this.currentQuestion.user_selected_option_id = this.selectedOptionId;
          this.currentQuestion.is_correct = response.is_correct; // Assuming API returns this
        }
        this.selectedOptionId = null; // Clear selection
        this.moveToNextQuestionOrLesson();
      },
      error: (err: HttpErrorResponse) => {
        console.error('Failed to submit answer', err);
        this.toastService.error('Error submitting answer: ' + (err.error?.message || err.message));
      }
    });
  }

  moveToNextQuestionOrLesson(): void {
    if (this.currentLesson && this.currentQuestionIndex < this.currentLesson.questions.length - 1) {
      this.currentQuestionIndex++;
    } else if (this.currentLesson && this.currentLessonIndex < this.course!.lessons.length - 1) {
      // All questions in current lesson answered, move to next lesson
      this.updateLessonProgress('completed');
      this.nextLesson();
    } else {
      // All lessons and questions completed
      this.updateLessonProgress('completed');
      this.markCourseCompleted();
    }
  }

  updateLessonProgress(status: 'not_started' | 'in_progress' | 'completed'): void {
    if (this.courseId === null || this.currentLesson === null) return;

    const progressData = {
      lesson_id: this.currentLesson.id,
      status: status
    };

    this.authService.updateLessonProgress(progressData).subscribe({
      next: () => {
        // Optionally update local state or show a subtle toast
      },
      error: (err: HttpErrorResponse) => {
        console.error('Failed to update lesson progress', err);
      }
    });
  }

  markCourseCompleted(): void {
    if (this.courseId === null) return;

    // Calculate score (simple example: percentage of correct answers)
    let totalQuestions = 0;
    let correctAnswers = 0;

    this.course?.lessons.forEach((lesson: any) => {
      lesson.questions.forEach((question: any) => {
        totalQuestions++;
        // Assuming question.is_correct is set after submitAnswer
        if (question.is_correct) {
          correctAnswers++;
        }
      });
    });

    const score = totalQuestions > 0 ? (correctAnswers / totalQuestions) * 100 : 0;

    const progressData = {
      course_id: this.courseId,
      status: 'completed',
      score: score
    };

    this.authService.updateCourseProgress(progressData).subscribe({
      next: () => {
        this.toastService.success('Course completed! Your score: ' + score.toFixed(2) + '%');
        this.router.navigate(['/dashboard']); // Redirect to user dashboard
      },
      error: (err: HttpErrorResponse) => {
        console.error('Failed to mark course completed', err);
        this.toastService.error('Error completing course: ' + (err.error?.message || err.message));
      }
    });
  }
}