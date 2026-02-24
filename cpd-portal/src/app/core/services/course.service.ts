import { Injectable } from '@angular/core';
import { HttpClient, HttpHeaders } from '@angular/common/http';
import { Observable, defer, map, switchMap, tap } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Course, CourseSchedulePayload, CourseType } from '../models/course.model';
import { ApiResponse } from '../models/api-response.model';
import { Lesson } from '../models/lesson.model';
import { Question, QuizSubmission } from '../models/quiz.model';
import { UserCourse, ActivityLog } from '../models/dashboard.model';

@Injectable({
  providedIn: 'root',
})
export class CourseService {
  private apiUrl = environment.apiUrl;

  constructor(private http: HttpClient) {}

  private ensureCsrfToken(): Observable<string> {
    return defer(() => {
      const existing = sessionStorage.getItem('csrfToken');
      if (existing && existing.trim().length > 0) {
        return defer(() => Promise.resolve(existing));
      }
      return this.http
        .post<{ csrfToken: string }>(`${this.apiUrl}/csrf.php`, {}, { withCredentials: true })
        .pipe(
          map((res) => res.csrfToken),
          tap((token) => sessionStorage.setItem('csrfToken', token)),
        );
    });
  }

  // --- Courses ---

  getCourses(type: CourseType | 'all' | 'library' = 'all'): Observable<Course[]> {
    return this.http.get<Course[]>(`${this.apiUrl}/get_courses.php?type=${type}`, {
      withCredentials: true,
    });
  }

  getUpcomingCourses(): Observable<Course[]> {
    return this.getCourses('upcoming');
  }

  getCourseById(courseId: number): Observable<Course> {
    return this.http.get<Course>(`${this.apiUrl}/get_course_by_id.php?id=${courseId}`, {
      withCredentials: true,
    });
  }

  adminCreateCourse(courseData: Partial<Course>): Observable<ApiResponse> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.post<ApiResponse>(`${this.apiUrl}/admin_create_course.php`, courseData, {
          withCredentials: true,
          headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken }),
        }),
      ),
    );
  }

  adminCreateCourseFromTemplate(templateData: CourseSchedulePayload): Observable<ApiResponse> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.post<ApiResponse>(
          `${this.apiUrl}/admin_create_course_from_template.php`,
          templateData,
          {
            withCredentials: true,
            headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken }),
          },
        ),
      ),
    );
  }

  adminUpdateCourse(courseId: number, courseData: Partial<Course>): Observable<ApiResponse> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.put<ApiResponse>(
          `${this.apiUrl}/admin_update_course.php?id=${courseId}`,
          courseData,
          {
            withCredentials: true,
            headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken }),
          },
        ),
      ),
    );
  }

  adminDeleteCourse(courseId: number): Observable<ApiResponse> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.delete<ApiResponse>(`${this.apiUrl}/admin_delete_course.php?id=${courseId}`, {
          withCredentials: true,
          headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken }),
        }),
      ),
    );
  }

  // --- Lessons ---

  getCourseLessons(courseId: number): Observable<Lesson[]> {
    return this.http.get<Lesson[]>(`${this.apiUrl}/get_course_lessons.php?course_id=${courseId}`, {
      withCredentials: true,
    });
  }

  saveLessonProgress(courseId: number, lessonId: number): Observable<ApiResponse> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.post<ApiResponse>(
          `${this.apiUrl}/save_lesson_progress.php`,
          { course_id: courseId, lesson_id: lessonId },
          {
            withCredentials: true,
            headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken }),
          },
        ),
      ),
    );
  }

  // --- User Enrolment & Progress ---

  getUserCourses(): Observable<UserCourse[]> {
    return this.http.get<UserCourse[]>(`${this.apiUrl}/get_user_courses.php`, {
      withCredentials: true,
    });
  }

  enrollCourse(courseId: number): Observable<ApiResponse> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.post<ApiResponse>(
          `${this.apiUrl}/enroll_course.php`,
          { course_id: courseId },
          {
            withCredentials: true,
            headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken }),
          },
        ),
      ),
    );
  }

  updateCourseProgress(progressData: {
    course_id: number;
    status?: string;
  }): Observable<ApiResponse> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.post<ApiResponse>(`${this.apiUrl}/update_course_progress.php`, progressData, {
          withCredentials: true,
          headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken }),
        }),
      ),
    );
  }

  completeCourse(courseId: number, hoursCompleted: number): Observable<ApiResponse> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.post<ApiResponse>(
          `${this.apiUrl}/complete_course.php`,
          { course_id: courseId, hours_completed: hoursCompleted },
          {
            withCredentials: true,
            headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken }),
          },
        ),
      ),
    );
  }

  // --- Quiz / Questions ---

  getCourseQuestions(courseId: number): Observable<Question[]> {
    return this.http.get<Question[]>(
      `${this.apiUrl}/get_course_questions.php?course_id=${courseId}`,
      { withCredentials: true },
    );
  }

  adminCreateQuestion(
    questionData: Omit<Question, 'id'> & { course_id: number },
  ): Observable<ApiResponse> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.post<ApiResponse>(`${this.apiUrl}/admin_create_question.php`, questionData, {
          withCredentials: true,
          headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken }),
        }),
      ),
    );
  }

  adminDeleteQuestion(questionId: number): Observable<ApiResponse> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.delete<ApiResponse>(`${this.apiUrl}/admin_delete_question.php?id=${questionId}`, {
          withCredentials: true,
          headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken }),
        }),
      ),
    );
  }

  submitQuiz(submission: QuizSubmission): Observable<ApiResponse> {
    return this.ensureCsrfToken().pipe(
      switchMap((csrfToken) =>
        this.http.post<ApiResponse>(`${this.apiUrl}/submit_quiz.php`, submission, {
          withCredentials: true,
          headers: new HttpHeaders({ 'X-CSRF-Token': csrfToken }),
        }),
      ),
    );
  }

  // --- Activity Log ---

  getActivityLog(limit: number = 20): Observable<ActivityLog[]> {
    return this.http.get<ActivityLog[]>(`${this.apiUrl}/get_activity_log.php?limit=${limit}`, {
      withCredentials: true,
    });
  }
}
