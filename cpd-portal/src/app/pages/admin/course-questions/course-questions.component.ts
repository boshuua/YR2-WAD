import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router } from '@angular/router';
import { FormBuilder, FormGroup, FormArray, Validators, ReactiveFormsModule } from '@angular/forms';
import { CourseService } from '../../../core/services/course.service';
import { ToastService } from '../../../core/services/toast.service';
import { ConfirmModalComponent } from '../../../shared/components/confirm-modal/confirm-modal.component';
import { Question } from '../../../core/models/quiz.model';

@Component({
  selector: 'app-course-questions',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, ConfirmModalComponent],
  templateUrl: './course-questions.component.html',
  styleUrls: ['./course-questions.component.css'],
})
export class CourseQuestionsComponent implements OnInit {
  courseId!: number;
  courseName = '';
  questions: Question[] = [];
  isLoading = true;
  errorMessage = '';

  questionForm!: FormGroup;
  showAddForm = false;

  showDeleteConfirmModal = false;
  questionToDeleteId: number | null = null;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private courseService: CourseService,
    private toastService: ToastService,
    private fb: FormBuilder,
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
      options: this.fb.array([this.createOption(), this.createOption()]),
    });

    this.questionForm
      .get('question_type')
      ?.valueChanges.subscribe((type: Question['question_type']) => {
        this.onQuestionTypeChange(type);
      });
  }

  createOption(): FormGroup {
    return this.fb.group({
      option_text: ['', Validators.required],
      is_correct: [false],
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

  onQuestionTypeChange(type: Question['question_type']): void {
    const currentOptions = this.options.length;

    if (type === 'true_false') {
      this.options.clear();
      this.options.push(
        this.fb.group({ option_text: ['True', Validators.required], is_correct: [false] }),
      );
      this.options.push(
        this.fb.group({ option_text: ['False', Validators.required], is_correct: [false] }),
      );
    } else if (type === 'multiple_choice' && currentOptions === 2) {
      const firstOption = this.options.at(0);
      if (
        firstOption.get('option_text')?.value === 'True' ||
        firstOption.get('option_text')?.value === 'False'
      ) {
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
    this.courseService.getCourseById(this.courseId).subscribe({
      next: (data) => {
        this.courseName = data.title;
      },
      error: (err) => {
        console.error('Error loading course details:', err);
        this.toastService.error('Error loading course details');
      },
    });
  }

  loadQuestions(): void {
    this.isLoading = true;
    this.errorMessage = '';
    this.courseService.getCourseQuestions(this.courseId).subscribe({
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
      },
    });
  }

  toggleAddForm(): void {
    this.showAddForm = !this.showAddForm;
    if (!this.showAddForm) {
      this.questionForm.reset({ question_text: '', question_type: 'multiple_choice' });
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

    const hasCorrect = (this.options.value as { option_text: string; is_correct: boolean }[]).some(
      (opt) => opt.is_correct,
    );
    if (!hasCorrect) {
      this.toastService.error('At least one option must be marked as correct');
      return;
    }

    const questionData = {
      course_id: this.courseId,
      ...this.questionForm.value,
    };

    this.courseService.adminCreateQuestion(questionData).subscribe({
      next: () => {
        this.toastService.success('Question created successfully!');
        this.toggleAddForm();
        this.loadQuestions();
      },
      error: (err) => {
        this.toastService.error('Error creating question: ' + (err.error?.message || err.message));
      },
    });
  }

  promptDeleteQuestion(questionId: number): void {
    this.questionToDeleteId = questionId;
    this.showDeleteConfirmModal = true;
  }

  confirmDelete(): void {
    if (this.questionToDeleteId !== null) {
      this.courseService.adminDeleteQuestion(this.questionToDeleteId).subscribe({
        next: () => {
          this.toastService.success('Question deleted successfully!');
          this.loadQuestions();
          this.closeDeleteModal();
        },
        error: (err) => {
          this.toastService.error(
            'Error deleting question: ' + (err.error?.message || err.message),
          );
          this.closeDeleteModal();
        },
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
