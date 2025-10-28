import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { AuthService } from '../../../service/auth.service';
import { ToastService } from '../../../service/toast.service';
import { ConfirmModalComponent } from '../../../components/confirm-modal/confirm-modal.component';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-question-management',
  standalone: true,
  imports: [CommonModule, ConfirmModalComponent, FormsModule],
  templateUrl: './question-management.component.html',
  styleUrls: ['./question-management.component.css']
})
export class QuestionManagementComponent implements OnInit {
  courseId: number | null = null;
  lessonId: number | null = null;
  questions: any[] = [];
  isLoading = true;
  errorMessage = '';
  noQuestionsFound: boolean = false;

  showAddEditQuestionModal = false;
  currentQuestion: any | null = null; // For editing
  questionFormText: string = '';
  questionFormOptions: { option_text: string, is_correct: boolean }[] = [];

  showDeleteConfirmModal = false;
  questionToDeleteId: number | null = null;
  questionToDeleteText: string = '';

  constructor(
    private authService: AuthService,
    private toastService: ToastService,
    private router: Router,
    private route: ActivatedRoute
  ) { }

  ngOnInit(): void {
    this.route.paramMap.subscribe(params => {
      const courseId = params.get('courseId');
      const lessonId = params.get('lessonId');

      if (courseId && lessonId) {
        this.courseId = +courseId;
        this.lessonId = +lessonId;
        this.loadQuestions();
      } else {
        this.toastService.error('Course ID or Lesson ID not found in route.');
        this.router.navigate(['/admin/courses']);
      }
    });
  }

  loadQuestions(): void {
    if (this.lessonId === null) return;

    this.isLoading = true;
    this.errorMessage = '';
    this.noQuestionsFound = false;
    this.authService.getQuestionsByLesson(this.lessonId).subscribe({
      next: (data) => {
        this.questions = data;
        this.isLoading = false;
        if (this.questions.length === 0) {
          this.noQuestionsFound = true;
        }
      },
      error: (err) => {
        console.error('Failed to load questions', err);
        if (err.status === 404 && err.error?.message === 'No questions found for this lesson.') {
          this.questions = [];
          this.errorMessage = '';
          this.noQuestionsFound = true;
        } else {
          this.errorMessage = 'Error loading questions: ' + (err.error?.message || err.message);
          this.toastService.error(this.errorMessage);
        }
        this.isLoading = false;
      }
    });
  }

  openAddQuestionModal(): void {
    this.currentQuestion = null;
    this.questionFormText = '';
    this.questionFormOptions = [{ option_text: '', is_correct: false }]; // Start with one empty option
    this.showAddEditQuestionModal = true;
  }

  openEditQuestionModal(question: any): void {
    this.currentQuestion = { ...question };
    this.questionFormText = question.question_text;
    this.questionFormOptions = question.options.map((opt: any) => ({ ...opt })); // Deep copy options
    this.showAddEditQuestionModal = true;
  }

  closeAddEditQuestionModal(): void {
    this.showAddEditQuestionModal = false;
    this.currentQuestion = null;
    this.questionFormText = '';
    this.questionFormOptions = [];
  }

  addOption(): void {
    this.questionFormOptions.push({ option_text: '', is_correct: false });
  }

  removeOption(index: number): void {
    this.questionFormOptions.splice(index, 1);
  }

  toggleCorrectOption(index: number): void {
    // Ensure only one option can be correct for multiple choice
    this.questionFormOptions.forEach((option, i) => {
      if (i === index) {
        option.is_correct = !option.is_correct;
      } else {
        option.is_correct = false;
      }
    });
  }

  saveQuestion(): void {
    if (this.lessonId === null) return;

    // Basic validation for options
    if (this.questionFormOptions.length === 0 || this.questionFormOptions.some(opt => opt.option_text.trim() === '')) {
      this.toastService.info('Please add at least one option and ensure all options have text.');
      return;
    }
    if (!this.questionFormOptions.some(opt => opt.is_correct)) {
      this.toastService.info('Please mark at least one option as correct.');
      return;
    }

    const questionData = {
      lesson_id: this.lessonId,
      question_text: this.questionFormText,
      question_type: 'multiple_choice', // Hardcoded for now
      options: this.questionFormOptions
    };

    if (this.currentQuestion) {
      // Update existing question
      this.authService.adminUpdateQuestion(this.currentQuestion.id, questionData).subscribe({
        next: () => {
          this.toastService.success('Question updated successfully!');
          this.loadQuestions();
          this.closeAddEditQuestionModal();
        },
        error: (err) => {
          console.error('Failed to update question', err);
          this.toastService.error('Error updating question: ' + (err.error?.message || err.message));
        }
      });
    } else {
      // Create new question
      this.authService.adminCreateQuestion(questionData).subscribe({
        next: () => {
          this.toastService.success('Question created successfully!');
          this.loadQuestions();
          this.closeAddEditQuestionModal();
        },
        error: (err) => {
          console.error('Failed to create question', err);
          this.toastService.error('Error creating question: ' + (err.error?.message || err.message));
        }
      });
    }
  }

  promptDeleteQuestion(questionId: number, questionText: string): void {
    this.questionToDeleteId = questionId;
    this.questionToDeleteText = questionText;
    this.showDeleteConfirmModal = true;
  }

  confirmDeleteQuestion(): void {
    if (this.questionToDeleteId !== null) {
      this.authService.adminDeleteQuestion(this.questionToDeleteId).subscribe({
        next: (response) => {
          this.toastService.success(response.message || 'Question deleted successfully!');
          this.loadQuestions();
          this.closeDeleteModal();
        },
        error: (err) => {
          console.error('Failed to delete question', err);
          this.toastService.error('Error deleting question: ' + (err.error?.message || err.message));
          this.closeDeleteModal();
        }
      });
    } else {
      console.error("Question ID to delete is null");
      this.closeDeleteModal();
    }
  }

  cancelDeleteQuestion(): void {
    this.closeDeleteModal();
  }

  private closeDeleteModal(): void {
    this.showDeleteConfirmModal = false;
    this.questionToDeleteId = null;
    this.questionToDeleteText = '';
  }
}