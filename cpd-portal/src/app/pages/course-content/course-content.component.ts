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
  lessons: any[] = [];
  assessmentQuestions: any[] = [];
  
  isLoading = true;
  errorMessage = '';
  isEnrolled = false;
  isCheckingEnrollment = true;

  // Navigation State
  currentView: 'lesson' | 'checkpoint' | 'assessment' | 'completed' = 'lesson';
  currentLessonIndex = 0;
  
  // Quiz/Assessment state
  userAnswers: Map<number, number[]> = new Map();
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
          this.toastService.error('You must enroll in this course first.');
          this.router.navigate(['/dashboard/my-courses']);
          return;
        }

        if (course.user_progress_status === 'completed') {
          this.currentView = 'completed';
          this.quizScore = course.score;
        }

        this.isEnrolled = true;
        this.loadCourseData(courseId);
      },
      error: () => {
        this.toastService.error('Failed to verify enrollment status.');
        this.router.navigate(['/dashboard/my-courses']);
      }
    });
  }

  loadCourseData(courseId: number): void {
    this.isLoading = true;
    
    // Load Course Basic Info
    this.authService.getCourseById(courseId).subscribe({
      next: (data) => {
        this.course = data;
        
        // Load Lessons (Training Content)
        this.authService.getCourseLessons(courseId).subscribe({
          next: (lessons) => {
            this.lessons = lessons;
            
            // Load Final Assessment Questions
            this.authService.getCourseQuestions(courseId).subscribe({
              next: (questions) => {
                this.assessmentQuestions = questions;
                this.isLoading = false;
              }
            });
          }
        });
      },
      error: () => {
        this.errorMessage = 'Failed to load course details.';
        this.isLoading = false;
      }
    });
  }

  get currentLesson() {
    return this.lessons[this.currentLessonIndex];
  }

  nextSection(): void {
    const lesson = this.currentLesson;
    
    if (this.currentView === 'lesson' && lesson.checkpoint_quiz?.length > 0) {
      // Move to checkpoint quiz
      this.currentView = 'checkpoint';
      this.resetQuizState();
    } else {
      // Move to next lesson or assessment
      if (this.currentLessonIndex < this.lessons.length - 1) {
        this.currentLessonIndex++;
        this.currentView = 'lesson';
      } else {
        this.currentView = 'assessment';
        this.resetQuizState();
      }
    }
  }

  resetQuizState(): void {
    this.userAnswers.clear();
    this.quizSubmitted = false;
    this.quizScore = null;
  }

  toggleAnswer(questionId: number, optionId: number, isSingle: boolean = true): void {
    if (this.quizSubmitted) return;
    this.userAnswers.set(questionId, [optionId]);
  }

  isOptionSelected(questionId: number, optionId: number): boolean {
    return this.userAnswers.get(questionId)?.includes(optionId) || false;
  }

  submitCheckpoint(): void {
    const questions = this.currentLesson.checkpoint_quiz;
    if (this.calculateScore(questions) >= 100) {
      this.toastService.success('Checkpoint passed!');
      this.nextSection();
    } else {
      this.toastService.error('Incorrect answer. Please review the section and try again.');
      this.currentView = 'lesson';
    }
  }

  submitFinalAssessment(): void {
    const score = this.calculateScore(this.assessmentQuestions);
    this.quizScore = score;
    this.quizSubmitted = true;

    if (score >= 80) {
      this.authService.submitQuiz(this.course.id, score).subscribe({
        next: () => {
          this.toastService.success('Congratulations! You have completed your annual assessment.');
          this.currentView = 'completed';
        }
      });
    } else {
      this.toastService.error('Assessment failed. You need 80% to pass.');
    }
  }

  private calculateScore(questions: any[]): number {
    let correct = 0;
    questions.forEach(q => {
      const userAns = this.userAnswers.get(q.id);
      const correctOpts = q.options.filter((o: any) => o.is_correct).map((o: any) => o.id);
      if (userAns && userAns[0] === correctOpts[0]) correct++;
    });
    return Math.round((correct / questions.length) * 100);
  }

  goBack(): void {
    this.router.navigate(['/dashboard/my-courses']);
  }
}