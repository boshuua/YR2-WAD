import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router } from '@angular/router';
import { FormBuilder, FormGroup, FormArray, Validators, ReactiveFormsModule } from '@angular/forms';
import { AuthService } from '../../../core/services/auth.service';
import { ToastService } from '../../../core/services/toast.service';
import { ConfirmModalComponent } from '../../../shared/components/confirm-modal/confirm-modal.component';

@Component({
  selector: 'app-course-questions',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, ConfirmModalComponent],
  templateUrl: './course-questions.component.html',
  styleUrls: ['./course-questions.component.css']
})
export class CourseQuestionsComponent implements OnInit {
  courseId!: number;
  courseName: string = '';
  questions: any[] = [];
  isLoading = true;
  errorMessage = '';

  questionForm!: FormGroup;
  showAddForm = false;

  showDeleteConfirmModal = false;
  questionToDeleteId: number | null = null;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private authService: AuthService,
    private toastService: ToastService,
    private fb: FormBuilder
  ) {}

  ngOnInit(): void {
    this.courseId = Number(this.route.snapshot.paramMap.get('id'));
    this.initForm();
    this.loadCourse();
    this.loadQuestions();
  }

  initForm(): void {
    this.questionForm = this.fb.group({
      question_text: ['', Validators.required],
      question_type: ['multiple_choice', Validators.required],
      options: this.fb.array([
        this.createOption(),
        this.createOption()
      ])
    });

    // Listen for question type changes
    this.questionForm.get('question_type')?.valueChanges.subscribe(type => {
      this.onQuestionTypeChange(type);
    });
  }

  createOption(): FormGroup {
    return this.fb.group({
      option_text: ['', Validators.required],
      is_correct: [false]
    });
  }

  get options(): FormArray {
    return this.questionForm.get('options') as FormArray;
  }

  addOption(): void {
    if (this.options.length < 6) {
      this.options.push(this.createOption());
    }
  }

  removeOption(index: number): void {
    if (this.options.length > 2) {
      this.options.removeAt(index);
    }
  }

  onQuestionTypeChange(type: string): void {
    const currentOptions = this.options.length;

    if (type === 'true_false') {
      // For true/false, set exactly 2 options with True/False text
      this.options.clear();

      const trueOption = this.fb.group({
        option_text: ['True', Validators.required],
        is_correct: [false]
      });

      const falseOption = this.fb.group({
        option_text: ['False', Validators.required],
        is_correct: [false]
      });

      this.options.push(trueOption);
      this.options.push(falseOption);
    } else if (type === 'multiple_choice' && currentOptions === 2) {
      // If switching from true/false to multiple choice, reset options
      const firstOption = this.options.at(0);
      const secondOption = this.options.at(1);

      // Only reset if they still have True/False text
      if (firstOption.get('option_text')?.value === 'True' ||
          firstOption.get('option_text')?.value === 'False') {
        this.options.clear();
        this.options.push(this.createOption());
        this.options.push(this.createOption());
      }
    }
  }

  isTrueFalse(): boolean {
    return this.questionForm.get('question_type')?.value === 'true_false';
  }

  loadCourse(): void {
    this.authService.getCourseById(this.courseId).subscribe({
      next: (data) => {
        this.courseName = data.title;
      },
      error: (err) => {
        console.error('Error loading course details:', err);
        this.toastService.error('Error loading course details');
      }
    });
  }

  loadQuestions(): void {
    this.isLoading = true;
    this.errorMessage = '';
    this.authService.getCourseQuestions(this.courseId).subscribe({
      next: (data) => {
        this.questions = Array.isArray(data) ? data : [];
        this.isLoading = false;
      },
      error: (err) => {
        console.error('Error loading questions:', err);
        this.questions = [];
        if (err.status !== 404) {
          this.errorMessage = 'Error loading questions';
          this.toastService.error(this.errorMessage);
        }
        this.isLoading = false;
      }
    });
  }

  toggleAddForm(): void {
    this.showAddForm = !this.showAddForm;
    if (!this.showAddForm) {
      this.questionForm.reset({
        question_text: '',
        question_type: 'multiple_choice'
      });
      this.options.clear();
      this.options.push(this.createOption());
      this.options.push(this.createOption());
    }
  }

  onSubmit(): void {
    if (this.questionForm.invalid) {
      this.toastService.error('Please fill all required fields');
      return;
    }

    const hasCorrect = this.options.value.some((opt: any) => opt.is_correct);
    if (!hasCorrect) {
      this.toastService.error('At least one option must be marked as correct');
      return;
    }

    const questionData = {
      course_id: this.courseId,
      ...this.questionForm.value
    };

    this.authService.adminCreateQuestion(questionData).subscribe({
      next: (response) => {
        this.toastService.success('Question created successfully!');
        this.toggleAddForm();
        this.loadQuestions();
      },
      error: (err) => {
        this.toastService.error('Error creating question: ' + (err.error?.message || err.message));
      }
    });
  }

  promptDeleteQuestion(questionId: number): void {
    this.questionToDeleteId = questionId;
    this.showDeleteConfirmModal = true;
  }

  confirmDelete(): void {
    if (this.questionToDeleteId !== null) {
      this.authService.adminDeleteQuestion(this.questionToDeleteId).subscribe({
        next: (response) => {
          this.toastService.success('Question deleted successfully!');
          this.loadQuestions();
          this.closeDeleteModal();
        },
        error: (err) => {
          this.toastService.error('Error deleting question: ' + (err.error?.message || err.message));
          this.closeDeleteModal();
        }
      });
    }
  }

  cancelDelete(): void {
    this.closeDeleteModal();
  }

  private closeDeleteModal(): void {
    this.showDeleteConfirmModal = false;
    this.questionToDeleteId = null;
  }

  goBack(): void {
    this.router.navigate(['/admin/courses']);
  }
}
